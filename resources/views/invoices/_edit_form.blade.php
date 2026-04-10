<form method="POST" action="{{ route('invoices.update', $invoice) }}" id="invoiceForm">
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

    @if($invoice->isProforma())
        <div style="margin-bottom: 1.25rem; padding: 0.9rem 1rem; border: 1px solid #bfdbfe; background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%); color: #1e40af; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <div>
                <strong style="display: block; margin-bottom: 0.25rem;">This is a Proforma Invoice</strong>
                @if($invoice->convertedTaxInvoice)
                    <span style="font-size: 0.85rem;">A tax invoice has already been created for this proforma invoice.</span>
                @else
                    <span style="font-size: 0.85rem;">You can convert this to a tax invoice. A new tax invoice will be created with the same items.</span>
                @endif
            </div>
            @if($invoice->convertedTaxInvoice)
                <a href="{{ route('invoices.show', $invoice->convertedTaxInvoice) }}" class="primary-button" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; padding: 0.65rem 1.2rem; font-size: 0.85rem; font-weight: 600; color: #ffffff; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-link"></i> View Tax Invoice
                </a>
            @else
                <button type="submit" form="convertToTaxInvoiceForm" class="primary-button" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: none; padding: 0.65rem 1.2rem; font-size: 0.85rem; font-weight: 600; color: #ffffff; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-file-invoice-dollar"></i> Convert to Tax Invoice
                </button>
            @endif
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="invoice-meta-card">
            <span class="invoice-meta-label">Invoice Type</span>
            <strong class="invoice-meta-value">{{ ucfirst($invoice->invoice_type ?? 'proforma') }}</strong>
        </div>
        <div class="invoice-meta-card">
            <span class="invoice-meta-label">Invoice For</span>
            <strong class="invoice-meta-value">{{ ucfirst(str_replace('_', ' ', $invoice->invoice_for ?? 'without_orders')) }}</strong>
        </div>
        <div class="invoice-meta-card">
            <span class="invoice-meta-label">Current Status</span>
            <strong class="invoice-meta-value">{{ ucfirst($invoice->status ?? 'draft') }}</strong>
        </div>
        <div class="invoice-meta-card">
            <span class="invoice-meta-label">Balance Due</span>
            <strong class="invoice-meta-value">{{ $invoice->currency_code ?? ($invoice->client->currency ?? 'INR') }} {{ number_format($invoice->balance_due ?? $invoice->grand_total ?? 0, 2) }}</strong>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div style="grid-column: 1 / -1;">
            <label for="clientid" class="field-label">Client</label>
            <div style="display: grid; grid-template-columns: minmax(280px, 420px) minmax(220px, 320px); gap: 1rem; align-items: end;">
                <div>
                    <select id="clientid" name="clientid" required class="form-input">
                        <option value="">Choose a client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->clientid }}" data-currency="{{ $client->currency ?? 'INR' }}" {{ old('clientid', $invoice->clientid) == $client->clientid ? 'selected' : '' }}>
                                {{ $client->business_name ?? $client->contact_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="invoice_number" class="field-label">Invoice Number</label>
                    <input type="text" id="invoice_number" name="invoice_number" value="{{ old('invoice_number', $invoice->invoice_number) }}" required class="form-input">
                </div>
            </div>
        </div>

        <div style="grid-column: 1 / -1; margin-top: 1rem;">
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
        <div>
            <label for="status" class="field-label">Status</label>
            <select id="status" name="status" class="form-input" required>
                @foreach(['draft', 'sent', 'paid', 'overdue', 'cancelled'] as $status)
                    <option value="{{ $status }}" {{ old('status', $invoice->status) === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <input type="hidden" name="currency_code" id="currency_code" value="{{ old('currency_code', $invoice->currency_code ?? ($invoice->client->currency ?? 'INR')) }}">
    </div>

    <input type="hidden" name="items_data" id="items_data" value="">

    <div class="workflow-panel" style="margin-top: 0; padding-top: 0; border-top: 0;">
        <div class="panel-heading-row" style="display: flex; justify-content: space-between; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <div>
                <h4 style="margin: 0; font-size: 1rem; color: #334155;">Invoice Items</h4>
            </div>
            <button type="button" id="toggleAddItemBtn" class="primary-button">+ Add Item</button>
        </div>

        <div id="addItemPanel" class="builder-card" style="display: none; margin-bottom: 1rem;">
            <div class="manual-grid">
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
                                    <option value="{{ $service->itemid }}" data-price="{{ $defaultCosting?->selling_price ?? 0 }}" data-tax-rate="{{ $defaultCosting?->tax_rate ?? 0 }}">
                                        {{ $service->name }} ({{ number_format($defaultCosting?->selling_price ?? 0, 0) }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="item_quantity" class="field-label small">Qty</label>
                    <input type="number" id="item_quantity" class="form-input" value="1" min="0.01" step="0.01">
                </div>
                <div>
                    <label for="item_unit_price" class="field-label small">Unit Price</label>
                    <input type="number" id="item_unit_price" class="form-input" min="0" step="0.01">
                </div>
                @if($account->allow_multi_taxation)
                <div>
                    <label for="item_tax_rate" class="field-label small">Tax % <a href="#" id="open-tax-modal-invoice-edit" style="font-size:11px;margin-left:4px;" class="text-link">+ Add</a></label>
                    <select id="item_tax_rate" class="form-input">
                        <option value="0">No Tax</option>
                        @foreach($taxes as $tax)
                            <option value="{{ $tax->rate }}">{{ $tax->tax_name }} ({{ number_format($tax->rate, 2) }}%)</option>
                        @endforeach
                    </select>
                </div>
                @else
                <input type="hidden" id="item_tax_rate" value="{{ $account->fixed_tax_rate ?? 0 }}">
                @endif
                <div>
                    <label for="item_frequency" class="field-label small">Frequency</label>
                    <select id="item_frequency" class="form-input">
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
                    <label for="item_duration" class="field-label small">Duration</label>
                    <input type="number" id="item_duration" class="form-input" min="0" step="1">
                </div>
                @if($account->have_users)
                <div>
                    <label for="item_users" class="field-label small">Users</label>
                    <input type="number" id="item_users" class="form-input" value="1" min="1" step="1">
                </div>
                @else
                <input type="hidden" id="item_users" value="1">
                @endif
                <div>
                    <label for="item_start_date" class="field-label small">Start Date</label>
                    <input type="date" id="item_start_date" class="form-input">
                </div>
                <div>
                    <label for="item_end_date" class="field-label small">End Date</label>
                    <input type="date" id="item_end_date" class="form-input">
                </div>
            </div>
            <div style="margin-top: 1rem; display: flex; gap: 0.75rem;">
                <button type="button" id="addItemBtn" class="primary-button">Add Item</button>
                <button type="button" id="cancelAddItemBtn" class="secondary-button" style="padding: 0.65rem 1rem;">Cancel</button>
            </div>
        </div>

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
                        <th>Duration</th>
                        <th>Frequency</th>
                        @if($account->have_users)
                        <th>Users</th>
                        @endif
                        <th>Start</th>
                        <th>End</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
            </table>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
            <div class="totals-card" style="min-width: 320px;">
                <div class="total-row"><span>Subtotal</span><strong id="subtotalDisplay">0.00</strong></div>
                <div class="total-row"><span>Tax</span><strong id="taxDisplay">0.00</strong></div>
                <div class="total-row total-row-grand"><span>Grand Total</span><strong id="grandTotalDisplay">0.00</strong></div>
            </div>
        </div>
    </div>

    <div style="margin-top: 1.5rem;">
        <label for="notes" class="field-label">Notes</label>
        <textarea id="notes" name="notes" rows="4" class="form-input" style="min-height: 110px;">{{ old('notes', $invoice->notes) }}</textarea>
    </div>

    @if(isset($inline) && $inline)
    <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem; justify-content: flex-end;">
        <button type="button" id="cancel-inline-edit-{{ $invoice->invoiceid }}" class="cancel-edit-btn" onclick="toggleInlineEdit({{ $invoice->invoiceid }})">
            <i class="fas fa-times" style="margin-right: 0.5rem;"></i> Cancel
        </button>
        <button type="submit" id="save-inline-edit-{{ $invoice->invoiceid }}" class="save-edit-btn">
            <i class="fas fa-save" style="margin-right: 0.5rem;"></i> Save Changes
        </button>
    </div>
    @else
    <div class="form-actions" style="margin-top: 1rem; display: flex; gap: 0.75rem;">
        <button type="submit" class="primary-button" id="updateInvoiceBtn">Update Invoice</button>
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
    const cancelAddItemBtn = document.getElementById('cancelAddItemBtn');
    const addItemBtn = document.getElementById('addItemBtn');
    const updateInvoiceBtn = document.getElementById('updateInvoiceBtn');

    const frequencyLabels = { 'one-time': 'One-Time', 'daily': 'Daily', 'weekly': 'Weekly', 'bi-weekly': 'Bi-Weekly', 'monthly': 'Monthly', 'quarterly': 'Quarterly', 'semi-annually': 'Semi-Annually', 'yearly': 'Yearly' };
    const frequencyOptions = ['', 'one-time', 'daily', 'weekly', 'bi-weekly', 'monthly', 'quarterly', 'semi-annually', 'yearly'];
    const taxOptions = @json(($taxes ?? collect())->map(fn ($tax) => ['name' => $tax->tax_name, 'rate' => (float) $tax->rate])->values());

    let clientCurrency = currencyCodeInput.value || 'INR';
    @php
        $itemsData = old('items_data') ? json_decode(old('items_data'), true) : $invoice->items->map(function ($item) {
            return [
                'itemid' => $item->itemid,
                'item_name' => $item->item_name ?? ($item->service->name ?? 'Item'),
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
        return `${clientCurrency} ${Number(amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function renderTaxSelect(selectedRate, attributes = '') {
        @if(!$account->allow_multi_taxation)
        return `<input type="hidden" value="0">`;
        @endif

        const normalizedRate = Number(selectedRate || 0);
        const options = [`<option value="0" ${normalizedRate === 0 ? 'selected' : ''}>No Tax</option>`];

        taxOptions.forEach((tax) => {
            const rate = Number(tax.rate || 0);
            options.push(`<option value="${rate}" ${rate === normalizedRate ? 'selected' : ''}>${tax.name} (${rate.toFixed(2)}%)</option>`);
        });

        const hasMatch = normalizedRate === 0 || taxOptions.some((tax) => Number(tax.rate || 0) === normalizedRate);
        if (!hasMatch && normalizedRate > 0) {
            options.push(`<option value="${normalizedRate}" selected>Custom (${normalizedRate.toFixed(2)}%)</option>`);
        }

        return `<select class="form-input item-input" ${attributes}>${options.join('')}</select>`;
    }

    function calculateLineTotal(quantity, unitPrice, users, frequency, duration) {
        let total = (Number(quantity) || 0) * (Number(unitPrice) || 0) * Math.max(1, Number(users) || 1);
        if (frequency && frequency !== 'one-time' && duration) {
            const durationNumber = Number(duration) || 0;
            if (durationNumber > 0) {
                total *= durationNumber;
            }
        }
        return total;
    }

    function calculateEndDate(startDate, frequency, duration) {
        if (!startDate || !frequency || !duration || frequency === 'one-time') {
            return '';
        }
        const start = new Date(startDate);
        const durationNumber = Number(duration);
        if (Number.isNaN(start.getTime()) || durationNumber <= 0) {
            return '';
        }
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
        let subtotal = 0;
        let taxTotal = 0;

        invoiceItems.forEach((item, index) => {
            item.quantity = Number(item.quantity) || 0;
            item.unit_price = Number(item.unit_price) || 0;
            item.tax_rate = Number(item.tax_rate) || 0;
            item.no_of_users = Math.max(1, Number(item.no_of_users) || 1);
            item.line_total = calculateLineTotal(item.quantity, item.unit_price, item.no_of_users, item.frequency, item.duration);
            const lineTax = item.line_total * (item.tax_rate / 100);
            subtotal += item.line_total;
            taxTotal += lineTax;

            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>${item.item_name || 'Item'}</strong></td>
                <td><input type="number" class="form-input item-input" data-index="${index}" data-field="quantity" min="0.01" step="0.01" value="${item.quantity}"></td>
                <td><input type="number" class="form-input item-input" data-index="${index}" data-field="unit_price" min="0" step="0.01" value="${item.unit_price}"></td>
                <td>${renderTaxSelect(item.tax_rate, `data-index="${index}" data-field="tax_rate"` )}</td>
                <td><input type="number" class="form-input item-input" data-index="${index}" data-field="duration" min="0" step="1" value="${item.duration ?? ''}"></td>
                <td>
                    <select class="form-input item-input" data-index="${index}" data-field="frequency">
                        ${frequencyOptions.map((value) => `<option value="${value}" ${item.frequency === value ? 'selected' : ''}>${value ? frequencyLabels[value] : 'Not recurring'}</option>`).join('')}
                    </select>
                </td>
                <td><input type="number" class="form-input item-input" data-index="${index}" data-field="no_of_users" min="1" step="1" value="${item.no_of_users || 1}"></td>
                <td><input type="date" class="form-input item-input" data-index="${index}" data-field="start_date" value="${item.start_date || ''}"></td>
                <td><input type="date" class="form-input item-input" data-index="${index}" data-field="end_date" value="${item.end_date || ''}"></td>
                <td><strong>${formatMoney(item.line_total + lineTax)}</strong></td>
                <td><button type="button" class="icon-action-btn delete remove-item" data-index="${index}" title="Remove"><i class="fas fa-trash"></i></button></td>
            `;
            itemsBody.appendChild(row);
        });

        document.getElementById('subtotalDisplay').textContent = formatMoney(subtotal);
        document.getElementById('taxDisplay').textContent = formatMoney(taxTotal);
        document.getElementById('grandTotalDisplay').textContent = formatMoney(subtotal + taxTotal);
    }

    function resetAddItemForm() {
        document.getElementById('item_itemid').value = '';
        document.getElementById('item_quantity').value = 1;
        document.getElementById('item_unit_price').value = '';
        document.getElementById('item_tax_rate').value = '0';
        document.getElementById('item_frequency').value = '';
        document.getElementById('item_duration').value = '';
        document.getElementById('item_users').value = 1;
        document.getElementById('item_start_date').value = '';
        document.getElementById('item_end_date').value = '';
    }

    clientSelect.addEventListener('change', function () {
        clientCurrency = this.options[this.selectedIndex]?.dataset?.currency || 'INR';
        currencyCodeInput.value = clientCurrency;
        renderItems();
    });

    toggleAddItemBtn.addEventListener('click', function () {
        addItemPanel.style.display = addItemPanel.style.display === 'none' ? 'block' : 'none';
    });

    cancelAddItemBtn.addEventListener('click', function () {
        addItemPanel.style.display = 'none';
        resetAddItemForm();
    });

    document.getElementById('item_itemid').addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        document.getElementById('item_unit_price').value = option?.dataset?.price || '';
        document.getElementById('item_tax_rate').value = option?.dataset?.taxRate || '0';
    });

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
            quantity: Number(document.getElementById('item_quantity').value) || 1,
            unit_price: Number(document.getElementById('item_unit_price').value) || 0,
            tax_rate: Number(document.getElementById('item_tax_rate').value) || 0,
            duration: document.getElementById('item_duration').value || null,
            frequency: document.getElementById('item_frequency').value || null,
            no_of_users: Math.max(1, Number(document.getElementById('item_users').value) || 1),
            start_date: document.getElementById('item_start_date').value || null,
            end_date: document.getElementById('item_end_date').value || null,
        };

        item.line_total = calculateLineTotal(item.quantity, item.unit_price, item.no_of_users, item.frequency, item.duration);
        invoiceItems.push(item);
        renderItems();
        resetAddItemForm();
        addItemPanel.style.display = 'none';
    });

    itemsBody.addEventListener('input', function (event) {
        const input = event.target.closest('.item-input');
        if (!input) {
            return;
        }

        const index = Number(input.dataset.index);
        const field = input.dataset.field;
        invoiceItems[index][field] = input.type === 'number' ? Number(input.value) : input.value || null;

        if (field === 'start_date' && invoiceItems[index].frequency && invoiceItems[index].duration) {
            invoiceItems[index].end_date = calculateEndDate(invoiceItems[index].start_date, invoiceItems[index].frequency, invoiceItems[index].duration);
        }

        if ((field === 'frequency' || field === 'duration') && invoiceItems[index].start_date) {
            invoiceItems[index].end_date = calculateEndDate(invoiceItems[index].start_date, invoiceItems[index].frequency, invoiceItems[index].duration);
        }

        renderItems();
    });

    itemsBody.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-item');
        if (!button) {
            return;
        }
        invoiceItems.splice(Number(button.dataset.index), 1);
        renderItems();
    });

    document.getElementById('invoiceForm').addEventListener('submit', function (event) {
        if (!invoiceItems.length) {
            event.preventDefault();
            alert('Add at least one invoice item before updating the invoice.');
            return;
        }

        itemsDataInput.value = JSON.stringify(invoiceItems.map((item) => ({
            itemid: item.itemid,
            item_name: item.item_name,
            quantity: Number(item.quantity) || 0,
            unit_price: Number(item.unit_price) || 0,
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
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header" style="padding: 0.75rem 1.25rem; border-bottom: 1px solid #e5e7eb;">
                <h5 class="modal-title" style="font-size: 1rem; font-weight: 600;">
                    <i class="fas fa-receipt" style="margin-right: 0.5rem; color: #64748b;"></i>Add Tax
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 1.25rem;">
                <form method="POST" action="{{ route('taxes.store') }}" id="quick-tax-form-invoice-edit">
                    @csrf
                    <input type="hidden" name="redirect_back" value="1">
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
.invoice-meta-card { padding: 0.95rem 1rem; border: 1px solid #e2e8f0; border-radius: 12px; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); }
.invoice-meta-label, .field-label.small { display: block; margin-bottom: 0.35rem; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; color: #64748b; }
.invoice-meta-value { color: #1e293b; font-size: 0.95rem; }
.field-label { display: block; margin-bottom: 0.45rem; font-size: 0.85rem; font-weight: 600; color: #475569; }
.workflow-panel { margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #e2e8f0; }
.panel-heading-row { margin-bottom: 0.8rem; }
.table-shell { border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; background: #ffffff; }
.builder-card { padding: 1rem; border: 1px solid #e2e8f0; border-radius: 14px; background: #f8fafc; }
.manual-grid { display: grid; grid-template-columns: 2fr 0.7fr 1fr 0.8fr 1fr 0.8fr 0.8fr 1fr 1fr; gap: 0.75rem; align-items: end; }
.totals-card { padding: 1rem; border-radius: 14px; background: #f8fafc; border: 1px solid #e2e8f0; }
.total-row { display: flex; justify-content: space-between; gap: 1rem; margin-bottom: 0.55rem; font-size: 0.9rem; color: #475569; }
.total-row:last-child { margin-bottom: 0; }
.total-row-grand { padding-top: 0.7rem; border-top: 1px solid #cbd5e1; font-size: 1rem; font-weight: 700; color: #1e293b; }
@media (max-width: 1200px) { .manual-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 720px) { .manual-grid { grid-template-columns: 1fr; } }
</style>
