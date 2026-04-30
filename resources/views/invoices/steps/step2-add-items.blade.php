@php
    $selectedClientCurrency = optional($clients->firstWhere('clientid', request('c', request('clientid'))))->currency ?? 'INR';
    $selectedClient = $clients->firstWhere('clientid', request('c', request('clientid')));
    $selectedClientName = $selectedClient ? ($selectedClient->business_name ?? $selectedClient->contact_name ?? 'Unknown Client') : 'No Client Selected';
    $selectedClientEmail = $selectedClient->email ?? '';
    $isTaxInvoiceStep2 = (request('tax_invoice', 0) == 1) || !empty($invoice?->ti_number);
    $initialHeaderNumberStep2 = $isTaxInvoiceStep2
        ? ($invoice?->ti_number ?: ($nextTaxInvoiceNumber ?? $nextInvoiceNumber))
        : ($invoice?->pi_number ?: $nextInvoiceNumber);
    $serviceGroups = collect($services ?? [])->groupBy(function ($service) {
        return optional($service->category)->name ?? 'No Category';
    });
@endphp
<!-- Step 2: Add Items (Without Orders) -->
<div id="step2" class="invoice-step">
    {{-- Client Info Header with Back Button --}}
    <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <button type="button" id="btnBackToStep1" class="secondary-button" style="padding: 0.4rem 0.65rem; flex-shrink: 0; font-size: 0.85rem;">
                <i class="fas fa-arrow-left" class="text-sm"></i>
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
                <div id="piNumberBadge" style="display: inline-block; padding: 0.35rem 0.75rem; background: #eef2ff; color: #4f46e5; border-radius: 6px; font-size: 0.85rem; font-weight: 700; border: 1px solid #c7d2fe;">
                    {{ $initialHeaderNumberStep2 }}
                </div>
            </div>
        </div>
    </div>

    <div class="invoice-grid-4" style="margin-bottom: 1rem;">
        <div style="overflow: visible;">
            <label for="invoice_title" class="field-label">Invoice Title</label>
            <input type="text" id="invoice_title" name="invoice_title" class="form-input" placeholder="e.g. Website Development - Monthly Subscription" required>
            <div id="invoiceTitleError" style="display:none; margin-top: 0.5rem; color: #b91c1c; font-size: 0.8rem; font-weight: 600;">Invoice title is required.</div>
        </div>
        <div>
            <label for="issue_date" class="field-label">Issue Date</label>
            <input type="date" id="issue_date" name="issue_date" class="form-input" required value="{{ old('issue_date', request('d') && $invoice ? $invoice->issue_date?->format('Y-m-d') : date('Y-m-d')) }}">
        </div>
        <div>
            <label for="due_date" class="field-label">Due Date</label>
            <input type="date" id="due_date" name="due_date" class="form-input" required value="{{ old('due_date', request('d') && $invoice ? $invoice->due_date?->format('Y-m-d') : date('Y-m-d', strtotime('+7 days'))) }}">
        </div>
        <div>
            <label for="notes" class="field-label">Notes</label>
            <textarea id="notes" name="notes" rows="1" class="form-input" style="min-height: 38px; resize: vertical;" placeholder="Optional notes">{{ old('notes', request('d') && $invoice ? $invoice->notes : '') }}</textarea>
        </div>
    </div>

    <input type="hidden" name="clientid" value="{{ request('c', request('clientid')) }}">
    <input type="hidden" name="invoice_number" value="{{ $initialHeaderNumberStep2 }}">
    <input type="hidden" name="items_data" id="items_data" value="">
    <input type="hidden" name="currency_code" id="currency_code" value="{{ $selectedClientCurrency }}">
    <input type="hidden" name="issue_date" id="step2_issue_date" value="{{ date('Y-m-d') }}">
    <input type="hidden" name="due_date" id="step2_due_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}">
    <input type="hidden" name="notes" id="step2_notes" value="">

    <div id="manualItemsSection" class="workflow-panel">
        <div class="panel-heading-row" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
            <div>
                <h4 style="margin: 0; font-size: 1rem; color: #334155;">Add Invoice Items</h4>
                <p style="margin: 0.2rem 0 0 0; color: #64748b; font-size: 0.85rem;">Add items to your invoice.</p>
            </div>
            <div>
                <button type="button" id="toggleAddItemFormBtn" class="text-link" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; font-size: 0.8rem; padding: 0.35rem 0.65rem; border: 1px solid #e5e7eb; border-radius: 6px; background: #ffffff; color: #4f46e5; font-weight: 500; line-height: 1;">
                    <i class="fas fa-plus" style="font-size: 0.75rem; line-height: 1; vertical-align: middle;"></i>
                    <span style="line-height: 1;">Add More Items</span>
                </button>
            </div>
        </div>

        <div class="builder-card" id="addItemFormCard" style="margin-bottom: 0.65rem; padding: 0.6rem; display: none;">
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
                                    <option value="{{ $service->itemid }}" data-selling-price="{{ $defaultCosting?->selling_price ?? 0 }}" data-tax-rate="{{ $defaultCosting?->tax_rate ?? 0 }}" data-user-wise="{{ (int) ($service->user_wise ?? 0) }}" data-description="{{ $service->description ?? '' }}">
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
                            <option value="{{ $tax->rate }}">{{ $tax->tax_name }} ({{ number_format($tax->rate, 0) }}%)</option>
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
                        <option value="One-Time">One-Time</option>
                        <option value="Day(s)">Day(s)</option>
                        <option value="Week(s)">Week(s)</option>
                        <option value="Month(s)">Month(s)</option>
                        <option value="Quarter(s)">Quarter(s)</option>
                        <option value="Year(s)">Year(s)</option>
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
            </div>
            <div style="margin-top: 0.45rem; display: flex; gap: 0.45rem; align-items: flex-end;">
                <textarea id="manual_item_description" class="form-input" rows="1" placeholder="Description (optional)" style="flex: 1 1 auto; min-height: 30px; resize: none; line-height: 1.2;"></textarea>
                <button type="button" id="addManualItemBtn" class="primary-button" style="padding: 0.55rem 1rem; white-space: nowrap;">Add</button>
            </div>
        </div>

        <div class="table-shell" style="margin-top: 1rem;">
            <table class="data-table" id="manualItemsTable" style="display: none; margin: 0; font-size: 0.84rem;">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price ({{ $selectedClientCurrency }})</th>
                        <th>Disc %</th>
                        @if($account->allow_multi_taxation)
                        <th>Tax %</th>
                        @endif
                        @if($account->have_users)
                        <th id="manualUsersHeader" class="hidden">Users</th>
                        @endif
                        <th>Freq</th>
                        <th id="manualDurationHeader" class="hidden">Dur</th>
                        <th id="manualStartHeader" class="hidden">Start</th>
                        <th id="manualEndHeader" class="hidden">End</th>
                        <th>Total ({{ $selectedClientCurrency }})</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="manualItemsBody"></tbody>
            </table>
            <div id="manualItemsEmpty" class="empty-state">No items added yet.</div>
        </div>

        <div id="manualOrderSummary" class="totals-card" style="display: none; margin-top: 1rem; margin-left: auto; max-width: 350px;">
            <div class="total-row"><span>Subtotal</span><strong id="manualSubtotal">0</strong></div>
            <div class="total-row"><span>Discount</span><strong id="manualDiscountTotal">0</strong></div>
            <div class="total-row"><span>Tax</span><strong id="manualTaxTotal">0</strong></div>
            <div class="total-row total-row-grand"><span>Total</span><strong id="manualGrandTotal">0</strong></div>
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
    const toggleAddItemFormBtn = document.getElementById('toggleAddItemFormBtn');
    const addItemFormCard = document.getElementById('addItemFormCard');
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
    const piNumberBadge = document.getElementById('piNumberBadge');
    const isTaxInvoice = @json($isTaxInvoiceStep2);
    const fallbackPiNumber = "{{ $nextInvoiceNumber }}";
    const fallbackTiNumber = "{{ $nextTaxInvoiceNumber ?? $nextInvoiceNumber }}";
    let draftPiNumber = '';
    let draftTiNumber = '';

    function getStep2HeaderNumber() {
        if (draftTiNumber) return draftTiNumber;
        if (isTaxInvoice) return fallbackTiNumber;
        return draftPiNumber || fallbackPiNumber;
    }

    function updateStep2HeaderNumber() {
        if (piNumberBadge) {
            piNumberBadge.textContent = getStep2HeaderNumber();
        }
    }

    const frequencyLabels = { 'One-Time': 'One-Time', 'Day(s)': 'Day(s)', 'Week(s)': 'Week(s)', 'Month(s)': 'Month(s)', 'Quarter(s)': 'Quarter(s)', 'Year(s)': 'Year(s)' };

    let manualItems = [];
    let editingManualItemIndex = null;

    function formatCurrency(amount) {
        return `${Number(amount || 0).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0})}`;
    }

    function roundTaxUp(value) {
        return Math.ceil(Math.max(0, Number(value) || 0));
    }

    function roundDiscountDown(value) {
        return Math.floor(Math.max(0, Number(value) || 0));
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
            return name;
        }
        return `
            <div style="font-weight: 600; color: #111827;">${name}</div>
            <div style="margin-top: 0.15rem; font-size: 0.78rem; color: #6b7280; white-space: pre-wrap;">${description}</div>
        `;
    }

    // Auto-fill price when item selected
    document.getElementById('manual_item_itemid').addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const price = selected.dataset.sellingPrice || 0;
        const taxRate = selected.dataset.taxRate || 0;
        document.getElementById('manual_item_unit_price').value = price;
        document.getElementById('manual_item_description').value = selected.dataset.description || '';
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
        return Boolean(frequency) && frequency !== 'One-Time';
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
            case 'Day(s)': end.setDate(end.getDate() + steps); break;
            case 'Week(s)': end.setDate(end.getDate() + (steps * 7)); break;
            case 'Month(s)': end.setMonth(end.getMonth() + steps); break;
            case 'Quarter(s)': end.setMonth(end.getMonth() + (steps * 3)); break;
            case 'Year(s)': end.setFullYear(end.getFullYear() + steps); break;
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
        if (showRecurring) {
            const durationValue = Number(manualDurationInput.value || 0);
            if (!manualDurationInput.value || durationValue <= 0) {
                manualDurationInput.value = '1';
            }
        } else {
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

    function resetManualItemForm() {
        editingManualItemIndex = null;
        addManualItemBtn.textContent = 'Add';
        document.getElementById('manual_item_itemid').value = '';
        document.getElementById('manual_item_quantity').value = '1';
        document.getElementById('manual_item_unit_price').value = '';
        document.getElementById('manual_item_discount').value = '0';
        document.getElementById('manual_item_frequency').value = '';
        document.getElementById('manual_item_duration').value = '';
        document.getElementById('manual_item_description').value = '';
        if (manualStartInput) manualStartInput.value = '';
        if (manualEndInput) manualEndInput.value = '';
        toggleManualRecurringFields();
        @if($account->have_users)
        toggleManualUsersField();
        @endif
    }

    function openAddItemForm() {
        if (addItemFormCard) {
            addItemFormCard.style.display = 'block';
        }
        if (toggleAddItemFormBtn) {
            toggleAddItemFormBtn.innerHTML = '<i class="fas fa-times" style="font-size: 0.75rem; line-height: 1; vertical-align: middle;"></i><span style="line-height: 1;">Cancel</span>';
        }
    }

    addManualItemBtn.addEventListener('click', function() {
        const itemId = document.getElementById('manual_item_itemid').value;
        const itemName = document.getElementById('manual_item_itemid').options[document.getElementById('manual_item_itemid').selectedIndex]?.text || '';
        const itemDescription = (document.getElementById('manual_item_description').value || '').trim();
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
            ? Math.max(1, parseInt(document.getElementById('manual_item_duration').value) || 1)
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
            item_description: itemDescription,
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

        if (editingManualItemIndex !== null && manualItems[editingManualItemIndex]) {
            manualItems[editingManualItemIndex] = newItem;
        } else {
            manualItems.push(newItem);
        }
        renderManualItems();

        resetManualItemForm();
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
        const showTaxColumn = @json((bool) ($account->allow_multi_taxation ?? false));

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
            const safeName = escapeHtml(item.item_name || 'Item');
            const safeDescription = escapeHtml(item.item_description || '').trim();

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${renderItemCell(item)}</td>
                <td style="text-align: center;">${Math.round(Number(item.quantity) || 0)}</td>
                <td class="text-right">${formatCurrency(item.unit_price)}</td>
                <td style="text-align: center;">${Number(item.discount_percent || 0).toFixed(0)}%</td>
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
                <td class="text-right">${formatCurrency(Math.max(0, Number(item.line_total || 0) - Number(item.discount_amount || 0)))}</td>
                <td style="text-align: center; white-space: nowrap;">
                    <button type="button" class="edit-item-btn icon-action-btn edit" data-index="${index}" title="Edit" style="padding: 0.15rem 0.3rem; font-size: 0.7rem; margin-right: 0.2rem;">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="remove-item-btn icon-action-btn delete" data-index="${index}" title="Delete" style="padding: 0.15rem 0.3rem; font-size: 0.7rem;">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            manualItemsBody.appendChild(row);
        });

        document.querySelectorAll('.edit-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = Number(this.dataset.index);
                const item = manualItems[index];
                if (!item) return;

                openAddItemForm();
                editingManualItemIndex = index;
                addManualItemBtn.textContent = 'Update';

                const itemSelect = document.getElementById('manual_item_itemid');
                itemSelect.value = item.itemid || '';
                if (itemSelect.value) {
                    itemSelect.dispatchEvent(new Event('change'));
                }

                document.getElementById('manual_item_quantity').value = Math.max(1, Math.round(Number(item.quantity || 1)));
                document.getElementById('manual_item_unit_price').value = Number(item.unit_price || 0);
                document.getElementById('manual_item_description').value = item.item_description || '';
                document.getElementById('manual_item_discount').value = Number(item.discount_percent || 0);
                document.getElementById('manual_item_tax_rate').value = Number(item.tax_rate || 0);
                document.getElementById('manual_item_frequency').value = item.frequency || '';
                document.getElementById('manual_item_duration').value = item.duration || '';
                if (manualStartInput) manualStartInput.value = item.start_date || '';
                if (manualEndInput) manualEndInput.value = item.end_date || '';

                @if($account->have_users)
                toggleManualUsersField();
                if (item.no_of_users) {
                    document.getElementById('manual_item_users').value = Math.max(1, Number(item.no_of_users || 1));
                }
                @endif

                toggleManualRecurringFields();
                if (isRecurringFrequency(item.frequency || '') && manualEndInput && !manualEndInput.value) {
                    manualEndInput.value = calculateEndDate(manualStartInput?.value || '', item.frequency || '', item.duration || '');
                }

                addManualItemBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        });

        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = Number(this.dataset.index);
                manualItems.splice(index, 1);

                if (editingManualItemIndex !== null) {
                    if (editingManualItemIndex === index) {
                        resetManualItemForm();
                    } else if (index < editingManualItemIndex) {
                        editingManualItemIndex -= 1;
                    }
                }

                renderManualItems();
            });
        });

        const roundedDiscountTotal = roundDiscountDown(discountTotal);
        const roundedTaxTotal = roundTaxUp(taxTotal);

        document.getElementById('manualSubtotal').textContent = formatCurrency(subtotal);
        document.getElementById('manualDiscountTotal').textContent = formatCurrency(roundedDiscountTotal);
        document.getElementById('manualTaxTotal').textContent = formatCurrency(roundedTaxTotal);
        document.getElementById('manualGrandTotal').textContent = formatCurrency(subtotal - roundedDiscountTotal + roundedTaxTotal);

        itemsDataInput.value = JSON.stringify(manualItems);
    }

    btnNextToStep3.addEventListener('click', function() {
        if (manualItems.length === 0) {
            alert('Please add at least one item.');
            return;
        }

        const clientId = "{{ request('c', request('clientid')) }}";
        if (!clientId) {
            alert('Please select a client before continuing.');
            return;
        }

        const invoiceTitle = invoiceTitleInput.value;
        if (!invoiceTitle.trim()) {
            invoiceTitleError.style.display = 'block';
            invoiceTitleInput.focus();
            return;
        }

        invoiceTitleError.style.display = 'none';

        // Save items to hidden input
        itemsDataInput.value = JSON.stringify(manualItems);

        const issueDateValue = document.getElementById('issue_date')?.value || '';
        const dueDateValue = document.getElementById('due_date')?.value || '';
        const notesValue = document.getElementById('notes')?.value || '';

        fetch("{{ route('invoices.save-draft') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                invoiceid: "{{ request('d', '') }}" || undefined,
                invoice_for: 'without_orders',
                clientid: clientId,
                invoice_title: invoiceTitle.trim(),
                issue_date: issueDateValue,
                due_date: dueDateValue,
                notes: notesValue,
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
            const clientToken = encodeURIComponent(clientId);
            let nextUrl = "{{ route('invoices.create') }}?step=3&invoice_for=without_orders&c=" + clientToken;
            if (isTaxInvoice) {
                nextUrl += "&tax_invoice=1";
            }
            if (data && data.invoiceid) {
                nextUrl += "&d=" + encodeURIComponent(data.invoiceid);
            }
            window.location.href = nextUrl;
        })
        .catch((error) => {
            console.error('Error saving draft:', error);
            alert('Unable to save draft right now. Please try again.');
        });
    });

    btnBackToStep1.addEventListener('click', function() {
        const clientId = "{{ request('c', request('clientid')) }}";
        const clientToken = encodeURIComponent(clientId);
        let backUrl = "{{ route('invoices.create') }}?step=1&c=" + clientToken;
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

    // Load draft items when editing
    function loadItems() {
        const draftId = "{{ request('d', '') }}";
        const clientId = "{{ request('c', request('clientid')) }}";
        const invoiceFor = "{{ request('invoice_for') }}";
        
        if (!draftId) return;
        
        const draftUrl = new URL("{{ route('invoices.get-draft', ['clientid' => '__CLIENTID__']) }}".replace('__CLIENTID__', clientId), window.location.origin);
        if (invoiceFor) {
            draftUrl.searchParams.set('invoice_for', invoiceFor);
        }
        draftUrl.searchParams.set('d', draftId);
        
        fetch(draftUrl.toString())
            .then(response => response.json())
            .then(data => {
                console.log('Draft data loaded:', data);
                if (data.draft) {
                    if (data.draft.items && data.draft.items.length > 0) {
                        manualItems = data.draft.items.map(item => ({
                            itemid: item.itemid,
                            item_name: item.item_name,
                            item_description: item.item_description || '',
                            quantity: item.quantity,
                            unit_price: item.unit_price,
                            discount_percent: item.discount_percent || 0,
                            discount_amount: item.discount_amount || 0,
                            tax_rate: item.tax_rate,
                            no_of_users: item.no_of_users,
                            frequency: item.frequency,
                            duration: item.duration,
                            start_date: item.start_date,
                            end_date: item.end_date,
                            tax_amount: item.tax_amount,
                            line_total: item.line_total
                        }));
                        renderManualItems();
                    }
                    
                    if (data.draft.invoice_title) {
                        invoiceTitleInput.value = data.draft.invoice_title;
                    }
                    
                    if (data.draft.issue_date) {
                        document.getElementById('issue_date').value = data.draft.issue_date;
                        document.getElementById('step2_issue_date').value = data.draft.issue_date;
                    }
                    if (data.draft.due_date) {
                        document.getElementById('due_date').value = data.draft.due_date;
                        document.getElementById('step2_due_date').value = data.draft.due_date;
                    }
                    if (data.draft.notes) {
                        document.getElementById('notes').value = data.draft.notes;
                        document.getElementById('step2_notes').value = data.draft.notes;
                    }
                    if (data.draft.currency_code) {
                        currencyCodeInput.value = data.draft.currency_code;
                    }

                    draftPiNumber = data.draft.pi_number || '';
                    draftTiNumber = data.draft.ti_number || '';
                    updateStep2HeaderNumber();
                }
            })
            .catch(error => {
                console.error('Failed to load draft items:', error);
            });
    }
    
    // Sync visible inputs to hidden inputs
    const issueDateInput = document.getElementById('issue_date');
    const dueDateInput = document.getElementById('due_date');
    const notesInput = document.getElementById('notes');
    const step2IssueDateInput = document.getElementById('step2_issue_date');
    const step2DueDateInput = document.getElementById('step2_due_date');
    const step2NotesInput = document.getElementById('step2_notes');
    
    if (issueDateInput && step2IssueDateInput) {
        issueDateInput.addEventListener('change', function() {
            step2IssueDateInput.value = this.value;
        });
    }
    if (dueDateInput && step2DueDateInput) {
        dueDateInput.addEventListener('change', function() {
            step2DueDateInput.value = this.value;
        });
    }
    if (notesInput && step2NotesInput) {
        notesInput.addEventListener('input', function() {
            step2NotesInput.value = this.value;
        });
    }
    
    // Toggle form visibility
    function toggleAddItemForm() {
        if (addItemFormCard) {
            addItemFormCard.style.display = addItemFormCard.style.display === 'none' ? 'block' : 'none';
        }
        if (toggleAddItemFormBtn) {
            toggleAddItemFormBtn.innerHTML = addItemFormCard.style.display === 'none' 
                ? '<i class="fas fa-plus" style="font-size: 0.75rem; line-height: 1; vertical-align: middle;"></i><span style="line-height: 1;">Add More Items</span>'
                : '<i class="fas fa-times" style="font-size: 0.75rem; line-height: 1; vertical-align: middle;"></i><span style="line-height: 1;">Cancel</span>';
        }
    }
    
    if (toggleAddItemFormBtn) {
        toggleAddItemFormBtn.addEventListener('click', toggleAddItemForm);
    }
    
    // Initialize
    loadItems();
    updateStep2HeaderNumber();
})();
</script>
