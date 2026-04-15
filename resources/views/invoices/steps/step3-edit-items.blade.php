<!-- Step 3: Edit Items (For Orders & Renewal) -->
<div id="step3" class="invoice-step">
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <button type="button" id="btnBackToStep2" class="secondary-button" style="padding: 0.5rem 1rem;">&larr; Back to Step 2</button>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span style="font-size: 0.75rem; padding: 0.3rem 0.7rem; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; border-radius: 20px; font-weight: 600; border: 1px solid #f59e0b;">
                <i class="fas fa-file-invoice" style="margin-right: 0.3rem;"></i>Proforma Invoice
            </span>
            <div style="text-align: right;">
                <span class="invoice-meta-label">Invoice Number</span>
                <strong class="invoice-meta-value">{{ $nextInvoiceNumber }}</strong>
            </div>
        </div>
    </div>

    <div style="margin-bottom: 1.5rem;">
        <label for="invoice_title" class="field-label">Invoice Title</label>
        <input type="text" id="invoice_title" name="invoice_title" class="form-input" placeholder="e.g. Website Development - Monthly Subscription">
    </div>

    <input type="hidden" name="clientid" value="{{ request('clientid') }}">
    <input type="hidden" name="orderid" value="{{ request('orderid', '') }}">
    <input type="hidden" name="subtotal" id="subtotal" value="0.00">
    <input type="hidden" name="tax_total" id="tax_total" value="0.00">
    <input type="hidden" name="grand_total" id="grand_total" value="0.00">
    <input type="hidden" name="items_data" id="items_data" value="">
    <input type="hidden" name="currency_code" id="currency_code" value="INR">

    <div id="itemsSection" class="workflow-panel">
        <div class="panel-heading-row">
            <div>
                <h4 style="margin: 0; font-size: 1rem; color: #334155;">
                    @if(request('orderid'))
                        Edit Items from Order
                    @else
                        Edit Invoice Items
                    @endif
                </h4>
                <p style="margin: 0.2rem 0 0 0; color: #64748b; font-size: 0.85rem;">Adjust quantity, pricing, tax, and other details before proceeding.</p>
            </div>
        </div>
        <div class="table-shell">
            <table class="data-table" id="itemsTable" style="margin: 0; font-size: 0.83rem;">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        @if($account->allow_multi_taxation)
                        <th>Tax %</th>
                        @endif
                        @if($account->have_users)
                        <th>Users</th>
                        @endif
                        <th>Freq</th>
                        <th>Dur</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
            </table>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
            <div class="totals-card" style="min-width: 320px;">
                <div class="total-row"><span>Subtotal</span><strong id="subtotalDisplay">INR 0.00</strong></div>
                <div class="total-row"><span>Tax</span><strong id="taxDisplay">INR 0.00</strong></div>
                <div class="total-row total-row-grand"><span>Grand Total</span><strong id="grandTotalDisplay">INR 0.00</strong></div>
            </div>
        </div>
    </div>

    @if(request('orderid'))
    <div id="orderSummary" class="workflow-panel" style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
        <div class="panel-heading-row">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <i class="fas fa-shopping-cart" style="color: #f59e0b; font-size: 1.1rem;"></i>
                <div>
                    <h4 id="orderSummaryTitle" style="margin: 0; font-size: 0.95rem; color: #1e293b;">Source Order Details</h4>
                    <p id="orderSummaryDetails" style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.8rem;"></p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div style="margin-top: 2rem;">
        <button type="button" class="primary-button" id="btnNextToStep4" style="width: 100%; padding: 1rem;">Review & Terms &rarr;</button>
    </div>
</div>

<script>
(function() {
    const clientId = "{{ request('clientid') }}";
    const invoiceFor = "{{ request('invoice_for') }}";
    const itemsBody = document.getElementById('itemsBody');
    const btnNextToStep4 = document.getElementById('btnNextToStep4');
    const btnBackToStep2 = document.getElementById('btnBackToStep2');
    const itemsDataInput = document.getElementById('items_data');

    const frequencyLabels = { 'one-time': 'One-Time', 'daily': 'Daily', 'weekly': 'Weekly', 'bi-weekly': 'Bi-Weekly', 'monthly': 'Monthly', 'quarterly': 'Quarterly', 'semi-annually': 'Semi-Annually', 'yearly': 'Yearly' };

    let invoiceItems = [];

    // Load items from draft/session + order details
    function loadItems() {
        const orderId = "{{ request('orderid') }}";
        
        fetch("{{ route('invoices.get-draft', ['clientid' => '__CLIENTID__']) }}".replace('__CLIENTID__', clientId))
        .then(response => response.json())
        .then(data => {
            if (data.draft && data.draft.items) {
                invoiceItems = data.draft.items;
                renderItems();
            }
            
            // Load order details for summary
            if (orderId) {
                fetch(`{{ route('invoices.order-items', ['orderid' => '__ORDERID__']) }}`.replace('__ORDERID__', orderId))
                .then(r => r.json())
                .then(orderData => {
                    if (orderData.order) {
                        const verifiedBadge = String(orderData.order.is_verified).toLowerCase() === 'yes'
                            ? '<span style="padding: 0.2rem 0.5rem; background: #dcfce7; color: #166534; border-radius: 4px; font-size: 0.75rem; font-weight: 600; margin-left: 0.5rem;">Verified</span>'
                            : '<span style="padding: 0.2rem 0.5rem; background: #fee2e2; color: #991b1b; border-radius: 4px; font-size: 0.75rem; font-weight: 600; margin-left: 0.5rem;">Unverified</span>';
                        
                        document.getElementById('orderSummaryTitle').innerHTML = `<strong>${orderData.order.order_title || 'Untitled Order'}</strong> ${verifiedBadge}`;
                        document.getElementById('orderSummaryDetails').innerHTML = 
                            `Order #${orderData.order.order_number} • ${orderData.order.order_date} • ` +
                            `${orderData.order.currency} ${Number(orderData.order.grand_total).toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
                    }
                })
                .catch(() => {});
            }
        })
        .catch(() => {
            // If no draft, try to load from order
            @if(request('invoice_for') === 'orders' && request('orderid'))
            loadOrderItems("{{ request('orderid') }}");
            @endif
        });
    }

    @if(request('invoice_for') === 'orders' && request('orderid'))
    function loadOrderItems(orderId) {
        fetch(`{{ route('invoices.order-items', ['orderid' => '__ORDERID__']) }}`.replace('__ORDERID__', orderId))
        .then(response => response.json())
        .then(data => {
            invoiceItems = data.items;
            renderItems();
        })
        .catch(() => {
            console.error('Failed to load order items');
        });
    }
    @endif

    function renderItems() {
        itemsBody.innerHTML = '';
        let subtotal = 0, taxTotal = 0;

        invoiceItems.forEach((item, index) => {
            const lineTax = item.line_total * ((item.tax_rate || 0) / 100);
            subtotal += item.line_total;
            taxTotal += lineTax;

            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" class="form-input item-name" data-index="${index}" value="${item.item_name}" style="min-width: 150px;"></td>
                <td><input type="number" class="form-input item-quantity" data-index="${index}" value="${item.quantity}" min="0.01" step="0.01" style="width: 80px;"></td>
                <td><input type="number" class="form-input item-price" data-index="${index}" value="${item.unit_price}" min="0" step="0.01" style="width: 100px;"></td>
                @if($account->allow_multi_taxation)
                <td>
                    <select class="form-input item-tax-rate" data-index="${index}" style="width: 90px;">
                        <option value="0" ${!item.tax_rate ? 'selected' : ''}>0%</option>
                        @foreach($taxes as $tax)
                        <option value="{{ $tax->rate }}" ${item.tax_rate == $tax->rate ? 'selected' : ''}>{{ $tax->rate }}%</option>
                        @endforeach
                    </select>
                </td>
                @endif
                @if($account->have_users)
                <td><input type="number" class="form-input item-users" data-index="${index}" value="${item.no_of_users || 1}" min="1" step="1" style="width: 70px;"></td>
                @endif
                <td>
                    <select class="form-input item-frequency" data-index="${index}" style="width: 100px;">
                        <option value="">None</option>
                        @foreach(['one-time', 'daily', 'weekly', 'bi-weekly', 'monthly', 'quarterly', 'semi-annually', 'yearly'] as $freq)
                        <option value="{{ $freq }}" ${(item.frequency || '') == '{{ $freq }}' ? 'selected' : ''}>{{ ucfirst(str_replace('-', ' ', $freq)) }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="number" class="form-input item-duration" data-index="${index}" value="${item.duration || ''}" min="0" step="1" style="width: 70px;" placeholder="-"></td>
                <td style="text-align: right; font-weight: 600;" class="item-total" data-index="${index}">INR ${item.line_total.toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                <td><button type="button" class="remove-item-btn" data-index="${index}" style="background: none; border: none; color: #ef4444; cursor: pointer;"><i class="fas fa-trash"></i></button></td>
            `;
            itemsBody.appendChild(row);
        });

        // Add event listeners for inline editing
        document.querySelectorAll('.item-quantity, .item-price, .item-tax-rate, .item-users, .item-frequency, .item-duration').forEach(input => {
            input.addEventListener('change', recalculateItems);
        });

        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                invoiceItems.splice(parseInt(this.dataset.index), 1);
                renderItems();
            });
        });

        document.getElementById('subtotalDisplay').textContent = `INR ${subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
        document.getElementById('taxDisplay').textContent = `INR ${taxTotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
        document.getElementById('grandTotalDisplay').textContent = `INR ${(subtotal + taxTotal).toLocaleString('en-IN', {minimumFractionDigits: 2})}`;

        document.getElementById('subtotal').value = subtotal.toFixed(2);
        document.getElementById('tax_total').value = taxTotal.toFixed(2);
        document.getElementById('grand_total').value = (subtotal + taxTotal).toFixed(2);
        itemsDataInput.value = JSON.stringify(invoiceItems);
    }

    function recalculateItems() {
        document.querySelectorAll('.item-quantity, .item-price, .item-tax-rate, .item-users, .item-frequency, .item-duration').forEach(input => {
            const index = parseInt(input.dataset.index);
            const field = input.className.includes('quantity') ? 'quantity' :
                         input.className.includes('price') ? 'unit_price' :
                         input.className.includes('tax-rate') ? 'tax_rate' :
                         input.className.includes('users') ? 'no_of_users' :
                         input.className.includes('frequency') ? 'frequency' :
                         input.className.includes('duration') ? 'duration' : null;

            if (field && invoiceItems[index]) {
                if (field === 'frequency' || field === 'duration') {
                    invoiceItems[index][field] = input.value;
                } else {
                    invoiceItems[index][field] = parseFloat(input.value) || 0;
                }

                const qty = invoiceItems[index].quantity || 0;
                const price = invoiceItems[index].unit_price || 0;
                const users = Math.max(1, invoiceItems[index].no_of_users || 1);
                const freq = invoiceItems[index].frequency;
                const dur = invoiceItems[index].duration || 0;

                invoiceItems[index].line_total = qty * price * users * (freq && freq !== 'one-time' && dur > 0 ? dur : 1);
            }
        });

        renderItems();
    }

    btnNextToStep4.addEventListener('click', function() {
        if (invoiceItems.length === 0) {
            alert('No items to invoice.');
            return;
        }

        const invoiceTitle = document.getElementById('invoice_title').value;
        fetch("{{ route('invoices.save-draft') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                clientid: clientId,
                invoice_for: invoiceFor,
                invoice_title: invoiceTitle,
                items_data: itemsDataInput.value
            })
        })
        .then(response => response.json())
        .then(() => {
            window.location.href = "{{ route('invoices.create') }}?step=4&invoice_for=" + invoiceFor + "&clientid=" + clientId;
        });
    });

    btnBackToStep2.addEventListener('click', function() {
        window.location.href = "{{ route('invoices.create') }}?step=2&invoice_for=" + invoiceFor + "&clientid=" + clientId;
    });

    // Initialize
    loadItems();
})();
</script>
