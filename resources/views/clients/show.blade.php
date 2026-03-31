@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">{{ $client->business_name ?? $client->contact_name }}</p>
        <h3>Client details</h3>
        <a href="{{ route('clients.index') }}" class="text-link">&larr; Back to clients</a>
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
    <div class="client-header" style="display: flex; gap: 2rem; align-items: center;">
        @if($client->logo_path)
            <div class="client-logo" style="width: 100px; height: 100px; border: 1px solid var(--line); border-radius: 1rem; overflow: hidden; background: white; display: grid; place-items: center;">
                <img src="{{ $client->logo_path }}" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
            </div>
        @endif
        <div style="flex: 1;">
            <p class="eyebrow">{{ $client->group_name }}</p>
            <h1>{{ $client->business_name }}</h1>
            <p>{{ $client->email }}</p>
            <span class="status-pill {{ strtolower($client->status ?? 'active') }}">{{ ucfirst($client->status ?? 'Active') }}</span>
        </div>
        <div class="client-stats" style="text-align: right;">
            <p class="eyebrow">Balance</p>
            <strong style="font-size: 1.5rem; display: block; margin-top: 0.5rem;">{{ $client->currency ?? 'INR' }} {{ number_format($outstanding) }}</strong>
        </div>
    </div>
</section>

<div class="content-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
    <section class="panel-card">
        <h4 style="margin-bottom: 1rem; border-bottom: 1px solid var(--line); padding-bottom: 0.5rem;">Contact Information</h4>
        <dl style="display: grid; grid-template-columns: 120px 1fr; gap: 0.75rem;">
            <dt style="color: var(--text-muted); font-size: 0.9rem;">Contact</dt>
            <dd style="font-weight: 600;">{{ $client->contact_name }}</dd>
            
            <dt style="color: var(--text-muted); font-size: 0.9rem;">Phone</dt>
            <dd>{{ $client->phone }}</dd>
            
            @if($client->whatsapp_number)
                <dt style="color: var(--text-muted); font-size: 0.9rem;">WhatsApp</dt>
                <dd>{{ $client->whatsapp_number }}</dd>
            @endif

            <dt style="color: var(--text-muted); font-size: 0.9rem;">Email</dt>
            <dd>{{ $client->email }}</dd>
        </dl>
    </section>

    <section class="panel-card">
        <h4 style="margin-bottom: 1rem; border-bottom: 1px solid var(--line); padding-bottom: 0.5rem;">Billing Details</h4>
        <dl style="display: grid; grid-template-columns: 120px 1fr; gap: 0.75rem;">
            <dt style="color: var(--text-muted); font-size: 0.9rem;">Billing ID</dt>
            <dd style="font-weight: 600;">{{ $client->bd_id ?? 'N/A' }}</dd>

            <dt style="color: var(--text-muted); font-size: 0.9rem;">Business</dt>
            <dd>{{ $client->billingDetail->business_name ?? 'N/A' }}</dd>

            <dt style="color: var(--text-muted); font-size: 0.9rem;">GSTIN</dt>
            <dd style="font-weight: 600;">{{ $client->billingDetail->gstin ?? 'N/A' }}</dd>
            
            <dt style="color: var(--text-muted); font-size: 0.9rem;">Billing Email</dt>
            <dd>{{ $client->billingDetail->billing_email ?? $client->billing_email ?? 'N/A' }}</dd>
            
            <dt style="color: var(--text-muted); font-size: 0.9rem;">Currency</dt>
            <dd>{{ $client->currency ?? 'INR' }}</dd>

            <dt style="color: var(--text-muted); font-size: 0.9rem;">Address</dt>
            <dd>
                {{ $client->billingDetail->address_line_1 ?? '' }}<br>
                {{ $client->billingDetail->city ?? '' }}, {{ $client->billingDetail->state ?? '' }}<br>
                {{ $client->billingDetail->country ?? 'India' }} {{ $client->billingDetail->postal_code ?? '' }}
            </dd>
        </dl>
    </section>
</div>

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

