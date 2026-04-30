@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('quotations.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left" class="icon-spaced"></i>Back to Quotations
    </a>
@endsection

@section('content')
<section class="panel-card">
    <form method="POST" action="{{ isset($quotation) ? route('quotations.update', $quotation) : route('quotations.store') }}" class="client-form">
        @isset($quotation)
            @method('PUT')
        @endisset
        @csrf
        <div class="form-grid">
            <div>
                <label for="clientid">Select Client *</label>
                <select id="clientid" name="clientid" required>
                    <option value="">-- Choose Client --</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('clientid', isset($quotation) ? $quotation->clientid : '') == $client->id ? 'selected' : '' }}>
                            {{ $client->business_name ?? $client->contact_name }}
                        </option>
                    @endforeach
                </select>
                @error('clientid') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="number">Quotation Number *</label>
                <input type="text" id="quotation_number" name="quotation_number" value="{{ old('quotation_number', isset($quotation) ? $quotation->quotation_number : 'QUO-' . date('Ymd') . '-' . rand(100, 999)) }}" required>
                @error('quotation_number') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="issue_date">Issue Date *</label>
                <input type="date" id="issue_date" name="issue_date" value="{{ old('issue_date', isset($quotation) ? $quotation->issue_date : date('Y-m-d')) }}" required>
                @error('issue_date') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="expiry_date">Expiry Date</label>
                <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date', isset($quotation) ? $quotation->expiry_date : date('Y-m-d', strtotime('+15 days'))) }}">
                @error('expiry_date') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="draft" {{ old('status', isset($quotation) ? $quotation->status : '') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="sent" {{ old('status', isset($quotation) ? $quotation->status : '') == 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="accepted" {{ old('status', isset($quotation) ? $quotation->status : '') == 'accepted' ? 'selected' : '' }}>Accepted</option>
                    <option value="declined" {{ old('status', isset($quotation) ? $quotation->status : '') == 'declined' ? 'selected' : '' }}>Declined</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-button">{{ isset($quotation) ? 'Update Quotation' : 'Create Quotation' }}</button>
            <a href="{{ route('quotations.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
@endsection
