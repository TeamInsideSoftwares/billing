@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('settings.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left" style="margin-right: 0.4rem;"></i>Back to Settings
    </a>
@endsection

@section('content')
<section class="panel-card">
    <form method="POST" action="{{ route('settings.store') }}" class="setting-form">
        @csrf
        <div class="form-grid">
            <div>
                <label for="key">Key *</label>
                <input type="text" id="key" name="key" value="{{ old('key') }}" required>
                @error('key') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="value">Value *</label>
                <input type="text" id="value" name="value" value="{{ old('value') }}" required>
                @error('value') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-button">Create Setting</button>
            <a href="{{ route('settings.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
@endsection
