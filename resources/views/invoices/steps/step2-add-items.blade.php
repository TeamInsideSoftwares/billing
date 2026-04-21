@php
    $selectedClientCurrency = optional($clients->firstWhere('clientid', request('clientid')))->currency ?? 'INR';
    $serviceGroups = collect($services ?? [])->groupBy(function ($service) {
        return optional($service->category)->name ?? 'No Category';
    });
@endphp
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
        <input type="text" id="invoice_title" name="invoice_title" class="form-input" placeholder="e.g. Website Development - Monthly Subscription" required>
        <div id="invoiceTitleError" style="display:none; margin-top: 0.35rem; color: #b91c1c; font-size: 0.8rem; font-weight: 600;">Invoice title is required.</div>
        </div>
    </div>

    <input type="hidden" name="clientid" value="{{ request('clientid') }}">
    <input type="hidden" name="subtotal" id="subtotal" value="0.00">
    <input type="hidden" name="tax_total" id="tax_total" value="0.00">
    <input type="hidden" name="discount_total" id="discount_total" value="0.00">
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
            <div class="manual-grid manual-grid-add-items">
                <div class="invoice-span-2">
                    <label for="manual_item_itemid" class="field-label small">Item</label>
                    <select id="manual_item_itemid" class="form-input">
                        <option value="">Select item</option>
                        @foreach($serviceGroups as $categoryName => $categoryServices)
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
                    <input type="number" id="manual_item_quantity" class="form-input" value="1" min="1" step="1">
                </div>
                <div>
                    <label for="manual_item_unit_price" class="field-label small">Unit Price</label>
                    <input type="number" id="manual_item_unit_price" class="form-input" min="0" step="0.01">
                </div>
                <div>
                    <label for="manual_item_discount" class="field-label small">Disc %</label>
                    <input type="number" id="manual_item_discount" class="form-input" min="0" max="100" step="0.01" value="0">
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
                <div id="manual_item_duration_wrap" style="display: none;">
                    <label for="manual_item_duration" class="field-label small">Dur</label>
                    <input type="number" id="manual_item_duration" class="form-input" min="0" step="1" placeholder="e.g. 12">
                </div>
                <div id="manual_item_start_date_wrap" style="display: none;">
                    <label for="manual_item_start_date" class="field-label small">Start</label>
                    <input type="date" id="manual_item_start_date" class="form-input">
                </div>
                <div id="manual_item_end_date_wrap" style="display: none;">
                    <label for="manual_item_end_date" class="field-label small">End</label>
                    <input type="date" id="manual_item_end_date" class="form-input">
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
                        <th>Disc %</th>
                        @if($account->allow_multi_taxation)
                        <th>Tax %</th>
                        @endif
                        @if($account->have_users)
                        <th id="manualUsersHeader" style="display:none;">Users</th>
                        @endif
                        <th>Freq</th>
                        <th id="manualDurationHeader" style="display:none;">Dur</th>
                        <th id="manualStartHeader" style="display:none;">Start</th>
                        <th id="manualEndHeader" style="display:none;">End</th>
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
            <div class="total-row"><span>Discount</span><strong id="manualDiscountTotal">0.00</strong></div>
            <div class="total-row"><span>Tax</span><strong id="manualTaxTotal">0.00</strong></div>
            <div class="total-row total-row-grand"><span>Total</span><strong id="manualGrandTotal">0.00</strong></div>
        </div>
    </div>

    <div style="margin-top: 2rem;">
        <button type="button" class="primary-button" id="btnNextToStep3" disabled style="width: 100%; padding: 1rem;">Review & Terms &rarr;</button>
    </div>
</div>

<style>
.manual-grid.manual-grid-add-items {
    grid-template-columns: 2.2fr 0.7fr 1fr 0.8fr 1fr 0.8fr 0.8fr 1fr 1fr 0.8fr;
}
@media (max-width: 1200px) {
    .manual-grid.manual-grid-add-items {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 720px) {
    .manual-grid.manual-grid-add-items {
        grid-template-columns: 1fr;
    }
}
</style>

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
    const invoiceTitleInput = document.getElementById('invoice_title');
    const invoiceTitleError = document.getElementById('invoiceTitleError');
    const manualFrequencyInput = document.getElementById('manual_item_frequency');
    const manualDurationWrap = document.getElementById('manual_item_duration_wrap');
    const manualDurationInput = document.getElementById('manual_item_duration');
    const manualStartWrap = document.getElementById('manual_item_start_date_wrap');
    const manualEndWrap = document.getElementById('manual_item_end_date_wrap');
    const manualStartInput = document.getElementById('manual_item_start_date');
    const manualEndInput = document.getElementById('manual_item_end_date');

    const frequencyLabels = { 'one-time': 'One-Time', 'daily': 'Daily', 'weekly': 'Weekly', 'bi-weekly': 'Bi-Weekly', 'monthly': 'Monthly', 'quarterly': 'Quarterly', 'semi-annually': 'Semi-Annually', 'yearly': 'Yearly' };

    let manualItems = [];

    function formatCurrency(amount) {
        const currency = currencyCodeInput.value || '{{ $selectedClientCurrency }}';
        return `${currency} ${Number(amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }

    function roundTaxUp(value) {
        return Math.ceil(Math.max(0, Number(value) || 0));
    }

    function roundDiscountDown(value) {
        return Math.floor(Math.max(0, Number(value) || 0));
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

    function isRecurringFrequency(frequency) {
        return Boolean(frequency) && frequency !== 'one-time';
    }

    function calculateEndDate(startDate, frequency, duration) {
        if (!startDate || !isRecurringFrequency(frequency) || !duration) {
            return '';
        }
        const start = new Date(startDate);
        const steps = Number(duration) || 0;
        if (Number.isNaN(start.getTime()) || steps <= 0) {
            return '';
        }
        const end = new Date(start);
        switch (frequency) {
            case 'daily': end.setDate(end.getDate() + steps); break;
            case 'weekly': end.setDate(end.getDate() + (steps * 7)); break;
            case 'bi-weekly': end.setDate(end.getDate() + (steps * 14)); break;
            case 'monthly': end.setMonth(end.getMonth() + steps); break;
            case 'quarterly': end.setMonth(end.getMonth() + (steps * 3)); break;
            case 'semi-annually': end.setMonth(end.getMonth() + (steps * 6)); break;
            case 'yearly': end.setFullYear(end.getFullYear() + steps); break;
            default: return '';
        }
        return end.toISOString().split('T')[0];
    }

    function toggleManualRecurringFields() {
        if (!manualDurationWrap || !manualDurationInput || !manualStartWrap || !manualEndWrap || !manualStartInput || !manualEndInput) return;
        const showRecurring = isRecurringFrequency(manualFrequencyInput?.value || '');
        manualDurationWrap.style.display = showRecurring ? 'block' : 'none';
        manualStartWrap.style.display = showRecurring ? 'block' : 'none';
        manualEndWrap.style.display = showRecurring ? 'block' : 'none';
        if (!showRecurring) {
            manualDurationInput.value = '';
            manualStartInput.value = '';
            manualEndInput.value = '';
        }
    }

    manualFrequencyInput?.addEventListener('change', function() {
        toggleManualRecurringFields();
        if (manualEndInput) {
            manualEndInput.value = calculateEndDate(manualStartInput?.value || '', manualFrequencyInput?.value || '', manualDurationInput?.value || '');
        }
    });
    [manualStartInput, manualDurationInput].forEach((input) => {
        input?.addEventListener('change', function() {
            if (manualEndInput) {
                manualEndInput.value = calculateEndDate(manualStartInput?.value || '', manualFrequencyInput?.value || '', manualDurationInput?.value || '');
            }
        });
    });
    toggleManualRecurringFields();

    function itemHasUsers(item) {
        return Number(item?.no_of_users || 0) > 0;
    }

    function itemIsRecurring(item) {
        return isRecurringFrequency(item?.frequency);
    }

    function syncManualHeaders() {
        const showRecurringColumns = manualItems.some(itemIsRecurring);
        const showUserColumns = manualItems.some(itemHasUsers);
        const usersHeader = document.getElementById('manualUsersHeader');
        const durationHeader = document.getElementById('manualDurationHeader');
        const startHeader = document.getElementById('manualStartHeader');
        const endHeader = document.getElementById('manualEndHeader');
        if (usersHeader) usersHeader.style.display = showUserColumns ? '' : 'none';
        if (durationHeader) durationHeader.style.display = showRecurringColumns ? '' : 'none';
        if (startHeader) startHeader.style.display = showRecurringColumns ? '' : 'none';
        if (endHeader) endHeader.style.display = showRecurringColumns ? '' : 'none';

        return { showRecurringColumns, showUserColumns };
    }

    addManualItemBtn.addEventListener('click', function() {
        const itemId = document.getElementById('manual_item_itemid').value;
        const itemName = document.getElementById('manual_item_itemid').options[document.getElementById('manual_item_itemid').selectedIndex]?.text || '';
        const quantity = Math.max(1, Math.round(Number(document.getElementById('manual_item_quantity').value) || 1));
        const unitPrice = parseFloat(document.getElementById('manual_item_unit_price').value) || 0;
        const discountPercent = Math.min(100, Math.max(0, parseFloat(document.getElementById('manual_item_discount').value) || 0));
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
        const duration = isRecurringFrequency(frequency)
            ? (parseInt(document.getElementById('manual_item_duration').value) || null)
            : null;
        const startDate = isRecurringFrequency(frequency) ? (manualStartInput?.value || null) : null;
        const endDate = isRecurringFrequency(frequency)
            ? ((manualEndInput?.value || calculateEndDate(startDate, frequency, duration)) || null)
            : null;

        if (!itemId) {
            alert('Please select an item.');
            return;
        }

        if (quantity <= 0) {
            alert('Quantity must be greater than 0.');
            return;
        }

        const durationMultiplier = (isRecurringFrequency(frequency) && Number(duration || 0) > 0) ? Number(duration) : 1;
        const lineTotal = quantity * unitPrice * Math.max(1, users) * durationMultiplier;
        const discountAmount = roundDiscountDown(lineTotal * (discountPercent / 100));
        const taxAmount = roundTaxUp(Math.max(0, lineTotal - discountAmount) * (taxRate / 100));

        const newItem = {
            itemid: itemId,
            item_name: itemName.split('(')[0].trim(),
            quantity,
            unit_price: unitPrice,
            discount_percent: discountPercent,
            discount_amount: discountAmount,
            tax_rate: taxRate,
            no_of_users: usersForStorage,
            frequency,
            duration,
            start_date: startDate,
            end_date: endDate,
            tax_amount: taxAmount,
            line_total: lineTotal
        };

        manualItems.push(newItem);
        renderManualItems();
        
        // Reset form
        document.getElementById('manual_item_itemid').value = '';
        document.getElementById('manual_item_quantity').value = '1';
        document.getElementById('manual_item_unit_price').value = '';
        document.getElementById('manual_item_discount').value = '0';
        document.getElementById('manual_item_frequency').value = '';
        document.getElementById('manual_item_duration').value = '';
        if (manualStartInput) manualStartInput.value = '';
        if (manualEndInput) manualEndInput.value = '';
        toggleManualRecurringFields();
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
            syncManualHeaders();
            return;
        }

        manualItemsTable.style.display = 'table';
        manualItemsEmpty.style.display = 'none';
        manualSummary.style.display = 'block';
        btnNextToStep3.disabled = false;
        const headerState = syncManualHeaders();
        const showRecurringColumns = headerState.showRecurringColumns;
        const showUserColumns = headerState.showUserColumns;

        manualItemsBody.innerHTML = '';
        let subtotal = 0;
        let discountTotal = 0;
        let taxTotal = 0;

        manualItems.forEach((item, index) => {
            const quantity = Math.max(1, Math.round(Number(item.quantity || 1)));
            const unitPrice = Number(item.unit_price || 0);
            const users = Math.max(1, Number(item.no_of_users || 1));
            const durationMultiplier = (itemIsRecurring(item) && Number(item.duration || 0) > 0) ? Number(item.duration) : 1;
            item.quantity = quantity;
            item.line_total = quantity * unitPrice * users * durationMultiplier;
            item.discount_percent = Math.min(100, Math.max(0, Number(item.discount_percent || 0)));
            item.discount_amount = roundDiscountDown(item.line_total * (item.discount_percent / 100));
            item.tax_amount = roundTaxUp(Math.max(0, item.line_total - item.discount_amount) * (Number(item.tax_rate || 0) / 100));

            subtotal += item.line_total;
            discountTotal += item.discount_amount;
            taxTotal += item.tax_amount;

            const rowRecurring = itemIsRecurring(item);
            const rowUsers = itemHasUsers(item);

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.item_name}</td>
                <td style="text-align: center;">${Math.round(Number(item.quantity) || 0)}</td>
                <td style="text-align: right;">${formatCurrency(item.unit_price)}</td>
                <td style="text-align: center;">${Number(item.discount_percent || 0).toFixed(2)}%</td>
                @if($account->allow_multi_taxation)
                <td style="text-align: center;">${item.tax_rate}%</td>
                @endif
                @if($account->have_users)
                <td style="text-align: center; display:${showUserColumns ? '' : 'none'};">${rowUsers ? item.no_of_users : '-'}</td>
                @endif
                <td>${item.frequency ? (frequencyLabels[item.frequency] || item.frequency) : '-'}</td>
                <td style="display:${showRecurringColumns ? '' : 'none'};">${rowRecurring ? (item.duration || '-') : '-'}</td>
                <td style="display:${showRecurringColumns ? '' : 'none'};">${rowRecurring ? (item.start_date || '-') : '-'}</td>
                <td style="display:${showRecurringColumns ? '' : 'none'};">${rowRecurring ? (item.end_date || '-') : '-'}</td>
                <td style="text-align: right; font-weight: 600;">${formatCurrency(Math.max(0, item.line_total - item.discount_amount + item.tax_amount))}</td>
                <td><button type="button" class="remove-item-btn" data-index="${index}" style="background: none; border: none; color: #ef4444; cursor: pointer;"><i class="fas fa-trash"></i></button></td>
            `;
            manualItemsBody.appendChild(row);
        });

        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                manualItems.splice(this.dataset.index, 1);
                renderManualItems();
            });
        });

        const roundedDiscountTotal = roundDiscountDown(discountTotal);
        const roundedTaxTotal = roundTaxUp(taxTotal);

        document.getElementById('manualSubtotal').textContent = formatCurrency(subtotal);
        document.getElementById('manualDiscountTotal').textContent = formatCurrency(roundedDiscountTotal);
        document.getElementById('manualTaxTotal').textContent = formatCurrency(roundedTaxTotal);
        document.getElementById('manualGrandTotal').textContent = formatCurrency(subtotal - roundedDiscountTotal + roundedTaxTotal);

        document.getElementById('subtotal').value = subtotal.toFixed(2);
        document.getElementById('discount_total').value = roundedDiscountTotal.toFixed(2);
        document.getElementById('tax_total').value = roundedTaxTotal.toFixed(2);
        document.getElementById('grand_total').value = (subtotal - roundedDiscountTotal + roundedTaxTotal).toFixed(2);
        itemsDataInput.value = JSON.stringify(manualItems);
    }

    btnNextToStep3.addEventListener('click', function() {
        if (manualItems.length === 0) {
            alert('Please add at least one item.');
            return;
        }

        const invoiceTitle = invoiceTitleInput.value;
        if (!invoiceTitle.trim()) {
            invoiceTitleError.style.display = 'block';
            invoiceTitleInput.focus();
            return;
        }

        invoiceTitleError.style.display = 'none';

        fetch("{{ route('invoices.save-draft') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                clientid: "{{ request('clientid') }}",
                invoice_for: 'without_orders',
                invoice_title: invoiceTitle,
                items_data: itemsDataInput.value,
                subtotal: document.getElementById('subtotal').value,
                discount_total: document.getElementById('discount_total').value,
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

    invoiceTitleInput.addEventListener('input', function() {
        if (this.value.trim()) {
            invoiceTitleError.style.display = 'none';
        }
    });
})();
</script>

