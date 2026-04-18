@extends('layouts.app')

@section('content')
<section class="section-bar">
    <a href="{{ route('groups.index') }}" class="text-link">&larr; Back to groups</a>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('groups.store') }}" class="client-form">
        @csrf
        <div class="form-grid">
            <div style="grid-column: span 2;">
                <label for="group_name">Group Name *</label>
                <input type="text" id="group_name" name="group_name" value="{{ old('group_name') }}" required maxlength="150">
                @error('group_name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="150">
                @error('email') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div style="grid-column: span 2;">
                <label for="address_line_1">Address Line 1</label>
                <input type="text" id="address_line_1" name="address_line_1" value="{{ old('address_line_1') }}" maxlength="150">
                @error('address_line_1') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div style="grid-column: span 2;">
                <label for="address_line_2">Address Line 2</label>
                <input type="text" id="address_line_2" name="address_line_2" value="{{ old('address_line_2') }}" maxlength="150">
                @error('address_line_2') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="city">City</label>
                <input type="text" id="city" name="city" value="{{ old('city') }}" maxlength="100">
                @error('city') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="state">State</label>
                <input type="text" id="state" name="state" value="{{ old('state') }}" maxlength="100">
                @error('state') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="postal_code">Postal Code</label>
                <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code') }}" maxlength="20">
                @error('postal_code') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="country">Country</label>
                <input type="text" id="country" name="country" value="{{ old('country', 'India') }}" maxlength="100">
                @error('country') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-button">Create Group</button>
            <a href="{{ route('groups.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
@endsection
