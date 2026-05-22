@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('quotations.index', request('c') ? ['c' => request('c')] : []) }}" class="secondary-button">
        Back to Quotations
    </a>
    <a href="{{ route('quotations.pdf', $quotation) }}" target="_blank" class="secondary-button small">View PDF</a>
    <a href="{{ route('quotations.email-compose', $quotation) }}" class="primary-button small">Compose Email</a>
    <a href="{{ route('quotations.create', ['step' => 2, 'c' => request('c', $quotation->clientid), 'd' => $quotation->quotationid]) }}" class="primary-button small">Edit</a>
    <form method="POST" action="{{ route('quotations.destroy', ['quotation' => $quotation, 'c' => request('c')]) }}" class="inline-delete"
        onsubmit="return confirm('Delete this quotation?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="secondary-button">Delete</button>
    </form>
@endsection

@section('content')
    @php
        $itemsSubtotal = (float) $quotation->items->sum(function ($item) {
            return (float) ($item->amount ?? 0);
        });
        $discountTotal = (float) $quotation->items->sum(function ($item) {
            $lineSub = (float) ($item->amount ?? 0);
            $discountPercent = max(0, min(100, (float) ($item->discount_percent ?? 0)));
            return floor($lineSub * ($discountPercent / 100));
        });
        $taxTotal = (float) $quotation->items->sum(function ($item) {
            $lineSub = (float) ($item->amount ?? 0);
            $discountPercent = max(0, min(100, (float) ($item->discount_percent ?? 0)));
            $discounted = max(0, $lineSub - floor($lineSub * ($discountPercent / 100)));
            return ceil($discounted * ((float) ($item->tax_rate ?? 0) / 100));
        });
        $grandTotal = max(0, $itemsSubtotal - $discountTotal + $taxTotal);
        $clientName = $quotation->client->business_name ?? ($quotation->client->contact_name ?? 'Client');
    @endphp

    <div class="invoice-show-container container-fluid py-1 px-0">
        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <section class="panel-card mb-4 border-0">
                    <div class="section-header d-flex align-items-center justify-content-between pb-3 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="section-icon bg-light text-primary"><i class="fas fa-list-ol"></i></div>
                            <h4 class="section-title mb-0 fw-bold">Quotation Items</h4>
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill font-medium">
                            {{ $quotation->items->count() }} {{ \Illuminate\Support\Str::plural('item', $quotation->items->count()) }}
                        </span>
                    </div>

                    @if ($quotation->items->count())
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
                                    @foreach ($quotation->items as $item)
                                        <tr class="invoice-item-row align-top">
                                            <td class="td-item ps-3 py-1">
                                                <div class="fw-bold text-dark lh-sm">{{ $item->item_name }}</div>
                                                @if (filled(trim((string) $item->item_description)))
                                                    <div class="item-desc text-muted mt-1 small item-desc-text lh-sm">{{ ltrim((string) $item->item_description) }}</div>
                                                @endif
                                            </td>
                                            <td class="td-center py-1 text-center text-secondary">{{ number_format((float) ($item->quantity ?? 1), 0) }}</td>
                                            <td class="td-right py-1 text-end text-secondary">{{ number_format((float) ($item->unit_price ?? 0), 0) }}</td>
                                            <td class="td-right py-1 text-end text-secondary">{{ number_format((float) ($item->discount_percent ?? 0), 0) }}%</td>
                                            <td class="td-right py-1 text-end text-secondary">{{ number_format((float) ($item->tax_rate ?? 0), 0) }}%</td>
                                            <td class="td-right py-1 text-end fw-bold text-dark pe-3">{{ number_format((float) ($item->line_total ?? $item->amount ?? 0), 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="invoice-foot-row border-top">
                                        <td colspan="5" class="foot-label text-end text-muted py-2">Subtotal:</td>
                                        <td class="foot-label text-end text-dark pe-3 py-2 fw-semibold">{{ number_format($itemsSubtotal, 0) }}</td>
                                    </tr>
                                    @if ($discountTotal > 0)
                                        <tr class="invoice-discount-row text-danger">
                                            <td colspan="5" class="foot-label-sm text-end py-2">Discount:</td>
                                            <td class="foot-label-sm text-end pe-3 py-2 fw-semibold">-{{ number_format($discountTotal, 0) }}</td>
                                        </tr>
                                    @endif
                                    @if ($taxTotal > 0)
                                        <tr class="invoice-tax-row text-secondary">
                                            <td colspan="5" class="foot-label-tax text-end py-2">Tax:</td>
                                            <td class="foot-label-tax text-end pe-3 py-2 fw-semibold">{{ number_format($taxTotal, 0) }}</td>
                                        </tr>
                                    @endif
                                    <tr class="invoice-grand-row bg-light">
                                        <td colspan="5" class="foot-label text-end py-3 fw-bold text-secondary grand-total-label">Grand Total:</td>
                                        <td class="foot-label text-end pe-3 py-3 fw-bold text-primary grand-total-value">{{ number_format($grandTotal, 0) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted bg-light rounded">
                            <i class="fas fa-box-open fa-2x mb-3 text-muted icon-opacity-40"></i>
                            <p class="mb-0">No items added to this quotation.</p>
                        </div>
                    @endif
                </section>
            </div>

            <div class="col-12 col-lg-4">
                <section class="panel-card mb-4 border-0 card-dashboard-summary">
                    <div class="section-header pb-3 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="section-icon bg-light text-primary"><i class="fas fa-file-invoice"></i></div>
                            <h4 class="section-title mb-0 fw-bold">Quotation Summary</h4>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between"><span class="text-muted">Number</span><strong>{{ $quotation->quo_number }}</strong></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Title</span><strong>{{ $quotation->quo_title ?: '-' }}</strong></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Client</span><strong>{{ $clientName }}</strong></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Issue Date</span><strong>{{ optional($quotation->issue_date)->format('d M Y') ?: '-' }}</strong></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Due Date</span><strong>{{ optional($quotation->due_date)->format('d M Y') ?: '-' }}</strong></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Status</span><span class="status-pill {{ strtolower($quotation->status ?? 'draft') }}">{{ ucfirst($quotation->status ?? 'draft') }}</span></div>
                    </div>

                    @if (!empty($quotation->notes))
                        <div class="mt-3 pt-3 border-top">
                            <div class="text-muted small mb-1">Notes</div>
                            <div class="small">{{ $quotation->notes }}</div>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
@endsection
