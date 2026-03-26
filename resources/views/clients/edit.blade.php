@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">Edit {{ $client->business_name ?? $client->contact_name }}</p>
        <h3>Update account details</h3>
    </div>
    <a href="{{ route('clients.index') }}" class="text-link">&larr; Back to clients</a>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('clients.update', $client) }}" class="client-form">
        @method('PUT')
        @csrf
        <div class="form-grid">
            <div style="display:none;">
                <input type="hidden" name="accountid" value="{{ $client->accountid ?? auth()->user()->accountid ?? 'ACC0000001' }}">
            </div>
            <div>
                <label for="business_name">Business Name *</label>
                <input type="text" id="business_name" name="business_name" value="{{ old('business_name', $client->business_name) }}" required>
                @error('business_name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="contact_name">Contact Person</label>
                <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name', $client->contact_name) }}">
            </div>
            <div>
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="{{ old('email', $client->email) }}" required>
                @error('email') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $client->phone) }}">
            </div>
            <div>
                <label for="billing_email">Billing Email</label>
                <input type="email" id="billing_email" name="billing_email" value="{{ old('billing_email', $client->billing_email) }}">
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" {{ old('status', $client->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="review" {{ old('status', $client->status) == 'review' ? 'selected' : '' }}>Review</option>
                    <option value="inactive" {{ old('status', $client->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-button">Update Client</button>
            <a href="{{ route('clients.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
@endsection

