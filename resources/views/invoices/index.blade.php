@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('invoices.create') }}" class="primary-button">
        <i class="fas fa-file-invoice" style="margin-right: 0.5rem;"></i>Create Proforma Invoice
    </a>
@endsection

@section('content')
    <style>
        .invoice-index-shell {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .invoice-group {
            overflow: hidden;
        }

        .invoice-group[open] .accordion-header {
            border-bottom: 1px solid #e5e7eb;
        }

        .invoice-group .accordion-header {
            padding: 1rem 1.1rem;
            background: #fff;
        }

        .invoice-client-meta {
            display: inline-flex;
            flex-direction: column;
            gap: 0.12rem;
        }

        .invoice-client-email {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 500;
        }

        .invoice-table-wrap {
            padding: 0;
            background: #fff;
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

        .invoice-type-badge.proforma {
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
            padding: 3rem;
            text-align: center;
            color: #9ca3af;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
        }

        .inline-edit-row {
            display: none;
            background: #fbfcfe;
        }

        .inline-edit-row.active {
            display: table-row;
        }

        .inline-edit-container {
            padding: 1.5rem;
        }

        .inline-edit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .inline-edit-header h4 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 600;
            color: #111827;
        }

        .cancel-edit-btn {
            padding: 0.65rem 1.2rem;
            background: #f9fafb;
            color: #374151;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
        }

        .cancel-edit-btn:hover {
            background: #f3f4f6;
        }

        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>

    <div class="invoice-index-shell">
    @forelse ($groupedInvoices as $clientName => $clientInvoices)
        @php
            $firstInvoice = $clientInvoices->first();
            $clientEmailForGroup = $firstInvoice->client->email ?? '';
            $clientId = $firstInvoice->clientid ?? '';
        @endphp
        <details class="category-accordion invoice-group" open>
            <summary class="accordion-header">
                <span class="invoice-client-meta" onclick="event.stopPropagation();">
                    @if($clientId)
                        <form action="{{ route('invoices.index') }}" method="GET" style="margin: 0;">
                            <select
                                name="c"
                                class="form-control form-control"
                                style="min-width: 260px; min-height: 34px;"
                                onchange="this.form.submit()"
                                onclick="event.stopPropagation();"
                            >
                                @foreach($clients as $clientOption)
                                    <option value="{{ $clientOption->clientid }}" {{ (string) $clientId === (string) $clientOption->clientid ? 'selected' : '' }}>
                                        {{ $clientOption->business_name ?? $clientOption->contact_name ?? 'Client' }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @else
                        <span class="category-title">{{ $clientName }}</span>
                    @endif
                    @if($clientEmailForGroup)
                        <span class="invoice-client-email">{{ $clientEmailForGroup }}</span>
                    @endif
                </span>
                <span class="service-count">{{ count($clientInvoices) }} invoice(s)</span>
                <span class="accordion-icon"></span>
            </summary>
            <div class="accordion-content invoice-table-wrap">
                <table class="data-table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th style="width: 30%;">Invoice</th>
                            <th style="width: 10%;">Type</th>
                            <th style="width: 12%;">For</th>
                            <th style="width: 12%;">Amount</th>
                            <th style="width: 12%;">End Date</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 14%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($clientInvoices as $invoice)
                        @php
                            $documentId = $invoice->proformaid ?? $invoice->invoiceid;
                            $documentType = $invoice->isProforma() ? 'Proforma' : 'Tax';
                            $amountPaid = (float) ($invoice->amount_paid ?? 0);
                            $grandTotal = (float) ($invoice->grand_total ?? 0);
                            $balanceDue = (float) ($invoice->balance_due ?? max(0, $grandTotal - $amountPaid));
                            $paymentStatus = ($amountPaid > 0 && $balanceDue <= 0 && $grandTotal > 0)
                                ? 'paid'
                                : ($amountPaid > 0 ? 'partially paid' : 'unpaid');
                            $latestEndDate = $invoice->items->max('end_date');
                            $isExpired = $latestEndDate && $latestEndDate < now();
                            $currency = $invoice->client->currency ?? 'INR';
                            $isProforma = $invoice->isProforma();
                            $convertedTaxInvoice = $invoice->convertedTaxInvoice;
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
                                <strong class="invoice-amount">{{ $currency }} {{ number_format($invoice->grand_total ?? 0, 2) }}</strong>
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
                                <span class="status-pill {{ str_replace(' ', '-', $paymentStatus) }}">{{ ucfirst($paymentStatus) }}</span>
                            </td>
                            <td class="table-actions">
                                <a href="{{ route('invoices.show', $documentId) }}" class="icon-action-btn view" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>

                                @if($isProforma && !$convertedTaxInvoice)
                                    <form method="POST" action="{{ route('invoices.convert-to-tax', $documentId) }}" style="display: inline;" onsubmit="return confirm('Convert this proforma invoice to a tax invoice?')">
                                        @csrf
                                        <button type="submit" class="icon-action-btn" style="width: 28px; height: 28px; color: #4338ca; border-color: #c7d2fe; background: #eef2ff;" title="Convert to Tax Invoice">
                                            <i class="fas fa-file-invoice-dollar"></i>
                                        </button>
                                    </form>
                                @elseif($isProforma && $convertedTaxInvoice)
                                    <a href="{{ route('invoices.show', $convertedTaxInvoice) }}" class="icon-action-btn" title="View Tax Invoice" style="width: 28px; height: 28px; color: #047857; border-color: #a7f3d0; background: #ecfdf5;">
                                        <i class="fas fa-link"></i>
                                    </a>
                                @endif

                                <a href="{{ route('invoices.edit', $invoice) }}" class="icon-action-btn edit" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <form method="POST" action="{{ route('invoices.destroy', $documentId) }}" class="inline-delete" onsubmit="return confirm('Delete this invoice?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="icon-action-btn delete" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <tr class="inline-edit-row" id="inline-edit-row-{{ $documentId }}">
                            <td colspan="7" style="padding: 0; border-top: 1px solid #c7d2fe;">
                                <div class="inline-edit-container">
                                    <div class="inline-edit-header">
                                        <h4><i class="fas fa-edit" style="margin-right: 0.5rem; color: #4f46e5;"></i> Editing Invoice: {{ $invoice->invoice_number }}</h4>
                                        <button type="button" class="cancel-edit-btn" onclick="toggleInlineEdit('{{ $documentId }}')">
                                            <i class="fas fa-times" style="margin-right: 0.5rem;"></i> Cancel
                                        </button>
                                    </div>
                                    <div id="inline-edit-content-{{ $documentId }}" style="text-align: center; padding: 2rem;">
                                        <p style="color: #6b7280;">Loading editor...</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @empty
        @if($selectedClientId)
            @php
                $selectedClient = $clients->firstWhere('clientid', $selectedClientId);
                $selectedClientName = $selectedClient->business_name ?? $selectedClient->contact_name ?? 'Selected Client';
                $selectedClientEmail = $selectedClient->email ?? '';
            @endphp
            <details class="category-accordion invoice-group" open>
                <summary class="accordion-header">
                    <span class="invoice-client-meta" onclick="event.stopPropagation();">
                        <form action="{{ route('invoices.index') }}" method="GET" style="margin: 0;">
                            <select
                                name="c"
                                class="form-control form-control"
                                style="min-width: 260px; min-height: 34px;"
                                onchange="this.form.submit()"
                                onclick="event.stopPropagation();"
                            >
                                @foreach($clients as $clientOption)
                                    <option value="{{ $clientOption->clientid }}" {{ (string) $selectedClientId === (string) $clientOption->clientid ? 'selected' : '' }}>
                                        {{ $clientOption->business_name ?? $clientOption->contact_name ?? 'Client' }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                        @if($selectedClientEmail)
                            <span class="invoice-client-email">{{ $selectedClientEmail }}</span>
                        @else
                            <span class="invoice-client-email">{{ $selectedClientName }}</span>
                        @endif
                    </span>
                    <span class="service-count">0 invoice(s)</span>
                    <span class="accordion-icon"></span>
                </summary>
                <div class="invoice-empty">
                    <i class="fas fa-file-invoice" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.3;"></i>
                    <p style="margin: 0; font-size: 0.95rem; font-weight: 500;">No invoices found</p>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">Create your first invoice to get started.</p>
                </div>
            </details>
        @else
            <div class="invoice-empty">
                <i class="fas fa-file-invoice" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.3;"></i>
                <p style="margin: 0; font-size: 0.95rem; font-weight: 500;">No invoices found</p>
                <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">Create your first invoice to get started.</p>
            </div>
        @endif
    @endforelse
    </div>

    <style>
        .status-pill.unpaid { background: #fee2e2; color: #991b1b; }
        .status-pill.partially-paid { background: #fef3c7; color: #92400e; }
        .status-pill.paid { background: #dcfce7; color: #166534; }
    </style>

    <script>
    const inlineEditState = {};

    async function toggleInlineEdit(invoiceId) {
        const row = document.getElementById(`inline-edit-row-${invoiceId}`);
        const contentDiv = document.getElementById(`inline-edit-content-${invoiceId}`);

        if (!row.classList.contains('active')) {
            row.classList.add('active');

            if (!inlineEditState[invoiceId]) {
                contentDiv.innerHTML = '<p style="color: #6b7280; text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin" style="margin-right: 0.5rem;"></i> Loading editor...</p>';

                try {
                    const response = await fetch(`/invoices/${invoiceId}/edit?inline=1`);
                    const html = await response.text();
                    contentDiv.innerHTML = html;
                    inlineEditState[invoiceId] = true;
                    initializeInlineEditForm(invoiceId);
                } catch (error) {
                    contentDiv.innerHTML = '<p style="color: #ef4444; text-align: center; padding: 2rem;">Error loading editor. Please try again.</p>';
                    console.error('Error loading editor:', error);
                }
            }
        } else {
            row.classList.remove('active');
        }
    }

    function initializeInlineEditForm(invoiceId) {
        const form = document.getElementById(`inline-edit-form-${invoiceId}`);
        if (!form) return;

        const saveBtn = document.getElementById(`save-inline-edit-${invoiceId}`);
        const cancelBtn = document.getElementById(`cancel-inline-edit-${invoiceId}`);

        if (cancelBtn) {
            cancelBtn.addEventListener('click', (e) => {
                e.preventDefault();
                toggleInlineEdit(invoiceId);
            });
        }

        if (saveBtn) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="loading-spinner"></span> Saving...';

                const formData = new FormData(form);
                formData.append('_method', 'PUT');

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        window.location.reload();
                    } else {
                        const errorData = await response.json();
                        if (errorData.errors) {
                            let errorMsg = 'Please fix the following errors:\n';
                            Object.values(errorData.errors).forEach(errors => {
                                errorMsg += errors.join('\n') + '\n';
                            });
                            alert(errorMsg);
                        } else {
                            alert('Error saving invoice. Please try again.');
                        }
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="fas fa-save" style="margin-right: 0.5rem;"></i> Save Changes';
                    }
                } catch (error) {
                    console.error('Error saving invoice:', error);
                    alert('Error saving invoice. Please try again.');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save" style="margin-right: 0.5rem;"></i> Save Changes';
                }
            });
        }
    }
    </script>
@endsection
