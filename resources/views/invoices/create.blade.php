@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #475569;">Create Invoice</h3>
    </div>
    <a href="{{ route('invoices.index') }}" class="text-link">&larr; Back to invoices</a>
</section>

<section class="panel-card" style="padding: 1.5rem;">
    <form method="POST" action="{{ route('invoices.store') }}" id="invoiceForm">
        @csrf

        @if ($errors->any())
            <div style="margin-bottom: 1.25rem; padding: 0.9rem 1rem; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; border-radius: 10px;">
                <strong style="display: block; margin-bottom: 0.4rem;">Fix these issues before creating the invoice:</strong>
                <ul style="margin: 0; padding-left: 1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="invoice-meta-card" style="margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: start; gap: 1rem; flex-wrap: wrap;">
                <div style="flex: 1 1 620px; display: grid; grid-template-columns: minmax(280px, 420px) minmax(220px, 320px); gap: 1rem; align-items: end;">
                    <div>
                        <label for="clientid" class="field-label">Client</label>
                        <select id="clientid" name="clientid" required class="form-input">
                            <option value="">Choose a client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->clientid }}" data-currency="{{ $client->currency ?? 'INR' }}" {{ old('clientid') == $client->clientid ? 'selected' : '' }}>
                                    {{ $client->business_name ?? $client->contact_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="invoice_number" class="field-label">Invoice Number</label>
                        <input type="text" id="invoice_number" name="invoice_number" value="{{ old('invoice_number', $nextInvoiceNumber) }}" readonly class="form-input" style="background: #f8fafc;">
                    </div>
                </div>
                <div style="min-width: 180px; text-align: right;">
                    <span class="invoice-meta-label">Invoice Type</span>
                    <strong class="invoice-meta-value">Proforma</strong>
                </div>
            </div>
        </div>

        <input type="hidden" name="orderid" id="orderid" value="{{ old('orderid') }}">
        <input type="hidden" name="invoice_type" value="proforma">
        <input type="hidden" name="status" value="draft">
        <input type="hidden" name="currency_code" id="currency_code" value="{{ old('currency_code', 'INR') }}">
        <input type="hidden" name="subtotal" id="subtotal" value="{{ old('subtotal', 0) }}">
        <input type="hidden" name="tax_total" id="tax_total" value="{{ old('tax_total', 0) }}">
        <input type="hidden" name="grand_total" id="grand_total" value="{{ old('grand_total', 0) }}">
        <input type="hidden" name="items_data" id="items_data" value="{{ old('items_data') }}">

        <div id="sourceWorkspace" style="display: none;">
            <div style="margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: center; margin-bottom: 0.8rem;">
                    <div>
                        <h4 style="margin: 0; font-size: 1rem; color: #334155;">Existing Invoices</h4>
                    </div>
                </div>
                <div id="clientInvoicesWrap" class="table-shell">
                    <table class="data-table" id="clientInvoicesTable" style="font-size: 0.85rem; margin: 0;">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Type</th>
                                <th>For</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="clientInvoicesBody"></tbody>
                    </table>
                    <div id="noInvoicesMessage" class="empty-state" style="display: none;">No invoices found for this client yet.</div>
                </div>
            </div>

            <div style="margin-bottom: 1rem; padding: 1rem 1.1rem; border: 1px solid #dbeafe; background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%); border-radius: 12px;">
                <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: center;">
                    <div>
                        <p style="margin: 0; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #1d4ed8;">Current Workflow</p>
                        <h4 style="margin: 0.3rem 0 0 0; font-size: 1rem; color: #1e293b;">Choose how you want to build this invoice</h4>
                    </div>
                    <span id="selectionSummary" style="display: none; padding: 0.5rem 0.75rem; background: #ffffff; border: 1px solid #bfdbfe; border-radius: 999px; color: #1e40af; font-size: 0.82rem; font-weight: 600;"></span>
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <p class="field-label" style="margin-bottom: 0.75rem;">Invoice Source</p>
                <div class="source-grid">
                    <label class="invoice-source-card">
                        <input type="radio" name="invoice_for" value="orders" {{ old('invoice_for') === 'orders' ? 'checked' : '' }}>
                        <span class="source-icon"><i class="fas fa-shopping-cart"></i></span>
                        <strong>From Orders</strong>
                    </label>
                    <label class="invoice-source-card">
                        <input type="radio" name="invoice_for" value="renewal" {{ old('invoice_for') === 'renewal' ? 'checked' : '' }}>
                        <span class="source-icon"><i class="fas fa-sync-alt"></i></span>
                        <strong>Renewal</strong>
                    </label>
                    <label class="invoice-source-card">
                        <input type="radio" name="invoice_for" value="without_orders" {{ old('invoice_for') === 'without_orders' ? 'checked' : '' }}>
                        <span class="source-icon"><i class="fas fa-pen-ruler"></i></span>
                        <strong>Without Orders</strong>
                    </label>
                </div>
            </div>

            <div id="ordersSection" class="workflow-panel" style="display: none;">
                <div class="panel-heading-row">
                    <div>
                        <h4 style="margin: 0; font-size: 1rem; color: #334155;">Available Orders</h4>
                        <p style="margin: 0.2rem 0 0 0; color: #64748b; font-size: 0.85rem;">Choose a pending order to pull its items into the invoice.</p>
                    </div>
                </div>
                <div class="table-shell">
                    <table class="data-table" style="font-size: 0.85rem; margin: 0;">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="ordersBody"></tbody>
                    </table>
                    <div id="noOrdersMessage" class="empty-state" style="display: none;">No uninvoiced orders are available for this client.</div>
                </div>
            </div>

            <div id="renewalSection" class="workflow-panel" style="display: none;">
                <div class="panel-heading-row">
                    <div>
                        <h4 style="margin: 0; font-size: 1rem; color: #334155;">Renewal Candidates</h4>
                        <p style="margin: 0.2rem 0 0 0; color: #64748b; font-size: 0.85rem;">Pick a previous invoice, then select the expired recurring items to renew.</p>
                    </div>
                </div>
                <div class="table-shell">
                    <table class="data-table" style="font-size: 0.85rem; margin: 0;">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Expired Items</th>
                                <th>Amount</th>
                                <th>Total Items</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="renewalBody"></tbody>
                    </table>
                    <div id="noRenewalMessage" class="empty-state" style="display: none;">No renewal-ready invoices were found for this client.</div>
                </div>
                <div id="renewalPicker" style="display: none; margin-top: 1rem;"></div>
            </div>

            <div id="manualItemsSection" class="workflow-panel" style="display: none;">
                <div class="panel-heading-row">
                    <div>
                        <h4 style="margin: 0; font-size: 1rem; color: #334155;">Manual Invoice Items</h4>
                        <p style="margin: 0.2rem 0 0 0; color: #64748b; font-size: 0.85rem;">Add the items you want to bill. You can edit quantity and price inline after adding them.</p>
                    </div>
                </div>

                <div class="builder-card">
                    <div class="manual-grid">
                        <div>
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
                                            <option value="{{ $service->itemid }}" data-selling-price="{{ $defaultCosting?->selling_price ?? 0 }}" data-tax-rate="{{ $defaultCosting?->tax_rate ?? 0 }}">
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
                        <div>
                            <label for="manual_item_tax_rate" class="field-label small">Tax</label>
                            <select id="manual_item_tax_rate" class="form-input">
                                <option value="0">No Tax</option>
                                @foreach($taxes as $tax)
                                    <option value="{{ $tax->rate }}">{{ $tax->tax_name }} ({{ number_format($tax->rate, 2) }}%)</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="manual_item_frequency" class="field-label small">Frequency</label>
                            <select id="manual_item_frequency" class="form-input">
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
                            <label for="manual_item_duration" class="field-label small">Duration</label>
                            <input type="number" id="manual_item_duration" class="form-input" min="0" step="1" placeholder="e.g. 12">
                        </div>
                        <div>
                            <label for="manual_item_users" class="field-label small">Users</label>
                            <input type="number" id="manual_item_users" class="form-input" value="1" min="1" step="1">
                        </div>
                        <div>
                            <label for="manual_item_start_date" class="field-label small">Start Date</label>
                            <input type="date" id="manual_item_start_date" class="form-input">
                        </div>
                        <div>
                            <label for="manual_item_end_date" class="field-label small">End Date</label>
                            <input type="date" id="manual_item_end_date" class="form-input">
                        </div>
                        <div style="display: flex; align-items: end;">
                            <button type="button" id="addManualItemBtn" class="primary-button" style="width: 100%;">Add Item</button>
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
                                <th>Frequency</th>
                                <th>Duration</th>
                                <th>Users</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="manualItemsBody"></tbody>
                    </table>
                    <div id="manualItemsEmpty" class="empty-state">No manual items added yet.</div>
                </div>

                <div id="manualOrderSummary" class="totals-card" style="display: none; margin-top: 1rem;">
                    <div class="total-row"><span>Subtotal</span><strong id="manualSubtotal">0.00</strong></div>
                    <div class="total-row"><span>Tax</span><strong id="manualTaxTotal">0.00</strong></div>
                    <div class="total-row total-row-grand"><span>Total</span><strong id="manualGrandTotal">0.00</strong></div>
                </div>

                <div style="margin-top: 1rem;">
                    <button type="submit" class="primary-button create-submit-btn" id="manualSubmitBtn" disabled>Create Invoice</button>
                </div>
            </div>

            <div id="itemsSection" class="workflow-panel" style="display: none;">
                <div class="panel-heading-row">
                    <div>
                        <h4 style="margin: 0; font-size: 1rem; color: #334155;">Review Invoice Items</h4>
                        <p style="margin: 0.2rem 0 0 0; color: #64748b; font-size: 0.85rem;">Adjust pricing, tax, duration, or dates before creating the invoice.</p>
                    </div>
                </div>
                <div class="table-shell">
                    <table class="data-table" style="margin: 0; font-size: 0.83rem;">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Tax %</th>
                                <th>Duration</th>
                                <th>Frequency</th>
                                <th>Users</th>
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
                        <div class="total-row"><span>Subtotal</span><strong id="subtotalDisplay">INR 0.00</strong></div>
                        <div class="total-row"><span>Tax</span><strong id="taxDisplay">INR 0.00</strong></div>
                        <div class="total-row total-row-grand"><span>Grand Total</span><strong id="grandTotalDisplay">INR 0.00</strong></div>
                    </div>
                </div>

                <div style="margin-top: 1rem;">
                    <button type="submit" class="primary-button create-submit-btn" id="itemsSubmitBtn" disabled>Create Invoice</button>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-top: 1.5rem;">
            <div>
                <label for="issue_date" class="field-label">Issue Date</label>
                <input type="date" id="issue_date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" class="form-input" required>
            </div>
            <div>
                <label for="due_date" class="field-label">Due Date</label>
                <input type="date" id="due_date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+7 days'))) }}" class="form-input" required>
            </div>
        </div>

        <div style="margin-top: 1rem;">
            <label for="notes" class="field-label">Internal Notes</label>
            <textarea id="notes" name="notes" rows="4" class="form-input" style="min-height: 110px;">{{ old('notes') }}</textarea>
        </div>
    </form>
</section>

<style>
.invoice-meta-card { padding: 0.95rem 1rem; border: 1px solid #e2e8f0; border-radius: 12px; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); }
.invoice-meta-label, .field-label.small { display: block; margin-bottom: 0.35rem; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; color: #64748b; }
.invoice-meta-value { color: #1e293b; font-size: 0.95rem; }
.field-label { display: block; margin-bottom: 0.45rem; font-size: 0.85rem; font-weight: 600; color: #475569; }
.source-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
.invoice-source-card { position: relative; display: flex; flex-direction: column; gap: 0.45rem; padding: 1rem 1.1rem; border: 1px solid #dbe4ee; border-radius: 14px; background: #ffffff; cursor: pointer; transition: 0.2s ease; }
.invoice-source-card:hover { border-color: #93c5fd; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05); }
.invoice-source-card input { position: absolute; opacity: 0; pointer-events: none; }
.invoice-source-card:has(input:checked) { border-color: #2563eb; background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%); box-shadow: 0 12px 32px rgba(37, 99, 235, 0.12); }
.source-icon { width: 42px; height: 42px; border-radius: 12px; background: #eff6ff; color: #2563eb; display: inline-flex; align-items: center; justify-content: center; font-size: 1rem; }
.invoice-source-card strong { color: #1e293b; }
.workflow-panel { margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #e2e8f0; }
.panel-heading-row { margin-bottom: 0.8rem; }
.table-shell { border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; background: #ffffff; }
.empty-state { padding: 1.4rem; text-align: center; color: #64748b; font-size: 0.88rem; }
.builder-card { padding: 1rem; border: 1px solid #e2e8f0; border-radius: 14px; background: #f8fafc; }
.manual-grid { display: grid; grid-template-columns: 2fr 0.7fr 1fr 1fr 1fr 0.8fr 0.8fr 1fr 1fr auto; gap: 0.75rem; align-items: end; }
.totals-card { padding: 1rem; border-radius: 14px; background: #f8fafc; border: 1px solid #e2e8f0; }
.total-row { display: flex; justify-content: space-between; gap: 1rem; margin-bottom: 0.55rem; font-size: 0.9rem; color: #475569; }
.total-row:last-child { margin-bottom: 0; }
.total-row-grand { padding-top: 0.7rem; border-top: 1px solid #cbd5e1; font-size: 1rem; font-weight: 700; color: #1e293b; }
@media (max-width: 1100px) { .manual-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 720px) { .manual-grid { grid-template-columns: 1fr; } }
</style>

<script>
(function () {
    const clientSelect = document.getElementById('clientid');
    const invoiceForm = document.getElementById('invoiceForm');
    const sourceWorkspace = document.getElementById('sourceWorkspace');
    const clientInvoicesBody = document.getElementById('clientInvoicesBody');
    const noInvoicesMessage = document.getElementById('noInvoicesMessage');
    const selectionSummary = document.getElementById('selectionSummary');
    const ordersSection = document.getElementById('ordersSection');
    const ordersBody = document.getElementById('ordersBody');
    const noOrdersMessage = document.getElementById('noOrdersMessage');
    const renewalSection = document.getElementById('renewalSection');
    const renewalBody = document.getElementById('renewalBody');
    const noRenewalMessage = document.getElementById('noRenewalMessage');
    const renewalPicker = document.getElementById('renewalPicker');
    const manualItemsSection = document.getElementById('manualItemsSection');
    const itemsSection = document.getElementById('itemsSection');
    const itemsBody = document.getElementById('itemsBody');
    const manualItemsBody = document.getElementById('manualItemsBody');
    const manualItemsTable = document.getElementById('manualItemsTable');
    const manualItemsEmpty = document.getElementById('manualItemsEmpty');
    const manualSummary = document.getElementById('manualOrderSummary');
    const manualSubmitBtn = document.getElementById('manualSubmitBtn');
    const itemsSubmitBtn = document.getElementById('itemsSubmitBtn');
    const sourceRadios = document.querySelectorAll('input[name="invoice_for"]');
    const orderIdInput = document.getElementById('orderid');
    const subtotalInput = document.getElementById('subtotal');
    const taxTotalInput = document.getElementById('tax_total');
    const grandTotalInput = document.getElementById('grand_total');
    const itemsDataInput = document.getElementById('items_data');
    const currencyCodeInput = document.getElementById('currency_code');
    const addManualItemBtn = document.getElementById('addManualItemBtn');

    const frequencyOptions = ['one-time', 'daily', 'weekly', 'bi-weekly', 'monthly', 'quarterly', 'semi-annually', 'yearly'];
    const frequencyLabels = { 'one-time': 'One-Time', 'daily': 'Daily', 'weekly': 'Weekly', 'bi-weekly': 'Bi-Weekly', 'monthly': 'Monthly', 'quarterly': 'Quarterly', 'semi-annually': 'Semi-Annually', 'yearly': 'Yearly' };
    const taxOptions = @json(($taxes ?? collect())->map(fn ($tax) => ['name' => $tax->tax_name, 'rate' => (float) $tax->rate])->values());

    let selectedClientId = clientSelect.value || null;
    let clientCurrency = currencyCodeInput.value || 'INR';
    let invoiceItems = [];
    let manualItems = [];
    let selectedSourceRecord = null;
    let manualItemCounter = 0;
    let editingManualItemId = null;

    function formatMoney(amount) {
        return `${clientCurrency} ${Number(amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function renderTaxSelect(selectedRate, inputClass, attributes = '') {
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

        return `<select class="form-input ${inputClass}" ${attributes}>${options.join('')}</select>`;
    }

    function getActiveInvoiceFor() {
        return document.querySelector('input[name="invoice_for"]:checked')?.value || '';
    }

    function setCurrency(currency) {
        clientCurrency = currency || 'INR';
        currencyCodeInput.value = clientCurrency;
        renderItems();
        renderManualItems();
    }

    function setSelectionBadge(text) {
        if (!text) {
            selectionSummary.style.display = 'none';
            selectionSummary.textContent = '';
            return;
        }
        selectionSummary.style.display = 'inline-flex';
        selectionSummary.textContent = text;
    }

    function updateSubmitButtons() {
        manualSubmitBtn.disabled = manualItems.length === 0;
        itemsSubmitBtn.disabled = invoiceItems.length === 0;
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

    function setTotals(subtotal, taxTotal) {
        const grandTotal = subtotal + taxTotal;
        subtotalInput.value = subtotal.toFixed(2);
        taxTotalInput.value = taxTotal.toFixed(2);
        grandTotalInput.value = grandTotal.toFixed(2);
        document.getElementById('subtotalDisplay').textContent = formatMoney(subtotal);
        document.getElementById('taxDisplay').textContent = formatMoney(taxTotal);
        document.getElementById('grandTotalDisplay').textContent = formatMoney(grandTotal);
        document.getElementById('manualSubtotal').textContent = formatMoney(subtotal);
        document.getElementById('manualTaxTotal').textContent = formatMoney(taxTotal);
        document.getElementById('manualGrandTotal').textContent = formatMoney(grandTotal);
    }

    function resetManualItemInputs() {
        document.getElementById('manual_item_itemid').value = '';
        document.getElementById('manual_item_quantity').value = 1;
        document.getElementById('manual_item_unit_price').value = '';
        document.getElementById('manual_item_tax_rate').value = '0';
        document.getElementById('manual_item_frequency').value = '';
        document.getElementById('manual_item_duration').value = '';
        document.getElementById('manual_item_users').value = 1;
        document.getElementById('manual_item_start_date').value = '';
        document.getElementById('manual_item_end_date').value = '';
    }

    function resetSourceData(options = {}) {
        const clearRadio = options.clearRadio ?? true;
        const keepManualItems = options.keepManualItems ?? false;
        invoiceItems = [];
        selectedSourceRecord = null;
        orderIdInput.value = '';
        itemsDataInput.value = '';
        setSelectionBadge('');
        renewalPicker.innerHTML = '';
        renewalPicker.style.display = 'none';
        itemsSection.style.display = 'none';
        ordersSection.style.display = 'none';
        renewalSection.style.display = 'none';
        manualItemsSection.style.display = 'none';
        if (!keepManualItems) {
            manualItems = [];
            manualItemCounter = 0;
            editingManualItemId = null;
            resetManualItemInputs();
            addManualItemBtn.textContent = 'Add Item';
        }
        if (clearRadio) {
            sourceRadios.forEach((radio) => radio.checked = false);
        }
        renderItems();
        renderManualItems();
        updateSubmitButtons();
    }

    function renderClientInvoices(invoices) {
        clientInvoicesBody.innerHTML = '';
        noInvoicesMessage.style.display = invoices.length ? 'none' : 'block';
        invoices.forEach((invoice) => {
            const row = document.createElement('tr');
            row.innerHTML = `<td><strong>${invoice.number}</strong></td><td>${invoice.invoice_type}</td><td>${invoice.invoice_for}</td><td>${invoice.amount}</td><td>${invoice.amount_paid}</td><td>${invoice.balance_due}</td><td><span class="status-pill ${invoice.payment_status}">${invoice.payment_status}</span></td>`;
            clientInvoicesBody.appendChild(row);
        });
    }

    function renderOrders(orders) {
        ordersBody.innerHTML = '';
        noOrdersMessage.style.display = orders.length ? 'none' : 'block';
        orders.forEach((order) => {
            const isSelected = selectedSourceRecord?.type === 'orders' && selectedSourceRecord.id === order.orderid;
            const row = document.createElement('tr');
            row.innerHTML = `<td><strong>${order.order_number}</strong></td><td>${order.order_date}</td><td>${order.currency} ${Number(order.grand_total || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td><td>${order.status}</td><td><button type="button" class="primary-button select-order-btn" data-orderid="${order.orderid}" data-order-number="${order.order_number}" style="padding: 0.45rem 0.9rem; font-size: 0.82rem;">${isSelected ? 'Selected' : 'Use Order'}</button></td>`;
            ordersBody.appendChild(row);
        });
    }

    function renderRenewals(invoices) {
        renewalBody.innerHTML = '';
        noRenewalMessage.style.display = invoices.length ? 'none' : 'block';
        invoices.forEach((invoice) => {
            const row = document.createElement('tr');
            row.innerHTML = `<td><strong>${invoice.invoice_number}</strong></td><td style="color: #ef4444; font-weight: 600;">${invoice.expired_items} <span style="text-transform: uppercase; font-size: 0.75rem;">expired</span></td><td>${invoice.currency} ${Number(invoice.grand_total || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td><td>${invoice.total_items} item(s)</td><td><button type="button" class="primary-button select-renewal-btn" data-invoiceid="${invoice.invoiceid}" data-invoice-number="${invoice.invoice_number}" style="padding: 0.45rem 0.9rem; font-size: 0.82rem;">Review Items</button></td>`;
            renewalBody.appendChild(row);
        });
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
            row.innerHTML = `<td><strong>${item.item_name || 'Item'}</strong></td><td><input type="number" class="form-input invoice-item-input" data-index="${index}" data-field="quantity" min="0.01" step="0.01" value="${item.quantity}"></td><td><input type="number" class="form-input invoice-item-input" data-index="${index}" data-field="unit_price" min="0" step="0.01" value="${item.unit_price}"></td><td>${renderTaxSelect(item.tax_rate, 'invoice-item-input', `data-index="${index}" data-field="tax_rate"` )}</td><td><input type="number" class="form-input invoice-item-input" data-index="${index}" data-field="duration" min="0" step="1" value="${item.duration ?? ''}"></td><td><select class="form-input invoice-item-input" data-index="${index}" data-field="frequency"><option value="">Not recurring</option>${frequencyOptions.map((value) => `<option value="${value}" ${item.frequency === value ? 'selected' : ''}>${frequencyLabels[value]}</option>`).join('')}</select></td><td><input type="number" class="form-input invoice-item-input" data-index="${index}" data-field="no_of_users" min="1" step="1" value="${item.no_of_users || 1}"></td><td><input type="date" class="form-input invoice-item-input" data-index="${index}" data-field="start_date" value="${item.start_date || ''}"></td><td><input type="date" class="form-input invoice-item-input" data-index="${index}" data-field="end_date" value="${item.end_date || ''}"></td><td><strong>${formatMoney(item.line_total + lineTax)}</strong></td><td><button type="button" class="icon-action-btn delete remove-invoice-item" data-index="${index}" title="Remove"><i class="fas fa-trash"></i></button></td>`;
            itemsBody.appendChild(row);
        });
        setTotals(subtotal, taxTotal);
        itemsSection.style.display = invoiceItems.length ? 'block' : 'none';
        updateSubmitButtons();
    }

    function renderManualItems() {
        manualItemsBody.innerHTML = '';
        let subtotal = 0;
        let taxTotal = 0;
        manualItems.forEach((item) => {
            item.quantity = Number(item.quantity) || 0;
            item.unit_price = Number(item.unit_price) || 0;
            item.tax_rate = Number(item.tax_rate) || 0;
            item.no_of_users = Math.max(1, Number(item.no_of_users) || 1);
            item.line_total = calculateLineTotal(item.quantity, item.unit_price, item.no_of_users, item.frequency, item.duration);
            item.tax_amount = item.line_total * (item.tax_rate / 100);
            subtotal += item.line_total;
            taxTotal += item.tax_amount;
            const row = document.createElement('tr');
            row.innerHTML = `<td>${item.item_name}</td><td><input type="number" class="form-input manual-inline-input" data-id="${item.id}" data-field="quantity" min="0.01" step="0.01" value="${item.quantity}"></td><td><input type="number" class="form-input manual-inline-input" data-id="${item.id}" data-field="unit_price" min="0" step="0.01" value="${item.unit_price}"></td><td>${frequencyLabels[item.frequency] || 'Not recurring'}</td><td>${item.duration || '-'}</td><td>${item.no_of_users || 1}</td><td>${item.start_date || '-'}</td><td>${item.end_date || '-'}</td><td><strong>${formatMoney(item.line_total + item.tax_amount)}</strong></td><td><button type="button" class="icon-action-btn edit edit-manual-item" data-id="${item.id}" title="Edit"><i class="fas fa-edit"></i></button> <button type="button" class="icon-action-btn delete remove-manual-item" data-id="${item.id}" title="Remove"><i class="fas fa-trash"></i></button></td>`;
            manualItemsBody.appendChild(row);
        });
        manualItemsTable.style.display = manualItems.length ? 'table' : 'none';
        manualItemsEmpty.style.display = manualItems.length ? 'none' : 'block';
        manualSummary.style.display = manualItems.length ? 'block' : 'none';
        if (getActiveInvoiceFor() === 'without_orders') {
            setTotals(subtotal, taxTotal);
        }
        updateSubmitButtons();
    }
    function readManualItemForm() {
        const itemSelect = document.getElementById('manual_item_itemid');
        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        const itemid = itemSelect.value;
        if (!itemid) {
            alert('Select an item first.');
            return null;
        }
        const item = {
            id: editingManualItemId || ++manualItemCounter,
            itemid,
            item_name: (selectedOption.text || '').split(' (')[0],
            quantity: Number(document.getElementById('manual_item_quantity').value) || 1,
            unit_price: Number(document.getElementById('manual_item_unit_price').value) || 0,
            frequency: document.getElementById('manual_item_frequency').value || null,
            duration: document.getElementById('manual_item_duration').value || null,
            no_of_users: Math.max(1, Number(document.getElementById('manual_item_users').value) || 1),
            start_date: document.getElementById('manual_item_start_date').value || null,
            end_date: document.getElementById('manual_item_end_date').value || null,
            tax_rate: Number(document.getElementById('manual_item_tax_rate').value || selectedOption?.dataset?.taxRate || 0),
        };
        item.line_total = calculateLineTotal(item.quantity, item.unit_price, item.no_of_users, item.frequency, item.duration);
        item.tax_amount = item.line_total * (item.tax_rate / 100);
        return item;
    }

    function loadInvoicesForClient(clientId) {
        clientInvoicesBody.innerHTML = '<tr><td colspan="7" style="padding: 1rem; text-align: center; color: #94a3b8;">Loading invoices...</td></tr>';
        noInvoicesMessage.style.display = 'none';
        fetch(`{{ route('invoices.index') }}?clientid=${clientId}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => response.json())
            .then((data) => renderClientInvoices(data.invoices || []))
            .catch(() => { clientInvoicesBody.innerHTML = ''; noInvoicesMessage.style.display = 'block'; });
    }

    function loadOrders() {
        if (!selectedClientId) return;
        ordersBody.innerHTML = '<tr><td colspan="5" style="padding: 1rem; text-align: center; color: #94a3b8;">Loading orders...</td></tr>';
        noOrdersMessage.style.display = 'none';
        fetch(`{{ route('invoices.client-orders') }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ clientid: selectedClientId }),
        })
            .then((response) => response.json())
            .then((orders) => renderOrders(orders || []))
            .catch(() => { ordersBody.innerHTML = ''; noOrdersMessage.style.display = 'block'; });
    }

    function loadRenewals() {
        if (!selectedClientId) return;
        renewalBody.innerHTML = '<tr><td colspan="5" style="padding: 1rem; text-align: center; color: #94a3b8;">Loading renewal candidates...</td></tr>';
        noRenewalMessage.style.display = 'none';
        fetch(`{{ route('invoices.renewal-invoices') }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ clientid: selectedClientId }),
        })
            .then((response) => response.json())
            .then((invoices) => renderRenewals(invoices || []))
            .catch(() => { renewalBody.innerHTML = ''; noRenewalMessage.style.display = 'block'; });
    }

    function showRenewalPicker(invoiceNumber, items) {
        const expiredItems = (items || []).filter((item) => item.is_expired);
        if (!expiredItems.length) {
            renewalPicker.style.display = 'block';
            renewalPicker.innerHTML = '<div class="empty-state" style="border: 1px solid #e2e8f0; border-radius: 14px;">No expired recurring items were found in this invoice.</div>';
            return;
        }
        renewalPicker.style.display = 'block';
        renewalPicker.innerHTML = `<div style="border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; background: #fff;"><div style="padding: 1rem 1.1rem; border-bottom: 1px solid #e2e8f0; background: #f8fafc;"><h5 style="margin: 0; font-size: 0.95rem; color: #1e293b;">Renew items from ${invoiceNumber}</h5><p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.84rem;">Select the expired items you want to add to the new invoice.</p></div><div style="overflow-x: auto;"><table class="data-table" style="margin: 0; font-size: 0.83rem;"><thead><tr><th></th><th>Item</th><th>Start</th><th>End</th><th>Frequency</th><th>Current Total</th></tr></thead><tbody>${expiredItems.map((item, index) => `<tr><td><label class="custom-checkbox" style="padding: 0.2rem;"><input type="checkbox" class="renewal-item-checkbox" data-index="${index}" checked><span class="checkbox-label" style="display: none;"></span></label></td><td><strong>${item.item_name}</strong></td><td>${item.start_date || '-'}</td><td style="color: #ef4444; font-weight: 600;">${item.end_date || '-'} <span style="font-size: 0.7rem; text-transform: uppercase;">expired</span></td><td>${frequencyLabels[item.frequency] || item.frequency || '-'}</td><td>${formatMoney(item.line_total)}</td></tr>`).join('')}</tbody></table></div><div style="padding: 1rem 1.1rem; border-top: 1px solid #e2e8f0; background: #f8fafc;"><button type="button" id="confirmRenewalBtn" class="primary-button">Add Selected Items</button></div></div>`;
        document.getElementById('confirmRenewalBtn').addEventListener('click', function () {
            const selectedIndexes = Array.from(document.querySelectorAll('.renewal-item-checkbox:checked')).map((checkbox) => Number(checkbox.dataset.index));
            if (!selectedIndexes.length) {
                alert('Select at least one item to renew.');
                return;
            }
            invoiceItems = selectedIndexes.map((index) => {
                const item = { ...expiredItems[index] };
                item.start_date = document.getElementById('issue_date').value || item.start_date || null;
                if (item.frequency && item.duration && item.start_date) {
                    item.end_date = calculateEndDate(item.start_date, item.frequency, item.duration);
                }
                return item;
            });
            selectedSourceRecord = { type: 'renewal', id: invoiceNumber, label: invoiceNumber };
            setSelectionBadge(`Renewal source: ${invoiceNumber}`);
            renderItems();
            itemsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    function activateSource(source) {
        const keepManualItems = source === 'without_orders';
        resetSourceData({ clearRadio: false, keepManualItems });
        if (source === 'orders') {
            ordersSection.style.display = 'block';
            loadOrders();
            return;
        }
        if (source === 'renewal') {
            renewalSection.style.display = 'block';
            loadRenewals();
            return;
        }
        if (source === 'without_orders') {
            manualItemsSection.style.display = 'block';
            renderManualItems();
        }
    }

    clientSelect.addEventListener('change', function () {
        selectedClientId = this.value || null;
        if (!selectedClientId) {
            sourceWorkspace.style.display = 'none';
            setCurrency('INR');
            resetSourceData();
            clientInvoicesBody.innerHTML = '';
            noInvoicesMessage.style.display = 'none';
            return;
        }
        setCurrency(this.options[this.selectedIndex]?.dataset?.currency || 'INR');
        sourceWorkspace.style.display = 'block';
        resetSourceData();
        loadInvoicesForClient(selectedClientId);
    });

    sourceRadios.forEach((radio) => radio.addEventListener('change', function () { activateSource(this.value); }));

    ordersBody.addEventListener('click', function (event) {
        const button = event.target.closest('.select-order-btn');
        if (!button) return;
        const orderId = button.dataset.orderid;
        const orderNumber = button.dataset.orderNumber;
        orderIdInput.value = orderId;
        fetch(`{{ url('/invoices/order-items') }}/${orderId}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => response.json())
            .then((data) => {
                invoiceItems = (data.items || []).map((item) => ({ ...item }));
                selectedSourceRecord = { type: 'orders', id: orderId, label: orderNumber };
                setSelectionBadge(`Order source: ${orderNumber}`);
                renderItems();
                loadOrders();
                itemsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            })
            .catch(() => alert('Unable to load order items right now.'));
    });

    renewalBody.addEventListener('click', function (event) {
        const button = event.target.closest('.select-renewal-btn');
        if (!button) return;
        const invoiceId = button.dataset.invoiceid;
        const invoiceNumber = button.dataset.invoiceNumber;
        fetch(`{{ url('/invoices/renewal-items') }}/${invoiceId}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => response.json())
            .then((data) => { showRenewalPicker(invoiceNumber, data.items || []); renewalPicker.scrollIntoView({ behavior: 'smooth', block: 'start' }); })
            .catch(() => alert('Unable to load renewal items right now.'));
    });
    itemsBody.addEventListener('input', function (event) {
        const input = event.target.closest('.invoice-item-input');
        if (!input) return;
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
        const button = event.target.closest('.remove-invoice-item');
        if (!button) return;
        invoiceItems.splice(Number(button.dataset.index), 1);
        renderItems();
    });

    document.getElementById('manual_item_itemid').addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        document.getElementById('manual_item_unit_price').value = option?.dataset?.sellingPrice || '';
        document.getElementById('manual_item_tax_rate').value = option?.dataset?.taxRate || '0';
    });

    ['manual_item_start_date', 'manual_item_frequency', 'manual_item_duration'].forEach((id) => {
        document.getElementById(id).addEventListener('change', function () {
            const startDate = document.getElementById('manual_item_start_date').value;
            const frequency = document.getElementById('manual_item_frequency').value;
            const duration = document.getElementById('manual_item_duration').value;
            document.getElementById('manual_item_end_date').value = calculateEndDate(startDate, frequency, duration);
        });
    });

    addManualItemBtn.addEventListener('click', function () {
        const item = readManualItemForm();
        if (!item) return;
        const existingIndex = manualItems.findIndex((entry) => entry.id === item.id);
        if (existingIndex >= 0) {
            manualItems[existingIndex] = item;
        } else {
            manualItems.push(item);
        }
        editingManualItemId = null;
        addManualItemBtn.textContent = 'Add Item';
        resetManualItemInputs();
        renderManualItems();
    });

    manualItemsBody.addEventListener('input', function (event) {
        const input = event.target.closest('.manual-inline-input');
        if (!input) return;
        const item = manualItems.find((entry) => entry.id === Number(input.dataset.id));
        if (!item) return;
        item[input.dataset.field] = Number(input.value) || 0;
        renderManualItems();
    });

    manualItemsBody.addEventListener('click', function (event) {
        const editButton = event.target.closest('.edit-manual-item');
        if (editButton) {
            const item = manualItems.find((entry) => entry.id === Number(editButton.dataset.id));
            if (!item) return;
            editingManualItemId = item.id;
            document.getElementById('manual_item_itemid').value = item.itemid;
            document.getElementById('manual_item_quantity').value = item.quantity;
            document.getElementById('manual_item_unit_price').value = item.unit_price;
            document.getElementById('manual_item_tax_rate').value = item.tax_rate || 0;
            document.getElementById('manual_item_frequency').value = item.frequency || '';
            document.getElementById('manual_item_duration').value = item.duration || '';
            document.getElementById('manual_item_users').value = item.no_of_users || 1;
            document.getElementById('manual_item_start_date').value = item.start_date || '';
            document.getElementById('manual_item_end_date').value = item.end_date || '';
            addManualItemBtn.textContent = 'Update Item';
            manualItemsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        const removeButton = event.target.closest('.remove-manual-item');
        if (!removeButton) return;
        const itemId = Number(removeButton.dataset.id);
        manualItems = manualItems.filter((entry) => entry.id !== itemId);
        if (editingManualItemId === itemId) {
            editingManualItemId = null;
            addManualItemBtn.textContent = 'Add Item';
            resetManualItemInputs();
        }
        renderManualItems();
    });

    invoiceForm.addEventListener('submit', function (event) {
        if (!selectedClientId) {
            event.preventDefault();
            alert('Select a client before creating the invoice.');
            return;
        }
        const invoiceFor = getActiveInvoiceFor();
        if (!invoiceFor) {
            event.preventDefault();
            alert('Choose how you want to create this invoice.');
            return;
        }
        let itemsToSubmit = [];
        if (invoiceFor === 'without_orders') {
            if (!manualItems.length) {
                event.preventDefault();
                alert('Add at least one manual item before submitting.');
                return;
            }
            itemsToSubmit = manualItems.map((item) => ({ itemid: item.itemid, item_name: item.item_name, quantity: item.quantity, unit_price: item.unit_price, tax_rate: item.tax_rate || 0, duration: item.duration || null, frequency: item.frequency || null, no_of_users: item.no_of_users || 1, start_date: item.start_date || null, end_date: item.end_date || null, line_total: item.line_total }));
        } else {
            if (!invoiceItems.length) {
                event.preventDefault();
                alert('Select at least one invoice item before submitting.');
                return;
            }
            if (invoiceFor === 'orders' && !orderIdInput.value) {
                event.preventDefault();
                alert('Choose an order before submitting this invoice.');
                return;
            }
            itemsToSubmit = invoiceItems.map((item) => ({ itemid: item.itemid, item_name: item.item_name, quantity: item.quantity, unit_price: item.unit_price, tax_rate: item.tax_rate || 0, duration: item.duration || null, frequency: item.frequency || null, no_of_users: item.no_of_users || 1, start_date: item.start_date || null, end_date: item.end_date || null, line_total: item.line_total }));
        }
        itemsDataInput.value = JSON.stringify(itemsToSubmit);
        document.querySelectorAll('.create-submit-btn').forEach((button) => { button.disabled = true; button.textContent = 'Creating...'; });
    });

    if (selectedClientId) {
        sourceWorkspace.style.display = 'block';
        setCurrency(clientSelect.options[clientSelect.selectedIndex]?.dataset?.currency || 'INR');
        loadInvoicesForClient(selectedClientId);
        if (getActiveInvoiceFor()) {
            activateSource(getActiveInvoiceFor());
        }
    } else {
        renderItems();
        renderManualItems();
    }
})();
</script>
@endsection
