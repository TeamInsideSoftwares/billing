@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">Pre-Sales</p>
        <h3>Create new estimate</h3>
    </div>
    <a href="{{ route('estimates.index') }}" class="text-link">&larr; Back to estimates</a>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('estimates.store') }}" class="client-form">
        @csrf
        <div class="form-grid">
            <div>
                <label for="clientid">Select Client *</label>
                <select id="clientid" name="clientid" required>
                    <option value="">-- Choose Client --</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('clientid') == $client->id ? 'selected' : '' }}>
                            {{ $client->business_name ?? $client->contact_name }}
                        </option>
                    @endforeach
                </select>
                @error('clientid') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="number">Estimate Number *</label>
                <input type="text" id="estimate_number" name="estimate_number" value="{{ old('estimate_number', 'EST-' . date('Ymd') . '-' . rand(100, 999)) }}" required>
                @error('estimate_number') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="issue_date">Issue Date *</label>
                <input type="date" id="issue_date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" required>
                @error('issue_date') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="expiry_date">Expiry Date</label>
                <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date', date('Y-m-d', strtotime('+15 days'))) }}">
                @error('expiry_date') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="sent" {{ old('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="accepted" {{ old('status') == 'accepted' ? 'selected' : '' }}>Accepted</option>
                    <option value="declined" {{ old('status') == 'declined' ? 'selected' : '' }}>Declined</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-button">Create Estimate</button>
            <a href="{{ route('estimates.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
@endsection
