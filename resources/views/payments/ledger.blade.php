@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('payments.index', $selectedClientId !== '' ? ['c' => $selectedClientId] : []) }}" class="secondary-button">
        <i class="fas fa-arrow-left icon-spaced"></i>Back to Payments
    </a>
    <a href="{{ route('payments.gst-report', $selectedClientId !== '' ? ['c' => $selectedClientId] : []) }}" class="secondary-button">
        GST Report
    </a>
    <a href="{{ route('payments.create', $selectedClientId !== '' ? ['c' => $selectedClientId] : []) }}" class="primary-button">
        <i class="fas fa-plus icon-spaced"></i>Record Payment
    </a>
@endsection

@section('content')
    <div class="ledger-shell">
        <section class="panel-card ledger-filter-panel">
            <form method="GET" action="{{ route('payments.ledger') }}" class="ledger-filter-grid">
                @if($selectedClientId !== '')
                    <input type="hidden" name="c" value="{{ $selectedClientId }}">
                @endif
                <div>
                    <label class="ledger-label">Client</label>
                    <div class="ledger-static-value">{{ $selectedClientName }}</div>
                </div>
                <div>
                    <label class="ledger-label" for="ledger_fy_filter">Financial Year</label>
                    <select name="fy" id="ledger_fy_filter" class="form-control">
                        <option value="all" {{ $selectedFyId === 'all' ? 'selected' : '' }}>All</option>
                        @foreach($financialYears as $fy)
                            <option value="{{ $fy->fy_id }}" {{ (string) $selectedFyId === (string) $fy->fy_id ? 'selected' : '' }}>
                                {{ $fy->financial_year }}{{ $fy->default ? ' (Default)' : '' }}
                            </option>
                        @endforeach
                    </select>
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
                <div class="ledger-table-toolbar">
                    <div>
                        <strong class="ledger-table-title">Ledger Entries</strong>
                        <div class="ledger-table-subtitle">{{ $ledgerEntries->count() }} row(s) in statement view</div>
                    </div>
                </div>
                <div class="ledger-table-wrap">
                    <table class="data-table ledger-table">
                        <thead>
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Narration</th>
                                <th scope="col">Reference</th>
                                <th scope="col" class="text-end">Billed</th>
                                <th scope="col" class="text-end">Received</th>
                                <th scope="col" class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ledgerEntries as $entry)
                                <tr>
                                    <td class="ledger-date-cell ledger-cell-text">{{ $entry['date'] }}</td>
                                    <td>
                                        {{ $entry['description'] !== '' ? $entry['description'] : '-' }}
                                    </td>
                                    <td class="ledger-cell-text">
                                        @if($entry['reference_url'])
                                            <a href="{{ $entry['reference_url'] }}" class="ledger-ref-link">{{ $entry['reference_label'] }}</a>
                                        @else
                                            <span class="ledger-ref-link">{{ $entry['reference_label'] }}</span>
                                        @endif
                                        @if(!empty($entry['reference_meta']))
                                            <div class="ledger-ref-meta">{{ $entry['reference_meta'] }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end ledger-amount-cell">
                                        {{ $entry['debit'] > 0 ? number_format($entry['debit']) : '-' }}
                                    </td>
                                    <td class="text-end ledger-amount-cell">
                                        {{ $entry['credit'] > 0 ? number_format($entry['credit']) : '-' }}
                                    </td>
                                    <td class="text-end ledger-balance-cell">
                                        {{ number_format($entry['balance']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5">Closing Balance</th>
                                <th class="text-end">{{ number_format($closingBalance) }}</th>
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
            grid-template-columns: 1.15fr 1fr auto;
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

        .ledger-static-value {
            min-height: 34px;
            display: flex;
            align-items: center;
            padding: 0.32rem 0.7rem;
            border: 1px solid #dbe2ea;
            border-radius: 0.55rem;
            background: #f8fafc;
            color: #0f172a;
            font-size: 0.84rem;
            font-weight: 600;
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

        .ledger-table-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.8rem 0.9rem;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }

        .ledger-table-title {
            display: block;
            font-size: 0.88rem;
            color: #0f172a;
            line-height: 1.1;
        }

        .ledger-table-subtitle {
            margin-top: 0.16rem;
            font-size: 0.75rem;
            color: #64748b;
        }

        .ledger-table-wrap {
            overflow-x: auto;
            margin: 0;
            background: #fff;
        }

        .ledger-table {
            margin: 0;
            border-collapse: collapse;
        }

        .ledger-table th,
        .ledger-table td {
            padding-top: 0.55rem;
            padding-bottom: 0.55rem;
            padding-left: 0.9rem;
            padding-right: 0.9rem;
            vertical-align: middle;
        }

        .ledger-table thead th {
            white-space: nowrap;
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
            background: #f8fafc;
            border-bottom: 1px solid #dbe2ea;
        }

        .ledger-table tbody td {
            border-bottom: 1px solid #eef2f7;
            color: #475569;
            line-height: 1.15;
        }

        .ledger-table tbody tr:last-child td {
            border-bottom: 1px solid #dbe2ea;
        }

        .ledger-date-cell {
            white-space: nowrap;
            color: #334155;
            font-weight: 600;
            font-size: 0.76rem;
            line-height: 1.15;
        }

        .ledger-cell-text {
            color: #475569;
            font-size: 0.76rem;
            line-height: 1.15;
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
            font-size: 0.88rem;
            line-height: 1.15;
            text-decoration: none;
            word-break: break-word;
        }

        .ledger-ref-link:hover {
            color: #2563eb;
        }

        .ledger-ref-meta {
            margin-top: 0.14rem;
            font-size: 0.72rem;
            line-height: 1.2;
            color: #64748b;
            word-break: break-word;
        }

        .ledger-amount-cell {
            font-variant-numeric: tabular-nums;
            color: #0f172a;
            font-size: 0.88rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .ledger-balance-cell {
            font-variant-numeric: tabular-nums;
            color: #0f172a;
            font-weight: 700;
            font-size: 0.88rem;
            white-space: nowrap;
        }

        .ledger-table tfoot th {
            background: #f8fafc;
            font-weight: 700;
            border-top: 1px solid #dbe2ea;
            border-bottom: 0;
            color: #0f172a;
            font-size: 0.78rem;
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
