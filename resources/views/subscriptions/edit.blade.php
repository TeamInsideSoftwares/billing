@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('subscriptions.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left" style="margin-right: 0.4rem;"></i>Back to Subscriptions
    </a>
@endsection

@section('content')
<section class="panel-card">
    <form method="POST" action="{{ route('subscriptions.update', $subscription) }}" class="client-form">
        @method('PUT')
        @csrf
        <div class="form-grid">
            <div>
                <label for="clientid">Select Client *</label>
                <select id="clientid" name="clientid" required>
                    <option value="">-- Choose Client --</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->clientid }}" {{ old('clientid', $subscription->clientid) == $client->clientid ? 'selected' : '' }}>
                            {{ $client->business_name ?? $client->contact_name }}
                        </option>
                    @endforeach
                </select>
                @error('clientid') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="itemid">Select Recurring Item *</label>
                <select id="itemid" name="itemid" required>
                    <option value="">-- Choose Item --</option>
                    @foreach($services as $service)
                        <option value="{{ $service->itemid }}" {{ old('itemid', $subscription->itemid) == $service->itemid ? 'selected' : '' }}>
                            {{ $service->name }} (Rs {{ number_format($service->unit_price) }})
                        </option>
                    @endforeach
                </select>
                @error('itemid') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="start_date">Start Date *</label>
                <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $subscription->start_date) }}" required>
                @error('start_date') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="next_billing_date">Next Billing Date *</label>
                <input type="date" id="next_billing_date" name="next_billing_date" value="{{ old('next_billing_date', $subscription->next_billing_date) }}" required>
                @error('next_billing_date') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="price">Subscription Price (Rs) *</label>
                <input type="number" step="0.01" id="price" name="price" value="{{ old('price', $subscription->price) }}" required>
                @error('price') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" {{ old('status', $subscription->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="cancelled" {{ old('status', $subscription->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="expired" {{ old('status', $subscription->status) == 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-button">Update Subscription</button>
            <a href="{{ route('subscriptions.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
@endsection



