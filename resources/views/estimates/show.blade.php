@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">{{ $estimate->number }}</p>
        <h3>Estimate details</h3>
    </div>
    <div>
        <a href="{{ route('estimates.edit', $estimate) }}" class="primary-button">Edit</a>
        <form method="POST" action="{{ route('estimates.destroy', $estimate) }}" class="inline-delete" onsubmit="return confirm('Delete this estimate?')">
            @csrf @method('DELETE')
            <button type="submit" class="danger-button">Delete</button>
        </form>
    </div>
</section>

<section class="panel-card">
    <div class="client-header">
        <div>
            <h1>{{ $estimate->number }}</h1>
            <p>Client: {{ $estimate->client->business_name ?? $estimate->client->contact_name ?? 'Client' }}</p>
            <span class="status-pill {{ strtolower($estimate->status ?? 'draft') }}">{{ ucfirst($estimate->status ?? 'Draft') }}</span>
        </div>
        <div class="client-stats">
            <strong>Issue Date: {{ $estimate->issue_date }}</strong>
            @if($estimate->expiry_date)
            <span>Expires {{ $estimate->expiry_date }}</span>
            @endif
        </div>
    </div>
</section>

<section class="panel-card">
    <h3>Details</h3>
    <dl>
        <dt>Client</dt>
        <dd>{{ $estimate->client->business_name ?? $estimate->client->contact_name ?? 'N/A' }}</dd>
        <dt>Issue Date</dt>
        <dd>{{ $estimate->issue_date }}</dd>
        @if($estimate->expiry_date)
        <dt>Expiry Date</dt>
        <dd>{{ $estimate->expiry_date }}</dd>
        @endif
        <dt>Status</dt>
        <dd><span class="status-pill {{ strtolower($estimate->status) }}">{{ ucfirst($estimate->status) }}</span></dd>
    </dl>
</section>
@endsection

