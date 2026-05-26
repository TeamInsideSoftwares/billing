@extends('layouts.superadmin')

@section('header_actions')
    <a href="{{ route('superadmin.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left icon-spaced"></i>Back to Agencies
    </a>
@endsection

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">Superadmin</p>
        <h3>Edit Account</h3>
    </div>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('superadmin.update', $account) }}" class="client-form">
        @csrf
        @method('PUT')

        <div class="form-grid">
            <div>
                <label for="name">Account Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $account->name) }}" required>
                @error('name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="account_email">Account Email *</label>
                <input type="email" id="account_email" name="account_email" value="{{ old('account_email', $account->email) }}" required>
                @error('account_email') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $account->phone) }}">
                @error('phone') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="website">Website</label>
                <input type="url" id="website" name="website" value="{{ old('website', $account->website) }}">
                @error('website') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="legal_name">Legal Entity Name</label>
                <input type="text" id="legal_name" name="legal_name" value="{{ old('legal_name', $account->legal_name) }}">
                @error('legal_name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="currency_code">Currency *</label>
                <select id="currency_code" name="currency_code" required>
                    @foreach(['INR','USD','EUR','GBP'] as $code)
                        <option value="{{ $code }}" {{ old('currency_code', $account->currency_code) === $code ? 'selected' : '' }}>{{ $code }}</option>
                    @endforeach
                </select>
                @error('currency_code') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="timezone">Timezone *</label>
                <select id="timezone" name="timezone" required>
                    @foreach(['Asia/Kolkata','UTC','America/New_York','Europe/London'] as $tz)
                        <option value="{{ $tz }}" {{ old('timezone', $account->timezone) === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                    @endforeach
                </select>
                @error('timezone') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="status">Membership Status *</label>
                <select id="status" name="status" required>
                    @foreach(['pending' => 'Pending Approval','active' => 'Active','inactive' => 'Inactive'] as $value => $label)
                        <option value="{{ $value }}" {{ old('status', $account->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="expires_at">Expires On</label>
                <input type="date" id="expires_at" name="expires_at" value="{{ old('expires_at', $account->expires_at?->format('Y-m-d')) }}">
                @error('expires_at') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="service-toggle" style="align-self:end;">
                <label for="allow_sync">Allow Sync</label>
                <label class="custom-checkbox service-check">
                    <input type="hidden" name="allow_sync" value="0">
                    <input type="checkbox" name="allow_sync" value="1" id="allow_sync" {{ old('allow_sync', $account->allow_sync) ? 'checked' : '' }}>
                </label>
                @error('allow_sync') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>

        <hr>
        <p class="eyebrow mb-2">Login Credentials</p>
        <div class="form-grid">
            <div>
                <label for="login_email">Login Email *</label>
                <input type="email" id="login_email" name="login_email" value="{{ old('login_email', $account->credential->email ?? '') }}" required>
                @error('login_email') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" minlength="6" placeholder="Leave blank to keep existing">
                @error('password') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="password_confirmation">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" minlength="6" placeholder="Re-enter new password">
            </div>
        </div>

        <div class="form-actions form-actions-xl">
            <button type="submit" class="primary-button">Update Account</button>
            <a href="{{ route('superadmin.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
@endsection
