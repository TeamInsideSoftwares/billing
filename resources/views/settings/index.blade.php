@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <p class="eyebrow">Settings</p>
            <h3>Agency & System Configuration</h3>
        </div>
    </section>

    @if($account)
    <section class="panel-card" style="margin-bottom: 2rem;">
        <div class="panel-header">
            <h4>Agency Profile</h4>
            <p>Your agency's public and billing information.</p>
        </div>
        <form method="POST" action="{{ route('agency.update') }}" class="form-grid" style="margin-top: 1.5rem;">
            @csrf
            @method('PUT')
            <div>
                <label for="name">Agency Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $account->name) }}" required>
            </div>
            <div>
                <label for="legal_name">Legal Entity Name</label>
                <input type="text" id="legal_name" name="legal_name" value="{{ old('legal_name', $account->legal_name) }}">
            </div>
            <div>
                <label for="email">Contact Email *</label>
                <input type="email" id="email" name="email" value="{{ old('email', $account->email) }}" required>
            </div>
            <div>
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $account->phone) }}">
            </div>
            <div>
                <label for="currency_code">Currency *</label>
                <input type="text" id="currency_code" name="currency_code" value="{{ old('currency_code', $account->currency_code ?? 'INR') }}" required maxlength="3">
            </div>
            <div>
                <label for="timezone">Timezone *</label>
                <input type="text" id="timezone" name="timezone" value="{{ old('timezone', $account->timezone ?? 'Asia/Kolkata') }}" required>
            </div>
            <div style="grid-column: span 2;">
                <label for="address_line_1">Address</label>
                <input type="text" id="address_line_1" name="address_line_1" value="{{ old('address_line_1', $account->address_line_1) }}">
            </div>
            <div>
                <label for="city">City</label>
                <input type="text" id="city" name="city" value="{{ old('city', $account->city) }}">
            </div>
            <div>
                <label for="country">Country</label>
                <input type="text" id="country" name="country" value="{{ old('country', $account->country) }}">
            </div>
            <div class="form-actions" style="grid-column: span 2;">
                <button type="submit" class="primary-button">Update Agency Profile</button>
            </div>
        </form>
    </section>
    @endif

    <section class="panel-card">
        <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <div>
                <h4>System Settings</h4>
                <p>Advanced configuration keys.</p>
            </div>
            <a href="{{ route('settings.create') }}" class="text-link">+ Add Key</a>
        </div>

        <div class="table-list">
            @if(count($settings) > 0)
                @foreach ($settings as $setting)
                    <div class="table-row">
                        <div style="flex: 1;">
                            <code style="background: var(--slate-100); padding: 2px 6px; border-radius: 4px; font-size: 0.85rem;">{{ $setting['key'] }}</code>
                        </div>
                        <div style="flex: 2;">
                            <strong>{{ $setting['value'] }}</strong>
                        </div>
                        <div class="table-actions">
                            <a href="{{ route('settings.edit', $setting['id']) }}" class="text-link">Edit</a>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="empty-state" style="padding: 2rem; text-align: center; color: var(--slate-400);">
                    No custom settings defined.
                </div>
            @endif
        </div>
    </section>
@endsection

