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
        <i class="fas fa-arrow-left" class="icon-spaced"></i>Back to Orders
    </a>
    @if(($order->status ?? '') !== 'cancelled' && ($order->status ?? '') !== 'completed')
    <a href="{{ route('invoices.create', ['step' => 3, 'invoice_for' => 'orders', 'o' => $order->orderid, 'c' => $order->clientid]) }}" class="primary-button small">
        <i class="fas fa-file-invoice" class="icon-spaced-sm"></i>Create PI
    </a>
    @endif
    @if(($order->status ?? '') !== 'cancelled')
        <a href="{{ route('orders.edit', ['order' => $order->orderid, 'c' => $order->clientid]) }}" class="primary-button small">
            <i class="fas fa-edit" class="icon-spaced-sm"></i>Edit
        </a>
        <form method="POST" action="{{ route('orders.destroy', ['order' => $order->orderid, 'c' => $order->clientid]) }}" class="inline-delete" onsubmit="return confirm('Cancel this order?')" style="display: inline;">
            @csrf
            @method('DELETE')
            <button type="submit" class="secondary-button">
                <i class="fas fa-ban" class="icon-spaced-sm"></i>Cancel Order
            </button>
        </form>
    @else
        <form method="POST" action="{{ route('orders.restore', ['order' => $order->orderid, 'c' => $order->clientid]) }}" class="inline-delete" onsubmit="return confirm('Restore this order?')" style="display: inline;">
            @csrf
            @method('PATCH')
            <button type="submit" class="primary-button small">
                <i class="fas fa-undo" class="icon-spaced-sm"></i>Restore Order
            </button>
        </form>
    @endif
@endsection

<section class="panel-card" style="padding: 1.25rem;">
    <div style="display: flex; gap: 1.5rem; align-items: center;">
        <div style="width: 64px; height: 64px; border-radius: 10px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
            <i class="fas fa-receipt"></i>
        </div>
        <div class="flex-fill">
            <div style="margin: 0.25rem 0 0; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                @if($order->order_title)
                    <p style="margin: 0; font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">{{ $order->order_title }}</p>
                @endif
                <span style="font-size: 0.8rem; color: #475569; font-weight: 600;">Order #{{ $order->order_number }}</span>
                @if(($order->status ?? '') === 'cancelled')
                    <span style="font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 999px; font-weight: 600; background: #e2e8f0; color: #475569;">
                        Cancelled
                    </span>
                @elseif(($order->status ?? '') === 'completed')
                    <span style="font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 999px; font-weight: 600; background: #dcfce7; color: #166534;">
                        Completed
                    </span>
                @elseif(($order->status ?? '') === 'paused')
                    <span style="font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 999px; font-weight: 600; background: #fef3c7; color: #92400e;">
                        Paused
                    </span>
                @else
                    <span style="font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 999px; font-weight: 600; background: #dbeafe; color: #1e40af;">
                        Running
                    </span>
                @endif
            </div>
            <h1 style="margin: 0.25rem 0 0.25rem 0; font-size: 1.3rem; font-weight: 700;">{{ $order->client->business_name ?? $order->client->contact_name }}</h1>
            <p class="mb-0 text-sm text-muted">{{ $order->client->email }}</p>
            <!-- <span class="status-pill {{ strtolower($order->status ?? 'draft') }}" class="status-pill-inline">{{ ucfirst($order->status ?? 'Draft') }}</span> -->
        </div>
        <div class="text-right">
            <p style="margin: 0; font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Grand Total</p>
            <strong style="font-size: 1.4rem; color: #0f172a; margin-top: 0.25rem; display: block;">{{ number_format($order->grand_total ?? 0, 0) }}</strong>
        </div>
    </div>
</section>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
    <!-- Order Details -->
    <section class="panel-card" style="padding: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb;">
            <div style="width: 28px; height: 28px; border-radius: 6px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;"><i class="fas fa-clipboard-list"></i></div>
            <h4 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1e293b;">Order Details</h4>
        </div>
        <div style="display: grid; grid-template-columns: 100px 1fr; gap: 0.5rem; font-size: 0.85rem;">
            <div style="color: #64748b;">Order Date</div>
            <div>{{ $order->order_date?->format('d M Y') ?? '—' }}</div>

            <div style="color: #64748b;">Delivery</div>
            <div>{{ $order->delivery_date?->format('d M Y') ?? '—' }}</div>

            <div style="color: #64748b;">Sales Person</div>
            <div>{{ $salesPersonName ?? '-' }}</div>

            @if($order->po_number)
            <div style="color: #64748b;">PO Number</div>
            <div style="font-weight: 500;">{{ $order->po_number }} {{ $order->po_date ? '(' . $order->po_date->format('d M Y') . ')' : '' }}</div>
            @endif

            @if($order->agreement_ref)
            <div style="color: #64748b;">Agreement Ref</div>
            <div style="font-weight: 500;">{{ $order->agreement_ref }} {{ $order->agreement_date ? '(' . $order->agreement_date->format('d M Y') . ')' : '' }}</div>
            @endif

            <!-- <div style="color: #64748b;">Status</div>
            <div><span class="status-pill {{ strtolower($order->status ?? 'draft') }}">{{ ucfirst($order->status ?? 'Draft') }}</span></div> -->

            @if($order->notes)

                <div style="color: #64748b;">Notes</div>
                <div>{{ $order->notes }}</div>
            @endif
        </div>
    </section>

    <!-- Client Info -->
    <section class="panel-card" style="padding: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb;">
            <div style="width: 28px; height: 28px; border-radius: 6px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;"><i class="fas fa-address-book"></i></div>
            <h4 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1e293b;">Client Information</h4>
        </div>
        <div style="display: grid; grid-template-columns: 100px 1fr; gap: 0.5rem; font-size: 0.85rem;">
            <div style="color: #64748b;">Contact</div>
            <div style="font-weight: 500;">{{ $order->client->contact_name ?? '—' }}</div>

            <div style="color: #64748b;">Email</div>
            <div>{{ $order->client->email ?? '—' }}</div>

            <div style="color: #64748b;">Phone</div>
            <div>{{ $order->client->phone ?? '—' }}</div>

            <div style="color: #64748b;">Location</div>
            <div>{{ $order->client->city ?? '—' }}{{ $order->client->state ? ', ' . $order->client->state : '' }}</div>
        </div>
    </section>
</div>

<!-- Order Items -->
<section class="panel-card" style="margin-top: 1rem; padding: 1rem;">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb;">
        <div style="width: 28px; height: 28px; border-radius: 6px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;"><i class="fas fa-box-open"></i></div>
        <h4 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1e293b;">Order Items ({{ $order->items->count() }})</h4>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-sm">Item</th>
                <th style="font-size: 0.8rem; text-align: center;">Qty</th>
                <th style="font-size: 0.8rem; text-align: right;">Price</th>
                <th style="font-size: 0.8rem; text-align: right;">Tax %</th>
                <th style="font-size: 0.8rem; text-align: right;">Discount</th>
                <th class="text-sm">Frequency / Duration</th>
                <th style="font-size: 0.8rem; text-align: center;">Users</th>
                <th style="font-size: 0.8rem; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->items as $item)
            <tr>
                <td class="small-text"><strong>{{ $item->item_name }}</strong></td>
                <td style="font-size: 0.85rem; text-align: center;">{{ number_format($item->quantity, 0) }}</td>
                <td style="font-size: 0.85rem; text-align: right;">{{ number_format($item->unit_price, 0) }}</td>
                <td style="font-size: 0.85rem; text-align: right;">{{ number_format($item->tax_rate, 0) }}%</td>
                <td style="font-size: 0.85rem; text-align: right;">
                    @if(($item->discount_amount ?? 0) > 0)
                        {{ number_format($item->discount_amount, 0) }}
                        @if(($item->discount_percent ?? 0) > 0)
                            <div style="font-size: 0.7rem; color: #64748b;">({{ number_format($item->discount_percent, 1) }}%)</div>
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
                <td style="font-size: 0.85rem; text-align: center;">{{ $item->no_of_users ?? '—' }}</td>
                <td style="font-size: 0.85rem; text-align: right;"><strong>{{ number_format($item->line_total, 0) }}</strong></td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="padding: 2rem; text-align: center; color: #94a3b8; font-style: italic;">No items in this order.</td>
            </tr>
            @endforelse
        </tbody>
        @if($order->items->count())
        <tfoot>
            <tr style="background: #f8fafc; border-top: 2px solid #e5e7eb;">
                <td colspan="7" style="padding: 0.5rem; text-align: right; font-weight: 600; font-size: 0.85rem; color: #64748b;">Subtotal:</td>
                <td style="padding: 0.5rem; font-weight: 600; font-size: 0.85rem; text-align: right; color: #1e293b;">{{ number_format($order->subtotal ?? 0, 0) }}</td>
            </tr>
            @if(($order->discount_total ?? 0) > 0)
            <tr style="background: #f8fafc;">
                <td colspan="7" style="padding: 0.5rem; text-align: right; font-weight: 600; font-size: 0.85rem; color: #64748b;">Discount:</td>
                <td style="padding: 0.5rem; font-weight: 600; font-size: 0.85rem; text-align: right; color: #ef4444;">-{{ number_format($order->discount_total, 0) }}</td>
            </tr>
            @endif
            @if($orderTaxTotal > 0)
                <tr style="background: #f8fafc;">
                    <td colspan="7" style="padding: 0.5rem; text-align: right; font-weight: 600; font-size: 0.85rem; color: #64748b;">
                        {{ $sameStateGst ? 'Tax (CGST + SGST):' : 'Tax (IGST):' }}
                    </td>
                    <td style="padding: 0.5rem; font-weight: 600; font-size: 0.85rem; text-align: right; color: #1e293b;">{{ number_format($orderTaxTotal, 0) }}</td>
                </tr>
            @endif
            <tr style="background: #f1f5f9;">
                <td colspan="7" style="padding: 0.75rem 0.5rem; text-align: right; font-weight: 700; font-size: 1rem; color: #0f172a;">Grand Total:</td>
                <td style="padding: 0.75rem 0.5rem; font-weight: 700; font-size: 1.1rem; text-align: right; color: #0f172a;">{{ number_format($order->grand_total ?? 0, 0) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</section>

@endsection
