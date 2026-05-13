@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('payments.index', ['c' => $payment->clientid]) }}" class="secondary-button">
        Back
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
    $invoice = $payment->invoice;

    $currency = $client->currency ?? 'INR';
    $amount = (float) ($payment->received_amount ?? 0);

    $clientName = $client->business_name ?? $client->contact_name ?? 'Client';
    $title = $displayTitle
        ?? $invoice->invoice_title
        ?? $invoice->invoice_number
        ?? $payment->paymentid;

    $paymentDate = optional($payment->payment_date)->format('d M Y');
    $paymentMode = strtoupper($payment->mode ?? '-');
    $paymentType = strtoupper($payment->type ?? 'payment');
@endphp

<section class="panel-card payment-show-card">
    <div class="payment-show-amount mb-3">
        <div class="text-muted small">Received Amount</div>
        <h3 class="mb-0">
                {{ $currency }} {{ number_format($amount) }}
        </h3>
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

                <tr>
                    <th>Payment Type</th>
                    <td>{{ $paymentType }}</td>
                </tr>

                @if($invoice)
                    <tr>
                        <th>Invoice</th>
                        <td>{{ $invoice->invoice_number ?? '-' }}</td>
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

                <tr>
                    <th>Total Received</th>
                    <td>
                        <strong>{{ $currency }} {{ number_format($amount) }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
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
