@extends('layouts.superadmin')

@section('header_actions')
    <a href="{{ route('superadmin.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left icon-spaced"></i>Back to Agencies
    </a>
@endsection

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">New Registration</p>
        <h3>Add master account</h3>
        <p class="text-muted mb-0">Step {{ $step ?? 1 }} of 2</p>
    </div>
</section>

<section class="panel-card">
    @if(($step ?? 1) === 1)
        <form method="POST" action="{{ route('superadmin.store-step1') }}" class="client-form">
            @csrf
            <div class="form-grid">
                <div>
                    <label for="name">Account Name *</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Acme Marketing">
                    @error('name') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="account_email">Account Email *</label>
                    <input type="email" id="account_email" name="account_email" value="{{ old('account_email') }}" required placeholder="billing@account.com">
                    @error('account_email') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="e.g. +91 9876543210">
                    @error('phone') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="website">Website</label>
                    <input type="url" id="website" name="website" value="{{ old('website') }}" placeholder="https://example.com">
                    @error('website') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="legal_name">Legal Entity Name</label>
                    <input type="text" id="legal_name" name="legal_name" value="{{ old('legal_name') }}" placeholder="Acme Marketing Ltd.">
                    @error('legal_name') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="currency_code">Currency *</label>
                    <select id="currency_code" name="currency_code" required>
                        <option value="INR" {{ old('currency_code', 'INR') === 'INR' ? 'selected' : '' }}>INR</option>
                        <option value="USD" {{ old('currency_code') === 'USD' ? 'selected' : '' }}>USD</option>
                        <option value="EUR" {{ old('currency_code') === 'EUR' ? 'selected' : '' }}>EUR</option>
                        <option value="GBP" {{ old('currency_code') === 'GBP' ? 'selected' : '' }}>GBP</option>
                    </select>
                    @error('currency_code') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="timezone">Timezone *</label>
                    <select id="timezone" name="timezone" required>
                        <option value="Asia/Kolkata" {{ old('timezone', 'Asia/Kolkata') === 'Asia/Kolkata' ? 'selected' : '' }}>Asia/Kolkata</option>
                        <option value="UTC" {{ old('timezone') === 'UTC' ? 'selected' : '' }}>UTC</option>
                        <option value="America/New_York" {{ old('timezone') === 'America/New_York' ? 'selected' : '' }}>America/New_York</option>
                        <option value="Europe/London" {{ old('timezone') === 'Europe/London' ? 'selected' : '' }}>Europe/London</option>
                    </select>
                    @error('timezone') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="status">Membership Status *</label>
                    <select id="status" name="status">
                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending Approval</option>
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="expires_at">Expires On</label>
                    <input type="date" id="expires_at" name="expires_at" value="{{ old('expires_at') }}" min="{{ now()->toDateString() }}">
                    @error('expires_at') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="service-toggle" style="align-self:end;">
                    <label for="allow_sync">Allow Sync</label>
                    <label class="custom-checkbox service-check">
                        <input type="hidden" name="allow_sync" value="0">
                        <input type="checkbox" name="allow_sync" value="1" id="allow_sync" {{ old('allow_sync') ? 'checked' : '' }}>
                    </label>
                    @error('allow_sync') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-actions form-actions-xl">
                <button type="submit" class="primary-button">Continue to Credentials</button>
                <a href="{{ route('superadmin.index') }}" class="text-link">Cancel</a>
            </div>
        </form>
    @else
        <form method="POST" action="{{ route('superadmin.store') }}" class="client-form">
            @csrf
            <div class="info-box info-box-muted mb-3">
                <strong>Account:</strong> {{ $draft['name'] ?? '-' }}<br>
                <strong>Account Email:</strong> {{ $draft['account_email'] ?? '-' }}
            </div>
            <div class="form-grid">
                <div>
                    <label for="login_email">Login Email *</label>
                    <input type="email" id="login_email" name="login_email" value="{{ old('login_email', $draft['account_email'] ?? '') }}" required>
                    @error('login_email') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="password">Initial Password *</label>
                    <input type="password" id="password" name="password" required minlength="6" placeholder="Min 6 characters">
                    @error('password') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="password_confirmation">Confirm Password *</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="6" placeholder="Re-enter password">
                    @error('password_confirmation') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-actions form-actions-xl">
                <a href="{{ route('superadmin.create') }}" class="secondary-button">Back</a>
                <button type="submit" class="primary-button">Create Account</button>
            </div>
        </form>
    @endif
</section>
@endsection
