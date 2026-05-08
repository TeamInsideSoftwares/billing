@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('payments.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left icon-spaced"></i>Back to Payments
    </a>
    <a href="{{ route('payments.ledger') }}" class="secondary-button">View Ledger</a>
@endsection

@section('content')
    @php
        $monthOptions = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];
    @endphp

    <div class="gst-report-shell">
        <section class="panel-card gst-filter-panel">
            <form method="GET" action="{{ route('payments.gst-report') }}" class="gst-filter-grid">
                <div>
                    <label class="gst-label" for="gst_month_filter">Month</label>
                    <select name="month" id="gst_month_filter" class="form-control">
                        @foreach($monthOptions as $monthValue => $monthLabel)
                            <option value="{{ $monthValue }}" {{ $selectedMonth === $monthValue ? 'selected' : '' }}>
                                {{ $monthLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="gst-label" for="gst_year_filter">Year</label>
                    <select name="year" id="gst_year_filter" class="form-control">
                        @foreach($availableYears as $year)
                            <option value="{{ $year }}" {{ $selectedYear === (int) $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="gst-filter-actions">
                    <button type="submit" class="primary-button">Apply</button>
                    <a href="{{ route('payments.gst-report') }}" class="secondary-button">Reset</a>
                </div>
            </form>
        </section>

        <section class="panel-card gst-table-card">
            @if($rows->isEmpty())
                <div class="no-records-cell">
                    <i class="fas fa-file-invoice empty-state-icon"></i>
                    <p class="no-empty-state-text">No tax invoices found</p>
                    <p class="small-text">Try another month or year.</p>
                </div>
            @else
                <div class="gst-table-wrap">
                    <table class="data-table gst-table">
                        <thead>
                            <tr>
                                <th>TI Number</th>
                                <th>Invoice Title</th>
                                <th>Client</th>
                                <th class="text-end">Grand Total</th>
                                <th class="text-end">IGST</th>
                                <th class="text-end">SGST</th>
                                <th class="text-end">CGST</th>
                                <th class="text-end">Total Tax</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    <td>
                                        <a href="{{ route('invoices.show', ['invoice' => $row['invoiceid']]) }}" class="gst-link">
                                            {{ $row['ti_number'] }}
                                        </a>
                                    </td>
                                    <td>{{ $row['invoice_title'] }}</td>
                                    <td>{{ $row['client_name'] }}</td>
                                    <td class="text-end gst-number">{{ number_format($row['grand_total'], 0) }}</td>
                                    <td class="text-end gst-number">{{ $row['igst'] > 0 ? number_format($row['igst'], 0) : '-' }}</td>
                                    <td class="text-end gst-number">{{ $row['sgst'] > 0 ? number_format($row['sgst'], 0) : '-' }}</td>
                                    <td class="text-end gst-number">{{ $row['cgst'] > 0 ? number_format($row['cgst'], 0) : '-' }}</td>
                                    <td class="text-end gst-number gst-total-cell">{{ number_format($row['tax_total'], 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3">Totals</th>
                                <th class="text-end gst-number">{{ number_format($grandTotalSum, 0) }}</th>
                                <th class="text-end gst-number">{{ $igstTotal > 0 ? number_format($igstTotal, 0) : '-' }}</th>
                                <th class="text-end gst-number">{{ $sgstTotal > 0 ? number_format($sgstTotal, 0) : '-' }}</th>
                                <th class="text-end gst-number">{{ $cgstTotal > 0 ? number_format($cgstTotal, 0) : '-' }}</th>
                                <th class="text-end gst-number gst-total-cell">{{ number_format($taxTotal, 0) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <style>
        .gst-report-shell {
            display: grid;
            gap: 0.85rem;
        }

        .gst-filter-panel {
            padding: 0.7rem 0.85rem;
        }

        .gst-filter-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 0.65rem;
            align-items: end;
            max-width: 620px;
        }

        .gst-label {
            display: block;
            margin-bottom: 0.18rem;
            font-size: 0.72rem;
            font-weight: 600;
            color: #475569;
        }

        .gst-filter-panel .form-control {
            min-height: 34px;
            height: 34px;
            padding-top: 0.32rem;
            padding-bottom: 0.32rem;
            font-size: 0.82rem;
        }

        .gst-filter-actions {
            display: flex;
            gap: 0.45rem;
            align-items: center;
        }

        .gst-filter-actions .primary-button,
        .gst-filter-actions .secondary-button {
            padding-top: 0.42rem;
            padding-bottom: 0.42rem;
            font-size: 0.82rem;
        }

        .gst-table-card {
            padding: 0;
            overflow: hidden;
        }

        .gst-table-wrap {
            overflow-x: auto;
        }

        .gst-table {
            margin: 0;
        }

        .gst-table th,
        .gst-table td {
            padding: 0.72rem 0.85rem;
            vertical-align: middle;
        }

        .gst-table thead th {
            white-space: nowrap;
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
            background: #f8fafc;
        }

        .gst-link {
            color: #0f172a;
            font-weight: 600;
            text-decoration: none;
        }

        .gst-link:hover {
            color: #2563eb;
        }

        .gst-number {
            font-variant-numeric: tabular-nums;
        }

        .gst-total-cell {
            font-weight: 700;
            color: #0f172a;
        }

        .gst-table tfoot th {
            background: #f8fafc;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .gst-filter-grid {
                grid-template-columns: 1fr;
            }

            .gst-filter-actions {
                flex-wrap: wrap;
            }
        }
    </style>
@endsection
