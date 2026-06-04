@extends('layouts.app')

@section('header_actions')
    <div class="header-actions-wrapper">
        <a href="{{ route('invoices.expiry-list', $selectedClientId ? ['c' => $selectedClientId] : []) }}"
            class="secondary-button">
            <i class="fas fa-calendar-times icon-spaced"></i>Expiry List
        </a>

        <a href="{{ route('invoices.create', $selectedClientId ? ['c' => $selectedClientId] : []) }}"
            class="primary-button">
            <i class="fas fa-plus icon-spaced"></i>Create Invoice
        </a>
    </div>
@endsection

@section('content')
    @php
        $selectedClient = $clients->firstWhere('clientid', $selectedClientId);
        $selectedClientCurrency = $selectedClient->currency ?? null;
        $currentTab = in_array($selectedTab ?? 'invoices', ['invoices', 'outstanding', 'upcoming', 'cancelled'], true)
            ? $selectedTab
            : 'invoices';
    @endphp

    <div class="invoice-index-shell">
        <section class="panel-card module-filter-panel filter-panel-regular">
            <form action="{{ route('invoices.index') }}" method="GET" class="module-filter-grid">
                <input type="hidden" name="tab" value="{{ $selectedTab ?? 'invoices' }}">
                <input type="hidden" name="type" value="{{ $selectedType ?? '' }}">

                <div class="module-filter-field">
                    <label class="module-filter-label" for="invoice_client_filter">Client</label>
                    <select name="c" id="invoice_client_filter" class="form-control">
                        <option value="">All Clients</option>
                        @foreach ($clients as $clientOption)
                            <option value="{{ $clientOption->clientid }}"
                                {{ (string) $selectedClientId === (string) $clientOption->clientid ? 'selected' : '' }}>
                                {{ $clientOption->business_name ?? ($clientOption->contact_name ?? 'Client') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="module-filter-actions">
                    <button type="submit" class="primary-button">Apply</button>
                    <a href="{{ route('invoices.index', array_filter([
                        'tab' => $selectedTab ?? 'invoices',
                        'type' => $selectedType ?? '',
                    ])) }}"
                        class="secondary-button">Reset</a>
                </div>
            </form>
        </section>

        <div class="invoice-tabs-container">
            <div class="invoice-tabs">
                <a href="{{ route('invoices.index', array_filter(['tab' => 'invoices', 'c' => $selectedClientId, 'type' => $selectedType ?? ''])) }}"
                    class="invoice-tab {{ $currentTab === 'invoices' ? 'is-active' : '' }}">
                    All <span>{{ $paidInvoicesCount ?? 0 }}</span>
                </a>
                <a href="{{ route('invoices.index', array_filter(['tab' => 'outstanding', 'c' => $selectedClientId, 'type' => $selectedType ?? ''])) }}"
                    class="invoice-tab {{ $currentTab === 'outstanding' ? 'is-active' : '' }}">
                    Outstanding <span>{{ $outstandingInvoicesCount ?? 0 }}</span>
                </a>
                <a href="{{ route('invoices.index', array_filter(['tab' => 'upcoming', 'c' => $selectedClientId, 'type' => $selectedType ?? ''])) }}"
                    class="invoice-tab {{ $currentTab === 'upcoming' ? 'is-active' : '' }}">
                    Upcoming <span>{{ $upcomingInvoicesCount ?? 0 }}</span>
                </a>
                <a href="{{ route('invoices.index', array_filter(['tab' => 'cancelled', 'c' => $selectedClientId, 'type' => $selectedType ?? ''])) }}"
                    class="invoice-tab {{ $currentTab === 'cancelled' ? 'is-active' : '' }}">
                    Cancelled <span>{{ $cancelledInvoicesCount ?? 0 }}</span>
                </a>
            </div>
        </div>

        <section class="invoice-group">
            @if ($currentTab === 'upcoming')
                <div class="invoice-list-meta">
                    <div class="meta-info">
                        <strong>Upcoming invoices</strong>
                        <span class="small-text">Draft invoices that are not finalized yet.</span>
                    </div>
                </div>
            @elseif ($currentTab === 'outstanding')
                <div class="invoice-list-meta">
                    <div class="meta-info">
                        <strong>Outstanding invoices</strong>
                        <span class="small-text">Invoices that are unpaid or partially paid.</span>
                    </div>
                </div>
            @elseif ($currentTab === 'cancelled')
                <div class="invoice-list-meta">
                    <div class="meta-info">
                        <strong>Cancelled invoices</strong>
                        <span class="small-text">All invoices that have been cancelled.</span>
                    </div>
                </div>
            @endif

            @if ($allInvoices->isEmpty())
                <div class="invoice-empty">
                    <i class="fas fa-file-invoice empty-state-icon"></i>
                    <p class="no-empty-state-text">No invoices found.</p>
                    <p class="small-text">Choose a client or create a new invoice to get started.</p>
                </div>
            @else
                <div class="invoice-table-wrap">
                    <table class="data-table table-no-margin">
                        <thead>
                            <tr>
                                <th class="w-5"></th>
                                <th>Client</th>
                                <th>Invoice</th>
                                <th>Issue Date</th>
                                <th>Amount{{ $selectedClientCurrency ? ' (' . $selectedClientCurrency . ')' : '' }}
                                </th>
                                <th>Balance{{ $selectedClientCurrency ? ' (' . $selectedClientCurrency . ')' : '' }}
                                </th>
                                <th>Payment Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
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
                                            if ($amountPaid > 0 && $balanceDue <= 0 && $invoiceAmount > 0) {
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
                                            <button type="button" class="expand-order-btn"
                                                onclick="toggleInvoiceItems('{{ $documentId }}')">
                                                <i class="fas fa-chevron-right expand-order-icon"
                                                    id="invoice-icon-{{ $documentId }}"></i>
                                            </button>
                                        </td>
                                        <td>{{ $clientName }}</td>
                                        <td>
                                            <div class="invoice-row-title">
                                                <div class="invoice-row-text">
                                                    <strong>{{ $invoice->invoice_title ?: $invoice->invoice_number }}</strong>
                                                    @if ($documentNumber)
                                                        <div class="invoice-number-line">
                                                            {{ $documentNumber }}
                                                            <span class="app-badge app-badge--xs {{ !empty($invoice->ti_number) ? 'app-badge--gray' : 'app-badge--violet' }}">
                                                                {{ !empty($invoice->ti_number) ? 'TI' : 'PI' }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            {{ $invoice->issue_date?->format('d M Y') ?? '-' }}
                                        </td>
                                        <td>
                                            <span class="invoice-amount">{{ number_format($invoiceAmount, 0) }}</span>
                                        </td>
                                        <td>
                                            <span class="invoice-amount">{{ number_format($balanceDue, 0) }}</span>
                                        </td>
                                        <td>
                                            @if ($paymentStatus === 'paid')
                                                <span class="status-pill is-paid">Paid</span>
                                            @elseif($paymentStatus === 'partly_paid')
                                                <span class="status-pill is-pending">Partly Paid</span>
                                            @else
                                                <span class="status-pill is-cancelled">Unpaid</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="table-actions justify-content-center">
                                                <a href="{{ route('invoices.show', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}" class="text-action-btn view">View</a>

                                                @if (($invoice->status ?? '') === 'cancelled')
                                                    <form method="POST" action="{{ route('invoices.restore', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}" class="inline-delete" onsubmit="return confirm('Restore this invoice?')">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-action-btn secondary">Restore</button>
                                                    </form>
                                                @else
                                                    <a href="{{ route('invoices.pdf', $invoice) }}" class="text-action-btn pdf" target="_blank">PDF</a>
                                                    <a href="{{ route('invoices.edit', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}" class="text-action-btn edit">Edit</a>
                                                    <form method="POST" action="{{ route('invoices.destroy', array_filter(['invoice' => $documentId, 'c' => $selectedClientId])) }}" class="inline-delete" onsubmit="return confirm('Cancel this invoice?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-action-btn delete">Cancel</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="order-items-row" id="invoice-items-{{ $documentId }}">
                                        <td colspan="8" class="order-items-cell">
                                            <div class="order-items-inner">
                                                <div class="order-items-head">
                                                    <i class="fas fa-box-open order-items-head-icon"></i>
                                                    <strong class="order-items-head-title">Invoice Items</strong>
                                                </div>
                                                <div class="order-items-content">
                                                    @if ($invoice->items->isNotEmpty())
                                                        <div class="order-items-grid">
                                                            @foreach ($invoice->items as $item)
                                                                @php
                                                                    $itemExpired =
                                                                        !empty($item->end_date) &&
                                                                        $item->end_date < now();
                                                                @endphp
                                                                <div class="order-item-card">
                                                                    <div class="order-item-card-row">
                                                                        <div>
                                                                            <strong
                                                                                class="order-item-name">{{ $item->item_name ?? 'Item' }}</strong>
                                                                            <div class="order-item-meta">
                                                                                Qty:
                                                                                {{ number_format((float) ($item->quantity ?? 1), 0) }}
                                                                                @if (!empty($item->frequency))
                                                                                    | Freq:
                                                                                    {{ ucfirst(str_replace('_', ' ', $item->frequency)) }}
                                                                                @endif
                                                                                @if (!empty($item->start_date))
                                                                                    | Start:
                                                                                    {{ $item->start_date->format('d M Y') }}
                                                                                @endif
                                                                                @if (!empty($item->end_date))
                                                                                    | End: <span
                                                                                        class="invoice-end-date {{ $itemExpired ? 'is-expired' : '' }}">{{ $item->end_date->format('d M Y') }}</span>
                                                                                @endif
                                                                                | Status: <span
                                                                                    class="invoice-muted">{{ ucfirst($item->status ?? 'active') }}</span>
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
            @endif
        </section>
    </div>

    <script>
        function toggleInvoiceItems(invoiceId) {
            const itemsRow = document.getElementById('invoice-items-' + invoiceId);
            const icon = document.getElementById('invoice-icon-' + invoiceId);
            if (!itemsRow || !icon) return;

            const isActive = itemsRow.classList.toggle('active');
            icon.style.transform = isActive ? 'rotate(90deg)' : 'rotate(0deg)';
        }
    </script>

@endsection
