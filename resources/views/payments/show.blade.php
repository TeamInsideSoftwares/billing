@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div style="display: flex; gap: 0.5rem;">
        <a href="{{ route('payments.edit', $payment) }}" class="icon-action-btn edit" title="Edit" style="width: 36px; height: 36px; font-size: 1rem;">
            <i class="fas fa-edit"></i>
        </a>
        <form method="POST" action="{{ route('payments.destroy', $payment) }}" class="inline-delete" onsubmit="return confirm('Delete this payment?')">
            @csrf @method('DELETE')
            <button type="submit" class="icon-action-btn delete" title="Delete" style="width: 36px; height: 36px; font-size: 1rem;">
                <i class="fas fa-trash"></i>
            </button>
        </form>
    </div>
</section>

<section class="panel-card">
    <div class="client-header">
        <div>
            <h1>{{ $payment->payment_number }}</h1>
            <p>Client: {{ $payment->client->business_name ?? $payment->client->contact_name ?? 'Client' }}</p>
        </div>
        <div class="client-stats">
            <strong>Rs {{ number_format($payment->amount ?? 0, 0) }}</strong>
            <span>{{ $payment->payment_date->format('d M Y') }} via {{ $payment->payment_method ?? 'N/A' }}</span>
        </div>
    </div>
</section>

<section class="panel-card">
    <h3>Details</h3>
    <dl>
        <dt>Client</dt>
        <dd>{{ $payment->client->business_name ?? $payment->client->contact_name ?? 'N/A' }}</dd>
        @if($payment->invoice)
        <dt>Invoice</dt>
        <dd>{{ $payment->invoice->invoice_number ?? 'N/A' }}</dd>
        @endif
        <dt>Amount</dt>
        <dd>Rs {{ number_format($payment->amount ?? 0, 0) }}</dd>
        <dt>Date</dt>
        <dd>{{ $payment->payment_date->format('d M Y') }}</dd>
        <dt>Method</dt>
        <dd>{{ ucfirst($payment->payment_method ?? 'N/A') }}</dd>
        @if($payment->reference_number)
        <dt>Reference</dt>
        <dd>{{ $payment->reference_number }}</dd>
        @endif
        @if($payment->notes)
        <dt>Notes</dt>
        <dd>{{ $payment->notes }}</dd>
        @endif
    </dl>
</section>
@endsection
