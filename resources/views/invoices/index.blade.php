@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('invoices.create', $selectedClientId ? ['c' => $selectedClientId] : []) }}" class="primary-button">
        <i class="fas fa-file-invoice icon-spaced"></i>Create Invoice
    </a>
@endsection

@section('content')
    @php
        $selectedClient = $clients->firstWhere('clientid', $selectedClientId);
        $selectedClientLabel = $selectedClient
            ? $selectedClient->business_name ?? ($selectedClient->contact_name ?? 'Selected Client')
            : 'All Clients';
        $selectedClientCurrency = $selectedClient->currency ?? null;
        $currentTab = in_array($selectedTab, ['invoices', 'upcoming', 'expired', 'suspended'], true)
            ? $selectedTab
            : 'invoices';
    @endphp

    <div class="invoice-index-shell">
        <section class="panel-card invoice-top-panel">
            <div class="invoice-ribbon">
                <div class="invoice-ribbon-main">
                    <div class="invoice-brand">
                        <strong class="invoice-toolbar-title">Invoices</strong>
                        <p class="invoice-toolbar-subtitle">{{ $selectedClientLabel }}</p>
                    </div>

                    <div class="invoice-tabs">
                        <a href="{{ route('invoices.index', array_filter(['c' => $selectedClientId, 'tab' => 'invoices'])) }}"
                            class="invoice-tab {{ $currentTab === 'invoices' ? 'is-active' : '' }}">
                            All <span>{{ $allInvoices->count() }}</span>
                        </a>
                        <a href="{{ route('invoices.index', array_filter(['c' => $selectedClientId, 'tab' => 'upcoming'])) }}"
                            class="invoice-tab {{ $currentTab === 'upcoming' ? 'is-active' : '' }}">
                            Upcoming <span>{{ $upcomingExpiryItems->count() }}</span>
                        </a>
                        <a href="{{ route('invoices.index', array_filter(['c' => $selectedClientId, 'tab' => 'expired'])) }}"
                            class="invoice-tab {{ $currentTab === 'expired' ? 'is-active' : '' }}">
                            Expired <span>{{ $expiredItems->count() }}</span>
                        </a>
                        <a href="{{ route('invoices.index', array_filter(['c' => $selectedClientId, 'tab' => 'suspended'])) }}"
                            class="invoice-tab {{ $currentTab === 'suspended' ? 'is-active' : '' }}">
                            Suspended <span>{{ $suspendedItems->count() }}</span>
                        </a>
                    </div>
                </div>

                <form action="{{ route('invoices.index') }}" method="GET" class="invoice-toolbar-form">
                    <input type="hidden" name="tab" value="{{ $currentTab }}">
                    <div class="compact-filter">
                        <i class="fas fa-filter filter-icon"></i>
                        <select name="c" id="invoice-client-filter" class="form-control select-client-pill"
                            onchange="this.form.submit()">
                            <option value="">All Clients</option>
                            @foreach ($clients as $clientOption)
                                <option value="{{ $clientOption->clientid }}"
                                    {{ (string) $selectedClientId === (string) $clientOption->clientid ? 'selected' : '' }}>
                                    {{ $clientOption->business_name ?? ($clientOption->contact_name ?? 'Client') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
        </section>

        @if ($currentTab === 'invoices')
            <section class="invoice-group">
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
                                    <th>Type</th>
                                    <th>Amount{{ $selectedClientCurrency ? ' (' . $selectedClientCurrency . ')' : '' }}
                                    </th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($allInvoices as $invoice)
                                    @php
                                        $documentId = $invoice->invoiceid;
                                        $latestEndDate = $invoice->items->max('end_date');
                                        $documentType = !empty($invoice->ti_number)
                                            ? 'Tax Invoice'
                                            : 'Proforma Invoice';
                                        $documentNumber =
                                            $invoice->ti_number ?: $invoice->pi_number ?: $invoice->invoice_number;
                                        $invoiceAmount = (float) ($invoice->grand_total ?? 0);
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
                                                        <span>{{ $documentNumber }}</span>
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
                                            <span class="invoice-amount">{{ number_format($invoiceAmount, 0) }}</span>
                                        </td>
                                        <td>
                                            @if (($invoice->status ?? '') === 'cancelled')
                                                <span class="status-pill status-pill-cancelled">Cancelled</span>
                                            @else
                                                <span class="status-pill status-pill-running">Active</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="table-actions">
                                                <a href="{{ route('invoices.show', [$documentId, 'c' => $selectedClientId ?: $invoice->clientid]) }}"
                                                    class="icon-action-btn view" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                @if (($invoice->status ?? '') === 'cancelled')
                                                    <form method="POST"
                                                        action="{{ route('invoices.restore', [$documentId, 'c' => $selectedClientId ?: $invoice->clientid]) }}"
                                                        class="inline-delete"
                                                        onsubmit="return confirm('Restore this invoice?')">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit"
                                                            class="order-create-pi-link order-pill-created"
                                                            title="Restore Invoice">
                                                            Restore
                                                        </button>
                                                    </form>
                                                @else
                                                    <a href="{{ route('invoices.pdf', $invoice) }}"
                                                        class="icon-action-btn pdf" title="Download PDF" target="_blank">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>

                                                    <a href="{{ route('invoices.edit', ['invoice' => $documentId, 'c' => $selectedClientId ?: $invoice->clientid]) }}"
                                                        class="icon-action-btn edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                    <form method="POST"
                                                        action="{{ route('invoices.destroy', [$documentId, 'c' => $selectedClientId ?: $invoice->clientid]) }}"
                                                        class="inline-delete"
                                                        onsubmit="return confirm('Cancel this invoice?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="icon-action-btn delete"
                                                            title="Cancel Invoice">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="order-items-row" id="invoice-items-{{ $documentId }}"
                                        style="display: none;">
                                        <td colspan="7" class="order-items-cell">
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
        @else
            @php
                $tabRows = match ($currentTab) {
                    'upcoming' => $upcomingExpiryItems,
                    'expired' => $expiredItems,
                    'suspended' => $suspendedItems,
                    default => collect(),
                };
            @endphp

            <section class="invoice-group">
                <div class="invoice-list-meta">
                    @if ($currentTab === 'upcoming')
                        <div class="meta-info">
                            <strong>Upcoming expiring items</strong>
                            <span class="small-text">Items ending in the next {{ $upcomingWindowDays }} days.</span>
                        </div>
                        <form method="GET" action="{{ route('invoices.index') }}" class="compact-meta-filter">
                            <input type="hidden" name="tab" value="upcoming">
                            <input type="hidden" name="c" value="{{ $selectedClientId }}">
                            <label for="next_days">Next Days:</label>
                            <input type="text" name="next_days" id="next_days" value="{{ $upcomingWindowDays }}"
                                inputmode="numeric">
                            <button type="submit" class="secondary-button small">Apply</button>
                        </form>
                    @elseif($currentTab === 'expired')
                        <div class="meta-info">
                            <strong>Expired items</strong>
                            <span class="small-text">Only items whose end date is already over.</span>
                        </div>
                    @else
                        <div class="meta-info">
                            <strong>Suspended items</strong>
                            <span class="small-text">These items were manually suspended.</span>
                        </div>
                    @endif
                </div>

                @if (collect($tabRows)->isEmpty())
                    <div class="invoice-empty">
                        <i class="fas fa-check-circle empty-state-icon"></i>
                        <p class="no-empty-state-text">No records found for this tab.</p>
                    </div>
                @else
                    <div class="invoice-table-wrap">
                        <table class="data-table table-no-margin">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Invoice</th>
                                    <th>Item</th>
                                    <th>Expiry Date</th>
                                    <th>Days</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tabRows as $row)
                                    <tr>
                                        <td>{{ $row['client_name'] }}</td>
                                        <td>
                                            <a href="{{ route('invoices.show', ['invoice' => $row['invoiceid'], 'c' => $row['clientid']]) }}"
                                                class="invoice-muted">
                                                <strong>{{ $row['invoice_label'] }}</strong>
                                            </a>
                                            <div class="small-text">{{ $row['invoice_number'] }}</div>
                                        </td>
                                        <td>
                                            <strong>{{ $row['item_name'] }}</strong>
                                            @if (!empty($row['frequency']) || !empty($row['duration']))
                                                <div class="small-text">
                                                    @if (!empty($row['duration']))
                                                        Duration: {{ $row['duration'] }}
                                                    @endif
                                                    @if (!empty($row['duration']) && !empty($row['frequency']))
                                                        |
                                                    @endif
                                                    @if (!empty($row['frequency']))
                                                        {{ ucfirst(str_replace('_', ' ', $row['frequency'])) }}
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ $row['end_date_display'] }}</td>
                                        <td>
                                            @if ($row['days_left'] === null)
                                                <span class="invoice-muted">-</span>
                                            @elseif($row['days_left'] > 0)
                                                <span class="text-success"
                                                    style="font-weight: 600;">{{ $row['days_left'] }} day(s)</span>
                                            @elseif($row['days_left'] === 0)
                                                <span class="text-warning" style="font-weight: 600;">Today</span>
                                            @else
                                                <span class="text-danger"
                                                    style="font-weight: 600;">{{ abs($row['days_left']) }} day(s)
                                                    ago</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if (($row['status'] ?? 'active') === 'suspended')
                                                <span class="status-pill status-pill-cancelled">Suspended</span>
                                            @elseif(($row['days_left'] ?? 1) <= 0)
                                                <span class="status-pill status-pill-cancelled">Expired</span>
                                            @else
                                                <span class="status-pill status-pill-running">Active</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="table-actions" style="justify-content: center;">
                                                <a href="{{ route('invoices.items.renew', ['item' => $row['invoice_itemid']]) }}"
                                                    class="secondary-button small" title="Renew">
                                                    Renew
                                                </a>

                                                @if ($row['status'] !== 'suspended' && ($row['days_left'] ?? 1) <= 0)
                                                    <form method="POST"
                                                        action="{{ route('invoices.items.suspend', ['invoice' => $row['invoiceid'], 'item' => $row['invoice_itemid'], 'c' => $selectedClientId]) }}"
                                                        class="inline-delete m-0"
                                                        onsubmit="return confirm('Suspend this item?')">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="secondary-button small"
                                                            title="Suspend">
                                                            Suspend
                                                        </button>
                                                    </form>
                                                @elseif($row['status'] === 'suspended')
                                                    <form method="POST"
                                                        action="{{ route('invoices.items.unsuspend', ['invoice' => $row['invoiceid'], 'item' => $row['invoice_itemid'], 'c' => $selectedClientId]) }}"
                                                        class="inline-delete m-0"
                                                        onsubmit="return confirm('Unsuspend this item?')">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="secondary-button small"
                                                            title="Unsuspend">
                                                            Unsuspend
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif
    </div>

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
    </script>

    <style>
        .invoice-top-panel {
            padding: 0.65rem 1rem;
            margin-bottom: 1.25rem;
        }

        .invoice-ribbon {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .invoice-ribbon-main {
            display: flex;
            align-items: center;
            gap: 2rem;
            flex: 1;
        }

        .invoice-brand {
            min-width: fit-content;
        }

        .invoice-toolbar-title {
            display: block;
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.2;
        }

        .invoice-toolbar-subtitle {
            margin: 0;
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 500;
        }

        .invoice-toolbar-form {
            margin: 0;
        }

        .compact-filter {
            display: flex;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 0.15rem 0.35rem 0.15rem 0.85rem;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .compact-filter:hover {
            border-color: #cbd5e1;
            background: #f1f5f9;
        }

        .filter-icon {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .select-client-pill {
            border: none !important;
            background: transparent !important;
            padding: 0.25rem 2rem 0.25rem 0 !important;
            font-size: 0.82rem !important;
            font-weight: 600 !important;
            color: #475569 !important;
            height: auto !important;
            width: auto !important;
            min-width: 140px;
            cursor: pointer;
            box-shadow: none !important;
        }

        .invoice-tabs {
            display: flex;
            gap: 0.35rem;
            align-items: center;
        }

        .invoice-tab {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.85rem;
            border-radius: 8px;
            color: #64748b;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.82rem;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .invoice-tab:hover {
            background: #f1f5f9;
            color: #334155;
        }

        .invoice-tab span {
            background: #e2e8f0;
            color: #475569;
            border-radius: 6px;
            padding: 0.1rem 0.4rem;
            font-size: 0.72rem;
            min-width: 20px;
            text-align: center;
        }

        .invoice-tab.is-active {
            background: white;
            color: #2563eb;
            border-color: #e2e8f0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }

        .invoice-tab.is-active span {
            background: #dbeafe;
            color: #2563eb;
        }

        .invoice-list-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.7rem;
            flex-wrap: wrap;
            background: #f8fafc;
            padding: 0.45rem 0.75rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .meta-info strong {
            display: block;
            color: #1e293b;
            font-size: 0.82rem;
            line-height: 1.1;
            margin-bottom: 0.1rem;
        }

        .meta-info .small-text {
            line-height: 1.05;
        }

        .compact-meta-filter {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
        }

        .compact-meta-filter label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #64748b;
            margin: 0;
        }

        .compact-meta-filter input {
            width: 70px;
            padding: 0.25rem 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.82rem;
            text-align: center;
        }

        .item-card-actions {
            margin-left: 1rem;
        }

        .invoice-end-date.is-expired {
            color: #ef4444;
            font-weight: 700;
        }

        .invoice-index-shell .data-table th,
        .invoice-index-shell .data-table td {
            padding: 0.7rem 0.7rem !important;
            font-size: 0.83rem;
            line-height: 1.2;
            vertical-align: middle;
        }

        .invoice-index-shell .data-table th {
            font-size: 0.76rem;
            white-space: nowrap;
        }

        .invoice-index-shell .data-table tbody td {
            padding-top: 0.8rem !important;
            padding-bottom: 0.8rem !important;
        }

        .invoice-index-shell .small-text {
            font-size: 0.72rem;
            line-height: 1.1;
        }

        .invoice-index-shell .invoice-row-title {
            gap: 0.4rem;
        }

        .invoice-index-shell .invoice-row-text span,
        .invoice-index-shell .order-item-meta {
            font-size: 0.72rem;
            line-height: 1.1;
        }

        .invoice-index-shell .table-actions {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.28rem;
        }

        .invoice-index-shell .secondary-button.small,
        .invoice-index-shell .order-create-pi-link,
        .invoice-index-shell .icon-action-btn {
            padding-top: 0.28rem;
            padding-bottom: 0.28rem;
        }
    </style>
@endsection
