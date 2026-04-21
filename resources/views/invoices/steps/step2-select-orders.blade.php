<!-- Step 2: Select Orders -->
<div id="step2" class="invoice-step">

    <div class="invoice-step-toolbar">
        <button type="button" id="btnBackToStep1" class="secondary-button" style="padding: 0.4rem 0.8rem;">
            &larr; Back
        </button>
    </div>

    <input type="hidden" name="clientid" value="{{ request('clientid') }}">
    <input type="hidden" name="orderid" id="orderid">
    <input type="hidden" name="items_data" id="items_data">

    <div class="section-title-card">
        <h4>Select Source Order</h4>
        <p>Select one verified order. Its details and items will expand directly under that row, and that exact order will be used to create the PI.</p>
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

    <div style="margin-top: 1rem;">
        <button type="button" id="btnNextToStep3" class="primary-button" disabled style="width:100%;">
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
    padding: 1rem 1rem 1.1rem;
    border-left: 3px solid #4f46e5;
}

.order-detail-header {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    flex-wrap: wrap;
    margin-bottom: 0.9rem;
}

.order-detail-meta {
    margin: 0.3rem 0 0;
    color: #6b7280;
    font-size: 0.82rem;
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
    padding: 0.7rem 0.75rem;
    font-size: 0.72rem;
    font-weight: 600;
    color: #6b7280;
    text-align: left;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.order-detail-items td {
    padding: 0.75rem;
    font-size: 0.83rem;
    border-bottom: 1px solid #e5e7eb;
    background: #fff;
}

.order-detail-items tr:last-child td {
    border-bottom: 0;
}

.order-detail-empty {
    padding: 1rem;
    text-align: center;
    color: #9ca3af;
    font-size: 0.85rem;
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
    const clientId = "{{ request('clientid') }}";
    const ordersBody = document.getElementById('ordersBody');
    const noOrdersMessage = document.getElementById('noOrdersMessage');
    const btnNext = document.getElementById('btnNextToStep3');
    const btnBackToStep1 = document.getElementById('btnBackToStep1');
    const orderIdInput = document.getElementById('orderid');
    const itemsDataInput = document.getElementById('items_data');

    let selectedOrderId = null;
    let orderItems = [];

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
                    <td>${order.currency} ${Number(order.grand_total).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
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
                                    <strong class="invoice-meta-value">${order.currency} ${Number(order.grand_total).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</strong>
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
        fetch(`{{ route('invoices.order-items', ['orderid' => '__ORDERID__']) }}`.replace('__ORDERID__', orderId))
        .then(res => res.json())
        .then(data => {
            orderItems = data.items || [];
            itemsDataInput.value = JSON.stringify(orderItems);
            btnNext.disabled = orderItems.length === 0;
            renderOrderItems(orderId, orderItems, data.order || {});
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
                ? `${Number(item.discount_amount).toLocaleString('en-IN', { minimumFractionDigits: 2 })}${Number(item.discount_percent || 0) > 0 ? `<div style="font-size:0.7rem;color:#6b7280;">(${Number(item.discount_percent).toFixed(1)}%)</div>` : ''}`
                : '-';

            const frequencyDuration = `
                ${item.frequency ? item.frequency.charAt(0).toUpperCase() + item.frequency.slice(1) : '-'}
                ${item.duration ? `<div style="font-size:0.75rem;color:#6b7280;">${item.duration}</div>` : ''}
            `;

            const dates = [
                item.start_date ? `S: ${item.start_date}` : '',
                item.end_date ? `E: ${item.end_date}` : '',
                item.delivery_date ? `D: ${item.delivery_date}` : ''
            ].filter(Boolean).join('<br>');

            return `
                <tr>
                    <td><strong>${item.item_name || 'Item'}</strong></td>
                    <td style="text-align:center;">${Math.max(1, Math.round(Number(item.quantity || 1))).toLocaleString('en-IN')}</td>
                    <td style="text-align:right;">${Number(item.unit_price || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                    <td style="text-align:right;">${Number(item.tax_rate || 0).toFixed(2)}%</td>
                    <td style="text-align:right;">${discount}</td>
                    <td>${frequencyDuration}</td>
                    <td style="text-align:center;">${item.no_of_users || '-'}</td>
                    <td style="font-size:0.78rem; color:#6b7280;">${dates || '-'}</td>
                    <td style="text-align:right;"><strong>${order.currency || 'INR'} ${Number(item.line_total || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</strong></td>
                </tr>
            `;
        }).join('');

        container.innerHTML = `
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th style="text-align:center;">Qty</th>
                        <th style="text-align:right;">Price</th>
                        <th style="text-align:right;">Tax %</th>
                        <th style="text-align:right;">Discount</th>
                        <th>Frequency / Duration</th>
                        <th style="text-align:center;">Users</th>
                        <th>Dates</th>
                        <th style="text-align:right;">Total</th>
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

        fetch("{{ route('invoices.save-draft') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                clientid: clientId,
                orderid: selectedOrderId,
                invoice_for: 'orders',
                items_data: itemsDataInput.value
            })
        })
        .then(() => {
            window.location.href = "{{ route('invoices.create') }}?step=3&invoice_for=orders&clientid=" + clientId + "&orderid=" + selectedOrderId;
        });
    });

    btnBackToStep1.addEventListener('click', function() {
        window.location.href = "{{ route('invoices.create') }}?step=1&clientid=" + clientId;
    });

    loadOrders();
})();
</script>
