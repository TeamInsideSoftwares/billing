@extends('layouts.app')

@section('header_actions')
<div class="d-flex align-items-center gap-2 flex-wrap">
    @if ($clientId && $clientId !== 'all')
    <a href="{{ route('payments.ledger', ['c' => $clientId]) }}"
        class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-receipt btn-icon"></i> View Ledger
    </a>
    @endif
    <a href="{{ route('payments.create', $clientId && $clientId !== 'all' ? ['c' => $clientId] : []) }}"
        class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
        Record Payment <i class="fas fa-arrow-right btn-icon ms-1"></i>
    </a>
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

<div class="position-relative bg-white p-2 rounded-3">
    <!-- Filters Card -->
    <div class="position-relative bg-DarkLight p-2 rounded-3 mb-2">
        <form action="{{ route('payments.index') }}" method="GET" class="mainForm">
            <div class="row g-2">
                <div class="col-12 col-md-2">
                    <select name="c" id="payments_client_filter" class="form-select" onchange="this.form.submit()">
                        <option value="all" {{ !$clientId || (string) $clientId==='all' ? 'selected' : '' }}>All Clients
                        </option>
                        @foreach ($clients as $client)
                        <option value="{{ $client->clientid }}" {{ (string) $clientId===(string) $client->clientid ?
                            'selected' : '' }}>
                            {{ $client->business_name ?? $client->contact_name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <div class="position-relative">
                        <i class="fas fa-search position-absolute text-muted"
                            style="left: 14px; top: 50%; transform: translateY(-50%); font-size: 15px;"></i>
                        <input type="text" name="search" id="payments_search_filter" class="form-control"
                            value="{{ $searchTerm ?? '' }}" placeholder="Search Receipt, Ref or Client Name"
                            style="padding-left: 38px;" onchange="this.form.submit()">
                    </div>
                </div>

                <div class="col-12 col-md-7 d-flex justify-content-end align-items-center gap-2 mt-auto">
                    <div class="btn-group shadow-sm" role="group" aria-label="View Toggle">
                        <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 h-auto"
                            style="font-size:0.875rem;" id="btn-grid-view">
                            <i class="fas fa-th-large toggle-icon"></i> Grid
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 h-auto"
                            style="font-size:0.875rem;" id="btn-list-view">
                            <i class="fas fa-list toggle-icon"></i> List
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Table View -->
    <div id="payments-list-view" class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3">
        <div class="table-responsive">
            <table class="table table-striped mainTable align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="30%">Payment Details</th>
                        <th width="30%">Client / Invoice</th>
                        <th width="20%" class="text-end">Settlement</th>
                        <th width="20%" class="text-end">Actions</th>
                    </tr> 
                </thead> 
                <tbody>
                    @forelse ($payments as $payment)
                    <tr>
                        <td>
                            <strong class="fw-semibold text-dark">{{ $payment['number'] }}</strong>
                            <div class="text-dark small lh-sm mt-1">
                                {{ $payment['date'] ?: 'Date not set' }}
                            </div>
                            <div class="text-dark small lh-sm mt-1">
                                <span class="badge bg-light  text-primary border text-uppercase fw-bold">{{ $payment['method'] }}</span>
                                @if (!empty($payment['receipt_number']))
                                <span class="mx-1">|</span>
                                <span class="badge text-bg-primary">{{ $payment['receipt_number'] }}</span>
                                @endif
                            </div>
                            @if (!empty($payment['reference_number']))
                            <div class="text-dark small lh-sm mt-1">Ref: {{ $payment['reference_number'] }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold">
                                    <span class="d-block position-absolute">{{ strtoupper(substr($payment['client'], 0,
                                        2)) }}</span>
                                    <div class="status-dot {{ ($payment['status'] ?? 'active') === 'cancelled' ? 'inactive' : 'active' }}"
                                        title="{{ ucfirst($payment['status'] ?? 'active') }}"></div>
                                </div>
                                <div>
                                    <span class="d-block fw-semibold text-dark">{{ $payment['client'] }}</span>
                                    @if (!empty($payment['invoice']))
                                    <span class="d-block text-dark small lh-sm">
                                        {{ $payment['invoice'] }}
                                    </span>
                                    @endif
                                    @if (!empty($payment['description']) && trim((string) $payment['description']) !==
                                    trim((string) $payment['number']))
                                    <span class="d-block text-dark small lh-sm mt-1">{{ $payment['description'] }}</span>
                                    @endif
                                </div> 
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="d-flex flex-column align-items-end">
                                <strong class="text-dark fw-bold">
                                    {{ number_format((float) ($payment['amount'] ?? 0) + (float) ($payment['tds_amount']
                                    ?? 0), 0) }}
                                </strong>
                                @if ((float) ($payment['amount'] ?? 0) > 0)
                                <div class="text-muted small">
                                    Received {{ number_format((float) ($payment['amount'] ?? 0), 0) }}
                                </div>
                                @endif
                                @if ((float) ($payment['tds_amount'] ?? 0) > 0)
                                <div class="text-muted small">
                                    TDS {{ number_format((float) ($payment['tds_amount'] ?? 0), 0) }}
                                </div>
                                @endif
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="tableActionButton d-inline-flex gap-1">
                                <button type="button" class="bg01 color01 border-0 view-pdf-btn"
                                    data-pdf-url="{{ route('payments.show', $payment['record_id']) }}">
                                    View
                                </button>
                                @if (($payment['status'] ?? 'active') !== 'cancelled')
                                <a href="{{ route('payments.edit', $payment['record_id']) }}"
                                    class="bg03 color03">Edit</a>
                                @endif
                                @if (($payment['status'] ?? 'active') !== 'cancelled')
                                <form method="POST" action="{{ route('payments.destroy', $payment['record_id']) }}"
                                    class="d-inline" data-name="{{ $payment['number'] }}"
                                    onsubmit="return confirm('Cancel ' + this.dataset.name + '?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg04 color04">Cancel</button>
                                </form>
                                @else
                                <form method="POST" action="{{ route('payments.restore', $payment['record_id']) }}"
                                    class="d-inline" data-name="{{ $payment['number'] }}"
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
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="fas fa-money-bill-wave mb-3 text-secondary fs-1 opacity-50"></i>
                            <p class="fw-semibold text-dark mb-1">No payments recorded</p>
                            <p class="small text-muted mb-0">Record your first payment to track your collections.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if (collect($payments)->isNotEmpty() && !empty($clientId) && $clientId !== 'all')
                <tfoot class="table-light">
                    <tr>
                        <th colspan="2" class="text-end fw-semibold text-muted">Total TDS</th>
                        <th class="text-end fw-bold text-danger">{{ number_format($paymentsTotalTds, 0) }}</th>
                        <th></th>
                    </tr>
                    <tr>
                        <th colspan="2" class="text-end fw-semibold text-muted">Total Received</th>
                        <th class="text-end fw-bold text-success fs-6 lh-sm">{{ number_format($paymentsTotalReceived, 0) }}
                        </th>
                        <th></th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    @if (collect($payments)->isNotEmpty() && !empty($clientId) && $clientId !== 'all')
    <!-- Payments Grid Summary -->
    <div id="payments-grid-summary" class="card border-0 bg-DarkLight p-2 rounded-3 mb-2 d-none">
        <div class="d-flex justify-content-end gap-4 text-dark fw-semibold">
            <div>
                <span class="text-dark small lh-sm">Total TDS:</span>
                <span class="text-danger fs-6 lh-sm ms-1">{{ number_format($paymentsTotalTds, 0) }} <span class="small lh-sm text-muted">{{ $selectedCurrency ?? 'INR' }}</span></span>
            </div>
            <div>
                <span class="text-dark small lh-sm">Total Received:</span>
                <span class="text-success fs-6 lh-sm ms-1">{{ number_format($paymentsTotalReceived, 0) }} <span class="small lh-sm text-muted">{{ $selectedCurrency ?? 'INR' }}</span></span>
            </div>
        </div>
    </div>
    @endif

    @if (count($payments) > 0)
    <!-- Payments Grid View -->
    <div id="payments-grid-view"
        class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-2 p-1 pb-3 mt-2 bg-DarkLight rounded-3 d-none mb-3">
        @foreach ($payments as $payment)
        <div class="col">
            <div class="card h-100 border-0 overflow-hidden">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div>
                        <!-- Header with Dates -->
                        <div class="d-flex justify-content-end align-items-center mb-3">
                            <div class="text-muted small" style="font-size: 13px;">Date: <span
                                    class="text-dark fw-semibold">{{ $payment['date'] ?: 'Date not set' }}</span></div>
                            <div>
                                @if(($payment['status'] ?? 'active') === 'cancelled')
                                <span class="status-pill d-inline-block overdue bg-danger-subtle text-danger fw-semibold rounded-pill py-0.5 px-2" style="font-size: 11px; line-height: 18px;">Cancelled</span>
                                @endif
                            </div>
                        </div>

                        <!-- Client details -->
                        <div class="d-flex align-items-center gap-2 mb-3 pb-3 border-bottom">
                            <div
                                class="tablePrifix position-relative align-self-center bg-primary-subtle text-primary rounded-circle fw-semibold flex-shrink-0">
                                <span class="d-block position-absolute">{{ strtoupper(substr($payment['client'], 0, 2)) }}</span>
                                <div class="status-dot {{ ($payment['status'] ?? 'active') === 'cancelled' ? 'inactive' : 'active' }}"
                                    title="{{ ucfirst($payment['status'] ?? 'active') }}"></div>
                            </div>
                            <div class="flex-grow-1 min-w-0 ps-2">
                                <h6 class="fw-semibold text-dark mb-1 text-truncate lh-sm"
                                    title="{{ $payment['client'] }}">
                                    {{ $payment['client'] }}
                                </h6>
                                @if (!empty($payment['invoice']))
                                <span class="d-block text-dark small lh-sm text-truncate" title="{{ $payment['invoice'] }}">
                                    {{ $payment['invoice'] }}
                                </span>
                                @else
                                <span class="d-block text-muted small lh-sm text-truncate">
                                   
                                </span>
                                @endif
                            </div>
                        </div>

                        <!-- Payment Info -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <strong class="text-dark text-truncate lh-sm"
                                    title="{{ $payment['number'] }}">
                                    {{ $payment['number'] }}
                                </strong>
                            </div>
                            <div class="invoice-number-line mt-1.5 d-flex flex-wrap align-items-center gap-1">
                                <span class="badge bg-light text-primary border text-uppercase fw-bold" style="font-size: 11px;">{{ $payment['method'] }}</span>
                                @if (!empty($payment['receipt_number']))
                                <span class="badge text-bg-primary" style="font-size: 11px;">#{{ $payment['receipt_number'] }}</span>
                                @endif
                                @if (!empty($payment['reference_number']))
                                <span class="text-dark small d-block w-100 mt-1">Ref: <b>{{ $payment['reference_number'] }}</b></span>
                                @endif
                            </div>
                            @if (!empty($payment['description']) && trim((string) $payment['description']) !== trim((string) $payment['number']))
                            <p class="text-dark small mt-1 mb-0 text-truncate-2" title="{{ $payment['description'] }}">{{ $payment['description'] }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Amount Area -->
                    <div class="bg-light rounded-3 px-3 py-2 mt-auto d-flex flex-column mb-2 text-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-medium">Settlement</span>
                            <div class="text-end">
                                <span class="text-dark fs-6 lh-sm fw-bold">{{ number_format((float) ($payment['amount'] ?? 0) + (float) ($payment['tds_amount'] ?? 0), 0) }}</span>
                                <span class="currency-code-small lh-sm text-muted d-block">{{ $payment['currency'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="tableActionButton d-flex flex-wrap gap-1 mt-2">
                        <button type="button" class="bg01 color01 flex-grow-1 text-center border-0 view-pdf-btn"
                            data-pdf-url="{{ route('payments.show', $payment['record_id']) }}">
                            View
                        </button>
                        @if (($payment['status'] ?? 'active') !== 'cancelled')
                        <a href="{{ route('payments.edit', $payment['record_id']) }}"
                            class="bg03 color03 flex-grow-1 text-center">Edit</a>
                        @endif
                        @if (($payment['status'] ?? 'active') !== 'cancelled')
                        <form method="POST" action="{{ route('payments.destroy', $payment['record_id']) }}"
                            class="d-inline flex-grow-1" data-name="{{ $payment['number'] }}"
                            onsubmit="return confirm('Cancel ' + this.dataset.name + '?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg04 color04 w-100 text-center border-0">Cancel</button>
                        </form>
                        @else
                        <form method="POST" action="{{ route('payments.restore', $payment['record_id']) }}"
                            class="d-inline flex-grow-1" data-name="{{ $payment['number'] }}"
                            onsubmit="return confirm('Restore ' + this.dataset.name + '?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="bg02 color02 w-100 text-center border-0">Restore</button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

<div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2" style="height: 80vh;">
                <iframe id="pdfViewerFrame" src="" style="width: 100%; height: 100%; border: 0;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
    const pdfModal = new bootstrap.Modal(document.getElementById('pdfViewerModal'));
    const pdfFrame = document.getElementById('pdfViewerFrame');

    document.querySelectorAll('.view-pdf-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            pdfFrame.src = this.dataset.pdfUrl;
            pdfModal.show();
        });
    });

    document.getElementById('pdfViewerModal').addEventListener('hidden.bs.modal', function () {
        pdfFrame.src = '';
    });

    // View Toggle Logic
    const btnList = document.getElementById('btn-list-view');
    const btnGrid = document.getElementById('btn-grid-view');
    const listView = document.getElementById('payments-list-view');
    const gridView = document.getElementById('payments-grid-view');
    const summaryEl = document.getElementById('payments-grid-summary');

    function setView(viewType) {
        if (viewType === 'grid') {
            if (listView) listView.classList.add('d-none');
            if (gridView) gridView.classList.remove('d-none');
            if (summaryEl) summaryEl.classList.remove('d-none');
            if (btnList) {
                btnList.classList.remove('active', 'btn-primary');
                btnList.classList.add('btn-outline-primary');
            }
            if (btnGrid) {
                btnGrid.classList.add('active', 'btn-primary');
                btnGrid.classList.remove('btn-outline-primary');
            }
            localStorage.setItem('payments_view_preference', 'grid');
        } else {
            if (listView) listView.classList.remove('d-none');
            if (gridView) gridView.classList.add('d-none');
            if (summaryEl) summaryEl.classList.add('d-none');
            if (btnList) {
                btnList.classList.add('active', 'btn-primary');
                btnList.classList.remove('btn-outline-primary');
            }
            if (btnGrid) {
                btnGrid.classList.remove('active', 'btn-primary');
                btnGrid.classList.add('btn-outline-primary');
            }
            localStorage.setItem('payments_view_preference', 'list');
        }
    }

    if (btnList && btnGrid) {
        btnList.addEventListener('click', () => setView('list'));
        btnGrid.addEventListener('click', () => setView('grid'));

        const savedPref = localStorage.getItem('payments_view_preference');
        if (savedPref === 'grid') {
            setView('grid');
        } else {
            setView('list');
        }
    }
</script>
@endsection
