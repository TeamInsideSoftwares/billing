@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('payments.index', ['c' => $payment->clientid]) }}" class="secondary-button">
        Back to Payments
    </a>

    <a href="{{ route('payments.edit', $payment) }}" class="secondary-button">
        Edit
    </a>

    @if(strtolower(trim((string) ($payment->status ?? 'active'))) === 'cancelled')
        <form method="POST"
              action="{{ route('payments.restore', $payment) }}"
              class="inline-delete"
              onsubmit="return confirm('Restore this payment?')">
            @csrf
            @method('PATCH')
            <button type="submit" class="secondary-button">
                Restore
            </button>
        </form>
    @else
        <form method="POST"
              action="{{ route('payments.destroy', $payment) }}"
              class="inline-delete"
              onsubmit="return confirm('Cancel this payment?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="secondary-button">
                Cancel
            </button>
        </form>
    @endif
@endsection

@section('content')
@php
    $client = $payment->client;
    $invoices = $payment->invoices ?? collect();
    $invoice = $payment->invoice;

    $currency = $client->currency ?? 'INR';
    $amount = (float) ($payment->received_amount ?? 0);
    $tdsAmount = (float) ($payment->paymentDetails->sum('tds_amount') ?? 0);

    $clientName = $client->business_name ?? $client->contact_name ?? 'Client';
    $title = $displayTitle
        ?? $invoice->invoice_title
        ?? $invoice->invoice_number
        ?? $payment->paymentid;

    $paymentDate = optional($payment->payment_date)->format('d M Y');
    $paymentMode = strtoupper($payment->mode ?? '-');
    $invoiceSummary = $invoices->map(function ($item) {
        return $item->ti_number ?: $item->pi_number ?: $item->invoice_number;
    })->filter()->implode(', ');
    if ($invoiceSummary === '' && $invoice) {
        $invoiceSummary = $invoice->ti_number ?: $invoice->pi_number ?: $invoice->invoice_number ?: '';
    }
@endphp

<section class="panel-card payment-show-card">
    <div class="payment-show-amount mb-3" style="background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0; display: block;">
        <div style="font-size: 0.85rem; color: #64748b; font-weight: 500;">Received Amount</div>
        <h3 style="font-size: 1.75rem; font-weight: 700; color: #0f172a; margin: 0.25rem 0 0.5rem 0;">
            {{ $currency }} {{ number_format($amount) }}
        </h3>
        <p style="font-size: 0.85rem; color: #475569; margin: 0; font-weight: 500; line-height: 1.5;">
            Actual amount received in bank/cash is <strong>{{ $currency }} {{ number_format($amount) }}</strong> and TDS deducted by client is <strong>{{ $currency }} {{ number_format($tdsAmount) }}</strong> (Total Settlement: <strong>{{ $currency }} {{ number_format($amount + $tdsAmount) }}</strong>).
        </p>
    </div>

    <div class="table-shell">
        <table class="data-table m-0">
            <tbody>
                <tr>
                    <th style="width: 220px;">Client</th>
                    <td>{{ $clientName }}</td>
                </tr>

                <tr>
                    <th>Payment Date</th>
                    <td>{{ $paymentDate ?: '-' }}</td>
                </tr>

                <tr>
                    <th>Payment Mode</th>
                    <td>{{ $paymentMode }}</td>
                </tr>

                @if($invoiceSummary !== '')
                    <tr>
                        <th>Invoices</th>
                        <td>{{ $invoiceSummary }}</td>
                    </tr>
                @endif

                @if(!empty($payment->receipt_number))
                    <tr>
                        <th>Receipt Number</th>
                        <td>{{ $payment->receipt_number }}</td>
                    </tr>
                @endif

                @if($payment->reference_number)
                    <tr>
                        <th>Reference Number</th>
                        <td>{{ $payment->reference_number }}</td>
                    </tr>
                @endif

                @if(!empty($payment->description))
                    <tr>
                        <th>Description</th>
                        <td>{{ $payment->description }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    @if($payment->paymentDetails->isNotEmpty())
        <div class="mt-4 pt-3 border-top">
            <h5 class="mb-3 text-muted small uppercase font-bold" style="letter-spacing: 0.05em; font-size: 0.8rem; font-weight: 700; color: #64748b; margin-top: 1.5rem;">Invoice Breakdown</h5>
            <div class="table-shell" style="border: 1px solid #dbe3ea; border-radius: 10px; overflow: hidden; background: #fff; margin-top: 0.5rem;">
                <table class="data-table m-0" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid #dbe3ea; text-align: left;">
                            <th style="padding: 0.75rem 1rem; font-size: 0.8rem; font-weight: 600; color: #475569;">Invoice</th>
                            <th style="padding: 0.75rem 1rem; font-size: 0.8rem; font-weight: 600; color: #475569; text-align: right;">Base Amount (Without Tax)</th>
                            <th style="padding: 0.75rem 1rem; font-size: 0.8rem; font-weight: 600; color: #475569; text-align: right;">TDS Amount</th>
                            <th style="padding: 0.75rem 1rem; font-size: 0.8rem; font-weight: 600; color: #475569; text-align: right;">Received Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payment->paymentDetails as $detail)
                            @php
                                $inv = $detail->invoice;
                                $invNumber = $inv ? ($inv->ti_number ?: $inv->pi_number ?: $inv->invoice_number) : '';
                                $invTitle = $inv ? $inv->invoice_title : '';
                                $dispTitle = $invTitle ?: ($invNumber ? "#$invNumber" : "Invoice #{$detail->invoiceid}");
                                $baseAmt = (float) $detail->received_amount + (float) $detail->tds_amount;
                            @endphp
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 0.75rem 1rem; font-size: 0.85rem; color: #0f172a; font-weight: 500;">
                                    @if($inv)
                                        <a href="{{ route('invoices.pdf', ['invoice' => $inv->invoiceid, 'type' => trim((string) ($inv->ti_number ?? '')) !== '' ? 'tax_invoice' : 'pi']) }}" target="_blank" style="text-decoration: none; color: #4f46e5; font-weight: 600;">
                                            {{ $dispTitle }}
                                        </a>
                                    @else
                                        {{ $dispTitle }}
                                    @endif
                                </td>
                                <td style="padding: 0.75rem 1rem; font-size: 0.85rem; color: #0f172a; text-align: right;">{{ $currency }} {{ number_format($baseAmt) }}</td>
                                <td style="padding: 0.75rem 1rem; font-size: 0.85rem; color: #ef4444; text-align: right;">{{ $currency }} {{ number_format((float)$detail->tds_amount) }}</td>
                                <td style="padding: 0.75rem 1rem; font-size: 0.85rem; color: #10b981; text-align: right; font-weight: 600;">{{ $currency }} {{ number_format((float)$detail->received_amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</section>

<style>
    .payment-show-card {
        max-width: 760px;
        margin: 0 auto;
    }

    .payment-show-amount {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.18rem;
    }
</style>
@endsection
