@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('invoices.expiry-list') }}" class="secondary-button">
        <i class="fas fa-hourglass-half icon-spaced"></i>Expiry List
    </a>
    @if($selectedClientId)
        <a href="{{ route('invoices.create', ['c' => $selectedClientId]) }}" class="primary-button">
            <i class="fas fa-file-invoice icon-spaced"></i>Create Invoice
        </a>
    @endif
@endsection

@section('content')
    @php
        $selectedInvoices = $selectedClientId ? $groupedInvoices->flatten(1) : collect();
        $selectedClient = $clients->firstWhere('clientid', $selectedClientId);
        $selectedClientCurrency = $selectedClient->currency ?? 'INR';
        $selectedClientLabel = $selectedClient
            ? ($selectedClient->business_name ?? $selectedClient->contact_name ?? 'Selected Client')
            : 'Selected Client';
    @endphp

    <div class="invoice-index-shell">
        @if(!$selectedClientId)
            <div class="invoice-client-picker-wrap">
                <div class="invoice-client-picker">
                    <div class="invoice-client-picker-head">
                        <div class="invoice-client-picker-title">
                            <div class="invoice-client-picker-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div>
                                <strong>Manage Invoices</strong>
                                <p>Choose a client first to load that client’s invoices and actions in a focused view.</p>
                            </div>
                        </div>
                        <span class="invoice-client-count">{{ $clients->count() }} client(s)</span>
                    </div>
                    <form action="{{ route('invoices.index') }}" method="GET">
                        <div class="invoice-client-picker-field">
                            <label for="invoice-client-filter">Client</label>
                            <select name="c" id="invoice-client-filter" class="form-control" autofocus>
                                <option value="" selected disabled>Select a client</option>
                                @foreach($clients as $clientOption)
                                    <option value="{{ $clientOption->clientid }}">
                                        {{ $clientOption->business_name ?? $clientOption->contact_name ?? 'Client' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="invoice-client-picker-actions">
                            <button type="button" id="btnViewInvoices" class="secondary-button action-btn-lg">
                                <i class="fas fa-list icon-spaced"></i> View Invoices
                            </button>
                            <button type="button" id="btnCreateInvoice" class="primary-button action-btn-lg">
                                <i class="fas fa-file-invoice icon-spaced"></i> Create Invoice
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @elseif($selectedInvoices->isEmpty())
            <section class="invoice-group">
                <div class="invoice-group-head">
                    <span class="invoice-client-meta">
                        <form action="{{ route('invoices.index') }}" method="GET" class="m-0">
                            <select
                                name="c"
                                class="form-control select-client-compact"
                                onchange="this.form.submit()"
                            >
                                @foreach($clients as $clientOption)
                                    <option value="{{ $clientOption->clientid }}" {{ (string) $selectedClientId === (string) $clientOption->clientid ? 'selected' : '' }}>
                                        {{ $clientOption->business_name ?? $clientOption->contact_name ?? 'Client' }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </span>
                    <span class="invoice-client-summary">
                        <span class="service-count">0 invoice(s)</span>
                    </span>
                </div>
                <div class="invoice-empty">
                    <i class="fas fa-file-invoice empty-state-icon"></i>
                    <p class="no-empty-state-text">No invoices found for {{ $selectedClientLabel }}</p>
                    <p class="small-text">Create your first invoice to get started.</p>
                </div>
            </section>
        @else
            <section class="invoice-group">
                <div class="invoice-group-head">
                    <span class="invoice-client-meta">
                        <form action="{{ route('invoices.index') }}" method="GET" class="m-0">
                            <select
                                name="c"
                                class="form-control select-client-compact"
                                onchange="this.form.submit()"
                            >
                                @foreach($clients as $clientOption)
                                    <option value="{{ $clientOption->clientid }}" {{ (string) $selectedClientId === (string) $clientOption->clientid ? 'selected' : '' }}>
                                        {{ $clientOption->business_name ?? $clientOption->contact_name ?? 'Client' }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </span>
                    <span class="invoice-client-summary">
                        <span class="service-count">{{ $selectedInvoices->count() }} invoice(s)</span>
                    </span>
                </div>

                <div class="invoice-table-wrap">
                    <table class="data-table table-no-margin">
                        <thead>
                            <tr>
                                <th class="w-5"></th>
                                <th class="w-30">Invoice</th>
                                <th class="w-10">Type</th>
                                <th class="w-12">For</th>
                                <th class="w-12">Amount ({{ $selectedClientCurrency }})</th>
                                <th class="w-12">End Date</th>
                                <th class="w-10">Status</th>
                                <th class="w-14">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($selectedInvoices as $invoice)
                                @php
                                    $documentId = $invoice->invoiceid;
                                    $latestEndDate = $invoice->items->max('end_date');
                                    $isExpired = $latestEndDate && $latestEndDate < now();
                                    $documentType = !empty($invoice->ti_number) ? 'Tax Invoice' : 'Proforma Invoice';
                                    $invoiceAmount = (float) $invoice->items->sum('line_total');
                                @endphp
                                <tr>
                                    <td>
                                        <button type="button" class="expand-order-btn" onclick="toggleInvoiceItems('{{ $documentId }}')">
                                            <i class="fas fa-chevron-right expand-order-icon" id="invoice-icon-{{ $documentId }}"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <div class="invoice-row-title">
                                            <div class="invoice-row-icon">
                                                <i class="fas fa-file-invoice"></i>
                                            </div>
                                            <div class="invoice-row-text">
                                                <strong>{{ $invoice->invoice_title ?: $invoice->invoice_number }}</strong>
                                                @if($invoice->invoice_title)
                                                    <span>{{ $invoice->invoice_number }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="invoice-type-badge {{ strtolower($documentType) }}">
                                            {{ $documentType }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="invoice-muted">{{ ucfirst(str_replace('_', ' ', $invoice->invoice_for ?? 'without orders')) }}</span>
                                    </td>
                                    <td>
                                        <strong class="invoice-amount">{{ number_format($invoiceAmount, 0) }}</strong>
                                    </td>
                                    <td>
                                        @if($latestEndDate)
                                            <span class="invoice-end-date {{ $isExpired ? 'is-expired' : '' }}">
                                                {{ $latestEndDate->format('d M Y') }}
                                                @if($isExpired)
                                                    <span class="invoice-expired-badge">expired</span>
                                                @endif
                                            </span>
                                        @else
                                            <span class="invoice-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(($invoice->status ?? '') === 'cancelled')
                                            <span class="status-pill status-pill-cancelled">Cancelled</span>
                                        @else
                                            <span class="status-pill status-pill-running">Active</span>
                                        @endif
                                    </td>
                                    <td class="table-actions">
                                        <a href="{{ route('invoices.show', [$documentId, 'c' => $selectedClientId]) }}" class="icon-action-btn view" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <a href="{{ route('invoices.pdf', $invoice) }}" class="icon-action-btn pdf" title="Download PDF" target="_blank">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>

                                        <a href="{{ route('invoices.edit', [
                                            'invoice' => $documentId,
                                            'c' => $selectedClientId ?: $invoice->clientid,
                                        ]) }}" class="icon-action-btn edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        @if(($invoice->status ?? '') !== 'cancelled')
                                        <form method="POST" action="{{ route('invoices.destroy', [$documentId, 'c' => $selectedClientId]) }}" class="inline-delete" onsubmit="return confirm('Cancel this invoice?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="icon-action-btn delete" title="Cancel Invoice">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="order-items-row" id="invoice-items-{{ $documentId }}" style="display: none;">
                                    <td colspan="8" class="order-items-cell">
                                        <div class="order-items-inner">
                                            <div class="order-items-head">
                                                <i class="fas fa-box-open order-items-head-icon"></i>
                                                <strong class="order-items-head-title">Invoice Items</strong>
                                            </div>
                                            <div class="order-items-content">
                                                @if($invoice->items->isNotEmpty())
                                                    <div class="order-items-grid">
                                                        @foreach($invoice->items as $item)
                                                            <div class="order-item-card">
                                                                <div class="order-item-card-row">
                                                                    <div>
                                                                        <strong class="order-item-name">{{ $item->item_name ?? 'Item' }}</strong>
                                                                        <div class="order-item-meta">
                                                                            Qty: {{ number_format((float) ($item->quantity ?? 1), 0) }}
                                                                            @if(!empty($item->frequency))
                                                                                | Freq: {{ ucfirst(str_replace('_', ' ', $item->frequency)) }}
                                                                            @endif
                                                                            @if(!empty($item->start_date))
                                                                                | Start: {{ $item->start_date->format('d M Y') }}
                                                                            @endif
                                                                            @if(!empty($item->end_date))
                                                                                | End: {{ $item->end_date->format('d M Y') }}
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <em class="text-muted-light">No items in this invoice</em>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .swal-compact-popup {
            padding: 0.9rem 1rem 0.8rem !important;
            border-radius: 12px !important;
        }

        .swal2-title.swal-compact-title {
            font-size: 1rem !important;
            margin: 0 0 0.35rem !important;
        }

        .swal2-html-container.swal-compact-text {
            font-size: 0.88rem !important;
            margin: 0 !important;
        }

        .swal2-actions.swal-compact-actions {
            margin-top: 0.75rem !important;
            gap: 0.45rem !important;
        }

        .swal2-confirm.swal-compact-btn,
        .swal2-cancel.swal-compact-btn {
            font-size: 0.82rem !important;
            padding: 0.38rem 0.8rem !important;
            border-radius: 8px !important;
        }
    </style>
    <script>
    function toggleInvoiceItems(invoiceId) {
        const itemsRow = document.getElementById('invoice-items-' + invoiceId);
        const icon = document.getElementById('invoice-icon-' + invoiceId);
        if (!itemsRow || !icon) return;

        if (itemsRow.style.display === 'none') {
            itemsRow.style.display = 'table-row';
            icon.style.transform = 'rotate(90deg)';
        } else {
            itemsRow.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
        }
    }

    document.querySelectorAll('.js-item-reminder-confirm').forEach((form) => {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const itemLabel = this.dataset.itemLabel || 'this item';

            if (typeof Swal === 'undefined') {
                this.submit();
                return;
            }

            Swal.fire({
                title: 'Send reminder?',
                text: `Reminder will be sent for ${itemLabel}.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Send',
                cancelButtonText: 'Cancel',
                width: 310,
                customClass: {
                    popup: 'swal-compact-popup',
                    title: 'swal-compact-title',
                    htmlContainer: 'swal-compact-text',
                    actions: 'swal-compact-actions',
                    confirmButton: 'swal-compact-btn',
                    cancelButton: 'swal-compact-btn',
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });
    });

    document.getElementById('btnViewInvoices')?.addEventListener('click', function() {
        const clientId = document.getElementById('invoice-client-filter')?.value;
        if (clientId) {
            window.location.href = "{{ route('invoices.index') }}?c=" + encodeURIComponent(clientId);
        } else {
            alert('Please select a client first.');
        }
    });

    document.getElementById('btnCreateInvoice')?.addEventListener('click', function() {
        const clientId = document.getElementById('invoice-client-filter')?.value;
        if (clientId) {
            window.location.href = "{{ route('invoices.create') }}?c=" + encodeURIComponent(clientId);
        } else {
            alert('Please select a client first.');
        }
    });
    </script>

@endsection
