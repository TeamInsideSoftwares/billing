@extends('layouts.app')

@section('header_actions')
<div class="d-flex align-items-center gap-2 flex-wrap">
    <a href="{{ route('invoices.expiry-list', $selectedClientId ? ['c' => $selectedClientId] : []) }}"
        class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-calendar-times btn-icon"></i> Expiry List
    </a>

    @if(auth()->user()->hasPermission('invoices.create'))
    <a href="{{ route('invoices.create', $selectedClientId ? ['c' => $selectedClientId] : []) }}"
        class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
        Create Invoice <i class="fas fa-arrow-right btn-icon"></i>
    </a>
    @endif
</div>
@endsection

@section('content')
@php
$selectedClient = $clients->firstWhere('clientid', $selectedClientId);
$selectedClientCurrency = $selectedClient->currency ?? null;
$currentTab = in_array($selectedTab ?? 'invoices', ['invoices', 'outstanding', 'draft', 'cancelled', 'paid'], true)
? $selectedTab
: 'invoices';
@endphp

<div class="position-relative bg-white p-2 rounded-3">
    <!-- Filters Card -->
    <div class="position-relative bg-DarkLight p-2 rounded-3 mb-2">
        <form action="{{ route('invoices.index') }}" method="GET" class="mainForm">
            <input type="hidden" name="tab" value="{{ $selectedTab ?? 'invoices' }}">
            <input type="hidden" name="type" value="{{ $selectedType ?? '' }}">

            <div class="row g-2">
                <div class="col-12 col-md-2">
                    <select name="c" id="invoice_client_filter" class="form-select" onchange="this.form.submit()">
                        <option value="">All Clients</option>
                        @foreach ($clients as $clientOption)
                        <option value="{{ $clientOption->clientid }}" {{ (string) $selectedClientId===(string)
                            $clientOption->clientid ? 'selected' : '' }}>
                            {{ $clientOption->business_name ?? ($clientOption->contact_name ?? 'Client') }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 px-1">
        <div class="btn-group" role="group" aria-label="Invoice Tabs">
            <a class="btn btn-md px-3 border-top-0 border-start-0 border-end-0 {{ $currentTab === 'invoices' ? 'rounded-top text-primary bg-primary-subtle border-primary border-bottom border-2 fw-bold' : 'rounded-0 text-primary bg-transparent border-bottom border-2 border-transparent' }} d-inline-flex align-items-center gap-2 fw-medium"
                href="{{ route('invoices.index', array_filter(['tab' => 'invoices', 'c' => $selectedClientId, 'type' => $selectedType ?? ''])) }}">
                All <span
                    class="badge rounded-pill {{ $currentTab === 'invoices' ? 'bg-primary text-white' : 'bg-primary-subtle text-primary' }}">{{
                    $allInvoicesCount ?? 0 }}</span>
            </a>
            <a class="btn btn-md px-3 border-top-0 border-start-0 border-end-0 {{ $currentTab === 'paid' ? 'rounded-top text-primary bg-primary-subtle border-primary border-bottom border-2 fw-bold' : 'rounded-0 text-primary bg-transparent border-bottom border-2 border-transparent' }} d-inline-flex align-items-center gap-2 fw-medium"
                href="{{ route('invoices.index', array_filter(['tab' => 'paid', 'c' => $selectedClientId, 'type' => $selectedType ?? ''])) }}">
                Paid <span
                    class="badge rounded-pill {{ $currentTab === 'paid' ? 'bg-primary text-white' : 'bg-primary-subtle text-primary' }}">{{
                    $paidInvoicesCount ?? 0 }}</span>
            </a>
            <a class="btn btn-md px-3 border-top-0 border-start-0 border-end-0 {{ $currentTab === 'outstanding' ? 'rounded-top text-primary bg-primary-subtle border-primary border-bottom border-2 fw-bold' : 'rounded-0 text-primary bg-transparent border-bottom border-2 border-transparent' }} d-inline-flex align-items-center gap-2 fw-medium"
                href="{{ route('invoices.index', array_filter(['tab' => 'outstanding', 'c' => $selectedClientId, 'type' => $selectedType ?? ''])) }}">
                Outstanding <span
                    class="badge rounded-pill {{ $currentTab === 'outstanding' ? 'bg-primary text-white' : 'bg-primary-subtle text-primary' }}">{{
                    $outstandingInvoicesCount ?? 0 }}</span>
            </a>
            <a class="btn btn-md px-3 border-top-0 border-start-0 border-end-0 {{ $currentTab === 'draft' ? 'rounded-top text-primary bg-primary-subtle border-primary border-bottom border-2 fw-bold' : 'rounded-0 text-primary bg-transparent border-bottom border-2 border-transparent' }} d-inline-flex align-items-center gap-2 fw-medium"
                href="{{ route('invoices.index', array_filter(['tab' => 'draft', 'c' => $selectedClientId, 'type' => $selectedType ?? ''])) }}">
                Draft <span
                    class="badge rounded-pill {{ $currentTab === 'draft' ? 'bg-primary text-white' : 'bg-primary-subtle text-primary' }}">{{
                    $draftInvoicesCount ?? 0 }}</span>
            </a>
            <a class="btn btn-md px-3 border-top-0 border-start-0 border-end-0 {{ $currentTab === 'cancelled' ? 'rounded-top text-primary bg-primary-subtle border-primary border-bottom border-2 fw-bold' : 'rounded-0 text-primary bg-transparent border-bottom border-2 border-transparent' }} d-inline-flex align-items-center gap-2 fw-medium"
                href="{{ route('invoices.index', array_filter(['tab' => 'cancelled', 'c' => $selectedClientId, 'type' => $selectedType ?? ''])) }}">
                Cancelled <span
                    class="badge rounded-pill {{ $currentTab === 'cancelled' ? 'bg-primary text-white' : 'bg-primary-subtle text-primary' }}">{{
                    $cancelledInvoicesCount ?? 0 }}</span>
            </a>
        </div>

        @if (!$allInvoices->isEmpty())
        <div class="btn-group shadow-sm" role="group" aria-label="View Toggle">
            <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                id="btn-grid-view">
                <i class="fas fa-th-large toggle-icon"></i> Grid
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                id="btn-list-view">
                <i class="fas fa-list toggle-icon"></i> List
            </button>
        </div>
        @endif
    </div>

    @if ($currentTab === 'draft')
    <div class="invoice-list-meta ps-2 mb-2">
        <div class="meta-info">
            <strong class="fw-bold fs-5 lh-sm">Draft invoices</strong>
        </div>
    </div>
    @elseif ($currentTab === 'paid')
    <div class="invoice-list-meta ps-2 mb-2">
        <div class="meta-info">
            <strong class="fw-bold fs-5 lh-sm">Paid invoices</strong>
        </div>
    </div>
    @elseif ($currentTab === 'outstanding')
    <div class="invoice-list-meta ps-2 mb-2">
        <div class="meta-info">
            <strong class="fw-bold fs-5 lh-sm">Outstanding invoices</strong>
        </div>
    </div>
    @elseif ($currentTab === 'cancelled')
    <div class="invoice-list-meta ps-2 mb-2">
        <div class="meta-info">
            <strong class="fw-bold fs-5 lh-sm">Cancelled invoices</strong>
        </div>
    </div>
    @endif

    @if ($allInvoices->isEmpty())
    <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3 mb-3">
        <div class="card-body bg-white rounded-3 py-5 text-center text-muted">
            <i class="fas fa-file-invoice mb-3 text-secondary fs-1 opacity-50"></i>
            <p class="fw-semibold text-dark mb-1">No invoices found.</p>
            <p class="small text-muted mb-0">Choose a client or create a new invoice to get started.</p>
        </div>
    </div>
    @else
    <div id="invoices-list-view" class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3 mb-3">
        <div class="table-responsive">
            <table class="table table-striped mainTable align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 10%;">Issue Date</th>
                        <th style="width: 10%;">Due Date</th>
                        <th style="width: 25%;">Client</th>
                        <th style="width: 15%;">Invoice</th>
                        <th style="width: 10%;" class="text-end">Invoice Amount{{
                            $selectedClientCurrency ? ' (' .
                            $selectedClientCurrency . ')'
                            : '' }}</th>
                        <th style="width: 10%;" class="text-end">Balance{{
                            $selectedClientCurrency ? ' (' .
                            $selectedClientCurrency . ')'
                            : '' }}</th>
                        <th style="width: 20%;" class="text-end">Actions</th>
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
                                {{ $invoice->issue_date?->format('d M Y') ?? '-' }}
                            </td>
                            <td>
                                {{ $invoice->due_date?->format('d M Y') ?? '-' }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div
                                        class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold">
                                        <span class="d-block position-absolute">{{ strtoupper(substr($clientName, 0, 2))
                                            }}</span>
                                    </div>
                                    <div>
                                        <span class="fw-semibold text-dark d-block mb-0">{{ $clientName }}</span>
                                        @if($invoice->client)
                                        <span class="d-block text-dark small lh-sm">{{ $invoice->client->primary_email ??
                                            $invoice->client->email }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="invoice-row-title">
                                    <div class="invoice-row-text">
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                            <strong class="text-dark">{{ $invoice->invoice_title ?: $invoice->invoice_number }}</strong>
                                            @if ($paymentStatus === 'paid')
                                            <span
                                                class="status-pill d-inline-block paid py-0.5 px-2 rounded-pill bg-success-subtle text-success fw-semibold"
                                                style="font-size: 11px;line-height:18px;">Paid</span>
                                            @elseif($paymentStatus === 'partly_paid')
                                            <span
                                                class="status-pill d-inline-block partial bg-primary-subtle text-primary fw-semibold rounded-pill py-0.5 px-2"
                                                style="font-size: 11px;line-height:18px;">Partly Paid</span>
                                            @else
                                            <span
                                                class="status-pill d-inline-block overdue bg-danger-subtle text-danger fw-semibold rounded-pill py-0.5 px-2"
                                                style="font-size: 11px;line-height:18px;">Unpaid</span>
                                            @endif
                                        </div>
                                        @if ($documentNumber)
                                        <div class="invoice-number-line mt-1 d-flex align-items-center gap-2">
                                            @if (!empty($invoice->ti_number))
                                            <span
                                                class="status-pill d-inline-block paid py-0.5 px-2 rounded-pill bg-success-subtle text-success fw-semibold"
                                                style="font-size: 11px;line-height:18px;">TI</span>
                                            @else
                                            <span
                                                class="status-pill d-inline-block partial bg-primary-subtle text-primary fw-semibold rounded-pill py-0.5 px-2"
                                                style="font-size: 11px;line-height:18px;">PI</span>
                                            @endif
                                            <span class="text-dark small">{{ $documentNumber }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-end text-dark">
                                <span>{{ number_format($invoiceAmount, 0) }}</span>
                                @if($invoice->client)
                                <span class="currency-code-small d-block text-muted">{{ $invoice->client->currency
                                    }}</span>
                                @endif
                            </td>
                            <td class="text-end text-dark">
                                <span class="text-danger fs-6 lh-sm fw-semibold">{{ number_format($balanceDue, 0)
                                    }}</span>
                                @if($invoice->client)
                                <span class="currency-code-small d-block text-muted">{{ $invoice->client->currency
                                    }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="tableActionButton d-inline-flex gap-1">
                                    @if (($invoice->status ?? '') === 'cancelled')
                                    @if(auth()->user()->hasPermission('invoices.cancel'))
                                    <form method="POST"
                                        action="{{ route('invoices.restore', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}"
                                        class="d-inline" onsubmit="return confirm('Restore this invoice?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="bg02 color02">Restore</button>
                                    </form>
                                    @endif
                                    @elseif (strtolower($invoice->status ?? '') === 'draft')
                                    @if(auth()->user()->hasPermission('invoices.edit'))
                                    <a href="{{ route('invoices.edit', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}"
                                        class="bg02 color02">Continue</a>
                                    @endif
                                    @if(auth()->user()->hasPermission('invoices.cancel'))
                                    <form method="POST"
                                        action="{{ route('invoices.destroy', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}"
                                        class="d-inline" onsubmit="return confirm('Delete this draft?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg04 color04">Delete</button>
                                    </form>
                                    @endif
                                    @else
                                    @if(auth()->user()->hasPermission('invoices.view'))
                                    <button type="button" class="bg01 color01 border-0 view-pdf-btn"
                                        data-pdf-url="{{ route('invoices.pdf', $invoice) }}">
                                        View
                                    </button>
                                    <a href="{{ route('invoices.email-compose', $invoice->invoiceid) }}"
                                        class="bg03 color03">Send</a>
                                    @endif
                                    @if(auth()->user()->hasPermission('invoices.edit'))
                                    <a href="{{ route('invoices.edit', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}"
                                        class="bg03 color03">Edit</a>
                                    @endif
                                    @if(auth()->user()->hasPermission('invoices.cancel'))
                                    <form method="POST"
                                        action="{{ route('invoices.destroy', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}"
                                        class="d-inline" onsubmit="return confirm('Cancel this invoice?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg04 color04">Cancel</button>
                                    </form>
                                    @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Invoices Grid View -->
    <div id="invoices-grid-view"
        class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-2 p-1 pb-3 mt-2 bg-DarkLight rounded-3 d-none">
        @foreach ($allInvoices as $invoice)
        @php
        $documentId = $invoice->invoiceid;
        $documentNumber = $invoice->ti_number ?: $invoice->pi_number ?: $invoice->invoice_number;
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
            $clientName = $invoice->client->business_name ?? ($invoice->client->contact_name ?? 'Client');
            @endphp
            <div class="col">
                <div class="card h-100 border-0 overflow-hidden">
                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                        <div>
                            <!-- Header with Dates -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="text-muted small" style="font-size: 13px;">Issue: <span
                                        class="text-dark fw-semibold">{{ $invoice->issue_date?->format('d M Y') ?? '-'
                                        }}</span></div>
                                <div class="text-dark small" style="font-size: 13px;">Due: <span
                                        class="text-dark fw-semibold">{{ $invoice->due_date?->format('d M Y') ?? '-'
                                        }}</span></div>
                            </div>

                            <!-- Client details -->
                            <div class="d-flex align-items-center gap-2 mb-3 pb-3 border-bottom">
                                <div
                                    class="tablePrifix position-relative align-self-center bg-primary-subtle text-primary rounded-circle fw-semibold flex-shrink-0">
                                    <span class="d-block position-absolute">{{ strtoupper(substr($clientName, 0, 2))
                                        }}</span>
                                </div>
                                <div class="flex-grow-1 min-w-0 ps-2">
                                    <h6 class="fw-semibold text-dark mb-1 text-truncate lh-sm"
                                        title="{{ $clientName }}">
                                        {{ $clientName }}
                                    </h6>
                                    @if($invoice->client)
                                    <span class="d-block text-dark lh-sm text-break grid-text-medium"
                                        title="{{ $invoice->client->primary_email ?? $invoice->client->email }}">{{
                                        $invoice->client->primary_email ?? $invoice->client->email }}</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Invoice Info -->
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <strong class="text-dark text-truncate lh-sm"
                                        title="{{ $invoice->invoice_title ?: $invoice->invoice_number }}">
                                        {{ $invoice->invoice_title ?: $invoice->invoice_number }}
                                    </strong>
                                    @if ($paymentStatus === 'paid')
                                    <span
                                        class="status-pill d-inline-block paid py-0.5 px-2 rounded-pill bg-success-subtle text-success fw-semibold"
                                        style="font-size: 11px;line-height:18px;">Paid</span>
                                    @elseif($paymentStatus === 'partly_paid')
                                    <span
                                        class="status-pill d-inline-block partial bg-primary-subtle text-primary fw-semibold rounded-pill py-0.5 px-2"
                                        style="font-size: 11px;line-height:18px;">Partly Paid</span>
                                    @else
                                    <span
                                        class="status-pill d-inline-block overdue bg-danger-subtle text-danger fw-semibold rounded-pill py-0.5 px-2"
                                        style="font-size: 11px;line-height:18px;">Unpaid</span>
                                    @endif
                                </div>
                                @if ($documentNumber)
                                <div class="invoice-number-line mt-1.5 d-flex align-items-center gap-2">
                                    @if (!empty($invoice->ti_number))
                                    <span
                                        class="status-pill d-inline-block paid py-0.5 px-2 rounded-pill bg-success-subtle text-success fw-semibold"
                                        style="font-size: 11px;line-height:18px;">TI</span>
                                    @else
                                    <span
                                        class="status-pill d-inline-block partial bg-primary-subtle text-primary fw-semibold rounded-pill py-0.5 px-2"
                                        style="font-size: 11px;line-height:18px;">PI</span>
                                    @endif
                                    <span class="text-dark small">{{ $documentNumber }}</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Amount & Balance Area -->
                        <div
                            class="bg-light rounded-3 px-3 py-2 mt-auto d-flex justify-content-between align-items-center mb-2 text-dark">
                            <span class="text-muted small fw-medium">Balance Amt</span>
                            <div class="text-end">
                                <span class="text-dark small">{{ number_format($invoiceAmount, 0) }} - {{
                                    number_format($amountPaid, 0) }}</span>
                                <div class="mt-0.5">
                                    = <span class="text-danger fs-6 lh-sm fw-semibold">{{ number_format($balanceDue, 0)
                                        }}</span>
                                    @if($invoice->client)
                                    <span class="currency-code-small text-muted">{{ $invoice->client->currency }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="tableActionButton d-flex flex-wrap gap-1 mt-2">
                            @if (($invoice->status ?? '') === 'cancelled')
                            @if(auth()->user()->hasPermission('invoices.cancel'))
                            <form method="POST"
                                action="{{ route('invoices.restore', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}"
                                class="d-inline flex-grow-1" onsubmit="return confirm('Restore this invoice?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="bg02 color02 w-100 text-center">Restore</button>
                            </form>
                            @endif
                            @elseif (strtolower($invoice->status ?? '') === 'draft')
                            @if(auth()->user()->hasPermission('invoices.edit'))
                            <a href="{{ route('invoices.edit', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}"
                                class="bg02 color02 flex-grow-1 text-center">Continue</a>
                            @endif
                            @if(auth()->user()->hasPermission('invoices.cancel'))
                            <form method="POST"
                                action="{{ route('invoices.destroy', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}"
                                class="d-inline flex-grow-1" onsubmit="return confirm('Delete this draft?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg04 color04 w-100 text-center">Delete</button>
                            </form>
                            @endif
                            @else
                            @if(auth()->user()->hasPermission('invoices.view'))
                            <button type="button" class="bg01 color01 border-0 view-pdf-btn flex-grow-1 text-center"
                                data-pdf-url="{{ route('invoices.pdf', $invoice) }}">
                                View
                            </button>
                            <a href="{{ route('invoices.email-compose', $invoice->invoiceid) }}"
                                class="bg03 color03 flex-grow-1 text-center">Send</a>
                            @endif
                            @if(auth()->user()->hasPermission('invoices.edit'))
                            <a href="{{ route('invoices.edit', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}"
                                class="bg03 color03 flex-grow-1 text-center">Edit</a>
                            @endif
                            @if(auth()->user()->hasPermission('invoices.cancel'))
                            <form method="POST"
                                action="{{ route('invoices.destroy', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}"
                                class="d-inline flex-grow-1" onsubmit="return confirm('Cancel this invoice?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg04 color04 w-100 text-center">Cancel</button>
                            </form>
                            @endif
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
                <h5 class="modal-title fw-semibold">Invoice PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2" style="height: 80vh;">
                <iframe id="pdfViewerFrame" src="" style="width: 100%; height: 100%; border: 0;"></iframe>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = new bootstrap.Modal(document.getElementById('pdfViewerModal'));
        const iframe = document.getElementById('pdfViewerFrame');

        document.querySelectorAll('.view-pdf-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                iframe.src = this.dataset.pdfUrl;
                modal.show();
            });
        });

        document.getElementById('pdfViewerModal').addEventListener('hidden.bs.modal', function () {
            iframe.src = '';
        });

        // View Toggle Logic
        const btnList = document.getElementById('btn-list-view');
        const btnGrid = document.getElementById('btn-grid-view');
        const listView = document.getElementById('invoices-list-view');
        const gridView = document.getElementById('invoices-grid-view');

        function setView(viewType) {
            if (viewType === 'grid') {
                if (listView) listView.classList.add('d-none');
                if (gridView) gridView.classList.remove('d-none');
                if (btnList) {
                    btnList.classList.remove('active', 'btn-primary');
                    btnList.classList.add('btn-outline-primary');
                }
                if (btnGrid) {
                    btnGrid.classList.add('active', 'btn-primary');
                    btnGrid.classList.remove('btn-outline-primary');
                }
                localStorage.setItem('invoices_view_preference', 'grid');
            } else {
                if (listView) listView.classList.remove('d-none');
                if (gridView) gridView.classList.add('d-none');
                if (btnList) {
                    btnList.classList.add('active', 'btn-primary');
                    btnList.classList.remove('btn-outline-primary');
                }
                if (btnGrid) {
                    btnGrid.classList.remove('active', 'btn-primary');
                    btnGrid.classList.add('btn-outline-primary');
                }
                localStorage.setItem('invoices_view_preference', 'list');
            }
        }

        if (btnList && btnGrid && listView && gridView) {
            btnList.addEventListener('click', () => setView('list'));
            btnGrid.addEventListener('click', () => setView('grid'));

            const savedPref = localStorage.getItem('invoices_view_preference');
            if (savedPref === 'grid') {
                setView('grid');
            } else {
                setView('list');
            }
        }
    });
</script>
@endpush

@endsection
