@php
    $normalizeTaxState = static fn ($value) => preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $value)));
    $serviceGroups = collect($services ?? [])->groupBy(function ($service) {
        return optional($service->category)->name ?? 'No Category';
    });
    $selectedInvoiceClient = $clients->firstWhere('clientid', request('c', request('clientid')));
    $selectedClientCurrency = optional($selectedInvoiceClient)->currency ?? 'INR';
    $selectedClientName = $selectedInvoiceClient ? ($selectedInvoiceClient->business_name ?? $selectedInvoiceClient->contact_name ?? 'Unknown Client') : 'No Client Selected';
    $selectedClientEmail = optional($selectedInvoiceClient)->email ?? '';
    $invoiceClientState = $normalizeTaxState(optional($selectedInvoiceClient)->state ?? '');
    $invoiceAccountState = $normalizeTaxState(optional($account)->state ?? '');
    $sameStateGstForInvoice = $invoiceClientState !== '' && $invoiceAccountState !== '' && $invoiceClientState === $invoiceAccountState;
    $isTaxInvoiceStep3 = (request('tax_invoice', 0) == 1) || !empty($invoice?->ti_number);
    $initialHeaderNumberStep3 = $isTaxInvoiceStep3
        ? ($invoice?->ti_number ?: ($nextTaxInvoiceNumber ?? $nextInvoiceNumber))
        : ($invoice?->pi_number ?: $nextInvoiceNumber);
@endphp
<!-- Step 3: Edit Items (For Orders & Renewal) -->
<div id="step3" class="invoice-step">
    {{-- Client Info Header with Back Button --}}
    <div class="invoice-client-header invoice-client-header--compact">
        <div class="invoice-client-header__row">
            <button type="button" id="btnBackToStep2" class="secondary-button invoice-back-btn invoice-back-btn--compact">
                <i class="fas fa-arrow-left" class="text-sm"></i>
            </button>
            <div class="invoice-client-header__divider"></div>
            <div class="invoice-client-header__icon">
                <i class="fas fa-user"></i>
            </div>
            <div class="invoice-client-header__body">
                <div class="invoice-client-header__name invoice-client-header__name--compact">{{ $selectedClientName }}</div>
                @if($selectedClientEmail)
                <div class="invoice-client-header__email invoice-client-header__email--compact">{{ $selectedClientEmail }}</div>
                @endif
            </div>
            <div class="invoice-client-header__right">
                <div id="piNumberBadgeStep3" class="invoice-number-badge invoice-number-badge--sm">
                    {{ $initialHeaderNumberStep3 }}
                </div>
            </div>
        </div>
    </div>

    <div class="invoice-grid-4 mb-3">
        <div class="d-flex flex-column">
            <label for="invoice_title" class="field-label">Invoice Title</label>
            <input type="text" id="invoice_title" name="invoice_title" class="form-input invoice-input-compact" placeholder="e.g. Website Development - Monthly Subscription" required>
            <div id="invoiceTitleError" class="invoice-field-error is-hidden">Invoice title is required.</div>
        </div>

        <div class="d-flex flex-column">
            <label for="issue_date" class="field-label">Issue Date</label>
            <input type="date" id="issue_date" name="issue_date" class="form-input invoice-input-compact" required value="{{ old('issue_date', $invoice?->issue_date?->format('Y-m-d') ?? date('Y-m-d')) }}">
        </div>

        <div class="d-flex flex-column">
            <label for="due_date" class="field-label">Due Date</label>
            <input type="date" id="due_date" name="due_date" class="form-input invoice-input-compact" required value="{{ old('due_date', $invoice?->due_date?->format('Y-m-d') ?? date('Y-m-d', strtotime('+7 days'))) }}">
        </div>

        <div class="d-flex flex-column">
            <label for="notes" class="field-label">Notes</label>
            <textarea id="notes" name="notes" class="form-input invoice-notes-compact" placeholder="Optional notes">{{ old('notes', $invoice?->notes ?? '') }}</textarea>
        </div>
    </div>

    <input type="hidden" name="clientid" value="{{ request('c', request('clientid')) }}">
    <input type="hidden" name="orderid" value="{{ request('o', request('orderid', '')) === '0' ? '' : request('o', request('orderid', '')) }}">
    <input type="hidden" name="items_data" id="items_data" value="">
    <input type="hidden" name="currency_code" id="currency_code" value="{{ $selectedClientCurrency }}">

    <div id="itemsSection" class="workflow-panel">
        <div class="panel-heading-row mb-2">
            <div>
            <h4 class="panel-heading-title panel-heading-title--sm">
                    @if(request('o', request('orderid')))
                        Edit Items from Order
                    @else
                        Edit Invoice Items
                    @endif
                </h4>
                <p class="panel-heading-subtitle panel-heading-subtitle--sm">Adjust quantity, pricing, tax, and other details before proceeding.</p>
            </div>
        </div>

        @if(request('o', request('orderid')))
        <div id="orderSummaryInline" class="invoice-order-summary is-hidden">
            <div class="invoice-order-summary__row">
                <div class="invoice-order-summary__main">
                    <div class="invoice-order-summary__eyebrow">Source Order</div>
                    <div id="orderSummaryTitle" class="invoice-order-summary__title">Source Order Details</div>
                </div>
                <div id="orderSummaryDetails" class="invoice-order-summary__badges"></div>
            </div>
        </div>
        <div class="d-flex justify-content-end mb-3">
            <button type="button" id="toggleAddItemFormBtn" class="text-link invoice-add-item-btn is-hidden">
                <i class="fas fa-plus invoice-add-item-btn__icon"></i>
                <span class="invoice-add-item-btn__text">Add More Items</span>
            </button>
        </div>
        @else
        <div class="d-flex justify-content-end mb-3">
            <button type="button" id="toggleAddItemFormBtn" class="text-link invoice-add-item-btn is-hidden">
                <i class="fas fa-plus invoice-add-item-btn__icon"></i>
                <span class="invoice-add-item-btn__text">Add More Items</span>
            </button>
        </div>
        @endif

        <div class="builder-card invoice-builder-card is-hidden" id="addItemFormCard">
            <div class="manual-grid manual-grid-step3">
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
                <div id="add_item_users_wrap" class="is-hidden">
                    <label for="add_item_users" class="field-label small">Users</label>
                    <input type="number" id="add_item_users" class="form-input" value="1" min="1" step="1">
                </div>
                <div>
                    <label for="add_item_frequency" class="field-label small">Freq</label>
                    <select id="add_item_frequency" class="form-input">
                        <option value="">None</option>
                        <option value="One-Time">One-Time</option>
                        <option value="Day(s)">Day(s)</option>
                        <option value="Week(s)">Week(s)</option>
                        <option value="Month(s)">Month(s)</option>
                        <option value="Quarter(s)">Quarter(s)</option>
                        <option value="Year(s)">Year(s)</option>
                    </select>
                </div>
                <div id="add_item_duration_wrap" class="is-hidden">
                    <label for="add_item_duration" class="field-label small">Dur</label>
                    <input type="number" id="add_item_duration" class="form-input" min="0" step="1">
                </div>
                <div id="add_item_start_wrap" class="is-hidden">
                    <label for="add_item_start_date" class="field-label small">Start</label>
                    <input type="date" id="add_item_start_date" class="form-input">
                </div>
                <div id="add_item_end_wrap" class="is-hidden">
                    <label for="add_item_end_date" class="field-label small">End</label>
                    <input type="date" id="add_item_end_date" class="form-input">
                </div>
            </div>
            <div class="invoice-item-desc-row">
                <textarea id="add_item_description" class="form-input invoice-item-desc-input" rows="1" placeholder="Description (optional)"></textarea>
                <button type="button" id="btnAddItemStep3" class="primary-button invoice-item-add-btn invoice-item-add-btn--sm">Add</button>
            </div>
        </div>

        <div class="table-shell">
            <table class="data-table m-0 invoice-items-table invoice-items-table--sm" id="itemsTable">
                <thead id="itemsTableHead">
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price ({{ $selectedClientCurrency }})</th>
                        <th id="itemsUsersHeader" class="is-hidden text-center">Users</th>
                        <th>Disc %</th>
                        @if($account->allow_multi_taxation)
                        <th>Tax %</th>
                        @endif
                        <th>Freq</th>
                        <th id="itemsDurationHeader">Dur</th>
                        <th id="itemsStartHeader" class="is-hidden">Start Date</th>
                        <th id="itemsEndHeader" class="is-hidden">End Date</th>
                        <th>Total ({{ $selectedClientCurrency }})</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end mt-3">
            <div class="totals-card totals-card--wide">
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

    <div class="mt-3">
        <button type="button" class="primary-button w-100 invoice-continue-btn" id="btnNextToStep4">Review & Terms &rarr;</button>
    </div>
</div>

<script>
(function() {
    const clientId = "{{ request('c', request('clientid', $invoice?->clientid ?? '')) }}";
    const invoiceFor = "{{ request('invoice_for', $invoice?->invoice_for ?? '') }}";
    const orderId = "{{ request('o', request('orderid', '')) }}";
    const draftId = "{{ request('d', '') }}";
    const isTaxInvoice = @json($isTaxInvoiceStep3);
    const hasOrderId = orderId && orderId !== '0';
    const sameStateGstForInvoice = @json($sameStateGstForInvoice);
    const itemsBody = document.getElementById('itemsBody');
    const currencyCodeInput = document.getElementById('currency_code');
    const btnNextToStep4 = document.getElementById('btnNextToStep4');
    const btnBackToStep2 = document.getElementById('btnBackToStep2');
    const itemsDataInput = document.getElementById('items_data');
    const invoiceTitleInput = document.getElementById('invoice_title');
    const invoiceTitleError = document.getElementById('invoiceTitleError');
    const piNumberBadgeStep3 = document.getElementById('piNumberBadgeStep3');
    const issueDateInput = document.getElementById('issue_date');
    const dueDateInput = document.getElementById('due_date');
    const notesInput = document.getElementById('notes');
    let currentInvoiceId = draftId || '';
    let draftPiNumber = "{{ $invoice?->pi_number ?? '' }}";
    let draftTiNumber = "{{ $invoice?->ti_number ?? '' }}";
    const fallbackPiNumber = "{{ $nextInvoiceNumber }}";
    const fallbackTiNumber = "{{ $nextTaxInvoiceNumber ?? $nextInvoiceNumber }}";

    function getStep3HeaderNumber() {
        if (draftTiNumber) {
            return draftTiNumber;
        }
        if (draftTiNumber || isTaxInvoice) {
            return draftTiNumber || fallbackTiNumber;
        }
        return draftPiNumber || fallbackPiNumber;
    }

    function updateStep3HeaderNumber() {
        if (piNumberBadgeStep3) {
            piNumberBadgeStep3.textContent = getStep3HeaderNumber();
        }
    }
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

    const frequencyLabels = {
        'One-Time': 'One-Time',
        'Day(s)': 'Day(s)',
        'Week(s)': 'Week(s)',
        'Month(s)': 'Month(s)',
        'Quarter(s)': 'Quarter(s)',
        'Year(s)': 'Year(s)',
    };

    let invoiceItems = [];
    let editingInvoiceItemIndex = null;
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

    function renderItemCell(item) {
        const name = escapeHtml(item.item_name || 'Item');
        const description = escapeHtml(item.item_description || '').trim();
        if (!description) {
            return `<div class="invoice-item-cell-title">${name}</div>`;
        }
        return `
            <div class="invoice-item-cell-title">${name}</div>
            <div class="invoice-item-cell-desc">${description}</div>
        `;
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
        return Boolean(item && item.requires_user_fields);
    }

    function itemHasRecurringFrequency(item) {
        return Boolean(item && item.frequency) && item.frequency !== 'One-Time';
    }

    function isAddItemUserWise() {
        const selectedOption = addItemSelect?.options[addItemSelect.selectedIndex];
        return String(selectedOption?.dataset?.userWise ?? '0') === '1';
    }

    function toggleAddItemUsersField() {
        if (!addItemUsersWrap || !addItemUsersInput) return;
        const show = isAddItemUserWise();
        addItemUsersWrap.classList.toggle('is-hidden', !show);
        if (!show) addItemUsersInput.value = 1;
    }

    function toggleAddItemRecurringFields() {
        const showRecurring = Boolean(addItemFrequencyInput?.value) && addItemFrequencyInput.value !== 'One-Time';
        if (addItemDurationWrap) addItemDurationWrap.classList.toggle('is-hidden', !showRecurring);
        if (addItemStartWrap) addItemStartWrap.classList.toggle('is-hidden', !showRecurring);
        if (addItemEndWrap) addItemEndWrap.classList.toggle('is-hidden', !showRecurring);
        if (showRecurring) {
            const durationValue = Number(addItemDurationInput?.value || 0);
            if (addItemDurationInput && (!addItemDurationInput.value || durationValue <= 0)) {
                addItemDurationInput.value = '1';
            }
        } else {
            if (addItemDurationInput) addItemDurationInput.value = '';
            if (addItemStartInput) addItemStartInput.value = '';
            if (addItemEndInput) addItemEndInput.value = '';
        }
    }

    function calculateEndDate(startDate, frequency, duration) {
        if (!startDate || !frequency || frequency === 'One-Time' || !duration) {
            return '';
        }

        const parts = String(startDate).split('-');
        const date = new Date(parts[0], (parts[1] || 1) - 1, parts[2] || 1);
        const steps = Math.max(0, Number(duration) || 0);

        if (steps <= 0 || Number.isNaN(date.getTime())) {
            return '';
        }

        switch (frequency) {
            case 'Day(s)':
                date.setDate(date.getDate() + steps);
                break;
            case 'Week(s)':
                date.setDate(date.getDate() + (steps * 7));
                break;
            case 'Month(s)':
                date.setMonth(date.getMonth() + steps);
                break;
            case 'Quarter(s)':
                date.setMonth(date.getMonth() + (steps * 3));
                break;
            case 'Year(s)':
                date.setFullYear(date.getFullYear() + steps);
                break;
            default:
                return '';
        }

        // End date is inclusive across billing cycles.
        date.setDate(date.getDate() - 1);
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function setDateInputValue(input, value) {
        if (!input) return;
        const normalized = value || '';
        if (input.value === normalized) return;
        input.value = normalized;
    }

    function syncAddItemEndDateFromInputs() {
        if (!addItemEndInput) return;
        const nextValue = calculateEndDate(
            addItemStartInput?.value || '',
            addItemFrequencyInput?.value || '',
            addItemDurationInput?.value || ''
        );
        setDateInputValue(addItemEndInput, nextValue);
    }

    function scheduleAddItemEndDateSync() {
        window.requestAnimationFrame(syncAddItemEndDateFromInputs);
    }

    function normalizeItem(item) {
        const requiresUserFields = (
            Boolean(item?.requires_user_fields) ||
            Number(item?.no_of_users || 0) > 0
        );

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
            requires_user_fields: requiresUserFields,
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
        editingInvoiceItemIndex = null;
        if (addItemSelect) addItemSelect.value = '';
        if (addItemQuantityInput) addItemQuantityInput.value = '1';
        if (addItemPriceInput) addItemPriceInput.value = '';
        if (addItemDescriptionInput) addItemDescriptionInput.value = '';
        if (addItemDiscountInput) addItemDiscountInput.value = '0';
        if (addItemFrequencyInput) addItemFrequencyInput.value = '';
        if (addItemDurationInput) addItemDurationInput.value = '';
        if (addItemStartInput) addItemStartInput.value = '';
        if (addItemEndInput) addItemEndInput.value = '';
        if (btnAddItemStep3) btnAddItemStep3.textContent = 'Add';
        toggleAddItemUsersField();
        toggleAddItemRecurringFields();
    }

    function beginEditItem(index) {
        const item = invoiceItems[index];
        if (!item) return;

        editingInvoiceItemIndex = index;

        if (addItemSelect) addItemSelect.value = item.itemid || '';
        if (addItemQuantityInput) addItemQuantityInput.value = String(Math.max(1, Math.round(Number(item.quantity || 1))));
        if (addItemPriceInput) addItemPriceInput.value = String(Number(item.unit_price || 0));
        if (addItemDescriptionInput) addItemDescriptionInput.value = item.item_description || '';
        if (addItemDiscountInput) addItemDiscountInput.value = String(Number(item.discount_percent || 0));
        if (addItemTaxRateInput) addItemTaxRateInput.value = String(Number(item.tax_rate || 0));
        if (addItemFrequencyInput) addItemFrequencyInput.value = item.frequency || '';
        if (addItemDurationInput) addItemDurationInput.value = item.duration ?? '';
        if (addItemStartInput) addItemStartInput.value = item.start_date || '';
        if (addItemEndInput) addItemEndInput.value = item.end_date || '';
        if (addItemUsersInput) addItemUsersInput.value = String(Math.max(1, Number(item.no_of_users || 1)));

        toggleAddItemUsersField();
        if (itemSupportsUserFields(item) && addItemUsersWrap) {
            addItemUsersWrap.classList.remove('is-hidden');
        }
        toggleAddItemRecurringFields();

        if (!addItemFormVisible) {
            addItemFormVisible = true;
            if (addItemFormCard) addItemFormCard.classList.remove('is-hidden');
            if (toggleAddItemFormBtn) {
                toggleAddItemFormBtn.innerHTML = '<i class="fas fa-times invoice-add-item-btn__icon"></i><span class="invoice-add-item-btn__text">Cancel</span>';
                toggleAddItemFormBtn.classList.add('is-danger');
            }
        }

        if (btnAddItemStep3) btnAddItemStep3.textContent = 'Update';
        btnAddItemStep3?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function toggleAddItemForm() {
        addItemFormVisible = !addItemFormVisible;
        if (addItemFormCard) {
            addItemFormCard.classList.toggle('is-hidden', !addItemFormVisible);
        }
        if (toggleAddItemFormBtn) {
            if (addItemFormVisible) {
                toggleAddItemFormBtn.innerHTML = '<i class="fas fa-times invoice-add-item-btn__icon"></i><span class="invoice-add-item-btn__text">Cancel</span>';
                toggleAddItemFormBtn.classList.add('is-danger');
            } else {
                toggleAddItemFormBtn.innerHTML = '<i class="fas fa-plus invoice-add-item-btn__icon"></i><span class="invoice-add-item-btn__text">Add More Items</span>';
                toggleAddItemFormBtn.classList.remove('is-danger');
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
                toggleAddItemFormBtn.classList.remove('is-hidden');
            }
            // Hide the form by default
            if (addItemFormCard) {
                addItemFormCard.classList.add('is-hidden');
            }
            addItemFormVisible = false;
        } else {
            // For without_orders, always show the form and hide the button
            if (toggleAddItemFormBtn) {
                toggleAddItemFormBtn.classList.add('is-hidden');
            }
            if (addItemFormCard) {
                addItemFormCard.classList.remove('is-hidden');
            }
            addItemFormVisible = true;
        }
    }

    function calculateLineInputTotal(item) {
        const qty = Math.max(1, Math.round(Number(item.quantity || 1)));
        const price = Number(item.unit_price || 0);
        const users = itemSupportsUserFields(item) ? Math.max(1, Number(item.no_of_users || 1)) : 1;
        const durationMultiplier = (item.frequency && item.frequency !== 'One-Time' && Number(item.duration || 0) > 0)
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

        const usersHeader = document.getElementById('itemsUsersHeader');
        const durationHeader = document.getElementById('itemsDurationHeader');
        const startHeader = document.getElementById('itemsStartHeader');
        const endHeader = document.getElementById('itemsEndHeader');

        if (usersHeader) usersHeader.classList.toggle('is-hidden', !showUserColumns);
        if (durationHeader) durationHeader.classList.toggle('is-hidden', !showDurationColumns);
        if (startHeader) startHeader.classList.toggle('is-hidden', !showDateColumns);
        if (endHeader) endHeader.classList.toggle('is-hidden', !showDateColumns);
    }

    function renderOrderSummary(order) {
        const summaryInline = document.getElementById('orderSummaryInline');
        if (!summaryInline || !order) {
            return;
        }

        summaryInline.classList.remove('is-hidden');
        currencyCodeInput.value = order.currency || getCurrencyCode();
        document.getElementById('orderSummaryTitle').innerHTML = `<strong>${order.order_title || 'Untitled Order'}</strong>`;
        document.getElementById('orderSummaryDetails').innerHTML =
            `<span class="invoice-step-badge is-primary">#${order.order_number}</span>` +
            `<span class="invoice-step-badge is-muted">${order.order_date}</span>` +
            `<span class="invoice-step-badge is-muted">${order.item_count || 0} item(s)</span>` +
            `<span class="invoice-step-badge is-muted">${Number(order.grand_total).toLocaleString('en-US', {minimumFractionDigits: 0})}</span>`;
    }

    function loadItems() {
        const draftUrl = new URL("{{ route('invoices.get-draft', ['clientid' => '__CLIENTID__']) }}".replace('__CLIENTID__', clientId), window.location.origin);
        if (invoiceFor) {
            draftUrl.searchParams.set('invoice_for', invoiceFor);
        }
        if (hasOrderId) {
            draftUrl.searchParams.set('o', orderId);
        }
        if (draftId) {
            draftUrl.searchParams.set('d', draftId);
        }

        fetch(draftUrl.toString())
            .then(response => response.json())
            .then(data => {
                const draftItems = Array.isArray(data && data.draft && data.draft.items) ? data.draft.items : [];
                const draftTitle = data && data.draft ? (data.draft.invoice_title || '') : '';

                if (draftTitle) {
                    invoiceTitleInput.value = draftTitle;
                }
                if (data && data.draft && data.draft.issue_date && issueDateInput) {
                    issueDateInput.value = data.draft.issue_date;
                }
                if (data && data.draft && data.draft.due_date && dueDateInput) {
                    dueDateInput.value = data.draft.due_date;
                }
                if (data && data.draft && data.draft.notes && notesInput) {
                    notesInput.value = data.draft.notes;
                }
                draftPiNumber = data && data.draft ? (data.draft.pi_number || '') : '';
                draftTiNumber = data && data.draft ? (data.draft.ti_number || '') : '';
                currentInvoiceId = (data && data.draft && data.draft.invoiceid) ? data.draft.invoiceid : currentInvoiceId;
                updateStep3HeaderNumber();
                updateStep3HeaderNumber();

                if (draftItems.length > 0) {
                    invoiceItems = draftItems.map(normalizeItem);
                    renderItems();
                    initializeAddItemFormVisibility();
                } else if (invoiceFor === 'orders' && orderId) {
                    loadOrderItems(orderId);
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
        const showTaxColumn = @json((bool) ($account->allow_multi_taxation ?? false));

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
            const formattedFrequency = item.frequency ? (frequencyLabels[item.frequency] || item.frequency) : '-';
            const lineAmount = Math.max(0, Number(item.line_total || 0) - Number(item.discount_amount || 0) + Number(item.tax_amount || 0));
            row.innerHTML = `
                <td>${renderItemCell(item)}</td>
                <td class="text-center">${Math.round(Number(item.quantity || 1))}</td>
                <td class="text-center">${formatCurrency(item.unit_price)}</td>
                <td class="text-center ${showUserColumns ? '' : 'is-hidden'}">
                    ${showUsersForRow
                        ? `${Math.max(1, Number(item.no_of_users || 1))}`
                        : '<span class="text-muted">-</span>'}
                </td>
                <td class="text-center">${Number(item.discount_percent || 0).toFixed(0)}%</td>
                @if($account->allow_multi_taxation)
                <td class="text-center">${Number(item.tax_rate || 0).toFixed(0)}%</td>
                @endif
                <td>${formattedFrequency}</td>
                <td class="${showDurationColumns ? '' : 'is-hidden'}">
                    ${showDatesForRow
                        ? `${item.duration || '-'}`
                        : '<span class="text-muted">-</span>'}
                </td>
                <td class="${showDateColumns ? '' : 'is-hidden'}">
                    ${showDatesForRow
                        ? `${item.start_date || '-'}`
                        : '<span class="text-muted">-</span>'}
                </td>
                <td class="${showDateColumns ? '' : 'is-hidden'}">
                    ${showDatesForRow
                        ? `${item.end_date || '-'}`
                        : '<span class="text-muted">-</span>'}
                </td>
                <td class="text-center"><strong>${formatCurrency(lineAmount)}</strong></td>
                <td class="text-center text-nowrap">
                    <button type="button" class="edit-item-btn icon-action-btn edit" data-index="${index}" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="remove-item-btn icon-action-btn delete" data-index="${index}" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            itemsBody.appendChild(row);
        });

        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const removeIndex = parseInt(this.dataset.index, 10);
                invoiceItems.splice(removeIndex, 1);
                if (editingInvoiceItemIndex !== null) {
                    if (editingInvoiceItemIndex === removeIndex) {
                        resetAddItemForm();
                    } else if (removeIndex < editingInvoiceItemIndex) {
                        editingInvoiceItemIndex -= 1;
                    }
                }
                renderItems();
            });
        });

        document.querySelectorAll('.edit-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index, 10);
                beginEditItem(index);
            });
        });

        const roundedDiscountTotal = roundDiscountDown(discountTotal);
        const roundedTaxTotal = roundTaxUp(taxTotal);

        document.getElementById('subtotalDisplay').textContent = formatCurrency(subtotal);
        document.getElementById('discountDisplay').textContent = formatCurrency(roundedDiscountTotal);
        updateTaxDisplay(roundedTaxTotal);
        document.getElementById('grandTotalDisplay').textContent = formatCurrency(subtotal - roundedDiscountTotal + roundedTaxTotal);

        itemsDataInput.value = JSON.stringify(invoiceItems);
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
        scheduleAddItemEndDateSync();
    });

    toggleAddItemFormBtn?.addEventListener('click', function() {
        toggleAddItemForm();
    });

    [addItemStartInput, addItemDurationInput, addItemFrequencyInput].forEach((input) => {
        input?.addEventListener('change', scheduleAddItemEndDateSync);
        input?.addEventListener('input', scheduleAddItemEndDateSync);
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
        const duration = itemHasRecurringFrequency({ frequency }) ? Math.max(1, Number(addItemDurationInput?.value || 1)) : null;
        const startDate = itemHasRecurringFrequency({ frequency }) ? (addItemStartInput?.value || null) : null;
        const endDate = itemHasRecurringFrequency({ frequency })
            ? (addItemEndInput?.value || calculateEndDate(startDate, frequency, duration) || null)
            : null;
        const requiresUserFields = isAddItemUserWise();
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
        if (editingInvoiceItemIndex !== null && invoiceItems[editingInvoiceItemIndex]) {
            invoiceItems[editingInvoiceItemIndex] = newItem;
        } else {
            invoiceItems.push(newItem);
        }
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
                invoiceid: currentInvoiceId || undefined,
                invoice_for: invoiceFor,
                clientid: clientId,
                orderid: hasOrderId ? orderId : null,
                invoice_title: invoiceTitle,
                issue_date: document.getElementById('issue_date').value,
                due_date: document.getElementById('due_date').value,
                notes: document.getElementById('notes').value,
                items_data: itemsDataInput.value
            })
        })
        .then(async (response) => {
            if (!response.ok) {
                const text = await response.text();
                throw new Error(text || 'Failed to update invoice.');
            }
            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                const json = await response.json();
                if (!response.ok) {
                    const message =
                        json?.message ||
                        (json?.errors ? Object.values(json.errors).flat().join('\n') : '') ||
                        'Failed to update invoice.';
                    throw new Error(message);
                }
                return json;
            }
            const text = await response.text();
            if (!response.ok) {
                throw new Error(text || 'Failed to update invoice.');
            }
            return {};
        })
        .then((data) => {
            if (data && data.invoiceid) {
                currentInvoiceId = data.invoiceid;
            }
            if (data && typeof data === 'object') {
                draftPiNumber = data.pi_number || draftPiNumber;
                draftTiNumber = data.ti_number || draftTiNumber;
            }
            updateStep3HeaderNumber();
            const clientToken = encodeURIComponent(clientId);
            let nextUrl = "{{ route('invoices.create') }}?step=4&invoice_for=" + encodeURIComponent(invoiceFor) + "&c=" + clientToken;
            if (hasOrderId) {
                const orderToken = encodeURIComponent(orderId);
                nextUrl += "&o=" + orderToken;
            }
            if (isTaxInvoice) {
                nextUrl += "&tax_invoice=1";
            }
            if (data && data.invoiceid) {
                nextUrl += "&d=" + encodeURIComponent(data.invoiceid);
            }
            window.location.href = nextUrl;
        })
        .catch((error) => {
            console.error('Error updating invoice:', error);
            alert(error?.message || 'Unable to update invoice right now. Please try again.');
        });
    });

    btnBackToStep2.addEventListener('click', function() {
        const clientToken = encodeURIComponent(clientId);
        let backUrl = "{{ route('invoices.create') }}?step=2&invoice_for=" + encodeURIComponent(invoiceFor) + "&c=" + clientToken;
        if (hasOrderId) {
            const orderToken = encodeURIComponent(orderId);
            backUrl += "&o=" + orderToken;
        }
        if (isTaxInvoice) {
            backUrl += "&tax_invoice=1";
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
