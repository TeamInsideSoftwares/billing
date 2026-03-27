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
    <form method="POST" action="{{ route('clients.store') }}" class="client-form" enctype="multipart/form-data">
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

        <div style="display:none;">
            <input type="hidden" name="accountid" value="{{ auth()->user()->accountid ?? 'ACC0000001' }}">
        </div>

        <h4 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--line); padding-bottom: 0.5rem;">Client Details</h4>
        <div class="form-grid">
            <div>
                <label for="business_name">Business Name *</label>
                <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}" required>
                @error('business_name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="group_name">Group Name (Business Name if empty)</label>
                <input type="text" id="group_name" name="group_name" value="{{ old('group_name') }}">
            </div>
            <div>
                <label for="logo">Company Logo (Square recommended)</label>
                <input type="file" id="logo" name="logo" accept="image/*">
                @error('logo') <span class="error">{{ $message }}</span> @enderror
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
                <label for="whatsapp_number">WhatsApp Number</label>
                <input type="tel" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number') }}">
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="review" {{ old('status') == 'review' ? 'selected' : '' }}>Review</option>
                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div>
                <label for="currency">Currency *</label>
                <input type="text" id="currency" name="currency" value="{{ old('currency', 'INR') }}" required maxlength="3">
            </div>
        </div>

        <h4 style="margin-top: 2rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--line); padding-bottom: 0.5rem;">Billing Details</h4>
        <div class="form-grid">
            <div>
                <label for="gstin">GSTIN NO.</label>
                <input type="text" id="gstin" name="gstin" value="{{ old('gstin') }}">
            </div>
            <div>
                <label for="billing_email">Billing Email</label>
                <input type="email" id="billing_email" name="billing_email" value="{{ old('billing_email') }}">
            </div>
            <div style="grid-column: span 2;">
                <label for="address_line_1">Address</label>
                <input type="text" id="address_line_1" name="address_line_1" value="{{ old('address_line_1') }}">
            </div>
            <div>
                <label for="city">City</label>
                <input type="text" id="city" name="city" value="{{ old('city') }}">
            </div>
            <div>
                <label for="state">State</label>
                <input type="text" id="state" name="state" value="{{ old('state') }}">
            </div>
            <div>
                <label for="postal_code">Postal Code</label>
                <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code') }}">
            </div>
            <div>
                <label for="country">Country</label>
                <input type="text" id="country" name="country" value="{{ old('country', 'India') }}">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="primary-button">Create Client</button>
            <a href="{{ route('clients.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
@endsection

iv>
    </form>
</section>
@endsection

