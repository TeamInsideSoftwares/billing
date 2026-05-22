@extends('layouts.app')

@section('header_actions')
    <div class="header-actions-wrapper">
        <a href="{{ route('quotations.create', $selectedClientId ? ['c' => $selectedClientId] : []) }}" class="primary-button">
            <i class="fas fa-plus icon-spaced"></i>Create Quotation
        </a>
    </div>
@endsection

@section('content')
    <section class="panel-card module-filter-panel filter-panel-regular">
        <form action="{{ route('quotations.index') }}" method="GET" class="module-filter-grid">
            <div class="module-filter-field">
                <label class="module-filter-label" for="quotation_client_filter">Client</label>
                <select name="c" id="quotation_client_filter" class="form-control">
                    <option value="">All Clients</option>
                    @foreach ($clients as $clientOption)
                        <option value="{{ $clientOption->clientid }}"
                            {{ (string) $selectedClientId === (string) $clientOption->clientid ? 'selected' : '' }}>
                            {{ $clientOption->business_name ?? ($clientOption->contact_name ?? 'Client') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="module-filter-actions">
                <button type="submit" class="primary-button">Apply</button>
                <a href="{{ route('quotations.index') }}" class="secondary-button">Reset</a>
            </div>
        </form>
    </section>

    <section class="invoice-group">
        @if (count($quotations) === 0)
            <div class="invoice-empty">
                <i class="fas fa-file-contract empty-state-icon"></i>
                <p class="no-empty-state-text">No quotations found.</p>
                <p class="small-text">Choose a client or create a new quotation to get started.</p>
            </div>
        @else
            <div class="invoice-table-wrap">
                <table class="data-table table-no-margin">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Quotation</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quotations as $quotation)
                            <tr>
                                <td>{!! $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $quotation['client']) : $quotation['client'] !!}</td>
                                <td>
                                    <div class="invoice-row-title">
                                        <div class="invoice-row-text">
                                            <strong>{!! $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $quotation['title'] ?? $quotation['number']) : ($quotation['title'] ?? $quotation['number']) !!}</strong>
                                            <div class="invoice-number-line">{!! $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $quotation['number']) : $quotation['number'] !!}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $quotation['due'] }}</td>
                                <td><span class="invoice-amount">{{ $quotation['amount'] }}</span></td>
                                <td>
                                    <span class="status-pill {{ strtolower($quotation['status']) }}">{{ $quotation['status'] }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('quotations.show', ['quotation' => $quotation['record_id'], 'c' => $selectedClientId]) }}" class="text-action-btn view">View</a>
                                    <a href="{{ route('quotations.create', ['step' => 2, 'c' => $quotation['client_id'] ?? $selectedClientId, 'd' => $quotation['record_id']]) }}" class="text-action-btn edit">Edit</a>
                                    <a href="{{ route('quotations.pdf', $quotation['record_id']) }}" class="text-action-btn pdf" target="_blank">PDF</a>
                                    <form method="POST" class="inline-delete" action="{{ route('quotations.destroy', ['quotation' => $quotation['record_id'], 'c' => $selectedClientId]) }}" onsubmit="return confirm('Delete {{ $quotation['number'] }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-action-btn delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
