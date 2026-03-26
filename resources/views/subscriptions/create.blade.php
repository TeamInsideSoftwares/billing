@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">Recurring Revenue</p>
        <h3>New Subscription</h3>
    </div>
    <a href="{{ route('subscriptions.index') }}" class="text-link">&larr; Back to subscriptions</a>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('subscriptions.store') }}" class="client-form">
        @csrf
        <div class="form-grid">
            <div>
                <label for="clientid">Select Client *</label>
                <select id="clientid" name="clientid" required>
                    <option value="">-- Choose Client --</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('clientid') == $client->id ? 'selected' : '' }}>
                            {{ $client->business_name ?? $client->contact_name }}
                        </option>
                    @endforeach
                </select>
                @error('clientid') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="serviceid">Select Recurring Service *</label>
                <select id="serviceid" name="serviceid" required>
                    <option value="">-- Choose Service --</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}" {{ old('serviceid') == $service->id ? 'selected' : '' }}>
                            {{ $service->name }} (Rs {{ number_format($service->unit_price) }})
                        </option>
                    @endforeach
                </select>
                @error('serviceid') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="start_date">Start Date *</label>
                <input type="date" id="start_date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required>
                @error('start_date') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="next_billing_date">Next Billing Date *</label>
                <input type="date" id="next_billing_date" name="next_billing_date" value="{{ old('next_billing_date', date('Y-m-d', strtotime('+1 month'))) }}" required>
                @error('next_billing_date') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="price">Subscription Price (Rs) *</label>
                <input type="number" step="0.01" id="price" name="price" value="{{ old('price') }}" required>
                @error('price') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="expired" {{ old('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-button">Create Subscription</button>
            <a href="{{ route('subscriptions.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
@endsection
