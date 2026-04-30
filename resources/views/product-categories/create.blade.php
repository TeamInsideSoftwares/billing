@extends('layouts.app')

@section('content')
<div class="panel-card">
    <h2>{{ $title }}</h2>
    
    <form method="POST" action="{{ route('product-categories.store') }}" class="form-grid">
        @csrf
        
        <div class="form-group">
            <label for="name">Product Category Name <span class="required">*</span></label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="150">
            @error('name')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" rows="4">{{ old('description') }}</textarea>
            @error('description')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status">
                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            @error('status')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-actions">
            <a href="{{ route('product-categories.index') }}" class="secondary-button">Cancel</a>
            <button type="submit" class="primary-button">Create Product Category</button>
        </div>
    </form>
</div>
@endsection

