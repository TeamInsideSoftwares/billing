@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('quotations.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left"></i>Back to Quotations
    </a>
@endsection

@section('content')
<section class="panel-card">
    <form method="POST" action="{{ route('quotations.update', $quotation) }}" class="client-form">
        @method('PUT')
        @csrf
        <div class="form-grid">
            <div>
                <label for="clientid">Select Client *</label>
                <select id="clientid" name="clientid" required>
                    <option value="">-- Choose Client --</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->clientid }}" {{ old('clientid', $quotation->clientid) == $client->clientid ? 'selected' : '' }}>
                            {{ $client->business_name ?? $client->contact_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="quo_number">Quotation Number *</label>
                <input type="text" id="quo_number" name="quo_number" value="{{ old('quo_number', $quotation->quo_number) }}" required>
            </div>
            <div>
                <label for="issue_date">Issue Date *</label>
                <input type="date" id="issue_date" name="issue_date" value="{{ old('issue_date', optional($quotation->issue_date)->format('Y-m-d')) }}" required>
            </div>
            <div>
                <label for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date" value="{{ old('due_date', optional($quotation->due_date)->format('Y-m-d')) }}">
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    @foreach(['draft', 'active', 'cancelled'] as $status)
                        <option value="{{ $status }}" {{ old('status', $quotation->status) == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-button">Update Quotation</button>
            <a href="{{ route('quotations.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
@endsection
