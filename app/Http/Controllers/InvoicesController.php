<?php

namespace App\Http\Controllers;

use App\Models\AccountBillingDetail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
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
        $selectedClientId = request('c', request('clientid'));

        $invoiceQuery = Invoice::with(['client', 'items', 'payments'])->latest();

        if ($selectedClientId) {
            $invoiceQuery->where('clientid', $selectedClientId);
        }

        $allInvoices = $invoiceQuery->get();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'invoices' => $allInvoices->map(function ($invoice) {
                    $amountPaid = (float) ($invoice->amount_paid ?? 0);
                    $grandTotal = (float) ($invoice->grand_total ?? 0);
                    $balanceDue = (float) ($invoice->balance_due ?? max(0, $grandTotal - $amountPaid));
                    $currency = $invoice->client->currency ?? 'INR';

                    $paymentStatus = 'unpaid';
                    if ($amountPaid > 0 && $balanceDue <= 0 && $grandTotal > 0) {
                        $paymentStatus = 'paid';
                    } elseif ($amountPaid > 0) {
                        $paymentStatus = 'partially paid';
                    }

                    return [
                        'record_id' => $invoice->invoiceid,
                        'number' => $invoice->invoice_number,
                        'title' => $invoice->invoice_title,
                        'clientid' => $invoice->clientid,
                        'issue_date' => $invoice->issue_date?->format('d M Y'),
                        'issue_date_raw' => $invoice->issue_date?->format('Y-m-d'),
                        'due_date' => $invoice->due_date?->format('d M Y'),
                        'due_date_raw' => $invoice->due_date?->format('Y-m-d'),
                        'invoice_for' => ucfirst(str_replace('_', ' ', $invoice->invoice_for ?? 'without orders')),
                        'amount' => $currency . ' ' . number_format($invoice->grand_total ?? 0, 0),
                        'amount_paid' => $currency . ' ' . number_format($amountPaid, 0),
                        'balance_due' => $currency . ' ' . number_format($balanceDue, 0),
                        'status' => $invoice->status ?? 'draft',
                        'payment_status' => $paymentStatus,
                        'items' => $invoice->items->map(function ($item) use ($currency) {
                            return [
                                'itemid' => $item->itemid ?? $item->invoice_itemid ?? '',
                                'name' => $item->item_name,
                                'item_name' => $item->item_name,
                                'qty' => $item->quantity,
                                'quantity' => $item->quantity,
                                'price' => $currency . ' ' . number_format($item->unit_price, 0),
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
                                'total' => $currency . ' ' . number_format($item->line_total, 0),
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
            'title' => $selectedClientId ? 'All Invoices' : 'Manage Invoices',
            'subtitle' => $selectedClientId ? 'Filtered by selected client.' : 'Choose a client first to view invoices.',
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
        $orderId = request('o', request('orderid'));
        $clientId = request('c', request('clientid'));
        $invoiceFor = request('invoice_for', session('invoice_for'));
        $currentUserId = $user?->userid ?? $user?->id;
        $draftId = request('d');

        $existingDraft = null;
        if (!empty($draftId) && !empty($currentUserId)) {
            $existingDraft = Invoice::query()
                ->where('invoiceid', $draftId)
                ->where('status', 'draft')
                ->where('created_by', $currentUserId)
                ->first();
        }

        if (!empty($clientId) && !empty($currentUserId)) {
            $existingDraft = $existingDraft ?: Invoice::query()
                ->where('clientid', $clientId)
                ->where('status', 'draft')
                ->where('created_by', $currentUserId)
                ->when($invoiceFor === 'orders', fn ($q) => $q->whereNotNull('orderid'))
                ->when($invoiceFor === 'orders' && !empty($orderId), fn ($q) => $q->where('orderid', $orderId))
                ->when(($invoiceFor ?? '') !== 'orders', fn ($q) => $q->whereNull('orderid'))
                ->where('updated_at', '>', now()->subHours(24))
                ->latest('updated_at')
                ->first();
        }

        $nextInvoiceNumber = $existingDraft?->invoice_number ?: $this->generateInvoiceNumber();

        $termAccountIds = array_values(array_filter(array_unique([$accountid, $legacyAccountId])));

        $billingTerms = TermsCondition::query()
            ->whereIn('accountid', $termAccountIds)
            ->where('type', 'billing')
            ->where('is_active', true)
            ->orderByRaw('COALESCE(sequence, 999999), created_at ASC')
            ->get();

        $accountBillingDetail = AccountBillingDetail::query()
            ->whereIn('accountid', $termAccountIds)
            ->orderByRaw('accountid = ? desc', [$accountid])
            ->first();

        return view('invoices.create', [
            'title' => 'Create Invoice',
            'clients' => Client::orderBy('business_name')->get(),
            'services' => Service::with(['category', 'costings'])->orderBy('sequence')->orderBy('name')->get(),
            'taxes' => ($account && $account->allow_multi_taxation) ? Tax::where('accountid', $accountid)->where('is_active', true)->orderByRaw('COALESCE(sequence, 999999), created_at DESC')->get() : collect(),
            'nextInvoiceNumber' => $nextInvoiceNumber,
            'account' => $account,
            'accountBillingDetail' => $accountBillingDetail,
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
            ->with(['client', 'items', 'salesPerson'])
            ->orderBy('order_date', 'desc')
            ->get(['orderid', 'order_number', 'order_title', 'order_date', 'delivery_date', 'status', 'clientid', 'is_verified', 'sales_person_id'])
            ->map(function ($order) {
                $currency = $order->client->currency ?? 'INR';

                return [
                    'orderid' => $order->orderid,
                    'order_number' => $order->order_number,
                    'order_title' => $order->order_title,
                    'order_date' => $order->order_date?->format('d M Y') ?? 'N/A',
                    'delivery_date' => $order->delivery_date?->format('d M Y') ?? 'N/A',
                    'grand_total' => $order->grand_total ?? '0',
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
        $daysInput = $request->input('days', 0);
        $daysFilter = is_numeric($daysInput) ? max(0, (int) $daysInput) : 0;

        if (!$clientId) {
            return response()->json([]);
        }

        $recurringFrequencies = ['daily', 'weekly', 'bi-weekly', 'monthly', 'yearly', 'quarterly', 'semi-annually'];
        $invoices = Invoice::where('clientid', $clientId)
            ->with('items', 'client')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($invoice) use ($recurringFrequencies, $daysFilter) {
                $today = now()->startOfDay();
                $upcomingThreshold = now()->addDays($daysFilter)->endOfDay();

                $renewalItems = $invoice->items
                    ->map(function ($item) use ($today, $upcomingThreshold, $recurringFrequencies) {
                        if (!$item->end_date) {
                            return null;
                        }

                        $hasRecurringFrequency = in_array($item->frequency, $recurringFrequencies, true);
                        if (!$hasRecurringFrequency) {
                            return null;
                        }

                        $itemEndDate = $item->end_date instanceof \Carbon\Carbon
                            ? $item->end_date
                            : \Carbon\Carbon::parse($item->end_date);

                        $isExpired = $itemEndDate <= $today;
                        $isUpcoming = $itemEndDate > $today && $itemEndDate <= $upcomingThreshold;

                        if (!$isExpired && !$isUpcoming) {
                            return null;
                        }

                        return [
                            'invoice_itemid' => $item->invoice_itemid,
                            'itemid' => $item->itemid,
                            'item_name' => $item->item_name,
                            'quantity' => (float) ($item->quantity ?? 0),
                            'unit_price' => (float) ($item->unit_price ?? 0),
                            'tax_rate' => (float) ($item->tax_rate ?? 0),
                            'discount_percent' => (float) ($item->discount_percent ?? 0),
                            'discount_amount' => (float) ($item->discount_amount ?? 0),
                            'duration' => $item->duration,
                            'frequency' => $item->frequency,
                            'no_of_users' => $item->no_of_users ? (int) $item->no_of_users : null,
                            'start_date' => $item->start_date?->format('Y-m-d'),
                            'end_date' => $item->end_date?->format('Y-m-d'),
                            'line_total' => (float) ($item->line_total ?? 0),
                            'is_expired' => $isExpired,
                            'is_upcoming' => $isUpcoming,
                        ];
                    })
                    ->filter()
                    ->values();

                $result = [
                    'invoiceid' => $invoice->invoiceid,
                    'invoice_number' => $invoice->invoice_number,
                    'grand_total' => $invoice->grand_total ?? '0',
                    'currency' => $invoice->client->currency ?? 'INR',
                    'total_items' => $invoice->items->count(),
                    'expired_items' => $renewalItems->where('is_expired', true)->count(),
                    'upcoming_items' => $renewalItems->where('is_upcoming', true)->count(),
                    'has_expired' => $renewalItems->where('is_expired', true)->isNotEmpty(),
                    'has_upcoming' => $renewalItems->where('is_upcoming', true)->isNotEmpty(),
                    'items' => $renewalItems,
                ];

                return $result;
            })
            ->filter(function ($invoice) {
                return $invoice['has_expired'] || $invoice['has_upcoming'];
            })
            ->values();

        return response()->json($invoices);
    }

    public function getOrderItems(Request $request, $orderid)
    {
        $order = \App\Models\Order::with(['items.item.costings', 'client', 'salesPerson'])->findOrFail($orderid);

        $items = $order->items->map(function ($item) {
            $taxRate = (float) ($item->tax_rate ?? 0);
            $lineTotal = (float) ($item->line_total ?? 0);
            return [
                'itemid' => $item->itemid,
                'item_name' => $item->item_name,
                'item_description' => $item->item_description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $taxRate,
                'discount_percent' => $item->discount_percent ?? 0,
                'discount_amount' => $item->discount_amount ?? 0,
                'duration' => $item->duration,
                'frequency' => $item->frequency,
                'no_of_users' => $item->no_of_users,
                'start_date' => $item->start_date?->format('Y-m-d'),
                'end_date' => $item->end_date?->format('Y-m-d'),
                'delivery_date' => $item->delivery_date?->format('Y-m-d'),
                'line_total' => $lineTotal,
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
        $invoice = Invoice::with('items')->findOrFail($invoiceid);
        $daysFilter = (int) $request->input('days', 1);
        
        $today = now()->startOfDay();
        $upcomingThreshold = now()->addDays($daysFilter)->endOfDay();

        \Log::info('getRenewalItems', [
            'invoiceid' => $invoiceid,
            'invoice_number' => $invoice->invoice_number,
            'total_items' => $invoice->items->count(),
        ]);

        $items = $invoice->items->map(function ($item) use ($today, $upcomingThreshold) {
            if (!$item->end_date) {
                $isExpired = false;
                $isUpcoming = false;
            } else {
                $itemEndDate = $item->end_date instanceof \Carbon\Carbon 
                    ? $item->end_date 
                    : \Carbon\Carbon::parse($item->end_date);
                
                $isExpired = $itemEndDate <= $today;
                $isUpcoming = !$isExpired && $itemEndDate > $today && $itemEndDate <= $upcomingThreshold;
            }

            $taxRate = (float) ($item->tax_rate ?? 0);
            $lineTotal = (float) ($item->line_total ?? 0);

            return [
                'invoice_itemid' => $item->invoice_itemid,
                'itemid' => $item->itemid,
                'item_name' => $item->item_name,
                'item_description' => $item->item_description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $taxRate,
                'discount_percent' => $item->discount_percent ?? 0,
                'discount_amount' => $item->discount_amount ?? 0,
                'duration' => $item->duration,
                'frequency' => $item->frequency,
                'no_of_users' => $item->no_of_users,
                'start_date' => $item->start_date?->format('Y-m-d'),
                'end_date' => $item->end_date?->format('Y-m-d'),
                'line_total' => $lineTotal,
                'is_expired' => $isExpired,
                'is_upcoming' => $isUpcoming,
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

    private function calculateInvoiceItemAmounts(array $itemData, float $taxRate): array
    {
        $lineTotal = $this->wholeAmount($itemData['line_total'] ?? 0);
        $discountPercent = $this->wholePercent($itemData['discount_percent'] ?? 0);

        $discountAmount = ($lineTotal * $discountPercent) / 100;
        $taxableAmount = max(0, $lineTotal - $discountAmount);
        $taxAmount = ($taxableAmount * $taxRate) / 100;

        return [
            'line_total' => round($lineTotal, 0),
            'discount_percent' => $discountPercent,
            'discount_amount' => $this->roundDiscountDown($discountAmount),
            'tax_amount' => $this->roundTaxUp($taxAmount),
        ];
    }

    private function roundTaxUp(float $amount): float
    {
        return (float) ceil(max(0, $amount));
    }

    private function roundDiscountDown(float $amount): float
    {
        return (float) floor(max(0, $amount));
    }

    private function wholeAmount(mixed $value): float
    {
        return (float) round(max(0, (float) $value), 0);
    }

    private function wholePercent(mixed $value): float
    {
        return (float) min(100, max(0, round((float) $value, 0)));
    }

    private function wholeQuantity(mixed $value): int
    {
        return max(1, (int) round((float) $value, 0));
    }

    public function storeBillingTerm(Request $request)
    {
        $validated = $request->validate([
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
            'content' => $validated['content'],
            'is_active' => true,
            'sequence' => ((int) ($maxSequence ?? 0)) + 1,
        ]);

        return response()->json([
            'ok' => true,
            'term' => [
                'id' => $term->tc_id,
                'content' => $term->content,
            ],
        ]);
    }

    protected function generateInvoiceNumber(): string
    {
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';

        // First, check for SerialConfiguration (from Financial Year tab settings)
        $serialConfig = \App\Models\SerialConfiguration::where('accountid', $accountid)
            ->whereIn('document_type', ['invoice', 'tax_invoice', 'proforma_invoice'])
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
            $count = Invoice::where('accountid', $accountid)->count();
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
            Invoice::where('accountid', $accountid)
                ->where(function ($query) use ($number) {
                    $query->where('pi_number', $number)
                        ->orWhere('ti_number', $number);
                })
                ->exists()
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
                'invoiceid' => 'nullable|exists:invoices,invoiceid',
                'clientid' => 'required|exists:clients,clientid',
                'orderid' => 'nullable|exists:orders,orderid',
                'invoice_number' => 'nullable|string',
                'invoice_title' => 'required|string|max:255',
                'invoice_for' => 'required|string|in:orders,renewal,without_orders',
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                'notes' => 'nullable|string',
                'terms' => 'nullable|string',
                'status' => 'nullable|in:unpaid,paid,partially-paid',
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
        
        if (!empty($validated['invoice_number'])) {
            $this->assertDocumentNumberAvailable($validated['invoice_number']);
        }

        if ($validated['invoice_for'] === 'orders') {
            if (empty($validated['orderid'])) {
                throw ValidationException::withMessages([
                    'orderid' => 'Select an order before creating an invoice from orders.',
                ]);
            }

            $order = \App\Models\Order::with(['invoices'])
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
        } elseif ($validated['invoice_for'] === 'renewal') {
            $validated['orderid'] = null;
        } else {
            // For without_orders, ensure orderid is null
            $validated['orderid'] = null;
        }

        $user = auth()->user();
        $client = Client::findOrFail($validated['clientid']);
        $validated['accountid'] = $validated['accountid'] ?? ($user->accountid ?? 'ACC0000001');
        $validated['created_by'] = $user?->userid ?? $user?->id;
        unset($validated['items_data']);
        
        // Check if we're updating an existing draft
        $existingDraft = null;
        if (!empty($validated['invoiceid'])) {
            $existingDraft = Invoice::whereIn('status', ['draft', 'unpaid', 'partially-paid'])
                ->find($validated['invoiceid']);
            if ($existingDraft) {
                // Use draft's invoice_number
                $validated['invoice_number'] = $existingDraft->invoice_number;
            }
        } else {
            // Generate new number
            $validated['invoice_number'] = $this->generateInvoiceNumber();
            $this->assertDocumentNumberAvailable($validated['invoice_number']);
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
            $quantity = $this->wholeQuantity($itemData['quantity'] ?? 1);
            $unitPrice = $this->wholeAmount($itemData['unit_price'] ?? 0);
            $taxRate = (float) ($itemData['tax_rate'] ?? 0);
            $amounts = $this->calculateInvoiceItemAmounts($itemData, $taxRate);
            $isUserWiseItem = $accountHasUsers && (bool) ($service?->user_wise ?? false);
            $hasRecurringFrequency = filled($itemData['frequency'] ?? null) && ($itemData['frequency'] ?? null) !== 'one-time';
            $users = $isUserWiseItem ? max(1, (int) ($itemData['no_of_users'] ?? 1)) : null;
            $lineTotal = $amounts['line_total'];

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
            $discountTotal += $amounts['discount_amount'];
            $taxTotal += $amounts['tax_amount'];

            $preparedItems[] = [
                'invoiceid' => null,
                'itemid' => $itemId,
                'item_name' => $itemData['item_name'] ?? ($service?->name ?? 'Custom Item'),
                'item_description' => $itemData['item_description'] ?? null,
                'quantity' => $this->wholeQuantity($quantity),
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'discount_percent' => $amounts['discount_percent'],
                'discount_amount' => $amounts['discount_amount'],
                'duration' => $itemData['duration'] ?? null,
                'frequency' => $itemData['frequency'] ?? null,
                'no_of_users' => $users,
                'start_date' => $hasRecurringFrequency ? ($itemData['start_date'] ?? null) : null,
                'end_date' => $hasRecurringFrequency ? ($itemData['end_date'] ?? null) : null,
                'amount' => $lineTotal,
            ];
        }

        $discountTotal = $this->roundDiscountDown($discountTotal);
        $taxTotal = $this->roundTaxUp($taxTotal);
        $grandTotal = $subtotal - $discountTotal + $taxTotal;
        $invoice = null;

        try {
            DB::transaction(function () use ($validated, $preparedItems, &$invoice, $request, $existingDraft) {
                if ($existingDraft) {
                    // Update existing draft
                    $existingDraft->update($validated);
                    $invoice = $existingDraft;
                    
                    // Delete existing items
                    InvoiceItem::where('invoiceid', $invoice->invoiceid)->delete();
                } else {
                    // Create new invoice
                    $invoice = Invoice::create($validated);
                }

                foreach ($preparedItems as $itemData) {
                    $itemData['invoiceid'] = $invoice->invoiceid;
                    InvoiceItem::create($itemData);
                }

                // Mark the linked order as completed when PI is created
                if (!empty($validated['orderid'])) {
                    \App\Models\Order::where('orderid', $validated['orderid'])
                        ->whereNotIn('status', ['cancelled'])
                        ->update(['status' => 'completed']);
                }

                // If this is a renewal, mark the original items as renewed
                if ($validated['invoice_for'] === 'renewal') {
                    $renewedItemIdsRaw = $request->input('renewed_item_ids');
                    $renewedItemIds = json_decode($renewedItemIdsRaw ?? '[]', true);
                    
                    \Log::info('Renewal submission check', [
                        'invoice_for' => $validated['invoice_for'],
                        'renewed_item_ids_raw' => $renewedItemIdsRaw,
                        'renewed_item_ids_parsed' => $renewedItemIds,
                        'new_invoice_id' => $invoice->invoiceid,
                    ]);
                    
                    if (!empty($renewedItemIds)) {
                        \Log::info('Renewal item ids received; skipping legacy renewed_* column updates on merged invoice_items table', [
                            'invoice_id' => $invoice->invoiceid,
                            'renewed_invoice_item_ids' => $renewedItemIds,
                        ]);
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
            'orderid' => 'nullable|exists:orders,orderid',
            'invoice_title' => 'sometimes|required|string|max:255',
            'invoice_for' => 'nullable|string|in:orders,renewal,without_orders',
            'status' => 'nullable|in:draft,unpaid,paid,partially-paid',
            'items_data' => 'nullable|json',
        ]);

        $user = auth()->user();
        $client = Client::findOrFail($validated['clientid']);

        // Check if draft already exists for this client
        $invoiceFor = $validated['invoice_for'] ?? null;
        $orderId = $validated['orderid'] ?? null;
        if (($invoiceFor ?? '') !== 'orders') {
            $orderId = null;
        }

        $draft = Invoice::where('clientid', $validated['clientid'])
            ->where('status', 'draft')
            ->where('created_by', $user?->userid ?? $user?->id)
            ->when($invoiceFor === 'orders', fn ($q) => $q->whereNotNull('orderid'))
            ->when($invoiceFor === 'orders' && !empty($orderId), fn ($q) => $q->where('orderid', $orderId))
            ->when(($invoiceFor ?? '') !== 'orders', fn ($q) => $q->whereNull('orderid'))
            ->where('updated_at', '>', now()->subHours(24))
            ->first();

        if ($draft) {
            // Update existing draft
            $draft->update([
                'invoice_title' => $validated['invoice_title'] ?? $draft->invoice_title,
                'orderid' => $orderId,
            ]);
        } else {
            // Create new draft
            $draft = Invoice::create([
                'accountid' => $user->accountid ?? 'ACC0000001',
                'clientid' => $validated['clientid'],
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_title' => $validated['invoice_title'] ?? '',
                'orderid' => $orderId,
                'status' => 'draft',
                'issue_date' => now(),
                'due_date' => now()->addDays(7),
                'created_by' => $user?->userid ?? $user?->id,
            ]);
        }

        $calculatedSubtotal = 0;
        $calculatedDiscountTotal = 0;
        $calculatedTaxTotal = 0;
        $rawItemsData = $request->input('items_data');
        if ($rawItemsData !== null) {
            $itemsData = json_decode($rawItemsData, true);
            if (!is_array($itemsData)) {
                $itemsData = [];
            }

            $draftItems = [];
            foreach ($itemsData as $index => $itemData) {
                if (!is_array($itemData)) {
                    continue;
                }

                $itemName = trim((string) ($itemData['item_name'] ?? ''));
                if ($itemName === '') {
                    continue;
                }

                $taxRate = (float) ($itemData['tax_rate'] ?? 0);
                $amounts = $this->calculateInvoiceItemAmounts($itemData, $taxRate);
                $calculatedSubtotal += (float) $amounts['line_total'];
                $calculatedDiscountTotal += (float) $amounts['discount_amount'];
                $calculatedTaxTotal += (float) $amounts['tax_amount'];

                $draftItems[] = [
                    'itemid' => $itemData['itemid'] ?? null,
                    'item_name' => $itemName,
                    'item_description' => $itemData['item_description'] ?? null,
                    'quantity' => max(0, (int) round((float) ($itemData['quantity'] ?? 0), 0)),
                    'unit_price' => $this->wholeAmount($itemData['unit_price'] ?? 0),
                    'tax_rate' => $taxRate,
                    'discount_percent' => $this->wholePercent($itemData['discount_percent'] ?? 0),
                    'discount_amount' => max(0, (float) $amounts['discount_amount']),
                    'duration' => $itemData['duration'] ?? null,
                    'frequency' => $itemData['frequency'] ?? null,
                    'no_of_users' => !empty($itemData['no_of_users']) ? max(1, (int) $itemData['no_of_users']) : null,
                    'start_date' => $itemData['start_date'] ?? null,
                    'end_date' => $itemData['end_date'] ?? null,
                    'amount' => $this->wholeAmount($amounts['line_total'] ?? 0),
                ];
            }

            $draft->items()->delete();
            if (!empty($draftItems)) {
                $draft->items()->createMany($draftItems);
            }
        }

        if ($rawItemsData !== null) {
            $calculatedDiscountTotal = $this->roundDiscountDown($calculatedDiscountTotal);
            $calculatedTaxTotal = $this->roundTaxUp($calculatedTaxTotal);
            $calculatedGrandTotal = max(0, $calculatedSubtotal - $calculatedDiscountTotal + $calculatedTaxTotal);
            $amountPaid = (float) ($draft->amount_paid ?? 0);
            $calculatedBalanceDue = max(0, $calculatedGrandTotal - $amountPaid);

            $draft->update(['status' => 'draft']);
        }

        return response()->json([
            'ok' => true,
            'invoiceid' => $draft->invoiceid,
            'invoice_number' => $draft->invoice_number,
        ]);
    }

    public function invoicesGetDraft($clientid)
    {
        $user = auth()->user();
        $invoiceFor = request('invoice_for');
        $orderId = request('o', request('orderid'));
        $draftId = request('d');
        if ($invoiceFor !== 'orders') {
            $orderId = null;
        }

        $draft = null;
        if (!empty($draftId)) {
            $draft = Invoice::where('invoiceid', $draftId)
                ->where('status', 'draft')
                ->where('created_by', $user?->userid ?? $user?->id)
                ->with(['items.item', 'order'])
                ->first();
        }

        if (!$draft) {
            $draft = Invoice::where('clientid', $clientid)
                ->where('status', 'draft')
                ->where('created_by', $user?->userid ?? $user?->id)
                ->when($invoiceFor === 'orders', fn ($q) => $q->whereNotNull('orderid'))
                ->when($invoiceFor === 'orders' && !empty($orderId), fn ($q) => $q->where('orderid', $orderId))
                ->when(($invoiceFor ?? '') !== 'orders', fn ($q) => $q->whereNull('orderid'))
                ->where('updated_at', '>', now()->subHours(24))
                ->with(['items.item', 'order'])
                ->first();
        }

        if (!$draft) {
            return response()->json(['ok' => false]);
        }

        return response()->json([
            'ok' => true,
            'draft' => [
                'invoiceid' => $draft->invoiceid,
                'invoice_number' => $draft->invoice_number,
                'invoice_title' => $draft->invoice_title,
                'invoice_for' => $draft->invoice_for,
                'orderid' => $draft->orderid,
                'po_number' => $draft->order?->po_number,
                'po_date' => $draft->order?->po_date?->format('Y-m-d'),
                'status' => $draft->status,
                'items' => $draft->items->map(fn($i) => [
                    'invoice_itemid' => $i->invoice_itemid,
                    'itemid' => $i->itemid,
                    'item_name' => $i->item_name,
                    'item_description' => $i->item_description,
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
                    'requires_user_fields' => (bool) ($i->item?->user_wise ?? false),
                ]),
            ],
        ]);
    }

    public function invoicesShow(string $invoice): View
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoice->loadMissing(['client', 'items.service', 'order', 'payments']);
        
        // Load account billing details for preview
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = \App\Models\Account::where('accountid', $accountid)->first();
        $accountBillingDetail = \App\Models\AccountBillingDetail::where('accountid', $accountid)->first();

        return view('invoices.show', [
            'title' => 'Invoice ' . ($invoice->invoice_number ?? 'Details'),
            'subtitle' => 'Invoice Details',
            'invoice' => $invoice,
            'account' => $account,
            'accountBillingDetail' => $accountBillingDetail,
        ]);
    }

    public function invoicesEdit(string $invoice)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoice->load(['items.service', 'items', 'payments']);
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = \App\Models\Account::find($accountid);

        $documentType = 'Invoice';

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
        $invoiceNumberColumn = 'pi_number';
        $itemModel = InvoiceItem::class;

        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'invoice_number' => 'required|string|unique:invoices,' . $invoiceNumberColumn . ',' . $invoice->invoiceid . ',invoiceid',
            'invoice_title' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'items_data' => 'required|json',
        ]);

        $itemsData = json_decode($request->items_data, true);
        $subtotal = 0;
        $taxTotal = 0;
        $discountTotal = 0;

        $this->assertDocumentNumberAvailable($validated['invoice_number'], $invoice->invoiceid);

        foreach ($itemsData as $itemData) {
            $taxRate = (float) ($itemData['tax_rate'] ?? 0);
            $amounts = $this->calculateInvoiceItemAmounts($itemData, $taxRate);
            $subtotal += $amounts['line_total'];
            $discountTotal += $amounts['discount_amount'];
            $taxTotal += $amounts['tax_amount'];
        }

        $invoice->update([
            'clientid' => $validated['clientid'],
            'invoice_number' => $validated['invoice_number'],
            'invoice_title' => $validated['invoice_title'] ?? $invoice->invoice_title,
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'],
        ]);

        $invoice->items()->delete();

        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = \App\Models\Account::find($accountid);
        $accountHasUsers = (bool) ($account?->have_users ?? false);

        foreach ($itemsData as $index => $itemData) {
            $service = Service::find($itemData['itemid'] ?? null);
            $taxRate = (float) ($itemData['tax_rate'] ?? 0);
            $amounts = $this->calculateInvoiceItemAmounts($itemData, $taxRate);
            $isUserWiseItem = $accountHasUsers && (bool) ($service?->user_wise ?? false);
            $hasRecurringFrequency = filled($itemData['frequency'] ?? null) && ($itemData['frequency'] ?? null) !== 'one-time';
            $payload = [
                'itemid'           => $itemData['itemid'] ?: null,
                'item_name'        => $itemData['item_name'] ?? ($service?->name ?? 'Custom Item'),
                'item_description' => $itemData['item_description'] ?? null,
                'quantity'         => $this->wholeQuantity($itemData['quantity'] ?? 1),
                'unit_price'       => $this->wholeAmount($itemData['unit_price'] ?? 0),
                'tax_rate'         => $taxRate,
                'discount_percent' => $amounts['discount_percent'],
                'discount_amount' => $amounts['discount_amount'],
                'duration'         => $itemData['duration'] ?? null,
                'frequency'        => $itemData['frequency'] ?? null,
                'no_of_users'      => $isUserWiseItem ? max(1, (int) ($itemData['no_of_users'] ?? 1)) : null,
                'start_date'       => $hasRecurringFrequency ? ($itemData['start_date'] ?: null) : null,
                'end_date'         => $hasRecurringFrequency ? ($itemData['end_date'] ?: null) : null,
                'amount'           => $amounts['line_total'],
                'invoiceid'        => $invoice->invoiceid,
            ];

            $itemModel::create($payload);
        }

        // Return JSON response for AJAX requests
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Invoice updated successfully.']);
        }

        $selectedClientId = $request->input('c') ?: $request->input('clientid') ?: $invoice->clientid;

        return redirect()
            ->route('invoices.index', $selectedClientId ? ['c' => $selectedClientId] : [])
            ->with('success', 'Invoice updated successfully.');
    }

    public function invoicesDestroy(string $invoice)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $selectedClientId = request('c') ?: $invoice->clientid;
        $invoice->update(['status' => 'cancelled']);

        return redirect()
            ->route('invoices.index', $selectedClientId ? ['c' => $selectedClientId] : [])
            ->with('success', 'Invoice cancelled successfully.');
    }

    protected function resolveInvoiceDocument(string $invoiceid): Invoice
    {
        return Invoice::findOrFail($invoiceid);
    }

    protected function assertDocumentNumberAvailable(string $invoiceNumber, ?string $ignoreInvoiceId = null): void
    {
        $numberExists = Invoice::query()
            ->where(function ($query) use ($invoiceNumber) {
                $query->where('pi_number', $invoiceNumber)
                    ->orWhere('ti_number', $invoiceNumber);
            })
            ->when($ignoreInvoiceId, function ($query) use ($ignoreInvoiceId) {
                $query->where('invoiceid', '!=', $ignoreInvoiceId);
            })
            ->exists();

        if ($numberExists) {
            throw ValidationException::withMessages([
                'invoice_number' => 'The invoice number must be unique.',
            ]);
        }
    }
}




