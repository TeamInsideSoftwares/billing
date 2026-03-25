@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">{{ $setting->key }}</p>
        <h3>Setting details</h3>
    </div>
    <div>
        <a href="{{ route('settings.edit', $setting) }}" class="primary-button">Edit</a>
        <form method="POST" action="{{ route('settings.destroy', $setting) }}" class="inline-delete" onsubmit="return confirm('Delete this setting?')">
            @csrf @method('DELETE')
            <button type="submit" class="danger-button">Delete</button>
        </form>
    </div>
</section>

<section class="panel-card">
    <div class="setting-header">
        <div>
            <h1>{{ $setting->key }}</h1>
            <p>Type: {{ ucfirst($setting->type ?? 'text') }}</p>
            <span class="status-pill {{ strtolower($setting->status ?? 'active') }}">{{ ucfirst($setting->status ?? 'Active') }}</span>
        </div>
        <div class="setting-stats">
            <strong>Value: {{ $setting->value }}</strong>
        </div>
    </div>
</section>

@if($setting->description)
<section class="panel-card">
    <h3>Description</h3>
    <p>{{ $setting->description }}</p>
</section>
@endif>

@endsection

