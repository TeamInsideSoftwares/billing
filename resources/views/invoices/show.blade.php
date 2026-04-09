@extends('layouts.app')

@section('content')
<h3 style="margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">Invoice Details</h3>

<section class="section-bar">
    <div>
        <p style="font-size: 1.1em; margin-bottom: 0.75rem;">
            <b>Client Name: </b>{{ $invoice->client->business_name ?? $invoice->client->contact_name ?? 'N/A' }}
        </p>
        <a href="{{ route('invoices.index') }}" class="text-link">← Back to Invoices</a>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
        <button type="button" id="togglePreviewBtn" onclick="togglePreview()" class="primary-button" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border: none; padding: 0.65rem 1.2rem; font-size: 0.85rem; font-weight: 600; color: #ffffff; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-eye"></i> Show PDF Preview
        </button>
        @if($invoice->isProforma() && !$invoice->convertedTaxInvoice)
            <form method="POST" action="{{ route('invoices.convert-to-tax', $invoice) }}" onsubmit="return confirm('Are you sure you want to convert this proforma invoice to a tax invoice? A new tax invoice will be created.')">
                @csrf
                <button type="submit" class="primary-button" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: none; padding: 0.65rem 1.2rem; font-size: 0.85rem; font-weight: 600; color: #ffffff; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-file-invoice-dollar"></i> Convert to Tax Invoice
                </button>
            </form>
        @elseif($invoice->isProforma() && $invoice->convertedTaxInvoice)
            <a href="{{ route('invoices.show', $invoice->convertedTaxInvoice) }}" class="primary-button" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; padding: 0.65rem 1.2rem; font-size: 0.85rem; font-weight: 600; color: #ffffff; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-link"></i> View Tax Invoice
            </a>
        @endif
        <a href="{{ route('invoices.edit', $invoice) }}" class="icon-action-btn edit" title="Edit" style="width: 36px; height: 36px; font-size: 1rem;">
            <i class="fas fa-edit"></i>
        </a>
        <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" class="inline-delete" onsubmit="return confirm('Delete this invoice?')">
            @csrf @method('DELETE')
            <button type="submit" class="icon-action-btn delete" title="Delete" style="width: 36px; height: 36px; font-size: 1rem;">
                <i class="fas fa-trash"></i>
            </button>
        </form>
    </div>
</section>

@if($invoice->isProforma() && $invoice->convertedTaxInvoice)
    <div style="margin-bottom: 1rem; padding: 0.9rem 1rem; border: 1px solid #a7f3d0; background: #ecfdf5; color: #065f46; border-radius: 10px;">
        This proforma invoice has already been converted to tax invoice
        <a href="{{ route('invoices.show', $invoice->convertedTaxInvoice) }}" style="font-weight: 600; color: inherit;">{{ $invoice->convertedTaxInvoice->invoice_number }}</a>.
    </div>
@endif

<!-- PDF Preview Section -->
<div id="invoicePreviewSection" style="display: none; margin-bottom: 1.5rem;">
    <div style="background: #f3f4f6; padding: 1rem; border-radius: 12px; margin-bottom: 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
            <h4 style="margin: 0; font-size: 1rem; color: #374151; font-weight: 600;">
                <i class="fas fa-file-pdf" style="color: #ef4444; margin-right: 0.5rem;"></i>
                Invoice Preview (How it will appear on PDF)
            </h4>
            <button type="button" onclick="window.print()" class="primary-button" style="background: #2563eb; border: none; padding: 0.5rem 1rem; font-size: 0.8rem; border-radius: 8px; cursor: pointer;">
                <i class="fas fa-print"></i> Print / Save as PDF
            </button>
        </div>
    </div>

    <!-- Actual Invoice Preview -->
    <div id="printableInvoice" style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 900px; margin: 0 auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
        <!-- Invoice Header -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 3px solid #2563eb;">
            <!-- Company Info -->
            <div style="flex: 1;">
                @if($account?->logo_path)
                    <div style="margin-bottom: 1rem;">
                        <img src="{{ asset($account->logo_path) }}" alt="Company Logo" style="max-width: 180px; max-height: 70px; object-fit: contain;">
                    </div>
                @endif
                <h2 style="margin: 0 0 0.5rem 0; font-size: 1.5rem; color: #1e293b;">{{ $accountBillingDetail->billing_name ?? $account->name ?? 'Company Name' }}</h2>
                @if($accountBillingDetail?->address)
                    <p style="margin: 0.2rem 0; font-size: 0.85rem; color: #64748b;">{{ $accountBillingDetail->address }}</p>
                @endif
                @if($accountBillingDetail?->city || $accountBillingDetail?->state || $accountBillingDetail?->country)
                    <p style="margin: 0.2rem 0; font-size: 0.85rem; color: #64748b;">
                        {{ $accountBillingDetail->city }}{{ $accountBillingDetail->state ? ', ' . $accountBillingDetail->state : '' }}
                        @if($accountBillingDetail?->postal_code) - {{ $accountBillingDetail->postal_code }}@endif
                    </p>
                @endif
                @if($accountBillingDetail?->country)
                    <p style="margin: 0.2rem 0; font-size: 0.85rem; color: #64748b;">{{ $accountBillingDetail->country }}</p>
                @endif
                @if($accountBillingDetail?->gstin)
                    <p style="margin: 0.5rem 0 0.2rem 0; font-size: 0.85rem;"><strong style="color: #374151;">GSTIN:</strong> {{ $accountBillingDetail->gstin }}</p>
                @endif
                @if($accountBillingDetail?->tin)
                    <p style="margin: 0.2rem 0; font-size: 0.85rem;"><strong style="color: #374151;">TIN:</strong> {{ $accountBillingDetail->tin }}</p>
                @endif
            </div>

            <!-- Invoice Details -->
            <div style="text-align: right; min-width: 250px;">
                <h1 style="margin: 0 0 0.5rem 0; font-size: 2rem; color: #2563eb; font-weight: 700; text-transform: uppercase;">
                    {{ strtoupper($invoice->invoice_type ?? 'proforma') }} INVOICE
                </h1>
                <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                    <p style="margin: 0.3rem 0; font-size: 0.9rem;"><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
                    <p style="margin: 0.3rem 0; font-size: 0.9rem;"><strong>Issue Date:</strong> {{ $invoice->issue_date?->format('d M Y') }}</p>
                    <p style="margin: 0.3rem 0; font-size: 0.9rem;"><strong>Due Date:</strong> {{ $invoice->due_date?->format('d M Y') }}</p>
                    <p style="margin: 0.3rem 0; font-size: 0.9rem;"><strong>Status:</strong> <span style="text-transform: uppercase; font-weight: 600;">{{ $invoice->status }}</span></p>
                </div>
            </div>
        </div>

        <!-- Bill To Section -->
        <div style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border-left: 4px solid #2563eb;">
            <h3 style="margin: 0 0 1rem 0; font-size: 1rem; color: #374151; text-transform: uppercase; letter-spacing: 0.05em;">Bill To</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    @if($invoice->client->business_name)
                        <h4 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; color: #1e293b;">{{ $invoice->client->business_name }}</h4>
                    @endif
                    @if($invoice->client->contact_name)
                        <p style="margin: 0.3rem 0; font-size: 0.9rem; color: #64748b;"><strong>Contact:</strong> {{ $invoice->client->contact_name }}</p>
                    @endif
                    @if($invoice->client->email)
                        <p style="margin: 0.3rem 0; font-size: 0.9rem; color: #64748b;"><strong>Email:</strong> {{ $invoice->client->email }}</p>
                    @endif
                    @if($invoice->client->phone)
                        <p style="margin: 0.3rem 0; font-size: 0.9rem; color: #64748b;"><strong>Phone:</strong> {{ $invoice->client->phone }}</p>
                    @endif
                </div>
                <div>
                    @if($invoice->client->billingDetail)
                        @if($invoice->client->billingDetail->business_name)
                            <p style="margin: 0.3rem 0; font-size: 0.9rem; font-weight: 600;">{{ $invoice->client->billingDetail->business_name }}</p>
                        @endif
                        @if($invoice->client->billingDetail->address_line_1)
                            <p style="margin: 0.3rem 0; font-size: 0.9rem; color: #64748b;">{{ $invoice->client->billingDetail->address_line_1 }}</p>
                        @endif
                        @if($invoice->client->billingDetail->city || $invoice->client->billingDetail->state)
                            <p style="margin: 0.3rem 0; font-size: 0.9rem; color: #64748b;">
                                {{ $invoice->client->billingDetail->city }}{{ $invoice->client->billingDetail->state ? ', ' . $invoice->client->billingDetail->state : '' }}
                                @if($invoice->client->billingDetail->postal_code) - {{ $invoice->client->billingDetail->postal_code }}@endif
                            </p>
                        @endif
                        @if($invoice->client->billingDetail->country)
                            <p style="margin: 0.3rem 0; font-size: 0.9rem; color: #64748b;">{{ $invoice->client->billingDetail->country }}</p>
                        @endif
                        @if($invoice->client->billingDetail->gstin)
                            <p style="margin: 0.5rem 0 0.3rem 0; font-size: 0.9rem;"><strong>GSTIN:</strong> {{ $invoice->client->billingDetail->gstin }}</p>
                        @endif
                    @else
                        <p style="color: #94a3b8; font-size: 0.85rem; font-style: italic;">No billing details available</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Items Table -->
        @if($invoice->items->count())
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; font-size: 0.9rem;">
            <thead>
                <tr style="background: #2563eb; color: white;">
                    <th style="padding: 0.75rem 0.5rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2);">#</th>
                    <th style="padding: 0.75rem 0.5rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2);">Item Description</th>
                    <th style="padding: 0.75rem 0.5rem; text-align: center; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2);">Qty</th>
                    <th style="padding: 0.75rem 0.5rem; text-align: right; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2);">Unit Price</th>
                    <th style="padding: 0.75rem 0.5rem; text-align: right; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2);">Tax</th>
                    <th style="padding: 0.75rem 0.5rem; text-align: right; font-weight: 600;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr style="border-bottom: 1px solid #e5e7eb; {{ $index % 2 == 0 ? 'background: #f8fafc;' : '' }}">
                    <td style="padding: 0.75rem 0.5rem; border-right: 1px solid #e5e7eb;">{{ $index + 1 }}</td>
                    <td style="padding: 0.75rem 0.5rem; border-right: 1px solid #e5e7eb;">
                        <strong>{{ $item->item_name }}</strong>
                        @if($item->item_description)
                            <div style="font-size: 0.8rem; color: #64748b; margin-top: 0.25rem;">{{ Str::limit($item->item_description, 80) }}</div>
                        @endif
                        @if($item->frequency && $item->duration)
                            <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">
                                {{ ucfirst($item->frequency) }} × {{ $item->duration }}
                                @if($item->start_date) | {{ $item->start_date->format('d M Y') }} to {{ $item->end_date?->format('d M Y') }}@endif
                            </div>
                        @endif
                    </td>
                    <td style="padding: 0.75rem 0.5rem; text-align: center; border-right: 1px solid #e5e7eb;">{{ number_format($item->quantity, 2) }}</td>
                    <td style="padding: 0.75rem 0.5rem; text-align: right; border-right: 1px solid #e5e7eb;">{{ $invoice->currency_code ?? 'INR' }} {{ number_format($item->unit_price, 2) }}</td>
                    <td style="padding: 0.75rem 0.5rem; text-align: right; border-right: 1px solid #e5e7eb;">{{ number_format($item->tax_rate, 2) }}%</td>
                    <td style="padding: 0.75rem 0.5rem; text-align: right; font-weight: 600;">{{ $invoice->currency_code ?? 'INR' }} {{ number_format($item->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div style="display: flex; justify-content: flex-end;">
            <div style="min-width: 320px; background: #f8fafc; padding: 1.5rem; border-radius: 8px; border: 2px solid #2563eb;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; font-size: 0.95rem;">
                    <span style="color: #64748b;">Subtotal</span>
                    <strong>{{ $invoice->currency_code ?? 'INR' }} {{ number_format($invoice->subtotal ?? 0, 2) }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; font-size: 0.95rem;">
                    <span style="color: #64748b;">Tax</span>
                    <strong>{{ $invoice->currency_code ?? 'INR' }} {{ number_format($invoice->tax_total ?? 0, 2) }}</strong>
                </div>
                @if($invoice->discount_total > 0)
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; font-size: 0.95rem; color: #16a34a;">
                    <span>Discount</span>
                    <strong>-{{ $invoice->currency_code ?? 'INR' }} {{ number_format($invoice->discount_total ?? 0, 2) }}</strong>
                </div>
                @endif
                <div style="display: flex; justify-content: space-between; margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #2563eb; font-size: 1.2rem; font-weight: 700; color: #2563eb;">
                    <span>Grand Total</span>
                    <span>{{ $invoice->currency_code ?? 'INR' }} {{ number_format($invoice->grand_total ?? 0, 2) }}</span>
                </div>
                @if($invoice->amount_paid > 0)
                <div style="display: flex; justify-content: space-between; margin-top: 0.75rem; font-size: 0.9rem; color: #16a34a;">
                    <span>Amount Paid</span>
                    <strong>-{{ $invoice->currency_code ?? 'INR' }} {{ number_format($invoice->amount_paid ?? 0, 2) }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 0.5rem; font-size: 0.95rem; color: #dc2626; font-weight: 600;">
                    <span>Balance Due</span>
                    <span>{{ $invoice->currency_code ?? 'INR' }} {{ number_format($invoice->balance_due ?? 0, 2) }}</span>
                </div>
                @endif
            </div>
        </div>
        @else
        <div style="text-align: center; padding: 3rem; color: #94a3b8; font-style: italic; border: 2px dashed #e5e7eb; border-radius: 8px; margin-bottom: 2rem;">
            No items in this invoice
        </div>
        @endif

        <!-- Notes -->
        @if($invoice->notes)
        <div style="margin-top: 2rem; padding: 1.5rem; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px;">
            <h4 style="margin: 0 0 0.75rem 0; font-size: 0.95rem; color: #92400e; font-weight: 600;">Notes</h4>
            <p style="margin: 0; font-size: 0.9rem; color: #78350f; line-height: 1.6;">{{ $invoice->notes }}</p>
        </div>
        @endif

        <!-- Terms & Conditions -->
        @php
            $terms = $account?->termsConditions()->where('is_active', true)->orderBy('sequence')->get();
        @endphp
        @if($terms?->count())
        <div style="margin-top: 2rem; padding: 1.5rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
            <h4 style="margin: 0 0 1rem 0; font-size: 0.95rem; color: #374151; font-weight: 600;">Terms & Conditions</h4>
            <ol style="margin: 0; padding-left: 1.5rem; font-size: 0.85rem; color: #64748b; line-height: 1.8;">
                @foreach($terms as $term)
                    <li>{{ $term->term }}</li>
                @endforeach
            </ol>
        </div>
        @endif

        <!-- Footer / Signatory -->
        @if($accountBillingDetail?->authorize_signatory)
        <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #e5e7eb; text-align: right;">
            <div style="display: inline-block;">
                @if($accountBillingDetail?->signature_upload)
                    <div style="margin-bottom: 0.5rem;">
                        <img src="{{ asset($accountBillingDetail->signature_upload) }}" alt="Signature" style="max-width: 150px; max-height: 60px; object-fit: contain;">
                    </div>
                @endif
                <div style="border-top: 2px solid #374151; padding-top: 0.5rem; margin-top: 0.5rem;">
                    <p style="margin: 0; font-size: 0.9rem; font-weight: 600; color: #1e293b;">{{ $accountBillingDetail->authorize_signatory }}</p>
                    <p style="margin: 0.2rem 0 0 0; font-size: 0.8rem; color: #64748b;">Authorized Signatory</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Footer -->
        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid #e5e7eb; text-align: center; font-size: 0.75rem; color: #94a3b8;">
            @if($accountBillingDetail?->billing_from_email)
                <p style="margin: 0.2rem 0;"><strong>Email:</strong> {{ $accountBillingDetail->billing_from_email }}</p>
            @endif
            <p style="margin: 0.5rem 0 0 0;">Generated on {{ now()->format('d M Y, h:i A') }}</p>
        </div>
    </div>
</div>

<section class="panel-card">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 2rem;">
        <div>
            <h1 style="margin: 0 0 0.5rem 0; font-size: 1.5em;">{{ $invoice->invoice_number }}</h1>
            <div style="margin-top: 0.5rem;">
                <span style="display: inline-block; padding: 0.3rem 0.7rem; background: {{ strtolower($invoice->invoice_type ?? 'proforma') === 'tax' ? '#fef3c7' : '#dbeafe' }}; color: {{ strtolower($invoice->invoice_type ?? 'proforma') === 'tax' ? '#92400e' : '#1e40af' }}; border-radius: 6px; font-size: 0.8rem; font-weight: 600;">
                    {{ ucfirst($invoice->invoice_type ?? 'proforma') }} Invoice
                </span>
            </div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 1.5em; font-weight: bold; margin-bottom: 0.25rem;">Rs {{ number_format($invoice->grand_total ?? 0, 0) }}</div>
            <div>{{ $invoice->issue_date?->format('d M Y') }} due {{ $invoice->due_date?->format('d M Y') }}</div>
        </div>
    </div>
</section>

<section class="panel-card" style="margin-top: 0.5rem;">
    <h3 style="margin-top: 0; font-size: 1em; padding: 5px; color: #374151;">Details</h3>
    <hr style="border: none; border-top: 1px solid #e5e7eb; margin-bottom: 1rem;">

    <div style="display: flex; justify-content: space-between; gap: 2rem;">

        <!-- Client Info -->
        <div style="flex: 1;">
            @if($invoice->client->email)
            <p style="margin-bottom: 0.75rem;">
                <span style="display: block; color: #6b7280; font-weight: 600; font-size: 0.85em;">
                    Email
                </span>
                <span>{{ $invoice->client->email }}</span>
            </p>
            @endif

            @if($invoice->client->phone)
            <p style="margin-bottom: 0.75rem;">
                <span style="display: block; color: #6b7280; font-weight: 600; font-size: 0.85em;">
                    Phone
                </span>
                <span>{{ $invoice->client->phone }}</span>
            </p>
            @endif
        </div>

        <!-- Invoice Info -->
        <div style="flex: 1;">
            <p style="margin-bottom: 0.75rem;">
                <span style="display: block; color: #6b7280; font-weight: 600; font-size: 0.85em;">
                    Issue Date
                </span>
                <span>{{ $invoice->issue_date?->format('d M Y') }}</span>
            </p>
            <p style="margin-bottom: 0.75rem;">
                <span style="display: block; color: #6b7280; font-weight: 600; font-size: 0.85em;">
                    Due Date
                </span>
                <span>{{ $invoice->due_date?->format('d M Y') }}</span>
            </p>
            <p style="margin-bottom: 0.75rem;">
                <span style="display: block; color: #6b7280; font-weight: 600; font-size: 0.85em;">
                    Status
                </span>
                <span class="status-pill {{ strtolower($invoice->status) }}">
                    {{ ucfirst($invoice->status) }}
                </span>
            </p>

            @if($invoice->notes)
           <p style="margin-bottom: 0.75rem;">
                <span style="display: block; color: #6b7280; font-weight: 600; font-size: 0.85em;">
                    Notes
                </span>
                <span>{{ $invoice->notes }}</span>
            </p>
            @endif
        </div>
    </div>
</section>
    </section>

Invoice Items Table
    <section class="panel-card" style="max-width: none;">
    <table style="width: 100%; border-collapse: collapse; font-size: 0.95em;">
        <thead>
            <tr style="background: #f8fafc; border-bottom: 2px solid #e5e7eb;">
                <th style="padding: 1rem 0.5rem 0.5rem 0; text-align: left; font-weight: 600;">Item</th>
                <th style="padding: 1rem 0.5rem 0.5rem 0; text-align: right; width: 80px; font-weight: 600;">Qty</th>
                <th style="padding: 1rem 0.5rem 0.5rem 0; text-align: right; width: 100px; font-weight: 600;">Unit Price</th>
                <th style="padding: 1rem 0.5rem 0.5rem 0; text-align: right; width: 100px; font-weight: 600;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $item)
            <tr style="border-bottom: 1px solid #f3f4f6;">
                <td style="padding: 0.75rem 0.5rem 0.75rem 0; vertical-align: top;">
                    <strong style="font-size: 1em;">{{ $item->item_name }}</strong>
                    @if($item->item_description)
                    <div style="font-size: 0.85em; color: #6b7280; margin-top: 0.25rem;">{{ Str::limit($item->item_description, 100) }}</div>
                    @endif
                </td>
                <td style="padding: 0.75rem 0.5rem 0.75rem 0; text-align: right;">
                    {{ number_format($item->quantity, 0) }}
                </td>
                <td style="padding: 0.75rem 0.5rem 0.75rem 0; text-align: right;">
                    Rs {{ number_format($item->unit_price, 0) }}
                </td>
                <td style="padding: 0.75rem 0.5rem 0.75rem 0; text-align: right; font-weight: 600;">
                    Rs {{ number_format($item->line_total, 0) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="padding: 2rem; text-align: center; color: #9ca3af; font-style: italic;">
                    No items added to this invoice.
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($invoice->items->count())
        <tfoot>
            <tr style="border-top: 2px solid #e5e7eb; font-weight: 600;">
                <td colspan="3" style="padding: 1rem 0.5rem; text-align: right;">Subtotal:</td>
                <td style="padding: 1rem 0.5rem; text-align: right;">Rs {{ number_format($invoice->subtotal ?? 0, 0) }}</td>
            </tr>
            <tr style="font-weight: 600;">
                <td colspan="3" style="padding: 0.5rem 0.5rem 1rem 0.5rem; text-align: right;">Tax:</td>
                <td style="padding: 0.5rem 0.5rem 1rem 0.5rem; text-align: right;">Rs {{ number_format($invoice->tax_total ?? 0, 0) }}</td>
            </tr>
            <tr style="background: #f8fafc; font-size: 1.1em; font-weight: bold;">
                <td colspan="3" style="padding: 1rem 0.5rem; text-align: right;">Grand Total:</td>
                <td style="padding: 1rem 0.5rem; text-align: right;">Rs {{ number_format($invoice->grand_total ?? 0, 0) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</section>

@if($invoice->payments->count())
<section class="panel-card" style="margin-top: 1rem;">
    <h3 style="margin-top: 0; font-size: 1.1em; color: #374151;">Payments received ({{ $invoice->payments->count() }})</h3>
    <table class="data-table" style="width: 100%; margin-top: 1rem;">
        <thead>
            <tr style="text-align: left; border-bottom: 2px solid #e5e7eb;">
                <th style="padding: 0.75rem 0.5rem;">Date</th>
                <th style="padding: 0.75rem 0.5rem;">Method</th>
                <th style="padding: 0.75rem 0.5rem;">Reference</th>
                <th style="padding: 0.75rem 0.5rem; text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->payments as $payment)
            <tr style="border-bottom: 1px solid #f3f4f6;">
                <td style="padding: 0.75rem 0.5rem;">{{ $payment->paid_at instanceof \DateTime ? $payment->paid_at->format('d M Y') : $payment->paid_at }}</td>
                <td style="padding: 0.75rem 0.5rem;">{{ $payment->method }}</td>
                <td style="padding: 0.75rem 0.5rem;">{{ $payment->reference ?? 'N/A' }}</td>
                <td style="padding: 0.75rem 0.5rem; text-align: right;"><strong>Rs {{ number_format($payment->amount, 0) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot style="border-top: 2px solid #e5e7eb;">
            <tr>
                <td colspan="3" style="padding: 1rem 0.5rem 0.5rem; text-align: right;"><strong>Total Paid:</strong></td>
                <td style="padding: 1rem 0.5rem 0.5rem; text-align: right;"><strong>Rs {{ number_format($invoice->payments->sum('amount'), 0) }}</strong></td>
            </tr>
            <tr>
                <td colspan="3" style="padding: 0.5rem; text-align: right;"><strong>Balance Due:</strong></td>
                <td style="padding: 0.5rem; text-align: right; color: #ef4444;"><strong>Rs {{ number_format($invoice->balance_due, 0) }}</strong></td>
            </tr>
        </tfoot>
    </table>
</section>
@else
<section class="panel-card" style="margin-top: 2rem;">
    <div style="text-align: center; padding: 2rem; color: #6b7280;">
        <p style="margin-bottom: 1rem;">No payments recorded for this invoice yet.</p>
        @if($invoice->isProforma())
            <p style="margin: 0; color: #92400e;">Convert this proforma invoice to a tax invoice before recording payment.</p>
        @else
            <a href="{{ route('payments.create', ['invoiceid' => $invoice->invoiceid, 'clientid' => $invoice->clientid, 'amount' => $invoice->balance_due]) }}" class="primary-button" style="text-decoration: none; display: inline-block;">Record a payment</a>
        @endif
    </div>
</section>
@endif

<script>
function togglePreview() {
    const previewSection = document.getElementById('invoicePreviewSection');
    const toggleBtn = document.getElementById('togglePreviewBtn');
    
    if (previewSection.style.display === 'none' || previewSection.style.display === '') {
        previewSection.style.display = 'block';
        toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i> Hide PDF Preview';
        toggleBtn.style.background = 'linear-gradient(135deg, #64748b 0%, #475569 100%)';
        previewSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        previewSection.style.display = 'none';
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i> Show PDF Preview';
        toggleBtn.style.background = 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)';
    }
}
</script>

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #printableInvoice, #printableInvoice * {
        visibility: visible !important;
    }
    #printableInvoice {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 20px;
        margin: 0;
        box-shadow: none;
        background: white;
    }
    .no-print {
        display: none !important;
    }
}
</style>
@endsection
