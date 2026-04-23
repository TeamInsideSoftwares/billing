@php
    $documentId = $invoice->invoiceid;
    $documentType = 'Invoice';
@endphp

<!-- Inline Edit Container -->
<div class="inline-edit-container">
    <div class="inline-edit-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0;">
        <h4 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #475569;">
            <i class="fas fa-edit" style="margin-right: 0.5rem; color: #3b82f6;"></i>
            Editing Invoice: {{ $invoice->invoice_number }}
        </h4>
        <button type="button" class="cancel-edit-btn" onclick="closeInlineEdit('{{ $documentId }}')">
            <i class="fas fa-times" style="margin-right: 0.5rem;"></i> Cancel
        </button>
    </div>

    <!-- Edit Form -->
    <form method="POST" action="{{ route('invoices.update', [$invoice, 'c' => request('c')]) }}" id="inline-edit-form-{{ $documentId }}">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div style="margin-bottom: 1.25rem; padding: 0.9rem 1rem; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; border-radius: 10px;">
                <strong style="display: block; margin-bottom: 0.4rem;">Fix these issues before updating the invoice:</strong>
                <ul style="margin: 0; padding-left: 1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Invoice Info Summary -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="invoice-meta-card">
                <span class="invoice-meta-label">Invoice Type</span>
                <strong class="invoice-meta-value">{{ $documentType }}</strong>
            </div>
            <div class="invoice-meta-card">
                <span class="invoice-meta-label">Invoice For</span>
                <strong class="invoice-meta-value">{{ ucfirst(str_replace('_', ' ', $invoice->invoice_for ?? 'without_orders')) }}</strong>
            </div>
            <div class="invoice-meta-card">
                <span class="invoice-meta-label">Current Status</span>
                <strong class="invoice-meta-value">{{ ($invoice->status ?? '') === 'cancelled' ? 'Cancelled' : 'Active' }}</strong>
            </div>
            <div class="invoice-meta-card">
                <span class="invoice-meta-label">Balance Due</span>
                <strong class="invoice-meta-value">{{ $invoice->currency_code ?? ($invoice->client->currency ?? 'INR') }} {{ number_format($invoice->balance_due ?? $invoice->grand_total ?? 0, 0) }}</strong>
            </div>
        </div>

        <!-- Basic Info Fields -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div style="grid-column: 1 / -1;">
                <label for="inline_clientid_{{ $documentId }}" class="field-label">Client</label>
                <select id="inline_clientid_{{ $documentId }}" name="clientid" required class="form-input">
                    <option value="">Choose a client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->clientid }}" data-currency="{{ $client->currency ?? 'INR' }}" {{ old('clientid', $invoice->clientid) == $client->clientid ? 'selected' : '' }}>
                            {{ $client->business_name ?? $client->contact_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="grid-column: 1 / -1;">
                <label for="inline_invoice_number_{{ $documentId }}" class="field-label">Invoice Number</label>
                <input type="text" id="inline_invoice_number_{{ $documentId }}" name="invoice_number" value="{{ old('invoice_number', $invoice->invoice_number) }}" required class="form-input">
            </div>

            <div style="grid-column: 1 / -1;">
                <label for="inline_invoice_title_{{ $documentId }}" class="field-label">Invoice Title</label>
                <input type="text" id="inline_invoice_title_{{ $documentId }}" name="invoice_title" value="{{ old('invoice_title', $invoice->invoice_title) }}" class="form-input" placeholder="e.g. Website Development - Phase 1">
            </div>

            <div>
                <label for="inline_issue_date_{{ $documentId }}" class="field-label">Issue Date</label>
                <input type="date" id="inline_issue_date_{{ $documentId }}" name="issue_date" value="{{ old('issue_date', optional($invoice->issue_date)->format('Y-m-d')) }}" class="form-input" required>
            </div>
            <div>
                <label for="inline_due_date_{{ $documentId }}" class="field-label">Due Date</label>
                <input type="date" id="inline_due_date_{{ $documentId }}" name="due_date" value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d')) }}" class="form-input" required>
            </div>
            <input type="hidden" name="status" value="{{ old('status', $invoice->status) }}">
            <input type="hidden" name="currency_code" id="inline_currency_{{ $documentId }}" value="{{ old('currency_code', $invoice->currency_code ?? ($invoice->client->currency ?? 'INR')) }}">
        </div>

        <input type="hidden" name="items_data" id="inline_items_data_{{ $documentId }}" value="">

        <!-- Items Section -->
        <div style="margin-top: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="margin: 0; font-size: 1rem; color: #334155;">Invoice Items</h4>
                <button type="button" id="inline_toggle_add_{{ $documentId }}" class="primary-button" style="padding: 0.5rem 1rem; font-size: 0.85rem;">+ Add Item</button>
            </div>

            <!-- Add Item Panel -->
            <div id="inline_add_panel_{{ $documentId }}" style="display: none; margin-bottom: 1rem; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.75rem; align-items: end;">
                    <div>
                        <label class="field-label small">Item</label>
                        <select id="inline_item_select_{{ $documentId }}" class="form-input">
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
                                            {{ $service->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label small">Qty</label>
                        <input type="number" id="inline_item_qty_{{ $documentId }}" class="form-input" value="1" min="1" step="1">
                    </div>
                    <div>
                        <label class="field-label small">Unit Price</label>
                        <input type="number" id="inline_item_price_{{ $documentId }}" class="form-input" min="0" step="0.01">
                    </div>
                    @if($account->allow_multi_taxation)
                    <div>
                        <label class="field-label small">Tax %</label>
                        <select id="inline_item_tax_{{ $documentId }}" class="form-input">
                            <option value="0">No Tax</option>
                            @foreach($taxes as $tax)
                                <option value="{{ $tax->rate }}">{{ $tax->tax_name }} ({{ number_format($tax->rate, 0) }}%)</option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <input type="hidden" id="inline_item_tax_{{ $documentId }}" value="{{ $account->fixed_tax_rate ?? 0 }}">
                    @endif
                    @if($account->have_users)
                    <div id="inline_item_users_wrap_{{ $documentId }}" style="display: none;">
                        <label class="field-label small">Users</label>
                        <input type="number" id="inline_item_users_{{ $documentId }}" class="form-input" value="1" min="1" step="1">
                    </div>
                    @else
                    <input type="hidden" id="inline_item_users_{{ $documentId }}" value="1">
                    @endif
                    <div>
                        <label class="field-label small">Frequency</label>
                        <select id="inline_item_freq_{{ $documentId }}" class="form-input">
                            <option value="">Not recurring</option>
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
                        <label class="field-label small">Duration</label>
                        <input type="number" id="inline_item_dur_{{ $documentId }}" class="form-input" min="0" step="1">
                    </div>
                </div>
                <div style="margin-top: 1rem; display: flex; gap: 0.75rem;">
                    <button type="button" id="inline_add_btn_{{ $documentId }}" class="primary-button" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Add Item</button>
                    <button type="button" id="inline_cancel_add_{{ $documentId }}" class="secondary-button" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Cancel</button>
                </div>
            </div>

            <!-- Items Table -->
            <div class="table-shell">
                <table class="data-table" style="margin: 0; font-size: 0.83rem;">
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
                            <th>Frequency</th>
                            <th>Duration</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="inline_items_body_{{ $documentId }}"></tbody>
                </table>
            </div>

            <!-- Totals -->
            <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                <div class="totals-card" style="min-width: 320px;">
                    <div class="total-row"><span>Subtotal</span><strong id="inline_subtotal_{{ $documentId }}">0</strong></div>
                    <div class="total-row"><span>Tax</span><strong id="inline_tax_{{ $documentId }}">0</strong></div>
                    <div class="total-row total-row-grand"><span>Grand Total</span><strong id="inline_grand_{{ $documentId }}">0</strong></div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div style="margin-top: 1.5rem;">
            <label for="inline_notes_{{ $documentId }}" class="field-label">Notes</label>
            <textarea id="inline_notes_{{ $documentId }}" name="notes" rows="3" class="form-input" style="min-height: 90px;">{{ old('notes', $invoice->notes) }}</textarea>
        </div>

        <!-- Action Buttons -->
        <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem; justify-content: flex-end;">
            <button type="button" class="cancel-edit-btn" onclick="closeInlineEdit('{{ $documentId }}')">
                <i class="fas fa-times" style="margin-right: 0.5rem;"></i> Cancel
            </button>
            <button type="submit" id="save-inline-edit-{{ $documentId }}" class="save-edit-btn">
                <i class="fas fa-save" style="margin-right: 0.5rem;"></i> Save Changes
            </button>
        </div>
    </form>
</div>

<style>
.invoice-meta-card { padding: 0.95rem 1rem; border: 1px solid #e2e8f0; border-radius: 12px; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); }
.invoice-meta-label, .field-label.small { display: block; margin-bottom: 0.35rem; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; color: #64748b; }
.invoice-meta-value { color: #1e293b; font-size: 0.95rem; }
.field-label { display: block; margin-bottom: 0.45rem; font-size: 0.85rem; font-weight: 600; color: #475569; }
.inline-edit-container { padding: 1.5rem; background: #ffffff; border: 2px solid #3b82f6; border-radius: 12px; margin-top: 0.5rem; }
.table-shell { border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; background: #ffffff; }
.totals-card { padding: 1rem; border-radius: 14px; background: #f8fafc; border: 1px solid #e2e8f0; }
.total-row { display: flex; justify-content: space-between; gap: 1rem; margin-bottom: 0.55rem; font-size: 0.9rem; color: #475569; }
.total-row:last-child { margin-bottom: 0; }
.total-row-grand { padding-top: 0.7rem; border-top: 1px solid #cbd5e1; font-size: 1rem; font-weight: 700; color: #1e293b; }
.cancel-edit-btn { padding: 0.65rem 1.2rem; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer; font-weight: 500; }
.cancel-edit-btn:hover { background: #e2e8f0; }
.save-edit-btn { padding: 0.65rem 1.2rem; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
.save-edit-btn:hover { opacity: 0.9; }
.save-edit-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.loading-spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid #ffffff; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite; margin-right: 0.5rem; }
@keyframes spin { to { transform: rotate(360deg); } }
@media (max-width: 1100px) { .manual-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 720px) { .manual-grid { grid-template-columns: 1fr; } }
</style>

<script>
(function () {
    const documentId = '{{ $documentId }}';
    const currencyCodeInput = document.getElementById(`inline_currency_${documentId}`);
    const clientSelect = document.getElementById(`inline_clientid_${documentId}`);
    const itemsBody = document.getElementById(`inline_items_body_${documentId}`);
    const itemsDataInput = document.getElementById(`inline_items_data_${documentId}`);
    const toggleAddBtn = document.getElementById(`inline_toggle_add_${documentId}`);
    const addPanel = document.getElementById(`inline_add_panel_${documentId}`);
    const cancelAddBtn = document.getElementById(`inline_cancel_add_${documentId}`);
    const addBtn = document.getElementById(`inline_add_btn_${documentId}`);

    const frequencyLabels = { 'one-time': 'One-Time', 'daily': 'Daily', 'weekly': 'Weekly', 'bi-weekly': 'Bi-Weekly', 'monthly': 'Monthly', 'quarterly': 'Quarterly', 'semi-annually': 'Semi-Annually', 'yearly': 'Yearly' };
    const frequencyOptions = ['', 'one-time', 'daily', 'weekly', 'bi-weekly', 'monthly', 'quarterly', 'semi-annually', 'yearly'];
    const taxOptions = @json(($taxes ?? collect())->map(fn ($tax) => ['name' => $tax->tax_name, 'rate' => (float) $tax->rate])->values());

    let clientCurrency = currencyCodeInput.value || 'INR';
    @php
        $itemsData = old('items_data') ? json_decode(old('items_data'), true) : $invoice->items->map(function ($item) {
            return [
                'itemid' => $item->itemid,
                'item_name' => $item->item_name ?? ($item->service->name ?? 'Item'),
                'item_description' => $item->item_description ?? '',
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $item->tax_rate,
                'duration' => $item->duration,
                'frequency' => $item->frequency,
                'no_of_users' => $item->no_of_users,
                'start_date' => optional($item->start_date)->format('Y-m-d'),
                'end_date' => optional($item->end_date)->format('Y-m-d'),
                'line_total' => $item->line_total,
            ];
        })->values()->toArray();
    @endphp
    let invoiceItems = @json($itemsData);

    function formatMoney(amount) {
        return `${clientCurrency} ${Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
    }

    function renderTaxSelect(selectedRate, attributes = '') {
        @if(!$account->allow_multi_taxation)
        return `<input type="hidden" value="0">`;
        @endif

        const normalizedRate = Number(selectedRate || 0);
        const options = [`<option value="0" ${normalizedRate === 0 ? 'selected' : ''}>No Tax</option>`];

        taxOptions.forEach((tax) => {
            const rate = Number(tax.rate || 0);
            options.push(`<option value="${rate}" ${rate === normalizedRate ? 'selected' : ''}>${tax.name} (${rate.toFixed(0)}%)</option>`);
        });

        const hasMatch = normalizedRate === 0 || taxOptions.some((tax) => Number(tax.rate || 0) === normalizedRate);
        if (!hasMatch && normalizedRate > 0) {
            options.push(`<option value="${normalizedRate}" selected>Custom (${normalizedRate.toFixed(0)}%)</option>`);
        }

        return `<select class="form-input item-input" ${attributes}>${options.join('')}</select>`;
    }

    function calculateLineTotal(quantity, unitPrice, users, frequency, duration) {
        let total = (Number(quantity) || 0) * (Number(unitPrice) || 0) * Math.max(1, Number(users) || 1);
        if (frequency && frequency !== 'one-time' && duration) {
            const durationNumber = Number(duration) || 0;
            if (durationNumber > 0) total *= durationNumber;
        }
        return total;
    }

    function roundTaxUp(value) {
        return Math.ceil(Math.max(0, Number(value) || 0));
    }

    function calculateEndDate(startDate, frequency, duration) {
        if (!startDate || !frequency || !duration || frequency === 'one-time') return '';
        const start = new Date(startDate);
        const durationNumber = Number(duration);
        if (Number.isNaN(start.getTime()) || durationNumber <= 0) return '';
        const end = new Date(start);
        switch (frequency) {
            case 'daily': end.setDate(end.getDate() + durationNumber); break;
            case 'weekly': end.setDate(end.getDate() + (durationNumber * 7)); break;
            case 'bi-weekly': end.setDate(end.getDate() + (durationNumber * 14)); break;
            case 'monthly': end.setMonth(end.getMonth() + durationNumber); break;
            case 'quarterly': end.setMonth(end.getMonth() + (durationNumber * 3)); break;
            case 'semi-annually': end.setMonth(end.getMonth() + (durationNumber * 6)); break;
            case 'yearly': end.setFullYear(end.getFullYear() + durationNumber); break;
            default: return '';
        }
        return end.toISOString().split('T')[0];
    }

    function renderItems() {
        itemsBody.innerHTML = '';
        let subtotal = 0, taxTotal = 0;
        let anyItemHasRecurringFrequency = false;

        invoiceItems.forEach((item) => {
            if (item.frequency && item.frequency !== 'one-time') {
                anyItemHasRecurringFrequency = true;
            }
        });

        invoiceItems.forEach((item, index) => {
            item.quantity = Math.max(1, Math.round(Number(item.quantity) || 1));
            const lineTotal = calculateLineTotal(item.quantity, item.unit_price, item.no_of_users, item.frequency, item.duration);
            const lineTax = roundTaxUp(lineTotal * (Number(item.tax_rate || 0) / 100));
            subtotal += lineTotal;
            taxTotal += lineTax;

            const showDates = item.frequency && item.frequency !== 'one-time';

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <div style="font-weight: 600; color: #111827;">${item.item_name || 'Item'}</div>
                    <textarea class="form-input item-input" data-index="${index}" data-field="item_description" rows="1" placeholder="Description (optional)" style="margin-top: 0.25rem; font-size: 0.75rem; min-height: 32px; resize: vertical;">${item.item_description || ''}</textarea>
                </td>
                <td><input type="number" class="form-input item-input" data-index="${index}" data-field="quantity" min="1" step="1" value="${item.quantity}"></td>
                <td><input type="number" class="form-input item-input" data-index="${index}" data-field="unit_price" min="0" step="0.01" value="${item.unit_price}"></td>
                ${renderTaxSelect(item.tax_rate, `data-index="${index}" data-field="tax_rate"`)}
                <td><input type="number" class="form-input item-input" data-index="${index}" data-field="no_of_users" min="1" step="1" value="${item.no_of_users || 1}"></td>
                <td>
                    <select class="form-input item-input" data-index="${index}" data-field="frequency">
                        ${frequencyOptions.map((value) => `<option value="${value}" ${item.frequency === value ? 'selected' : ''}>${value ? frequencyLabels[value] : 'Not recurring'}</option>`).join('')}
                    </select>
                </td>
                <td><input type="number" class="form-input item-input" data-index="${index}" data-field="duration" min="0" step="1" value="${item.duration ?? ''}"></td>
                <td style="display: ${showDates ? '' : 'none'}"><input type="date" class="form-input item-input" data-index="${index}" data-field="start_date" value="${item.start_date || ''}" ${showDates ? '' : 'disabled'}></td>
                <td style="display: ${showDates ? '' : 'none'}"><input type="date" class="form-input item-input" data-index="${index}" data-field="end_date" value="${item.end_date || ''}" ${showDates ? '' : 'disabled'}></td>
                <td><strong>${formatMoney(lineTotal + lineTax)}</strong></td>
                <td><button type="button" class="icon-action-btn delete remove-item" data-index="${index}" title="Remove"><i class="fas fa-trash"></i></button></td>
            `;
            itemsBody.appendChild(row);
        });

        const roundedTaxTotal = roundTaxUp(taxTotal);
        document.getElementById(`inline_subtotal_${documentId}`).textContent = formatMoney(subtotal);
        document.getElementById(`inline_tax_${documentId}`).textContent = formatMoney(roundedTaxTotal);
        document.getElementById(`inline_grand_${documentId}`).textContent = formatMoney(subtotal + roundedTaxTotal);
    }

    function resetAddItemForm() {
        document.getElementById(`inline_item_select_${documentId}`).value = '';
        document.getElementById(`inline_item_qty_${documentId}`).value = 1;
        document.getElementById(`inline_item_price_${documentId}`).value = '';
        document.getElementById(`inline_item_tax_${documentId}`).value = '0';
        document.getElementById(`inline_item_freq_${documentId}`).value = '';
        document.getElementById(`inline_item_dur_${documentId}`).value = '';
        document.getElementById(`inline_item_users_${documentId}`).value = 1;
        @if($account->have_users)
        toggleInlineAddUsersField();
        @endif
    }

    clientSelect.addEventListener('change', function () {
        clientCurrency = this.options[this.selectedIndex]?.dataset?.currency || 'INR';
        currencyCodeInput.value = clientCurrency;
        renderItems();
    });

    toggleAddBtn.addEventListener('click', function () {
        addPanel.style.display = addPanel.style.display === 'none' ? 'block' : 'none';
    });

    cancelAddBtn.addEventListener('click', function () {
        addPanel.style.display = 'none';
        resetAddItemForm();
    });

    document.getElementById(`inline_item_select_${documentId}`).addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        document.getElementById(`inline_item_price_${documentId}`).value = option?.dataset?.price || '';
        document.getElementById(`inline_item_tax_${documentId}`).value = option?.dataset?.taxRate || '0';
        @if($account->have_users)
        toggleInlineAddUsersField();
        @endif
    });

    @if($account->have_users)
    function isInlineAddItemUserWise() {
        const select = document.getElementById(`inline_item_select_${documentId}`);
        const option = select?.options[select.selectedIndex];
        return option?.dataset?.userWise === '1';
    }

    function toggleInlineAddUsersField() {
        const wrap = document.getElementById(`inline_item_users_wrap_${documentId}`);
        const users = document.getElementById(`inline_item_users_${documentId}`);
        if (!wrap || !users) return;
        const show = isInlineAddItemUserWise();
        wrap.style.display = show ? '' : 'none';
        if (!show) users.value = 1;
    }
    toggleInlineAddUsersField();
    @endif

    addBtn.addEventListener('click', function () {
        const select = document.getElementById(`inline_item_select_${documentId}`);
        const option = select.options[select.selectedIndex];
        if (!select.value) {
            alert('Select an item first.');
            return;
        }

        const item = {
            itemid: select.value,
            item_name: (option.text || '').split(' (')[0],
            quantity: Math.max(1, Math.round(Number(document.getElementById(`inline_item_qty_${documentId}`).value) || 1)),
            unit_price: Number(document.getElementById(`inline_item_price_${documentId}`).value) || 0,
            tax_rate: Number(document.getElementById(`inline_item_tax_${documentId}`).value) || 0,
            duration: document.getElementById(`inline_item_dur_${documentId}`).value || null,
            frequency: document.getElementById(`inline_item_freq_${documentId}`).value || null,
            @if($account->have_users)
            no_of_users: isInlineAddItemUserWise() ? Math.max(1, Number(document.getElementById(`inline_item_users_${documentId}`).value) || 1) : null,
            @else
            no_of_users: Math.max(1, Number(document.getElementById(`inline_item_users_${documentId}`).value) || 1),
            @endif
            start_date: null,
            end_date: null,
        };

        item.line_total = calculateLineTotal(item.quantity, item.unit_price, item.no_of_users, item.frequency, item.duration);
        invoiceItems.push(item);
        renderItems();
        resetAddItemForm();
        addPanel.style.display = 'none';
    });

    itemsBody.addEventListener('input', function (event) {
        const input = event.target.closest('.item-input');
        if (!input) return;

        const index = Number(input.dataset.index);
        const field = input.dataset.field;
        if (field === 'quantity') {
            const qty = Math.max(1, Math.round(Number(input.value) || 1));
            invoiceItems[index][field] = qty;
            input.value = qty;
        } else {
            invoiceItems[index][field] = input.type === 'number' ? Number(input.value) : input.value || null;
        }

        if (field === 'frequency' && (!input.value || input.value === 'one-time')) {
            invoiceItems[index].start_date = null;
            invoiceItems[index].end_date = null;
        }

        if (['start_date', 'frequency', 'duration'].includes(field)) {
            invoiceItems[index].end_date = calculateEndDate(
                invoiceItems[index].start_date,
                invoiceItems[index].frequency,
                invoiceItems[index].duration
            );
        }
        renderItems();
    });

    itemsBody.addEventListener('click', function (event) {
        const btn = event.target.closest('.remove-item');
        if (!btn) return;
        invoiceItems.splice(Number(btn.dataset.index), 1);
        renderItems();
    });

    // Save items data on form submit
    const form = document.getElementById(`inline-edit-form-${documentId}`);
    form.addEventListener('submit', function (e) {
        itemsDataInput.value = JSON.stringify(invoiceItems.map(i => ({
            ...i,
            item_description: i.item_description || '',
            line_total: calculateLineTotal(i.quantity, i.unit_price, i.no_of_users, i.frequency, i.duration)
        })));
    });

    // Initial render
    renderItems();
})();
</script>
