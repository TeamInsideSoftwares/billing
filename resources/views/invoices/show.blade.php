@extends('layouts.app')

@section('content')
    @php
        $normalizeTaxState = static fn($value) => preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $value)));
        $clientState = $normalizeTaxState($invoice->client->state ?? '');
        $accountState = $normalizeTaxState($account->state ?? '');
        $sameStateGst = $clientState !== '' && $accountState !== '' && $clientState === $accountState;
        $itemsSubtotal = (float) $invoice->items->sum(function ($item) {
            return (float) ($item->line_total ?? 0);
        });
        $itemsDiscountTotal = (float) $invoice->items->sum(function ($item) {
            $lineTotal = (float) ($item->line_total ?? 0);
            $discountedAmount = (float) ($item->discount_amount ?? 0);
            return max(0, $lineTotal - ($discountedAmount > 0 ? $discountedAmount : $lineTotal));
        });
        $invoiceTaxTotal = (float) $invoice->items->sum(function ($item) {
            $lineTotal = (float) ($item->line_total ?? 0);
            $discountedAmount = (float) ($item->discount_amount ?? 0);
            $taxableAmount = max(0, $discountedAmount > 0 ? $discountedAmount : $lineTotal);
            return ceil($taxableAmount * ((float) ($item->tax_rate ?? 0) / 100));
        });
        $invoiceGrandTotal = max(0, $itemsSubtotal - $itemsDiscountTotal + $invoiceTaxTotal);
        $totalPaidAmount = (float) $invoice->payments->sum(function ($payment) {
            return (float) ($payment->received_amount ?? 0);
        });
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
                $signatureUploadUrl = asset(
                    str_starts_with($signatureUploadPath, 'storage/')
                        ? $signatureUploadPath
                        : 'storage/' . ltrim($signatureUploadPath, '/'),
                );
            }
        }

        $documentType = !empty(trim($invoice->ti_number ?? '')) ? 'Tax Invoice' : 'Proforma Invoice';
        $displayNumber = $invoice->ti_number ?: $invoice->pi_number ?: $invoice->invoice_number;
        $isOverdue = $balanceDueAmount > 0 && $invoice->due_date?->isPast();
    @endphp
@section('header_actions')
    <a href="{{ route('invoices.index', request('c') ? ['c' => request('c')] : []) }}" class="secondary-button">
        Back to Invoices
    </a>
    @if (($invoice->status ?? '') === 'cancelled')
        <form method="POST" action="{{ route('invoices.restore', [$invoice, 'c' => request('c')]) }}" class="inline-delete"
            onsubmit="return confirm('Restore this invoice?')">
            @csrf
            @method('PATCH')
            <button type="submit" class="secondary-button">
                Restore Invoice
            </button>
        </form>
    @else
        <a href="{{ route('invoices.pdf', $invoice) }}" class="secondary-button small" target="_blank">
            View PDF
        </a>
        @if (empty(trim($invoice->ti_number ?? '')))
            <form method="POST" action="{{ route('invoices.create-tax-invoice') }}" class="inline-delete"
                onsubmit="return confirm('Convert this Proforma to Tax Invoice? This will generate a Tax Invoice number.')">
                @csrf
                <input type="hidden" name="invoiceid" value="{{ $invoice->invoiceid }}">
                <button type="submit" class="primary-button small btn-success-solid">
                    Convert to Tax Invoice
                </button>
            </form>
        @endif
        <a href="{{ route('invoices.create', [
            'step' => 2,
            'c' => request('c', $invoice->clientid),
            'd' => $invoice->invoiceid,
            'o' => $invoice->orderid ?? null,
            'tax_invoice' => !empty($invoice->ti_number) ? 1 : null,
        ]) }}"
            class="primary-button small">
            Edit
        </a>
        <form method="POST" action="{{ route('invoices.destroy', [$invoice, 'c' => request('c')]) }}" class="inline-delete"
            onsubmit="return confirm('Cancel this invoice?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="secondary-button">
                Cancel Invoice
            </button>
        </form>
    @endif
@endsection
<div class="invoice-show-container container-fluid py-1 px-0">
    <div class="row g-4">
        <!-- Main Column (Left) -->
        <div class="col-12 col-lg-8">

            <!-- Invoice Items Section -->
            <section class="panel-card mb-4 border-0">
                <div class="section-header d-flex align-items-center justify-content-between pb-3 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="section-icon bg-light text-primary"><i class="fas fa-list-ol"></i></div>
                        <h4 class="section-title mb-0 fw-bold">Invoice Items</h4>
                    </div>
                    <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill font-medium">
                        {{ $invoice->items->count() }} {{ Str::plural('item', $invoice->items->count()) }}
                    </span>
                </div>

                @if ($invoice->items->count())
                    <div class="table-responsive">
                        <table class="invoice-items-table table align-middle mb-0">
                            <thead>
                                <tr class="invoice-items-head-row bg-light">
                                    <th class="th-left border-0 ps-3">Item Details</th>
                                    <th class="th-center th-w-70 border-0 text-center">Qty</th>
                                    <th class="th-right th-w-90 border-0 text-end">Price</th>
                                    <th class="th-right th-w-70 border-0 text-end">Disc %</th>
                                    <th class="th-right th-w-70 border-0 text-end">Tax %</th>
                                    <th class="th-right th-w-90 border-0 text-end pe-3">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoice->items as $item)
                                    <tr class="invoice-item-row align-top">
                                        <td class="td-item ps-3 py-1">
                                            <div class="fw-bold text-dark lh-sm">{{ $item->item_name }}</div>
                                            @if (filled(trim((string) $item->item_description)))
                                                <div class="item-desc text-muted mt-1 small item-desc-text lh-sm">{{ ltrim((string) $item->item_description) }}</div>
                                            @endif
                                            @if ($item->frequency && $item->frequency !== 'One-Time')
                                                <div
                                                    class="item-cycle mt-1 small text-secondary bg-light p-1 px-2 rounded item-cycle-text lh-sm">
                                                    <i class="far fa-clock me-1"></i>
                                                    <span class="fw-semibold">{{ $item->frequency }}</span>
                                                    @if ($item->duration)
                                                        <span>for {{ $item->duration }}
                                                            {{ $item->frequency === 'Day(s)' ? 'day(s)' : ($item->frequency === 'Week(s)' ? 'week(s)' : ($item->frequency === 'Month(s)' ? 'month(s)' : ($item->frequency === 'Quarter(s)' ? 'quarter(s)' : 'year(s)'))) }}</span>
                                                    @endif
                                                    @if ($item->start_date)
                                                        <span class="ms-2">(Start:
                                                            {{ $item->start_date->format('d M Y') }})</span>
                                                    @endif
                                                    @if ($item->end_date)
                                                        @php($itemExpired = $item->end_date < now())
                                                        <span class="ms-2">(End: <span
                                                                class="invoice-end-date {{ $itemExpired ? 'text-danger fw-bold' : 'text-success fw-bold' }}">{{ $item->end_date->format('d M Y') }}</span>)</span>
                                                        @if ($itemExpired)
                                                            <span
                                                                class="badge bg-danger-subtle text-danger ms-1 px-2 py-0.5 rounded-pill uppercase badge-expired">expired</span>
                                                        @endif
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td class="td-center py-1 text-center text-secondary">
                                            {{ number_format($item->quantity, 0) }}
                                            @if ($item->no_of_users && $item->no_of_users > 1)
                                                <div class="text-xs text-muted text-users-count lh-sm">
                                                    {{ $item->no_of_users }} users</div>
                                            @endif
                                        </td>
                                        <td class="td-right py-1 text-end text-secondary">
                                            {{ number_format($item->unit_price, 0) }}
                                        </td>
                                        <td class="td-right py-1 text-end text-secondary">
                                            {{ number_format($item->discount_percent, 0) }}%
                                        </td>
                                        <td class="td-right py-1 text-end text-secondary">
                                            {{ number_format($item->tax_rate, 0) }}%
                                        </td>
                                        <td class="td-right py-1 text-end fw-bold text-dark pe-3">
                                            {{ number_format($item->line_total, 0) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="invoice-foot-row border-top">
                                    <td colspan="5" class="foot-label text-end text-muted py-2">Subtotal:</td>
                                    <td class="foot-label text-end text-dark pe-3 py-2 fw-semibold">
                                        {{ number_format($itemsSubtotal, 0) }}</td>
                                </tr>
                                @if ($itemsDiscountTotal > 0)
                                    <tr class="invoice-discount-row text-danger">
                                        <td colspan="5" class="foot-label-sm text-end py-2">Discount:</td>
                                        <td class="foot-label-sm text-end pe-3 py-2 fw-semibold">-
                                            {{ number_format($itemsDiscountTotal, 0) }}</td>
                                    </tr>
                                @endif
                                @if ($invoiceTaxTotal > 0)
                                    <tr class="invoice-tax-row text-secondary">
                                        <td colspan="5" class="foot-label-tax text-end py-2">
                                            {{ $sameStateGst ? 'Tax (CGST + SGST):' : 'Tax (IGST):' }}</td>
                                        <td class="foot-label-tax text-end pe-3 py-2 fw-semibold">
                                            {{ number_format($invoiceTaxTotal, 0) }}</td>
                                    </tr>
                                @endif
                                <tr class="invoice-grand-row bg-light">
                                    <td colspan="5"
                                        class="foot-label text-end py-3 fw-bold text-secondary grand-total-label">Grand
                                        Total:</td>
                                    <td class="foot-label text-end pe-3 py-3 fw-bold text-primary grand-total-value">
                                        {{ number_format($invoiceGrandTotal, 0) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 text-muted bg-light rounded">
                        <i class="fas fa-box-open fa-2x mb-3 text-muted icon-opacity-40"></i>
                        <p class="mb-0">No items added to this invoice.</p>
                    </div>
                @endif
            </section>

            <!-- Payments Received Section -->
            <section class="panel-card mb-4 border-0">
                <div class="section-header d-flex align-items-center justify-content-between pb-3 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="section-icon bg-light text-success"><i class="fas fa-history"></i></div>
                        <h4 class="section-title mb-0 fw-bold">Payments History</h4>
                    </div>
                </div>

                @if ($invoice->payments->count())
                    <div class="table-responsive">
                        <table class="data-table table align-middle mb-0">
                            <thead>
                                <tr class="payment-head-row bg-light">
                                    <th class="td-pad border-0 ps-3">Payment Date</th>
                                    <th class="td-pad border-0">Method</th>
                                    <th class="td-pad border-0">Reference / Notes</th>
                                    <th class="td-pad border-0 text-end pe-3">Received Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoice->payments as $payment)
                                    <tr class="payment-row">
                                        <td class="td-pad ps-3 py-3 text-secondary">
                                            {{ optional($payment->payment_date)->format('d M Y') }}</td>
                                        <td class="td-pad py-3">
                                            <span
                                                class="badge bg-light text-dark border px-2 py-1">{{ $payment->mode ?? '-' }}</span>
                                        </td>
                                        <td class="td-pad py-3">
                                            @if ($payment->reference_number)
                                                <div class="text-dark fw-medium">{{ $payment->reference_number }}
                                                </div>
                                            @else
                                                <span class="text-muted small">N/A</span>
                                            @endif
                                            @if (($payment->type ?? 'payment') === 'tds')
                                                <span
                                                    class="badge bg-warning-subtle text-warning-emphasis ms-1 badge-tds">TDS
                                                    Deducted</span>
                                            @endif
                                            @if (!empty($payment->description))
                                                <div class="text-muted small mt-1 payment-note">
                                                    "{{ $payment->description }}"</div>
                                            @endif
                                        </td>
                                        <td class="td-pad py-3 text-end pe-3 fw-bold text-success">
                                            {{ number_format((float) ($payment->received_amount ?? 0), 0) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="payment-foot bg-light">
                                <tr>
                                    <td colspan="3"
                                        class="payment-foot-label text-end py-2 text-muted fw-medium border-0">Total
                                        Paid:</td>
                                    <td class="payment-foot-label text-end pe-3 py-2 fw-bold text-success border-0">
                                        {{ number_format($totalPaidAmount, 0) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3"
                                        class="foot-label-sm text-end py-2 text-muted fw-medium border-0">Balance Due:
                                    </td>
                                    <td class="payment-balance-value text-end pe-3 py-2 fw-bold text-danger border-0">
                                        {{ number_format($balanceDueAmount, 0) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 text-muted bg-light rounded">
                        <div class="empty-state-icon"><i
                                class="fas fa-receipt fa-2x mb-3 text-muted icon-opacity-40"></i></div>
                        <p class="mb-3 font-medium">No payments recorded for this invoice yet.</p>
                        @if ($balanceDueAmount > 0 && ($invoice->status ?? '') !== 'cancelled')
                            <a href="{{ route('payments.create', ['i' => $invoice->invoiceid, 'c' => $invoice->clientid]) }}"
                                class="primary-button btn-sm d-inline-flex align-items-center gap-2 btn-no-deco">
                                <i class="fas fa-plus"></i> Record a Payment
                            </a>
                        @endif
                    </div>
                @endif
            </section>
        </div>

        <!-- Sidebar Column (Right) -->
        <div class="col-12 col-lg-4">

            <!-- Summary Card -->
            <section class="panel-card mb-4 border-0 card-dashboard-summary">
                <div class="section-header pb-3 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="section-icon bg-light text-primary"><i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h4 class="section-title mb-0 fw-bold">Overview</h4>
                    </div>
                </div>

                <div class="invoice-summary-details">
                    <!-- Balance Widget -->
                    <div
                        class="text-center py-4 px-3 mb-4 rounded balance-widget shadow-sm position-relative overflow-hidden {{ $balanceDueAmount > 0 ? 'is-due' : 'is-paid' }}">
                        <span class="text-muted-uppercase d-block text-xs mb-1 balance-label">Balance Due</span>
                        <h2
                            class="heading-lg my-1 fw-extrabold {{ $balanceDueAmount > 0 ? 'text-danger' : 'text-success' }} balance-amount">
                            {{ $invoice->client->currency ?? 'INR' }} {{ number_format($balanceDueAmount, 0) }}
                        </h2>

                        <div class="mt-3">
                            @if (($invoice->status ?? '') === 'cancelled')
                                <span class="status-pill cancelled">Cancelled</span>
                            @elseif($balanceDueAmount <= 0)
                                <span class="status-pill paid">Paid</span>
                            @elseif($totalPaidAmount > 0)
                                <span class="status-pill partial">Partially-Paid</span>
                            @else
                                <span class="status-pill unpaid">Unpaid</span>
                            @endif
                        </div>
                    </div>

                    <!-- Meta details grid -->
                    <div class="info-grid-2col pb-3 mb-3 border-bottom gap-lg">
                        <div class="info-label text-muted font-medium">Invoice No.</div>
                        <div class="info-value text-dark fw-bold">{{ $displayNumber }}</div>

                        <div class="info-label text-muted font-medium">Doc Type</div>
                        <div class="info-value text-secondary"><span
                                class="badge bg-blue-subtle text-primary border border-primary-subtle px-2 py-1 doc-type-badge">{{ $documentType }}</span>
                        </div>

                        <div class="info-label text-muted font-medium">Status</div>
                        <div class="info-value">
                            <span
                                class="status-pill {{ ($invoice->status ?? '') === 'cancelled' ? 'cancelled' : 'active' }} status-pill-small">
                                {{ ($invoice->status ?? '') === 'cancelled' ? 'Cancelled' : 'Active' }}
                            </span>
                        </div>
                    </div>

                    <div class="info-grid-2col pb-3 mb-3 border-bottom gap-lg">
                        <div class="info-label text-muted font-medium">Issue Date</div>
                        <div class="info-value text-dark">{{ $invoice->issue_date?->format('d M Y') ?? '-' }}</div>

                        <div class="info-label text-muted font-medium">Due Date</div>
                        <div class="info-value text-dark fw-semibold {{ $isOverdue ? 'text-danger' : '' }}">
                            {{ $invoice->due_date?->format('d M Y') ?? '-' }}
                            @if ($isOverdue)
                                <span
                                    class="badge bg-danger-subtle text-danger ms-1 px-2 py-0.5 rounded-pill font-bold badge-overdue">Overdue</span>
                            @endif
                        </div>
                    </div>

                    <!-- Financial Summary List -->
                    <div class="info-grid-2col pb-2 gap-md">
                        <div class="info-label text-muted font-medium">Subtotal</div>
                        <div class="info-value text-dark fw-medium">{{ number_format($itemsSubtotal, 0) }}</div>

                        @if ($itemsDiscountTotal > 0)
                            <div class="info-label text-muted font-medium">Discount</div>
                            <div class="info-value text-danger fw-medium">-
                                {{ number_format($itemsDiscountTotal, 0) }}</div>
                        @endif

                        @if ($invoiceTaxTotal > 0)
                            @if ($cgstAmount > 0)
                                <div class="info-label text-muted font-medium">CGST</div>
                                <div class="info-value text-dark fw-medium">{{ number_format($cgstAmount, 0) }}</div>
                            @endif
                            @if ($sgstAmount > 0)
                                <div class="info-label text-muted font-medium">SGST</div>
                                <div class="info-value text-dark fw-medium">{{ number_format($sgstAmount, 0) }}</div>
                            @endif
                            @if ($igstAmount > 0)
                                <div class="info-label text-muted font-medium">IGST</div>
                                <div class="info-value text-dark fw-medium">{{ number_format($igstAmount, 0) }}</div>
                            @endif
                        @endif

                        <div class="info-label text-muted font-medium border-top pt-2 mt-1">Grand Total</div>
                        <div class="info-value text-dark fw-bold border-top pt-2 mt-1">
                            {{ number_format($invoiceGrandTotal, 0) }}</div>

                        <div class="info-label text-muted font-medium border-top pt-2 mt-1">Total Paid</div>
                        <div class="info-value text-success fw-bold border-top pt-2 mt-1">
                            {{ number_format($totalPaidAmount, 0) }}</div>
                    </div>
                </div>
            </section>

            <!-- Client Card -->
            <section class="panel-card mb-4 border-0">
                <div class="section-header pb-3 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="section-icon bg-light text-primary"><i class="fas fa-building"></i></div>
                        <h4 class="section-title mb-0 fw-bold">Client Information</h4>
                    </div>
                </div>

                <div class="client-summary-details">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div
                            class="avatar-large bg-primary-subtle text-primary fw-bold rounded d-flex align-items-center justify-content-center client-avatar-large">
                            {{ strtoupper(substr($invoice->client->business_name ?? ($invoice->client->contact_name ?? 'CL'), 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <h5 class="text-dark fw-bold mb-0 text-truncate client-name-heading">
                                <a href="{{ route('clients.show', $invoice->clientid) }}"
                                    class="text-decoration-none text-dark hover-primary-link">
                                    {{ $invoice->client->business_name ?? ($invoice->client->contact_name ?? '-') }}
                                </a>
                            </h5>
                            @if ($invoice->client->contact_name && $invoice->client->business_name)
                                <div class="text-muted small mt-0.5 text-truncate client-contact-text"><i
                                        class="far fa-user me-1"></i>{{ $invoice->client->contact_name }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="info-grid-2col pt-2 border-top gap-md">
                        @if ($invoice->client->email)
                            <div class="info-label text-muted font-medium">Email</div>
                            <div class="info-value text-truncate" title="{{ $invoice->client->email }}">
                                <a href="mailto:{{ $invoice->client->email }}"
                                    class="text-decoration-none text-primary">{{ $invoice->client->email }}</a>
                            </div>
                        @endif

                        @if ($invoice->client->phone)
                            <div class="info-label text-muted font-medium">Phone</div>
                            <div class="info-value text-dark">{{ $invoice->client->phone }}</div>
                        @endif

                        @if ($invoice->client->billingDetail?->gstin)
                            <div class="info-label text-muted font-medium">GSTIN</div>
                            <div class="info-value"><span
                                    class="badge bg-light text-secondary border px-2 py-1 font-monospace gstin-badge">{{ $invoice->client->billingDetail->gstin }}</span>
                            </div>
                        @endif

                        @if ($invoice->client->state)
                            <div class="info-label text-muted font-medium">State</div>
                            <div class="info-value text-dark">{{ $invoice->client->state }}</div>
                        @endif

                        @if ($invoice->client->address_line_1)
                            <div class="info-label text-muted font-medium">Address</div>
                            <div class="info-value text-dark small address-text">
                                {{ $invoice->client->address_line_1 }}
                                @if ($invoice->client->city)
                                    <br>{{ $invoice->client->city }}
                                @endif
                                @if ($invoice->client->postal_code)
                                    - {{ $invoice->client->postal_code }}
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

@endsection
