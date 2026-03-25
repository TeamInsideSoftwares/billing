@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">New Agency Registration</p>
        <h3>Add master agency account</h3>
    </div>
    <a href="{{ route('accounts.index') }}" class="text-link">&larr; Back to agencies</a>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('accounts.store') }}" class="client-form">
        @csrf
        <div class="form-grid">
            <div>
                <label for="name">Agency Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Acme Marketing">
                @error('name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="slug">Agency Slug (for workspace URL) *</label>
                <input type="text" id="slug" name="slug" value="{{ old('slug') }}" required placeholder="e.g. acme-mkt">
                @error('slug') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="owner_name">Owner's Full Name *</label>
                <input type="text" id="owner_name" name="owner_name" value="{{ old('owner_name') }}" required placeholder="e.g. John Doe">
                @error('owner_name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="email">Owner Email (Account Login) *</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="owner@agency.com">
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
        <div class="form-actions" style="margin-top: 2rem; border-top: 1px solid var(--slate-100); padding-top: 2rem;">
            <button type="submit" class="primary-button">Register Agency & Create Owner</button>
            <a href="{{ route('accounts.index') }}" class="text-link">Cancel</a>
        </div>
    </form>

    <div style="margin-top: 2rem; padding: 1rem; background: var(--slate-50); border-radius: 8px; font-size: 0.85rem; color: var(--slate-500);">
        <strong>Info:</strong> When you register an agency, the system will automatically create an 'Agency Admin' user with the provided email. The default password will be 'password123'. The agency owner must update their profile after login.
    </div>
</section>
@endsection
