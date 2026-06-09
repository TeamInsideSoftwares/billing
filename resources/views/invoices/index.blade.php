@extends('layouts.app')

@section('header_actions')
<div class="d-flex align-items-center gap-2 flex-wrap">
    <a href="{{ route('invoices.expiry-list', $selectedClientId ? ['c' => $selectedClientId] : []) }}"
        class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-calendar-times btn-icon"></i> Expiry List
    </a>

    <a href="{{ route('invoices.create', $selectedClientId ? ['c' => $selectedClientId] : []) }}"
        class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-plus btn-icon"></i> Create Invoice
    </a>
</div>
@endsection

@section('content')
@php
$selectedClient = $clients->firstWhere('clientid', $selectedClientId);
$selectedClientCurrency = $selectedClient->currency ?? null;
$currentTab = in_array($selectedTab ?? 'invoices', ['invoices', 'outstanding', 'upcoming', 'draft', 'cancelled'], true)
? $selectedTab
: 'invoices';
@endphp

<div class="position-relative bg-white p-3 rounded-3 shadow-sm">
    <!-- Filters Card -->
    <div class="position-relative bg-light border p-3 rounded-3 mb-2">
        <form action="{{ route('invoices.index') }}" method="GET" class="mainForm">
            <input type="hidden" name="tab" value="{{ $selectedTab ?? 'invoices' }}">
            <input type="hidden" name="type" value="{{ $selectedType ?? '' }}">

            <div class="row g-2">
                <div class="col-12 col-md-2">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                        for="invoice_client_filter">Client</label>
                    <select name="c" id="invoice_client_filter" class="form-select">
                        <option value="">All Clients</option>
                        @foreach ($clients as $clientOption)
                        <option value="{{ $clientOption->clientid }}" {{ (string) $selectedClientId===(string)
                            $clientOption->clientid ? 'selected' : '' }}>
                            {{ $clientOption->business_name ?? ($clientOption->contact_name ?? 'Client') }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2 mt-auto d-flex gap-2">
                    <a href="{{ route('invoices.index', array_filter([
                            'tab' => $selectedTab ?? 'invoices',
                            'type' => $selectedType ?? '',
                        ])) }}" class="btn btn-outline-primary bg-white text-primary fw-medium">
                        <i class="fas fa-sync-alt btn-icon me-1"></i> Reset
                    </a>
                    <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                        Apply <i class="fas fa-arrow-right btn-icon ms-1"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <ul class="nav nav-underline mb-3">
        <li class="nav-item">
            <a class="nav-link rounded-0 {{ $currentTab === 'invoices' ? 'active' : 'text-secondary' }} d-flex align-items-center gap-1 fw-medium"
                href="{{ route('invoices.index', array_filter(['tab' => 'invoices', 'c' => $selectedClientId, 'type' => $selectedType ?? ''])) }}">
                All <span
                    class="badge rounded-pill {{ $currentTab === 'invoices' ? 'bg-primary text-white' : 'bg-secondary text-white' }}">{{
                    $paidInvoicesCount ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-0 {{ $currentTab === 'outstanding' ? 'active' : 'text-secondary' }} d-flex align-items-center gap-1 fw-medium"
                href="{{ route('invoices.index', array_filter(['tab' => 'outstanding', 'c' => $selectedClientId, 'type' => $selectedType ?? ''])) }}">
                Outstanding <span
                    class="badge rounded-pill {{ $currentTab === 'outstanding' ? 'bg-primary text-white' : 'bg-secondary text-white' }}">{{
                    $outstandingInvoicesCount ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-0 {{ $currentTab === 'upcoming' ? 'active' : 'text-secondary' }} d-flex align-items-center gap-1 fw-medium"
                href="{{ route('invoices.index', array_filter(['tab' => 'upcoming', 'c' => $selectedClientId, 'type' => $selectedType ?? ''])) }}">
                Upcoming <span
                    class="badge rounded-pill {{ $currentTab === 'upcoming' ? 'bg-primary text-white' : 'bg-secondary text-white' }}">{{
                    $upcomingInvoicesCount ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-0 {{ $currentTab === 'draft' ? 'active' : 'text-secondary' }} d-flex align-items-center gap-1 fw-medium"
                href="{{ route('invoices.index', array_filter(['tab' => 'draft', 'c' => $selectedClientId, 'type' => $selectedType ?? ''])) }}">
                Draft <span
                    class="badge rounded-pill {{ $currentTab === 'draft' ? 'bg-primary text-white' : 'bg-secondary text-white' }}">{{
                    $draftInvoicesCount ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-0 {{ $currentTab === 'cancelled' ? 'active' : 'text-secondary' }} d-flex align-items-center gap-1 fw-medium"
                href="{{ route('invoices.index', array_filter(['tab' => 'cancelled', 'c' => $selectedClientId, 'type' => $selectedType ?? ''])) }}">
                Cancelled <span
                    class="badge rounded-pill {{ $currentTab === 'cancelled' ? 'bg-primary text-white' : 'bg-secondary text-white' }}">{{
                    $cancelledInvoicesCount ?? 0 }}</span>
            </a>
        </li>
    </ul>

    @if ($currentTab === 'upcoming')
    <div class="invoice-list-meta px-1 mb-2">
        <div class="meta-info">
            <strong class="text-dark">Upcoming invoices</strong>
            <span class="text-muted small d-block">Active invoices with a future due date.</span>
        </div>
    </div>
    @elseif ($currentTab === 'draft')
    <div class="invoice-list-meta px-1 mb-2">
        <div class="meta-info">
            <strong class="text-dark">Draft invoices</strong>
            <span class="text-muted small d-block">Invoices that are not finalized yet.</span>
        </div>
    </div>
    @elseif ($currentTab === 'outstanding')
    <div class="invoice-list-meta px-1 mb-2">
        <div class="meta-info">
            <strong class="text-dark">Outstanding invoices</strong>
            <span class="text-muted small d-block">Invoices that are unpaid or partially paid.</span>
        </div>
    </div>
    @elseif ($currentTab === 'cancelled')
    <div class="invoice-list-meta px-1 mb-2">
        <div class="meta-info">
            <strong class="text-dark">Cancelled invoices</strong>
            <span class="text-muted small d-block">All invoices that have been cancelled.</span>
        </div>
    </div>
    @endif

    @if ($allInvoices->isEmpty())
    <div class="card border-0 shadow-sm py-5 text-center text-muted mb-3">
        <div class="card-body">
            <i class="fas fa-file-invoice mb-3 text-secondary fs-1 opacity-50"></i>
            <p class="fw-semibold text-dark mb-1">No invoices found.</p>
            <p class="small text-muted mb-0">Choose a client or create a new invoice to get started.</p>
        </div>
    </div>
    @else
    <div class="card border-0 shadow-sm overflow-hidden mb-3">
        <div class="table-responsive">
            <table class="table mainTable border align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 5%;"></th>
                        <th style="width: 15%;">Issue Date</th>
                        <th style="width: 20%;">Client</th>
                        <th style="width: 25%;">Invoice</th>
                        <th style="width: 10%;">Amount{{ $selectedClientCurrency ? ' (' . $selectedClientCurrency . ')'
                            : '' }}</th>
                        <th style="width: 10%;">Balance{{ $selectedClientCurrency ? ' (' . $selectedClientCurrency . ')'
                            : '' }}</th>
                        <th style="width: 10%;">Payment Status</th>
                        <th style="width: 5%;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="invoice-items-accordion">
                    @foreach ($allInvoices as $invoice)
                    @php
                    $documentId = $invoice->invoiceid;
                    $documentNumber =
                    $invoice->ti_number ?: $invoice->pi_number ?: $invoice->invoice_number;
                    $invoiceAmount = (float) ($invoice->grand_total ?? 0);
                    $amountPaid = (float) ($invoice->amount_paid ?? 0);
                    $balanceDue = (float) ($invoice->balance_due ?? max(0, $invoiceAmount - $amountPaid));
                    $paymentStatus = strtolower(trim((string) ($invoice->payment_status ?? '')));
                    if (!in_array($paymentStatus, ['paid', 'partly_paid', 'unpaid'], true)) {
                    $paymentStatus = 'unpaid';
                    if ($amountPaid > 0 && $balanceDue <= 0 && $invoiceAmount> 0) {
                        $paymentStatus = 'paid';
                        } elseif ($amountPaid > 0) {
                        $paymentStatus = 'partly_paid';
                        }
                        }
                        $clientName =
                        $invoice->client->business_name ??
                        ($invoice->client->contact_name ?? 'Client');
                        @endphp
                        <tr>
                            <td>
                                <button type="button" class="expand-order-btn" data-bs-toggle="collapse"
                                    data-bs-target="#invoice-items-{{ $documentId }}"
                                    data-bs-parent="#invoice-items-accordion" aria-expanded="false"
                                    aria-controls="invoice-items-{{ $documentId }}">
                                    <i class="fas fa-chevron-down expand-order-icon"></i>
                                </button>
                            </td>
                            <td>
                                {{ $invoice->issue_date?->format('d M Y') ?? '-' }}
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $clientName }}</div>
                            </td>
                            <td>
                                <div class="invoice-row-title">
                                    <div class="invoice-row-text">
                                        <strong>{{ $invoice->invoice_title ?: $invoice->invoice_number }}</strong>
                                        @if ($documentNumber)
                                        <div class="invoice-number-line mt-1">
                                            <span class="text-muted small me-2">{{ $documentNumber }}</span>
                                            <span
                                                class="app-badge app-badge--xs {{ !empty($invoice->ti_number) ? 'app-badge--gray' : 'app-badge--violet' }}">
                                                {{ !empty($invoice->ti_number) ? 'TI' : 'PI' }}
                                            </span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ number_format($invoiceAmount, 0) }}</span>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ number_format($balanceDue, 0) }}</span>
                            </td>
                            <td>
                                @if ($paymentStatus === 'paid')
                                <span class="status-pill active">Paid</span>
                                @elseif($paymentStatus === 'partly_paid')
                                <span class="status-pill review">Partly Paid</span>
                                @else
                                <span class="status-pill inactive">Unpaid</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="tableActionButton d-inline-flex gap-1">
                                    <a href="{{ route('invoices.show', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}"
                                        class="bg01 color01">View</a>

                                    @if (($invoice->status ?? '') === 'cancelled')
                                    <form method="POST"
                                        action="{{ route('invoices.restore', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}"
                                        class="d-inline" onsubmit="return confirm('Restore this invoice?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="bg02 color02">Restore</button>
                                    </form>
                                    @else
                                    <a href="{{ route('invoices.pdf', $invoice) }}"
                                        class="bg02 color02 text-decoration-none" target="_blank">PDF</a>
                                    <a href="{{ route('invoices.edit', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}"
                                        class="bg03 color03">Edit</a>
                                    <form method="POST"
                                        action="{{ route('invoices.destroy', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}"
                                        class="d-inline" onsubmit="return confirm('Cancel this invoice?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg04 color04">Cancel</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="8" class="p-0 border-0">
                                <div class="collapse" id="invoice-items-{{ $documentId }}"
                                    data-bs-parent="#invoice-items-accordion">
                                    <div
                                        class="card border-0 rounded-0 bg-light border-top border-primary border-3 shadow-none">
                                        <div class="card-body p-0">
                                            @if ($invoice->items->isNotEmpty())
                                            <div class="row row-cols-1 row-cols-md-3 g-0 mx-0 p-3">
                                                @foreach ($invoice->items as $item)
                                                @php
                                                $itemExpired = !empty($item->end_date) && $item->end_date < now();
                                                    @endphp <div class="col mb-3 px-2">
                                                    <div class="border rounded-3 bg-white p-3 h-100">
                                                        <div
                                                            class="d-flex align-items-start justify-content-between gap-3">
                                                            <div class="min-w-0">
                                                                <div class="fw-semibold text-dark">{{ $item->item_name
                                                                    ?? 'Item' }}</div>
                                                                @if (!empty($item->description))
                                                                <div class="text-muted small mt-1">
                                                                    {{ $item->description }}
                                                                </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div
                                                            class="d-flex flex-wrap gap-2 gap-md-3 mt-3 text-muted small">
                                                            <span><strong class="text-dark">Qty:</strong> {{
                                                                number_format((float) ($item->quantity ?? 1), 0)
                                                                }}</span>
                                                            <span><strong class="text-dark">Amount:</strong> {{
                                                                number_format((float) ($item->line_total ?? $item->total
                                                                ?? 0), 0) }}</span>
                                                            <span><strong class="text-dark">Freq:</strong> {{
                                                                !empty($item->frequency) ? ucfirst(str_replace('_', ' ',
                                                                $item->frequency)) : '-' }}</span>
                                                            <span><strong class="text-dark">Start:</strong> {{
                                                                !empty($item->start_date) ? $item->start_date->format('d
                                                                M Y') : '-' }}</span>
                                                            <span><strong class="text-dark">End:</strong>
                                                                @if (!empty($item->end_date))
                                                                <span
                                                                    class="{{ $itemExpired ? 'text-danger fw-semibold' : 'text-dark' }}">
                                                                    {{ $item->end_date->format('d M Y') }}
                                                                </span>
                                                                @else
                                                                -
                                                                @endif
                                                            </span>
                                                        </div>
                                                    </div>
                                            </div>
                                            @endforeach
                                        </div>
                                        @else
                                        <div class="alert alert-light border rounded-0 mb-0" role="alert">
                                            No items in this invoice
                                        </div>
                                        @endif
                                    </div>
                                </div>
        </div>
        </td>
        </tr>
        @endforeach
        </tbody>
        </table>
    </div>
</div>
@endif
</div>

@endsection
