@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('payments.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left icon-spaced"></i>Back to Payments
    </a>
    <a href="{{ route('payments.edit', $payment) }}" class="secondary-button">
        <i class="fas fa-edit icon-spaced"></i>Edit
    </a>
    <form method="POST" action="{{ route('payments.destroy', $payment) }}" class="inline-delete" onsubmit="return confirm('Delete this payment?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="secondary-button">
            <i class="fas fa-trash icon-spaced"></i>Delete
        </button>
    </form>
@endsection

@section('content')
@php
    $currency = $payment->client->currency ?? 'INR';
    $amount = (float) ($payment->received_amount ?? 0);
@endphp
<section class="panel-card">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h2 class="mb-1">Ledger Entry {{ $displayTitle ?? ($payment->invoice->invoice_title ?? $payment->invoice->invoice_number ?? $payment->paymentid) }}</h2>
            <p class="text-muted mb-0">{{ $payment->client->business_name ?? $payment->client->contact_name ?? 'Client' }}</p>
        </div>
        <div class="text-end">
            <div class="text-muted mt-2">{{ optional($payment->payment_date)->format('d M Y') }}</div>
        </div>
    </div>
</section>

<section class="panel-card">
    <h3 class="mb-3">Payment View</h3>
    <div class="table-shell">
        <table class="data-table m-0">
            <thead>
                <tr>
                    <th>Particulars</th>
                    <th class="text-end">Amount ({{ $currency }})</th>
                    <th class="text-end">TDS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $payment->client->business_name ?? $payment->client->contact_name ?? 'Client' }}</strong>
                        <div class="text-muted">Mode: {{ strtoupper($payment->mode ?? '-') }}</div>
                        @if($payment->invoice)
                            <div class="text-muted">Invoice: {{ $payment->invoice->invoice_number ?? '-' }}</div>
                        @endif
                        @if($payment->reference_number)
                            <div class="text-muted">Ref: {{ $payment->reference_number }}</div>
                        @endif
                        @if(!empty($payment->description))
                            <div class="text-muted">Description: {{ $payment->description }}</div>
                        @endif
                    </td>
                    <td class="text-end">{{ number_format($amount, 2) }}</td>
                    <td class="text-end">{{ $payment->tds ? 'Yes' : 'No' }}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th>Total Amount</th>
                    <th class="text-end" colspan="2">
                        {{ $currency }} {{ number_format($amount, 2) }}
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>
</section>
@endsection
