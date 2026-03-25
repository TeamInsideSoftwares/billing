@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">{{ $client->business_name ?? $client->contact_name }}</p>
        <h3>Client details</h3>
    </div>
    <div>
        <a href="{{ route('clients.edit', $client) }}" class="primary-button">Edit</a>
        <form method="POST" action="{{ route('clients.destroy', $client) }}" class="inline-delete" onsubmit="return confirm('Delete this client?')">
            @csrf @method('DELETE')
            <button type="submit" class="danger-button">Delete</button>
        </form>
    </div>
</section>

<section class="panel-card">
    <div class="client-header">
        <div>
            <h1>{{ $client->business_name ?? $client->contact_name }}</h1>
            <p>{{ $client->email }}</p>
            <span class="status-pill {{ strtolower($client->status ?? 'active') }}">{{ ucfirst($client->status ?? 'Active') }}</span>
        </div>
        <div class="client-stats">
            <strong>Outstanding: {{ $outstanding }}</strong>
            <span>Recent activity...</span>
        </div>
    </div>
</section>

<section class="panel-card">
    <h3>Contact Information</h3>
    <dl>
        <dt>Contact</dt>
        <dd>{{ $client->contact_name }}</dd>
        <dt>Phone</dt>
        <dd>{{ $client->phone }}</dd>
        <dt>Billing Email</dt>
        <dd>{{ $client->billing_email }}</dd>
    </dl>
</section>

@if($client->invoices->count())
<section class="panel-card">
    <h3>Invoices ({{ $client->invoices->count() }})</h3>
    <div class="table-list">
        @foreach($client->invoices->take(5) as $invoice)
        <div class="table-row">
            <div><strong>{{ $invoice->number }}</strong></div>
            <div>Rs {{ number_format($invoice->total) }}</div>
            <div><span class="status-pill">{{ $invoice->status }}</span></div>
        </div>
        @endforeach
    </div>
</section>
@endif

@endsection

