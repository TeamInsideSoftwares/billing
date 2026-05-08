@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('payments.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left icon-spaced"></i>Back to Payments
    </a>
    <a href="{{ route('payments.create') }}" class="primary-button">
        <i class="fas fa-plus icon-spaced"></i>Record Payment
    </a>
@endsection

@section('content')
    <div class="ledger-shell">
        <section class="panel-card ledger-filter-panel">
            <form method="GET" action="{{ route('payments.ledger') }}" class="ledger-filter-grid">
                <div>
                    <label class="ledger-label" for="ledger_client_filter">Client</label>
                    <select name="c" id="ledger_client_filter" class="form-control">
                        <option value="">All Clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->clientid }}" {{ (string) $selectedClientId === (string) $client->clientid ? 'selected' : '' }}>
                                {{ $client->business_name ?? $client->contact_name ?? 'Client' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ledger-label" for="ledger_type_filter">Type</label>
                    <select name="type" id="ledger_type_filter" class="form-control">
                        <option value="">All Entries</option>
                        <option value="invoice" {{ $selectedType === 'invoice' ? 'selected' : '' }}>Invoice</option>
                        <option value="payment" {{ $selectedType === 'payment' ? 'selected' : '' }}>Payment</option>
                        <option value="tds" {{ $selectedType === 'tds' ? 'selected' : '' }}>TDS</option>
                    </select>
                </div>
                <div>
                    <label class="ledger-label" for="ledger_from_filter">From</label>
                    <input type="date" name="from" id="ledger_from_filter" class="form-control" value="{{ $fromDate }}">
                </div>
                <div>
                    <label class="ledger-label" for="ledger_to_filter">To</label>
                    <input type="date" name="to" id="ledger_to_filter" class="form-control" value="{{ $toDate }}">
                </div>
                <div class="ledger-filter-search">
                    <label class="ledger-label" for="ledger_search_filter">Search</label>
                    <input type="text" name="search" id="ledger_search_filter" class="form-control" value="{{ $searchTerm }}" placeholder="Reference, client, description">
                </div>
                <div class="ledger-filter-actions">
                    <button type="submit" class="primary-button">Apply</button>
                    <a href="{{ route('payments.ledger') }}" class="secondary-button">Reset</a>
                </div>
            </form>
        </section>

        <section class="panel-card ledger-table-card">
            @if($ledgerEntries->isEmpty())
                <div class="no-records-cell">
                    <i class="fas fa-book empty-state-icon"></i>
                    <p class="no-empty-state-text">No ledger entries found</p>
                    <p class="small-text">Try widening the filters or record invoices and payments first.</p>
                </div>
            @else
                <div class="ledger-table-wrap">
                    <table class="data-table ledger-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Particulars</th>
                                <th>Reference</th>
                                <th>Type</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ledgerEntries as $entry)
                                <tr>
                                    <td class="ledger-date-cell">{{ $entry['date'] }}</td>
                                    <td>
                                        <strong>{{ $entry['client_name'] }}</strong>
                                        @if($entry['description'] !== '')
                                            <div class="ledger-note">{{ $entry['description'] }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($entry['reference_url'])
                                            <a href="{{ $entry['reference_url'] }}" class="ledger-ref-link">{{ $entry['reference_label'] }}</a>
                                        @else
                                            <span class="ledger-ref-link">{{ $entry['reference_label'] }}</span>
                                        @endif
                                        <div class="ledger-meta">{{ $entry['reference_number'] }}</div>
                                    </td>
                                    <td>
                                        <span class="ledger-type-badge is-{{ $entry['type'] }}">{{ $entry['type_label'] }}</span>
                                    </td>
                                    <td class="text-end ledger-amount-cell">
                                        {{ number_format($entry['amount'], 2) }}
                                    </td>
                                    <td class="text-end ledger-balance-cell">
                                        {{ number_format($entry['balance'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5">Closing Balance</th>
                                <th class="text-end">{{ number_format($closingBalance, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <style>
        .ledger-shell {
            display: grid;
            gap: 0.85rem;
        }

        .ledger-filter-panel {
            padding: 0.7rem 0.85rem;
        }

        .ledger-filter-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.8fr 0.8fr 0.8fr 1.2fr auto;
            gap: 0.6rem;
            align-items: end;
        }

        .ledger-label {
            display: block;
            margin-bottom: 0.18rem;
            font-size: 0.72rem;
            font-weight: 600;
            color: #475569;
        }

        .ledger-filter-panel .form-control {
            min-height: 34px;
            height: 34px;
            padding-top: 0.32rem;
            padding-bottom: 0.32rem;
            font-size: 0.82rem;
        }

        .ledger-filter-search {
            min-width: 180px;
        }

        .ledger-filter-actions {
            display: flex;
            gap: 0.45rem;
            align-items: center;
        }

        .ledger-filter-actions .primary-button,
        .ledger-filter-actions .secondary-button {
            padding-top: 0.42rem;
            padding-bottom: 0.42rem;
            font-size: 0.82rem;
        }

        .ledger-table-card {
            padding: 0;
            overflow: hidden;
        }

        .ledger-table-wrap {
            overflow-x: auto;
        }

        .ledger-table {
            margin: 0;
        }

        .ledger-table th,
        .ledger-table td {
            padding: 0.8rem 0.9rem;
            vertical-align: middle;
        }

        .ledger-table thead th {
            white-space: nowrap;
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
            background: #f8fafc;
        }

        .ledger-date-cell {
            white-space: nowrap;
            color: #334155;
            font-weight: 600;
        }

        .ledger-note,
        .ledger-meta {
            margin-top: 0.18rem;
            font-size: 0.76rem;
            color: #64748b;
            line-height: 1.3;
        }

        .ledger-ref-link {
            color: #0f172a;
            font-weight: 600;
            text-decoration: none;
        }

        .ledger-ref-link:hover {
            color: #2563eb;
        }

        .ledger-type-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 88px;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .ledger-type-badge.is-invoice {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .ledger-type-badge.is-payment {
            background: #dcfce7;
            color: #15803d;
        }

        .ledger-type-badge.is-tds {
            background: #fef3c7;
            color: #b45309;
        }

        .ledger-amount-cell {
            font-variant-numeric: tabular-nums;
            color: #0f172a;
            font-weight: 600;
        }

        .ledger-balance-cell {
            font-variant-numeric: tabular-nums;
            color: #0f172a;
            font-weight: 700;
        }

        .ledger-table tfoot th {
            background: #f8fafc;
            font-weight: 700;
        }

        @media (max-width: 1100px) {
            .ledger-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ledger-filter-actions {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 768px) {
            .ledger-summary-grid,
            .ledger-filter-grid {
                grid-template-columns: 1fr;
            }

            .ledger-filter-actions {
                flex-wrap: wrap;
            }
        }
    </style>
@endsection
