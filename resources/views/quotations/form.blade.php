@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('quotations.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left"></i>Back to Quotations
    </a>
@endsection

@section('content')
<section class="panel-card">
    @php
        $quotationDateBounds = $quotationDateBounds ?? [
            'min_date' => date('Y-m-d'),
            'max_date' => date('Y-m-d'),
            'issue_max_date' => date('Y-m-d'),
            'due_max_date' => date('Y-m-d'),
            'default_issue_date' => '',
            'default_due_date' => '',
        ];
    @endphp
    <form method="POST" action="{{ route('quotations.update', $quotation) }}" class="client-form">
        @method('PUT')
        @csrf
        <div class="form-grid">
            <div>
                <label for="clientid">Select Client *</label>
                <select id="clientid" name="clientid" required>
                    <option value="">-- Choose Client --</option>
                    @php $groupedClients = $clients->groupBy(fn ($c) => $c->type === 'trial' ? 'trial' : 'regular') @endphp
                    @foreach (['regular', 'trial'] as $group)
                        @if ($groupedClients->has($group))
                        <optgroup label="{{ $group === 'regular' ? 'Regular Clients' : 'Trial Clients' }}">
                            @foreach ($groupedClients[$group] as $client)
                                <option value="{{ $client->clientid }}" {{ old('clientid', $quotation->clientid) == $client->clientid ? 'selected' : '' }}>
                                    {{ $client->business_name ?? $client->contact_name }}
                                </option>
                            @endforeach
                        </optgroup>
                        @endif
                    @endforeach
                </select>
            </div>
            <div>
                <label for="quo_number">Quotation Number *</label>
                <input type="text" id="quo_number" name="quo_number" value="{{ old('quo_number', $quotation->quo_number) }}" required>
            </div>
            <div>
                <label for="issue_date">Issue Date *</label>
                <input type="date" id="issue_date" name="issue_date"
                    min="{{ $quotationDateBounds['min_date'] }}"
                    max="{{ $quotationDateBounds['issue_max_date'] ?? $quotationDateBounds['max_date'] }}"
                    value="{{ old('issue_date', optional($quotation->issue_date)->format('Y-m-d') ?: $quotationDateBounds['default_issue_date']) }}" required>
            </div>
            <div>
                <label for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date"
                    min="{{ $quotationDateBounds['min_date'] }}"
                    max="{{ $quotationDateBounds['due_max_date'] ?? $quotationDateBounds['max_date'] }}"
                    value="{{ old('due_date', optional($quotation->due_date)->format('Y-m-d') ?: $quotationDateBounds['default_due_date']) }}">
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
