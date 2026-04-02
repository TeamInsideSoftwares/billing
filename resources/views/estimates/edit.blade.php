@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <h3 style="margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">Edit {{ $estimate->number }}</h3>
    </div>
    <a href="{{ route('estimates.index') }}" class="text-link">&larr; Back to estimates</a>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('estimates.update', $estimate) }}" class="client-form">
        @method('PUT')
        @csrf
        <div class="form-grid">
            <div>
                <label for="clientid">Select Client *</label>
                <select id="clientid" name="clientid" required>
                    <option value="">-- Choose Client --</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('clientid', $estimate->clientid) == $client->id ? 'selected' : '' }}>
                            {{ $client->business_name ?? $client->contact_name }}
                        </option>
                    @endforeach
                </select>
                @error('clientid') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="number">Estimate Number *</label>
                <input type="text" id="estimate_number" name="estimate_number" value="{{ old('estimate_number', $estimate->estimate_number) }}" required>
                @error('estimate_number') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="issue_date">Issue Date *</label>
                <input type="date" id="issue_date" name="issue_date" value="{{ old('issue_date', $estimate->issue_date) }}" required>
                @error('issue_date') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="expiry_date">Expiry Date</label>
                <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date', $estimate->expiry_date) }}">
                @error('expiry_date') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="draft" {{ old('status', $estimate->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="sent" {{ old('status', $estimate->status) == 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="accepted" {{ old('status', $estimate->status) == 'accepted' ? 'selected' : '' }}>Accepted</option>
                    <option value="declined" {{ old('status', $estimate->status) == 'declined' ? 'selected' : '' }}>Declined</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-button">Update Estimate</button>
            <a href="{{ route('estimates.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
@endsection

