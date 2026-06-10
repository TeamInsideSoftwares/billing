@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('groups.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left icon-spaced"></i>Back to Groups
    </a>
@endsection

@section('content')
<section class="panel-card">
    <form method="POST" action="{{ isset($group) ? route('groups.update', $group) : route('groups.store') }}" class="client-form">
        @isset($group)
            @method('PUT')
        @endisset
        @csrf
        <div class="form-grid">
            <div class="col-span-2">
                <label for="group_name">Group Name *</label>
                <input type="text" id="group_name" name="group_name" value="{{ old('group_name', isset($group) ? $group->group_name : '') }}" required>
                @error('group_name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', isset($group) ? $group->email : '') }}">
                @error('email') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="col-span-2">
                <h4 style="margin-top: 1rem; margin-bottom: 0.5rem; color: var(--primary-color, #0d6efd); font-weight: 600;">Registered Address</h4>
            </div>
            <div class="col-span-2">
                <label for="registered_address">Address</label>
                <input type="text" id="registered_address" name="registered_address" value="{{ old('registered_address', isset($group) ? $group->registered_address : '') }}">
                @error('registered_address') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="city">City</label>
                <input type="text" id="city" name="city" value="{{ old('city', isset($group) ? $group->city : '') }}">
                @error('city') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="state">State</label>
                <input type="text" id="state" name="state" value="{{ old('state', isset($group) ? $group->state : '') }}">
                @error('state') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="postal_code">Postal Code</label>
                <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', isset($group) ? $group->postal_code : '') }}">
                @error('postal_code') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="country">Country</label>
                <input type="text" id="country" name="country" value="{{ old('country', isset($group) ? $group->country : 'India') }}">
                @error('country') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2">
                <h4 style="margin-top: 1rem; margin-bottom: 0.5rem; color: var(--primary-color, #0d6efd); font-weight: 600;">Business Address</h4>
            </div>
            <div class="col-span-2">
                <label for="business_address">Address</label>
                <input type="text" id="business_address" name="business_address" value="{{ old('business_address', isset($group) ? $group->business_address : '') }}">
                @error('business_address') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="business_city">City</label>
                <input type="text" id="business_city" name="business_city" value="{{ old('business_city', isset($group) ? $group->business_city : '') }}">
                @error('business_city') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="business_state">State</label>
                <input type="text" id="business_state" name="business_state" value="{{ old('business_state', isset($group) ? $group->business_state : '') }}">
                @error('business_state') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="business_postal_code">Postal Code</label>
                <input type="text" id="business_postal_code" name="business_postal_code" value="{{ old('business_postal_code', isset($group) ? $group->business_postal_code : '') }}">
                @error('business_postal_code') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="business_country">Country</label>
                <input type="text" id="business_country" name="business_country" value="{{ old('business_country', isset($group) ? $group->business_country : 'India') }}">
                @error('business_country') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-button">{{ isset($group) ? 'Update Group' : 'Create Group' }}</button>
            <a href="{{ route('groups.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
@endsection
