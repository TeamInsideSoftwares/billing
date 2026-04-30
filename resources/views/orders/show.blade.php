@extends('layouts.app')

@section('content')
@php
    $normalizeTaxState = static fn ($value) => preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $value)));
    $clientState = $normalizeTaxState($order->client->state ?? '');
    $accountState = $normalizeTaxState($account->state ?? '');
    $sameStateGst = $clientState !== '' && $accountState !== '' && $clientState === $accountState;
    $orderTaxTotal = (float) ($order->tax_total ?? 0);
    $cgstAmount = $sameStateGst ? round($orderTaxTotal / 2, 0) : 0;
    $sgstAmount = $sameStateGst ? round($orderTaxTotal / 2, 0) : 0;
    $igstAmount = $sameStateGst ? 0 : round($orderTaxTotal, 0);
@endphp

@section('header_actions')
    <a href="{{ route('orders.index', ['c' => $order->clientid]) }}" class="secondary-button">
        <i class="fas fa-arrow-left icon-spaced"></i>Back to Orders
    </a>
    @if(($order->status ?? '') !== 'cancelled' && ($order->status ?? '') !== 'completed')
    <a href="{{ route('invoices.create', ['step' => 3, 'invoice_for' => 'orders', 'o' => $order->orderid, 'c' => $order->clientid]) }}" class="primary-button small">
        <i class="fas fa-file-invoice icon-spaced-sm"></i>Create PI
    </a>
    @endif
    @if(($order->status ?? '') !== 'cancelled')
        <a href="{{ route('orders.edit', ['order' => $order->orderid, 'c' => $order->clientid]) }}" class="primary-button small">
            <i class="fas fa-edit icon-spaced-sm"></i>Edit
        </a>
        <form method="POST" action="{{ route('orders.destroy', ['order' => $order->orderid, 'c' => $order->clientid]) }}" class="inline-delete" onsubmit="return confirm('Cancel this order?')" class="inline-delete">
            @csrf
            @method('DELETE')
            <button type="submit" class="secondary-button">
                <i class="fas fa-ban icon-spaced-sm"></i>Cancel Order
            </button>
        </form>
    @else
        <form method="POST" action="{{ route('orders.restore', ['order' => $order->orderid, 'c' => $order->clientid]) }}" class="inline-delete" onsubmit="return confirm('Restore this order?')" class="inline-delete">
            @csrf
            @method('PATCH')
            <button type="submit" class="primary-button small">
                <i class="fas fa-undo icon-spaced-sm"></i>Restore Order
            </button>
        </form>
    @endif
@endsection

<section class="panel-card panel-card-lg">
    <div class="order-show-header">
        <div class="order-show-avatar">
            <i class="fas fa-receipt"></i>
        </div>
        <div class="flex-fill">
            <div class="order-show-meta">
                @if($order->order_title)
                    <p class="text-muted-uppercase">{{ $order->order_title }}</p>
                @endif
                <span class="order-show-number">Order #{{ $order->order_number }}</span>
                @if(($order->status ?? '') === 'cancelled')
                    <span class="status-pill status-pill-cancelled">
                        Cancelled
                    </span>
                @elseif(($order->status ?? '') === 'completed')
                    <span class="status-pill status-pill-completed">
                        Completed
                    </span>
                @elseif(($order->status ?? '') === 'paused')
                    <span class="status-pill status-pill-paused">
                        Paused
                    </span>
                @else
                    <span class="status-pill status-pill-running">
                        Running
                    </span>
                @endif
            </div>
            <h1 class="heading-lg">{{ $order->client->business_name ?? $order->client->contact_name }}</h1>
            <p class="mb-0 text-sm text-muted">{{ $order->client->email }}</p>
            <!-- <span class="status-pill {{ strtolower($order->status ?? 'draft') }} status-pill-inline">{{ ucfirst($order->status ?? 'Draft') }}</span> -->
        </div>
        <div class="text-right">
            <p class="text-muted-uppercase">Grand Total</p>
            <strong class="order-show-total">{{ number_format($order->grand_total ?? 0, 0) }}</strong>
        </div>
    </div>
</section>

<div class="order-show-grid">
    <!-- Order Details -->
    <section class="panel-card panel-card-md">
        <div class="section-header">
            <div class="section-icon"><i class="fas fa-clipboard-list"></i></div>
            <h4 class="section-title">Order Details</h4>
        </div>
        <div class="info-grid-2col">
            <div class="info-label">Order Date</div>
            <div>{{ $order->order_date?->format('d M Y') ?? '—' }}</div>

            <div class="info-label">Delivery</div>
            <div>{{ $order->delivery_date?->format('d M Y') ?? '—' }}</div>

            <div class="info-label">Sales Person</div>
            <div>{{ $salesPersonName ?? '-' }}</div>

            @if($order->po_number)
            <div class="info-label">PO Number</div>
            <div class="info-value">{{ $order->po_number }} {{ $order->po_date ? '(' . $order->po_date->format('d M Y') . ')' : '' }}</div>
            @endif

            @if($order->agreement_ref)
            <div class="info-label">Agreement Ref</div>
            <div class="info-value">{{ $order->agreement_ref }} {{ $order->agreement_date ? '(' . $order->agreement_date->format('d M Y') . ')' : '' }}</div>
            @endif

            <!-- <div class="info-label">Status</div>
            <div><span class="status-pill {{ strtolower($order->status ?? 'draft') }}">{{ ucfirst($order->status ?? 'Draft') }}</span></div> -->

            @if($order->notes)

                <div class="info-label">Notes</div>
                <div>{{ $order->notes }}</div>
            @endif
        </div>
    </section>

    <!-- Client Info -->
    <section class="panel-card panel-card-md">
        <div class="section-header">
            <div class="section-icon"><i class="fas fa-address-book"></i></div>
            <h4 class="section-title">Client Information</h4>
        </div>
        <div class="info-grid-2col">
            <div class="info-label">Contact</div>
            <div class="info-value">{{ $order->client->contact_name ?? '—' }}</div>

            <div class="info-label">Email</div>
            <div>{{ $order->client->email ?? '—' }}</div>

            <div class="info-label">Phone</div>
            <div>{{ $order->client->phone ?? '—' }}</div>

            <div class="info-label">Location</div>
            <div>{{ $order->client->city ?? '—' }}{{ $order->client->state ? ', ' . $order->client->state : '' }}</div>
        </div>
    </section>
</div>

<!-- Order Items -->
<section class="panel-card panel-card-md mt-2">
    <div class="section-header">
        <div class="section-icon"><i class="fas fa-box-open"></i></div>
        <h4 class="section-title">Order Items ({{ $order->items->count() }})</h4>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-sm">Item</th>
                <th class="text-xs text-center">Qty</th>
                <th class="text-xs text-right">Price</th>
                <th class="text-xs text-right">Tax %</th>
                <th class="text-xs text-right">Discount</th>
                <th class="text-sm">Frequency / Duration</th>
                <th class="text-xs text-center">Users</th>
                <th class="text-xs text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->items as $item)
            <tr>
                <td class="small-text"><strong>{{ $item->item_name }}</strong></td>
                <td class="text-sm text-center">{{ number_format($item->quantity, 0) }}</td>
                <td class="text-sm text-right">{{ number_format($item->unit_price, 0) }}</td>
                <td class="text-sm text-right">{{ number_format($item->tax_rate, 0) }}%</td>
                <td class="text-sm text-right">
                    @if(($item->discount_amount ?? 0) > 0)
                        {{ number_format($item->discount_amount, 0) }}
                        @if(($item->discount_percent ?? 0) > 0)
                            <div class="text-xs text-muted">({{ number_format($item->discount_percent, 1) }}%)</div>
                        @endif
                    @else
                        —
                    @endif
                </td>
                <td class="small-text">
                    @if($item->duration && $item->frequency && $item->frequency !== 'One-Time')
                        {{ $item->duration }} {{ $item->frequency }}
                    @else
                        {{ $item->frequency ?? '—' }}
                    @endif
                </td>
                <td class="text-sm text-center">{{ $item->no_of_users ?? '—' }}</td>
                <td class="text-sm text-right"><strong>{{ number_format($item->line_total, 0) }}</strong></td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="no-records-cell">No items in this order.</td>
            </tr>
            @endforelse
        </tbody>
        @if($order->items->count())
        <tfoot>
            <tr class="order-total-row">
                <td colspan="7" class="order-total-label">Subtotal:</td>
                <td class="order-total-value">{{ number_format($order->subtotal ?? 0, 0) }}</td>
            </tr>
            @if(($order->discount_total ?? 0) > 0)
            <tr class="order-total-row">
                <td colspan="7" class="order-total-label">Discount:</td>
                <td class="order-total-value order-total-negative">-{{ number_format($order->discount_total, 0) }}</td>
            </tr>
            @endif
            @if($orderTaxTotal > 0)
                <tr class="order-total-row">
                    <td colspan="7" class="order-total-label">
                        {{ $sameStateGst ? 'Tax (CGST + SGST):' : 'Tax (IGST):' }}
                    </td>
                    <td class="order-total-value">{{ number_format($orderTaxTotal, 0) }}</td>
                </tr>
            @endif
            <tr class="order-grand-total-row">
                <td colspan="7" class="order-grand-total-label">Grand Total:</td>
                <td class="order-grand-total-value">{{ number_format($order->grand_total ?? 0, 0) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</section>

@endsection
