@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">{{ $service->name }}</p>
        <h3>Service details</h3>
    </div>
    <div>
        <a href="{{ route('services.edit', $service) }}" class="primary-button">Edit</a>
        <form method="POST" action="{{ route('services.destroy', $service) }}" class="inline-delete" onsubmit="return confirm('Delete this service?')">
            @csrf @method('DELETE')
            <button type="submit" class="danger-button">Delete</button>
        </form>
    </div>
</section>

<section class="panel-card">
    <div class="service-header">
        <div>
            <h1>{{ $service->name }}</h1>
            <p>Billing Type: {{ ucfirst(str_replace('-', ' ', $service->billing_type ?? 'one-time')) }}</p>
            <span class="status-pill {{ strtolower($service->status ?? 'active') }}">{{ ucfirst($service->status ?? 'Active') }}</span>
        </div>
        <div class="service-stats">
            <strong>Unit Price: Rs {{ number_format($service->unit_price ?? 0, 2) }}</strong>
            <span>Tax {{ $service->tax_rate ?? 18 }}%</span>
        </div>
    </div>
</section>

@if($service->description)
<section class="panel-card">
    <h3>Description</h3>
    <p>{{ $service->description }}</p>
</section>
@endif

@if(isset($service->subscriptions) && $service->subscriptions->count())
<section class="panel-card">
    <h3>Subscriptions ({{ $service->subscriptions->count() }})</h3>
    <div class="table-list">
        @foreach($service->subscriptions->take(5) as $subscription)
        <div class="table-row">
            <div><strong>{{ $subscription->client->business_name ?? 'Client' }}</strong></div>
            <div>Rs {{ number_format($subscription->price ?? 0) }}</div>
            <div><span class="status-pill">{{ $subscription->status ?? 'Active' }}</span></div>
        </div>
        @endforeach
    </div>
</section>
@endif

@endsection

