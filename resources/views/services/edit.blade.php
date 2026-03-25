@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">Edit {{ $service->name }}</p>
        <h3>Update service details</h3>
    </div>
    <a href="{{ route('services.index') }}" class="text-link">&larr; Back to services</a>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('services.update', $service) }}" class="service-form">
        @method('PUT')
        @csrf
        <div class="form-grid">
            <div>
                <label for="name">Service Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $service->name) }}" required>
                @error('name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3">{{ old('description', $service->description) }}</textarea>
                @error('description') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="billing_type">Billing Type *</label>
                <select id="billing_type" name="billing_type" required>
                    <option value="one-time" {{ old('billing_type', $service->billing_type) == 'one-time' ? 'selected' : '' }}>One Time</option>
                    <option value="recurring" {{ old('billing_type', $service->billing_type) == 'recurring' ? 'selected' : '' }}>Recurring</option>
                </select>
                @error('billing_type') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="unit_price">Unit Price (Rs) *</label>
                <input type="number" id="unit_price" name="unit_price" step="0.01" min="0" value="{{ old('unit_price', $service->unit_price) }}" required>
                @error('unit_price') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="tax_rate">Tax Rate (%)</label>
                <input type="number" id="tax_rate" name="tax_rate" step="0.01" min="0" max="100" value="{{ old('tax_rate', $service->tax_rate) }}">
                @error('tax_rate') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" {{ old('status', $service->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $service->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-button">Update Service</button>
            <a href="{{ route('services.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
@endsection

