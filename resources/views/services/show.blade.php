@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">{{ $service->name }}</p>
        <h3>Item details</h3>
    </div>
    <div>
        <a href="{{ route('services.edit', $service) }}" class="primary-button">Edit</a>
        <form method="POST" action="{{ route('services.destroy', $service) }}" class="inline-delete" onsubmit="return confirm('Delete this item?')">
            @csrf @method('DELETE')
            <button type="submit" class="danger-button">Delete</button>
        </form>
    </div>
</section>

<section class="panel-card">
    <div class="service-header">
        <div>
            <h1>{{ $service->name }}</h1>
            <p>Type: {{ ucfirst($service->type ?? 'service') }}</p>
            <span class="status-pill {{ $service->is_active ? 'active' : 'inactive' }}">{{ $service->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        <div class="service-stats">
            @if($service->costings->count())
                <strong>{{ $service->costings->count() }} costing{{ $service->costings->count() > 1 ? 's' : '' }}</strong>
                <span>Default tax {{ $service->costings->first()->tax_rate ?? 0 }}%</span>
            @else
                <strong>No costings yet</strong>
            @endif
        </div>
    </div>
</section>

@if($service->costings->count())
<section class="panel-card">
    <h3>Costings</h3>
    <div style="overflow-x:auto;">
        <table class="data-table" style="min-width: 500px;">
            <thead>
                <tr>
                    <th>Currency</th>
                    <th>Cost Price</th>
                    <th>Selling Price</th>
                    <th>Tax Type</th>
                    <th>SAC Code</th>
                    <th>Tax %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($service->costings as $costing)
                    <tr>
                        <td><strong>{{ $costing->currency_code }}</strong></td>
                        <td>{{ number_format($costing->cost_price, 0) }}</td>
                        <td>{{ number_format($costing->selling_price, 0) }}</td>
                        <td>{{ $costing->tax_included === 'yes' ? 'Incl. Tax' : 'Excl. Tax' }}</td>
                        <td>{{ $costing->sac_code ?? '-' }}</td>
                        <td>{{ number_format($costing->tax_rate, 0) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endif

@if(isset($addonItems) && $addonItems->count())
<section class="panel-card">
    <h3>Linked Add-ons ({{ $addonItems->count() }})</h3>
    <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.5rem;">
        @foreach($addonItems as $addon)
            <span class="badge" style="display: inline-block; padding: 0.2rem 0.45rem; background: #f3f4f6; color: #374151; border-radius: 0.25rem; font-size: 0.75rem;">
                {{ $addon->name }} ({{ ucfirst($addon->type ?? 'service') }})
            </span>
        @endforeach
    </div>
</section>
@endif

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
