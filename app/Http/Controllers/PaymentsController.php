<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CommunicationLog;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\Ledger;
use App\Models\Account;
use App\Models\AccountBillingDetail;
use App\Models\MessageTemplate;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\SerialConfiguration;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PaymentsController extends Controller
{
    private function generatePaymentReceiptNumber(string $accountid): string
    {
        $serialConfig = SerialConfiguration::query()
            ->where('accountid', $accountid)
            ->where('document_type', 'payment_receipt')
            ->first();

        if ($serialConfig) {
            $candidate = trim((string) $serialConfig->generateNextSerialNumber());
            return $this->ensureUniquePaymentReceiptNumber($candidate !== '' ? $candidate : 'PR-0001', $accountid);
        }

        $count = Payment::query()
            ->where('accountid', $accountid)
            ->whereNotNull('receipt_number')
            ->where('receipt_number', '!=', '')
            ->count();

        return $this->ensureUniquePaymentReceiptNumber('PR-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT), $accountid);
    }

    private function ensureUniquePaymentReceiptNumber(string $candidate, string $accountid): string
    {
        $candidate = trim($candidate) ?: 'PR-0001';
        $number = $candidate;
        $sequence = 2;

        while (Payment::query()->where('accountid', $accountid)->where('receipt_number', $number)->exists()) {
            if (preg_match('/^(.*?)(\d+)$/', $candidate, $matches)) {
                $number = $matches[1] . str_pad((string) ((int) $matches[2] + $sequence - 1), strlen($matches[2]), '0', STR_PAD_LEFT);
            } else {
                $number = $candidate . '-' . $sequence;
            }
            $sequence++;
        }

        return $number;
    }

    private function resolveDefaultFyId(string $accountid): ?string
    {
        return FinancialYear::query()
            ->where('accountid', $accountid)
            ->where('default', 1)
            ->value('fy_id');
    }

    private function resolveDefaultFinancialYear(string $accountid): ?FinancialYear
    {
        return FinancialYear::query()
            ->where('accountid', $accountid)
            ->where('default', true)
            ->orderByDesc('created_at')
            ->first();
    }

    private function resolvePaymentDateBounds(string $accountid): array
    {
        return $this->resolveFinancialYearDateBounds($accountid);
    }

    private function normalizeInvoiceIds(array $invoiceIds): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn($invoiceId) => trim((string) $invoiceId),
            $invoiceIds,
        ))));
    }

    private function requestInvoiceIds(Request $request): array
    {
        $invoiceIds = $request->input('invoice_ids', []);

        if (!is_array($invoiceIds)) {
            $invoiceIds = [$invoiceIds];
        }

        return $this->normalizeInvoiceIds($invoiceIds);
    }

    private function syncPaymentDetails(Payment $payment, array $invoiceIds, array $customReceived = [], array $customTds = []): void
    {
        $invoiceIds = $this->normalizeInvoiceIds($invoiceIds);

        PaymentDetail::query()
            ->where('paymentid', $payment->paymentid)
            ->delete();

        if (empty($invoiceIds)) {
            return;
        }

        $invoices = Invoice::query()
            ->where('accountid', $payment->accountid)
            ->where('clientid', $payment->clientid)
            ->whereIn('invoiceid', $invoiceIds)
            ->with('client')
            ->get()
            ->keyBy('invoiceid');

        if ($invoices->isEmpty()) {
            return;
        }

        $receivedTotal = max(0, (float) ($payment->received_amount ?? 0));
        $tdsTotal = max(0, (float) ($payment->tds_amount ?? 0));
        $outstandingTotal = (float) $invoices->sum(function (Invoice $invoice) {
            return max(0, (float) ($invoice->balance_due ?? 0));
        });
        $invoiceCount = $invoices->count();
        $remainingReceived = $receivedTotal;
        $remainingTds = $tdsTotal;

        foreach ($invoiceIds as $index => $invoiceId) {
            $invoice = $invoices->get($invoiceId);
            if (!$invoice) {
                continue;
            }

            $isLast = $index === count($invoiceIds) - 1;

            if (!empty($customReceived) || !empty($customTds)) {
                $allocatedReceived = (float) ($customReceived[$invoiceId] ?? 0);
                $allocatedTds = (float) ($customTds[$invoiceId] ?? 0);
            } else {
                $totalSettlement = $receivedTotal + $tdsTotal;
                $tdsPercent = $totalSettlement > 0 ? ($tdsTotal / $totalSettlement) : 0;

                if ($isLast) {
                    $allocatedReceived = round($remainingReceived, 2);
                    $allocatedTds = round($remainingTds, 2);
                } else {
                    $limit = (float) ($invoice->subtotal - $invoice->discount_total);
                    if ($invoice->balance_due < $limit && $invoice->balance_due > 0) {
                        $limit = (float) $invoice->balance_due;
                    }

                    $remainingSettlement = $remainingReceived + $remainingTds;
                    $allocation = min($limit, $remainingSettlement);

                    $allocatedTds = round($allocation * $tdsPercent, 2);
                    $allocatedTds = min($allocatedTds, $remainingTds);
                    $allocatedReceived = $allocation - $allocatedTds;

                    if ($allocatedReceived > $remainingReceived) {
                        $allocatedReceived = $remainingReceived;
                        $allocatedTds = $allocation - $allocatedReceived;
                    }
                }
            }

            PaymentDetail::query()->create([
                'accountid' => (string) $payment->accountid,
                'clientid' => (string) $payment->clientid,
                'paymentid' => (string) $payment->paymentid,
                'invoiceid' => (string) $invoice->invoiceid,
                'received_amount' => $allocatedReceived,
                'tds_amount' => $allocatedTds,
            ]);

            if (empty($customReceived) && empty($customTds)) {
                $remainingReceived = max(0.0, $remainingReceived - $allocatedReceived);
                $remainingTds = max(0.0, $remainingTds - $allocatedTds);
            }
        }
    }

    private function ensurePaymentBelongsToAccount(Payment $payment): void
    {
        if ((string) $payment->accountid !== $this->resolveAccountId()) {
            abort(404);
        }
    }

    public function payments(): View
    {
        $userAccountId = $this->resolveAccountId();
        $clientId = request('c');
        $selectedClient = null;

        $query = Payment::query()
            ->where('accountid', $userAccountId)
            ->with(['client', 'invoices', 'paymentDetails']);

        if ($clientId && $clientId !== 'all') {
            $query->where('clientid', $clientId);
            $selectedClient = Client::query()
                ->where('accountid', $userAccountId)
                ->find($clientId);
        }

        $searchTerm = request('search', '');

        if ($searchTerm) {
            $query->where(function($q) use ($searchTerm) {
                $q->where('reference_number', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('client', function ($cq) use ($searchTerm) {
                        $cq->where('business_name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('contact_name', 'like', '%' . $searchTerm . '%');
                    });
            });
        }

        $resultCount = $query->count();

        $paymentRecords = $query->latest('payment_date')->latest('created_at')->take(50)->get();
        $paymentCurrencies = $paymentRecords->pluck('client.currency')->filter()->unique()->values();

        $payments = $paymentRecords->map(function ($payment) {
            $receivedAmount = (float) ($payment->received_amount ?? 0);
            $tdsAmount = (float) ($payment->tds_amount ?? 0);
            $currency = $payment->client->currency ?? 'INR';
            $primaryInvoice = $payment->invoice;
            $invoiceNumber = $payment->invoices->map(function ($invoice) {
                return trim((string) ($invoice->ti_number ?: $invoice->pi_number ?: $invoice->invoice_number ?: ''));
            })->filter()->implode(', ');
            if ($invoiceNumber === '' && $primaryInvoice) {
                $invoiceNumber = trim((string) ($primaryInvoice->ti_number ?: $primaryInvoice->pi_number ?: $primaryInvoice->invoice_number ?: ''));
            }
            $invoiceTitle = trim((string) ($payment->invoices->first()?->invoice_title ?? $primaryInvoice?->invoice_title ?? ''));
            $paymentDescription = trim((string) ($payment->description ?? ''));
            $displayTitle = $invoiceTitle !== ''
                ? $invoiceTitle
                : ($paymentDescription !== '' ? $paymentDescription : 'Payment');
            $paymentStatus = strtolower(trim((string) ($payment->status ?? 'active')));
            return [
                'record_id' => $payment->paymentid,
                'number' => $displayTitle,
                'receipt_number' => (string) ($payment->receipt_number ?? ''),
                'client' => $payment->client->business_name ?? 'Client',
                'invoice' => $invoiceNumber,
                'invoice_count' => $payment->invoices->count(),
                'currency' => $currency,
                'date' => $payment->payment_date?->format('d M Y'),
                'method' => $payment->mode ?? '-',
                'amount' => $receivedAmount,
                'tds_amount' => $tdsAmount,
                'description' => (string) ($payment->description ?? ''),
                'reference_number' => (string) ($payment->reference_number ?? ''),
                'status' => $paymentStatus,
            ];
        });

        return view('payments.index', [
            'title' => 'All Payments',
            'subtitle' => $searchTerm ? 'Search results for "' . $searchTerm . '"' : null,
            'payments' => $payments,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
            'selectedCurrency' => $paymentCurrencies->count() === 1 ? $paymentCurrencies->first() : null,
            'clientId' => $clientId,
            'selectedClient' => $selectedClient,
            'clients' => Client::where('accountid', $userAccountId)->regular()->orderBy('business_name')->get(),
        ]);
    }

    public function paymentsLedger(): View
    {
        $accountid = $this->resolveAccountId();
        $clientId = trim((string) request('c', ''));
        $financialYears = FinancialYear::query()
            ->where('accountid', $accountid)
            ->orderByDesc('default')
            ->orderByDesc('financial_year')
            ->get(['fy_id', 'financial_year', 'default']);
        $selectedFyId = trim((string) request('fy', 'all'));
        if ($selectedFyId === '') {
            $selectedFyId = 'all';
        }
        if ($selectedFyId !== 'all' && !$financialYears->contains('fy_id', $selectedFyId)) {
            $selectedFyId = 'all';
        }
        $searchTerm = trim((string) request('search', ''));
        $clients = Client::query()
            ->where('accountid', $accountid)
            ->regular()
            ->orderBy('business_name')
            ->get(['clientid', 'business_name', 'contact_name']);
        $selectedClient = $clientId !== '' && $clientId !== 'all'
            ? $clients->firstWhere('clientid', $clientId)
            : null;

        $query = Ledger::query()
            ->where('accountid', $accountid)
            ->with('client')
            ->when($clientId !== '' && $clientId !== 'all', function ($ledgerQuery) use ($clientId) {
                $ledgerQuery->where('clientid', $clientId);
            })
            ->when($searchTerm !== '', function ($ledgerQuery) use ($searchTerm) {
                $ledgerQuery->where(function ($searchQuery) use ($searchTerm) {
                    $searchQuery->where('invoiceid_paymentid', 'like', '%' . $searchTerm . '%')
                        ->orWhere('description', 'like', '%' . $searchTerm . '%')
                        ->orWhereHas('client', function ($clientQuery) use ($searchTerm) {
                            $clientQuery->where('business_name', 'like', '%' . $searchTerm . '%')
                                ->orWhere('contact_name', 'like', '%' . $searchTerm . '%');
                        });
                });
            })
            ->orderBy('date')
            ->orderBy('created_at')
            ->orderBy('ledgerid');

        $entries = $query->get();

        $invoiceIds = $entries->where('type', 'dr')->pluck('invoiceid_paymentid')->filter()->unique()->values();
        $paymentIds = $entries->where('type', 'cr')->pluck('invoiceid_paymentid')->filter()->unique()->values();

        $invoiceMap = Invoice::query()
            ->whereIn('invoiceid', $invoiceIds)
            ->with('invoiceItems')
            ->get(['invoiceid', 'invoice_title', 'pi_number', 'ti_number', 'clientid', 'fy_id', 'status'])
            ->keyBy('invoiceid');

        $paymentMap = Payment::query()
            ->whereIn('paymentid', $paymentIds)
            ->with('invoices')
            ->with('paymentDetails')
            ->get(['paymentid', 'receipt_number', 'reference_number', 'mode', 'clientid', 'received_amount', 'tds_amount', 'fy_id', 'status'])
            ->keyBy('paymentid');

        // Hide entries tied to cancelled invoices/payments from ledger listing.
        $entries = $entries->filter(function (Ledger $entry) use ($invoiceMap, $paymentMap) {
            if ($entry->type === 'dr') {
                $invoiceStatus = strtolower(trim((string) ($invoiceMap->get($entry->invoiceid_paymentid)?->status ?? '')));
                return $invoiceStatus !== 'cancelled';
            }

            $payment = $paymentMap->get($entry->invoiceid_paymentid);
            $paymentStatus = strtolower(trim((string) ($payment?->status ?? 'active')));
            if ($paymentStatus === 'cancelled') {
                return false;
            }
            $invoiceStatuses = $payment?->invoices?->map(function ($invoice) {
                return strtolower(trim((string) ($invoice->status ?? '')));
            }) ?? collect();
            return $invoiceStatuses->isEmpty() || $invoiceStatuses->contains(fn($status) => $status !== 'cancelled');
        })->values();

        if ($selectedFyId !== 'all') {
            $entries = $entries->filter(function (Ledger $entry) use ($selectedFyId, $invoiceMap, $paymentMap) {
                if ($entry->type === 'dr') {
                    return (string) ($invoiceMap->get($entry->invoiceid_paymentid)?->fy_id ?? '') === $selectedFyId;
                }

                return (string) ($paymentMap->get($entry->invoiceid_paymentid)?->fy_id ?? '') === $selectedFyId;
            })->values();
        }

        $runningBalance = 0;

        $ledgerEntries = $entries->map(function (Ledger $entry) use (&$runningBalance, $invoiceMap, $paymentMap) {
            $amount = (float) ($entry->amount ?? 0);
            $isInvoice = $entry->type === 'dr';
            $debit = 0;
            $credit = 0;

            if ($isInvoice) {
                $invoice = $invoiceMap->get($entry->invoiceid_paymentid);
                if ($invoice) {
                    $amount = (float) ($invoice->grand_total ?? 0);
                }
                $debit = $amount;
            } else {
                $payment = $paymentMap->get($entry->invoiceid_paymentid);
                $receivedAmount = (float) ($payment->received_amount ?? 0);
                $tdsAmount = (float) ($payment->tds_amount ?? 0);
                if ($amount <= 0 && ($receivedAmount > 0 || $tdsAmount > 0)) {
                    $amount = $receivedAmount > 0 ? $receivedAmount : $tdsAmount;
                }
                $credit = $amount;
            }

            $runningBalance = $runningBalance + $debit - $credit;

            $referenceLabel = $entry->invoiceid_paymentid;
            $referenceMeta = '';
            $referenceUrl = null;
            $entryKind = 'payment';

            if ($isInvoice) {
                $invoice = $invoiceMap->get($entry->invoiceid_paymentid);
                if ($invoice) {
                    $referenceLabel = $invoice->ti_number ?: $invoice->pi_number ?: $invoice->invoiceid;
                    $pdfType = trim((string) ($invoice->ti_number ?? '')) !== '' ? 'tax_invoice' : 'pi';
                    $referenceUrl = route('invoices.pdf', ['invoice' => $invoice->invoiceid, 'type' => $pdfType]);
                }
                $entryKind = 'invoice';
            } else {
                $payment = $paymentMap->get($entry->invoiceid_paymentid);
                if ($payment) {
                    $modeLabel = trim((string) ($payment->mode ?? '')) ?: '-';
                    $referenceNumber = trim((string) ($payment->reference_number ?? ''));
                    $referenceLabel = $modeLabel;
                    $receiptNumber = trim((string) ($payment->receipt_number ?? ''));
                    $referenceMeta = $receiptNumber !== '' ? ('Receipt: ' . $receiptNumber) : '';
                    if ($referenceNumber !== '') {
                        $referenceMeta .= ($referenceMeta !== '' ? ' | ' : '') . ('Ref: ' . $referenceNumber);
                    }
                    $referenceUrl = route('payments.show', $payment->paymentid);
                    $entryKind = (float) ($payment->tds_amount ?? 0) > 0 ? 'tds' : 'payment';
                }
            }

            return [
                'ledgerid' => $entry->ledgerid,
                'date' => $entry->date?->format('d M Y') ?? '-',
                'raw_date' => $entry->date?->format('Y-m-d') ?? '',
                'client_name' => $entry->client?->business_name ?? $entry->client?->contact_name ?? 'Client',
                'status' => strtolower(trim((string) ($entry->status ?? 'active'))),
                'type' => $entry->type,
                'type_label' => strtoupper($entry->type),
                'entry_kind' => $entryKind,
                'invoiceid_paymentid' => $entry->invoiceid_paymentid,
                'reference_label' => $referenceLabel,
                'reference_meta' => $referenceMeta,
                'reference_url' => $referenceUrl,
                'description' => (string) ($entry->description ?? ''),
                'amount' => $amount,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,
            ];
        });

        $invoiceTotal = (float) $ledgerEntries->sum('debit');
        $creditTotal = (float) $ledgerEntries->sum('credit');
        $tdsTotal = (float) $ledgerEntries->where('entry_kind', 'tds')->sum('credit');
        $paymentTotal = (float) $ledgerEntries->where('entry_kind', 'payment')->sum('credit');
        $closingBalance = $runningBalance;

        return view('payments.ledger', [
            'title' => 'Ledger',
            'subtitle' => 'Statement-style view of invoices, payments, and TDS entries.',
            'ledgerEntries' => $ledgerEntries,
            'clients' => $clients,
            'selectedClientId' => $clientId,
            'selectedClientName' => $selectedClient?->business_name ?? $selectedClient?->contact_name ?? 'All Clients',
            'financialYears' => $financialYears,
            'selectedFyId' => $selectedFyId,
            'searchTerm' => $searchTerm,
            'invoiceTotal' => $invoiceTotal,
            'paymentTotal' => $paymentTotal,
            'tdsTotal' => $tdsTotal,
            'closingBalance' => $closingBalance,
        ]);
    }

    public function paymentsGstReport(): View
    {
        $accountid = $this->resolveAccountId();
        $selectedClientId = trim((string) request('c', ''));
        $selectedMonth = max(1, min(12, (int) request('month', now()->month)));
        $selectedYear = max(2000, (int) request('year', now()->year));

        $account = \App\Models\Account::query()
            ->select(['accountid', 'state'])
            ->where('accountid', $accountid)
            ->first();

        $normalizeTaxState = static fn ($value) => preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $value)));
        $accountState = $normalizeTaxState($account?->state ?? '');

        $taxInvoices = Invoice::query()
            ->where('accountid', $accountid)
            ->whereNotNull('ti_number')
            ->where('ti_number', '!=', '')
            ->whereMonth('issue_date', $selectedMonth)
            ->whereYear('issue_date', $selectedYear)
            ->when($selectedClientId !== '' && $selectedClientId !== 'all', function ($query) use ($selectedClientId) {
                $query->where('clientid', $selectedClientId);
            })
            ->with(['client.billingDetail', 'invoiceItems'])
            ->orderBy('issue_date')
            ->orderBy('created_at')
            ->get();

        $rows = $taxInvoices->map(function (Invoice $invoice) use ($normalizeTaxState, $accountState) {
            $clientState = $normalizeTaxState($invoice->client?->state ?? '');
            $sameStateGst = $clientState !== '' && $accountState !== '' && $clientState === $accountState;

            $taxTotal = (float) $invoice->items->sum(function ($item) {
                $lineTotal = (float) ($item->line_total ?? 0);
                $discountPercent = max(0, min(100, (float) ($item->discount_percent ?? 0)));
                $taxableAmount = max(0, $lineTotal - ($lineTotal * $discountPercent / 100));

                return ceil($taxableAmount * ((float) ($item->tax_rate ?? 0) / 100));
            });

            $cgst = $sameStateGst ? round($taxTotal / 2, 0) : 0;
            $sgst = $sameStateGst ? round($taxTotal / 2, 0) : 0;
            $igst = $sameStateGst ? 0 : round($taxTotal, 0);

            return [
                'invoiceid' => $invoice->invoiceid,
                'ti_number' => (string) ($invoice->ti_number ?? ''),
                'client_name' => (string) ($invoice->client?->business_name ?? $invoice->client?->contact_name ?? 'Client'),
                'gstin' => (string) ($invoice->client?->billingDetail?->gstin ?? '-'),
                'state' => (string) ($invoice->client?->billingDetail?->state ?? '-'),
                'grand_total' => (float) ($invoice->grand_total ?? 0),
                'igst' => (float) $igst,
                'sgst' => (float) $sgst,
                'cgst' => (float) $cgst,
                'tax_total' => (float) ($igst + $sgst + $cgst),
            ];
        })->values();

        $availableYears = Invoice::query()
            ->where('accountid', $accountid)
            ->whereNotNull('ti_number')
            ->where('ti_number', '!=', '')
            ->whereNotNull('issue_date')
            ->when($selectedClientId !== '', function ($query) use ($selectedClientId) {
                $query->where('clientid', $selectedClientId);
            })
            ->selectRaw('DISTINCT YEAR(issue_date) as year_value')
            ->orderByDesc('year_value')
            ->pluck('year_value')
            ->filter()
            ->map(fn ($year) => (int) $year)
            ->values();

        if ($availableYears->isEmpty()) {
            $availableYears = collect([now()->year]);
        }

        return view('payments.gst-report', [
            'title' => 'GST Report',
            'subtitle' => 'Month-wise tax invoice summary.',
            'rows' => $rows,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'selectedClientId' => $selectedClientId,
            'availableYears' => $availableYears,
            'grandTotalSum' => (float) $rows->sum('grand_total'),
            'igstTotal' => (float) $rows->sum('igst'),
            'sgstTotal' => (float) $rows->sum('sgst'),
            'cgstTotal' => (float) $rows->sum('cgst'),
            'taxTotal' => (float) $rows->sum('tax_total'),
        ]);
    }

    public function paymentsCreate(): View
    {
        $accountId = $this->resolveAccountId();
        $selectedClientId = request('c', request('clientid'));
        if ($selectedClientId === 'all') {
            $selectedClientId = null;
        }
        $selectedInvoiceId = request('i');
        $paymentDateBounds = $this->resolvePaymentDateBounds($accountId);

        if ($selectedInvoiceId && !$selectedClientId) {
            $selectedClientId = Invoice::query()
                ->where('invoiceid', $selectedInvoiceId)
                ->value('clientid');
        }

        $selectedClient = $selectedClientId ? Client::query()->find($selectedClientId) : null;

        return view('payments.form', [
            'title' => 'Record New Payment',
            'clients' => Client::query()->where('accountid', $accountId)->regular()->get(),
            'invoices' => Invoice::query()->where('accountid', $accountId)->with(['client', 'invoiceItems'])
                ->where('status', '!=', 'cancelled')
                ->get(),
            'selectedClientId' => $selectedClientId,
            'selectedInvoiceIds' => $selectedInvoiceId ? [$selectedInvoiceId] : [],
            'selectedCurrency' => $selectedClient?->currency ?? 'INR',
            'paymentDateBounds' => $paymentDateBounds,
        ]);
    }

    public function paymentsStore(Request $request)
    {
        $paymentDateBounds = $this->resolvePaymentDateBounds($this->resolveAccountId());
        $validated = $request->validate([
            'payment_flow' => 'nullable|in:standard,tds',
            'clientid' => 'required|exists:clients,clientid',
            'invoice_ids' => 'nullable|array',
            'invoice_ids.*' => 'string|exists:invoices,invoiceid',
            'invoice_received_amounts' => 'nullable|array',
            'invoice_received_amounts.*' => 'numeric|min:0',
            'invoice_tds_amounts' => 'nullable|array',
            'invoice_tds_amounts.*' => 'numeric|min:0',
            'received_amount' => 'required|numeric|min:0',
            'tds_amount' => 'nullable|numeric|min:0',
            'payment_date' => 'required|date_format:Y-m-d|after_or_equal:' . $paymentDateBounds['min_date'] . '|before_or_equal:' . $paymentDateBounds['max_date'],
            'mode' => 'required|in:Bank Transfer,Online,Cash',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
        ]);
        $paymentFlow = (string) ($validated['payment_flow'] ?? 'standard');
        if ($paymentFlow === 'standard' && (float) $validated['received_amount'] <= 0) {
            return redirect()->back()->withInput()->withErrors(['received_amount' => 'Amount must be greater than zero for standard payment.']);
        }
        if ($paymentFlow === 'tds' && (float) ($validated['tds_amount'] ?? 0) <= 0) {
            return redirect()->back()->withInput()->withErrors(['tds_amount' => 'TDS amount must be greater than zero for TDS payment.']);
        }

        $invoiceIds = $this->requestInvoiceIds($request);
        $customReceived = $request->input('invoice_received_amounts', []);
        $customTds = $request->input('invoice_tds_amounts', []);
        $invoiceMap = Invoice::query()
            ->whereIn('invoiceid', $invoiceIds)
            ->get()
            ->keyBy('invoiceid');

        foreach ($invoiceMap as $invoice) {
            if ($invoice->clientid !== $validated['clientid']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['invoice_ids' => 'One of the selected invoices does not belong to the selected client.']);
            }
        }

        if (!empty($invoiceIds)) {
            $sumReceived = 0;
            $sumTds = 0;
            foreach ($invoiceIds as $invoiceId) {
                $invoice = $invoiceMap->get($invoiceId);
                if (!$invoice) {
                    continue;
                }

                $rowReceived = (float) ($customReceived[$invoiceId] ?? 0);
                $rowTds = (float) ($customTds[$invoiceId] ?? 0);
                $rowAllocation = $rowReceived + $rowTds;

                $previousAllocations = (float) $invoice->amount_paid;
                $grandTotal = (float) ($invoice->grand_total ?? 0);
                $withoutTaxTotal = (float) ($invoice->subtotal - $invoice->discount_total);
                $balanceDue = (float) ($invoice->balance_due ?? max(0, $grandTotal - $previousAllocations));
                $baseDueWithoutTax = $grandTotal > 0
                    ? max(0.0, ($balanceDue * $withoutTaxTotal) / $grandTotal)
                    : max(0.0, $balanceDue);
                $availableLimit = $paymentFlow === 'standard'
                    ? max(0.0, (float) ($invoice->balance_due ?? 0))
                    : $baseDueWithoutTax;

                if ($rowAllocation > ($availableLimit + 0.1)) {
                    $invNum = $invoice->ti_number ?: $invoice->pi_number ?: $invoice->invoice_number ?: $invoiceId;
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['invoice_ids' => "Allocation for invoice #{$invNum} exceeds its available amount without tax of {$availableLimit}."]);
                }

                $sumReceived += $rowReceived;
                $sumTds += $rowTds;
            }

            $mainReceived = (float) $validated['received_amount'];
            $mainTds = (float) ($validated['tds_amount'] ?? 0);

            if ($paymentFlow === 'standard' && ($sumReceived - $mainReceived) > 0.1) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['received_amount' => 'The sum of allocated invoice received amounts must not exceed the main received amount.']);
            }
            if ($paymentFlow !== 'standard' && abs($sumReceived - $mainReceived) > 0.1) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['received_amount' => 'The sum of allocated invoice received amounts must equal the main received amount.']);
            }
            if ($paymentFlow === 'standard' && $sumTds > 0.1) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['tds_amount' => 'Standard payment does not allow invoice-level TDS allocation.']);
            }
            if ($paymentFlow !== 'standard' && abs($sumTds - $mainTds) > 0.1) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['tds_amount' => 'The sum of allocated invoice TDS amounts must equal the main TDS amount.']);
            }
            if (($sumReceived + $sumTds) > ($mainReceived + $mainTds + 0.1)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['received_amount' => 'Total invoice allocations must not exceed the Total Settlement Amount.']);
            }
        }

        $userAccountId = $this->resolveAccountId();

        $paymentData = [
            'accountid' => $userAccountId,
            'fy_id' => $this->resolveDefaultFyId($userAccountId),
            'clientid' => $validated['clientid'],
            'receipt_number' => $this->generatePaymentReceiptNumber($userAccountId),
            'received_amount' => $paymentFlow === 'standard' ? (float) $validated['received_amount'] : 0.0,
            'tds_amount' => $paymentFlow === 'standard' ? 0.0 : (float) ($validated['tds_amount'] ?? 0),
            'payment_date' => $validated['payment_date'],
            'mode' => $validated['mode'],
            'reference_number' => $validated['reference_number'] ?? null,
            'description' => $validated['description'] ?? null,
        ];
        if ($this->hasPaymentsStatusColumn()) {
            $paymentData['status'] = 'active';
        }

        $payment = null;

        DB::transaction(function () use ($paymentData, &$payment, $invoiceIds, $customReceived, $customTds) {
            $payment = Payment::query()->create($paymentData);
            $this->syncPaymentLedgerEntry($payment);
            $this->syncPaymentDetails($payment, $invoiceIds, $customReceived, $customTds);
            $this->refreshPaymentInvoices($invoiceIds);
        });

        if ($payment && (float) ($payment->received_amount ?? 0) > 0) {
            $this->dispatchPaymentReceivedCommunications($payment);
        }

        return redirect()
            ->route('payments.index', ['c' => $payment->clientid])
            ->with('success', 'Payment recorded successfully.');
    }

    public function paymentsShow(Request $request, Payment $payment): View
    {
        $this->ensurePaymentBelongsToAccount($payment);
        $payment->load(['client', 'invoices']);
        $primaryInvoice = $payment->invoices->first();
        $displayTitle = $primaryInvoice?->invoice_title
            ?? $primaryInvoice?->invoice_number
            ?? $payment->paymentid;
        $previewOnly = $request->boolean('preview');

        if ($previewOnly) {
            return view('payments.show-preview', [
                'title' => $displayTitle,
                'payment' => $payment,
                'displayTitle' => $displayTitle,
            ]);
        }

        return view('payments.show', [
            'title' => $displayTitle,
            'subtitle' => 'Payment Details',
            'payment' => $payment,
            'displayTitle' => $displayTitle,
        ]);
    }

    public function paymentsEdit(Payment $payment): View
    {
        $this->ensurePaymentBelongsToAccount($payment);
        if (strtolower(trim((string) ($payment->status ?? 'active'))) === 'cancelled') {
            abort(404);
        }
        $payment->loadMissing(['client', 'invoices']);
        $primaryInvoice = $payment->invoices->first();
        $displayTitle = $primaryInvoice?->invoice_title
            ?? $primaryInvoice?->invoice_number
            ?? $payment->paymentid;
        $paymentDateBounds = $this->resolvePaymentDateBounds($payment->accountid);
        return view('payments.form', [
            'title' => 'Edit ' . $displayTitle,
            'payment' => $payment,
            'clients' => Client::query()->where('accountid', $payment->accountid)->regular()->get(),
            'invoices' => Invoice::query()->where('accountid', $payment->accountid)->with(['client', 'invoiceItems'])
                ->get(),
            'selectedClientId' => $payment->clientid,
            'selectedInvoiceIds' => $payment->invoices->pluck('invoiceid')->filter()->values()->all(),
            'selectedCurrency' => $payment->client?->currency ?? 'INR',
            'paymentDateBounds' => $paymentDateBounds,
        ]);
    }

    public function paymentsUpdate(Request $request, Payment $payment)
    {
        $this->ensurePaymentBelongsToAccount($payment);
        if (strtolower(trim((string) ($payment->status ?? 'active'))) === 'cancelled') {
            return redirect()
                ->route('payments.index', ['c' => $payment->clientid])
                ->with('error', 'Cancelled payment cannot be edited.');
        }
        $paymentDateBounds = $this->resolvePaymentDateBounds($payment->accountid);
        $validated = $request->validate([
            'payment_flow' => 'nullable|in:standard,tds',
            'clientid' => 'required|exists:clients,clientid',
            'invoice_ids' => 'nullable|array',
            'invoice_ids.*' => 'string|exists:invoices,invoiceid',
            'invoice_received_amounts' => 'nullable|array',
            'invoice_received_amounts.*' => 'numeric|min:0',
            'invoice_tds_amounts' => 'nullable|array',
            'invoice_tds_amounts.*' => 'numeric|min:0',
            'received_amount' => 'required|numeric|min:0',
            'tds_amount' => 'nullable|numeric|min:0',
            'payment_date' => 'required|date_format:Y-m-d|after_or_equal:' . $paymentDateBounds['min_date'] . '|before_or_equal:' . $paymentDateBounds['max_date'],
            'mode' => 'required|in:Bank Transfer,Online,Cash',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
        ]);
        $paymentFlow = (string) ($validated['payment_flow'] ?? 'standard');
        if ($paymentFlow === 'standard' && (float) $validated['received_amount'] <= 0) {
            return redirect()->back()->withInput()->withErrors(['received_amount' => 'Amount must be greater than zero for standard payment.']);
        }
        if ($paymentFlow === 'tds' && (float) ($validated['tds_amount'] ?? 0) <= 0) {
            return redirect()->back()->withInput()->withErrors(['tds_amount' => 'TDS amount must be greater than zero for TDS payment.']);
        }

        $invoiceIds = $this->requestInvoiceIds($request);
        $customReceived = $request->input('invoice_received_amounts', []);
        $customTds = $request->input('invoice_tds_amounts', []);
        $invoiceMap = Invoice::query()
            ->whereIn('invoiceid', $invoiceIds)
            ->get()
            ->keyBy('invoiceid');

        foreach ($invoiceMap as $invoice) {
            if ($invoice->clientid !== $validated['clientid']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['invoice_ids' => 'One of the selected invoices does not belong to the selected client.']);
            }
        }

        if (!empty($invoiceIds)) {
            $sumReceived = 0;
            $sumTds = 0;
            foreach ($invoiceIds as $invoiceId) {
                $invoice = $invoiceMap->get($invoiceId);
                if (!$invoice) {
                    continue;
                }

                $rowReceived = (float) ($customReceived[$invoiceId] ?? 0);
                $rowTds = (float) ($customTds[$invoiceId] ?? 0);
                $rowAllocation = $rowReceived + $rowTds;

                $storedAllocation = 0.0;
                $detail = $payment->paymentDetails()->where('invoiceid', $invoiceId)->first();
                if ($detail) {
                    $storedAllocation = (float) ($detail->received_amount ?? 0) + (float) ($detail->tds_amount ?? 0);
                }

                $previousAllocations = (float) $invoice->amount_paid - $storedAllocation;
                $grandTotal = (float) ($invoice->grand_total ?? 0);
                $withoutTaxTotal = (float) ($invoice->subtotal - $invoice->discount_total);
                $balanceDue = (float) ($invoice->balance_due ?? max(0, $grandTotal - ((float) $invoice->amount_paid)));
                $baseDueWithoutTax = $grandTotal > 0
                    ? max(0.0, ($balanceDue * $withoutTaxTotal) / $grandTotal)
                    : max(0.0, $balanceDue);
                $availableLimit = $paymentFlow === 'standard'
                    ? max(0.0, (float) ($invoice->balance_due ?? 0) + $storedAllocation)
                    : max(0.0, $baseDueWithoutTax + $storedAllocation, $storedAllocation);

                if ($rowAllocation > ($availableLimit + 0.1)) {
                    $invNum = $invoice->ti_number ?: $invoice->pi_number ?: $invoice->invoice_number ?: $invoiceId;
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['invoice_ids' => "Allocation for invoice #{$invNum} exceeds its available amount without tax of {$availableLimit}."]);
                }

                $sumReceived += $rowReceived;
                $sumTds += $rowTds;
            }

            $mainReceived = (float) $validated['received_amount'];
            $mainTds = (float) ($validated['tds_amount'] ?? 0);

            if ($paymentFlow === 'standard' && ($sumReceived - $mainReceived) > 0.1) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['received_amount' => 'The sum of allocated invoice received amounts must not exceed the main received amount.']);
            }
            if ($paymentFlow !== 'standard' && abs($sumReceived - $mainReceived) > 0.1) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['received_amount' => 'The sum of allocated invoice received amounts must equal the main received amount.']);
            }
            if ($paymentFlow === 'standard' && $sumTds > 0.1) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['tds_amount' => 'Standard payment does not allow invoice-level TDS allocation.']);
            }
            if ($paymentFlow !== 'standard' && abs($sumTds - $mainTds) > 0.1) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['tds_amount' => 'The sum of allocated invoice TDS amounts must equal the main TDS amount.']);
            }
            if (($sumReceived + $sumTds) > ($mainReceived + $mainTds + 0.1)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['received_amount' => 'Total invoice allocations must not exceed the Total Settlement Amount.']);
            }
        }

        $previousInvoiceIds = $payment->paymentDetails()->pluck('invoiceid')->filter()->values()->all();

        DB::transaction(function () use ($payment, $validated, $request, $invoiceIds, $previousInvoiceIds, $customReceived, $customTds, $paymentFlow) {
            $updatePayload = [
                'fy_id' => $payment->fy_id ?: $this->resolveDefaultFyId($payment->accountid),
                'clientid' => $validated['clientid'],
                'receipt_number' => trim((string) ($payment->receipt_number ?? '')) !== ''
                    ? (string) $payment->receipt_number
                    : $this->generatePaymentReceiptNumber($payment->accountid),
                'received_amount' => $paymentFlow === 'standard' ? (float) $validated['received_amount'] : 0.0,
                'tds_amount' => $paymentFlow === 'standard' ? 0.0 : (float) ($validated['tds_amount'] ?? 0),
                'payment_date' => $validated['payment_date'],
                'mode' => $validated['mode'],
                'reference_number' => $validated['reference_number'] ?? null,
                'description' => $validated['description'] ?? null,
            ];
            if ($this->hasPaymentsStatusColumn()) {
                $updatePayload['status'] = (string) ($payment->status ?: 'active');
            }
            $payment->update($updatePayload);

            $this->syncPaymentLedgerEntry($payment);
            $this->syncPaymentDetails($payment, $invoiceIds, $customReceived, $customTds);
            $this->refreshPaymentInvoices(array_values(array_unique(array_merge($previousInvoiceIds, $invoiceIds))));
        });

        return redirect()
            ->route('payments.index', ['c' => $payment->clientid])
            ->with('success', 'Payment updated successfully.');
    }

    public function paymentsDestroy(Payment $payment)
    {
        $this->ensurePaymentBelongsToAccount($payment);
        $invoiceIds = $payment->paymentDetails()->pluck('invoiceid')->filter()->values()->all();

        DB::transaction(function () use ($payment, $invoiceIds) {
            if ($this->hasPaymentsStatusColumn()) {
                $payment->update(['status' => 'cancelled']);
            }
            $this->syncPaymentLedgerEntry($payment);
            $this->refreshPaymentInvoices($invoiceIds);
        });

        return redirect()
            ->route('payments.index', ['c' => $payment->clientid])
            ->with('success', 'Payment cancelled successfully.');
    }

    public function paymentsRestore(Payment $payment)
    {
        $this->ensurePaymentBelongsToAccount($payment);
        $invoiceIds = $payment->paymentDetails()->pluck('invoiceid')->filter()->values()->all();

        DB::transaction(function () use ($payment, $invoiceIds) {
            if ($this->hasPaymentsStatusColumn()) {
                $payment->update(['status' => 'active']);
            }
            $this->syncPaymentLedgerEntry($payment);
            $this->refreshPaymentInvoices($invoiceIds);
        });

        return redirect()
            ->route('payments.index', ['c' => $payment->clientid])
            ->with('success', 'Payment restored successfully.');
    }

    private function refreshInvoicePaymentStatus(Invoice $invoice): void
    {
        $invoice->loadMissing(['paymentDetails.payment']);
        $hasPaymentStatusColumn = $this->hasPaymentsStatusColumn();

        $amountPaid = (float) $invoice->paymentDetails->sum(function (PaymentDetail $detail) use ($hasPaymentStatusColumn) {
            if ($hasPaymentStatusColumn && strtolower(trim((string) ($detail->payment?->status ?? 'active'))) === 'cancelled') {
                return 0;
            }
            return (float) ($detail->received_amount ?? 0) + (float) ($detail->tds_amount ?? 0);
        });
        $amountPaid = max(0, $amountPaid);
        $baseTotal = (float) ($invoice->subtotal - $invoice->discount_total);
        $baseBalanceDue = max(0.0, $baseTotal - $amountPaid);

        $invoice->payment_status = $amountPaid > 0 && $baseBalanceDue <= 0.1 && $baseTotal > 0
            ? 'paid'
            : ($amountPaid > 0 ? 'partly_paid' : 'unpaid');
        $invoice->save();
    }

    private function refreshPaymentInvoices(array $invoiceIds): void
    {
        foreach ($this->normalizeInvoiceIds($invoiceIds) as $invoiceId) {
            $invoice = Invoice::query()->find($invoiceId);
            if ($invoice) {
                $this->refreshInvoicePaymentStatus($invoice);
            }
        }
    }

    private function syncPaymentLedgerEntry(Payment $payment): void
    {
        $ledgerEntry = Ledger::query()
            ->where('invoiceid_paymentid', $payment->paymentid)
            ->where('type', 'cr')
            ->first();

        if (!$ledgerEntry) {
            $ledgerEntry = new Ledger();
            $ledgerEntry->invoiceid_paymentid = $payment->paymentid;
        }

        $ledgerEntry->accountid = $payment->accountid;
        $ledgerEntry->clientid = $payment->clientid;
        $ledgerEntry->date = $payment->payment_date;
        $receivedAmount = (float) ($payment->received_amount ?? 0);
        $tdsAmount = (float) ($payment->tds_amount ?? 0);
        $ledgerEntry->amount = $receivedAmount > 0 ? $receivedAmount : $tdsAmount;
        $ledgerEntry->type = 'cr';
        $ledgerEntry->mode = $payment->mode;
        $ledgerEntry->reference_number = $payment->reference_number;
        $ledgerEntry->description = $payment->description;
        if ($this->hasLedgerStatusColumn()) {
            $ledgerEntry->status = strtolower(trim((string) ($payment->status ?? 'active')));
        }
        $ledgerEntry->save();
    }

    private function syncInvoiceLedgerStatus(Invoice $invoice, string $status): void
    {
        Ledger::query()
            ->where('invoiceid_paymentid', $invoice->invoiceid)
            ->where('type', 'dr')
            ->update(['status' => $status]);
    }

    private function hasPaymentsStatusColumn(): bool
    {
        static $hasColumn;
        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn('payments', 'status');
        }
        return $hasColumn;
    }

    private function hasLedgerStatusColumn(): bool
    {
        static $hasColumn;
        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn('ledger', 'status');
        }
        return $hasColumn;
    }

    private function deletePaymentLedgerEntry(Payment $payment): void
    {
        Ledger::query()
            ->where('invoiceid_paymentid', $payment->paymentid)
            ->where('type', 'cr')
            ->delete();
    }

    private function dispatchPaymentReceivedCommunications(Payment $payment): void
    {
        $payment->loadMissing(['client.billingDetail', 'invoices', 'paymentDetails']);
        $accountId = (string) $payment->accountid;

        $templates = MessageTemplate::query()
            ->where('accountid', $accountId)
            ->where('template_type', 'payment_received')
            ->where('is_active', true)
            ->whereIn('channel', ['email', 'whatsapp', 'sms'])
            ->orderBy('channel')
            ->get();

        if ($templates->isEmpty()) {
            return;
        }

        foreach ($templates as $template) {
            $send = $this->sendPaymentCommunication($payment, $template);
            $status = $send['ok'] ? 'sent' : 'failed';

            CommunicationLog::query()->create([
                'accountid' => $accountId,
                'invoiceid' => (string) ($payment->invoice?->invoiceid ?? ''),
                'clientid' => (string) ($payment->clientid ?? ''),
                'from_email' => (string) ($send['from_email'] ?? ''),
                'to_email' => (string) ($send['to_email'] ?? ''),
                'phone_number' => (string) ($send['phone'] ?? ''),
                'subject' => $send['subject'] ?? null,
                'body' => $send['body'] ?? null,
                'attachment_type' => 'payment_received',
                'channel' => (string) $template->channel,
                'status' => $status,
                'created_by' => (string) (auth()->user()?->userid ?? auth()->id() ?? 'SYSTEM'),
            ]);
        }
    }

    private function sendPaymentCommunication(Payment $payment, MessageTemplate $template): array
    {
        $channel = (string) $template->channel;
        $account = Account::query()->find((string) $payment->accountid);
        $accountBilling = AccountBillingDetail::query()->where('accountid', (string) $payment->accountid)->first();
        $toEmail = $this->resolvePaymentRecipientEmail($payment);
        $phone = trim((string) (
            $payment->client?->billingDetail?->billing_phone
            ?? $payment->client?->whatsapp_number
            ?? $payment->client?->phone
            ?? ''
        ));

        if ($channel === 'email' && $toEmail === '') {
            return ['ok' => false, 'message' => 'Client email not found.', 'to_email' => '', 'phone' => $phone];
        }
        if ($channel !== 'email' && $phone === '') {
            return ['ok' => false, 'message' => 'Client phone/whatsapp not found.', 'to_email' => $toEmail, 'phone' => ''];
        }

        $replace = $this->buildPaymentTemplateReplacements(
            $payment,
            (string) ($accountBilling?->billing_name ?? $account?->name ?? '')
        );
        $subject = strtr((string) ($template->subject ?? ''), $replace);
        $body = strtr((string) ($template->body ?? ''), $replace);
        $payload = [
            'account_id' => (string) $payment->accountid,
            'campaign_name' => '',
            'schedule_at' => now()->toIso8601String(),
            'source_url' => config('app.url'),
            'notes' => 'Payment received notification',
            'records' => [$this->buildPaymentRecipientRecord($payment, $channel, $toEmail, $phone)],
        ];

        if ($channel === 'email') {
            $payload['subject'] = $this->sanitizeForCampioText($subject);
            // Keep editor formatting for email; do not strip HTML decorations.
            $payload['message'] = $body;
            $payload['sender_id'] = (string) ($accountBilling?->billing_name ?: $accountBilling?->billing_from_email ?: '');
        } else {
            $payload['message'] = $this->sanitizeForCampioText($this->htmlToPlainText($body));
            if (!empty($template->template_id)) {
                $payload['template_id'] = (string) $template->template_id;
            }
            if (!empty($template->meta_template_id)) {
                $payload['meta_template_id'] = (string) $template->meta_template_id;
            }
            if (!empty($template->sender_id)) {
                $payload['sender_id'] = (string) $template->sender_id;
            }
        }

        $sendResult = $this->sendViaCampioForPayments($channel, $payload);

        return $sendResult + [
            'to_email' => $toEmail,
            'phone' => $phone,
            'subject' => $channel === 'email' ? $subject : null,
            'body' => $body,
            'from_email' => (string) ($accountBilling?->billing_from_email ?? ''),
        ];
    }

    private function resolvePaymentRecipientEmail(Payment $payment): string
    {
        $candidates = [
            (string) ($payment->client?->billingDetail?->billing_email ?? ''),
            (string) ($payment->client?->billing_email ?? ''),
            (string) ($payment->client?->primary_email ?? ''),
            (string) ($payment->client?->email ?? ''),
        ];

        foreach ($candidates as $candidate) {
            $parts = preg_split('/[,;]+/', $candidate) ?: [];
            foreach ($parts as $part) {
                $email = trim($part);
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return $email;
                }
            }
        }

        return '';
    }

    private function buildPaymentTemplateReplacements(Payment $payment, string $businessName = ''): array
    {
        $clientName = (string) ($payment->client?->business_name ?: $payment->client?->contact_name ?: 'Client');
        $invoice = $payment->invoice;
        $invoiceNumber = (string) ($invoice?->ti_number ?: $invoice?->pi_number ?: '');
        $invoiceTitle = (string) ($invoice?->invoice_title ?? '');
        $rawAmount = (float) ($payment->received_amount ?? 0);
        $amount = fmod($rawAmount, 1.0) == 0.0
            ? number_format($rawAmount, 0, '.', '')
            : number_format($rawAmount, 2, '.', '');
        $currency = (string) ($payment->client?->currency ?? 'INR');
        $paymentDate = $payment->payment_date?->format('d M Y') ?? Carbon::today()->format('d M Y');

        return [
            '{{client_name}}' => $clientName,
            '{{client_business_name}}' => (string) ($payment->client?->business_name ?? ''),
            '{{client_contact_person}}' => (string) ($payment->client?->contact_name ?? ''),
            '{{business_name}}' => $businessName,
            '{{company_name}}' => $businessName,
            '{{account_name}}' => $businessName,
            '{{payment_amount}}' => $amount,
            '{{amount}}' => $amount,
            '{{currency}}' => $currency,
            '{{payment_date}}' => $paymentDate,
            '{{payment_mode}}' => (string) ($payment->mode ?? ''),
            '{{reference_number}}' => (string) ($payment->reference_number ?? ''),
            '{{invoice_number}}' => $invoiceNumber,
            '{{invoice_title}}' => $invoiceTitle,
            '{{payment_type}}' => ((float) ($payment->tds_amount ?? 0) > 0) ? 'tds' : 'payment',
        ];
    }

    private function buildPaymentRecipientRecord(Payment $payment, string $channel, string $toEmail, string $phone): array
    {
        $clientName = trim((string) (
            $payment->client?->business_name
            ?: $payment->client?->contact_name
            ?: 'Customer'
        ));
        $record = [
            'id' => (string) ($payment->clientid ?: $payment->paymentid),
            'name' => $clientName,
            'student_customer_name' => $clientName,
            'paymentid' => (string) ($payment->paymentid ?? ''),
            'payment_amount' => (string) ($payment->received_amount ?? '0'),
        ];

        if ($channel === 'email') {
            $record['email'] = $toEmail;
            return $record;
        }

        $phoneDigits = preg_replace('/[^0-9]/', '', (string) $phone);
        if (strlen($phoneDigits) === 12 && str_starts_with($phoneDigits, '91')) {
            $phoneDigits = substr($phoneDigits, 2);
        }
        $record['mobile'] = $phoneDigits !== '' ? $phoneDigits : $phone;
        return $record;
    }

    private function sendViaCampioForPayments(string $channel, array $payload): array
    {
        $baseUrl = rtrim((string) env('CAMPIO_BASE_URL', 'http://alpha.skoolready.com/campio'), '/');
        if ($baseUrl === '') {
            return ['ok' => false, 'message' => 'CAMPIO_BASE_URL is not configured.'];
        }

        $endpoint = $baseUrl . '/api/campaigns/schedule/' . $channel;
        $token = trim((string) env('CAMPIO_AUTH_TOKEN', ''));
        $apiKey = trim((string) env('CAMPIO_API_KEY', ''));

        $request = Http::acceptJson()->asJson()->timeout(30);
        if ($token !== '') {
            $request = $request->withToken($token);
        }
        if ($apiKey !== '') {
            $request = $request->withHeaders(['X-API-KEY' => $apiKey]);
        }

        try {
            $response = $request->post($endpoint, $payload);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Campio request failed: ' . $e->getMessage()];
        }

        $json = $response->json();
        if (!$response->successful()) {
            Log::error('Campio payment_received dispatch failed', [
                'channel' => $channel,
                'status' => $response->status(),
                'response' => $json,
            ]);
            return [
                'ok' => false,
                'message' => is_array($json)
                    ? ((string) ($json['message'] ?? 'Campio API returned an error.'))
                    : ('Campio API returned HTTP ' . $response->status() . '.'),
            ];
        }

        return [
            'ok' => true,
            'campaign_id' => (string) (is_array($json) ? ($json['campaign_id'] ?? '') : ''),
        ];
    }

    private function sanitizeForCampioText(string $text): string
    {
        $value = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = str_replace(["\\r\\n", "\\n"], "\n", $value);
        $value = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $value) ?? $value;
        $value = preg_replace('/[^\P{C}\n\t]+/u', '', $value) ?? $value;
        $value = preg_replace("/\n{3,}/", "\n\n", $value) ?? $value;
        return trim($value);
    }

    private function htmlToPlainText(string $value): string
    {
        $withBreaks = str_replace(["\r\n", "\r"], "\n", $value);
        $withBreaks = preg_replace('/<br\s*\/?>/i', "\n", $withBreaks) ?? $withBreaks;
        $withBreaks = preg_replace('/<\/p>/i', "\n", $withBreaks) ?? $withBreaks;
        $withBreaks = preg_replace('/<p[^>]*>/i', '', $withBreaks) ?? $withBreaks;
        $withBreaks = preg_replace('/<\/div>/i', "\n", $withBreaks) ?? $withBreaks;
        $withBreaks = preg_replace('/<div[^>]*>/i', '', $withBreaks) ?? $withBreaks;
        $plain = strip_tags($withBreaks);

        return trim(preg_replace("/\n{3,}/", "\n\n", $plain) ?? $plain);
    }
}
