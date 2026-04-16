@php($selectedClientCurrency = optional($clients->firstWhere('clientid', request('clientid')))->currency ?? 'INR')
<!-- Step 2: Add Items (Without Orders) -->
<div id="step2" class="invoice-step">
    <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
        <button type="button" id="btnBackToStep1" class="secondary-button" style="padding: 0.5rem 1rem;">&larr; Back</button>
        <div class="invoice-side-meta">
            <span class="invoice-meta-label">PI</span>
            <strong class="invoice-meta-value">{{ $nextInvoiceNumber }}</strong>
            <input type="hidden" name="invoice_number" value="{{ $nextInvoiceNumber }}">
        </div>
    </div>

    <div class="invoice-grid-4" style="margin-bottom: 1rem;">
        <div class="invoice-span-3">
        <label for="invoice_title" class="field-label">Invoice Title</label>
        <input type="text" id="invoice_title" name="invoice_title" class="form-input" placeholder="e.g. Website Development - Monthly Subscription">
        </div>
    </div>

    <input type="hidden" name="clientid" value="{{ request('clientid') }}">
    <input type="hidden" name="subtotal" id="subtotal" value="0.00">
    <input type="hidden" name="tax_total" id="tax_total" value="0.00">
    <input type="hidden" name="grand_total" id="grand_total" value="0.00">
    <input type="hidden" name="items_data" id="items_data" value="">
    <input type="hidden" name="currency_code" id="currency_code" value="{{ $selectedClientCurrency }}">

    <div id="manualItemsSection" class="workflow-panel">
        <div class="panel-heading-row">
            <div>
                <h4 style="margin: 0; font-size: 1rem; color: #334155;">Add Invoice Items</h4>
                <p style="margin: 0.2rem 0 0 0; color: #64748b; font-size: 0.85rem;">Add items to your invoice.</p>
            </div>
        </div>

        <div class="builder-card">
            <div class="manual-grid">
                <div class="invoice-span-2">
                    <label for="manual_item_itemid" class="field-label small">Item</label>
                    <select id="manual_item_itemid" class="form-input">
                        <option value="">Select item</option>
                        @php
                            $groupedServices = $services->groupBy(fn ($service) => $service->category->name ?? 'No Category');
                        @endphp
                        @foreach($groupedServices as $categoryName => $categoryServices)
                            <optgroup label="{{ $categoryName }}">
                                @foreach($categoryServices as $service)
                                    @php
                                        $defaultCosting = $service->costings->sortBy('currency_code')->first();
                                    @endphp
                                    <option value="{{ $service->itemid }}" data-selling-price="{{ $defaultCosting?->selling_price ?? 0 }}" data-tax-rate="{{ $defaultCosting?->tax_rate ?? 0 }}" data-taxid="{{ $defaultCosting?->taxid ?? '' }}" data-user-wise="{{ (int) ($service->user_wise ?? 0) }}">
                                        {{ $service->name }} ({{ number_format($defaultCosting?->selling_price ?? 0, 0) }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="manual_item_quantity" class="field-label small">Qty</label>
                    <input type="number" id="manual_item_quantity" class="form-input" value="1" min="0.01" step="0.01">
                </div>
                <div>
                    <label for="manual_item_unit_price" class="field-label small">Unit Price</label>
                    <input type="number" id="manual_item_unit_price" class="form-input" min="0" step="0.01">
                </div>
                @if($account->allow_multi_taxation)
                <div>
                    <label for="manual_item_tax_rate" class="field-label small">Tax</label>
                    <select id="manual_item_tax_rate" class="form-input">
                        <option value="0">No Tax</option>
                        @foreach($taxes as $tax)
                            <option value="{{ $tax->rate }}" data-taxid="{{ $tax->taxid }}">{{ $tax->tax_name }} ({{ number_format($tax->rate, 2) }}%)</option>
                        @endforeach
                    </select>
                </div>
                @else
                <input type="hidden" id="manual_item_tax_rate" value="{{ $account->fixed_tax_rate ?? 0 }}">
                @endif
                @if($account->have_users)
                <div id="manual_item_users_wrap" style="display: none;">
                    <label for="manual_item_users" class="field-label small">Users</label>
                    <input type="number" id="manual_item_users" class="form-input" value="1" min="1" step="1">
                </div>
                @else
                <input type="hidden" id="manual_item_users" value="1">
                @endif
                <div>
                    <label for="manual_item_frequency" class="field-label small">Freq</label>
                    <select id="manual_item_frequency" class="form-input">
                        <option value="">None</option>
                        <option value="one-time">One-Time</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="bi-weekly">Bi-Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="semi-annually">Semi-Annually</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
                <div>
                    <label for="manual_item_duration" class="field-label small">Dur</label>
                    <input type="number" id="manual_item_duration" class="form-input" min="0" step="1" placeholder="e.g. 12">
                </div>
                <div style="display: flex; align-items: end;">
                    <button type="button" id="addManualItemBtn" class="primary-button" style="width: 100%;">Add</button>
                </div>
            </div>
        </div>

        <div class="table-shell" style="margin-top: 1rem;">
            <table class="data-table" id="manualItemsTable" style="display: none; margin: 0; font-size: 0.84rem;">
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
                <tbody id="manualItemsBody"></tbody>
            </table>
            <div id="manualItemsEmpty" class="empty-state">No items added yet.</div>
        </div>

        <div id="manualOrderSummary" class="totals-card" style="display: none; margin-top: 1rem;">
            <div class="total-row"><span>Subtotal</span><strong id="manualSubtotal">0.00</strong></div>
            <div class="total-row"><span>Tax</span><strong id="manualTaxTotal">0.00</strong></div>
            <div class="total-row total-row-grand"><span>Total</span><strong id="manualGrandTotal">0.00</strong></div>
        </div>
    </div>

    <div style="margin-top: 2rem;">
        <button type="button" class="primary-button" id="btnNextToStep3" disabled style="width: 100%; padding: 1rem;">Review & Terms &rarr;</button>
    </div>
</div>

<script>
(function() {
    const addManualItemBtn = document.getElementById('addManualItemBtn');
    const manualItemsBody = document.getElementById('manualItemsBody');
    const manualItemsTable = document.getElementById('manualItemsTable');
    const manualItemsEmpty = document.getElementById('manualItemsEmpty');
    const manualSummary = document.getElementById('manualOrderSummary');
    const btnNextToStep3 = document.getElementById('btnNextToStep3');
    const btnBackToStep1 = document.getElementById('btnBackToStep1');
    const itemsDataInput = document.getElementById('items_data');
    const currencyCodeInput = document.getElementById('currency_code');

    const frequencyLabels = { 'one-time': 'One-Time', 'daily': 'Daily', 'weekly': 'Weekly', 'bi-weekly': 'Bi-Weekly', 'monthly': 'Monthly', 'quarterly': 'Quarterly', 'semi-annually': 'Semi-Annually', 'yearly': 'Yearly' };

    let manualItems = [];

    function formatCurrency(amount) {
        const currency = currencyCodeInput.value || '{{ $selectedClientCurrency }}';
        return `${currency} ${Number(amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }

    // Auto-fill price when item selected
    document.getElementById('manual_item_itemid').addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const price = selected.dataset.sellingPrice || 0;
        const taxRate = selected.dataset.taxRate || 0;
        document.getElementById('manual_item_unit_price').value = price;
        if (document.getElementById('manual_item_tax_rate')) {
            document.getElementById('manual_item_tax_rate').value = taxRate;
        }
        @if($account->have_users)
        toggleManualUsersField();
        @endif
    });

    @if($account->have_users)
    function isManualItemUserWise() {
        const select = document.getElementById('manual_item_itemid');
        const option = select?.options[select.selectedIndex];
        return option?.dataset?.userWise === '1';
    }

    function toggleManualUsersField() {
        const wrap = document.getElementById('manual_item_users_wrap');
        const usersInput = document.getElementById('manual_item_users');
        if (!wrap || !usersInput) return;
        const show = isManualItemUserWise();
        wrap.style.display = show ? 'block' : 'none';
        if (!show) usersInput.value = 1;
    }
    toggleManualUsersField();
    @endif

    addManualItemBtn.addEventListener('click', function() {
        const itemId = document.getElementById('manual_item_itemid').value;
        const itemName = document.getElementById('manual_item_itemid').options[document.getElementById('manual_item_itemid').selectedIndex]?.text || '';
        const quantity = parseFloat(document.getElementById('manual_item_quantity').value) || 0;
        const unitPrice = parseFloat(document.getElementById('manual_item_unit_price').value) || 0;
        const taxRate = parseFloat(document.getElementById('manual_item_tax_rate').value) || 0;
        @if($account->have_users)
        const isUserWiseItem = isManualItemUserWise();
        const users = isUserWiseItem ? (parseInt(document.getElementById('manual_item_users').value) || 1) : 1;
        const usersForStorage = isUserWiseItem ? users : null;
        @else
        const users = parseInt(document.getElementById('manual_item_users').value) || 1;
        const usersForStorage = users;
        @endif
        const frequency = document.getElementById('manual_item_frequency').value;
        const duration = parseInt(document.getElementById('manual_item_duration').value) || null;

        if (!itemId) {
            alert('Please select an item.');
            return;
        }

        if (quantity <= 0) {
            alert('Quantity must be greater than 0.');
            return;
        }

        const lineTotal = quantity * unitPrice * Math.max(1, users) * (frequency && frequency !== 'one-time' && duration ? duration : 1);

        const newItem = {
            itemid: itemId,
            item_name: itemName.split('(')[0].trim(),
            quantity,
            unit_price: unitPrice,
            tax_rate: taxRate,
            no_of_users: usersForStorage,
            frequency,
            duration,
            line_total: lineTotal
        };

        manualItems.push(newItem);
        renderManualItems();
        
        // Reset form
        document.getElementById('manual_item_itemid').value = '';
        document.getElementById('manual_item_quantity').value = '1';
        document.getElementById('manual_item_unit_price').value = '';
        document.getElementById('manual_item_duration').value = '';
        @if($account->have_users)
        toggleManualUsersField();
        @endif
    });

    function renderManualItems() {
        if (manualItems.length === 0) {
            manualItemsTable.style.display = 'none';
            manualItemsEmpty.style.display = 'block';
            manualSummary.style.display = 'none';
            btnNextToStep3.disabled = true;
            return;
        }

        manualItemsTable.style.display = 'table';
        manualItemsEmpty.style.display = 'none';
        manualSummary.style.display = 'block';
        btnNextToStep3.disabled = false;

        manualItemsBody.innerHTML = '';
        let subtotal = 0, taxTotal = 0;

        manualItems.forEach((item, index) => {
            const tax = item.line_total * (item.tax_rate / 100);
            subtotal += item.line_total;
            taxTotal += tax;

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.item_name}</td>
                <td style="text-align: center;">${item.quantity}</td>
                <td style="text-align: right;">${formatCurrency(item.unit_price)}</td>
                @if($account->allow_multi_taxation)
                <td style="text-align: center;">${item.tax_rate}%</td>
                @endif
                @if($account->have_users)
                <td style="text-align: center;">${item.no_of_users ?? '—'}</td>
                @endif
                <td>${item.frequency ? (frequencyLabels[item.frequency] || item.frequency) : '-'}</td>
                <td>${item.duration || '-'}</td>
                <td style="text-align: right; font-weight: 600;">${formatCurrency(item.line_total)}</td>
                <td><button type="button" class="remove-item-btn" data-index="${index}" style="background: none; border: none; color: #ef4444; cursor: pointer;"><i class="fas fa-trash"></i></button></td>
            `;
            manualItemsBody.appendChild(row);
        });

        // Add remove listeners
        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                manualItems.splice(this.dataset.index, 1);
                renderManualItems();
            });
        });

        document.getElementById('manualSubtotal').textContent = formatCurrency(subtotal);
        document.getElementById('manualTaxTotal').textContent = formatCurrency(taxTotal);
        document.getElementById('manualGrandTotal').textContent = formatCurrency(subtotal + taxTotal);

        document.getElementById('subtotal').value = subtotal.toFixed(2);
        document.getElementById('tax_total').value = taxTotal.toFixed(2);
        document.getElementById('grand_total').value = (subtotal + taxTotal).toFixed(2);
        itemsDataInput.value = JSON.stringify(manualItems);
    }

    btnNextToStep3.addEventListener('click', function() {
        if (manualItems.length === 0) {
            alert('Please add at least one item.');
            return;
        }

        const invoiceTitle = document.getElementById('invoice_title').value;
        fetch("{{ route('invoices.save-draft') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                clientid: "{{ request('clientid') }}",
                invoice_for: 'without_orders',
                invoice_title: invoiceTitle,
                items_data: itemsDataInput.value,
                subtotal: document.getElementById('subtotal').value,
                tax_total: document.getElementById('tax_total').value,
                grand_total: document.getElementById('grand_total').value
            })
        })
        .then(response => response.json())
        .then(() => {
            window.location.href = "{{ route('invoices.create') }}?step=3&invoice_for=without_orders&clientid={{ request('clientid') }}";
        });
    });

    btnBackToStep1.addEventListener('click', function() {
        window.location.href = "{{ route('invoices.create') }}?step=1&clientid={{ request('clientid') }}";
    });
})();
</script>
