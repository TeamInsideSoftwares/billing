@extends('layouts.app')

@section('content')
@php
    $normalizeTaxState = static fn ($value) => preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $value)));
    $clientState = $normalizeTaxState($invoice->client->state ?? '');
    $accountState = $normalizeTaxState($account->state ?? '');
    $sameStateGst = $clientState !== '' && $accountState !== '' && $clientState === $accountState;
    $itemsSubtotal = (float) $invoice->items->sum(function ($item) {
        return (float) ($item->line_total ?? 0);
    });
    $itemsDiscountTotal = (float) $invoice->items->sum(function ($item) {
        $lineTotal = (float) ($item->line_total ?? 0);
        $discountPercent = (float) ($item->discount_percent ?? 0);
        $discountAmount = isset($item->discount_amount)
            ? (float) ($item->discount_amount ?? 0)
            : floor(max(0, $lineTotal * ($discountPercent / 100)));
        return max(0, $discountAmount);
    });
    $invoiceTaxTotal = (float) $invoice->items->sum(function ($item) {
        $lineTotal = (float) ($item->line_total ?? 0);
        $discountPercent = (float) ($item->discount_percent ?? 0);
        $discountAmount = isset($item->discount_amount)
            ? (float) ($item->discount_amount ?? 0)
            : floor(max(0, $lineTotal * ($discountPercent / 100)));
        $taxableAmount = max(0, $lineTotal - max(0, $discountAmount));
        return ceil($taxableAmount * ((float) ($item->tax_rate ?? 0) / 100));
    });
    $invoiceGrandTotal = max(0, $itemsSubtotal - $itemsDiscountTotal + $invoiceTaxTotal);
    $totalPaidAmount = (float) $invoice->payments->sum('amount');
    $balanceDueAmount = max(0, $invoiceGrandTotal - $totalPaidAmount);
    $cgstAmount = $sameStateGst ? round($invoiceTaxTotal / 2, 0) : 0;
    $sgstAmount = $sameStateGst ? round($invoiceTaxTotal / 2, 0) : 0;
    $igstAmount = $sameStateGst ? 0 : round($invoiceTaxTotal, 0);

    $signatureUploadPath = optional($accountBillingDetail)->signature_upload;
    $signatureUploadUrl = null;
    if (!empty($signatureUploadPath)) {
        if (str_starts_with($signatureUploadPath, 'http://') || str_starts_with($signatureUploadPath, 'https://')) {
            $signatureUploadUrl = $signatureUploadPath;
        } else {
            $signatureUploadUrl = asset(str_starts_with($signatureUploadPath, 'storage/') ? $signatureUploadPath : 'storage/' . ltrim($signatureUploadPath, '/'));
        }
    }

    $documentType = !empty(trim($invoice->ti_number ?? '')) ? 'Tax Invoice' : 'Proforma Invoice';
@endphp
@section('header_actions')
    <a href="{{ route('invoices.index', request('c') ? ['c' => request('c')] : []) }}" class="secondary-button">
        <i class="fas fa-arrow-left icon-spaced"></i>Back to Invoices
    </a>
    @if(($invoice->status ?? '') !== 'cancelled')
        <a href="{{ route('invoices.pdf', $invoice) }}" class="secondary-button small" target="_blank">
            <i class="fas fa-file-pdf icon-spaced-sm"></i>View PDF
        </a>
        @if(empty(trim($invoice->ti_number ?? '')))
            <form method="POST" action="{{ route('invoices.create-tax-invoice') }}" class="inline-delete" onsubmit="return confirm('Convert this Proforma to Tax Invoice? This will generate a Tax Invoice number.')">
                @csrf
                <input type="hidden" name="invoiceid" value="{{ $invoice->invoiceid }}">
                <button type="submit" class="primary-button small btn-success-solid">
                    <i class="fas fa-check-double icon-spaced-sm"></i>Convert to Tax Invoice
                </button>
            </form>
        @endif
    <a href="{{ route('invoices.create', [
        'step' => ($invoice->invoice_for ?? 'orders') === 'without_orders' ? 2 : 3,
        'invoice_for' => $invoice->invoice_for ?? 'orders',
        'c' => request('c', $invoice->clientid),
        'd' => $invoice->invoiceid,
        'o' => ($invoice->invoice_for ?? '') === 'orders' ? ($invoice->orderid ?? null) : null,
        'tax_invoice' => !empty($invoice->ti_number) ? 1 : null,
    ]) }}" class="primary-button small">
        <i class="fas fa-edit icon-spaced-sm"></i>Edit
    </a>
    <form method="POST" action="{{ route('invoices.destroy', [$invoice, 'c' => request('c')]) }}" class="inline-delete" onsubmit="return confirm('Cancel this invoice?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="secondary-button">
            <i class="fas fa-ban icon-spaced-sm"></i>Cancel Invoice
        </button>
    </form>
    @endif
@endsection
<section class="panel-card">
    <div class="invoice-show-head">
        <div>
            <h1 class="invoice-show-number">{{ $invoice->invoice_number }}</h1>
            <div class="mt-2">
                <span class="invoice-doc-badge">
                    {{ $documentType }}
                </span>
            </div>
        </div>
        <div class="text-right">
            <div class="invoice-show-amount">{{ number_format($invoiceGrandTotal, 0) }}</div>
            <div>{{ $invoice->issue_date?->format('d M Y') }} due {{ $invoice->due_date?->format('d M Y') }}</div>
        </div>
    </div>
</section>

<section class="panel-card mt-2">
    <h3 class="section-title section-title-sm">Details</h3>
    <hr class="section-separator">

    <div class="invoice-details-grid">

        <!-- Client Info -->
        <div class="flex-fill">
            @if($invoice->client->email)
            <p class="mb-3">
                <span class="info-key">
                    Email
                </span>
                <span>{{ $invoice->client->email }}</span>
            </p>
            @endif

            @if($invoice->client->phone)
            <p class="mb-3">
                <span class="info-key">
                    Phone
                </span>
                <span>{{ $invoice->client->phone }}</span>
            </p>
            @endif
        </div>

        <!-- Invoice Info -->
        <div class="flex-fill">
            <p class="mb-3">
                <span class="info-key">
                    Issue Date
                </span>
                <span>{{ $invoice->issue_date?->format('d M Y') }}</span>
            </p>
            <p class="mb-3">
                <span class="info-key">
                    Due Date
                </span>
                <span>{{ $invoice->due_date?->format('d M Y') }}</span>
            </p>
            <p class="mb-3">
                <span class="info-key">
                    Status
                </span>
                <span class="status-pill {{ ($invoice->status ?? '') === 'cancelled' ? 'status-pill-cancelled' : 'status-pill-running' }}">
                    {{ ($invoice->status ?? '') === 'cancelled' ? 'Cancelled' : 'Active' }}
                </span>
            </p>

            @if($invoice->order?->po_number)
            <p class="mb-3">
                <span class="info-key">
                    PO Number
                </span>
                <span>{{ $invoice->order->po_number }}</span>
            </p>
            @endif

            @if($invoice->order?->po_date)
            <p class="mb-3">
                <span class="info-key">
                    PO Date
                </span>
                <span>{{ $invoice->order->po_date->format('d M Y') }}</span>
            </p>
            @endif

            @if($invoice->notes)
           <p class="mb-3">
                <span class="info-key">
                    Notes
                </span>
                <span>{{ $invoice->notes }}</span>
            </p>
            @endif
        </div>
    </div>
</section>

<!-- Invoice Items Table -->
    <section class="panel-card panel-card-full">
    <table class="invoice-items-table">
        <thead>
            <tr class="invoice-items-head-row">
                <th class="th-left">Item</th>
                <th class="th-center th-w-70">Qty</th>
                <th class="th-right th-w-90">Price</th>
                <th class="th-right th-w-70">Disc %</th>
                <th class="th-right th-w-70">Tax %</th>
                <th class="th-right th-w-90">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $item)
            <tr class="invoice-item-row">
                <td class="td-item">
                    <strong class="text-base">{{ $item->item_name }}</strong>
                    @if($item->item_description)
                    <div class="item-desc">{{ $item->item_description }}</div>
                    @endif
                    @if($item->frequency && $item->frequency !== 'One-Time')
                    <div class="item-cycle">
                        <span class="mr-2">{{ $item->frequency }}</span>
                        @if($item->duration)
                        <span>for {{ $item->duration }} {{ $item->frequency === 'Day(s)' ? 'day(s)' : ($item->frequency === 'Week(s)' ? 'week(s)' : ($item->frequency === 'Month(s)' ? 'month(s)' : ($item->frequency === 'Quarter(s)' ? 'quarter(s)' : 'year(s)'))) }}</span>
                        @endif
                        @if($item->start_date)
                        <span class="ml-2">(Start: {{ $item->start_date->format('d M Y') }})</span>
                        @endif
                        @if($item->end_date)
                        <span class="ml-2">(End: {{ $item->end_date->format('d M Y') }})</span>
                        @endif
                    </div>
                    @endif
                </td>
                <td class="td-center">
                    {{ number_format($item->quantity, 0) }}
                    @if($item->no_of_users && $item->no_of_users > 1)
                    <div class="text-xs text-muted">{{ $item->no_of_users }} users</div>
                    @endif
                </td>
                <td class="td-right">
                    {{ number_format($item->unit_price, 0) }}
                </td>
                <td class="td-right">
                    {{ number_format($item->discount_percent, 0) }}%
                </td>
                <td class="td-right">
                    {{ number_format($item->tax_rate, 0) }}%
                </td>
                <td class="td-right fw-semibold">
                    {{ number_format($item->line_total, 0) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="no-records-cell">
                    No items added to this invoice.
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($invoice->items->count())
        <tfoot>
            <tr class="invoice-foot-row">
                <td colspan="5" class="foot-label">Subtotal:</td>
                <td class="foot-label">{{ number_format($itemsSubtotal, 0) }}</td>
            </tr>
            @if($itemsDiscountTotal > 0)
            <tr class="invoice-discount-row">
                <td colspan="5" class="foot-label-sm">Discount:</td>
                <td class="foot-label-sm">- {{ number_format($itemsDiscountTotal, 0) }}</td>
            </tr>
            @endif
            @if($invoiceTaxTotal > 0)
                <tr class="invoice-tax-row">
                    <td colspan="5" class="foot-label-tax">{{ $sameStateGst ? 'Tax (CGST + SGST):' : 'Tax (IGST):' }}</td>
                    <td class="foot-label-tax">{{ number_format($invoiceTaxTotal, 0) }}</td>
                </tr>
            @endif
            <tr class="invoice-grand-row">
                <td colspan="5" class="foot-label">Grand Total:</td>
                <td class="foot-label">{{ number_format($invoiceGrandTotal, 0) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</section>

@if($invoice->payments->count())
<section class="panel-card mt-2">
    <h3 class="section-title">Payments received ({{ $invoice->payments->count() }})</h3>
    <table class="data-table mt-2">
        <thead>
            <tr class="payment-head-row">
                <th class="td-pad">Date</th>
                <th class="td-pad">Method</th>
                <th class="td-pad">Reference</th>
                <th class="td-pad text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->payments as $payment)
            <tr class="invoice-item-row">
                <td class="td-pad">{{ $payment->paid_at instanceof \DateTime ? $payment->paid_at->format('d M Y') : $payment->paid_at }}</td>
                <td class="td-pad">{{ $payment->method }}</td>
                <td class="td-pad">{{ $payment->reference ?? 'N/A' }}</td>
                <td class="td-pad text-right"><strong>{{ number_format($payment->amount, 0) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="payment-foot">
            <tr>
                <td colspan="3" class="payment-foot-label"><strong>Total Paid:</strong></td>
                <td class="payment-foot-label"><strong>{{ number_format($totalPaidAmount, 0) }}</strong></td>
            </tr>
            <tr>
                <td colspan="3" class="foot-label-sm"><strong>Balance Due:</strong></td>
                <td class="payment-balance-value"><strong>{{ number_format($balanceDueAmount, 0) }}</strong></td>
            </tr>
        </tfoot>
    </table>
</section>
@else
<section class="panel-card mt-4">
    <div class="empty-payments">
        <p class="mb-4">No payments recorded for this invoice yet.</p>
        <a href="{{ route('payments.create', ['invoiceid' => $invoice->invoiceid, 'clientid' => $invoice->clientid, 'amount' => $balanceDueAmount]) }}" class="primary-button">Record a payment</a>
    </div>
</section>
@endif

@endsection
