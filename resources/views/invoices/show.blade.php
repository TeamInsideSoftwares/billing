@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <a href="{{ route('invoices.index') }}" class="text-link" style="margin-bottom: 1rem; display: inline-block;">← Back to Invoices</a>
        <p class="eyebrow">{{ $invoice->invoice_number }}</p>
        <p style="font-size: 1.1em; margin-bottom: 0.75rem;">
                {{ $invoice->client->business_name ?? $invoice->client->contact_name ?? 'N/A' }}
        </p>
    </div>
    <div>
        <a href="{{ route('invoices.edit', $invoice) }}" class="primary-button">Edit</a>
        <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" class="inline-delete" onsubmit="return confirm('Delete this invoice?')">
            @csrf @method('DELETE')
            <button type="submit" class="danger-button">Delete</button>
        </form>
    </div>
</section>

<section class="panel-card">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 2rem;">
        <div>
            <h1 style="margin: 0 0 0.5rem 0; font-size: 1.5em;">{{ $invoice->invoice_number }}</h1>
            <span class="status-pill {{ strtolower($invoice->status ?? 'draft') }}">{{ ucfirst($invoice->status ?? 'Draft') }}</span>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 1.5em; font-weight: bold; margin-bottom: 0.25rem;">Rs {{ number_format($invoice->grand_total ?? 0, 2) }}</div>
            <div>{{ $invoice->issue_date?->format('d M Y') }} due {{ $invoice->due_date?->format('d M Y') }}</div>
        </div>
    </div>
</section>

<section class="panel-card" style="margin-top: 10px;">
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
                    {{ number_format($item->quantity, 2) }}
                </td>
                <td style="padding: 0.75rem 0.5rem 0.75rem 0; text-align: right;">
                    Rs {{ number_format($item->unit_price, 2) }}
                </td>
                <td style="padding: 0.75rem 0.5rem 0.75rem 0; text-align: right; font-weight: 600;">
                    Rs {{ number_format($item->line_total, 2) }}
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
                <td style="padding: 1rem 0.5rem; text-align: right;">Rs {{ number_format($invoice->subtotal ?? 0, 2) }}</td>
            </tr>
            <tr style="font-weight: 600;">
                <td colspan="3" style="padding: 0.5rem 0.5rem 1rem 0.5rem; text-align: right;">Tax:</td>
                <td style="padding: 0.5rem 0.5rem 1rem 0.5rem; text-align: right;">Rs {{ number_format($invoice->tax_total ?? 0, 2) }}</td>
            </tr>
            <tr style="background: #f8fafc; font-size: 1.1em; font-weight: bold;">
                <td colspan="3" style="padding: 1rem 0.5rem; text-align: right;">Grand Total:</td>
                <td style="padding: 1rem 0.5rem; text-align: right;">Rs {{ number_format($invoice->grand_total ?? 0, 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</section>

@if($invoice->payments->count())
<section class="panel-card">
    <h3>Payments ({{ $invoice->payments->count() }})</h3>
    <div class="table-list">
        @foreach($invoice->payments->take(5) as $payment)
        <div class="table-row">
            <div><strong>{{ $payment->reference ?? 'Payment' }}</strong></div>
            <div>Rs {{ number_format($payment->amount) }}</div>
            <div>{{ $payment->paid_at }} - {{ $payment->method }}</div>
        </div>
        @endforeach
    </div>
</section>
@endif
@endsection

