@extends('layouts.app')

@section('header_actions')
    @if($selectedClientId)
        <a href="{{ route('invoices.create', ['c' => $selectedClientId]) }}" class="primary-button">
            <i class="fas fa-file-invoice" style="margin-right: 0.5rem;"></i>Create Invoice
        </a>
    @endif
@endsection

@section('content')
    <style>
        .invoice-index-shell {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .invoice-group {
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--panel);
        }

        .invoice-group-head {
            padding: 0.72rem 0.85rem;
            background: #fff;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .invoice-client-meta {
            display: inline-flex;
            flex-direction: row;
            align-items: center;
            gap: 0.4rem;
            flex-wrap: wrap;
        }

        .invoice-client-summary {
            margin-left: auto;
            display: inline-flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.05rem;
            text-align: right;
        }

        .invoice-client-summary .service-count {
            margin: 0;
            font-size: 0.74rem;
            color: #334155;
            font-weight: 700;
        }

        .invoice-table-wrap {
            padding: 0;
            background: var(--panel);
        }

        .invoice-row-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .invoice-row-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #f3f4f6;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .invoice-row-text strong {
            display: block;
            font-size: 0.9rem;
            color: #111827;
        }

        .invoice-row-text span {
            display: block;
            margin-top: 0.15rem;
            font-size: 0.75rem;
            color: #6b7280;
        }

        .invoice-type-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
            border: 1px solid transparent;
        }

        .invoice-type-badge.invoice {
            background: #eef2ff;
            color: #4338ca;
            border-color: #c7d2fe;
        }

        .invoice-type-badge.tax {
            background: #f3f4f6;
            color: #374151;
            border-color: #e5e7eb;
        }

        .invoice-muted {
            font-size: 0.84rem;
            color: #6b7280;
        }

        .invoice-amount {
            font-size: 0.9rem;
            font-weight: 600;
            color: #111827;
        }

        .invoice-empty {
            padding: 2rem 1.25rem;
            text-align: center;
            color: #9ca3af;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--panel);
        }

        .invoice-client-picker-wrap {
            max-width: 760px;
            margin: 1rem auto 0;
        }

        .invoice-client-picker {
            padding: 1.1rem;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--panel);
        }

        .invoice-client-picker-head {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            align-items: flex-start;
            margin-bottom: 0.8rem;
            padding-bottom: 0.7rem;
            border-bottom: 1px solid var(--line);
            flex-wrap: wrap;
        }

        .invoice-client-picker-title {
            display: flex;
            gap: 0.65rem;
            align-items: flex-start;
        }

        .invoice-client-picker-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #eef2ff;
            color: #4f46e5;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .invoice-client-picker-title strong {
            display: block;
            font-size: 0.92rem;
            color: #0f172a;
        }

        .invoice-client-picker-title p {
            margin: 0.2rem 0 0;
            font-size: 0.78rem;
            color: #6b7280;
        }

        .invoice-client-count {
            padding: 0.3rem 0.6rem;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: #f8fafc;
            font-size: 0.72rem;
            font-weight: 600;
            color: #475569;
        }

        .invoice-client-picker form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.65rem;
            align-items: end;
        }

        .invoice-client-picker-field label {
            display: block;
            margin-bottom: 0.3rem;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #64748b;
        }

        .invoice-client-picker-field select {
            width: 100%;
            min-height: 40px;
            padding: 0.55rem 0.72rem;
            border: 1px solid #dbe3ee;
            border-radius: 8px;
            background: #f8fafc;
            font-size: 0.84rem;
            color: #0f172a;
        }

        .invoice-table-wrap .data-table {
            margin-top: 0;
        }

        .invoice-table-wrap .data-table th {
            padding: 0.58rem 0.72rem;
            font-size: 0.68rem;
        }

        .invoice-table-wrap .data-table td {
            padding: 0.62rem 0.72rem;
            vertical-align: middle;
        }

        @media (max-width: 720px) {
            .invoice-client-picker form {
                grid-template-columns: 1fr;
            }

            .invoice-group-head {
                flex-wrap: wrap;
            }

            .invoice-client-summary {
                margin-left: 0;
                width: 100%;
                align-items: flex-start;
                text-align: left;
            }
        }
    </style>

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
                        <button type="submit" class="primary-button" style="min-height: 46px; padding-inline: 1.15rem;">
                            <i class="fas fa-arrow-right" style="margin-right: 0.4rem;"></i> View Invoices
                        </button>
                    </form>
                </div>
            </div>
        @elseif($selectedInvoices->isEmpty())
            <section class="invoice-group">
                <div class="invoice-group-head">
                    <span class="invoice-client-meta">
                        <form action="{{ route('invoices.index') }}" method="GET" style="margin: 0;">
                            <select
                                name="c"
                                class="form-control"
                                style="min-width: 260px; min-height: 34px;"
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
                    <i class="fas fa-file-invoice" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.3;"></i>
                    <p style="margin: 0; font-size: 0.95rem; font-weight: 500;">No invoices found for {{ $selectedClientLabel }}</p>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">Create your first invoice to get started.</p>
                </div>
            </section>
        @else
            <section class="invoice-group">
                <div class="invoice-group-head">
                    <span class="invoice-client-meta">
                        <form action="{{ route('invoices.index') }}" method="GET" style="margin: 0;">
                            <select
                                name="c"
                                class="form-control"
                                style="min-width: 260px; min-height: 34px;"
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
                    <table class="data-table" style="margin: 0;">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Invoice</th>
                                <th style="width: 10%;">Type</th>
                                <th style="width: 12%;">For</th>
                                <th style="width: 12%;">Amount ({{ $selectedClientCurrency }})</th>
                                <th style="width: 12%;">End Date</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 14%;">Actions</th>
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
                                            <span style="font-size: 0.84rem; color: {{ $isExpired ? '#dc2626' : '#6b7280' }}; font-weight: {{ $isExpired ? '600' : '500' }};">
                                                {{ $latestEndDate->format('d M Y') }}
                                                @if($isExpired)
                                                    <span style="margin-left: 0.35rem; font-size: 0.7rem; text-transform: uppercase;">expired</span>
                                                @endif
                                            </span>
                                        @else
                                            <span class="invoice-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(($invoice->status ?? '') === 'cancelled')
                                            <span class="status-pill" style="background: #e2e8f0; color: #475569;">Cancelled</span>
                                        @else
                                            <span class="status-pill" style="background: #dbeafe; color: #1e40af;">Active</span>
                                        @endif
                                    </td>
                                    <td class="table-actions">
                                        <a href="{{ route('invoices.show', [$documentId, 'c' => $selectedClientId]) }}" class="icon-action-btn view" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <a href="{{ route('invoices.edit', [$invoice, 'c' => $selectedClientId]) }}" class="icon-action-btn edit" title="Edit">
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>

    <style>
        .status-pill.active { background: #dbeafe; color: #1e40af; }
        .status-pill.cancelled { background: #e2e8f0; color: #475569; }
    </style>

@endsection
