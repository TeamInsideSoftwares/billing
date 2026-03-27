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
                <label for="product_categoryid">Category</label>
                <select id="product_categoryid" name="product_categoryid">
                    <option value="">-- No Category --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->product_categoryid }}" {{ old('product_categoryid', $service->product_categoryid) == $category->product_categoryid ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('product_categoryid') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="sac_code">SAC Code</label>
                <input type="text" id="sac_code" name="sac_code" value="{{ old('sac_code', $service->sac_code) }}">
                @error('sac_code') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="cost_price">Cost Price (Rs) *</label>
                <input type="number" step="0.01" id="cost_price" name="cost_price" value="{{ old('cost_price', $service->cost_price) }}" required>
                @error('cost_price') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="selling_price">Selling Price (Rs) *</label>
                <input type="number" step="0.01" id="selling_price" name="selling_price" value="{{ old('selling_price', $service->selling_price) }}" required>
                @error('selling_price') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="tax_rate">Tax Rate (%)</label>
                <input type="number" id="tax_rate" name="tax_rate" step="0.01" min="0" max="100" value="{{ old('tax_rate', $service->tax_rate) }}">
                @error('tax_rate') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" {{ old('status', $service->is_active ? 'active' : 'inactive') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $service->is_active ? 'active' : 'inactive') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div style="grid-column: span 2;">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3">{{ old('description', $service->description) }}</textarea>
                @error('description') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-button">Update Service</button>
            <a href="{{ route('services.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
@endsection

