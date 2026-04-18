@extends('layouts.app')

@section('content')
<section class="section-bar">
    <a href="{{ route('settings.index') }}" class="text-link">&larr; Back to settings</a>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('settings.update', $setting) }}" class="setting-form">
        @method('PUT')
        @csrf
        <div class="form-grid">
            <div>
                <label for="key">Key *</label>
                <input type="text" id="key" name="key" value="{{ old('key', $setting->key) }}" required>
                @error('key') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="value">Value *</label>
                <input type="text" id="value" name="value" value="{{ old('value', $setting->value) }}" required>
                @error('value') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-button">Update Setting</button>
            <a href="{{ route('settings.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
@endsection

