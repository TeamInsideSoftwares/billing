@extends('layouts.app')

@section('header_actions')
    @if($clientId)
        <a href="{{ route('orders.create', ['c' => $clientId]) }}" class="primary-button">
            <i class="fas fa-plus icon-spaced"></i>Create Order
        </a>
        <!-- <a href="{{ route('orders.index', ['c' => $clientId]) }}" class="secondary-button">
            <i class="fas fa-list icon-spaced"></i>View Orders
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
                        <button type="button" id="btnViewOrders" class="secondary-button action-btn-lg">
                            <i class="fas fa-list icon-spaced"></i> View Orders
                        </button>
                        <button type="button" id="btnCreateOrder" class="primary-button action-btn-lg">
                            <i class="fas fa-plus icon-spaced"></i> Create Order
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
                            <form action="{{ route('orders.index') }}" method="GET" class="m-0">
                                <select
                                    name="c"
                                    class="form-control select-client-compact"
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
                    <table class="data-table table-no-margin">
                        <thead>
                            <tr>
                                <th class="w-5"></th>
                                <th class="w-30">Order</th>
                                <th class="w-12">Order Date</th>
                                <th class="w-12">Amount ({{ $groupCurrency }})</th>
                                <th class="w-10">Status</th>
                                <th class="w-16">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($clientOrders as $order)
                            <tr class="order-row" data-order-id="{{ $order['record_id'] ?? '' }}">
                                <td>
                                    <button type="button" class="expand-order-btn" onclick="toggleOrderItems('{{ $order['record_id'] ?? '' }}')">
                                        <i class="fas fa-chevron-right expand-order-icon" id="icon-{{ $order['record_id'] ?? '' }}"></i>
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
                                        <span class="status-pill status-pill-cancelled">Cancelled</span>
                                    @elseif(($order['status'] ?? '') === 'completed')
                                        <span class="status-pill status-pill-completed">Completed</span>
                                    @elseif(($order['status'] ?? '') === 'paused')
                                        <span class="status-pill status-pill-paused">Paused</span>
                                    @else
                                        <span class="status-pill status-pill-running">Running</span>
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
                                            <a href="{{ route('invoices.create', [
                                                'step' => (($order['linked_invoice_for'] ?? 'orders') === 'without_orders') ? 2 : 3,
                                                'invoice_for' => $order['linked_invoice_for'] ?? 'orders',
                                                'c' => $clientId,
                                                'd' => $order['linked_invoice_id'],
                                                'o' => (($order['linked_invoice_for'] ?? 'orders') === 'orders') ? ($order['record_id'] ?? '') : null,
                                                'tax_invoice' => !empty($order['linked_invoice_has_ti']) ? 1 : null,
                                            ]) }}" class="order-create-pi-link order-pill-created" title="Edit PI">
                                                Edit PI
                                            </a>
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
                                                <button type="submit" class="order-create-pi-link order-pill-created" title="Restore Order">
                                                    Restore
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            {{-- Order Items Row (Hidden by default) --}}
                            <tr class="order-items-row" id="items-{{ $order['record_id'] ?? '' }}">
                                <td colspan="6" class="order-items-cell">
                                    <div class="order-items-inner">
                                        <div class="order-items-head">
                                            <i class="fas fa-box-open order-items-head-icon"></i>
                                            <strong class="order-items-head-title">Order Items</strong>
                                        </div>
                                        <div id="order-items-content-{{ $order['record_id'] ?? '' }}" class="order-items-content">
                                            @if(!empty($order['items']) && count($order['items']) > 0)
                                                <div class="order-items-grid">
                                                    @foreach($order['items'] as $item)
                                                        <div class="order-item-card">
                                                            <div class="order-item-card-row">
                                                                <div>
                                                                    <strong class="order-item-name">{{ $item['item_name'] ?? 'Item' }}</strong>
                                                                    <div class="order-item-meta">
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
                                                                <strong class="order-item-total">{{ number_format($item['line_total'] ?? 0, 0) }}</strong>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <em class="text-muted-light">No items in this order</em>
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
                <i class="fas fa-receipt empty-state-icon"></i>
                <p class="no-empty-state-text">No orders found</p>
                <p class="small-text">Create your first order to get started.</p>
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
@endsection
