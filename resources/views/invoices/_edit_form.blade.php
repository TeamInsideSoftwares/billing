@php
    $documentId = $invoice->invoiceid;
    $documentType = 'Invoice';
    $normalizeTaxState = static fn ($value) => preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $value)));
    $clientState = $normalizeTaxState($invoice->client->state ?? '');
    $accountState = $normalizeTaxState($account->state ?? '');
    $sameStateGst = $clientState !== '' && $accountState !== '' && $clientState === $accountState;
@endphp
<form method="POST" action="{{ route('invoices.update', [$invoice, 'c' => request('c')]) }}" id="{{ isset($inline) && $inline ? 'inline-edit-form-' . $documentId : 'invoiceForm' }}">
    @csrf
    @method('PUT')

    @if ($errors->any())
        <div style="margin-bottom: 1.25rem; padding: 0.9rem 1rem; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; border-radius: 10px;">
            <strong class="d-block mb-1">Fix these issues before updating the invoice:</strong>
            <ul class="plain-list">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="display: flex; justify-content: flex-end; margin-bottom: 0.55rem;">
        <div style="display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.26rem 0.58rem; border-radius: 999px; border: 1px solid #e2e8f0; background: #f8fafc; color: #0f172a; font-size: 0.76rem; font-weight: 700; letter-spacing: 0.01em;">
            <span>#{{ $invoice->invoice_number }}</span>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.6rem; margin-bottom: 0.8rem;">
        <input type="hidden" name="invoice_number" value="{{ old('invoice_number', $invoice->invoice_number) }}">
        <input type="hidden" name="clientid" value="{{ old('clientid', $invoice->clientid) }}">
        <div>
            <label for="clientid" class="field-label">Client</label>
            <select id="clientid" disabled class="form-input">
                <option value="">Choose a client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->clientid }}" data-currency="{{ $client->currency ?? 'INR' }}" {{ old('clientid', $invoice->clientid) == $client->clientid ? 'selected' : '' }}>
                        {{ $client->business_name ?? $client->contact_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="invoice_title" class="field-label">Invoice Title</label>
            <input type="text" id="invoice_title" name="invoice_title" value="{{ old('invoice_title', $invoice->invoice_title) }}" class="form-input" placeholder="e.g. Website Development - Phase 1">
        </div>

        <div>
            <label for="issue_date" class="field-label">Issue Date</label>
            <input type="date" id="issue_date" name="issue_date" value="{{ old('issue_date', optional($invoice->issue_date)->format('Y-m-d')) }}" class="form-input" required>
        </div>
        <div>
            <label for="due_date" class="field-label">Due Date</label>
            <input type="date" id="due_date" name="due_date" value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d')) }}" class="form-input" required>
        </div>
        <input type="hidden" name="currency_code" id="currency_code" value="{{ old('currency_code', $invoice->currency_code ?? ($invoice->client->currency ?? 'INR')) }}">
    </div>

    <div style="margin-bottom: 0.8rem;">
        <label for="notes" class="field-label">Notes</label>
        <textarea id="notes" name="notes" rows="3" class="form-input" style="min-height: 70px;">{{ old('notes', $invoice->notes) }}</textarea>
    </div>

    <input type="hidden" name="items_data" id="items_data" value="">

    <div class="workflow-panel" style="margin-top: 0; padding-top: 0; border-top: 0;">
        <div class="panel-heading-row" style="display: flex; justify-content: space-between; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <div>
                <h4 style="margin: 0; font-size: 1rem; color: #334155;"><i class="fas fa-edit" style="margin-right: 0.35rem; color: #4f46e5;"></i>Invoice Items</h4>
            </div>
            <button type="button" id="toggleAddItemBtn" class="primary-button">+ Add Item</button>
        </div>

        <div id="addItemPanel" class="builder-card" style="display: none; margin-bottom: 0.75rem;">
            <div class="manual-grid invoice-edit-add-row">
                <div>
                    <label for="item_itemid" class="field-label small">Item</label>
                    <select id="item_itemid" class="form-input">
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
                                    <option value="{{ $service->itemid }}" data-price="{{ $defaultCosting?->selling_price ?? 0 }}" data-tax-rate="{{ $defaultCosting?->tax_rate ?? 0 }}" data-user-wise="{{ (int) ($service->user_wise ?? 0) }}">
                                        {{ $service->name }} ({{ number_format($defaultCosting?->selling_price ?? 0, 0) }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="item_quantity" class="field-label small">Qty</label>
                    <input type="number" id="item_quantity" class="form-input" value="1" min="1" step="1">
                </div>
                <div>
                    <label for="item_unit_price" class="field-label small">Price</label>
                    <input type="number" id="item_unit_price" class="form-input" min="0" step="0.01">
                </div>
                <div>
                    <label for="item_discount" class="field-label small">Disc %</label>
                    <input type="number" id="item_discount" class="form-input" min="0" max="100" step="0.01" value="0">
                </div>
                @if($account->allow_multi_taxation)
                <div>
                    <label for="item_tax_rate" class="field-label small">Tax % @if(!isset($inline) || !$inline)<a href="#" id="open-tax-modal-invoice-edit" style="font-size:11px;margin-left:4px;" class="text-link">+ Add</a>@endif</label>
                    <select id="item_tax_rate" class="form-input">
                        <option value="0">No Tax</option>
                        @foreach($taxes as $tax)
                            <option value="{{ $tax->rate }}">{{ $tax->tax_name }} ({{ number_format($tax->rate, 0) }}%)</option>
                        @endforeach
                    </select>
                </div>
                @else
                <input type="hidden" id="item_tax_rate" value="{{ $account->fixed_tax_rate ?? 0 }}">
                @endif
                @if($account->have_users)
                <div id="item_users_wrap" style="display: none;">
                    <label for="item_users" class="field-label small">Users</label>
                    <input type="number" id="item_users" class="form-input" value="1" min="1" step="1">
                </div>
                @else
                <input type="hidden" id="item_users" value="1">
                @endif
                <div>
                    <label for="item_frequency" class="field-label small">Freq</label>
                    <select id="item_frequency" class="form-input">
                        <option value="">None</option>
                        <option value="One-Time">One-Time</option>
                        <option value="Day(s)">Day(s)</option>
                        <option value="Week(s)">Week(s)</option>
                        <option value="Month(s)">Month(s)</option>
                        <option value="Quarter(s)">Quarter(s)</option>
                        <option value="Year(s)">Year(s)</option>
                    </select>
                </div>
                <div id="item_duration_wrap" style="display: none;">
                    <label for="item_duration" class="field-label small">Dur</label>
                    <input type="number" id="item_duration" class="form-input" min="0" step="1">
                </div>
                <div id="item_start_date_wrap" style="display: none;">
                    <label for="item_start_date" class="field-label small">Start</label>
                    <input type="date" id="item_start_date" class="form-input">
                </div>
                <div id="item_end_date_wrap" style="display: none;">
                    <label for="item_end_date" class="field-label small">End</label>
                    <input type="date" id="item_end_date" class="form-input">
                </div>
            </div>
            <div style="margin-top: 0.45rem; display: flex; gap: 0.45rem; align-items: flex-end;">
                <textarea id="item_description" class="form-input" rows="1" placeholder="Description (optional)" style="flex: 1 1 auto; min-height: 30px; resize: none; line-height: 1.2;"></textarea>
                <button type="button" id="addItemBtn" class="primary-button" style="padding: 0.55rem 1rem; white-space: nowrap;">Add Item</button>
            </div>
        </div>

        <div class="table-shell">
            <table class="data-table" style="margin: 0; font-size: 0.83rem;">
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
                        <th>Users</th>
                        @endif
                        <th>Freq</th>
                        <th id="itemsDurationHeader" class="hidden">Dur</th>
                        <th id="itemsStartHeader" class="hidden">Start</th>
                        <th id="itemsEndHeader" class="hidden">End</th>
                        <th>Amount</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
            </table>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 0.55rem;">
            <div class="totals-card" style="min-width: 270px; max-width: 350px;">
                <div class="total-row"><span>Subtotal</span><strong id="subtotalDisplay">0</strong></div>
                <div class="total-row"><span>Discount</span><strong id="discountDisplay">0</strong></div>
                <div class="total-row"><span>{{ $sameStateGst ? 'Tax (CGST + SGST)' : 'Tax (IGST)' }}</span><strong id="taxDisplay">0</strong></div>
                <div class="total-row total-row-grand"><span>Grand Total</span><strong id="grandTotalDisplay">0</strong></div>
            </div>
        </div>
    </div>

    @if(isset($inline) && $inline)
    <div style="margin-top: 0.75rem; display: flex; gap: 0.5rem; justify-content: flex-end;">
        <button type="button" id="cancel-inline-edit-{{ $documentId }}" class="cancel-edit-btn" onclick="toggleInlineEdit('{{ $documentId }}')">
            <i class="fas fa-times" style="margin-right: 0.5rem;"></i> Cancel
        </button>
        <button type="submit" id="save-inline-edit-{{ $documentId }}" class="save-edit-btn">
            <i class="fas fa-save" style="margin-right: 0.5rem;"></i> Save Changes
        </button>
    </div>
    @else
    <div class="form-actions" style="margin-top: 0.75rem; display: flex; gap: 0.5rem;">
        <button type="submit" class="primary-button" id="updateInvoiceBtn"><i class="fas fa-edit" style="margin-right: 0.45rem;"></i>Update Invoice</button>
    </div>
    @endif
</form>

<script>
(function () {
    const currencyCodeInput = document.getElementById('currency_code');
    const clientSelect = document.getElementById('clientid');
    const itemsBody = document.getElementById('itemsBody');
    const itemsDataInput = document.getElementById('items_data');
    const toggleAddItemBtn = document.getElementById('toggleAddItemBtn');
    const addItemPanel = document.getElementById('addItemPanel');
    const addItemBtn = document.getElementById('addItemBtn');
    const updateInvoiceBtn = document.getElementById('updateInvoiceBtn');
    const allowMultiTaxation = @json((bool) ($account->allow_multi_taxation ?? false));
    const accountHasUsers = @json((bool) ($account->have_users ?? false));

    const frequencyLabels = { 'One-Time': 'One-Time', 'Day(s)': 'Day(s)', 'Week(s)': 'Week(s)', 'Month(s)': 'Month(s)', 'Quarter(s)': 'Quarter(s)', 'Year(s)': 'Year(s)' };

    let clientCurrency = currencyCodeInput.value || 'INR';
    @php
        $itemsData = old('items_data') ? json_decode(old('items_data'), true) : $invoice->items->map(function ($item) {
            // Get default selling price from service costing if unit_price is missing
            $defaultPrice = $item->service?->costings?->sortBy('currency_code')->first()?->selling_price ?? 0;
            
            return [
                'invoice_itemid' => $item->invoice_itemid,
                'itemid' => $item->itemid,
                'item_name' => $item->item_name ?? ($item->service->name ?? 'Item'),
                'item_description' => $item->item_description ?? '',
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price ?? $defaultPrice ?? 0,
                'tax_rate' => $item->tax_rate,
                'discount_percent' => $item->discount_percent ?? 0,
                'duration' => $item->duration,
                'frequency' => $item->frequency,
                'no_of_users' => $item->no_of_users,
                'start_date' => optional($item->start_date)->format('Y-m-d'),
                'end_date' => optional($item->end_date)->format('Y-m-d'),
                'line_total' => $item->line_total,
            ];
        })->values()->toArray();

        // Debug: Log items for inline edit
        if (isset($inline) && $inline) {
            echo '<script>console.log("Items from server:", ' . json_encode($itemsData) . ')</script>';
        }
    @endphp
    let invoiceItems = @json($itemsData);

    console.log('Loaded invoice items:', invoiceItems);

    function formatMoney(amount) {
        return `${clientCurrency} ${Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
    }


    function calculateLineTotal(quantity, unitPrice, users, frequency, duration) {
        let total = (Number(quantity) || 0) * (Number(unitPrice) || 0) * Math.max(1, Number(users) || 1);
        if (frequency && frequency !== 'One-Time' && duration) {
            const durationNumber = Number(duration) || 0;
            if (durationNumber > 0) {
                total *= durationNumber;
            }
        }
        return total;
    }

    function roundTaxUp(value) {
        return Math.ceil(Math.max(0, Number(value) || 0));
    }

    function roundDiscountDown(value) {
        return Math.floor(Math.max(0, Number(value) || 0));
    }

    function calculateEndDate(startDate, frequency, duration) {
        if (!startDate || !frequency || !duration || frequency === 'One-Time') {
            return '';
        }
        const start = new Date(startDate);
        const durationNumber = Number(duration);
        if (Number.isNaN(start.getTime()) || durationNumber <= 0) {
            return '';
        }
        const end = new Date(start);
        switch (frequency) {
            case 'Day(s)': end.setDate(end.getDate() + durationNumber); break;
            case 'Week(s)': end.setDate(end.getDate() + (durationNumber * 7)); break;
            case 'Month(s)': end.setMonth(end.getMonth() + durationNumber); break;
            case 'Quarter(s)': end.setMonth(end.getMonth() + (durationNumber * 3)); break;
            case 'Year(s)': end.setFullYear(end.getFullYear() + durationNumber); break;
            default: return '';
        }
        return end.toISOString().split('T')[0];
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function renderItems() {
        itemsBody.innerHTML = '';
        let subtotal = 0;
        let taxTotal = 0;
        let discountTotal = 0;
        const anyItemHasRecurringFrequency = invoiceItems.some((item) => item.frequency && item.frequency !== 'One-Time');

        invoiceItems.forEach((item, index) => {
            item.quantity = Math.max(1, Math.round(Number(item.quantity) || 1));
            item.unit_price = Number(item.unit_price) || 0;
            item.tax_rate = Number(item.tax_rate) || 0;
            item.discount_percent = Math.min(100, Math.max(0, Number(item.discount_percent) || 0));
            item.no_of_users = Math.max(1, Number(item.no_of_users) || 1);
            item.line_total = calculateLineTotal(item.quantity, item.unit_price, item.no_of_users, item.frequency, item.duration);
            const lineDiscount = roundDiscountDown(item.line_total * (item.discount_percent / 100));
            const taxableAmount = Math.max(0, item.line_total - lineDiscount);
            const lineTax = roundTaxUp(taxableAmount * (item.tax_rate / 100));
            subtotal += item.line_total;
            discountTotal += lineDiscount;
            taxTotal += lineTax;

            const showDates = item.frequency && item.frequency !== 'One-Time';
            const description = escapeHtml(item.item_description || '').trim();

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <div style="font-weight: 600; color: #111827;">${escapeHtml(item.item_name || 'Item')}</div>
                    ${description ? `<div style="margin-top: 0.15rem; font-size: 0.78rem; color: #6b7280; white-space: pre-wrap;">${description}</div>` : ''}
                </td>
                <td>${item.quantity}</td>
                <td>${item.unit_price}</td>
                <td>${item.discount_percent}%</td>
                ${allowMultiTaxation ? `<td>${item.tax_rate}%</td>` : ''}
                ${accountHasUsers ? `<td>${item.no_of_users || 1}</td>` : ''}
                <td>${item.frequency ? (frequencyLabels[item.frequency] || item.frequency) : '-'}</td>
                <td style="display: ${anyItemHasRecurringFrequency ? '' : 'none'}">${showDates ? (item.duration || '-') : '-'}</td>
                <td style="display: ${anyItemHasRecurringFrequency ? '' : 'none'}">${showDates ? (item.start_date || '-') : '-'}</td>
                <td style="display: ${anyItemHasRecurringFrequency ? '' : 'none'}">${showDates ? (item.end_date || '-') : '-'}</td>
                <td><strong>${formatMoney(Math.max(0, taxableAmount + lineTax))}</strong></td>
                <td style="white-space: nowrap;">
                    <button type="button" class="icon-action-btn edit edit-item" data-index="${index}" title="Edit"><i class="fas fa-edit"></i></button>
                    <button type="button" class="icon-action-btn delete remove-item" data-index="${index}" title="Remove"><i class="fas fa-trash"></i></button>
                </td>
            `;
            itemsBody.appendChild(row);
        });

        const durationHeader = document.getElementById('itemsDurationHeader');
        const startHeader = document.getElementById('itemsStartHeader');
        const endHeader = document.getElementById('itemsEndHeader');
        if (durationHeader) durationHeader.style.display = anyItemHasRecurringFrequency ? '' : 'none';
        if (startHeader) startHeader.style.display = anyItemHasRecurringFrequency ? '' : 'none';
        if (endHeader) endHeader.style.display = anyItemHasRecurringFrequency ? '' : 'none';

        const roundedDiscountTotal = roundDiscountDown(discountTotal);
        const roundedTaxTotal = roundTaxUp(taxTotal);

        document.getElementById('subtotalDisplay').textContent = formatMoney(subtotal);
        document.getElementById('discountDisplay').textContent = formatMoney(roundedDiscountTotal);
        document.getElementById('taxDisplay').textContent = formatMoney(roundedTaxTotal);
        document.getElementById('grandTotalDisplay').textContent = formatMoney(subtotal - roundedDiscountTotal + roundedTaxTotal);
    }

    function resetAddItemForm() {
        editingItemIndex = null;
        addItemBtn.textContent = 'Add Item';
        document.getElementById('item_itemid').value = '';
        document.getElementById('item_quantity').value = 1;
        document.getElementById('item_unit_price').value = '';
        document.getElementById('item_discount').value = 0;
        document.getElementById('item_tax_rate').value = '0';
        document.getElementById('item_frequency').value = '';
        document.getElementById('item_duration').value = '';
        document.getElementById('item_description').value = '';
        document.getElementById('item_users').value = 1;
        @if($account->have_users)
        toggleAddFormUsersField();
        @endif
        document.getElementById('item_start_date').value = '';
        document.getElementById('item_end_date').value = '';
        toggleAddFormRecurringFields();
    }

    let editingItemIndex = null;
    let addItemPanelVisible = false;

    function openAddItemPanel() {
        addItemPanelVisible = true;
        addItemPanel.style.display = 'block';
        toggleAddItemBtn.textContent = 'Cancel';
        toggleAddItemBtn.classList.remove('primary-button');
        toggleAddItemBtn.classList.add('secondary-button');
    }

    function closeAddItemPanel() {
        addItemPanelVisible = false;
        addItemPanel.style.display = 'none';
        toggleAddItemBtn.textContent = '+ Add Item';
        toggleAddItemBtn.classList.remove('secondary-button');
        toggleAddItemBtn.classList.add('primary-button');
        resetAddItemForm();
    }

    clientSelect.addEventListener('change', function () {
        clientCurrency = this.options[this.selectedIndex]?.dataset?.currency || 'INR';
        currencyCodeInput.value = clientCurrency;
        renderItems();
    });

    toggleAddItemBtn.addEventListener('click', function () {
        if (addItemPanelVisible) {
            closeAddItemPanel();
        } else {
            openAddItemPanel();
        }
    });

    document.getElementById('item_itemid').addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        document.getElementById('item_unit_price').value = option?.dataset?.price || '';
        document.getElementById('item_tax_rate').value = option?.dataset?.taxRate || '0';
        @if($account->have_users)
        toggleAddFormUsersField();
        @endif
    });

    @if($account->have_users)
    function isAddFormItemUserWise() {
        const select = document.getElementById('item_itemid');
        const option = select?.options[select.selectedIndex];
        return option?.dataset?.userWise === '1';
    }

    function toggleAddFormUsersField() {
        const wrap = document.getElementById('item_users_wrap');
        const users = document.getElementById('item_users');
        if (!wrap || !users) return;
        const show = isAddFormItemUserWise();
        wrap.style.display = show ? '' : 'none';
        if (!show) users.value = 1;
    }
    toggleAddFormUsersField();
    @endif

    function isRecurringFrequency(frequency) {
        return Boolean(frequency) && frequency !== 'One-Time';
    }

    function toggleAddFormRecurringFields() {
        const frequency = document.getElementById('item_frequency').value;
        const showRecurringFields = isRecurringFrequency(frequency);
        const durationWrap = document.getElementById('item_duration_wrap');
        const startDateWrap = document.getElementById('item_start_date_wrap');
        const endDateWrap = document.getElementById('item_end_date_wrap');

        if (durationWrap) durationWrap.style.display = showRecurringFields ? 'block' : 'none';
        if (startDateWrap) startDateWrap.style.display = showRecurringFields ? 'block' : 'none';
        if (endDateWrap) endDateWrap.style.display = showRecurringFields ? 'block' : 'none';

        if (!showRecurringFields) {
            document.getElementById('item_duration').value = '';
            document.getElementById('item_start_date').value = '';
            document.getElementById('item_end_date').value = '';
        }
    }

    document.getElementById('item_frequency').addEventListener('change', toggleAddFormRecurringFields);
    toggleAddFormRecurringFields();

    ['item_start_date', 'item_frequency', 'item_duration'].forEach((id) => {
        document.getElementById(id).addEventListener('change', function () {
            document.getElementById('item_end_date').value = calculateEndDate(
                document.getElementById('item_start_date').value,
                document.getElementById('item_frequency').value,
                document.getElementById('item_duration').value
            );
        });
    });

    addItemBtn.addEventListener('click', function () {
        const select = document.getElementById('item_itemid');
        const option = select.options[select.selectedIndex];
        if (!select.value) {
            alert('Select an item first.');
            return;
        }

        const item = {
            itemid: select.value,
            item_name: (option.text || '').split(' (')[0],
            item_description: (document.getElementById('item_description').value || '').trim(),
            quantity: Math.max(1, Math.round(Number(document.getElementById('item_quantity').value) || 1)),
            unit_price: Number(document.getElementById('item_unit_price').value) || 0,
            discount_percent: Math.min(100, Math.max(0, Number(document.getElementById('item_discount').value) || 0)),
            tax_rate: Number(document.getElementById('item_tax_rate').value) || 0,
            duration: isRecurringFrequency(document.getElementById('item_frequency').value)
                ? (document.getElementById('item_duration').value || null)
                : null,
            frequency: document.getElementById('item_frequency').value || null,
            @if($account->have_users)
            no_of_users: isAddFormItemUserWise() ? Math.max(1, Number(document.getElementById('item_users').value) || 1) : null,
            @else
            no_of_users: Math.max(1, Number(document.getElementById('item_users').value) || 1),
            @endif
            start_date: isRecurringFrequency(document.getElementById('item_frequency').value)
                ? (document.getElementById('item_start_date').value || null)
                : null,
            end_date: isRecurringFrequency(document.getElementById('item_frequency').value)
                ? (document.getElementById('item_end_date').value || null)
                : null,
        };

        item.line_total = calculateLineTotal(item.quantity, item.unit_price, item.no_of_users, item.frequency, item.duration);

        if (editingItemIndex !== null) {
            const existingItem = invoiceItems[editingItemIndex];
            item.invoice_itemid = existingItem.invoice_itemid || null;
            invoiceItems[editingItemIndex] = item;

            // If this is a persisted item, save it to DB immediately via AJAX
            if (item.invoice_itemid) {
                const invoiceId = '{{ $invoice->invoiceid }}';
                const url = '{{ url("invoices") }}/' + invoiceId + '/items/' + item.invoice_itemid;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                    || document.querySelector('input[name="_token"]')?.value || '';

                addItemBtn.disabled = true;
                addItemBtn.textContent = 'Saving...';

                fetch(url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        item_name: item.item_name,
                        item_description: item.item_description || '',
                        quantity: item.quantity,
                        unit_price: item.unit_price,
                        tax_rate: item.tax_rate,
                        discount_percent: item.discount_percent,
                        duration: item.duration || null,
                        frequency: item.frequency || null,
                        no_of_users: item.no_of_users || null,
                        start_date: item.start_date || null,
                        end_date: item.end_date || null,
                        line_total: item.line_total,
                    }),
                })
                .then((res) => {
                    if (!res.ok) {
                        return res.text().then((text) => { throw new Error(`HTTP ${res.status}: ${text.substring(0, 300)}`); });
                    }
                    return res.json();
                })
                .then((data) => {
                    addItemBtn.disabled = false;
                    addItemBtn.textContent = 'Update Item';
                    if (!data.success) {
                        alert('Failed to save item: ' + (data.message || 'Unknown error'));
                    } else {
                        closeAddItemPanel();
                    }
                })
                .catch((err) => {
                    addItemBtn.disabled = false;
                    addItemBtn.textContent = 'Update Item';
                    alert('Error saving item: ' + err.message);
                });

                renderItems();
                return;
            }
        } else {
            invoiceItems.push(item);
        }

        renderItems();
        closeAddItemPanel();
    });

    itemsBody.addEventListener('click', function (event) {
        const editBtn = event.target.closest('.edit-item');
        if (editBtn) {
            const index = Number(editBtn.dataset.index);
            const item = invoiceItems[index];
            if (!item) return;

            editingItemIndex = index;
            addItemBtn.textContent = 'Update Item';

            const select = document.getElementById('item_itemid');
            select.value = item.itemid || '';
            document.getElementById('item_quantity').value = item.quantity;
            document.getElementById('item_unit_price').value = item.unit_price;
            document.getElementById('item_discount').value = item.discount_percent || 0;
            document.getElementById('item_tax_rate').value = item.tax_rate || 0;
            document.getElementById('item_description').value = item.item_description || '';
            document.getElementById('item_frequency').value = item.frequency || '';
            document.getElementById('item_duration').value = item.duration || '';
            document.getElementById('item_start_date').value = item.start_date || '';
            document.getElementById('item_end_date').value = item.end_date || '';
            document.getElementById('item_users').value = item.no_of_users || 1;

            @if($account->have_users)
            toggleAddFormUsersField();
            @endif
            toggleAddFormRecurringFields();
            openAddItemPanel();
            addItemPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            return;
        }

        const removeBtn = event.target.closest('.remove-item');
        if (removeBtn) {
            invoiceItems.splice(Number(removeBtn.dataset.index), 1);
            renderItems();
        }
    });

    document.getElementById('{{ isset($inline) && $inline ? 'inline-edit-form-' . $documentId : 'invoiceForm' }}').addEventListener('submit', function (event) {
        if (!invoiceItems.length) {
            event.preventDefault();
            alert('Add at least one invoice item before updating the invoice.');
            return;
        }

        itemsDataInput.value = JSON.stringify(invoiceItems.map((item) => ({
            itemid: item.itemid,
            item_name: item.item_name,
            item_description: item.item_description || '',
            quantity: Math.max(1, Math.round(Number(item.quantity) || 1)),
            unit_price: Number(item.unit_price) || 0,
            discount_percent: Math.min(100, Math.max(0, Number(item.discount_percent) || 0)),
            tax_rate: Number(item.tax_rate) || 0,
            duration: item.duration || null,
            frequency: item.frequency || null,
            no_of_users: Math.max(1, Number(item.no_of_users) || 1),
            start_date: item.start_date || null,
            end_date: item.end_date || null,
            line_total: calculateLineTotal(item.quantity, item.unit_price, item.no_of_users, item.frequency, item.duration),
        })));

        @if(!isset($inline) || !$inline)
        updateInvoiceBtn.disabled = true;
        updateInvoiceBtn.textContent = 'Updating...';
        @endif
    });

    renderItems();
})();
</script>

{{-- Add Tax Modal --}}
@if($account->allow_multi_taxation)
<div class="modal fade" id="addTaxModalInvoiceEdit" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content" class="rounded-panel">
            <div class="modal-header" class="modal-header-custom">
                <h5 class="modal-title" style="font-size: 1rem; font-weight: 600;">
                    <i class="fas fa-receipt" style="margin-right: 0.5rem; color: #64748b;"></i>Add Tax
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 1.25rem;">
                <form method="POST" action="{{ route('taxes.store') }}" id="quick-tax-form-invoice-edit">
                    @csrf
                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Rate (%)</label>
                        <input type="number" name="rate" placeholder="18" step="0.01" min="0" max="100" required
                               style="padding: 0.4rem 0.75rem; font-size: 0.9rem; width: 100%;">
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Type</label>
                        <select name="type" required
                                style="padding: 0.4rem 0.75rem; font-size: 0.9rem; width: 100%;">
                            @foreach(['GST'=>'GST','VAT'=>'VAT'] as $v=>$l)
                                <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <button type="submit" class="primary-button small">Add Tax</button>
                        <button type="button" class="text-link small" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const taxModalEl = document.getElementById('addTaxModalInvoiceEdit');
    const openTaxModalLink = document.getElementById('open-tax-modal-invoice-edit');
    if (taxModalEl && openTaxModalLink) {
        const taxModal = new bootstrap.Modal(taxModalEl);
        openTaxModalLink.addEventListener('click', function(e) {
            e.preventDefault();
            taxModal.show();
        });
    }
})();
</script>
@endif

<style>
.invoice-create-shell #invoiceForm .form-input,
.invoice-create-shell #invoiceForm input[type="text"],
.invoice-create-shell #invoiceForm input[type="number"],
.invoice-create-shell #invoiceForm input[type="date"],
.invoice-create-shell #invoiceForm select,
.invoice-create-shell #invoiceForm textarea {
    min-height: 32px;
    padding: 0.32rem 0.55rem;
    font-size: 0.8rem;
}
.invoice-create-shell #invoiceForm textarea {
    line-height: 1.3;
}
.invoice-meta-card { padding: 0.95rem 1rem; border: 1px solid #e2e8f0; border-radius: 12px; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); }
.invoice-meta-label, .field-label.small { display: block; margin-bottom: 0.35rem; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; color: #64748b; }
.invoice-meta-value { color: #1e293b; font-size: 0.9rem; }
.field-label { display: block; margin-bottom: 0.22rem; font-size: 0.78rem; font-weight: 600; color: #475569; }
.workflow-panel { margin-top: 0.85rem; padding-top: 0.85rem; border-top: 1px solid #e2e8f0; }
.panel-heading-row { margin-bottom: 0.4rem; }
.table-shell { border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; background: #ffffff; }
.builder-card { padding: 0.6rem; border: 1px solid #e2e8f0; border-radius: 14px; background: #f8fafc; }
.manual-grid { display: grid; grid-template-columns: 2fr 0.7fr 1fr 0.8fr 1fr 0.8fr 0.8fr 1fr 1fr; gap: 0.4rem; align-items: end; }
.totals-card { padding: 0.7rem 0.75rem; border-radius: 14px; background: #f8fafc; border: 1px solid #e2e8f0; }
.total-row { display: flex; justify-content: space-between; gap: 1rem; margin-bottom: 0.28rem; font-size: 0.82rem; color: #475569; }
.total-row:last-child { margin-bottom: 0; }
.total-row-grand { padding-top: 0.35rem; border-top: 1px solid #cbd5e1; font-size: 0.9rem; font-weight: 700; color: #1e293b; }
@media (max-width: 1200px) { .manual-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 720px) { .manual-grid { grid-template-columns: 1fr; } }
</style>
