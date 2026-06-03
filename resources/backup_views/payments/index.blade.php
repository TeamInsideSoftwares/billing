@extends('layouts.app')

@section('header_actions')
    <div class="header-actions-wrapper">
        @if ($clientId)
            <a href="{{ route('payments.ledger', ['c' => $clientId]) }}" class="secondary-button">View Ledger</a>
            <a href="{{ route('payments.create', ['c' => $clientId]) }}" class="primary-button">Record Payment</a>
        @endif
    </div>
@endsection

@section('content')
    @php
        $paymentsCount = collect($payments ?? [])->count();
        $paymentsTotalReceived = collect($payments ?? [])->sum(function ($paymentRow) {
            return (float) ($paymentRow['amount'] ?? 0);
        });
        $paymentsTotalTds = collect($payments ?? [])->sum(function ($paymentRow) {
            return (float) ($paymentRow['tds_amount'] ?? 0);
        });
    @endphp
    @if (!$clientId)
        <div class="payment-client-picker-wrap">
            <div class="payment-client-picker">
                <div class="payment-client-picker-head">
                    <div class="payment-client-picker-title">
                        <div class="payment-client-picker-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div>
                            <strong>Manage Payments</strong>
                            <p>Choose a client first to view and record payments in a focused view.</p>
                        </div>
                    </div>
                    <span class="payment-client-count">{{ $clients->count() }} client(s)</span>
                </div>
                <form action="{{ route('payments.index') }}" method="GET" class="payment-client-picker-form">
                    <div class="payment-client-picker-field">
                        <label for="payment-client-select">Client</label>
                        <select name="c" id="payment-client-select" class="form-control" autofocus>
                            <option value="" selected disabled>Select a client</option>
                            <option value="all">All Clients</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->clientid }}">
                                    {{ $client->business_name ?? $client->contact_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="payment-client-picker-actions">
                        <button type="button" id="btnViewPayments" class="secondary-button action-btn-lg">
                            <i class="fas fa-list icon-spaced"></i> View Payments
                        </button>
                        <button type="button" id="btnViewLedger" class="secondary-button action-btn-lg">
                            <i class="fas fa-book icon-spaced"></i> View Ledger
                        </button>
                        <button type="button" id="btnCreatePayment" class="primary-button action-btn-lg">
                            <i class="fas fa-plus icon-spaced"></i> Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <section class="panel-card module-filter-panel filter-panel-regular">
            <form action="{{ route('payments.index') }}" method="GET" class="module-filter-grid">
                <div class="module-filter-field">
                    <label class="module-filter-label" for="payments_client_filter">Client</label>
                    <select name="c" id="payments_client_filter" class="form-control">
                        <option value="all" {{ (string) $clientId === 'all' ? 'selected' : '' }}>All Clients</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->clientid }}"
                                {{ (string) $clientId === (string) $client->clientid ? 'selected' : '' }}>
                                {{ $client->business_name ?? $client->contact_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="module-filter-actions">
                    <button type="submit" class="primary-button">Apply</button>
                    <a href="{{ route('payments.index', ['c' => $clientId]) }}" class="secondary-button">Reset</a>
                </div>
            </form>
        </section>

        <section class="panel-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Payment</th>
                        <th>Client / Invoice</th>
                        <th class="text-end">Settlement</th>
                        <th class="text-end">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>
                                <strong class="payment-row-title">{{ $payment['number'] }}</strong>
                                <div class="payment-row-note">
                                    {{ $payment['date'] ?: 'Date not set' }}
                                </div>
                                <div class="payment-row-note">
                                    {{ $payment['method'] }}
                                    @if (!empty($payment['receipt_number']))
                                        <span class="mx-1">|</span>{{ $payment['receipt_number'] }}
                                    @endif
                                </div>
                                @if (!empty($payment['reference_number']))
                                    <div class="payment-row-note">Ref: {{ $payment['reference_number'] }}</div>
                                @endif
                            </td>
                            <td class="payment-cell-text">
                                <div class="fw-semibold text-dark">{{ $payment['client'] }}</div>
                                <div class="payment-row-note">
                                    {{ $payment['invoice'] ?: 'No linked invoice' }}
                                </div>
                                @if (!empty($payment['description']) && trim((string) $payment['description']) !== trim((string) $payment['number']))
                                    <div class="payment-row-note">{{ $payment['description'] }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="payment-settlement-block">
                                    <strong class="payment-row-amount">
                                        {{ number_format((float) ($payment['amount'] ?? 0) + (float) ($payment['tds_amount'] ?? 0), 0) }}
                                    </strong>
                                    @if ((float) ($payment['amount'] ?? 0) > 0)
                                        <div class="payment-row-note payment-row-note--right">
                                            Received {{ number_format((float) ($payment['amount'] ?? 0), 0) }}
                                        </div>
                                    @endif
                                    @if ((float) ($payment['tds_amount'] ?? 0) > 0)
                                        <div class="payment-row-note payment-row-note--right payment-row-note--tds">
                                            TDS {{ number_format((float) ($payment['tds_amount'] ?? 0), 0) }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end">
                                @if (($payment['status'] ?? 'active') === 'cancelled')
                                    <span class="status-pill status-pill-cancelled">Cancelled</span>
                                @else
                                    <span class="status-pill status-pill-running">Active</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="table-actions payment-table-actions">
                                    <a href="{{ route('payments.show', $payment['record_id']) }}"
                                        class="text-action-btn view">View</a>
                                    @if (($payment['status'] ?? 'active') !== 'cancelled')
                                        <a href="{{ route('payments.edit', $payment['record_id']) }}"
                                            class="text-action-btn edit">Edit</a>
                                    @endif
                                    @if (($payment['status'] ?? 'active') !== 'cancelled')
                                        <form method="POST"
                                            action="{{ route('payments.destroy', $payment['record_id']) }}"
                                            class="inline-delete"
                                            onsubmit="return confirm('Cancel {{ $payment['number'] }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-action-btn delete">Cancel</button>
                                        </form>
                                    @else
                                        <form method="POST"
                                            action="{{ route('payments.restore', $payment['record_id']) }}"
                                            class="inline-delete"
                                            onsubmit="return confirm('Restore {{ $payment['number'] }}?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-action-btn secondary">Restore</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="no-records-cell">
                                <i class="fas fa-money-bill-wave empty-state-icon"></i>
                                <p class="no-empty-state-text">No payments recorded</p>
                                <p class="small-text">Record your first payment to track your collections.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if (collect($payments)->isNotEmpty() && !empty($clientId) && $clientId !== 'all')
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-end">Total TDS</th>
                            <th class="text-end">{{ number_format($paymentsTotalTds, 0) }}</th>
                            <th colspan="2"></th>
                        </tr>
                        <tr>
                            <th colspan="2" class="text-end">Total Received</th>
                            <th class="text-end">{{ number_format($paymentsTotalReceived, 0) }}</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </section>
    @endif

    <script>
        document.getElementById('btnViewPayments')?.addEventListener('click', function() {
            let clientId = document.getElementById('payment-client-select')?.value;
            if (!clientId) {
                clientId = 'all';
            }
            window.location.href = "{{ route('payments.index') }}?c=" + encodeURIComponent(clientId);
        });

        document.getElementById('btnCreatePayment')?.addEventListener('click', function() {
            const clientId = document.getElementById('payment-client-select')?.value;
            if (clientId && clientId !== 'all') {
                window.location.href = "{{ route('payments.create') }}?c=" + encodeURIComponent(clientId);
            } else {
                alert('Please select a specific client first.');
            }
        });

        document.getElementById('btnViewLedger')?.addEventListener('click', function() {
            let clientId = document.getElementById('payment-client-select')?.value;
            if (!clientId) {
                clientId = 'all';
            }
            window.location.href = "{{ route('payments.ledger') }}?c=" + encodeURIComponent(clientId);
        });

        document.querySelector('.payment-client-picker-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            document.getElementById('btnViewPayments')?.click();
        });
    </script>
@endsection
