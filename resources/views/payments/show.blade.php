@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">{{ $payment->reference ?? 'Payment #' . $payment->id }}</p>
        <h3>Payment details</h3>
    </div>
    <div>
        <a href="{{ route('payments.edit', $payment) }}" class="primary-button">Edit</a>
        <form method="POST" action="{{ route('payments.destroy', $payment) }}" class="inline-delete" onsubmit="return confirm('Delete this payment?')">
            @csrf @method('DELETE')
            <button type="submit" class="danger-button">Delete</button>
        </form>
    </div>
</section>

<section class="panel-card">
    <div class="client-header">
        <div>
            <h1>{{ $payment->reference ?? 'Payment #' . $payment->id }}</h1>
            <p>Client: {{ $payment->client->business_name ?? $payment->client->contact_name ?? 'Client' }}</p>
        </div>
        <div class="client-stats">
            <strong>Rs {{ number_format($payment->amount ?? 0, 2) }}</strong>
            <span>{{ $payment->paid_at }} via {{ $payment->method ?? 'N/A' }}</span>
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
        <dd>{{ $payment->invoice->number ?? 'N/A' }}</dd>
        @endif
        <dt>Amount</dt>
        <dd>Rs {{ number_format($payment->amount ?? 0, 2) }}</dd>
        <dt>Date</dt>
        <dd>{{ $payment->paid_at }}</dd>
        <dt>Method</dt>
        <dd>{{ ucfirst($payment->method ?? 'N/A') }}</dd>
        @if($payment->reference)
        <dt>Reference</dt>
        <dd>{{ $payment->reference }}</dd>
        @endif
        @if($payment->notes)
        <dt>Notes</dt>
        <dd>{{ $payment->notes }}</dd>
        @endif
    </dl>
</section>
@endsection

