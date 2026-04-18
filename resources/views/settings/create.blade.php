@extends('layouts.app')

@section('content')
<section class="section-bar">
    <a href="{{ route('settings.index') }}" class="text-link">&larr; Back to settings</a>
</section>

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
