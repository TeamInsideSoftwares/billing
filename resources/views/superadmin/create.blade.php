@extends('layouts.superadmin')

@section('header_actions')
    <a href="{{ route('superadmin.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Agencies
    </a>
@endsection

@section('content')
<div class="mb-4">
    <span class="text-uppercase text-muted small fw-bold d-block mb-1">New Registration</span>
    <h3 class="h4 mb-0">Add master account</h3>
    <p class="text-muted mb-0">Step {{ $step ?? 1 }} of 2</p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        @if(($step ?? 1) === 1)
            <form method="POST" action="{{ route('superadmin.store-step1') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Account Name *</label>
                        <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required placeholder="e.g. Acme Marketing">
                        @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="account_email" class="form-label">Account Email *</label>
                        <input type="email" id="account_email" name="account_email" class="form-control" value="{{ old('account_email') }}" required placeholder="billing@account.com">
                        @error('account_email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="e.g. +91 9876543210">
                        @error('phone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="website" class="form-label">Website</label>
                        <input type="url" id="website" name="website" class="form-control" value="{{ old('website') }}" placeholder="https://example.com">
                        @error('website') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="legal_name" class="form-label">Legal Entity Name</label>
                        <input type="text" id="legal_name" name="legal_name" class="form-control" value="{{ old('legal_name') }}" placeholder="Acme Marketing Ltd.">
                        @error('legal_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="currency_code" class="form-label">Currency *</label>
                        <select id="currency_code" name="currency_code" class="form-select" required>
                            <option value="INR" {{ old('currency_code', 'INR') === 'INR' ? 'selected' : '' }}>INR</option>
                            <option value="USD" {{ old('currency_code') === 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="EUR" {{ old('currency_code') === 'EUR' ? 'selected' : '' }}>EUR</option>
                            <option value="GBP" {{ old('currency_code') === 'GBP' ? 'selected' : '' }}>GBP</option>
                        </select>
                        @error('currency_code') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="timezone" class="form-label">Timezone *</label>
                        <select id="timezone" name="timezone" class="form-select" required>
                            <option value="Asia/Kolkata" {{ old('timezone', 'Asia/Kolkata') === 'Asia/Kolkata' ? 'selected' : '' }}>Asia/Kolkata</option>
                            <option value="UTC" {{ old('timezone') === 'UTC' ? 'selected' : '' }}>UTC</option>
                            <option value="America/New_York" {{ old('timezone') === 'America/New_York' ? 'selected' : '' }}>America/New_York</option>
                            <option value="Europe/London" {{ old('timezone') === 'Europe/London' ? 'selected' : '' }}>Europe/London</option>
                        </select>
                        @error('timezone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Membership Status *</label>
                        <select id="status" name="status" class="form-select">
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="expires_at" class="form-label">Expires On</label>
                        <input type="date" id="expires_at" name="expires_at" class="form-control" value="{{ old('expires_at') }}" min="{{ now()->toDateString() }}">
                        @error('expires_at') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input type="hidden" name="allow_sync" value="0">
                            <input class="form-check-input" type="checkbox" name="allow_sync" value="1" id="allow_sync" {{ old('allow_sync') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="allow_sync">Allow Sync</label>
                            @error('allow_sync') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input type="hidden" name="has_team_management" value="0">
                            <input class="form-check-input" type="checkbox" name="has_team_management" value="1" id="has_team_management" {{ old('has_team_management') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="has_team_management">Allow Team Management</label>
                            @error('has_team_management') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Continue to Account User</button>
                    <a href="{{ route('superadmin.index') }}" class="btn btn-link text-decoration-none">Cancel</a>
                </div>
            </form>
        @else
            <form method="POST" action="{{ route('superadmin.store') }}">
                @csrf
                <div class="alert alert-secondary mb-3">
                    <strong>Account:</strong> {{ $draft['name'] ?? '-' }}<br>
                    <strong>Account Email:</strong> {{ $draft['account_email'] ?? '-' }}
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="login_email" class="form-label">Account User Email *</label>
                        <input type="email" id="login_email" name="login_email" class="form-control" value="{{ old('login_email', $draft['account_email'] ?? '') }}" required>
                        @error('login_email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="password" class="form-label">Initial Password *</label>
                        <input type="password" id="password" name="password" class="form-control" required minlength="6" placeholder="Min 6 characters">
                        @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="password_confirmation" class="form-label">Confirm Password *</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required minlength="6" placeholder="Re-enter password">
                        @error('password_confirmation') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <a href="{{ route('superadmin.create') }}" class="btn btn-outline-secondary">Back</a>
                    <button type="submit" class="btn btn-primary">Create Account</button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
