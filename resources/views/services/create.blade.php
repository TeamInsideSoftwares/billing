@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">Catalog</p>
        <h3>Add new service</h3>
    </div>
    <a href="{{ route('services.index') }}" class="text-link">&larr; Back to services</a>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('services.store') }}" class="client-form">
        @csrf
        <div class="form-grid">
            <div>
                <label for="name">Service Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                @error('name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="billing_type">Billing Type *</label>
                <select id="billing_type" name="billing_type" required>
                    <option value="one-time" {{ old('billing_type') == 'one-time' ? 'selected' : '' }}>One Time</option>
                    <option value="recurring" {{ old('billing_type') == 'recurring' ? 'selected' : '' }}>Recurring</option>
                </select>
                @error('billing_type') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="unit_price">Unit Price (Rs) *</label>
                <input type="number" step="0.01" id="unit_price" name="unit_price" value="{{ old('unit_price') }}" required>
                @error('unit_price') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="tax_rate">Tax Rate (%)</label>
                <input type="number" step="0.01" id="tax_rate" name="tax_rate" value="{{ old('tax_rate', 18) }}">
                @error('tax_rate') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div style="grid-column: span 2;">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3">{{ old('description') }}</textarea>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-button">Create Service</button>
            <a href="{{ route('services.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
@endsection
