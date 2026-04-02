@extends('layouts.app')

@section('content')

<section class="section-bar">
    <div>
        <p class="eyebrow">{{ $quotation->number }}</p>
        <h3 style="margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">Quotation Details</h3>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="{{ route('quotations.edit', $quotation) }}" class="icon-action-btn edit" title="Edit" style="width: 36px; height: 36px; font-size: 1rem;">
            <i class="fas fa-edit"></i>
        </a>
        <form method="POST" action="{{ route('quotations.destroy', $quotation) }}" class="inline-delete" onsubmit="return confirm('Delete this quotation?')">
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
            <h1>{{ $quotation->number }}</h1>
            <p>Client: {{ $quotation->client->business_name ?? $quotation->client->contact_name ?? 'Client' }}</p>
            <span class="status-pill {{ strtolower($quotation->status ?? 'draft') }}">{{ ucfirst($quotation->status ?? 'Draft') }}</span>
        </div>
        <div class="client-stats">
            <strong>Issue Date: {{ $quotation->issue_date }}</strong>
            @if($quotation->expiry_date)
            <span>Expires {{ $quotation->expiry_date }}</span>
            @endif
        </div>
    </div>
</section>

<section class="panel-card">
    <h3>Details</h3>
    <dl>
        <dt>Client</dt>
        <dd>{{ $quotation->client->business_name ?? $quotation->client->contact_name ?? 'N/A' }}</dd>
        <dt>Issue Date</dt>
        <dd>{{ $quotation->issue_date }}</dd>
        @if($quotation->expiry_date)
        <dt>Expiry Date</dt>
        <dd>{{ $quotation->expiry_date }}</dd>
        @endif
        <dt>Status</dt>
        <dd><span class="status-pill {{ strtolower($quotation->status) }}">{{ ucfirst($quotation->status) }}</span></dd>
    </dl>
</section>
@endsection

