@extends('layouts.app')

@section('header_actions')
    @if($clientId)
        <a href="{{ route('orders.create', ['c' => $clientId]) }}" class="primary-button">
            <i class="fas fa-plus" style="margin-right: 0.5rem;"></i>Create Order
        </a>
        <!-- <a href="{{ route('orders.index', ['c' => $clientId]) }}" class="secondary-button">
            <i class="fas fa-list" style="margin-right: 0.5rem;"></i>View Orders
        </a> -->
    @endif
@endsection

@section('content')
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
                            <strong>Manage Orders</strong>
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
                    <div class="order-client-picker-actions">
                        <button type="button" id="btnViewOrders" class="secondary-button" style="min-height: 46px; padding-inline: 1.15rem;">
                            <i class="fas fa-list" style="margin-right: 0.4rem;"></i> View Orders
                        </button>
                        <button type="button" id="btnCreateOrder" class="primary-button" style="min-height: 46px; padding-inline: 1.15rem;">
                            <i class="fas fa-plus" style="margin-right: 0.4rem;"></i> Create Order
                        </button>
                    </div>
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
                $clientId = $firstOrder['clientid'] ?? '';
                $groupCurrency = $firstOrder['currency'] ?? 'INR';
                $clientForGroup = $allClients->firstWhere('clientid', $clientId);
                $clientGstin = trim((string) (optional($clientForGroup?->billingDetail)->gstin ?? ''));
                $clientTypeLabel = $clientGstin !== '' ? 'B2B' : 'B2C';
                $clientMetaLabel = $clientGstin !== '' ? 'GSTIN available' : 'No GSTIN';
            @endphp
            <section class="order-group">
                <div class="order-group-head">
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
                    </span>
                    <span class="order-client-summary">
                        <span class="service-count">{{ count($clientOrders) }} order(s)</span>
                        <span class="order-client-summary__meta">{{ $clientTypeLabel }} • {{ $clientMetaLabel }}</span>
                    </span>
                </div>
                <div class="order-table-wrap">
                    <table class="data-table" style="margin: 0;">
                        <thead>
                            <tr>
                                <th style="width: 5%;"></th>
                                <th style="width: 30%;">Order</th>
                                <th style="width: 12%;">Order Date</th>
                                <th style="width: 12%;">Amount ({{ $groupCurrency }})</th>
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
                                    <strong class="order-amount">{{ $order['amount'] ?? number_format(0, 0) }}</strong>
                                </td>
                                <td>
                                    @if(($order['status'] ?? '') === 'cancelled')
                                        <span class="status-pill" style="background: #e2e8f0; color: #475569;">Cancelled</span>
                                    @elseif(($order['status'] ?? '') === 'completed')
                                        <span class="status-pill" style="background: #dcfce7; color: #166534;">Completed</span>
                                    @elseif(($order['status'] ?? '') === 'paused')
                                        <span class="status-pill" style="background: #fef3c7; color: #92400e;">Paused</span>
                                    @else
                                        <span class="status-pill" style="background: #dbeafe; color: #1e40af;">Running</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('orders.show', ['order' => $order['record_id'] ?? '' ]) }}" class="icon-action-btn view" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if(($order['verified'] ?? false) && (($order['status'] ?? '') !== 'cancelled'))
                                            @if(!($order['has_pi'] ?? false))
                                            <a href="{{ route('invoices.create', ['step' => 3, 'invoice_for' => 'orders', 'o' => $order['record_id'] ?? '', 'c' => $clientId]) }}" class="order-create-pi-link" title="Create PI">
                                                Create PI
                                            </a>
                                            @else
                                            <span class="order-create-pi-link" style="background: #ecfdf5; border-color: #a7f3d0; color: #047857; cursor: default;" title="PI already created">
                                                PI Created
                                            </span>
                                            @endif
                                        @endif
                                        @if(($order['status'] ?? '') !== 'cancelled')
                                            <a href="{{ route('orders.edit', ['order' => $order['record_id'] ?? '' ]) }}" class="icon-action-btn edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="{{ route('orders.destroy', ['order' => $order['record_id'] ?? '' ]) }}" class="inline-delete" onsubmit="return confirm('Cancel {{ $order['number'] ?? 'this order' }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="icon-action-btn delete" title="Cancel Order">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('orders.restore', ['order' => $order['record_id'] ?? '' ]) }}" class="inline-delete" onsubmit="return confirm('Restore {{ $order['number'] ?? 'this order' }}?')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="order-create-pi-link" style="background: #ecfdf5; border-color: #a7f3d0; color: #047857;" title="Restore Order">
                                                    Restore
                                                </button>
                                            </form>
                                        @endif
                                    </div>
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
                                                                        Qty: {{ number_format($item['quantity'] ?? 1, 0) }}
                                                                        @if(!empty($item['duration']))
                                                                            | Dur: {{ $item['duration'] }}
                                                                        @endif
                                                                        | Price: {{ number_format($item['unit_price'] ?? 0, 0) }}
                                                                        @if(($item['tax_rate'] ?? 0) > 0)
                                                                            | Tax: {{ number_format($item['tax_rate'], 0) }}%
                                                                        @endif
                                                                        @if(($item['discount_percent'] ?? 0) > 0)
                                                                            | Disc: {{ number_format($item['discount_percent'], 0) }}%
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <strong style="color: #0f172a; font-size: 0.85rem;">{{ number_format($item['line_total'] ?? 0, 0) }}</strong>
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
            </section>
        @empty
            <div class="order-empty">
                <i class="fas fa-receipt" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.3;"></i>
                <p style="margin: 0; font-size: 0.95rem; font-weight: 500;">No orders found</p>
                <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">Create your first order to get started.</p>
            </div>
        @endforelse
    @endif
    </div>

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

    // Handle View Orders button
    document.getElementById('btnViewOrders')?.addEventListener('click', function() {
        const clientId = document.getElementById('client-select')?.value;
        if (clientId) {
            window.location.href = "{{ route('orders.index') }}?c=" + encodeURIComponent(clientId);
        } else {
            alert('Please select a client first.');
        }
    });

    // Handle Create Order button
    document.getElementById('btnCreateOrder')?.addEventListener('click', function() {
        const clientId = document.getElementById('client-select')?.value;
        if (clientId) {
            window.location.href = "{{ route('orders.create') }}?c=" + encodeURIComponent(clientId);
        } else {
            alert('Please select a client first.');
        }
    });
    </script>

    <style>
    .order-client-picker-actions {
        display: flex;
        gap: 0.6rem;
        margin-top: 0.75rem;
    }

    .order-client-picker-actions .secondary-button {
        background: #ffffff;
        border: 1px solid #d1d5db;
        color: #374151;
    }

    .order-client-picker-actions .secondary-button:hover {
        background: #f9fafb;
        border-color: #9ca3af;
    }

    .order-client-picker-actions .primary-button {
        background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
        border: none;
        color: #ffffff;
    }

    .order-client-picker-actions .primary-button:hover {
        background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%);
    }

    @media (max-width: 768px) {
        .order-client-picker-actions {
            flex-direction: column;
        }
        
        .order-client-picker-actions .secondary-button,
        .order-client-picker-actions .primary-button {
            width: 100%;
        }
    }
    </style>
@endsection
