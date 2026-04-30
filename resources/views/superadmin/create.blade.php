@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('accounts.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left icon-spaced"></i>Back to Agencies
    </a>
@endsection

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">New Registration</p>
        <h3>Add master account</h3>
    </div>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('accounts.store') }}" class="client-form">
        @csrf
        <div class="form-grid">
            <div>
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Acme Marketing">
                @error('name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="owner_name">Owner's Full Name *</label>
                <input type="text" id="owner_name" name="owner_name" value="{{ old('owner_name') }}" required placeholder="e.g. John Doe">
                @error('owner_name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="email">Owner Email (Account Login) *</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="owner@account.com">
                @error('email') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="password">Initial Password *</label>
                <input type="password" id="password" name="password" required placeholder="Min 8 characters">
                @error('password') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="legal_name">Legal Entity Name</label>
                <input type="text" id="legal_name" name="legal_name" value="{{ old('legal_name') }}" placeholder="Acme Marketing Ltd.">
                @error('legal_name') <span class="error">{{ $message }}</span> @enderror
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
        </div>
        <div class="form-actions form-actions-xl">
            <button type="submit" class="primary-button">Register & Create Owner</button>
            <a href="{{ route('accounts.index') }}" class="text-link">Cancel</a>
        </div>
    </form>

    <div class="info-box info-box-muted">
        <strong>Info:</strong> When you register an , the system will automatically create an 'Admin' user with the provided email. The default password will be 'password123'. The account owner must update their profile after login.
    </div>
</section>
@endsection
