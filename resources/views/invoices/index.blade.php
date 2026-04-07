@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">All Invoices</h3>
        </div>
        <a href="{{ route('invoices.create') }}" class="primary-button">Create Invoice</a>
    </section>

    <section class="panel-card" style="padding: 0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 13%;">Invoice #</th>
                    <th style="width: 16%;">Client</th>
                    <th style="width: 9%;">Type</th>
                    <th style="width: 11%;">For</th>
                    <th style="width: 11%;">Amount</th>
                    <th style="width: 10%;">End Date</th>
                    <th style="width: 9%;">Status</th>
                    <th style="width: 11%;">Actions</th>
                </tr>
            </thead>
            <tbody>
            @php
                $allInvoices = \App\Models\Invoice::with('client', 'payments', 'items')->latest()->take(50)->get();
            @endphp
            @forelse ($allInvoices as $invoice)
                @php
                    $amountPaid = $invoice->payments->sum('amount');
                    $balanceDue = $invoice->grand_total - $amountPaid;
                    $paymentStatus = $balanceDue <= 0 ? 'paid' : ($amountPaid > 0 ? 'partial' : 'pending');
                    
                    // Get the latest end_date from items
                    $latestEndDate = $invoice->items->max('end_date');
                    $isExpired = $latestEndDate && $latestEndDate < now();
                    
                    // Get client currency
                    $currency = $invoice->client->currency ?? 'INR';
                @endphp
                <tr>
                    <td>
                        <strong style="font-size: 0.9rem;">{{ $invoice->invoice_number ?? 'INV-' . str_pad($invoice->invoiceid, 4, '0', STR_PAD_LEFT) }}</strong>
                    </td>
                    <td>
                        <div style="font-size: 0.85rem;">{{ $invoice->client->business_name ?? $invoice->client->contact_name ?? 'N/A' }}</div>
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
                                    <i class="fas fa-exclamation-circle" style="margin-left: 0.25rem; color: #ef4444;" title="Expired"></i>
                                @endif
                            </span>
                        @else
                            <span style="font-size: 0.85rem; color: #94a3b8;">-</span>
                        @endif
                    </td>
                    <td>
                        <span class="status-pill {{ $paymentStatus }}">{{ ucfirst($paymentStatus) }}</span>
                    </td>
                    <td class="table-actions">
                        <a href="{{ route('invoices.show', $invoice->invoiceid) }}" class="icon-action-btn view" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('invoices.edit', $invoice->invoiceid) }}" class="icon-action-btn edit" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('invoices.destroy', $invoice->invoiceid) }}" class="inline-delete" onsubmit="return confirm('Delete this invoice?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="icon-action-btn delete" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="padding: 3rem; text-align: center; color: #94a3b8;">
                        <i class="fas fa-file-invoice" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.3;"></i>
                        <p style="margin: 0; font-size: 0.95rem; font-weight: 500;">No invoices found</p>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">Create your first invoice to get started.</p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endsection
