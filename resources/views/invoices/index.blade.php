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
$currentTab = in_array($selectedTab ?? 'invoices', ['invoices', 'outstanding', 'draft', 'cancelled'], true)
? $selectedTab
: 'invoices';
@endphp

<div class="position-relative bg-white p-2 rounded-3">
    <!-- Filters Card -->
    <div class="position-relative bg-light p-2 rounded-3 mb-2">
        <form action="{{ route('invoices.index') }}" method="GET" class="mainForm">
            <input type="hidden" name="tab" value="{{ $selectedTab ?? 'invoices' }}">
            <input type="hidden" name="type" value="{{ $selectedType ?? '' }}">

            <div class="row g-2">
                <div class="col-12 col-md-2">
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
                        <i class="fas fa-sync-alt btn-icon me-1"></i> Clear
                    </a>
                    <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                        <i class="fas fa-filter btn-icon me-1"></i> Filter
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

    @if ($currentTab === 'draft')
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
                        <th style="width: 10%;">Issue Date</th>
                        <th style="width: 10%;">Due Date</th>
                        <th style="width: 15%;">Client</th>
                        <th style="width: 20%;">Invoice</th>
                        <th style="width: 15%;">Amount / Balance{{ $selectedClientCurrency ? ' (' .
                            $selectedClientCurrency . ')'
                            : '' }}</th>
                        <th style="width: 10%;">Payment Status</th>
                        <th style="width: 10%;" class="text-end">Actions</th>
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
                                <div class="text-muted small">Due: {{ number_format($balanceDue, 0) }}</div>
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
                                    @if (($invoice->status ?? '') === 'cancelled')
                                    <form method="POST"
                                        action="{{ route('invoices.restore', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}"
                                        class="d-inline" onsubmit="return confirm('Restore this invoice?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="bg02 color02">Restore</button>
                                    </form>
                                    @elseif (strtolower($invoice->status ?? '') === 'draft')
                                    <a href="{{ route('invoices.edit', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}"
                                        class="bg02 color02">Continue</a>
                                    <form method="POST"
                                        action="{{ route('invoices.destroy', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}"
                                        class="d-inline" onsubmit="return confirm('Delete this draft?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg04 color04">Delete</button>
                                    </form>
                                    @else
                                    <button type="button" class="bg01 color01 border-0 view-pdf-btn"
                                        data-pdf-url="{{ route('invoices.pdf', $invoice) }}">
                                        View
                                    </button>
                                    <a href="{{ route('invoices.email-compose', $invoice->invoiceid) }}"
                                        class="bg03 color03">Send</a>
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
                        @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-white border-bottom py-2">
                <h5 class="modal-title fw-semibold">Invoice PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 85vh;">
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
    });
</script>
@endpush

@endsection
