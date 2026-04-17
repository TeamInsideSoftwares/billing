@extends('layouts.app')

@section('content')
    <section class="section-bar order-index-header" 
    style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
    
    <div>
        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #111827;">All Orders</h3>
        <p style="margin: 0.3rem 0 0; font-size: 0.84rem; color: #6b7280;">
            Grouped by client with quick actions and cleaner list styling.
        </p>
    </div>

    @if($clientId)
        <div style="display: flex; gap: 0.5rem; align-items: end; flex-wrap: wrap;">
            <a href="{{ route('orders.create', ['c' => $clientId]) }}" class="primary-button">
                <i class="fas fa-receipt" style="margin-right: 0.5rem;"></i>Create Order
            </a>
        </div>
    @endif

</section>

    <style>
        .order-index-header {
            margin-bottom: 1rem;
        }

        .order-index-shell {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .order-group {
            overflow: hidden;
        }

        .order-group[open] .accordion-header {
            border-bottom: 1px solid #e5e7eb;
        }

        .order-group .accordion-header {
            padding: 1rem 1.1rem;
            background: #fff;
        }

        .order-client-meta {
            display: inline-flex;
            flex-direction: column;
            gap: 0.12rem;
        }

        .order-client-email {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 500;
        }

        .order-table-wrap {
            padding: 0;
            background: #fff;
        }

        .order-row-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .order-row-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #f3f4f6;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .order-row-text strong {
            display: block;
            font-size: 0.9rem;
            color: #111827;
        }

        .order-row-text span {
            display: block;
            margin-top: 0.15rem;
            font-size: 0.75rem;
            color: #6b7280;
        }

        .order-muted {
            font-size: 0.84rem;
            color: #6b7280;
        }

        .order-amount {
            font-size: 0.9rem;
            font-weight: 600;
            color: #111827;
        }

        .order-empty {
            padding: 3rem;
            text-align: center;
            color: #9ca3af;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
        }

        .order-items-row {
            display: none;
            background: #fbfcfe;
        }

        .order-items-row.active {
            display: table-row;
        }

        .order-client-picker-wrap {
            max-width: 760px;
            margin: 1.5rem auto 0;
        }

        .order-client-picker {
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
        }

        .order-client-picker-head {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: flex-start;
            margin-bottom: 1.1rem;
            padding-bottom: 0.95rem;
            border-bottom: 1px solid #e5e7eb;
            flex-wrap: wrap;
        }

        .order-client-picker-title {
            display: flex;
            gap: 0.85rem;
            align-items: flex-start;
        }

        .order-client-picker-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .order-client-picker-title strong {
            display: block;
            font-size: 1rem;
            color: #0f172a;
        }

        .order-client-picker-title p {
            margin: 0.25rem 0 0;
            font-size: 0.84rem;
            color: #6b7280;
        }

        .order-client-count {
            padding: 0.45rem 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            background: #f8fafc;
            font-size: 0.78rem;
            font-weight: 600;
            color: #475569;
        }

        .order-client-picker form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.85rem;
            align-items: end;
        }

        .order-client-picker-field label {
            display: block;
            margin-bottom: 0.45rem;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #64748b;
        }

        .order-client-picker-field select {
            width: 100%;
            min-height: 46px;
            padding: 0.72rem 0.9rem;
            border: 1px solid #dbe3ee;
            border-radius: 10px;
            background: #f8fafc;
            font-size: 0.9rem;
            color: #0f172a;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .order-client-picker-field select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
            background: #fff;
        }

        .order-client-picker-note {
            margin: 0.85rem 0 0;
            font-size: 0.78rem;
            color: #94a3b8;
        }

        @media (max-width: 720px) {
            .order-client-picker form {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="order-index-shell">
        @if(!$clientId)
        {{-- Client Selection View --}}
        <div class="order-client-picker-wrap">
            <div class="order-client-picker">
                <div class="order-client-picker-head">
                    <div class="order-client-picker-title">
                        <div class="order-client-picker-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div>
                            <strong>Select Client</strong>
                            <p>Choose a client first to load that client’s orders and actions in a cleaner focused view.</p>
                        </div>
                    </div>
                    <span class="order-client-count">{{ $allClients->count() }} client(s)</span>
                </div>
                <form action="{{ route('orders.index') }}" method="GET">
                    <div class="order-client-picker-field">
                        <label for="client-select">Client</label>
                        <select name="c" id="client-select" class="form-control" autofocus>
                            <option value="" selected disabled>Select a client</option>
                            @foreach($allClients as $client)
                                <option value="{{ $client->clientid }}">
                                    {{ $client->business_name ?? $client->contact_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="primary-button" style="min-height: 46px; padding-inline: 1.15rem;">
                        <i class="fas fa-arrow-right" style="margin-right: 0.4rem;"></i> View Orders
                    </button>
                </form>
                <!-- <p class="order-client-picker-note">
                    This keeps the orders screen focused and avoids mixing records from multiple clients in one list.
                </p> -->
            </div>
        </div>
    @else
        {{-- Orders List View - Grouped by Client --}}
        @forelse ($groupedOrders as $clientName => $clientOrders)
            @php
                $firstOrder = $clientOrders->first();
                $clientEmailForGroup = $firstOrder['client_email'] ?? '';
                $clientId = $firstOrder['clientid'] ?? '';
            @endphp
            <details class="category-accordion order-group" open>
                <summary class="accordion-header">
                    <span class="order-client-meta" onclick="event.stopPropagation();">
                        @if($clientId)
                            <form action="{{ route('orders.index') }}" method="GET" style="margin: 0;">
                                <select
                                    name="c"
                                    class="form-control form-control"
                                    style="min-width: 260px; min-height: 34px;"
                                    onchange="this.form.submit()"
                                    onclick="event.stopPropagation();"
                                >
                                    @foreach($allClients as $clientOption)
                                        @php($optionName = $clientOption->business_name ?? $clientOption->contact_name ?? 'Client')
                                        <option value="{{ $clientOption->clientid }}" {{ (string) $clientId === (string) $clientOption->clientid ? 'selected' : '' }}>
                                            {{ $optionName }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <span class="category-title">{{ $clientName }}</span>
                        @endif
                        @if($clientEmailForGroup)
                            <span class="order-client-email">{{ $clientEmailForGroup }}</span>
                        @endif
                    </span>
                    
                    <span class="service-count">{{ count($clientOrders) }} order(s)</span>
                    <span class="accordion-icon"></span>
                </summary>
                <div class="accordion-content order-table-wrap">
                    <table class="data-table" style="margin: 0;">
                        <thead>
                            <tr>
                                <th style="width: 5%;"></th>
                                <th style="width: 30%;">Order</th>
                                <th style="width: 12%;">Order Date</th>
                                <th style="width: 12%;">Amount</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 16%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($clientOrders as $order)
                            <tr class="order-row" data-order-id="{{ $order['record_id'] ?? '' }}">
                                <td>
                                    <button type="button" class="expand-order-btn" onclick="toggleOrderItems('{{ $order['record_id'] ?? '' }}')" style="width: 24px; height: 24px; border: 1px solid #e2e8f0; background: white; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='white';">
                                        <i class="fas fa-chevron-right" id="icon-{{ $order['record_id'] ?? '' }}" style="font-size: 0.7rem; color: #64748b; transition: transform 0.2s;"></i>
                                    </button>
                                </td>
                                <td>
                                    <div class="order-row-title">
                                        <div class="order-row-icon">
                                            <i class="fas fa-receipt"></i>
                                        </div>
                                        <div class="order-row-text">
                                            <strong>{{ $order['order_title'] ?? '-' }}</strong>
                                            <span>{{ $order['number'] ?? '-' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="order-muted">{{ $order['order_date'] ?? '-' }}</span>
                                </td>
                                <td>
                                    <strong class="order-amount">{{ $order['currency'] ?? 'INR' }} {{ $order['amount'] ?? number_format(0, 2) }}</strong>
                                </td>
                                <td>
                                    <span class="status-pill {{ ($order['verified'] ?? false) ? 'verified' : 'unverified' }}">
                                        {{ ($order['verified'] ?? false) ? 'Verified' : 'Unverified' }}
                                    </span>
                                </td>
                                <td class="table-actions">
                                    <a href="{{ route('orders.show', ['order' => $order['record_id'] ?? '' ]) }}" class="icon-action-btn view" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($order['verified'] ?? false)
                                    <a href="{{ route('invoices.create', ['o' => $order['record_id'] ?? '', 'c' => $clientId]) }}" class="icon-action-btn" style="color: #8b5cf6; border-color: #ddd6fe;" title="Create PI" onmouseover="this.style.background='#f5f3ff'; this.style.borderColor='#8b5cf6'; this.style.transform='scale(1.1)';" onmouseout="this.style.background='white'; this.style.borderColor='#ddd6fe'; this.style.transform='scale(1)';">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                    @endif
                                    <a href="{{ route('orders.edit', ['order' => $order['record_id'] ?? '' ]) }}" class="icon-action-btn edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('orders.destroy', ['order' => $order['record_id'] ?? '' ]) }}" class="inline-delete" onsubmit="return confirm('Delete {{ $order['number'] ?? 'this order' }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-action-btn delete" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            {{-- Order Items Row (Hidden by default) --}}
                            <tr class="order-items-row" id="items-{{ $order['record_id'] ?? '' }}" style="display: none;">
                                <td colspan="6" style="padding: 0; background: #f8fafc; border-left: 3px solid #3b82f6;">
                                    <div style="padding: 0.875rem 1rem;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.625rem;">
                                            <i class="fas fa-box-open" style="color: #3b82f6; font-size: 0.85rem;"></i>
                                            <strong style="font-size: 0.8rem; color: #0f172a;">Order Items</strong>
                                        </div>
                                        <div id="order-items-content-{{ $order['record_id'] ?? '' }}" style="font-size: 0.8rem; color: #64748b;">
                                            @if(!empty($order['items']) && count($order['items']) > 0)
                                                <div style="display: grid; gap: 0.5rem;">
                                                    @foreach($order['items'] as $item)
                                                        <div style="background: white; padding: 0.625rem; border-radius: 4px; border: 1px solid #e2e8f0;">
                                                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                                                                <div>
                                                                    <strong style="color: #0f172a;">{{ $item['item_name'] ?? 'Item' }}</strong>
                                                                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.15rem;">
                                                                        Qty: {{ number_format($item['quantity'] ?? 1, 2) }}
                                                                        | Price: {{ $order['currency'] ?? 'INR' }} {{ number_format($item['unit_price'] ?? 0, 2) }}
                                                                        @if(($item['tax_rate'] ?? 0) > 0)
                                                                            | Tax: {{ number_format($item['tax_rate'], 2) }}%
                                                                        @endif
                                                                        @if(($item['discount_percent'] ?? 0) > 0)
                                                                            | Disc: {{ number_format($item['discount_percent'], 2) }}%
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <strong style="color: #0f172a; font-size: 0.85rem;">{{ $order['currency'] ?? 'INR' }} {{ number_format($item['line_total'] ?? 0, 2) }}</strong>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <em style="color: #94a3b8;">No items in this order</em>
                                            @endif
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
            <div class="order-empty">
                <i class="fas fa-receipt" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.3;"></i>
                <p style="margin: 0; font-size: 0.95rem; font-weight: 500;">No orders found</p>
                <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">Create your first order to get started.</p>
            </div>
        @endforelse
    @endif
    </div>

    <style>
        .status-pill.verified { background: #dcfce7; color: #166534; }
        .status-pill.pending { background: #fef3c7; color: #92400e; }
    </style>

    <script>
    function toggleOrderItems(orderRecordId) {
        const itemsRow = document.getElementById('items-' + orderRecordId);
        const icon = document.getElementById('icon-' + orderRecordId);
        
        if (itemsRow.style.display === 'none') {
            // Show items
            itemsRow.style.display = 'table-row';
            icon.style.transform = 'rotate(90deg)';
        } else {
            // Hide items
            itemsRow.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
        }
    }
    </script>
@endsection
