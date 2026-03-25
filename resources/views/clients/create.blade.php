@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">New Client</p>
        <h3>Add account details</h3>
    </div>
    <a href="{{ route('clients.index') }}" class="text-link">&larr; Back to clients</a>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('clients.store') }}" class="client-form">
        @csrf

        @if ($errors->any())
            <div class="alert alert-danger" style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 1rem; margin-bottom: 2rem; border-radius: 4px;">
                <ul style="margin: 0; padding-left: 1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li style="color: #991b1b; font-size: 0.9rem;">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="form-grid">
            <div style="display:none;">
                <input type="hidden" name="account_id" value="{{ auth()->user()->account_id ?? 'ACC0000001' }}">
            </div>
            <div>
                <label for="business_name">Business Name *</label>
                <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}" required>
                @error('business_name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="contact_name">Contact Person</label>
                <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name') }}">
            </div>
            <div>
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                @error('email') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}">
            </div>
            <div>
                <label for="billing_email">Billing Email</label>
                <input type="email" id="billing_email" name="billing_email" value="{{ old('billing_email') }}">
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="review" {{ old('status') == 'review' ? 'selected' : '' }}>Review</option>
                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-button">Create Client</button>
            <a href="{{ route('clients.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
@endsection

