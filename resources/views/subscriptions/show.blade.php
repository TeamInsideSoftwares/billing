@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">{{ $subscription->client->business_name ?? $subscription->client->contact_name ?? 'Subscription' }}</p>
        <h3>Subscription details</h3>
    </div>
    <div>
        <a href="{{ route('subscriptions.edit', $subscription) }}" class="primary-button">Edit</a>
        <form method="POST" action="{{ route('subscriptions.destroy', $subscription) }}" class="inline-delete" onsubmit="return confirm('Delete this subscription?')">
            @csrf @method('DELETE')
            <button type="submit" class="danger-button">Delete</button>
        </form>
    </div>
</section>

<section class="panel-card">
    <div class="client-header">
        <div>
            <h1>{{ $subscription->service->name ?? 'Subscription' }}</h1>
            <p>Client: {{ $subscription->client->business_name ?? $subscription->client->contact_name ?? 'Client' }}</p>
            <span class="status-pill {{ strtolower($subscription->status ?? 'active') }}">{{ ucfirst($subscription->status ?? 'Active') }}</span>
        </div>
        <div class="client-stats">
            <strong>Rs {{ number_format($subscription->price ?? 0, 2) }}</strong>
            <span>Next: {{ $subscription->next_billing_date }}</span>
        </div>
    </div>
</section>

<section class="panel-card">
    <h3>Details</h3>
    <dl>
        <dt>Client</dt>
        <dd>{{ $subscription->client->business_name ?? $subscription->client->contact_name ?? 'N/A' }}</dd>
        <dt>Service</dt>
        <dd>{{ $subscription->service->name ?? 'N/A' }}</dd>
        <dt>Start Date</dt>
        <dd>{{ $subscription->start_date }}</dd>
        <dt>Next Billing</dt>
        <dd>{{ $subscription->next_billing_date }}</dd>
        <dt>Price</dt>
        <dd>Rs {{ number_format($subscription->price ?? 0, 2) }}</dd>
        <dt>Status</dt>
        <dd><span class="status-pill {{ strtolower($subscription->status) }}">{{ ucfirst($subscription->status) }}</span></dd>
    </dl>
</section>
@endsection

