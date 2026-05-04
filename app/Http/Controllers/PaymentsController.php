<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PaymentsController extends Controller
{
    public function payments(): View
    {
        $userAccountId = $this->resolveAccountId();
        $query = Payment::where('accountid', $userAccountId)->with(['client', 'invoice']);
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

        $payments = $query->latest('payment_date')->latest('created_at')->take(50)->get()->map(function ($payment) {
            $receivedAmount = (float) ($payment->received_amount ?? 0);
            $tdsAmount = (float) ($payment->tds_amount ?? 0);
            $totalSettled = $receivedAmount + $tdsAmount;
            $currency = $payment->client->currency ?? 'INR';
            $displayTitle = $payment->invoice->invoice_title
                ?? $payment->invoice->invoice_number
                ?? $payment->paymentid;
            return [
                'record_id' => $payment->paymentid,
                'number' => $displayTitle,
                'client' => $payment->client->business_name ?? 'Client',
                'invoice' => $payment->invoice->invoice_number ?? null,
                'currency' => $currency,
                'date' => $payment->payment_date?->format('d M Y'),
                'method' => $payment->mode ?? '-',
                'received_amount' => $receivedAmount,
                'tds_amount' => $tdsAmount,
                'total_settled' => $totalSettled,
            ];
        });

        return view('payments.index', [
            'title' => 'All Payments',
            'subtitle' => $searchTerm ? 'Search results for "' . $searchTerm . '"' : null,
            'payments' => $payments,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
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

        $selectedClient = $selectedClientId ? Client::find($selectedClientId) : null;

        return view('payments.form', [
            'title' => 'Record New Payment',
            'clients' => Client::where('accountid', auth()->user()->accountid ?? 'ACC0000001')->get(),
            'invoices' => Invoice::where('accountid', auth()->user()->accountid ?? 'ACC0000001')->with('client')
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
            'tds_amount' => 'nullable|numeric|min:0',
            'payment_date' => 'required|date',
            'mode' => 'required|in:Bank Transfer,Online,Cash',
            'reference_number' => 'nullable|string|max:100',
        ]);

        $invoice = null;

        if (!empty($validated['invoiceid'])) {
            $invoice = Invoice::find($validated['invoiceid']);

            if ($invoice && $invoice->clientid !== $validated['clientid']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['invoiceid' => 'The selected invoice does not belong to the selected client.']);
            }
        }

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';

        $paymentData = [
            'accountid' => $userAccountId,
            'clientid' => $validated['clientid'],
            'invoiceid' => $validated['invoiceid'] ?? null,
            'received_amount' => $validated['received_amount'],
            'tds_amount' => $validated['tds_amount'] ?? 0,
            'payment_date' => $validated['payment_date'],
            'mode' => $validated['mode'],
            'reference_number' => $validated['reference_number'] ?? null,
        ];

        $payment = Payment::create($paymentData);

        if ($payment->invoiceid && $invoice) {
            $this->refreshInvoicePaymentStatus($invoice);
        }

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
            'clients' => Client::where('accountid', $payment->accountid)->get(),
            'invoices' => Invoice::where('accountid', $payment->accountid)->with('client')
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
            'tds_amount' => 'nullable|numeric|min:0',
            'payment_date' => 'required|date',
            'mode' => 'required|in:Bank Transfer,Online,Cash',
            'reference_number' => 'nullable|string|max:100',
        ]);

        if (!empty($validated['invoiceid'])) {
            $invoice = Invoice::find($validated['invoiceid']);

            if ($invoice && $invoice->clientid !== $validated['clientid']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['invoiceid' => 'The selected invoice does not belong to the selected client.']);
            }
        }

        $payment->update([
            'clientid' => $validated['clientid'],
            'invoiceid' => $validated['invoiceid'] ?? null,
            'received_amount' => $validated['received_amount'],
            'tds_amount' => $validated['tds_amount'] ?? 0,
            'payment_date' => $validated['payment_date'],
            'mode' => $validated['mode'],
            'reference_number' => $validated['reference_number'] ?? null,
        ]);

        if ($payment->invoiceid) {
            $invoice = Invoice::find($payment->invoiceid);
            if ($invoice) {
                $this->refreshInvoicePaymentStatus($invoice);
            }
        }

        return redirect()->route('payments.index')->with('success', 'Payment updated successfully.');
    }

    public function paymentsDestroy(Payment $payment)
    {
        if ($payment->invoiceid) {
            $invoice = Invoice::find($payment->invoiceid);
            if ($invoice) {
                $this->refreshInvoicePaymentStatus($invoice);
            }
        }

        $payment->delete();

        return redirect()->route('payments.index')->with('success', 'Payment deleted successfully.');
    }

    private function refreshInvoicePaymentStatus(Invoice $invoice): void
    {
        $invoice->loadMissing('payments');

        $amountPaid = (float) $invoice->payments->sum(function ($payment) {
            return ((float) ($payment->received_amount ?? 0)) + ((float) ($payment->tds_amount ?? 0));
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
}
