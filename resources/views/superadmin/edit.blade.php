@extends('layouts.superadmin')

@section('header_actions')
    <a href="{{ route('superadmin.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Agencies
    </a>
@endsection

@section('content')
    <div class="mb-4">
        <span class="text-uppercase text-muted small fw-bold d-block mb-1">Superadmin</span>
        <h3 class="h4 mb-0">Edit Account</h3>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('superadmin.update', $account) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Account Name *</label>
                        <input type="text" id="name" name="name" class="form-control"
                            value="{{ old('name', $account->name) }}" required>
                        @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="account_email" class="form-label">Account Email *</label>
                        <input type="email" id="account_email" name="account_email" class="form-control"
                            value="{{ old('account_email', $account->email) }}" required>
                        @error('account_email')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" id="phone" name="phone" class="form-control"
                            value="{{ old('phone', $account->phone) }}">
                        @error('phone')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="website" class="form-label">Website</label>
                        <input type="url" id="website" name="website" class="form-control"
                            value="{{ old('website', $account->website) }}">
                        @error('website')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="legal_name" class="form-label">Legal Entity Name</label>
                        <input type="text" id="legal_name" name="legal_name" class="form-control"
                            value="{{ old('legal_name', $account->legal_name) }}">
                        @error('legal_name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="currency_code" class="form-label">Currency *</label>
                        <select id="currency_code" name="currency_code" class="form-select" required>
                            @foreach (['INR', 'USD', 'EUR', 'GBP'] as $code)
                                <option value="{{ $code }}"
                                    {{ old('currency_code', $account->currency_code) === $code ? 'selected' : '' }}>
                                    {{ $code }}</option>
                            @endforeach
                        </select>
                        @error('currency_code')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="timezone" class="form-label">Timezone *</label>
                        <select id="timezone" name="timezone" class="form-select" required>
                            @foreach (['Asia/Kolkata', 'UTC', 'America/New_York', 'Europe/London'] as $tz)
                                <option value="{{ $tz }}"
                                    {{ old('timezone', $account->timezone) === $tz ? 'selected' : '' }}>
                                    {{ $tz }}</option>
                            @endforeach
                        </select>
                        @error('timezone')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Membership Status *</label>
                        <select id="status" name="status" class="form-select" required>
                            @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                                <option value="{{ $value }}"
                                    {{ old('status', $account->status) === $value ? 'selected' : '' }}>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="expires_at" class="form-label">Expires On</label>
                        <input type="date" id="expires_at" name="expires_at" class="form-control"
                            value="{{ old('expires_at', $account->expires_at?->format('Y-m-d')) }}">
                        @error('expires_at')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input type="hidden" name="allow_sync" value="0">
                            <input class="form-check-input" type="checkbox" name="allow_sync" value="1" id="allow_sync"
                                {{ old('allow_sync', $account->allow_sync) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="allow_sync">Allow Sync</label>
                            @error('allow_sync')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input type="hidden" name="has_team_management" value="0">
                            <input class="form-check-input" type="checkbox" name="has_team_management" value="1" id="has_team_management"
                                {{ old('has_team_management', $account->has_team_management) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="has_team_management">Allow Team Management</label>
                            @error('has_team_management')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="h6 text-uppercase text-muted fw-bold mb-3">Account User</h5>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="login_email" class="form-label">Account User Email *</label>
                        <input type="email" id="login_email" name="login_email" class="form-control"
                            value="{{ old('login_email', $account->credential->email ?? '') }}" required>
                        @error('login_email')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" id="password" name="password" class="form-control" minlength="6"
                            placeholder="Leave blank to keep existing">
                        @error('password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                            class="form-control" minlength="6" placeholder="Re-enter new password">
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update Account</button>
                    <a href="{{ route('superadmin.index') }}" class="btn btn-link text-decoration-none">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    </section>
@endsection
