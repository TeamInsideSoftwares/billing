<!-- Step 2: Select Orders -->
<div id="step2" class="invoice-step">

    <!-- Header -->
    <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
        <button type="button" id="btnBackToStep1" class="secondary-button" style="padding: 0.4rem 0.8rem;">
            &larr; Back
        </button>
    </div>

    <input type="hidden" name="clientid" value="{{ request('clientid') }}">
    <input type="hidden" name="orderid" id="orderid">
    <input type="hidden" name="items_data" id="items_data">

    <!-- Orders Table -->
    <div class="workflow-panel">
        <div class="table-shell">
            <table class="data-table" id="ordersTable">
                <thead>
                    <tr>
                        <th style="width:50px;">Select</th>
                        <th>Order</th>
                        <th style="width:90px;">Date</th>
                        <th style="width:110px;">Amount</th>
                        <th style="width:90px;">Verified</th>
                    </tr>
                </thead>
                <tbody id="ordersBody"></tbody>
            </table>

            <div id="noOrdersMessage" class="empty-state" style="display:none;">
                No orders available
            </div>
        </div>
    </div>

    <!-- Continue -->
    <div style="margin-top: 1rem;">
        <button type="button" id="btnNextToStep3" class="primary-button" disabled style="width:100%;">
            Continue →
        </button>
    </div>
</div>

<!-- 🔹 COMPACT TABLE CSS -->
<style>
.data-table th,
.data-table td {
    padding: 6px 8px;
    font-size: 13px;
    line-height: 1.2;
}

.data-table tr {
    height: 34px;
}

/* Expand row */
.order-items-row td {
    background: #f8fafc;
    padding: 10px;
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
    from { opacity:0; transform:translateY(-4px); }
    to { opacity:1; transform:translateY(0); }
}

</style>

<!-- 🔹 SCRIPT -->
<script>
(function() {

    const clientId = "{{ request('clientid') }}";
    const ordersBody = document.getElementById('ordersBody');
    const noOrdersMessage = document.getElementById('noOrdersMessage');
    const btnNext = document.getElementById('btnNextToStep3');
    const orderIdInput = document.getElementById('orderid');
    const itemsDataInput = document.getElementById('items_data');

    let selectedOrderId = null;
    let orderItems = [];

    // ✅ Load Orders
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

                row.innerHTML = `
                    <td>
                        <label class="custom-radio">
                            <input type="radio" name="selected_order" value="${order.orderid}">
                        </label>
                    </td>

                    <td>
                        <strong>${order.order_title || 'Untitled'}</strong>
                        <div style="font-size:11px; color:#64748b;">
                            ${order.order_number}
                        </div>
                    </td>

                    <td>${order.order_date}</td>

                    <td>
                        ${order.currency} 
                        ${Number(order.grand_total).toLocaleString('en-IN', {minimumFractionDigits:2})}
                    </td>

                    <td>
                        ${verified 
                            ? '<span style="color:#16a34a; font-weight:600;">✔</span>' 
                            : '<span style="color:#dc2626;">✖</span>'}
                    </td>
                `;

                ordersBody.appendChild(row);
            });

            attachRadioEvents();
        });
    }

    // ✅ Radio change
    function attachRadioEvents() {
        document.querySelectorAll('input[name="selected_order"]').forEach(radio => {

            radio.addEventListener('change', function() {

                selectedOrderId = this.value;
                orderIdInput.value = selectedOrderId;

                // remove old preview
                document.querySelectorAll('.order-items-row').forEach(r => r.remove());

                const currentRow = this.closest('tr');

                loadItemsInline(selectedOrderId, currentRow);
            });
        });
    }

    // ✅ Load items inline
    function loadItemsInline(orderId, row) {

        fetch(`{{ route('invoices.order-items', ['orderid' => '__ORDERID__']) }}`
            .replace('__ORDERID__', orderId))
        .then(res => res.json())
        .then(data => {

            orderItems = data.items;
            itemsDataInput.value = JSON.stringify(orderItems);
            btnNext.disabled = false;

            let html = '';

            orderItems.forEach(item => {
                html += `
                    <div style="padding:6px 0; border-bottom:1px dashed #e2e8f0; font-size:12px;">
                        
                        <!-- Top Row -->
                        <div style="display:flex; justify-content:space-between; gap:10px;">
                            <span style="font-weight:600; color:#1e293b;">
                                ${item.item_name || 'Item'} (x${item.quantity || 1})
                            </span>
                            <span style="font-weight:600;">
                                ${item.currency || ''} 
                                ${Number(item.line_total || 0).toLocaleString('en-IN', {minimumFractionDigits:2})}
                            </span>
                        </div>

                        <!-- Details Row -->
                        <div style="margin-top:2px; color:#64748b;">
                            ${
                                [
                                    item.price ? `Unit: ${item.price}` : '',
                                    item.tax_rate ? `Tax: ${item.tax_rate}%` : '',
                                    item.discount ? `Disc: ${item.discount}%` : '',
                                    item.no_of_users > 1 ? `Users: ${item.no_of_users}` : '',
                                    item.frequency ? `Freq: ${item.frequency}` : '',
                                    item.duration ? `Dur: ${item.duration}` : ''
                                ].filter(Boolean).join(' | ')
                            }
                        </div>
                        <!-- Dates Row -->
                        ${
                            (item.start_date || item.end_date) 
                            ? `<div style="color:#94a3b8; font-size:11px;">
                                ${item.start_date ? `Start: ${item.start_date}` : ''}
                                ${item.end_date ? ` | End: ${item.end_date}` : ''}
                            </div>`
                            : ''
                        }
                    </div>
                `;
            });

            if (!html) {
                html = '<div style="color:#94a3b8;">No items</div>';
            }

            const newRow = document.createElement('tr');
            newRow.classList.add('order-items-row');

            newRow.innerHTML = `
                <td colspan="5">
                    <div style="border-left:3px solid #3b82f6; padding-left:10px;">
                        ${html}
                    </div>
                </td>
            `;

            row.insertAdjacentElement('afterend', newRow);
        });
    }

    // ✅ Next step
    btnNext.addEventListener('click', function() {

        if (!selectedOrderId) {
            alert('Select order first');
            return;
        }

        fetch("{{ route('invoices.save-draft') }}", {
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'X-CSRF-TOKEN':'{{ csrf_token() }}'
            },
            body: JSON.stringify({
                clientid: clientId,
                orderid: selectedOrderId,
                invoice_for: 'orders',
                items_data: itemsDataInput.value
            })
        })
        .then(() => {
            window.location.href = "{{ route('invoices.create') }}?step=3&clientid=" + clientId;
        });
    });

    // init
    loadOrders();

})();
</script>