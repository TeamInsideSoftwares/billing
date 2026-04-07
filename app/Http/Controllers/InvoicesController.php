<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoicesController extends Controller
{
    public function invoices()
    {
        $clients = Client::orderBy('business_name')->get();
        $selectedClientId = request('clientid');

        $invoices = collect();

        if ($selectedClientId) {
            $invoices = Invoice::with('client', 'payments')
                ->where('clientid', $selectedClientId)
                ->latest()
                ->get()
                ->map(function ($invoice) {
                    $amountPaid = $invoice->amount_paid ?? 0;
                    $balanceDue = $invoice->balance_due ?? $invoice->grand_total;
                    $currency = $invoice->client->currency ?? 'INR';

                    return [
                        'record_id' => $invoice->invoiceid,
                        'number' => $invoice->invoice_number ?? 'INV-' . str_pad($invoice->invoiceid, 4, '0', STR_PAD_LEFT),
                        'invoice_type' => ucfirst($invoice->invoice_type ?? 'proforma'),
                        'invoice_for' => ucfirst(str_replace('_', ' ', $invoice->invoice_for ?? 'without orders')),
                        'amount' => $currency . ' ' . number_format($invoice->grand_total ?? 0, 2),
                        'amount_paid' => $currency . ' ' . number_format($amountPaid, 2),
                        'balance_due' => $currency . ' ' . number_format($balanceDue, 2),
                        'status' => $invoice->status ?? 'draft',
                        'payment_status' => $balanceDue <= 0 ? 'paid' : ($amountPaid > 0 ? 'partial' : 'pending'),
                    ];
                });
        }

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'invoices' => $invoices,
                'selectedClientId' => $selectedClientId,
            ]);
        }

        return view('invoices.index', [
            'title' => 'Invoices',
            'clients' => $clients,
            'invoices' => $invoices,
            'selectedClientId' => $selectedClientId,
        ]);
    }

    public function invoicesCreate(): View
    {
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';

        return view('invoices.create', [
            'title' => 'Create Invoice',
            'clients' => Client::orderBy('business_name')->get(),
            'services' => Service::with(['category', 'costings'])->orderBy('sequence')->orderBy('name')->get(),
            'taxes' => Tax::where('accountid', $accountid)->where('is_active', true)->orderByRaw('COALESCE(sequence, 999999), created_at DESC')->get(),
            'nextInvoiceNumber' => $this->generateInvoiceNumber(),
        ]);
    }

    public function getClientOrders(Request $request)
    {
        $clientId = $request->input('clientid');

        if (!$clientId) {
            return response()->json([]);
        }

        $orders = \App\Models\Order::where('clientid', $clientId)
            ->whereDoesntHave('invoices')
            ->with('client')
            ->orderBy('order_date', 'desc')
            ->get(['orderid', 'order_number', 'order_date', 'grand_total', 'status', 'clientid'])
            ->map(function ($order) {
                $currency = $order->client->currency ?? 'INR';

                return [
                    'orderid' => $order->orderid,
                    'order_number' => $order->order_number,
                    'order_date' => $order->order_date?->format('d M Y') ?? 'N/A',
                    'grand_total' => $order->grand_total ?? '0.00',
                    'currency' => $currency,
                    'status' => $order->status ?? 'draft',
                ];
            });

        return response()->json($orders);
    }

    public function getRenewalInvoices(Request $request)
    {
        $clientId = $request->input('clientid');

        if (!$clientId) {
            return response()->json([]);
        }

        $invoices = Invoice::where('clientid', $clientId)
            ->whereHas('items', function ($query) {
                $query->where('end_date', '<', now())
                    ->whereIn('frequency', ['monthly', 'yearly', 'quarterly', 'semi-annually']);
            })
            ->with('items', 'client')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($invoice) {
                $expiredCount = $invoice->items->filter(function ($item) {
                    return $item->end_date
                        && $item->end_date < now()
                        && in_array($item->frequency, ['monthly', 'yearly', 'quarterly', 'semi-annually'], true);
                })->count();

                $currency = $invoice->client->currency ?? 'INR';

                return [
                    'invoiceid' => $invoice->invoiceid,
                    'invoice_number' => $invoice->invoice_number,
                    'grand_total' => $invoice->grand_total ?? '0.00',
                    'currency' => $currency,
                    'total_items' => $invoice->items->count(),
                    'expired_items' => $expiredCount,
                ];
            })
            ->values();

        return response()->json($invoices);
    }

    public function getOrderItems(Request $request, $orderid)
    {
        $order = \App\Models\Order::with('items.item')->findOrFail($orderid);

        $items = $order->items->map(function ($item) {
            return [
                'itemid' => $item->itemid,
                'item_name' => $item->item_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $item->tax_rate,
                'duration' => $item->duration,
                'frequency' => $item->frequency,
                'no_of_users' => $item->no_of_users,
                'start_date' => $item->start_date?->format('Y-m-d'),
                'end_date' => $item->end_date?->format('Y-m-d'),
                'line_total' => $item->line_total,
            ];
        })->values();

        return response()->json([
            'order' => [
                'orderid' => $order->orderid,
                'order_number' => $order->order_number,
                'grand_total' => $order->grand_total,
            ],
            'items' => $items,
        ]);
    }

    public function getRenewalItems(Request $request, $invoiceid)
    {
        $invoice = Invoice::with('items')->findOrFail($invoiceid);

        $items = $invoice->items->map(function ($item) {
            $isExpired = $item->end_date
                && $item->end_date < now()
                && in_array($item->frequency, ['monthly', 'yearly', 'quarterly', 'semi-annually'], true);

            return [
                'itemid' => $item->itemid,
                'item_name' => $item->item_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $item->tax_rate,
                'duration' => $item->duration,
                'frequency' => $item->frequency,
                'no_of_users' => $item->no_of_users,
                'start_date' => $item->start_date?->format('Y-m-d'),
                'end_date' => $item->end_date?->format('Y-m-d'),
                'line_total' => $item->line_total,
                'is_expired' => $isExpired,
            ];
        })->values();

        return response()->json([
            'invoice' => [
                'invoiceid' => $invoice->invoiceid,
                'invoice_number' => $invoice->invoice_number,
                'grand_total' => $invoice->grand_total,
            ],
            'items' => $items,
        ]);
    }

    protected function generateInvoiceNumber(): string
    {
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';

        $billingDetail = \App\Models\AccountBillingDetail::where('accountid', $accountid)->first();

        if (!$billingDetail) {
            return 'INV-' . str_pad(Invoice::where('accountid', $accountid)->count() + 1, 4, '0', STR_PAD_LEFT);
        }

        return $billingDetail->serial_preview ?? 'INV-0001';
    }

    public function invoicesStore(Request $request)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'orderid' => 'nullable|exists:orders,orderid',
            'invoice_number' => 'required|string|unique:invoices,invoice_number',
            'invoice_type' => 'nullable|string|in:proforma,tax,receipt',
            'invoice_for' => 'required|string|in:orders,renewal,without_orders',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'currency_code' => 'nullable|string|max:10',
            'subtotal' => 'required|numeric|min:0',
            'tax_total' => 'required|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'items_data' => 'required|json',
            'accountid' => 'nullable|size:10',
        ]);

        $itemsData = json_decode($request->items_data, true);
        if (!is_array($itemsData) || empty($itemsData)) {
            throw ValidationException::withMessages([
                'items_data' => 'Add at least one invoice item before submitting.',
            ]);
        }

        if ($validated['invoice_for'] === 'orders') {
            if (empty($validated['orderid'])) {
                throw ValidationException::withMessages([
                    'orderid' => 'Select an order before creating an invoice from orders.',
                ]);
            }

            $order = \App\Models\Order::with('invoices')
                ->where('orderid', $validated['orderid'])
                ->where('clientid', $validated['clientid'])
                ->first();

            if (!$order) {
                throw ValidationException::withMessages([
                    'orderid' => 'The selected order does not belong to this client.',
                ]);
            }

            if ($order->invoices->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'orderid' => 'The selected order already has an invoice.',
                ]);
            }
        } else {
            $validated['orderid'] = null;
        }

        $user = auth()->user();
        $client = Client::findOrFail($validated['clientid']);
        $validated['accountid'] = $validated['accountid'] ?? ($user->accountid ?? 'ACC0000001');
        $validated['invoice_type'] = $validated['invoice_type'] ?? 'proforma';
        $validated['currency_code'] = $validated['currency_code'] ?? ($client->currency ?? 'INR');
        $validated['created_by'] = $user?->userid ?? $user?->id;
        unset($validated['items_data']);

        $subtotal = 0;
        $taxTotal = 0;
        $preparedItems = [];

        foreach ($itemsData as $index => $itemData) {
            $itemId = $itemData['itemid'] ?? null;
            $service = $itemId ? Service::find($itemId) : null;
            $quantity = (float) ($itemData['quantity'] ?? 0);
            $unitPrice = (float) ($itemData['unit_price'] ?? 0);
            $taxRate = (float) ($itemData['tax_rate'] ?? 0);
            $users = max(1, (int) ($itemData['no_of_users'] ?? 1));
            $lineTotal = (float) ($itemData['line_total'] ?? 0);

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'items_data' => 'Each invoice item must have a quantity greater than 0.',
                ]);
            }

            if ($unitPrice < 0 || $lineTotal < 0) {
                throw ValidationException::withMessages([
                    'items_data' => 'Item amounts cannot be negative.',
                ]);
            }

            $subtotal += $lineTotal;
            $taxTotal += $lineTotal * ($taxRate / 100);

            $preparedItems[] = [
                'invoiceid' => null,
                'itemid' => $itemId,
                'item_name' => $itemData['item_name'] ?? ($service?->name ?? 'Custom Item'),
                'item_description' => null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'duration' => $itemData['duration'] ?? null,
                'frequency' => $itemData['frequency'] ?? null,
                'no_of_users' => $users,
                'start_date' => $itemData['start_date'] ?? null,
                'end_date' => $itemData['end_date'] ?? null,
                'line_total' => $lineTotal,
                'sort_order' => $index + 1,
            ];
        }

        $grandTotal = $subtotal + $taxTotal;
        $validated['subtotal'] = round($subtotal, 2);
        $validated['tax_total'] = round($taxTotal, 2);
        $validated['grand_total'] = round($grandTotal, 2);
        $validated['amount_paid'] = 0;
        $validated['balance_due'] = round($grandTotal, 2);

        $invoice = null;

        DB::transaction(function () use ($validated, $preparedItems, &$invoice) {
            $invoice = Invoice::create($validated);

            foreach ($preparedItems as $itemData) {
                $itemData['invoiceid'] = $invoice->invoiceid;
                InvoiceItem::create($itemData);
            }
        });

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully with items.');
    }

    public function invoicesShow(Invoice $invoice): View
    {
        $invoice->load(['client', 'items.service', 'payments']);

        return view('invoices.show', ['title' => 'Invoice Details', 'invoice' => $invoice]);
    }

    public function invoicesEdit(Invoice $invoice): View
    {
        $invoice->load(['items.service']);
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';

        return view('invoices.edit', [
            'title' => 'Edit Invoice',
            'invoice' => $invoice,
            'clients' => Client::all(),
            'services' => Service::with(['category', 'costings'])->orderBy('sequence')->orderBy('name')->get(),
            'taxes' => Tax::where('accountid', $accountid)->where('is_active', true)->orderByRaw('COALESCE(sequence, 999999), created_at DESC')->get(),
            'items' => $invoice->items,
        ]);
    }

    public function invoicesUpdate(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'invoice_number' => 'required|string|unique:invoices,invoice_number,' . $invoice->invoiceid . ',invoiceid',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'items_data' => 'required|json',
        ]);

        $itemsData = json_decode($request->items_data, true);
        $subtotal = 0;
        $taxTotal = 0;

        foreach ($itemsData as $itemData) {
            $subtotal += $itemData['line_total'];
            $taxTotal += $itemData['line_total'] * ($itemData['tax_rate'] / 100);
        }

        $grandTotal = $subtotal + $taxTotal;

        $invoice->update([
            'clientid' => $validated['clientid'],
            'invoice_number' => $validated['invoice_number'],
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'],
            'status' => $validated['status'],
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'balance_due' => $grandTotal,
        ]);

        $invoice->items()->delete();

        foreach ($itemsData as $index => $itemData) {
            $service = Service::find($itemData['itemid']);
            InvoiceItem::create([
                'invoiceid' => $invoice->invoiceid,
                'itemid' => $itemData['itemid'],
                'item_name' => $service?->name ?? 'Custom Item',
                'item_description' => null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'tax_rate' => $itemData['tax_rate'],
                'line_total' => $itemData['line_total'],
                'sort_order' => $index + 1,
            ]);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function invoicesDestroy(Invoice $invoice)
    {
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }
}
