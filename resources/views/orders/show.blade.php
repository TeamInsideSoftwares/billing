@extends('layouts.app')

@section('content')

<section class="section-bar">
    <div>
        <p class="eyebrow">Orders</p>
        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">{{ $order->order_number }}</h3>
        <a href="{{ route('orders.index') }}" class="text-link" style="font-size: 0.85rem;">&larr; Back to orders</a>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="{{ route('orders.edit', $order) }}" class="icon-action-btn edit" title="Edit" style="width: 36px; height: 36px; font-size: 1rem;">
            <i class="fas fa-edit"></i>
        </a>
        <form method="POST" action="{{ route('orders.destroy', $order) }}" class="inline-delete" onsubmit="return confirm('Delete this order?')">
            @csrf @method('DELETE')
            <button type="submit" class="icon-action-btn delete" title="Delete" style="width: 36px; height: 36px; font-size: 1rem;">
                <i class="fas fa-trash"></i>
            </button>
        </form>
    </div>
</section>

<section class="panel-card" style="padding: 1.25rem;">
    <div style="display: flex; gap: 1.5rem; align-items: center;">
        <div style="width: 64px; height: 64px; border-radius: 10px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
            <i class="fas fa-receipt"></i>
        </div>
        <div style="flex: 1;">
            @if($order->order_title)
                <p style="margin: 0; font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">{{ $order->order_title }}</p>
            @endif
            <h1 style="margin: 0.25rem 0 0.25rem 0; font-size: 1.3rem; font-weight: 700;">{{ $order->client->business_name ?? $order->client->contact_name }}</h1>
            <p style="margin: 0; font-size: 0.85rem; color: #64748b;">{{ $order->client->email }}</p>
            <span class="status-pill {{ strtolower($order->status ?? 'draft') }}" style="margin-top: 0.25rem; display: inline-block;">{{ ucfirst($order->status ?? 'Draft') }}</span>
        </div>
        <div style="text-align: right;">
            <p style="margin: 0; font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Total</p>
            <strong style="font-size: 1.3rem; display: block; margin-top: 0.25rem;">{{ number_format($order->grand_total ?? 0) }}</strong>
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
            <div style="color: #64748b;">Order #</div>
            <div style="font-weight: 500;">{{ $order->order_number }}</div>

            <div style="color: #64748b;">Order Date</div>
            <div>{{ $order->order_date?->format('d M Y') ?? '—' }}</div>

            <div style="color: #64748b;">Delivery</div>
            <div>{{ $order->delivery_date?->format('d M Y') ?? '—' }}</div>

            <div style="color: #64748b;">Sales Person</div>
            <div>{{ $order->salesPerson->name ?? '—' }}</div>

            <div style="color: #64748b;">Status</div>
            <div><span class="status-pill {{ strtolower($order->status ?? 'draft') }}">{{ ucfirst($order->status ?? 'Draft') }}</span></div>

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
                <th style="font-size: 0.8rem;">Item</th>
                <th style="font-size: 0.8rem;">Qty</th>
                <th style="font-size: 0.8rem;">Price</th>
                <th style="font-size: 0.8rem;">Frequency</th>
                <th style="font-size: 0.8rem;">Duration</th>
                <th style="font-size: 0.8rem;">Users</th>
                <th style="font-size: 0.8rem;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->items as $item)
            <tr>
                <td style="font-size: 0.85rem;"><strong>{{ $item->item_name }}</strong></td>
                <td style="font-size: 0.85rem;">{{ $item->quantity }}</td>
                <td style="font-size: 0.85rem;">{{ number_format($item->unit_price, 2) }}</td>
                <td style="font-size: 0.85rem;">{{ ucfirst($item->frequency ?? '—') }}</td>
                <td style="font-size: 0.85rem;">{{ $item->duration ?? '—' }}</td>
                <td style="font-size: 0.85rem;">{{ $item->no_of_users ?? '—' }}</td>
                <td style="font-size: 0.85rem;"><strong>{{ number_format($item->line_total, 2) }}</strong></td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="padding: 2rem; text-align: center; color: #94a3b8; font-style: italic;">No items in this order.</td>
            </tr>
            @endforelse
        </tbody>
        @if($order->items->count())
        <tfoot>
            <tr style="background: #f8fafc;">
                <td colspan="6" style="padding: 0.75rem 0.5rem; text-align: right; font-weight: 700; font-size: 1rem;">Total:</td>
                <td style="padding: 0.75rem 0.5rem; font-weight: 700; font-size: 1rem;">{{ number_format($order->grand_total, 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</section>

@endsection
