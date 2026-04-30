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
        $query = Payment::with('client');
        $searchTerm = request('search', '');

        if ($searchTerm) {
            $query->where('reference_number', 'like', '%' . $searchTerm . '%')
                ->orWhereHas('client', function ($q) use ($searchTerm) {
                    $q->where('business_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('contact_name', 'like', '%' . $searchTerm . '%');
                });
        }
        $resultCount = $query->count();

        $payments = $query->latest()->take(20)->get()->map(function ($payment) {
            return [
                'record_id' => $payment->paymentid,
                'number' => $payment->payment_number,
                'client' => $payment->client->business_name ?? 'Client',
                'date' => $payment->payment_date?->format('d M Y'),
                'method' => $payment->payment_method ?? 'Bank Transfer',
                'amount' => 'Rs ' . number_format($payment->amount ?? 0),
                'status' => $payment->status ?? 'Completed',
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
        return view('payments.form', [
            'title' => 'Record New Payment',
            'clients' => Client::all(),
            'invoices' => Invoice::with('client')
                ->where('status', '!=', 'paid')
                ->get(),
        ]);
    }

    public function paymentsStore(Request $request)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'invoiceid' => 'nullable|exists:invoices,invoiceid',
            'reference' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string',
            'paid_at' => 'required|date',
            'notes' => 'nullable|string',
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
            'invoiceid' => $validated['invoiceid'],
            'payment_number' => 'PAY-' . strtoupper(bin2hex(random_bytes(3))),
            'payment_date' => $validated['paid_at'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['method'],
            'reference_number' => $validated['reference'],
            'notes' => $validated['notes'],
            'status' => 'completed',
            'received_by' => (auth()->user() instanceof \App\Models\User) ? auth()->id() : null,
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
        return view('payments.show', [
            'title' => $payment->payment_number ?? 'Payment',
            'subtitle' => 'Payment Details',
            'payment' => $payment,
        ]);
    }

    public function paymentsEdit(Payment $payment): View
    {
        return view('payments.form', [
            'title' => 'Edit ' . ($payment->payment_number ?? 'Payment'),
            'payment' => $payment,
            'clients' => Client::all(),
            'invoices' => Invoice::with('client')
                ->get(),
        ]);
    }

    public function paymentsUpdate(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'invoiceid' => 'nullable|exists:invoices,invoiceid',
            'reference' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string',
            'paid_at' => 'required|date',
            'notes' => 'nullable|string',
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
            'invoiceid' => $validated['invoiceid'],
            'payment_date' => $validated['paid_at'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['method'],
            'reference_number' => $validated['reference'],
            'notes' => $validated['notes'],
        ]);

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

        $amountPaid = (float) ($invoice->payments->sum('amount') ?? 0);
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
