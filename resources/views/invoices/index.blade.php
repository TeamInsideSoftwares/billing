@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">All Invoices</h3>
        </div>
        <div>
            <a href="{{ route('invoices.create') }}" class="primary-button">Create Invoice</a>
        </div>
    </section>

    @if(session('success'))
        <div style="margin-bottom: 1rem; padding: 0.9rem 1rem; border: 1px solid #bbf7d0; background: #f0fdf4; color: #166534; border-radius: 10px;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="margin-bottom: 1rem; padding: 0.9rem 1rem; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; border-radius: 10px;">
            {{ session('error') }}
        </div>
    @endif

    <style>
        .inline-edit-row { display: none; background: #f8fafc; }
        .inline-edit-row.active { display: table-row; }
        .inline-edit-container { padding: 1.5rem; }
        .inline-edit-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0; }
        .inline-edit-header h4 { margin: 0; font-size: 1.1rem; font-weight: 600; color: #475569; }
        .inline-edit-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .inline-edit-actions { display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem; }
        .cancel-edit-btn { padding: 0.65rem 1.2rem; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer; font-weight: 500; }
        .cancel-edit-btn:hover { background: #e2e8f0; }
        .save-edit-btn { padding: 0.65rem 1.2rem; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .save-edit-btn:hover { opacity: 0.9; }
        .save-edit-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .loading-spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid #ffffff; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite; margin-right: 0.5rem; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .edit-toggle-btn { transition: all 0.2s; }
        .edit-toggle-btn:hover { transform: scale(1.05); }
    </style>

    <div class="services-accordion-container">
    @forelse ($groupedInvoices as $clientName => $clientInvoices)
        @php
            $clientEmailForGroup = $clientInvoices->first()->client->email;
        @endphp
        <details class="category-accordion" open>
            <summary class="accordion-header">
                <span style="display: inline-flex; flex-direction: column; gap: 0.1rem;">
                    <span class="category-title">{{ $clientName }}</span>
                    @if($clientEmailForGroup)
                        <span style="font-size: 0.75rem; color: #64748b; font-weight: 500;">{{ $clientEmailForGroup }}</span>
                    @endif
                </span>
                <span class="service-count">{{ count($clientInvoices) }} invoice(s)</span>
                <span class="accordion-icon"></span>
            </summary>
            <div class="accordion-content">
                <table class="data-table">
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
                            $amountPaid = $invoice->payments->sum('amount');
                            $balanceDue = $invoice->grand_total - $amountPaid;
                            $paymentStatus = $balanceDue <= 0 ? 'paid' : ($amountPaid > 0 ? 'partially paid' : 'unpaid');

                            // Get the latest end_date from items
                            $latestEndDate = $invoice->items->max('end_date');
                            $isExpired = $latestEndDate && $latestEndDate < now();

                            // Get client currency
                            $currency = $invoice->client->currency ?? 'INR';

                            // Check if it's a proforma invoice
                            $isProforma = $invoice->isProforma();
                            $convertedTaxInvoice = $invoice->convertedTaxInvoice;
                        @endphp
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;">
                                        <i class="fas fa-file-invoice"></i>
                                    </div>
                                    <div>
                                        @if($invoice->invoice_title)
                                            <strong style="font-size: 0.9rem;">{{ $invoice->invoice_title }}</strong>
                                            <div style="font-size: 0.75rem; color: #64748b;">{{ $invoice->invoice_number }}</div>
                                        @else
                                            <strong style="font-size: 0.9rem;">{{ $invoice->invoice_number }}</strong>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php
                                    $typeBadgeColor = '#dbeafe';
                                    $typeBadgeTextColor = '#1e40af';
                                    if (strtolower($invoice->invoice_type ?? 'proforma') === 'tax') {
                                        $typeBadgeColor = '#fef3c7';
                                        $typeBadgeTextColor = '#92400e';
                                    } elseif (strtolower($invoice->invoice_type ?? 'proforma') === 'receipt') {
                                        $typeBadgeColor = '#d1fae5';
                                        $typeBadgeTextColor = '#065f46';
                                    }
                                @endphp
                                <span style="display: inline-block; padding: 0.25rem 0.6rem; background: {{ $typeBadgeColor }}; color: {{ $typeBadgeTextColor }}; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                                    {{ ucfirst($invoice->invoice_type ?? 'proforma') }}
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 0.85rem; color: #64748b;">{{ ucfirst(str_replace('_', ' ', $invoice->invoice_for ?? 'without orders')) }}</span>
                            </td>
                            <td>
                                <strong style="font-size: 0.9rem;">{{ $currency }} {{ number_format($invoice->grand_total ?? 0, 2) }}</strong>
                            </td>
                            <td>
                                @if($latestEndDate)
                                    <span style="font-size: 0.85rem; color: {{ $isExpired ? '#ef4444' : '#64748b' }}; font-weight: {{ $isExpired ? '600' : '400' }};">
                                        {{ $latestEndDate->format('d M Y') }}
                                        @if($isExpired)
                                            <span style="color: #ef4444; font-weight: 600; text-transform: uppercase; font-size: 0.7rem; margin-left: 0.3rem;">expired</span>
                                        @endif
                                    </span>
                                @else
                                    <span style="font-size: 0.85rem; color: #94a3b8;">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="status-pill {{ str_replace(' ', '-', $paymentStatus) }}">{{ ucfirst($paymentStatus) }}</span>
                            </td>
                            <td class="table-actions">
                                <div style="display: flex; gap: 0.4rem; align-items: center;">
                                    <a href="{{ route('invoices.show', $invoice->invoiceid) }}" class="icon-action-btn view" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($isProforma && !$convertedTaxInvoice)
                                        <form method="POST" action="{{ route('invoices.convert-to-tax', $invoice->invoiceid) }}" style="display: inline;" onsubmit="return confirm('Convert this proforma invoice to a tax invoice?')">
                                            @csrf
                                            <button type="submit" class="icon-action-btn" style="background: #fef3c7; color: #92400e; border: none; padding: 0.5rem; border-radius: 4px; cursor: pointer;" title="Convert to Tax Invoice">
                                                <i class="fas fa-file-invoice-dollar"></i>
                                            </button>
                                        </form>
                                    @elseif($isProforma && $convertedTaxInvoice)
                                        <a href="{{ route('invoices.show', $convertedTaxInvoice) }}" class="icon-action-btn view" title="View Tax Invoice" style="background: #ecfdf5; color: #047857;">
                                            <i class="fas fa-link"></i>
                                        </a>
                                    @endif
                                    <button type="button" class="icon-action-btn edit edit-toggle-btn" title="Edit" onclick="toggleInlineEdit({{ $invoice->invoiceid }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="{{ route('invoices.destroy', $invoice->invoiceid) }}" class="inline-delete" onsubmit="return confirm('Delete this invoice?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-action-btn delete" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr class="inline-edit-row" id="inline-edit-row-{{ $invoice->invoiceid }}">
                            <td colspan="7" style="padding: 0; border-top: 2px solid #3b82f6;">
                                <div class="inline-edit-container">
                                    <div class="inline-edit-header">
                                        <h4><i class="fas fa-edit" style="margin-right: 0.5rem; color: #3b82f6;"></i> Editing Invoice: {{ $invoice->invoice_number }}</h4>
                                        <button type="button" class="cancel-edit-btn" onclick="toggleInlineEdit({{ $invoice->invoiceid }})">
                                            <i class="fas fa-times" style="margin-right: 0.5rem;"></i> Cancel
                                        </button>
                                    </div>
                                    <div id="inline-edit-content-{{ $invoice->invoiceid }}" style="text-align: center; padding: 2rem;">
                                        <p style="color: #64748b;">Loading editor...</p>
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
        <div style="padding: 3rem; text-align: center; color: #94a3b8;">
            <i class="fas fa-file-invoice" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.3;"></i>
            <p style="margin: 0; font-size: 0.95rem; font-weight: 500;">No invoices found</p>
            <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">Create your first invoice to get started.</p>
        </div>
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
            // Show the row
            row.classList.add('active');

            // Load content if not already loaded
            if (!inlineEditState[invoiceId]) {
                contentDiv.innerHTML = '<p style="color: #64748b; text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin" style="margin-right: 0.5rem;"></i> Loading editor...</p>';

                try {
                    const response = await fetch(`/invoices/${invoiceId}/edit?inline=1`);
                    const html = await response.text();
                    contentDiv.innerHTML = html;
                    inlineEditState[invoiceId] = true;

                    // Initialize the edit form
                    initializeInlineEditForm(invoiceId);
                } catch (error) {
                    contentDiv.innerHTML = '<p style="color: #ef4444; text-align: center; padding: 2rem;">Error loading editor. Please try again.</p>';
                    console.error('Error loading editor:', error);
                }
            }
        } else {
            // Hide the row
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
                        // Reload the page to show updated data
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
