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
                <label for="product_categoryid">Category</label>
                <select id="product_categoryid" name="product_categoryid">
                    <option value="">-- No Category --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->product_categoryid }}" {{ old('product_categoryid') == $category->product_categoryid ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('product_categoryid') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="sac_code">SAC Code</label>
                <input type="text" id="sac_code" name="sac_code" value="{{ old('sac_code') }}">
                @error('sac_code') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="cost_price">Cost Price (Rs) *</label>
                <input type="number" step="0.01" id="cost_price" name="cost_price" value="{{ old('cost_price') }}" required>
                @error('cost_price') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="selling_price">Selling Price (Rs) *</label>
                <input type="number" step="0.01" id="selling_price" name="selling_price" value="{{ old('selling_price') }}" required>
                @error('selling_price') <span class="error">{{ $message }}</span> @enderror
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
