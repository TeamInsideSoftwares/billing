@php
    $selectedClient = $clients->firstWhere('clientid', request('c', request('clientid')));
    $selectedClientName = $selectedClient ? ($selectedClient->business_name ?? $selectedClient->contact_name ?? 'Unknown Client') : 'No Client Selected';
    $selectedClientEmail = $selectedClient->email ?? '';
@endphp
<!-- Step 2: Select Orders -->
<div id="step2" class="invoice-step">
    {{-- Client Info Header with Back Button --}}
    <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <button type="button" id="btnBackToStep1" class="secondary-button" style="padding: 0.4rem 0.65rem; flex-shrink: 0; font-size: 0.85rem;">
                <i class="fas fa-arrow-left" style="font-size: 0.8rem;"></i>
            </button>
            <div style="width: 1px; height: 32px; background: #d1d5db; flex-shrink: 0;"></div>
            <div style="width: 36px; height: 36px; border-radius: 8px; background: #e0e7ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas fa-user"></i>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 0.9rem; font-weight: 600; color: #111827; margin-top: 0.1rem;">{{ $selectedClientName }}</div>
                @if($selectedClientEmail)
                <div style="font-size: 0.78rem; color: #64748b; margin-top: 0.05rem;">{{ $selectedClientEmail }}</div>
                @endif
            </div>
            <div style="text-align: right; flex-shrink: 0;">
            </div>
        </div>
    </div>

    <input type="hidden" name="clientid" value="{{ request('c', request('clientid')) }}">
    <input type="hidden" name="orderid" id="orderid">
    <input type="hidden" name="items_data" id="items_data">
    <input type="hidden" name="pi_number" id="pi_number" value="{{ $invoice?->pi_number ?? $nextInvoiceNumber }}">
    <input type="hidden" name="issue_date" id="step2_select_orders_issue_date" value="{{ date('Y-m-d') }}">
    <input type="hidden" name="due_date" id="step2_select_orders_due_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}">
    <input type="hidden" name="notes" id="step2_select_orders_notes" value="">

    <div class="section-title-card" style="margin-bottom: 0.65rem;">
        <h4>Select Source Order</h4>
        <p>Select one verified order. The items expand inline so you can move faster.</p>
    </div>

    <div class="workflow-panel" style="margin-top: 0;">
        <div class="table-shell">
            <table class="data-table" id="ordersTable" style="margin: 0;">
                <thead>
                    <tr>
                        <th style="width: 56px;">Select</th>
                        <th style="width: 28%;">Order Title</th>
                        <th style="width: 13%;">Order Date</th>
                        <th style="width: 13%;">Delivery</th>
                        <th style="width: 14%;">Sales Person</th>
                        <th style="width: 12%;">Items</th>
                        <th style="width: 10%;">Amount</th>
                        <th style="width: 10%;">Status</th>
                    </tr>
                </thead>
                <tbody id="ordersBody"></tbody>
            </table>

            <div id="noOrdersMessage" class="empty-state" style="display:none;">
                No verified orders are available for this client.
            </div>
        </div>
    </div>

    <div style="margin-top: 0.75rem;">
        <button type="button" id="btnNextToStep3" class="primary-button" disabled style="width:100%; padding: 0.7rem 1rem; font-size: 0.9rem;">
            Continue &rarr;
        </button>
    </div>
</div>

<style>
#ordersTable tbody tr.order-option-row {
    cursor: pointer;
}

#ordersTable tbody tr.order-option-row.is-selected {
    background: #f8faff;
}

#ordersTable tbody tr.order-option-row.is-selected td {
    border-bottom-color: #c7d2fe;
}

.order-ref {
    display: block;
    margin-top: 0.15rem;
    font-size: 11px;
    color: #6b7280;
}

.order-detail-row td {
    padding: 0;
    background: #fbfcfe;
    border-bottom: 1px solid #dbe3f3;
}

.order-detail-shell {
    padding: 0.62rem 0.68rem 0.72rem;
    border-left: 3px solid #4f46e5;
}

.order-detail-header {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    flex-wrap: wrap;
    margin-bottom: 0.62rem;
}

.order-detail-meta {
    margin: 0.2rem 0 0;
    color: #6b7280;
    font-size: 0.72rem;
}

.order-detail-items {
    overflow-x: auto;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #fff;
}

.order-detail-items table {
    width: 100%;
    border-collapse: collapse;
}

.order-detail-items th {
    padding: 0.42rem 0.52rem;
    font-size: 0.68rem;
    font-weight: 600;
    color: #6b7280;
    text-align: left;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.order-detail-items td {
    padding: 0.44rem 0.52rem;
    font-size: 0.74rem;
    border-bottom: 1px solid #e5e7eb;
    background: #fff;
}

.order-detail-items tr:last-child td {
    border-bottom: 0;
}

.order-detail-empty {
    padding: 0.62rem;
    text-align: center;
    color: #9ca3af;
    font-size: 0.74rem;
}

@media (max-width: 900px) {
    .order-detail-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<script>
(function() {
    const clientId = "{{ request('c', request('clientid')) }}";
    const preselectedOrderId = "{{ request('o', request('orderid', '')) }}";
    const hasPreselectedOrder = preselectedOrderId && preselectedOrderId !== '0';
    const ordersBody = document.getElementById('ordersBody');
    const noOrdersMessage = document.getElementById('noOrdersMessage');
    const btnNext = document.getElementById('btnNextToStep3');
    const btnBackToStep1 = document.getElementById('btnBackToStep1');
    const orderIdInput = document.getElementById('orderid');
    const itemsDataInput = document.getElementById('items_data');

    let selectedOrderId = null;
    let orderItems = [];
    const orderItemsCache = new Map();
    const orderSummaryCache = new Map();
    let activeOrderRequestController = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function loadOrders() {
        fetch("{{ route('invoices.client-orders') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ clientid: clientId })
        })
        .then(res => res.json())
        .then(orders => {
            noOrdersMessage.style.display = 'none';
            if (!orders.length) {
                noOrdersMessage.style.display = 'block';
                return;
            }

            ordersBody.innerHTML = '';

            orders.forEach(order => {
                const verified = String(order.is_verified).toLowerCase() === 'yes';
                const row = document.createElement('tr');
                row.className = 'order-option-row';
                row.dataset.orderId = order.orderid;

                row.innerHTML = `
                    <td>
                        <label class="custom-radio">
                            <input type="radio" name="selected_order" value="${order.orderid}">
                        </label>
                    </td>
                    <td>
                        <strong>${order.order_title || 'Untitled Order'}</strong>
                        <span class="order-ref">${order.order_number}</span>
                    </td>
                    <td>${order.order_date || 'N/A'}</td>
                    <td>${order.delivery_date || 'N/A'}</td>
                    <td>${order.sales_person || '-'}</td>
                    <td>${order.item_count || 0} item(s)</td>
                    <td>${(order.currency || 'INR')} ${Number(order.grand_total).toLocaleString('en-US', { minimumFractionDigits: 0 })}</td>
                    <td>
                        <span style="font-size: 0.8rem; padding: 0.25rem 0.5rem; border-radius: 4px; font-weight: 500; background: ${verified ? '#dcfce7' : '#fef3c7'}; color: ${verified ? '#16a34a' : '#d97706'};">
                            ${verified ? 'Verified' : 'Unverified'}
                        </span>
                    </td>
                `;

                const detailRow = document.createElement('tr');
                detailRow.className = 'order-detail-row';
                detailRow.id = `order-detail-${order.orderid}`;
                detailRow.style.display = 'none';
                detailRow.innerHTML = `
                    <td colspan="8">
                        <div class="order-detail-shell">
                            <div class="order-detail-header">
                                <div>
                                    <span class="invoice-meta-label">Selected Order</span>
                                    <strong class="invoice-meta-value">${order.order_title || order.order_number}</strong>
                                    <p class="order-detail-meta">
                                        ${order.order_number} | ${order.order_date || 'N/A'} | Delivery: ${order.delivery_date || 'N/A'} | Sales Person: ${order.sales_person || '-'}
                                    </p>
                                </div>
                                <div class="invoice-side-meta">
                                    <span class="invoice-meta-label">Grand Total</span>
                                    <strong class="invoice-meta-value">${(order.currency || 'INR')} ${Number(order.grand_total).toLocaleString('en-US', { minimumFractionDigits: 0 })}</strong>
                                </div>
                            </div>
                            <div class="order-detail-items" id="order-detail-items-${order.orderid}">
                                <div class="order-detail-empty">Loading order items...</div>
                            </div>
                        </div>
                    </td>
                `;

                ordersBody.appendChild(row);
                ordersBody.appendChild(detailRow);
            });

            attachOrderEvents();

            if (hasPreselectedOrder) {
                const preselectedRadio = ordersBody.querySelector(`input[name="selected_order"][value="${preselectedOrderId}"]`);
                if (preselectedRadio) {
                    preselectedRadio.checked = true;
                    preselectedRadio.dispatchEvent(new Event('change'));
                }
            }
        });
    }

    function attachOrderEvents() {
        document.querySelectorAll('input[name="selected_order"]').forEach(radio => {
            radio.addEventListener('change', function() {
                selectOrder(this.value, this.closest('tr'));
            });
        });

        document.querySelectorAll('.order-option-row').forEach(row => {
            row.addEventListener('click', function(event) {
                if (event.target.closest('input, label, button, a')) {
                    return;
                }

                const radio = this.querySelector('input[name="selected_order"]');
                if (radio) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                }
            });
        });
    }

    function selectOrder(orderId, row) {
        if (selectedOrderId === orderId && orderItemsCache.has(orderId)) {
            orderItems = orderItemsCache.get(orderId) || [];
            itemsDataInput.value = JSON.stringify(orderItems);
            btnNext.disabled = orderItems.length === 0;
            return;
        }

        selectedOrderId = orderId;
        orderIdInput.value = selectedOrderId;

        document.querySelectorAll('.order-option-row').forEach(item => item.classList.remove('is-selected'));
        document.querySelectorAll('.order-detail-row').forEach(item => item.style.display = 'none');

        row.classList.add('is-selected');
        const detailRow = document.getElementById(`order-detail-${orderId}`);
        if (detailRow) {
            detailRow.style.display = 'table-row';
        }

        loadOrderDetails(orderId);
    }

    function loadOrderDetails(orderId) {
        if (activeOrderRequestController) {
            activeOrderRequestController.abort();
        }

        if (orderItemsCache.has(orderId)) {
            const cachedItems = orderItemsCache.get(orderId) || [];
            const cachedOrder = orderSummaryCache.get(orderId) || {};
            orderItems = cachedItems;
            itemsDataInput.value = JSON.stringify(orderItems);
            btnNext.disabled = orderItems.length === 0;
            renderOrderItems(orderId, cachedItems, cachedOrder);
            return;
        }

        activeOrderRequestController = new AbortController();
        const container = document.getElementById(`order-detail-items-${orderId}`);
        if (container) {
            container.innerHTML = '<div class="order-detail-empty">Loading order items...</div>';
        }

        fetch(`{{ route('invoices.order-items', ['orderid' => '__ORDERID__']) }}`.replace('__ORDERID__', orderId), {
            signal: activeOrderRequestController.signal
        })
        .then(res => res.json())
        .then(data => {
            orderItems = data.items || [];
            orderItemsCache.set(orderId, orderItems);
            orderSummaryCache.set(orderId, data.order || {});
            itemsDataInput.value = JSON.stringify(orderItems);
            btnNext.disabled = orderItems.length === 0;
            renderOrderItems(orderId, orderItems, data.order || {});
        })
        .catch(error => {
            if (error?.name === 'AbortError') return;
            if (container) {
                container.innerHTML = '<div class="order-detail-empty">Unable to load items. Try selecting again.</div>';
            }
        })
        .finally(() => {
            activeOrderRequestController = null;
        });
    }

    function renderOrderItems(orderId, items, order) {
        const container = document.getElementById(`order-detail-items-${orderId}`);
        if (!container) {
            return;
        }

        if (!items.length) {
            container.innerHTML = '<div class="order-detail-empty">No items found in this order.</div>';
            return;
        }

        const rows = items.map(item => {
            const discount = Number(item.discount_amount || 0) > 0
                ? `${Number(item.discount_amount).toLocaleString('en-US', { minimumFractionDigits: 0 })}${Number(item.discount_percent || 0) > 0 ? `<div style="font-size:0.7rem;color:#6b7280;">(${Number(item.discount_percent).toFixed(1)}%)</div>` : ''}`
                : '-';
            const description = escapeHtml(item.item_description || '').trim();
            const itemName = escapeHtml(item.item_name || 'Item');

            const frequencyDuration = item.duration && item.frequency
                ? `${item.duration} ${item.frequency}`
                : (item.frequency || '-');

            const dates = [
                item.start_date ? `S: ${item.start_date}` : '',
                item.end_date ? `E: ${item.end_date}` : '',
                item.delivery_date ? `D: ${item.delivery_date}` : ''
            ].filter(Boolean).join('<br>');

            return `
                <tr>
                    <td>
                        <strong>${itemName}</strong>
                        ${description ? `<div style="margin-top:0.18rem; font-size:0.74rem; color:#6b7280; white-space: pre-wrap;">${description}</div>` : ''}
                    </td>
                    <td style="text-align:center;">${Math.max(1, Math.round(Number(item.quantity || 1))).toLocaleString('en-US')}</td>
                    <td style="text-align:right;">${(order.currency || 'INR')} ${Number(item.unit_price || 0).toLocaleString('en-US', { minimumFractionDigits: 0 })}</td>
                    <td style="text-align:right;">${Number(item.tax_rate || 0).toFixed(0)}%</td>
                    <td style="text-align:right;">${discount}</td>
                    <td>${frequencyDuration}</td>
                    <td style="text-align:center;">${item.no_of_users || '-'}</td>
                    <td style="font-size:0.78rem; color:#6b7280;">${dates || '-'}</td>
                    <td style="text-align:right;"><strong>${(order.currency || 'INR')} ${Math.max(0, Number(item.line_total || 0) - Number(item.discount_amount || ((Number(item.line_total || 0) * Number(item.discount_percent || 0)) / 100) || 0)).toLocaleString('en-US', { minimumFractionDigits: 0 })}</strong></td>
                </tr>
            `;
        }).join('');

        container.innerHTML = `
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th style="text-align:center;">Qty</th>
                        <th style="text-align:right;">Price ({{ $selectedClientCurrency }})</th>
                        <th style="text-align:right;">Tax %</th>
                        <th style="text-align:right;">Discount</th>
                        <th>Frequency / Duration</th>
                        <th style="text-align:center;">Users</th>
                        <th>Dates</th>
                        <th style="text-align:right;">Total ({{ $selectedClientCurrency }})</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        `;
    }

    btnNext.addEventListener('click', function() {
        if (!selectedOrderId) {
            alert('Select order first');
            return;
        }

        // Save orderid to hidden input
        orderIdInput.value = selectedOrderId;

        // Redirect to step3 without saving draft
        const clientToken = encodeURIComponent(clientId);
        const orderToken = encodeURIComponent(selectedOrderId);
        window.location.href = "{{ route('invoices.create') }}?step=3&invoice_for=orders&c=" + clientToken + "&o=" + orderToken;
    });

    btnBackToStep1.addEventListener('click', function() {
        const clientToken = encodeURIComponent(clientId);
        window.location.href = "{{ route('invoices.create') }}?step=1&c=" + clientToken;
    });

    // Load draft items when editing
    function loadItems() {
        const draftId = "{{ request('d', '') }}";
        
        if (!draftId) return;
        
        const draftUrl = new URL("{{ route('invoices.get-draft', ['clientid' => '__CLIENTID__']) }}".replace('__CLIENTID__', clientId), window.location.origin);
        draftUrl.searchParams.set('invoice_for', 'orders');
        draftUrl.searchParams.set('d', draftId);
        
        fetch(draftUrl.toString())
            .then(response => response.json())
            .then(data => {
                if (data.draft) {
                    if (data.draft.items && data.draft.items.length > 0) {
                        orderItems = data.draft.items;
                        itemsDataInput.value = JSON.stringify(orderItems);
                        btnNext.disabled = orderItems.length === 0;
                    }
                    
                    if (data.draft.orderid) {
                        orderIdInput.value = data.draft.orderid;
                        selectedOrderId = data.draft.orderid;
                        
                        // Select the order in the UI
                        const preselectedRadio = ordersBody.querySelector(`input[name="selected_order"][value="${data.draft.orderid}"]`);
                        if (preselectedRadio) {
                            preselectedRadio.checked = true;
                            preselectedRadio.dispatchEvent(new Event('change'));
                        }
                    }
                    
                    if (data.draft.issue_date) {
                        const issueDateField = document.getElementById('issue_date');
                        if (issueDateField) {
                            issueDateField.value = data.draft.issue_date;
                        }
                        document.getElementById('step2_select_orders_issue_date').value = data.draft.issue_date;
                    }
                    if (data.draft.due_date) {
                        const dueDateField = document.getElementById('due_date');
                        if (dueDateField) {
                            dueDateField.value = data.draft.due_date;
                        }
                        document.getElementById('step2_select_orders_due_date').value = data.draft.due_date;
                    }
                    if (data.draft.notes) {
                        const notesField = document.getElementById('notes');
                        if (notesField) {
                            notesField.value = data.draft.notes;
                        }
                        document.getElementById('step2_select_orders_notes').value = data.draft.notes;
                    }
                }
            })
            .catch(error => {
                console.error('Failed to load draft items:', error);
            });
    }
    
    // Initialize
    loadItems();
    loadOrders();
})();
</script>
