@extends('layouts.app')

@section('header_actions')
    @if ($clientId)
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('payments.ledger', ['c' => $clientId]) }}"
                class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium">
                <i class="fas fa-book btn-icon"></i> View Ledger
            </a>
            <a href="{{ route('payments.create', ['c' => $clientId]) }}"
                class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
                <i class="fas fa-plus btn-icon"></i> Record Payment
            </a>
        </div>
    @endif
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
<div class="position-relative">
    <div class="row">
        <div class="col-12 col-md-4 mx-auto">
            <div class="bg-white p-3 rounded-3 shadow-sm">
                <div class="bg-light p-4 rounded-3 border mx-auto">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div>
                                <h5 class="fw-semibold text-black mb-0">Manage Payments</h5>
                                <p class="text-muted mb-0">Choose a client first to view and record payments in a focused view.</p>
                            </div>
                        </div>
                    </div>
                    <form action="{{ route('payments.index') }}" method="GET" class="payment-client-picker-form mainForm">
                        <div class="row g-2 mb-3">
                            <div class="col-12">
                                <label for="client-select"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Client({{ $clients->count() }})<span class="text-danger">*</span></label>
                                <select name="c" id="payment-client-select" class="form-select" autofocus>
                                    <option value="" selected disabled>Select a client</option>
                                    <option value="all">All Clients</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->clientid }}">
                                            {{ $client->business_name ?? $client->contact_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-end gap-2 mt-3">
                            <button type="button" id="btnViewPayments" class="btn btn-outline-primary bg-white text-primary fw-medium px-3 py-2">
                                <i class="fas fa-list btn-icon me-1"></i> View Payments
                            </button>
                            <button type="button" id="btnViewLedger" class="btn btn-outline-primary bg-white text-primary fw-medium px-3 py-2">
                                <i class="fas fa-book btn-icon me-1"></i> View Ledger
                            </button>
                            <button type="button" id="btnCreatePayment" class="btn btn-outline-primary btn-primary text-white fw-medium px-4 py-2">
                                <i class="fas fa-plus btn-icon me-1"></i> Record Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
    @else
        <div class="position-relative bg-white p-3 rounded-3 shadow-sm">
            <!-- Filters Card -->
            <div class="position-relative bg-light border p-3 rounded-3 mb-2">
                <form action="{{ route('payments.index') }}" method="GET" class="mainForm">
                    <div class="row g-2">
                        <div class="col-12 col-md-10">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1" for="payments_client_filter">Client</label>
                            <select name="c" id="payments_client_filter" class="form-select">
                                <option value="all" {{ (string) $clientId === 'all' ? 'selected' : '' }}>All Clients</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->clientid }}"
                                        {{ (string) $clientId === (string) $client->clientid ? 'selected' : '' }}>
                                        {{ $client->business_name ?? $client->contact_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-2 mt-auto d-flex gap-2">
                            <a href="{{ route('payments.index', ['c' => $clientId]) }}"
                                class="btn btn-outline-primary bg-white text-primary fw-medium w-100 text-center justify-content-center">
                                <i class="fas fa-sync-alt btn-icon me-1"></i> Reset
                            </a>
                            <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium w-100">
                                Apply <i class="fas fa-arrow-right btn-icon ms-1"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Table View -->
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="table-responsive">
                    <table class="table mainTable border align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Payment</th>
                                <th>Client / Invoice</th>
                                <th class="text-end">Settlement</th>
                                <th class="text-end">Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payments as $payment)
                                <tr>
                                    <td>
                                        <strong class="fw-semibold text-dark">{{ $payment['number'] }}</strong>
                                        <div class="text-muted small mt-1">
                                            {{ $payment['date'] ?: 'Date not set' }}
                                        </div>
                                        <div class="text-muted small">
                                            {{ $payment['method'] }}
                                            @if (!empty($payment['receipt_number']))
                                                <span class="mx-1">|</span>{{ $payment['receipt_number'] }}
                                            @endif
                                        </div>
                                        @if (!empty($payment['reference_number']))
                                            <div class="text-muted small">Ref: {{ $payment['reference_number'] }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $payment['client'] }}</div>
                                        <div class="text-muted small">
                                            {{ $payment['invoice'] ?: 'No linked invoice' }}
                                        </div>
                                        @if (!empty($payment['description']) && trim((string) $payment['description']) !== trim((string) $payment['number']))
                                            <div class="text-muted small">{{ $payment['description'] }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-column align-items-end">
                                            <strong class="text-dark fw-bold">
                                                {{ number_format((float) ($payment['amount'] ?? 0) + (float) ($payment['tds_amount'] ?? 0), 0) }}
                                            </strong>
                                            @if ((float) ($payment['amount'] ?? 0) > 0)
                                                <div class="text-muted small">
                                                    Received {{ number_format((float) ($payment['amount'] ?? 0), 0) }}
                                                </div>
                                            @endif
                                            @if ((float) ($payment['tds_amount'] ?? 0) > 0)
                                                <div class="text-danger small">
                                                    TDS {{ number_format((float) ($payment['tds_amount'] ?? 0), 0) }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        @if (($payment['status'] ?? 'active') === 'cancelled')
                                            <span class="status-pill cancelled">Cancelled</span>
                                        @else
                                            <span class="status-pill active">Active</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="tableActionButton d-inline-flex gap-1">
                                            <a href="{{ route('payments.show', $payment['record_id']) }}"
                                                class="bg01 color01">View</a>
                                            @if (($payment['status'] ?? 'active') !== 'cancelled')
                                                <a href="{{ route('payments.edit', $payment['record_id']) }}"
                                                    class="bg03 color03">Edit</a>
                                            @endif
                                            @if (($payment['status'] ?? 'active') !== 'cancelled')
                                                <form method="POST"
                                                    action="{{ route('payments.destroy', $payment['record_id']) }}"
                                                    class="d-inline"
                                                    data-name="{{ $payment['number'] }}"
                                                    onsubmit="return confirm('Cancel ' + this.dataset.name + '?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="bg04 color04">Cancel</button>
                                                </form>
                                            @else
                                                <form method="POST"
                                                    action="{{ route('payments.restore', $payment['record_id']) }}"
                                                    class="d-inline"
                                                    data-name="{{ $payment['number'] }}"
                                                    onsubmit="return confirm('Restore ' + this.dataset.name + '?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="bg02 color02">Restore</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-money-bill-wave mb-3 text-secondary fs-1 opacity-50"></i>
                                        <p class="fw-semibold text-dark mb-1">No payments recorded</p>
                                        <p class="small text-muted mb-0">Record your first payment to track your collections.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (collect($payments)->isNotEmpty() && !empty($clientId) && $clientId !== 'all')
                            <tfoot class="table-light border-top">
                                <tr>
                                    <th colspan="2" class="text-end fw-semibold text-muted">Total TDS</th>
                                    <th class="text-end fw-bold text-dark">{{ number_format($paymentsTotalTds, 0) }}</th>
                                    <th colspan="2"></th>
                                </tr>
                                <tr>
                                    <th colspan="2" class="text-end fw-semibold text-muted">Total Received</th>
                                    <th class="text-end fw-bold text-primary">{{ number_format($paymentsTotalReceived, 0) }}</th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
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
            const clientId = document.getElementById('payment-client-select')?.value;
            if (clientId && clientId !== 'all') {
                window.location.href = "{{ route('payments.ledger') }}?c=" + encodeURIComponent(clientId);
            } else {
                alert('Please select a specific client first.');
            }
        });

        document.querySelector('.payment-client-picker-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            document.getElementById('btnViewPayments')?.click();
        });
    </script>
@endsection
