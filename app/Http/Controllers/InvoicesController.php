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
                                'discount_percent' => (float) ($item->discount_percent ?? 0),
                                'discount_amount' => (float) ($item->discount_amount ?? 0),
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
        $orderId = request('o');
        $clientId = request('c');

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
            'orderId' => $orderId,
            'clientId' => $clientId,
        ]);
    }

    public function getClientOrders(Request $request)
    {
        $clientId = $request->input('clientid');

        if (!$clientId) {
            return response()->json([]);
        }

        $orders = \App\Models\Order::where('clientid', $clientId)
            ->where('is_verified', 'yes') 
            ->whereDoesntHave('invoices')
            ->whereDoesntHave('proformaInvoices')
            ->with(['client', 'items', 'salesPerson'])
            ->orderBy('order_date', 'desc')
            ->get(['orderid', 'order_number', 'order_title', 'order_date', 'delivery_date', 'grand_total', 'status', 'clientid', 'is_verified', 'sales_person_id'])
            ->map(function ($order) {
                $currency = $order->client->currency ?? 'INR';

                return [
                    'orderid' => $order->orderid,
                    'order_number' => $order->order_number,
                    'order_title' => $order->order_title,
                    'order_date' => $order->order_date?->format('d M Y') ?? 'N/A',
                    'delivery_date' => $order->delivery_date?->format('d M Y') ?? 'N/A',
                    'grand_total' => $order->grand_total ?? '0.00',
                    'currency' => $currency,
                    'status' => $order->status ?? 'draft',
                    'is_verified' => $order->is_verified ?? 'no',
                    'sales_person' => $order->salesPerson->name ?? '-',
                    'item_count' => $order->items->count(),
                ];
            });

        return response()->json($orders);
    }

    public function getRenewalInvoices(Request $request)
    {
        $clientId = $request->input('clientid');
        $daysFilter = (int) $request->input('days', 1);

        if (!$clientId) {
            return response()->json([]);
        }

        \Log::info('getRenewalInvoices', ['clientid' => $clientId, 'days_filter' => $daysFilter]);

        // Get all invoices for this client
        $invoices = ProformaInvoice::where('clientid', $clientId)
            ->with('items', 'client')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($invoice) use ($daysFilter) {
                $today = now()->startOfDay();
                $upcomingThreshold = now()->addDays($daysFilter)->endOfDay();
                
                // Check for expired recurring items (includes today, not renewed)
                $expiredItems = $invoice->items->filter(function ($item) use ($today) {
                    if (!$item->end_date || $item->renewed_to_proformaid) {
                        return false;
                    }
                    
                    $itemEndDate = $item->end_date instanceof \Carbon\Carbon 
                        ? $item->end_date 
                        : \Carbon\Carbon::parse($item->end_date);
                    
                    $isExpired = $itemEndDate <= $today;
                    $hasRecurringFrequency = in_array($item->frequency, ['daily', 'weekly', 'bi-weekly', 'monthly', 'yearly', 'quarterly', 'semi-annually'], true);
                    
                    \Log::info('Item check (expired):', [
                        'item_name' => $item->item_name,
                        'end_date' => $itemEndDate->toDateTimeString(),
                        'frequency' => $item->frequency,
                        'is_expired' => $isExpired,
                        'has_recurring' => $hasRecurringFrequency,
                        'is_renewed' => (bool) $item->renewed_to_proformaid,
                    ]);
                    
                    return $isExpired && $hasRecurringFrequency;
                });
                
                // Check for upcoming expiring items (within 30 days, not renewed, not already expired)
                $upcomingItems = $invoice->items->filter(function ($item) use ($today, $upcomingThreshold) {
                    if (!$item->end_date || $item->renewed_to_proformaid) {
                        return false;
                    }
                    
                    $itemEndDate = $item->end_date instanceof \Carbon\Carbon 
                        ? $item->end_date 
                        : \Carbon\Carbon::parse($item->end_date);
                    
                    // Not expired yet but will expire within 30 days
                    $isUpcoming = $itemEndDate > $today && $itemEndDate <= $upcomingThreshold;
                    $hasRecurringFrequency = in_array($item->frequency, ['daily', 'weekly', 'bi-weekly', 'monthly', 'yearly', 'quarterly', 'semi-annually'], true);
                    
                    return $isUpcoming && $hasRecurringFrequency;
                });

                $result = [
                    'proformaid' => $invoice->proformaid,
                    'invoice_number' => $invoice->invoice_number,
                    'grand_total' => $invoice->grand_total ?? '0.00',
                    'currency' => $invoice->client->currency ?? 'INR',
                    'total_items' => $invoice->items->count(),
                    'expired_items' => $expiredItems->count(),
                    'upcoming_items' => $upcomingItems->count(),
                    'has_expired' => $expiredItems->count() > 0,
                    'has_upcoming' => $upcomingItems->count() > 0,
                ];

                \Log::info('Invoice result:', [
                    'invoice_number' => $result['invoice_number'],
                    'expired_items' => $result['expired_items'],
                    'upcoming_items' => $result['upcoming_items'],
                ]);

                return $result;
            })
            ->filter(function ($invoice) {
                // Return invoices with expired OR upcoming items
                return $invoice['has_expired'] || $invoice['has_upcoming'];
            })
            ->values();

        \Log::info('Renewal invoices found:', ['count' => $invoices->count()]);

        return response()->json($invoices);
    }

    public function getOrderItems(Request $request, $orderid)
    {
        $order = \App\Models\Order::with(['items.item', 'client', 'salesPerson'])->findOrFail($orderid);

        $items = $order->items->map(function ($item) {
            return [
                'itemid' => $item->itemid,
                'item_name' => $item->item_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $item->tax_rate,
                'discount_percent' => $item->discount_percent ?? 0,
                'discount_amount' => $item->discount_amount ?? 0,
                'duration' => $item->duration,
                'frequency' => $item->frequency,
                'no_of_users' => $item->no_of_users,
                'start_date' => $item->start_date?->format('Y-m-d'),
                'end_date' => $item->end_date?->format('Y-m-d'),
                'delivery_date' => $item->delivery_date?->format('Y-m-d'),
                'line_total' => $item->line_total,
                'requires_user_fields' => (bool) ($item->item?->user_wise ?? false),
            ];
        })->values();

        return response()->json([
            'order' => [
                'orderid' => $order->orderid,
                'order_number' => $order->order_number,
                'order_title' => $order->order_title,
                'order_date' => $order->order_date?->format('d M Y') ?? 'N/A',
                'delivery_date' => $order->delivery_date?->format('d M Y') ?? 'N/A',
                'grand_total' => $order->grand_total,
                'currency' => $order->client->currency ?? 'INR',
                'sales_person' => $order->salesPerson->name ?? '-',
                'is_verified' => $order->is_verified ?? 'no',
                'item_count' => $order->items->count(),
            ],
            'items' => $items,
        ]);
    }

    public function getRenewalItems(Request $request, $invoiceid)
    {
        $invoice = ProformaInvoice::with('items')->findOrFail($invoiceid);
        $daysFilter = (int) $request->input('days', 1);
        
        $today = now()->startOfDay();
        $upcomingThreshold = now()->addDays($daysFilter)->endOfDay();

        \Log::info('getRenewalItems', [
            'invoiceid' => $invoiceid,
            'invoice_number' => $invoice->invoice_number,
            'total_items' => $invoice->items->count(),
        ]);

        $items = $invoice->items->map(function ($item) use ($today, $upcomingThreshold) {
            if (!$item->end_date || $item->renewed_to_proformaid) {
                $isExpired = false;
                $isUpcoming = false;
            } else {
                $itemEndDate = $item->end_date instanceof \Carbon\Carbon 
                    ? $item->end_date 
                    : \Carbon\Carbon::parse($item->end_date);
                
                $isExpired = $itemEndDate <= $today;
                $isUpcoming = !$isExpired && $itemEndDate > $today && $itemEndDate <= $upcomingThreshold;
            }

            return [
                'proformaitemid' => $item->proformaitemid,
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
                'is_upcoming' => $isUpcoming,
                'renewed_to_proformaid' => $item->renewed_to_proformaid,
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
                'proformaid' => 'nullable|exists:proforma_invoices,proformaid',
                'clientid' => 'required|exists:clients,clientid',
                'orderid' => 'nullable|exists:orders,orderid',
                'invoice_number' => 'nullable|string',
                'invoice_title' => 'nullable|string|max:255',
                'invoice_for' => 'required|string|in:orders,renewal,without_orders',
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                'notes' => 'nullable|string',
                'terms' => 'nullable|string',
                'status' => 'nullable|in:unpaid,paid,partially-paid',
                'currency_code' => 'nullable|string|max:10',
                'subtotal' => 'required|numeric|min:0',
                'tax_total' => 'required|numeric|min:0',
                'grand_total' => 'required|numeric|min:0',
                'items_data' => 'required|json',
                'accountid' => 'nullable|size:10',
                'renewed_item_ids' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
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

        // Set default status if not provided
        $validated['status'] = $validated['status'] ?? 'unpaid';
        
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
        
        // Check if we're updating an existing draft
        $existingDraft = null;
        if (!empty($validated['proformaid'])) {
            $existingDraft = ProformaInvoice::whereIn('status', ['unpaid', 'partially-paid'])
                ->find($validated['proformaid']);
            if ($existingDraft) {
                // Use draft's invoice_number
                $validated['invoice_number'] = $existingDraft->invoice_number;
            }
        } else {
            // Generate new number
            $validated['invoice_number'] = $this->generateInvoiceNumber();
            $this->assertDocumentNumberAvailable($validated['invoice_number'], null, ProformaInvoice::class);
        }

        $itemsData = json_decode($request->items_data, true);
        if (!is_array($itemsData) || empty($itemsData)) {
            throw ValidationException::withMessages([
                'items_data' => 'Add at least one invoice item before submitting.',
            ]);
        }
        
        $subtotal = 0;
        $taxTotal = 0;
        $discountTotal = 0;
        $preparedItems = [];

        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = \App\Models\Account::find($accountid);
        $accountHasUsers = (bool) ($account?->have_users ?? false);

        foreach ($itemsData as $index => $itemData) {
            $itemId = $itemData['itemid'] ?? null;
            $service = $itemId ? Service::find($itemId) : null;
            $quantity = (float) ($itemData['quantity'] ?? 0);
            $unitPrice = (float) ($itemData['unit_price'] ?? 0);
            $taxRate = (float) ($itemData['tax_rate'] ?? 0);
            $discountPercent = min(100, max(0, (float) ($itemData['discount_percent'] ?? 0)));
            $discountAmount = max(0, (float) ($itemData['discount_amount'] ?? 0));
            $taxid = $itemData['taxid'] ?? null;
            $isUserWiseItem = $accountHasUsers && (bool) ($service?->user_wise ?? false);
            $hasRecurringFrequency = filled($itemData['frequency'] ?? null) && ($itemData['frequency'] ?? null) !== 'one-time';
            $users = $isUserWiseItem ? max(1, (int) ($itemData['no_of_users'] ?? 1)) : null;
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
            $discountTotal += $discountAmount;
            $taxTotal += max(0, $lineTotal - $discountAmount) * ($taxRate / 100);

            $preparedItems[] = [
                'proformaid' => null,
                'itemid' => $itemId,
                'item_name' => $itemData['item_name'] ?? ($service?->name ?? 'Custom Item'),
                'item_description' => null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'taxid' => $taxid,
                'duration' => $itemData['duration'] ?? null,
                'frequency' => $itemData['frequency'] ?? null,
                'no_of_users' => $users,
                'start_date' => $hasRecurringFrequency ? ($itemData['start_date'] ?? null) : null,
                'end_date' => $hasRecurringFrequency ? ($itemData['end_date'] ?? null) : null,
                'line_total' => $lineTotal,
                'sort_order' => $index + 1,
                'renewed_from_proformaitemid' => $itemData['renewed_from_proformaitemid'] ?? null,
            ];
        }

        $grandTotal = $subtotal - $discountTotal + $taxTotal;
        $validated['subtotal'] = round($subtotal, 2);
        $validated['tax_total'] = round($taxTotal, 2);
        $validated['discount_total'] = round($discountTotal, 2);
        $validated['grand_total'] = round($grandTotal, 2);
        $validated['amount_paid'] = 0;
        $validated['balance_due'] = round($grandTotal, 2);

        $invoice = null;

        try {
            DB::transaction(function () use ($validated, $preparedItems, &$invoice, $request, $existingDraft) {
                if ($existingDraft) {
                    // Update existing draft
                    $existingDraft->update($validated);
                    $invoice = $existingDraft;
                    
                    // Delete existing items
                    ProformaInvoiceItem::where('proformaid', $invoice->proformaid)->delete();
                } else {
                    // Create new invoice
                    $invoice = ProformaInvoice::create($validated);
                }

                foreach ($preparedItems as $itemData) {
                    $itemData['proformaid'] = $invoice->proformaid;
                    ProformaInvoiceItem::create($itemData);
                }

                // If this is a renewal, mark the original items as renewed
                if ($validated['invoice_for'] === 'renewal') {
                    $renewedItemIdsRaw = $request->input('renewed_item_ids');
                    $renewedItemIds = json_decode($renewedItemIdsRaw ?? '[]', true);
                    
                    \Log::info('Renewal submission check', [
                        'invoice_for' => $validated['invoice_for'],
                        'renewed_item_ids_raw' => $renewedItemIdsRaw,
                        'renewed_item_ids_parsed' => $renewedItemIds,
                        'new_invoice_id' => $invoice->proformaid,
                    ]);
                    
                    if (!empty($renewedItemIds)) {
                        \Log::info('Marking proforma items as renewed', [
                            'invoice_id' => $invoice->proformaid,
                            'renewed_proformaitem_ids' => $renewedItemIds,
                        ]);

                        $updated = ProformaInvoiceItem::whereIn('proformaitemid', $renewedItemIds)
                            ->whereNull('renewed_to_proformaid')
                            ->update([
                                'renewed_to_proformaid' => $invoice->proformaid,
                                'renewed_at' => now(),
                            ]);
                            
                        \Log::info('Updated items count:', ['updated' => $updated]);
                    } else {
                        \Log::warning('No renewed_item_ids found for renewal invoice');
                    }
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

    public function invoicesSaveDraft(Request $request)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'invoice_title' => 'nullable|string|max:255',
            'invoice_for' => 'nullable|string|in:orders,renewal,without_orders',
                'status' => 'nullable|in:unpaid,paid,partially-paid',
        ]);

        $user = auth()->user();
        $client = Client::findOrFail($validated['clientid']);

        // Check if draft already exists for this client
        $draft = ProformaInvoice::where('clientid', $validated['clientid'])
            ->whereIn('status', ['unpaid', 'partially-paid'])
            ->where('created_by', $user?->userid ?? $user?->id)
            ->where('updated_at', '>', now()->subHours(24))
            ->first();

        if ($draft) {
            // Update existing draft
            $draft->update([
                'invoice_title' => $validated['invoice_title'] ?? $draft->invoice_title,
                'invoice_for' => $validated['invoice_for'] ?? $draft->invoice_for,
            ]);
        } else {
            // Create new draft
            $draft = ProformaInvoice::create([
                'accountid' => $user->accountid ?? 'ACC0000001',
                'clientid' => $validated['clientid'],
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_title' => $validated['invoice_title'] ?? '',
                'invoice_for' => $validated['invoice_for'] ?? 'without_orders',
                'status' => 'unpaid',
                'issue_date' => now(),
                'due_date' => now()->addDays(7),
                'currency_code' => $client->currency ?? 'INR',
                'subtotal' => 0,
                'tax_total' => 0,
                'grand_total' => 0,
                'amount_paid' => 0,
                'balance_due' => 0,
                'created_by' => $user?->userid ?? $user?->id,
            ]);
        }

        return response()->json([
            'ok' => true,
            'proformaid' => $draft->proformaid,
            'invoice_number' => $draft->invoice_number,
        ]);
    }

    public function invoicesGetDraft($clientid)
    {
        $user = auth()->user();
        
        $draft = ProformaInvoice::where('clientid', $clientid)
            ->whereIn('status', ['unpaid', 'partially-paid'])
            ->where('created_by', $user?->userid ?? $user?->id)
            ->where('updated_at', '>', now()->subHours(24))
            ->with('items.item')
            ->first();

        if (!$draft) {
            return response()->json(['ok' => false]);
        }

        return response()->json([
            'ok' => true,
            'draft' => [
                'proformaid' => $draft->proformaid,
                'invoice_number' => $draft->invoice_number,
                'invoice_title' => $draft->invoice_title,
                'invoice_for' => $draft->invoice_for,
                'status' => $draft->status,
                'items' => $draft->items->map(fn($i) => [
                    'proformaitemid' => $i->proformaitemid,
                    'itemid' => $i->itemid,
                    'item_name' => $i->item_name,
                    'quantity' => $i->quantity,
                    'unit_price' => $i->unit_price,
                    'tax_rate' => $i->tax_rate,
                    'discount_percent' => $i->discount_percent ?? 0,
                    'discount_amount' => $i->discount_amount ?? 0,
                    'duration' => $i->duration,
                    'frequency' => $i->frequency,
                    'no_of_users' => $i->no_of_users,
                    'start_date' => $i->start_date?->format('Y-m-d'),
                    'end_date' => $i->end_date?->format('Y-m-d'),
                    'line_total' => $i->line_total,
                    'renewed_from_proformaitemid' => $i->renewed_from_proformaitemid,
                    'requires_user_fields' => (bool) ($i->item?->user_wise ?? false),
                ]),
            ],
        ]);
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
            'status' => 'required|in:unpaid,paid,partially-paid',
            'items_data' => 'required|json',
        ]);

        $itemsData = json_decode($request->items_data, true);
        $subtotal = 0;
        $taxTotal = 0;
        $discountTotal = 0;

        $this->assertDocumentNumberAvailable($validated['invoice_number'], $invoice instanceof ProformaInvoice ? $invoice->proformaid : $invoice->invoiceid, $invoice::class);

        foreach ($itemsData as $itemData) {
            $lineTotal = (float) ($itemData['line_total'] ?? 0);
            $discountAmount = max(0, (float) ($itemData['discount_amount'] ?? 0));
            $subtotal += $lineTotal;
            $discountTotal += $discountAmount;
            $taxTotal += max(0, $lineTotal - $discountAmount) * ((float) ($itemData['tax_rate'] ?? 0) / 100);
        }

        $grandTotal = $subtotal - $discountTotal + $taxTotal;

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
            'discount_total' => $discountTotal,
            'grand_total' => $grandTotal,
            'balance_due' => $grandTotal,
        ]);

        $invoice->items()->delete();

        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = \App\Models\Account::find($accountid);
        $accountHasUsers = (bool) ($account?->have_users ?? false);

        foreach ($itemsData as $index => $itemData) {
            $service = Service::find($itemData['itemid'] ?? null);
            $isUserWiseItem = $accountHasUsers && (bool) ($service?->user_wise ?? false);
            $hasRecurringFrequency = filled($itemData['frequency'] ?? null) && ($itemData['frequency'] ?? null) !== 'one-time';
            $payload = [
                'itemid'           => $itemData['itemid'] ?: null,
                'item_name'        => $itemData['item_name'] ?? ($service?->name ?? 'Custom Item'),
                'item_description' => null,
                'quantity'         => $itemData['quantity'],
                'unit_price'       => $itemData['unit_price'],
                'tax_rate'         => $itemData['tax_rate'] ?? 0,
                'discount_percent' => min(100, max(0, (float) ($itemData['discount_percent'] ?? 0))),
                'discount_amount' => max(0, (float) ($itemData['discount_amount'] ?? 0)),
                'duration'         => $itemData['duration'] ?? null,
                'frequency'        => $itemData['frequency'] ?? null,
                'no_of_users'      => $isUserWiseItem ? max(1, (int) ($itemData['no_of_users'] ?? 1)) : null,
                'start_date'       => $hasRecurringFrequency ? ($itemData['start_date'] ?: null) : null,
                'end_date'         => $hasRecurringFrequency ? ($itemData['end_date'] ?: null) : null,
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
                    'discount_percent' => $item->discount_percent,
                    'discount_amount' => $item->discount_amount,
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
