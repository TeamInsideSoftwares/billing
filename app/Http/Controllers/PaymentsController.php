<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Ledger;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentsController extends Controller
{
    public function payments(): View
    {
        $userAccountId = $this->resolveAccountId();
        $query = Payment::query()->where('accountid', $userAccountId)->with(['client', 'invoice']);
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
            $displayTitle = $payment->invoice->invoice_title
                ?? $invoiceNumber
                ?? $payment->paymentid;
            return [
                'record_id' => $payment->paymentid,
                'number' => $displayTitle,
                'client' => $payment->client->business_name ?? 'Client',
                'invoice' => $invoiceNumber,
                'currency' => $currency,
                'date' => $payment->payment_date?->format('d M Y'),
                'method' => $payment->mode ?? '-',
                'amount' => $receivedAmount,
                'tds' => (bool) ($payment->tds ?? false),
                'description' => (string) ($payment->description ?? ''),
                'reference_number' => (string) ($payment->reference_number ?? ''),
            ];
        });

        return view('payments.index', [
            'title' => 'All Payments',
            'subtitle' => $searchTerm ? 'Search results for "' . $searchTerm . '"' : null,
            'payments' => $payments,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
            'selectedCurrency' => $paymentCurrencies->count() === 1 ? $paymentCurrencies->first() : null,
        ]);
    }

    public function paymentsLedger(): View
    {
        $accountid = $this->resolveAccountId();
        $clientId = trim((string) request('c', ''));
        $type = trim((string) request('type', ''));
        $searchTerm = trim((string) request('search', ''));
        $fromDate = trim((string) request('from', ''));
        $toDate = trim((string) request('to', ''));

        $allowedTypes = ['payment', 'tds', 'invoice'];
        if (!in_array($type, $allowedTypes, true)) {
            $type = '';
        }

        $query = Ledger::query()
            ->where('accountid', $accountid)
            ->with('client')
            ->when($clientId !== '', function ($ledgerQuery) use ($clientId) {
                $ledgerQuery->where('clientid', $clientId);
            })
            ->when($type !== '', function ($ledgerQuery) use ($type) {
                $ledgerQuery->where('type', $type);
            })
            ->when($fromDate !== '', function ($ledgerQuery) use ($fromDate) {
                $ledgerQuery->whereDate('date', '>=', $fromDate);
            })
            ->when($toDate !== '', function ($ledgerQuery) use ($toDate) {
                $ledgerQuery->whereDate('date', '<=', $toDate);
            })
            ->when($searchTerm !== '', function ($ledgerQuery) use ($searchTerm) {
                $ledgerQuery->where(function ($searchQuery) use ($searchTerm) {
                    $searchQuery->where('reference_number', 'like', '%' . $searchTerm . '%')
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

        $invoiceIds = $entries->where('type', 'invoice')->pluck('reference_number')->filter()->unique()->values();
        $paymentIds = $entries->whereIn('type', ['payment', 'tds'])->pluck('reference_number')->filter()->unique()->values();

        $invoiceMap = Invoice::query()
            ->whereIn('invoiceid', $invoiceIds)
            ->get(['invoiceid', 'invoice_title', 'pi_number', 'ti_number', 'clientid'])
            ->keyBy('invoiceid');

        $paymentMap = Payment::query()
            ->whereIn('paymentid', $paymentIds)
            ->get(['paymentid', 'invoiceid', 'reference_number', 'clientid'])
            ->keyBy('paymentid');

        $runningBalance = 0;
        $ledgerEntries = $entries->map(function (Ledger $entry) use (&$runningBalance, $invoiceMap, $paymentMap) {
            $amount = (float) ($entry->amount ?? 0);
            $isInvoice = $entry->type === 'invoice';
            $debit = $isInvoice ? $amount : 0;
            $credit = $isInvoice ? 0 : $amount;
            $runningBalance += $debit - $credit;

            $referenceLabel = $entry->reference_number;
            $referenceUrl = null;

            if ($isInvoice) {
                $invoice = $invoiceMap->get($entry->reference_number);
                if ($invoice) {
                    $referenceLabel = $invoice->ti_number ?: $invoice->pi_number ?: $invoice->invoiceid;
                    $referenceUrl = route('invoices.show', ['invoice' => $invoice->invoiceid, 'c' => $invoice->clientid]);
                }
            } else {
                $payment = $paymentMap->get($entry->reference_number);
                if ($payment) {
                    $referenceLabel = $payment->reference_number ?: $payment->paymentid;
                    $referenceUrl = route('payments.show', $payment->paymentid);
                }
            }

            return [
                'ledgerid' => $entry->ledgerid,
                'date' => $entry->date?->format('d M Y') ?? '-',
                'raw_date' => $entry->date?->format('Y-m-d') ?? '',
                'client_name' => $entry->client?->business_name ?? $entry->client?->contact_name ?? 'Client',
                'type' => $entry->type,
                'type_label' => $entry->type === 'invoice' ? 'Invoice' : ($entry->type === 'tds' ? 'TDS' : 'Payment'),
                'reference_number' => $entry->reference_number,
                'reference_label' => $referenceLabel,
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
        $tdsTotal = (float) $ledgerEntries->where('type', 'tds')->sum('credit');
        $paymentTotal = (float) $ledgerEntries->where('type', 'payment')->sum('credit');
        $closingBalance = $invoiceTotal - $creditTotal;

        return view('payments.ledger', [
            'title' => 'Ledger',
            'subtitle' => 'Statement-style view of invoices, payments, and TDS entries.',
            'ledgerEntries' => $ledgerEntries,
            'clients' => Client::query()->where('accountid', $accountid)->orderBy('business_name')->get(['clientid', 'business_name', 'contact_name']),
            'selectedClientId' => $clientId,
            'selectedType' => $type,
            'searchTerm' => $searchTerm,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'invoiceTotal' => $invoiceTotal,
            'paymentTotal' => $paymentTotal,
            'tdsTotal' => $tdsTotal,
            'closingBalance' => $closingBalance,
        ]);
    }

    public function paymentsGstReport(): View
    {
        $accountid = $this->resolveAccountId();
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
            ->with(['client:clientid,business_name,contact_name,state', 'items'])
            ->orderBy('issue_date')
            ->orderBy('created_at')
            ->get();

        $rows = $taxInvoices->map(function (Invoice $invoice) use ($normalizeTaxState, $accountState) {
            $clientState = $normalizeTaxState($invoice->client?->state ?? '');
            $sameStateGst = $clientState !== '' && $accountState !== '' && $clientState === $accountState;

            $taxTotal = (float) $invoice->items->sum(function ($item) {
                $lineTotal = (float) ($item->line_total ?? 0);
                $discountPercent = (float) ($item->discount_percent ?? 0);
                $discountAmount = isset($item->discount_amount)
                    ? (float) ($item->discount_amount ?? 0)
                    : floor(max(0, $lineTotal * ($discountPercent / 100)));
                $taxableAmount = max(0, $lineTotal - max(0, $discountAmount));
                return ceil($taxableAmount * ((float) ($item->tax_rate ?? 0) / 100));
            });

            $cgst = $sameStateGst ? round($taxTotal / 2, 0) : 0;
            $sgst = $sameStateGst ? round($taxTotal / 2, 0) : 0;
            $igst = $sameStateGst ? 0 : round($taxTotal, 0);

            return [
                'invoiceid' => $invoice->invoiceid,
                'ti_number' => (string) ($invoice->ti_number ?? ''),
                'invoice_title' => (string) ($invoice->invoice_title ?: $invoice->ti_number ?: $invoice->invoiceid),
                'client_name' => (string) ($invoice->client?->business_name ?? $invoice->client?->contact_name ?? 'Client'),
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
                ->where('status', '!=', 'paid')
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
            'tds' => 'nullable|in:0,1',
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
            'clientid' => $validated['clientid'],
            'invoiceid' => $validated['invoiceid'] ?? null,
            'received_amount' => (float) $validated['received_amount'],
            'tds' => $request->boolean('tds'),
            'payment_date' => $validated['payment_date'],
            'mode' => $validated['mode'],
            'reference_number' => $validated['reference_number'] ?? null,
            'description' => $validated['description'] ?? null,
        ];

        $payment = null;

        DB::transaction(function () use ($paymentData, &$payment, $invoice) {
            $payment = Payment::query()->create($paymentData);
            $this->syncPaymentLedgerEntry($payment);

            if ($payment->invoiceid && $invoice) {
                $this->refreshInvoicePaymentStatus($invoice);
            }
        });

        return redirect()->route('payments.index')->with('success', 'Payment recorded successfully.');
    }

    public function paymentsShow(Payment $payment): View
    {
        $payment->load(['client', 'invoice']);
        $displayTitle = $payment->invoice->invoice_title
            ?? $payment->invoice->invoice_number
            ?? $payment->paymentid;
        return view('payments.show', [
            'title' => $displayTitle,
            'subtitle' => 'Payment Details',
            'payment' => $payment,
            'displayTitle' => $displayTitle,
        ]);
    }

    public function paymentsEdit(Payment $payment): View
    {
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
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'invoiceid' => 'nullable|exists:invoices,invoiceid',
            'received_amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'mode' => 'required|in:Bank Transfer,Online,Cash',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'tds' => 'nullable|in:0,1',
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
            $payment->update([
                'clientid' => $validated['clientid'],
                'invoiceid' => $validated['invoiceid'] ?? null,
                'received_amount' => (float) $validated['received_amount'],
                'tds' => $request->boolean('tds'),
                'payment_date' => $validated['payment_date'],
                'mode' => $validated['mode'],
                'reference_number' => $validated['reference_number'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);

            $this->syncPaymentLedgerEntry($payment);

            $invoiceIds = array_values(array_filter(array_unique([$previousInvoiceId, $payment->invoiceid])));
            foreach ($invoiceIds as $invoiceId) {
                $invoice = Invoice::query()->find($invoiceId);
                if ($invoice) {
                    $this->refreshInvoicePaymentStatus($invoice);
                }
            }
        });

        return redirect()->route('payments.index')->with('success', 'Payment updated successfully.');
    }

    public function paymentsDestroy(Payment $payment)
    {
        $invoiceId = $payment->invoiceid;

        DB::transaction(function () use ($payment, $invoiceId) {
            $this->deletePaymentLedgerEntry($payment);
            $payment->delete();

            if ($invoiceId) {
                $invoice = Invoice::query()->find($invoiceId);
                if ($invoice) {
                    $this->refreshInvoicePaymentStatus($invoice);
                }
            }
        });

        return redirect()->route('payments.index')->with('success', 'Payment deleted successfully.');
    }

    private function refreshInvoicePaymentStatus(Invoice $invoice): void
    {
        $invoice->loadMissing('payments');

        $amountPaid = (float) $invoice->payments->sum(function ($payment) {
            return (float) ($payment->received_amount ?? 0);
        });
        $amountPaid = max(0, $amountPaid);
        $grandTotal = (float) ($invoice->grand_total ?? 0);
        $balanceDue = max(0, $grandTotal - $amountPaid);

        if ($amountPaid > 0 && $balanceDue <= 0 && $grandTotal > 0) {
            $invoice->status = 'paid';
        } elseif ($amountPaid > 0) {
            $invoice->status = 'partially-paid';
        } else {
            $invoice->status = 'unpaid';
        }

        $invoice->save();
    }

    private function syncPaymentLedgerEntry(Payment $payment): void
    {
        $ledgerEntry = Ledger::query()
            ->where('reference_number', $payment->paymentid)
            ->whereIn('type', ['payment', 'tds'])
            ->first();

        if (!$ledgerEntry) {
            $ledgerEntry = new Ledger();
            $ledgerEntry->reference_number = $payment->paymentid;
        }

        $ledgerEntry->accountid = $payment->accountid;
        $ledgerEntry->clientid = $payment->clientid;
        $ledgerEntry->date = $payment->payment_date;
        $ledgerEntry->amount = (float) ($payment->received_amount ?? 0);
        $ledgerEntry->type = $payment->tds ? 'tds' : 'payment';
        $ledgerEntry->description = $payment->description;
        $ledgerEntry->save();
    }

    private function deletePaymentLedgerEntry(Payment $payment): void
    {
        Ledger::query()
            ->where('reference_number', $payment->paymentid)
            ->whereIn('type', ['payment', 'tds'])
            ->delete();
    }
}
