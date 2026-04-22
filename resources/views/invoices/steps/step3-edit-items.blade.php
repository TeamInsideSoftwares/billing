@php
    $normalizeTaxState = static fn ($value) => preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $value)));
    $serviceGroups = collect($services ?? [])->groupBy(function ($service) {
        return optional($service->category)->name ?? 'No Category';
    });
    $selectedInvoiceClient = $clients->firstWhere('clientid', request('c', request('clientid')));
    $selectedClientCurrency = optional($selectedInvoiceClient)->currency ?? 'INR';
    $invoiceClientState = $normalizeTaxState(optional($selectedInvoiceClient)->state ?? '');
    $invoiceAccountState = $normalizeTaxState(optional($account)->state ?? '');
    $sameStateGstForInvoice = $invoiceClientState !== '' && $invoiceAccountState !== '' && $invoiceClientState === $invoiceAccountState;
@endphp
<!-- Step 3: Edit Items (For Orders & Renewal) -->
<div id="step3" class="invoice-step">
    <div class="invoice-step-toolbar">
        <button type="button" id="btnBackToStep2" class="secondary-button" style="padding: 0.4rem 0.8rem;">&larr; Back</button>
        <div class="invoice-side-meta">
            <span class="invoice-meta-label">PI</span>
            <strong class="invoice-meta-value" id="piNumberBadgeStep3">{{ $nextInvoiceNumber }}</strong>
        </div>
    </div>

    <div class="invoice-grid-4" style="margin-bottom: 0.85rem;">
        <div style="display:flex; flex-direction:column;">
            <label for="invoice_title" class="field-label">Invoice Title</label>
            <input type="text" id="invoice_title" name="invoice_title" class="form-input" placeholder="e.g. Website Development - Monthly Subscription" required style="height:42px; box-sizing:border-box;">
            <div id="invoiceTitleError" style="display:none; margin-top: 0.35rem; color: #b91c1c; font-size: 0.8rem; font-weight: 600;">Invoice title is required.</div>
        </div>

        <div style="display:flex; flex-direction:column;">
            <label for="issue_date" class="field-label">Issue Date</label>
            <input type="date" id="issue_date" name="issue_date" class="form-input" required style="height:42px; box-sizing:border-box;">
        </div>

        <div style="display:flex; flex-direction:column;">
            <label for="due_date" class="field-label">Due Date</label>
            <input type="date" id="due_date" name="due_date" class="form-input" required style="height:42px; box-sizing:border-box;">
        </div>

        <div style="display:flex; flex-direction:column;">
            <label for="notes" class="field-label">Notes</label>
            <textarea id="notes" name="notes" class="form-input" placeholder="Optional notes" style="height:42px; min-height:42px; padding:0.5rem 0.75rem; box-sizing:border-box; resize:none; overflow:hidden; line-height:1.4;"></textarea>
        </div>
    </div>

    <input type="hidden" name="clientid" value="{{ request('c', request('clientid')) }}">
    <input type="hidden" name="orderid" value="{{ request('o', request('orderid', '')) === '0' ? '' : request('o', request('orderid', '')) }}">
    <input type="hidden" name="subtotal" id="subtotal" value="0">
    <input type="hidden" name="tax_total" id="tax_total" value="0">
    <input type="hidden" name="discount_total" id="discount_total" value="0">
    <input type="hidden" name="grand_total" id="grand_total" value="0">
    <input type="hidden" name="items_data" id="items_data" value="">
    <input type="hidden" name="currency_code" id="currency_code" value="{{ $selectedClientCurrency }}">

    <div id="itemsSection" class="workflow-panel">
        <div class="panel-heading-row" style="margin-bottom: 0.5rem;">
            <div>
            <h4 style="margin: 0; font-size: 0.92rem; color: #111827;">
                    @if(request('o', request('orderid')))
                        Edit Items from Order
                    @else
                        Edit Invoice Items
                    @endif
                </h4>
                <p style="margin: 0.15rem 0 0 0; color: #6b7280; font-size: 0.78rem;">Adjust quantity, pricing, tax, and other details before proceeding.</p>
            </div>
        </div>

        @if(request('o', request('orderid')))
        <div id="orderSummaryInline" style="display: none; margin-bottom: 0.5rem; padding: 0.65rem 0.8rem; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px;">
            <div style="display: flex; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.04em;">Source Order</div>
                    <div id="orderSummaryTitle" style="margin-top: 0.15rem; font-size: 0.88rem; font-weight: 600; color: #111827;">Source Order Details</div>
                </div>
                <div id="orderSummaryDetails" style="display: flex; gap: 0.45rem; flex-wrap: wrap; align-items: center;"></div>
            </div>
        </div>
        <div style="margin-bottom: 0.65rem; display: flex; justify-content: flex-end;">
            <button type="button" id="toggleAddItemFormBtn" class="text-link" style="display: none; align-items: center; justify-content: center; gap: 0.35rem; font-size: 0.8rem; padding: 0.35rem 0.65rem; border: 1px solid #e5e7eb; border-radius: 6px; background: #ffffff; color: #4f46e5; font-weight: 500; line-height: 1;">
                <i class="fas fa-plus" style="font-size: 0.75rem; line-height: 1; vertical-align: middle;"></i>
                <span style="line-height: 1;">Add More Items</span>
            </button>
        </div>
        @else
        <div style="margin-bottom: 0.65rem; display: flex; justify-content: flex-end;">
            <button type="button" id="toggleAddItemFormBtn" class="text-link" style="display: none; align-items: center; justify-content: center; gap: 0.35rem; font-size: 0.8rem; padding: 0.35rem 0.65rem; border: 1px solid #e5e7eb; border-radius: 6px; background: #ffffff; color: #4f46e5; font-weight: 500; line-height: 1;">
                <i class="fas fa-plus" style="font-size: 0.75rem; line-height: 1; vertical-align: middle;"></i>
                <span style="line-height: 1;">Add More Items</span>
            </button>
        </div>
        @endif

        <div class="builder-card" id="addItemFormCard" style="margin-bottom: 0.65rem; padding: 0.6rem; display: none;">
            <div class="manual-grid" style="grid-template-columns: 2fr 0.7fr 0.85fr 0.75fr 0.85fr 0.85fr 0.7fr 0.9fr 0.9fr 0.85fr; gap: 0.45rem;">
                <div class="invoice-span-2">
                    <label for="add_item_itemid" class="field-label small">Item</label>
                    <select id="add_item_itemid" class="form-input">
                        <option value="">Select item</option>
                        @foreach($serviceGroups as $categoryName => $categoryServices)
                            <optgroup label="{{ $categoryName }}">
                                @foreach($categoryServices as $service)
                                    @php
                                        $defaultCosting = $service->costings->sortBy('currency_code')->first();
                                    @endphp
                                    <option
                                        value="{{ $service->itemid }}"
                                        data-name="{{ $service->name }}"
                                        data-selling-price="{{ $defaultCosting?->selling_price ?? 0 }}"
                                        data-tax-rate="{{ $defaultCosting?->tax_rate ?? 0 }}"
                                        data-description="{{ $service->description ?? '' }}"
                                        data-user-wise="{{ (int) ($service->user_wise ?? 0) }}"
                                    >
                                        {{ $service->name }} ({{ number_format($defaultCosting?->selling_price ?? 0, 0) }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <label for="add_item_description" class="field-label small" style="margin-top: 0.3rem;">Description</label>
                    <textarea id="add_item_description" class="form-input" rows="1" placeholder="Description (optional)" style="height: 34px; min-height: 34px; resize: none; line-height: 1.2;"></textarea>
                </div>
                <div>
                    <label for="add_item_quantity" class="field-label small">Qty</label>
                    <input type="number" id="add_item_quantity" class="form-input" value="1" min="1" step="1">
                </div>
                <div>
                    <label for="add_item_unit_price" class="field-label small">Price</label>
                    <input type="number" id="add_item_unit_price" class="form-input" min="0" step="0.01">
                </div>
                <div>
                    <label for="add_item_discount" class="field-label small">Disc %</label>
                    <input type="number" id="add_item_discount" class="form-input" value="0" min="0" max="100" step="0.01">
                </div>
                @if($account->allow_multi_taxation)
                <div>
                    <label for="add_item_tax_rate" class="field-label small">Tax %</label>
                    <select id="add_item_tax_rate" class="form-input">
                        <option value="0">0%</option>
                        @foreach($taxes as $tax)
                            <option value="{{ $tax->rate }}">{{ number_format($tax->rate, 0) }}%</option>
                        @endforeach
                    </select>
                </div>
                @else
                <input type="hidden" id="add_item_tax_rate" value="{{ $account->fixed_tax_rate ?? 0 }}">
                @endif
                @if($account->have_users)
                <div id="add_item_users_wrap" style="display: none;">
                    <label for="add_item_users" class="field-label small">Users</label>
                    <input type="number" id="add_item_users" class="form-input" value="1" min="1" step="1">
                </div>
                @else
                <input type="hidden" id="add_item_users" value="1">
                @endif
                <div>
                    <label for="add_item_frequency" class="field-label small">Freq</label>
                    <select id="add_item_frequency" class="form-input">
                        <option value="">None</option>
                        @foreach(['one-time', 'daily', 'weekly', 'bi-weekly', 'monthly', 'quarterly', 'semi-annually', 'yearly'] as $freq)
                        <option value="{{ $freq }}">{{ ucfirst(str_replace('-', ' ', $freq)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="add_item_duration_wrap" style="display: none;">
                    <label for="add_item_duration" class="field-label small">Dur</label>
                    <input type="number" id="add_item_duration" class="form-input" min="0" step="1">
                </div>
                <div id="add_item_start_wrap" style="display: none;">
                    <label for="add_item_start_date" class="field-label small">Start</label>
                    <input type="date" id="add_item_start_date" class="form-input">
                </div>
                <div id="add_item_end_wrap" style="display: none;">
                    <label for="add_item_end_date" class="field-label small">End</label>
                    <input type="date" id="add_item_end_date" class="form-input">
                </div>
                <div style="display: flex; align-items: start; align-self: start; margin-top: 1.45rem;">
                    <button type="button" id="btnAddItemStep3" class="primary-button" style="width: 100%; padding: 0.5rem 0.75rem; font-size: 0.85rem;">Add</button>
                </div>
            </div>
        </div>

        <div class="table-shell">
            <table class="data-table" id="itemsTable" style="margin: 0; font-size: 0.8rem;">
                <thead id="itemsTableHead">
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th id="itemsUsersHeader" style="display:none;">Users</th>
                        <th>Disc %</th>
                        @if($account->allow_multi_taxation)
                        <th>Tax %</th>
                        @endif
                        <th>Freq</th>
                        <th id="itemsDurationHeader">Dur</th>
                        <th id="itemsStartHeader" style="display:none;">Start Date</th>
                        <th id="itemsEndHeader" style="display:none;">End Date</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
            </table>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 0.8rem;">
            <div class="totals-card" style="min-width: 280px; max-width: 350px;">
                <div class="total-row"><span>Subtotal</span><strong id="subtotalDisplay">0</strong></div>
                <div class="total-row"><span>Discount</span><strong id="discountDisplay">0</strong></div>
                <div id="step3TaxRow" class="total-row">
                    <span id="step3TaxLabel">{{ $sameStateGstForInvoice ? 'Tax (CGST + SGST)' : 'Tax (IGST)' }}</span>
                    <strong id="taxDisplay">0</strong>
                </div>
                <div class="total-row total-row-grand"><span>Grand Total</span><strong id="grandTotalDisplay">0</strong></div>
            </div>
        </div>
    </div>

    <div style="margin-top: 0.9rem;">
        <button type="button" class="primary-button" id="btnNextToStep4" style="width: 100%; padding: 0.75rem 1rem;">Review & Terms &rarr;</button>
    </div>
</div>

<script>
(function() {
    const clientId = "{{ request('c', request('clientid')) }}";
    const invoiceFor = "{{ request('invoice_for') }}";
    const orderId = "{{ request('o', request('orderid', '')) }}";
    const hasOrderId = orderId && orderId !== '0';
    const accountHasUsers = @json((bool) ($account->have_users ?? false));
    const sameStateGstForInvoice = @json($sameStateGstForInvoice);
    const itemsBody = document.getElementById('itemsBody');
    const currencyCodeInput = document.getElementById('currency_code');
    const btnNextToStep4 = document.getElementById('btnNextToStep4');
    const btnBackToStep2 = document.getElementById('btnBackToStep2');
    const itemsDataInput = document.getElementById('items_data');
    const invoiceTitleInput = document.getElementById('invoice_title');
    const invoiceTitleError = document.getElementById('invoiceTitleError');
    const piNumberBadgeStep3 = document.getElementById('piNumberBadgeStep3');
    const addItemSelect = document.getElementById('add_item_itemid');
    const addItemQuantityInput = document.getElementById('add_item_quantity');
    const addItemPriceInput = document.getElementById('add_item_unit_price');
    const addItemDescriptionInput = document.getElementById('add_item_description');
    const addItemDiscountInput = document.getElementById('add_item_discount');
    const addItemTaxRateInput = document.getElementById('add_item_tax_rate');
    const addItemUsersWrap = document.getElementById('add_item_users_wrap');
    const addItemUsersInput = document.getElementById('add_item_users');
    const addItemFrequencyInput = document.getElementById('add_item_frequency');
    const addItemDurationWrap = document.getElementById('add_item_duration_wrap');
    const addItemDurationInput = document.getElementById('add_item_duration');
    const addItemStartWrap = document.getElementById('add_item_start_wrap');
    const addItemEndWrap = document.getElementById('add_item_end_wrap');
    const addItemStartInput = document.getElementById('add_item_start_date');
    const addItemEndInput = document.getElementById('add_item_end_date');
    const btnAddItemStep3 = document.getElementById('btnAddItemStep3');
    const toggleAddItemFormBtn = document.getElementById('toggleAddItemFormBtn');
    const addItemFormCard = document.getElementById('addItemFormCard');

    let invoiceItems = [];
    let addItemFormVisible = false;

    function getCurrencyCode() {
        return currencyCodeInput.value || '{{ $selectedClientCurrency }}';
    }

    function formatCurrency(amount) {
        return `${Number(amount || 0).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0})}`;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function updateTaxDisplay(taxTotal) {
        const taxRow = document.getElementById('step3TaxRow');
        const taxLabel = document.getElementById('step3TaxLabel');
        const taxDisplay = document.getElementById('taxDisplay');

        if (taxRow) taxRow.style.display = '';
        if (taxLabel) taxLabel.textContent = (sameStateGstForInvoice ? 'Tax (CGST + SGST)' : 'Tax (IGST)');
        if (taxDisplay) taxDisplay.textContent = formatCurrency(taxTotal);
    }

    function itemSupportsUserFields(item) {
        return accountHasUsers && Boolean(item && item.requires_user_fields);
    }

    function itemHasRecurringFrequency(item) {
        return Boolean(item && item.frequency) && item.frequency !== 'one-time';
    }

    function isAddItemUserWise() {
        const selectedOption = addItemSelect?.options[addItemSelect.selectedIndex];
        return selectedOption?.dataset?.userWise === '1';
    }

    function toggleAddItemUsersField() {
        if (!addItemUsersWrap || !addItemUsersInput) return;
        const show = accountHasUsers && isAddItemUserWise();
        addItemUsersWrap.style.display = show ? 'block' : 'none';
        if (!show) addItemUsersInput.value = 1;
    }

    function toggleAddItemRecurringFields() {
        const showRecurring = Boolean(addItemFrequencyInput?.value) && addItemFrequencyInput.value !== 'one-time';
        if (addItemDurationWrap) addItemDurationWrap.style.display = showRecurring ? 'block' : 'none';
        if (addItemStartWrap) addItemStartWrap.style.display = showRecurring ? 'block' : 'none';
        if (addItemEndWrap) addItemEndWrap.style.display = showRecurring ? 'block' : 'none';
        if (!showRecurring) {
            if (addItemDurationInput) addItemDurationInput.value = '';
            if (addItemStartInput) addItemStartInput.value = '';
            if (addItemEndInput) addItemEndInput.value = '';
        }
    }

    function calculateEndDate(startDate, frequency, duration) {
        if (!startDate || !frequency || frequency === 'one-time' || !duration) {
            return '';
        }

        const date = new Date(startDate);
        const steps = Math.max(0, Number(duration) || 0);

        if (steps <= 0 || Number.isNaN(date.getTime())) {
            return '';
        }

        switch (frequency) {
            case 'daily':
                date.setDate(date.getDate() + steps);
                break;
            case 'weekly':
                date.setDate(date.getDate() + (steps * 7));
                break;
            case 'bi-weekly':
                date.setDate(date.getDate() + (steps * 14));
                break;
            case 'monthly':
                date.setMonth(date.getMonth() + steps);
                break;
            case 'quarterly':
                date.setMonth(date.getMonth() + (steps * 3));
                break;
            case 'semi-annually':
                date.setMonth(date.getMonth() + (steps * 6));
                break;
            case 'yearly':
                date.setFullYear(date.getFullYear() + steps);
                break;
            default:
                return '';
        }

        return date.toISOString().split('T')[0];
    }

    function normalizeItem(item) {
        const normalizedItem = {
            ...item,
            item_description: item.item_description || '',
            quantity: Math.max(1, Math.round(Number(item.quantity || 1))),
            unit_price: Number(item.unit_price || 0),
            tax_rate: Number(item.tax_rate || 0),
            discount_percent: Number(item.discount_percent || 0),
            discount_amount: Number(item.discount_amount || 0),
            duration: item.duration ?? null,
            frequency: item.frequency ?? '',
            line_total: Number(item.line_total || 0),
            requires_user_fields: accountHasUsers && Boolean(item && item.requires_user_fields),
            no_of_users: null,
            start_date: item.start_date || null,
            end_date: item.end_date || null,
        };

        if (itemSupportsUserFields(normalizedItem)) {
            normalizedItem.no_of_users = Math.max(1, Number(item.no_of_users || 1));
        }

        if (itemHasRecurringFrequency(normalizedItem) && !normalizedItem.end_date && normalizedItem.start_date && normalizedItem.duration) {
            normalizedItem.end_date = calculateEndDate(normalizedItem.start_date, normalizedItem.frequency, normalizedItem.duration);
        }

        if (!itemHasRecurringFrequency(normalizedItem)) {
            normalizedItem.duration = null;
            normalizedItem.start_date = null;
            normalizedItem.end_date = null;
        }

        return normalizedItem;
    }

    function resetAddItemForm() {
        if (addItemSelect) addItemSelect.value = '';
        if (addItemQuantityInput) addItemQuantityInput.value = '1';
        if (addItemPriceInput) addItemPriceInput.value = '';
        if (addItemDescriptionInput) addItemDescriptionInput.value = '';
        if (addItemDiscountInput) addItemDiscountInput.value = '0';
        if (addItemFrequencyInput) addItemFrequencyInput.value = '';
        if (addItemDurationInput) addItemDurationInput.value = '';
        if (addItemStartInput) addItemStartInput.value = '';
        if (addItemEndInput) addItemEndInput.value = '';
        toggleAddItemUsersField();
        toggleAddItemRecurringFields();
    }

    function toggleAddItemForm() {
        addItemFormVisible = !addItemFormVisible;
        if (addItemFormCard) {
            addItemFormCard.style.display = addItemFormVisible ? 'block' : 'none';
        }
        if (toggleAddItemFormBtn) {
            if (addItemFormVisible) {
                toggleAddItemFormBtn.innerHTML = '<i class="fas fa-times" style="margin-right: 0.35rem; font-size: 0.75rem;"></i>Cancel';
                toggleAddItemFormBtn.style.background = '#fef2f2';
                toggleAddItemFormBtn.style.color = '#dc2626';
                toggleAddItemFormBtn.style.borderColor = '#fecaca';
            } else {
                toggleAddItemFormBtn.innerHTML = '<i class="fas fa-plus" style="margin-right: 0.35rem; font-size: 0.75rem;"></i>Add More Items';
                toggleAddItemFormBtn.style.background = '#ffffff';
                toggleAddItemFormBtn.style.color = '#4f46e5';
                toggleAddItemFormBtn.style.borderColor = '#e5e7eb';
            }
        }
        if (!addItemFormVisible) {
            resetAddItemForm();
        }
    }

    function initializeAddItemFormVisibility() {
        // Show the "Add More Items" button only for orders and renewal sources
        if (invoiceFor === 'orders' || invoiceFor === 'renewal') {
            if (toggleAddItemFormBtn) {
                toggleAddItemFormBtn.style.display = 'inline-flex';
            }
            // Hide the form by default
            if (addItemFormCard) {
                addItemFormCard.style.display = 'none';
            }
            addItemFormVisible = false;
        } else {
            // For without_orders, always show the form and hide the button
            if (toggleAddItemFormBtn) {
                toggleAddItemFormBtn.style.display = 'none';
            }
            if (addItemFormCard) {
                addItemFormCard.style.display = 'block';
            }
            addItemFormVisible = true;
        }
    }

    function calculateLineInputTotal(item) {
        const qty = Math.max(1, Math.round(Number(item.quantity || 1)));
        const price = Number(item.unit_price || 0);
        const users = itemSupportsUserFields(item) ? Math.max(1, Number(item.no_of_users || 1)) : 1;
        const durationMultiplier = (item.frequency && item.frequency !== 'one-time' && Number(item.duration || 0) > 0)
            ? Number(item.duration || 0)
            : 1;
        return qty * price * users * durationMultiplier;
    }

    function roundTaxUp(value) {
        return Math.ceil(Math.max(0, Number(value) || 0));
    }

    function roundDiscountDown(value) {
        return Math.floor(Math.max(0, Number(value) || 0));
    }

    function normalizeItemAmounts(item) {
        const taxRate = Number(item.tax_rate || 0);
        const discountPercent = Math.min(100, Math.max(0, Number(item.discount_percent || 0)));
        const lineTotal = calculateLineInputTotal(item);
        const discountAmount = roundDiscountDown(lineTotal * (discountPercent / 100));
        const taxAmount = roundTaxUp(Math.max(0, lineTotal - discountAmount) * (taxRate / 100));

        item.line_total = lineTotal;
        item.discount_percent = discountPercent;
        item.discount_amount = discountAmount;
        item.tax_amount = taxAmount;
    }

    function syncConditionalHeaders() {
        const showUserColumns = invoiceItems.some(itemSupportsUserFields);
        const showDurationColumns = invoiceItems.some(itemHasRecurringFrequency);
        const showDateColumns = invoiceItems.some(itemHasRecurringFrequency);

        document.getElementById('itemsUsersHeader').style.display = showUserColumns ? '' : 'none';
        document.getElementById('itemsDurationHeader').style.display = showDurationColumns ? '' : 'none';
        document.getElementById('itemsStartHeader').style.display = showDateColumns ? '' : 'none';
        document.getElementById('itemsEndHeader').style.display = showDateColumns ? '' : 'none';
    }

    function renderOrderSummary(order) {
        const summaryInline = document.getElementById('orderSummaryInline');
        if (!summaryInline || !order) {
            return;
        }

        summaryInline.style.display = 'block';
        currencyCodeInput.value = order.currency || getCurrencyCode();
        document.getElementById('orderSummaryTitle').innerHTML = `<strong>${order.order_title || 'Untitled Order'}</strong>`;
        document.getElementById('orderSummaryDetails').innerHTML =
            `<span class="invoice-step-badge" style="background:#eef2ff; color:#3730a3;">#${order.order_number}</span>` +
            `<span class="invoice-step-badge" style="background:#f8fafc; color:#475569;">${order.order_date}</span>` +
            `<span class="invoice-step-badge" style="background:#f8fafc; color:#475569;">${order.item_count || 0} item(s)</span>` +
            `<span class="invoice-step-badge" style="background:#f8fafc; color:#475569;">${Number(order.grand_total).toLocaleString('en-US', {minimumFractionDigits: 0})}</span>`;
    }

    function loadItems() {
        const draftUrl = new URL("{{ route('invoices.get-draft', ['clientid' => '__CLIENTID__']) }}".replace('__CLIENTID__', clientId), window.location.origin);
        if (invoiceFor) {
            draftUrl.searchParams.set('invoice_for', invoiceFor);
        }
        if (hasOrderId) {
            draftUrl.searchParams.set('o', orderId);
        }

        fetch(draftUrl.toString())
            .then(response => response.json())
            .then(data => {
                const draftItems = Array.isArray(data && data.draft && data.draft.items) ? data.draft.items : [];
                const draftTitle = data && data.draft ? (data.draft.invoice_title || '') : '';

                if (draftTitle) {
                    document.getElementById('invoice_title').value = draftTitle;
                }
                if (data && data.draft && data.draft.invoice_number && piNumberBadgeStep3) {
                    piNumberBadgeStep3.textContent = data.draft.invoice_number;
                }

                if (draftItems.length > 0) {
                    invoiceItems = draftItems.map(normalizeItem);
                    renderItems();
                    initializeAddItemFormVisibility();
                } else if (invoiceFor === 'orders' && orderId) {
                    loadOrderItems(orderId);
                }

                if (orderId) {
                    fetch(`{{ route('invoices.order-items', ['orderid' => '__ORDERID__']) }}`.replace('__ORDERID__', orderId))
                        .then(response => response.json())
                        .then(orderData => {
                            if (orderData && orderData.order) {
                                renderOrderSummary(orderData.order);
                            }
                        })
                        .catch(() => {});
                }
            })
            .catch(() => {
                @if(request('invoice_for') === 'orders' && request('o', request('orderid')))
                loadOrderItems("{{ request('o', request('orderid')) }}");
                @endif
            });
    }

    @if(request('invoice_for') === 'orders' && request('o', request('orderid')))
    function loadOrderItems(selectedOrderId) {
        fetch(`{{ route('invoices.order-items', ['orderid' => '__ORDERID__']) }}`.replace('__ORDERID__', selectedOrderId))
            .then(response => response.json())
            .then(data => {
                if (data && data.order) {
                    renderOrderSummary(data.order);
                }
                invoiceItems = (data.items || []).map(normalizeItem);
                renderItems();
                initializeAddItemFormVisibility();
            })
            .catch(() => {
                console.error('Failed to load order items');
            });
    }
    @endif

    function renderItems() {
        itemsBody.innerHTML = '';
        let subtotal = 0;
        let taxTotal = 0;
        let discountTotal = 0;
        const showUserColumns = invoiceItems.some(itemSupportsUserFields);
        const showDurationColumns = invoiceItems.some(itemHasRecurringFrequency);
        const showDateColumns = invoiceItems.some(itemHasRecurringFrequency);

        syncConditionalHeaders();

        invoiceItems.forEach((item, index) => {
            normalizeItemAmounts(item);
            const lineDiscount = Number(item.discount_amount || 0);
            const lineTax = Number(item.tax_amount || 0);
            subtotal += item.line_total;
            discountTotal += lineDiscount;
            taxTotal += lineTax;
            const showUsersForRow = itemSupportsUserFields(item);
            const showDatesForRow = itemHasRecurringFrequency(item);

            const row = document.createElement('tr');
            const safeItemName = escapeHtml(item.item_name || '');
            const safeItemDescription = escapeHtml(item.item_description || '');
            row.innerHTML = `
                <td>
                    <input type="text" class="form-input item-name" data-index="${index}" value="${safeItemName}" style="min-width: 150px;">
                    <textarea class="form-input item-description" data-index="${index}" rows="2" placeholder="Description (optional)" style="margin-top: 0.35rem; min-width: 180px;">${safeItemDescription}</textarea>
                </td>
                <td><input type="number" class="form-input item-quantity" data-index="${index}" value="${item.quantity}" min="1" step="1" style="width: 80px;"></td>
                <td><input type="number" class="form-input item-price" data-index="${index}" value="${item.unit_price}" min="0" step="0.01" style="width: 100px;"></td>
                <td style="display:${showUserColumns ? '' : 'none'};">
                    ${showUsersForRow
                        ? `<input type="number" class="form-input item-users" data-index="${index}" value="${item.no_of_users || 1}" min="1" step="1" style="width: 70px;">`
                        : '<span style="color:#9ca3af;">-</span>'}
                </td>
                <td><input type="number" class="form-input item-discount" data-index="${index}" value="${item.discount_percent || 0}" min="0" max="100" step="0.01" style="width: 85px;"></td>
                @if($account->allow_multi_taxation)
                <td>
                    <select class="form-input item-tax-rate" data-index="${index}" style="width: 90px;">
                        <option value="0" ${!item.tax_rate ? 'selected' : ''}>0%</option>
                        @foreach($taxes as $tax)
                        <option value="{{ $tax->rate }}" ${item.tax_rate == {{ $tax->rate }} ? 'selected' : ''}>{{ $tax->rate }}%</option>
                        @endforeach
                    </select>
                </td>
                @endif
                <td>
                    <select class="form-input item-frequency" data-index="${index}" style="width: 100px;">
                        <option value="">None</option>
                        @foreach(['one-time', 'daily', 'weekly', 'bi-weekly', 'monthly', 'quarterly', 'semi-annually', 'yearly'] as $freq)
                        <option value="{{ $freq }}" ${(item.frequency || '') === '{{ $freq }}' ? 'selected' : ''}>{{ ucfirst(str_replace('-', ' ', $freq)) }}</option>
                        @endforeach
                    </select>
                </td>
                <td style="display:${showDurationColumns ? '' : 'none'};">
                    ${showDatesForRow
                        ? `<input type="number" class="form-input item-duration" data-index="${index}" value="${item.duration || ''}" min="0" step="1" style="width: 70px;" placeholder="-">`
                        : '<span style="color:#9ca3af;">-</span>'}
                </td>
                <td style="display:${showDateColumns ? '' : 'none'};">
                    ${showDatesForRow
                        ? `<input type="date" class="form-input item-start-date" data-index="${index}" value="${item.start_date || ''}" style="min-width: 135px;">`
                        : '<span style="color:#9ca3af;">-</span>'}
                </td>
                <td style="display:${showDateColumns ? '' : 'none'};">
                    ${showDatesForRow
                        ? `<input type="date" class="form-input item-end-date" data-index="${index}" value="${item.end_date || ''}" style="min-width: 135px;">`
                        : '<span style="color:#9ca3af;">-</span>'}
                </td>
                <td style="text-align: right; font-weight: 600;" class="item-total" data-index="${index}">${formatCurrency(Math.max(0, Number(item.line_total || 0) - Number(item.discount_amount || 0)))}</td>
                <td>
                    <button type="button" class="remove-item-btn" data-index="${index}" title="Remove" style="background: none; border: none; color: #ef4444; cursor: pointer;"><i class="fas fa-trash"></i></button>
                </td>
            `;
            itemsBody.appendChild(row);
        });

        document.querySelectorAll('.item-name, .item-description, .item-quantity, .item-price, .item-discount, .item-tax-rate, .item-users, .item-frequency, .item-duration, .item-start-date, .item-end-date').forEach(input => {
            input.addEventListener('change', recalculateItems);
        });

        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                invoiceItems.splice(parseInt(this.dataset.index, 10), 1);
                renderItems();
            });
        });

        const roundedDiscountTotal = roundDiscountDown(discountTotal);
        const roundedTaxTotal = roundTaxUp(taxTotal);

        document.getElementById('subtotalDisplay').textContent = formatCurrency(subtotal);
        document.getElementById('discountDisplay').textContent = formatCurrency(roundedDiscountTotal);
        updateTaxDisplay(roundedTaxTotal);
        document.getElementById('grandTotalDisplay').textContent = formatCurrency(subtotal - roundedDiscountTotal + roundedTaxTotal);

        document.getElementById('subtotal').value = subtotal.toFixed(0);
        document.getElementById('discount_total').value = roundedDiscountTotal.toFixed(0);
        document.getElementById('tax_total').value = roundedTaxTotal.toFixed(0);
        document.getElementById('grand_total').value = (subtotal - roundedDiscountTotal + roundedTaxTotal).toFixed(0);
        itemsDataInput.value = JSON.stringify(invoiceItems);
    }

    function recalculateItems() {
        document.querySelectorAll('.item-name, .item-description, .item-quantity, .item-price, .item-discount, .item-tax-rate, .item-users, .item-frequency, .item-duration, .item-start-date, .item-end-date').forEach(input => {
            const index = parseInt(input.dataset.index, 10);
            const field = input.className.includes('item-name') ? 'item_name' :
                input.className.includes('item-description') ? 'item_description' :
                input.className.includes('quantity') ? 'quantity' :
                input.className.includes('price') ? 'unit_price' :
                input.className.includes('discount') ? 'discount_percent' :
                input.className.includes('tax-rate') ? 'tax_rate' :
                input.className.includes('users') ? 'no_of_users' :
                input.className.includes('frequency') ? 'frequency' :
                input.className.includes('start-date') ? 'start_date' :
                input.className.includes('end-date') ? 'end_date' :
                input.className.includes('duration') ? 'duration' : null;

            if (!field || !invoiceItems[index]) {
                return;
            }

            if (field === 'item_name' || field === 'item_description' || field === 'frequency' || field === 'duration' || field === 'start_date' || field === 'end_date') {
                invoiceItems[index][field] = input.value;
            } else if (field === 'quantity') {
                const qty = Math.max(1, Math.round(Number(input.value) || 1));
                invoiceItems[index][field] = qty;
                input.value = qty;
            } else {
                invoiceItems[index][field] = parseFloat(input.value) || 0;
            }

            if (field === 'duration') {
                invoiceItems[index].duration = input.value === '' ? null : Math.max(0, Number(input.value) || 0);
            }
            if (field === 'start_date' || field === 'end_date') {
                invoiceItems[index][field] = input.value || null;
            }
            if (field === 'frequency') {
                invoiceItems[index].frequency = input.value || '';
            }

            if (!itemSupportsUserFields(invoiceItems[index])) {
                invoiceItems[index].no_of_users = null;
            } else {
                invoiceItems[index].no_of_users = Math.max(1, Number(invoiceItems[index].no_of_users || 1));
            }

            if (!itemHasRecurringFrequency(invoiceItems[index])) {
                invoiceItems[index].duration = null;
                invoiceItems[index].start_date = null;
                invoiceItems[index].end_date = null;
            } else if (field === 'start_date' || field === 'frequency' || field === 'duration') {
                invoiceItems[index].end_date = calculateEndDate(
                    invoiceItems[index].start_date,
                    invoiceItems[index].frequency,
                    invoiceItems[index].duration
                ) || null;
            }
            normalizeItemAmounts(invoiceItems[index]);
        });

        renderItems();
    }

    addItemSelect?.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const price = Number(selectedOption?.dataset?.sellingPrice || 0);
        const taxRate = Number(selectedOption?.dataset?.taxRate || 0);
        const description = selectedOption?.dataset?.description || '';
        if (addItemPriceInput) addItemPriceInput.value = price ? String(price) : '';
        if (addItemDescriptionInput) addItemDescriptionInput.value = description;
        if (addItemTaxRateInput) addItemTaxRateInput.value = String(taxRate || 0);
        toggleAddItemUsersField();
    });

    addItemFrequencyInput?.addEventListener('change', function() {
        toggleAddItemRecurringFields();
        if (addItemEndInput) {
            addItemEndInput.value = calculateEndDate(addItemStartInput?.value || '', addItemFrequencyInput?.value || '', addItemDurationInput?.value || '');
        }
    });

    toggleAddItemFormBtn?.addEventListener('click', function() {
        toggleAddItemForm();
    });

    [addItemStartInput, addItemDurationInput].forEach((input) => {
        input?.addEventListener('change', function() {
            if (addItemEndInput) {
                addItemEndInput.value = calculateEndDate(addItemStartInput?.value || '', addItemFrequencyInput?.value || '', addItemDurationInput?.value || '');
            }
        });
    });

    btnAddItemStep3?.addEventListener('click', function() {
        const selectedOption = addItemSelect?.options[addItemSelect.selectedIndex];
        const itemid = addItemSelect?.value || '';
        if (!itemid || !selectedOption) {
            alert('Please select an item.');
            return;
        }

        const itemName = (selectedOption.dataset.name || selectedOption.text || '').split('(')[0].trim();
        const itemDescription = (addItemDescriptionInput?.value || '').trim();
        const quantity = Math.max(1, Math.round(Number(addItemQuantityInput?.value || 1)));
        const unitPrice = Math.max(0, Number(addItemPriceInput?.value || 0));
        const discountPercent = Math.min(100, Math.max(0, Number(addItemDiscountInput?.value || 0)));
        const taxRate = Math.max(0, Number(addItemTaxRateInput?.value || 0));
        const frequency = addItemFrequencyInput?.value || '';
        const duration = itemHasRecurringFrequency({ frequency }) ? (Number(addItemDurationInput?.value || 0) || null) : null;
        const startDate = itemHasRecurringFrequency({ frequency }) ? (addItemStartInput?.value || null) : null;
        const endDate = itemHasRecurringFrequency({ frequency })
            ? (addItemEndInput?.value || calculateEndDate(startDate, frequency, duration) || null)
            : null;
        const requiresUserFields = accountHasUsers && isAddItemUserWise();
        const noOfUsers = requiresUserFields ? Math.max(1, Number(addItemUsersInput?.value || 1)) : null;

        const newItem = normalizeItem({
            itemid,
            item_name: itemName || 'Custom Item',
            item_description: itemDescription,
            quantity,
            unit_price: unitPrice,
            tax_rate: taxRate,
            discount_percent: discountPercent,
            frequency,
            duration,
            start_date: startDate,
            end_date: endDate,
            no_of_users: noOfUsers,
            requires_user_fields: requiresUserFields,
        });

        normalizeItemAmounts(newItem);
        invoiceItems.push(newItem);
        renderItems();
        resetAddItemForm();
    });

    btnNextToStep4.addEventListener('click', function() {
        const invoiceTitle = invoiceTitleInput.value;
        if (!invoiceTitle.trim()) {
            invoiceTitleError.style.display = 'block';
            invoiceTitleInput.focus();
            return;
        }

        invoiceTitleError.style.display = 'none';

        if (invoiceItems.length === 0) {
            alert('No items to invoice.');
            return;
        }

        fetch("{{ route('invoices.save-draft') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                clientid: clientId,
                invoice_for: invoiceFor,
                orderid: hasOrderId ? orderId : null,
                invoice_title: invoiceTitle,
                items_data: itemsDataInput.value
            })
        })
        .then(async (response) => {
            if (!response.ok) {
                const text = await response.text();
                throw new Error(text || 'Failed to save draft.');
            }
            const contentType = response.headers.get('content-type') || '';
            return contentType.includes('application/json') ? response.json() : {};
        })
        .then((data) => {
            if (data && data.invoice_number && piNumberBadgeStep3) {
                piNumberBadgeStep3.textContent = data.invoice_number;
            }
            const clientToken = encodeURIComponent(clientId);
            let nextUrl = "{{ route('invoices.create') }}?step=4&invoice_for=" + encodeURIComponent(invoiceFor) + "&c=" + clientToken;
            if (hasOrderId) {
                const orderToken = encodeURIComponent(orderId);
                nextUrl += "&o=" + orderToken;
            }
            if (data && data.proformaid) {
                nextUrl += "&d=" + encodeURIComponent(data.proformaid);
            }
            window.location.href = nextUrl;
        })
        .catch((error) => {
            console.error('Error saving draft:', error);
            alert('Unable to save draft right now. Please try again.');
        });
    });

    btnBackToStep2.addEventListener('click', function() {
        const clientToken = encodeURIComponent(clientId);
        let backUrl = "{{ route('invoices.create') }}?step=2&invoice_for=" + encodeURIComponent(invoiceFor) + "&c=" + clientToken;
        if (hasOrderId) {
            const orderToken = encodeURIComponent(orderId);
            backUrl += "&o=" + orderToken;
        }
        window.location.href = backUrl;
    });

    invoiceTitleInput.addEventListener('input', function() {
        if (this.value.trim()) {
            invoiceTitleError.style.display = 'none';
        }
    });

    toggleAddItemUsersField();
    toggleAddItemRecurringFields();
    loadItems();
})();
</script>

<style>
#addItemFormCard .form-input {
    padding: 0.45rem 0.6rem;
    font-size: 0.82rem;
}

#addItemFormCard .field-label.small {
    font-size: 0.7rem;
    margin-bottom: 0.25rem;
}

#addItemFormCard textarea.form-input {
    padding: 0.4rem 0.55rem;
    font-size: 0.8rem;
}

#toggleAddItemFormBtn:hover {
    background: #f8faff !important;
    border-color: #c7d2fe !important;
}

#toggleAddItemFormBtn:active {
    transform: scale(0.98);
}
</style>
