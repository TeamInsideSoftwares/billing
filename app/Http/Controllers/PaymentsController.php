<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\Ledger;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PaymentsController extends Controller
{
    private function resolveDefaultFyId(string $accountid): ?string
    {
        return FinancialYear::query()
            ->where('accountid', $accountid)
            ->where('default', 1)
            ->value('fy_id');
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

        $query = Payment::query()->where('accountid', $userAccountId)->with(['client', 'invoice']);
        
        if ($clientId) {
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
            $currency = $payment->client->currency ?? 'INR';
            $invoiceNumber = $payment->invoice?->ti_number
                ?? $payment->invoice?->pi_number
                ?? null;
            $invoiceTitle = trim((string) ($payment->invoice?->invoice_title ?? ''));
            $paymentDescription = trim((string) ($payment->description ?? ''));
            $displayTitle = $invoiceTitle !== ''
                ? $invoiceTitle
                : ($paymentDescription !== '' ? $paymentDescription : 'Payment');
            $paymentStatus = strtolower(trim((string) ($payment->status ?? 'active')));
            return [
                'record_id' => $payment->paymentid,
                'number' => $displayTitle,
                'client' => $payment->client->business_name ?? 'Client',
                'invoice' => $invoiceNumber,
                'currency' => $currency,
                'date' => $payment->payment_date?->format('d M Y'),
                'method' => $payment->mode ?? '-',
                'amount' => $receivedAmount,
                'type' => (string) ($payment->type ?? 'payment'),
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
            'clients' => Client::where('accountid', $userAccountId)->orderBy('business_name')->get(),
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
            ->orderBy('business_name')
            ->get(['clientid', 'business_name', 'contact_name']);
        $selectedClient = $clientId !== ''
            ? $clients->firstWhere('clientid', $clientId)
            : null;

        $query = Ledger::query()
            ->where('accountid', $accountid)
            ->with('client')
            ->when($clientId !== '', function ($ledgerQuery) use ($clientId) {
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
            ->with('items')
            ->get(['invoiceid', 'invoice_title', 'pi_number', 'ti_number', 'clientid', 'fy_id', 'status'])
            ->keyBy('invoiceid');

        $paymentMap = Payment::query()
            ->whereIn('paymentid', $paymentIds)
            ->with('invoice')
            ->get(['paymentid', 'invoiceid', 'reference_number', 'mode', 'clientid', 'received_amount', 'type', 'fy_id', 'status'])
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
            $invoiceStatus = strtolower(trim((string) ($payment?->invoice?->status ?? '')));
            return $invoiceStatus !== 'cancelled';
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
                if ($payment) {
                    $credit = (float) ($payment->received_amount ?? 0);
                } else {
                    $credit = $amount;
                }
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
                    $referenceNumber = trim((string) ($payment->reference_number ?? '')) ?: (string) $payment->paymentid;
                    $referenceLabel = $modeLabel;
                    $referenceMeta = $referenceNumber;
                    $referenceUrl = route('payments.show', $payment->paymentid);
                    $entryKind = ($payment->type ?? 'payment') === 'tds' ? 'tds' : 'payment';
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
            ->when($selectedClientId !== '', function ($query) use ($selectedClientId) {
                $query->where('clientid', $selectedClientId);
            })
            ->with(['client.billingDetail', 'items'])
            ->orderBy('issue_date')
            ->orderBy('created_at')
            ->get();

        $rows = $taxInvoices->map(function (Invoice $invoice) use ($normalizeTaxState, $accountState) {
            $clientState = $normalizeTaxState($invoice->client?->state ?? '');
            $sameStateGst = $clientState !== '' && $accountState !== '' && $clientState === $accountState;

            $taxTotal = (float) $invoice->items->sum(function ($item) {
                $lineTotal = (float) ($item->line_total ?? 0);
                $discountedAmount = (float) ($item->discount_amount ?? 0);
                $taxableAmount = max(0, $discountedAmount > 0 ? $discountedAmount : $lineTotal);
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
        $selectedClientId = request('c', request('clientid'));
        $selectedInvoiceId = request('i', request('invoiceid'));

        if ($selectedInvoiceId && !$selectedClientId) {
            $selectedClientId = Invoice::query()
                ->where('invoiceid', $selectedInvoiceId)
                ->value('clientid');
        }

        $selectedClient = $selectedClientId ? Client::query()->find($selectedClientId) : null;

        return view('payments.form', [
            'title' => 'Record New Payment',
            'clients' => Client::query()->where('accountid', $this->resolveAccountId())->get(),
            'invoices' => Invoice::query()->where('accountid', $this->resolveAccountId())->with('client')
                ->where('status', '!=', 'cancelled')
                ->where('payment_status', '!=', 'paid')
                ->get(),
            'selectedClientId' => $selectedClientId,
            'selectedInvoiceId' => $selectedInvoiceId,
            'selectedCurrency' => $selectedClient?->currency ?? 'INR',
        ]);
    }

    public function paymentsStore(Request $request)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'invoiceid' => 'nullable|exists:invoices,invoiceid',
            'received_amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'mode' => 'required|in:Bank Transfer,Online,Cash',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'type' => 'required|in:payment,tds',
        ]);

        $invoice = null;

        if (!empty($validated['invoiceid'])) {
            $invoice = Invoice::query()->find($validated['invoiceid']);

            if ($invoice && $invoice->clientid !== $validated['clientid']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['invoiceid' => 'The selected invoice does not belong to the selected client.']);
            }
        }

        $userAccountId = $this->resolveAccountId();

        $paymentData = [
            'accountid' => $userAccountId,
            'fy_id' => $this->resolveDefaultFyId($userAccountId),
            'clientid' => $validated['clientid'],
            'invoiceid' => $validated['invoiceid'] ?? null,
            'received_amount' => (float) $validated['received_amount'],
            'type' => $validated['type'],
            'payment_date' => $validated['payment_date'],
            'mode' => $validated['mode'],
            'reference_number' => $validated['reference_number'] ?? null,
            'description' => $validated['description'] ?? null,
        ];
        if ($this->hasPaymentsStatusColumn()) {
            $paymentData['status'] = 'active';
        }

        $payment = null;

        DB::transaction(function () use ($paymentData, &$payment, $invoice) {
            $payment = Payment::query()->create($paymentData);
            $this->syncPaymentLedgerEntry($payment);

            if ($payment->invoiceid && $invoice) {
                $this->refreshInvoicePaymentStatus($invoice);
            }
        });

        return redirect()
            ->route('payments.index', ['c' => $payment->clientid])
            ->with('success', 'Payment recorded successfully.');
    }

    public function paymentsShow(Request $request, Payment $payment): View
    {
        $this->ensurePaymentBelongsToAccount($payment);
        $payment->load(['client', 'invoice']);
        $displayTitle = $payment->invoice->invoice_title
            ?? $payment->invoice->invoice_number
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
        $displayTitle = $payment->invoice->invoice_title
            ?? $payment->invoice->invoice_number
            ?? $payment->paymentid;
        return view('payments.form', [
            'title' => 'Edit ' . $displayTitle,
            'payment' => $payment,
            'clients' => Client::query()->where('accountid', $payment->accountid)->get(),
            'invoices' => Invoice::query()->where('accountid', $payment->accountid)->with('client')
                ->get(),
            'selectedClientId' => $payment->clientid,
            'selectedInvoiceId' => $payment->invoiceid,
            'selectedCurrency' => $payment->client?->currency ?? 'INR',
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
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'invoiceid' => 'nullable|exists:invoices,invoiceid',
            'received_amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'mode' => 'required|in:Bank Transfer,Online,Cash',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'type' => 'required|in:payment,tds',
        ]);

        if (!empty($validated['invoiceid'])) {
            $invoice = Invoice::query()->find($validated['invoiceid']);

            if ($invoice && $invoice->clientid !== $validated['clientid']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['invoiceid' => 'The selected invoice does not belong to the selected client.']);
            }
        }

        $previousInvoiceId = $payment->invoiceid;

        DB::transaction(function () use ($payment, $validated, $request, $previousInvoiceId) {
            $updatePayload = [
                'fy_id' => $payment->fy_id ?: $this->resolveDefaultFyId($payment->accountid),
                'clientid' => $validated['clientid'],
                'invoiceid' => $validated['invoiceid'] ?? null,
                'received_amount' => (float) $validated['received_amount'],
                'type' => $validated['type'],
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

            $invoiceIds = array_values(array_filter(array_unique([$previousInvoiceId, $payment->invoiceid])));
            foreach ($invoiceIds as $invoiceId) {
                $invoice = Invoice::query()->find($invoiceId);
                if ($invoice) {
                    $this->refreshInvoicePaymentStatus($invoice);
                }
            }
        });

        return redirect()
            ->route('payments.index', ['c' => $payment->clientid])
            ->with('success', 'Payment updated successfully.');
    }

    public function paymentsDestroy(Payment $payment)
    {
        $this->ensurePaymentBelongsToAccount($payment);
        $invoiceId = $payment->invoiceid;

        DB::transaction(function () use ($payment, $invoiceId) {
            if ($this->hasPaymentsStatusColumn()) {
                $payment->update(['status' => 'cancelled']);
            }
            $this->syncPaymentLedgerEntry($payment);

            if ($invoiceId) {
                $invoice = Invoice::query()->find($invoiceId);
                if ($invoice) {
                    $this->refreshInvoicePaymentStatus($invoice);
                }
            }
        });

        return redirect()
            ->route('payments.index', ['c' => $payment->clientid])
            ->with('success', 'Payment cancelled successfully.');
    }

    public function paymentsRestore(Payment $payment)
    {
        $this->ensurePaymentBelongsToAccount($payment);
        $invoiceId = $payment->invoiceid;

        DB::transaction(function () use ($payment, $invoiceId) {
            if ($this->hasPaymentsStatusColumn()) {
                $payment->update(['status' => 'active']);
            }
            $this->syncPaymentLedgerEntry($payment);

            if ($invoiceId) {
                $invoice = Invoice::query()->find($invoiceId);
                if ($invoice) {
                    $this->refreshInvoicePaymentStatus($invoice);
                }
            }
        });

        return redirect()
            ->route('payments.index', ['c' => $payment->clientid])
            ->with('success', 'Payment restored successfully.');
    }

    private function refreshInvoicePaymentStatus(Invoice $invoice): void
    {
        $invoice->loadMissing('payments');
        $hasPaymentStatusColumn = $this->hasPaymentsStatusColumn();

        $amountPaid = (float) $invoice->payments->sum(function ($payment) use ($hasPaymentStatusColumn) {
            if ($hasPaymentStatusColumn && strtolower(trim((string) ($payment->status ?? 'active'))) === 'cancelled') {
                return 0;
            }
            return (float) ($payment->received_amount ?? 0);
        });
        $amountPaid = max(0, $amountPaid);
        $grandTotal = (float) ($invoice->grand_total ?? 0);
        $balanceDue = max(0, $grandTotal - $amountPaid);

        $invoice->payment_status = $amountPaid > 0 && $balanceDue <= 0 && $grandTotal > 0
            ? 'paid'
            : ($amountPaid > 0 ? 'partly_paid' : 'unpaid');
        $invoice->save();
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
        $ledgerEntry->amount = (float) ($payment->received_amount ?? 0);
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
}
