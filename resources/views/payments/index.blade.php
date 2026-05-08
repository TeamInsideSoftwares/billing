@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('payments.gst-report') }}" class="secondary-button">GST Report</a>
    <a href="{{ route('payments.ledger') }}" class="secondary-button">View Ledger</a>
    <a href="{{ route('payments.create') }}" class="primary-button">Record Payment</a>
@endsection

@section('content')
    <section class="panel-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Payment</th>
                    <th>Client</th>
                    <th>Invoice</th>
                    <th>Reference</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th class="text-center">TDS</th>
                    <th class="text-end">Amount{{ !empty($selectedCurrency) ? ' (' . $selectedCurrency . ')' : '' }}</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($payments as $payment)
                <tr>
                    <td>
                        <strong class="payment-row-title">{!! isset($searchTerm) && $searchTerm ? str_ireplace($searchTerm, '<mark>' . $searchTerm . '</mark>', $payment['number']) : $payment['number'] !!}</strong>
                        @if(!empty($payment['description']))
                            <div class="payment-row-note">{{ $payment['description'] }}</div>
                        @endif
                    </td>
                    <td class="payment-cell-text">{!! isset($searchTerm) && $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $payment['client']) : $payment['client'] !!}</td>
                    <td class="payment-cell-text">{{ $payment['invoice'] ?: '-' }}</td>
                    <td class="payment-cell-text">{{ $payment['reference_number'] ?: '-' }}</td>
                    <td class="payment-cell-text">{{ $payment['date'] ?: '-' }}</td>
                    <td class="payment-cell-text">{{ $payment['method'] }}</td>
                    <td class="text-center">
                        <span class="payment-tds-badge {{ $payment['tds'] ? 'is-yes' : 'is-no' }}">
                            {{ $payment['tds'] ? 'Yes' : 'No' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <strong class="payment-row-amount">{{ number_format($payment['amount'], 0) }}</strong>
                    </td>
                    <td class="text-center">
                        <div class="table-actions payment-table-actions">
                            <a href="{{ route('payments.show', $payment['record_id']) }}" class="icon-action-btn view" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('payments.edit', $payment['record_id']) }}" class="icon-action-btn edit" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('payments.destroy', $payment['record_id']) }}" class="inline-delete" onsubmit="return confirm('Delete {{ $payment['number'] }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="icon-action-btn delete" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                <td colspan="9" class="no-records-cell">
                        <i class="fas fa-money-bill-wave empty-state-icon"></i>
                        <p class="no-empty-state-text">No payments recorded</p>
                        <p class="small-text">Record your first payment to track your collections.</p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>

    <style>
        .data-table th,
        .data-table td {
            padding-top: 0.55rem;
            padding-bottom: 0.55rem;
            vertical-align: middle;
        }

        .payment-row-title {
            color: #0f172a;
            font-size: 0.88rem;
            line-height: 1.15;
        }

        .payment-cell-text {
            color: #475569;
            font-size: 0.76rem;
            line-height: 1.15;
        }

        .payment-row-note {
            color: #64748b;
            font-size: 0.72rem;
            line-height: 1.2;
            margin-top: 0.14rem;
        }

        .payment-row-amount {
            color: #0f172a;
            font-size: 0.88rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .payment-tds-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 54px;
            padding: 0.12rem 0.42rem;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 700;
        }

        .payment-tds-badge.is-yes {
            background: #fef3c7;
            color: #b45309;
        }

        .payment-tds-badge.is-no {
            background: #e2e8f0;
            color: #475569;
        }

        .payment-table-actions {
            justify-content: center;
            gap: 0.28rem;
        }
    </style>
@endsection
