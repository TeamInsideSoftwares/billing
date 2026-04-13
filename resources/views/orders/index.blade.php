@extends('layouts.app')

@section('content')
    @if(!$selectedClient)
        {{-- Client Selection View --}}
        <section class="section-bar">
            <div>
                <h3 style="margin: 0; font-size: 1rem; font-weight: 600; color: #64748b;">Select Client for Orders</h3>
                <p style="margin: 0.2rem 0 0 0; font-size: 0.75rem; color: #64748b;">
                    Choose a client to view their orders
                </p>
            </div>
        </section>

        <div class="panel-card" style="padding: 1.5rem;">
            <div style="max-width: 450px; margin: 0 auto; text-align: center;">
                <label for="client-select" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem; color: #0f172a;">
                    Select Client
                </label>
                <select 
                    id="client-select" 
                    onchange="if(this.value) window.location.href='{{ route('orders.index') }}?c=' + this.value"
                    style="width: 100%; padding: 0.625rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.875rem; background: white; cursor: pointer; transition: all 0.2s;"
                    onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 2px rgba(59, 130, 246, 0.1)';"
                    onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none';"
                >
                    <option value="">-- Choose a Client --</option>
                    @foreach($allClients as $client)
                        <option value="{{ $client->clientid }}">
                            {{ $client->business_name ?? $client->contact_name }}
                            @if($client->email)
                                ({{ $client->email }})
                            @endif
                        </option>
                    @endforeach
                </select>
                <p style="margin-top: 0.75rem; font-size: 0.8rem; color: #64748b;">
                    Select a client to view their orders
                </p>
            </div>
        </div>
    @else
        {{-- Orders List View --}}
        <section class="section-bar">
            <div>
                <h3 style="margin: 0; font-size: 1rem; font-weight: 600; color: #64748b;">
                    Orders: {{ $selectedClient->business_name ?? $selectedClient->contact_name }}
                </h3>
                @if(request('search'))
                    <p style="margin: 0.2rem 0 0 0; font-size: 0.75rem; color: #64748b;">
                        Found {{ $resultCount }} result(s) for "{{ request('search') }}"
                    </p>
                @endif
            </div>
            <div style="display: flex; gap: 0.625rem; align-items: center;">
                <a href="{{ route('orders.create', ['c' => $clientId]) }}" class="primary-button">Create Order</a>
                <a href="{{ route('orders.index') }}" class="secondary-button">Change Client</a>
            </div>
        </section>

        <div class="panel-card" style="padding: 1rem;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;"></th>
                            <th style="width: 12%;">Order #</th>
                            <th style="width: 25%;">Title</th>
                            <th style="width: 12%;">Order Date</th>
                            <th style="width: 12%;">Delivery</th>
                            <th style="width: 10%;">Amount</th>
                            <th style="width: 8%;">Status</th>
                            <th style="width: 16%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($groupedOrders['All Orders'] ?? [] as $order)
                        <tr class="order-row" data-order-id="{{ $order['record_id'] }}">
                            <td>
                                <button type="button" class="expand-order-btn" onclick="toggleOrderItems('{{ $order['record_id'] }}', '{{ $order['clientid'] }}')" style="width: 24px; height: 24px; border: 1px solid #e2e8f0; background: white; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='white';">
                                    <i class="fas fa-chevron-right" id="icon-{{ $order['record_id'] }}" style="font-size: 0.7rem; color: #64748b; transition: transform 0.2s;"></i>
                                </button>
                            </td>
                            <td>
                                <strong style="font-size: 0.85rem;">{{ $order['number'] }}</strong>
                            </td>
                            <td>
                                <span style="font-size: 0.85rem;">{{ $order['order_title'] ?? '-' }}</span>
                            </td>
                            <td>
                                <span style="font-size: 0.8rem;">{{ $order['order_date'] }}</span>
                            </td>
                            <td>
                                <span style="font-size: 0.8rem;">{{ $order['delivery_date'] }}</span>
                            </td>
                            <td>
                                <strong style="font-size: 0.85rem;">{{ $order['amount'] }}</strong>
                            </td>
                            <td>
                                <span class="status-pill {{ strtolower($order['status']) }}">{{ $order['status'] }}</span>
                            </td>
                            <td class="table-actions" style="vertical-align: middle; white-space: nowrap; width: 1%;">
                                <div style="display: flex; gap: 0.375rem; align-items: center;">
                                    <a href="{{ route('orders.show', ['order' => $order['record_id'], 'c' => $clientId]) }}" class="icon-action-btn view" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('invoices.create', ['order_id' => $order['record_id'], 'c' => $clientId]) }}" class="icon-action-btn" style="color: #8b5cf6; border-color: #ddd6fe;" title="Create PI" onmouseover="this.style.background='#f5f3ff'; this.style.borderColor='#8b5cf6'; this.style.transform='scale(1.1)';" onmouseout="this.style.background='white'; this.style.borderColor='#ddd6fe'; this.style.transform='scale(1)';">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                    <a href="{{ route('orders.edit', ['order' => $order['record_id'], 'c' => $clientId]) }}" class="icon-action-btn edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('orders.destroy', ['order' => $order['record_id'], 'c' => $clientId]) }}" class="inline-delete" onsubmit="return confirm('Delete {{ $order['number'] }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-action-btn delete" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        {{-- Order Items Row (Hidden by default) --}}
                        <tr class="order-items-row" id="items-{{ $order['record_id'] }}" style="display: none;">
                            <td colspan="8" style="padding: 0; background: #f8fafc; border-left: 3px solid #3b82f6;">
                                <div style="padding: 0.875rem 1rem;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.625rem;">
                                        <i class="fas fa-box-open" style="color: #3b82f6; font-size: 0.85rem;"></i>
                                        <strong style="font-size: 0.8rem; color: #0f172a;">Order Items</strong>
                                    </div>
                                    <div id="order-items-content-{{ $order['record_id'] }}" style="font-size: 0.8rem; color: #64748b;">
                                        <em>Loading items...</em>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 2.5rem; text-align: center; color: #94a3b8;">
                                <i class="fas fa-receipt" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.3;"></i>
                                <p style="margin: 0; font-size: 0.9rem; font-weight: 500;">No orders found for this client</p>
                                <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem;">Get started by creating your first order.</p>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        function toggleOrderItems(orderRecordId, clientId) {
            const itemsRow = document.getElementById('items-' + orderRecordId);
            const icon = document.getElementById('icon-' + orderRecordId);
            const contentDiv = document.getElementById('order-items-content-' + orderRecordId);
            
            if (itemsRow.style.display === 'none') {
                // Show items
                itemsRow.style.display = 'table-row';
                icon.style.transform = 'rotate(90deg)';
                
                // Load items via AJAX if not already loaded
                if (contentDiv.innerHTML.includes('Loading items...')) {
                    // Use a relative URL that works with subdirectory installations
                    const fetchUrl = `orders/json?order_id=${encodeURIComponent(orderRecordId)}`;
                    // console.log('Fetching:', fetchUrl);
                    
                    fetch(fetchUrl)
                        .then(response => {
                            console.log('Response status:', response.status);
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Received data:', data);
                            if (data.items && data.items.length > 0) {
                                let html = '<div style="display: grid; gap: 0.5rem;">';
                                data.items.forEach((item, index) => {
                                    const itemName = item.item_name || (item.service && item.service.name) || 'Item ' + (index + 1);
                                    const qty = item.quantity || 1;
                                    const price = parseFloat(item.unit_price || 0).toFixed(2);
                                    const tax = parseFloat(item.tax_rate || 0).toFixed(2);
                                    const total = parseFloat(item.line_total || 0).toFixed(2);
                                    
                                    html += `
                                        <div style="background: white; padding: 0.625rem; border-radius: 4px; border: 1px solid #e2e8f0;">
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <div>
                                                    <strong style="color: #0f172a;">${itemName}</strong>
                                                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.15rem;">
                                                        Qty: ${qty} × ${price}
                                                        ${tax > 0 ? ' | Tax: ' + tax + '%' : ''}
                                                    </div>
                                                </div>
                                                <strong style="color: #0f172a; font-size: 0.85rem;">${total}</strong>
                                            </div>
                                        </div>
                                    `;
                                });
                                html += '</div>';
                                contentDiv.innerHTML = html;
                            } else {
                                contentDiv.innerHTML = '<em style="color: #94a3b8;">No items in this order</em>';
                            }
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            contentDiv.innerHTML = '<em style="color: #ef4444;">Error: ' + error.message + '</em>';
                        });
                }
            } else {
                // Hide items
                itemsRow.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        }
        </script>
    @endif
@endsection
