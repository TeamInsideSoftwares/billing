<?php

namespace App\Http\Controllers;

use App\Models\AccountBillingDetail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceItem;
use App\Models\Service;
use App\Models\Tax;
use App\Models\TermsCondition;
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

        $invoiceQuery = ProformaInvoice::with(['client', 'items', 'convertedTaxInvoice'])->latest();

        if ($selectedClientId) {
            $invoiceQuery->where('clientid', $selectedClientId);
        }

        $allInvoices = $invoiceQuery->get();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'invoices' => $allInvoices->map(function ($invoice) {
                    $amountPaid = (float) ($invoice->amount_paid ?? 0);
                    $balanceDue = (float) ($invoice->balance_due ?? $invoice->grand_total);
                    $currency = $invoice->client->currency ?? 'INR';

                    $paymentStatus = 'unpaid';
                    if ($balanceDue <= 0) {
                        $paymentStatus = 'paid';
                    } elseif ($amountPaid > 0) {
                        $paymentStatus = 'partially paid';
                    }

                    return [
                        'record_id' => $invoice->proformaid,
                        'number' => $invoice->invoice_number,
                        'title' => $invoice->invoice_title,
                        'clientid' => $invoice->clientid,
                        'issue_date' => $invoice->issue_date?->format('d M Y'),
                        'issue_date_raw' => $invoice->issue_date?->format('Y-m-d'),
                        'due_date' => $invoice->due_date?->format('d M Y'),
                        'due_date_raw' => $invoice->due_date?->format('Y-m-d'),
                        'invoice_for' => ucfirst(str_replace('_', ' ', $invoice->invoice_for ?? 'without orders')),
                        'amount' => $currency . ' ' . number_format($invoice->grand_total ?? 0, 2),
                        'amount_paid' => $currency . ' ' . number_format($amountPaid, 2),
                        'balance_due' => $currency . ' ' . number_format($balanceDue, 2),
                        'status' => $invoice->status ?? 'draft',
                        'payment_status' => $paymentStatus,
                        'items' => $invoice->items->map(function ($item) use ($currency) {
                            return [
                                'itemid' => $item->itemid ?? $item->proformaitemid ?? '',
                                'name' => $item->item_name,
                                'item_name' => $item->item_name,
                                'qty' => $item->quantity,
                                'quantity' => $item->quantity,
                                'price' => $currency . ' ' . number_format($item->unit_price, 2),
                                'unit_price' => (float) $item->unit_price,
                                'tax_rate' => (float) ($item->tax_rate ?? 0),
                                'duration' => $item->duration,
                                'frequency' => $item->frequency,
                                'users' => (int) ($item->no_of_users ?? 1),
                                'no_of_users' => (int) ($item->no_of_users ?? 1),
                                'start_date' => $item->start_date?->format('Y-m-d'),
                                'end_date' => $item->end_date?->format('Y-m-d'),
                                'total' => $currency . ' ' . number_format($item->line_total, 2),
                                'line_total' => (float) $item->line_total,
                            ];
                        }),
                    ];
                }),
                'selectedClientId' => $selectedClientId,
            ]);
        }

        $groupedInvoices = $allInvoices->groupBy(function ($invoice) {
            return $invoice->client->business_name ?? $invoice->client->contact_name ?? 'N/A';
        });

        return view('invoices.index', [
            'title' => 'Invoices',
            'clients' => $clients,
            'groupedInvoices' => $groupedInvoices,
            'selectedClientId' => $selectedClientId,
        ]);
    }

    public function invoicesCreate(): View
    {
        $user = auth()->user();
        $accountid = auth()->check() ? ($user?->accountid ?? 'ACC0000001') : 'ACC0000001';
        $legacyAccountId = $user?->id ? (string) $user->id : null;
        $account = \App\Models\Account::find($accountid);

        $termAccountIds = array_values(array_filter(array_unique([$accountid, $legacyAccountId])));

        $billingTerms = TermsCondition::query()
            ->whereIn('accountid', $termAccountIds)
            ->where('type', 'billing')
            ->where('is_active', true)
            ->orderByRaw('COALESCE(sequence, 999999), created_at ASC')
            ->get();

        return view('invoices.create', [
            'title' => 'Create Invoice',
            'clients' => Client::orderBy('business_name')->get(),
            'services' => Service::with(['category', 'costings'])->orderBy('sequence')->orderBy('name')->get(),
            'taxes' => ($account && $account->allow_multi_taxation) ? Tax::where('accountid', $accountid)->where('is_active', true)->orderByRaw('COALESCE(sequence, 999999), created_at DESC')->get() : collect(),
            'nextInvoiceNumber' => $this->generateInvoiceNumber(),
            'account' => $account,
            'accountBillingDetail' => AccountBillingDetail::where('accountid', $accountid)->first(),
            'billingTerms' => $billingTerms,
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
            ->whereDoesntHave('proformaInvoices')
            ->with('client')
            ->orderBy('order_date', 'desc')
            ->get(['orderid', 'order_number', 'order_title', 'order_date', 'grand_total', 'status', 'clientid'])
            ->map(function ($order) {
                $currency = $order->client->currency ?? 'INR';

                return [
                    'orderid' => $order->orderid,
                    'order_number' => $order->order_number,
                    'order_title' => $order->order_title,
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

        $invoices = ProformaInvoice::where('clientid', $clientId)
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
                    'proformaid' => $invoice->proformaid,
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
        $invoice = ProformaInvoice::with('items')->findOrFail($invoiceid);

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
                'invoiceid' => $invoice->proformaid,
                'invoice_number' => $invoice->invoice_number,
                'grand_total' => $invoice->grand_total,
            ],
            'items' => $items,
        ]);
    }

    public function storeBillingTerm(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
        ]);

        $user = auth()->user();
        $accountid = $user?->accountid ?? (string) ($user?->id ?? 'ACC0000001');
        $maxSequence = TermsCondition::query()
            ->where('accountid', $accountid)
            ->where('type', 'billing')
            ->max('sequence');

        $term = TermsCondition::create([
            'accountid' => $accountid,
            'type' => 'billing',
            'title' => trim((string) ($validated['title'] ?? '')) ?: 'Term',
            'content' => $validated['content'],
            'is_active' => true,
            'sequence' => ((int) ($maxSequence ?? 0)) + 1,
        ]);

        return response()->json([
            'ok' => true,
            'term' => [
                'id' => $term->tc_id,
                'title' => $term->title,
                'content' => $term->content,
            ],
        ]);
    }

    protected function generateInvoiceNumber(): string
    {
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';

        // First, check for SerialConfiguration (from Financial Year tab settings)
        $serialConfig = \App\Models\SerialConfiguration::where('accountid', $accountid)
            ->where('document_type', 'proforma_invoice')
            ->first();

        if ($serialConfig) {
            // Use the SerialConfiguration from Financial Year tab
            $candidate = $serialConfig->generateNextSerialNumber();
            return $this->ensureUniqueDocumentNumber($candidate !== '' ? $candidate : 'INV-0001', $accountid);
        }

        // Fallback: Check AccountBillingDetail (legacy configuration)
        $billingDetail = AccountBillingDetail::where('accountid', $accountid)->first();

        if (!$billingDetail) {
            // Fallback: simple auto-increment if no configuration exists
            $count = ProformaInvoice::where('accountid', $accountid)->count();
            $candidate = 'INV-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            return $this->ensureUniqueDocumentNumber($candidate, $accountid);
        }

        // Use the billing detail's serial number generation (legacy)
        $candidate = $billingDetail->generateNextSerialNumber();

        // Ensure we don't generate an empty string
        return $this->ensureUniqueDocumentNumber($candidate !== '' ? $candidate : 'INV-0001', $accountid);
    }

    protected function generateTaxInvoiceNumber(): string
    {
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $count = Invoice::where('accountid', $accountid)->count();
        $candidate = 'TAX-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        return $this->ensureUniqueDocumentNumber($candidate, $accountid);
    }

    protected function ensureUniqueDocumentNumber(string $candidate, string $accountid): string
    {
        $candidate = trim($candidate);

        if ($candidate === '') {
            $candidate = 'INV-0001';
        }

        $number = $candidate;
        $sequence = 2;

        while (
            Invoice::where('accountid', $accountid)->where('invoice_number', $number)->exists()
            || ProformaInvoice::where('accountid', $accountid)->where('invoice_number', $number)->exists()
        ) {
            if (preg_match('/^(.*?)(\d+)$/', $candidate, $matches)) {
                $prefix = $matches[1];
                $digits = $matches[2];
                $number = $prefix . str_pad((string) ((int) $digits + $sequence - 1), strlen($digits), '0', STR_PAD_LEFT);
            } else {
                $number = $candidate . '-' . $sequence;
            }

            $sequence++;
        }

        return $number;
    }

    public function invoicesStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'clientid' => 'required|exists:clients,clientid',
                'orderid' => 'nullable|exists:orders,orderid',
                'invoice_number' => 'required|string|unique:proforma_invoices,invoice_number',
                'invoice_title' => 'nullable|string|max:255',
                'invoice_for' => 'required|string|in:orders,renewal,without_orders',
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                'notes' => 'nullable|string',
                'terms' => 'nullable|string',
                'status' => 'required|in:draft,sent,paid,overdue,cancelled',
                'currency_code' => 'nullable|string|max:10',
                'subtotal' => 'required|numeric|min:0',
                'tax_total' => 'required|numeric|min:0',
                'grand_total' => 'required|numeric|min:0',
                'items_data' => 'required|json',
                'accountid' => 'nullable|size:10',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Invoice validation failed: ' . $e->getMessage(), [
                'request_data' => $request->except('items_data'),
                'error' => $e->getMessage(),
            ]);
            throw ValidationException::withMessages([
                'general' => 'Invalid form data. Please check all fields and try again.',
            ]);
        }

        $itemsData = json_decode($request->items_data, true);
        if (!is_array($itemsData) || empty($itemsData)) {
            throw ValidationException::withMessages([
                'items_data' => 'Add at least one invoice item before submitting.',
            ]);
        }

        $this->assertDocumentNumberAvailable($validated['invoice_number'], null, ProformaInvoice::class);

        if ($validated['invoice_for'] === 'orders') {
            if (empty($validated['orderid'])) {
                throw ValidationException::withMessages([
                    'orderid' => 'Select an order before creating an invoice from orders.',
                ]);
            }

            $order = \App\Models\Order::with(['invoices', 'proformaInvoices'])
                ->where('orderid', $validated['orderid'])
                ->where('clientid', $validated['clientid'])
                ->first();

            if (!$order) {
                throw ValidationException::withMessages([
                    'orderid' => 'The selected order does not belong to this client.',
                ]);
            }

            if ($order->invoices->isNotEmpty() || $order->proformaInvoices->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'orderid' => 'The selected order already has an invoice.',
                ]);
            }
        } elseif ($validated['invoice_for'] === 'renewal') {
            $validated['orderid'] = null;
        } else {
            // For without_orders, ensure orderid is null
            $validated['orderid'] = null;
        }

        $user = auth()->user();
        $client = Client::findOrFail($validated['clientid']);
        $validated['accountid'] = $validated['accountid'] ?? ($user->accountid ?? 'ACC0000001');
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
            $taxid = $itemData['taxid'] ?? null;
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
                'proformaid' => null,
                'itemid' => $itemId,
                'item_name' => $itemData['item_name'] ?? ($service?->name ?? 'Custom Item'),
                'item_description' => null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'taxid' => $taxid,
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

        try {
            DB::transaction(function () use ($validated, $preparedItems, &$invoice) {
                $invoice = ProformaInvoice::create($validated);

                foreach ($preparedItems as $itemData) {
                    $itemData['proformaid'] = $invoice->proformaid;
                    ProformaInvoiceItem::create($itemData);
                }
            });
        } catch (\Exception $e) {
            \Log::error('Failed to create invoice: ' . $e->getMessage(), [
                'validated' => $validated,
                'preparedItems' => $preparedItems,
                'trace' => $e->getTraceAsString(),
            ]);
            throw ValidationException::withMessages([
                'general' => 'Failed to create invoice. Please try again or contact support if the issue persists.',
            ]);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully with items.');
    }

    public function invoicesShow(string $invoice): View
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoice->loadMissing(['client', 'items.service']);

        if ($invoice instanceof ProformaInvoice) {
            $invoice->loadMissing('convertedTaxInvoice');
            $invoice->setRelation('payments', collect());
        } else {
            $invoice->loadMissing(['convertedFromInvoice', 'payments']);
        }
        
        // Load account billing details for preview
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = \App\Models\Account::where('accountid', $accountid)->first();
        $accountBillingDetail = \App\Models\AccountBillingDetail::where('accountid', $accountid)->first();

        return view('invoices.show', [
            'title' => 'Invoice Details', 
            'invoice' => $invoice,
            'account' => $account,
            'accountBillingDetail' => $accountBillingDetail,
        ]);
    }

    public function invoicesEdit(string $invoice)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoice->load(['items.service', 'items']);
        if ($invoice instanceof ProformaInvoice) {
            $invoice->load('convertedTaxInvoice');
        } else {
            $invoice->load(['convertedFromInvoice', 'payments']);
        }
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = \App\Models\Account::find($accountid);

        // Determine document type
        $documentType = $invoice->isProforma() ? 'Proforma' : 'Tax';

        $viewData = [
            'title' => 'Edit Invoice',
            'invoice' => $invoice,
            'clients' => Client::all(),
            'services' => Service::with(['category', 'costings'])->orderBy('sequence')->orderBy('name')->get(),
            'taxes' => ($account && $account->allow_multi_taxation) ? Tax::where('accountid', $accountid)->where('is_active', true)->orderByRaw('COALESCE(sequence, 999999), created_at DESC')->get() : collect(),
            'items' => $invoice->items,
            'account' => $account,
            'documentType' => $documentType,
        ];

        // Return inline edit view with PDF preview for AJAX/inline requests
        if (request('inline')) {
            return view('invoices._inline-edit', array_merge($viewData, ['inline' => true]));
        }

        return view('invoices.edit', $viewData);
    }

    public function invoicesUpdate(Request $request, string $invoice)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoiceTable = $invoice instanceof ProformaInvoice ? 'proforma_invoices' : 'tax_invoices';
        $itemModel = $invoice instanceof ProformaInvoice ? ProformaInvoiceItem::class : InvoiceItem::class;

        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'invoice_number' => 'required|string|unique:' . $invoiceTable . ',invoice_number,' . ($invoice instanceof ProformaInvoice ? $invoice->proformaid : $invoice->invoiceid) . ',' . ($invoice instanceof ProformaInvoice ? 'proformaid' : 'invoiceid'),
            'invoice_title' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'items_data' => 'required|json',
        ]);

        $itemsData = json_decode($request->items_data, true);
        $subtotal = 0;
        $taxTotal = 0;

        $this->assertDocumentNumberAvailable($validated['invoice_number'], $invoice instanceof ProformaInvoice ? $invoice->proformaid : $invoice->invoiceid, $invoice::class);

        foreach ($itemsData as $itemData) {
            $lineTotal = (float) ($itemData['line_total'] ?? 0);
            $subtotal += $lineTotal;
            $taxTotal += $lineTotal * ((float) ($itemData['tax_rate'] ?? 0) / 100);
        }

        $grandTotal = $subtotal + $taxTotal;

        $invoice->update([
            'clientid' => $validated['clientid'],
            'invoice_number' => $validated['invoice_number'],
            'invoice_title' => $validated['invoice_title'] ?? $invoice->invoice_title,
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
            $service = Service::find($itemData['itemid'] ?? null);
            $payload = [
                'itemid'           => $itemData['itemid'] ?: null,
                'item_name'        => $itemData['item_name'] ?? ($service?->name ?? 'Custom Item'),
                'item_description' => null,
                'quantity'         => $itemData['quantity'],
                'unit_price'       => $itemData['unit_price'],
                'tax_rate'         => $itemData['tax_rate'] ?? 0,
                'duration'         => $itemData['duration'] ?? null,
                'frequency'        => $itemData['frequency'] ?? null,
                'no_of_users'      => $itemData['no_of_users'] ?? 1,
                'start_date'       => $itemData['start_date'] ?: null,
                'end_date'         => $itemData['end_date'] ?: null,
                'line_total'       => $itemData['line_total'],
                'sort_order'       => $index + 1,
            ];

            if ($invoice instanceof ProformaInvoice) {
                $payload['proformaid'] = $invoice->proformaid;
            } else {
                $payload['invoiceid'] = $invoice->invoiceid;
            }

            $itemModel::create($payload);
        }

        // Return JSON response for AJAX requests
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Invoice updated successfully.']);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function invoicesDestroy(string $invoice)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    public function convertToTaxInvoice(ProformaInvoice $invoice)
    {
        $invoice->loadMissing(['items', 'convertedTaxInvoice']);

        if (!$invoice->isProforma()) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Only proforma invoices can be converted to tax invoices.');
        }

        if ($invoice->convertedTaxInvoice) {
            return redirect()->route('invoices.show', $invoice->convertedTaxInvoice)
                ->with('success', 'A tax invoice has already been created for this proforma invoice.');
        }

        if ($invoice->items->isEmpty()) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Add at least one invoice item before converting this proforma invoice.');
        }

        $newInvoiceNumber = $this->generateTaxInvoiceNumber();
        $taxInvoice = null;

        DB::transaction(function () use ($invoice, $newInvoiceNumber, &$taxInvoice) {
            $taxInvoice = Invoice::create([
                'accountid' => $invoice->accountid,
                'fy_id' => $invoice->fy_id,
                'clientid' => $invoice->clientid,
                'orderid' => $invoice->orderid,
                'proformaid' => $invoice->proformaid,
                'invoice_number' => $newInvoiceNumber,
                'invoice_title' => $invoice->invoice_title,
                'invoice_for' => $invoice->invoice_for,
                'status' => 'draft',
                'issue_date' => now()->toDateString(),
                'due_date' => ($invoice->due_date && $invoice->due_date->isFuture())
                    ? $invoice->due_date->toDateString()
                    : now()->toDateString(),
                'subtotal' => $invoice->subtotal,
                'tax_total' => $invoice->tax_total,
                'discount_total' => $invoice->discount_total ?? 0,
                'grand_total' => $invoice->grand_total,
                'amount_paid' => 0,
                'balance_due' => $invoice->grand_total,
                'currency_code' => $invoice->currency_code,
                'notes' => $invoice->notes,
                'terms' => $invoice->terms,
                'created_by' => auth()->user()?->userid ?? auth()->user()?->id,
            ]);

            foreach ($invoice->items as $item) {
                InvoiceItem::create([
                    'invoiceid' => $taxInvoice->invoiceid,
                    'itemid' => $item->itemid,
                    'item_name' => $item->item_name,
                    'item_description' => $item->item_description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_rate' => $item->tax_rate,
                    'taxid' => $item->taxid,
                    'duration' => $item->duration,
                    'frequency' => $item->frequency,
                    'no_of_users' => $item->no_of_users,
                    'start_date' => $item->start_date,
                    'end_date' => $item->end_date,
                    'line_total' => $item->line_total,
                    'sort_order' => $item->sort_order,
                ]);
            }
        });

        return redirect()->route('invoices.show', $taxInvoice)
            ->with('success', 'Tax invoice created successfully from the selected proforma invoice.');
    }

    protected function resolveInvoiceDocument(string $invoiceid): ProformaInvoice|Invoice
    {
        return ProformaInvoice::with('convertedTaxInvoice')->find($invoiceid)
            ?? Invoice::with('convertedFromInvoice')->findOrFail($invoiceid);
    }

    protected function assertDocumentNumberAvailable(string $invoiceNumber, ?string $ignoreInvoiceId, string $currentModel): void
    {
        $proformaExists = ProformaInvoice::where('invoice_number', $invoiceNumber)
            ->when($currentModel === ProformaInvoice::class && $ignoreInvoiceId, function ($query) use ($ignoreInvoiceId) {
                $query->where('proformaid', '!=', $ignoreInvoiceId);
            })
            ->exists();

        $taxExists = Invoice::where('invoice_number', $invoiceNumber)
            ->when($currentModel === Invoice::class && $ignoreInvoiceId, function ($query) use ($ignoreInvoiceId) {
                $query->where('invoiceid', '!=', $ignoreInvoiceId);
            })
            ->exists();

        if ($proformaExists || $taxExists) {
            throw ValidationException::withMessages([
                'invoice_number' => 'The invoice number must be unique across proforma and tax invoices.',
            ]);
        }
    }
}
