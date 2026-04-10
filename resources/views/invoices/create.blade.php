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
        
        @if ($errors->has('general'))
            <div style="margin-bottom: 1.25rem; padding: 0.9rem 1rem; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; border-radius: 10px;">
                <strong>Error:</strong> {{ $errors->first('general') }}
            </div>
        @endif

        <!-- Step 1: Client & Source Selection -->
        <div id="step1">
            <div class="invoice-meta-card" style="margin-bottom: 1.5rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; align-items: end;">
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
                        <label for="invoice_title" class="field-label">Invoice Title</label>
                        <input type="text" id="invoice_title" name="invoice_title" value="{{ old('invoice_title') }}" class="form-input" placeholder="e.g. Website Development - Phase 1">
                    </div>
                </div>
            </div>

            <div id="existingInvoicesSection" style="display: none; margin-bottom: 1.5rem;">
                <h4 style="margin: 0 0 0.8rem 0; font-size: 1rem; color: #334155;">Existing Invoices</h4>
                <div id="clientInvoicesAccordion" class="services-accordion-container">
                    <!-- Accordion items will be injected here -->
                </div>
                <div id="noInvoicesMessage" class="empty-state" style="display: none;">No invoices found for this client yet.</div>
            </div>

            <div id="sourceSelectionSection" style="display: none; margin-bottom: 1.5rem;">
                <div style="margin-bottom: 1rem; padding: 1rem 1.1rem; border: 1px solid #dbeafe; background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%); border-radius: 12px;">
                    <h4 style="margin: 0; font-size: 1rem; color: #1e293b;">Choose Invoice Source</h4>
                </div>
                
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

                <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
                    <button type="button" id="btnNextToStep2" class="primary-button" style="padding: 0.8rem 2.5rem; font-size: 1rem;">Next Step &rarr;</button>
                </div>
            </div>
        </div>

        <!-- Step 2: Items & Details -->
        <div id="step2" style="display: none;">
            <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                <button type="button" id="btnBackToStep1" class="secondary-button" style="padding: 0.5rem 1rem;">&larr; Back to Step 1</button>
                <div style="text-align: right;">
                    <span class="invoice-meta-label">Invoice Number</span>
                    <strong class="invoice-meta-value">{{ $nextInvoiceNumber }}</strong>
                    <input type="hidden" name="invoice_number" value="{{ $nextInvoiceNumber }}">
                </div>
            </div>

            <input type="hidden" name="orderid" id="orderid" value="{{ old('orderid', '') }}">
            <input type="hidden" name="invoice_type" value="proforma">
            <input type="hidden" name="status" value="draft">
            <input type="hidden" name="currency_code" id="currency_code" value="{{ old('currency_code', 'INR') }}">
            <input type="hidden" name="subtotal" id="subtotal" value="{{ old('subtotal', '0.00') }}">
            <input type="hidden" name="tax_total" id="tax_total" value="{{ old('tax_total', '0.00') }}">
            <input type="hidden" name="grand_total" id="grand_total" value="{{ old('grand_total', '0.00') }}">
            <input type="hidden" name="items_data" id="items_data" value="{{ old('items_data', '') }}">

            <div id="ordersSection" class="workflow-panel" style="display: none;">
                <div class="panel-heading-row">
                    <div>
                        <h4 style="margin: 0; font-size: 1rem; color: #334155;">Available Orders</h4>
                        <p style="margin: 0.2rem 0 0 0; color: #64748b; font-size: 0.85rem;">Choose a pending order to pull its items into the invoice.</p>
                    </div>
                </div>
                <div class="table-shell">
                    <table class="data-table" id="ordersTable" style="font-size: 0.85rem; margin: 0;">
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
                    <table class="data-table" id="renewalTable" style="font-size: 0.85rem; margin: 0;">
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
                        <p style="margin: 0.2rem 0 0 0; color: #64748b; font-size: 0.85rem;">Add items to your invoice. You can edit them after adding.</p>
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
                        @if($account->allow_multi_taxation)
                        <div>
                            <label for="manual_item_tax_rate" class="field-label small">Tax <a href="#" id="open-tax-modal-invoice" style="font-size:11px;margin-left:4px;" class="text-link">+ Add</a></label>
                            <select id="manual_item_tax_rate" class="form-input">
                                <option value="0">No Tax</option>
                                @foreach($taxes as $tax)
                                    <option value="{{ $tax->rate }}">{{ $tax->tax_name }} ({{ number_format($tax->rate, 2) }}%)</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <input type="hidden" id="manual_item_tax_rate" value="{{ $account->fixed_tax_rate ?? 0 }}">
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
                        @if($account->have_users)
                        <div>
                            <label for="manual_item_users" class="field-label small">Users</label>
                            <input type="number" id="manual_item_users" class="form-input" value="1" min="1" step="1">
                        </div>
                        @else
                        <input type="hidden" id="manual_item_users" value="1">
                        @endif
                        <div>
                            <label for="manual_item_start_date" class="field-label small">Start</label>
                            <input type="date" id="manual_item_start_date" class="form-input">
                        </div>
                        <div>
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
                                <th>Freq</th>
                                <th>Dur</th>
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
            </div>

            <div id="itemsSection" class="workflow-panel" style="display: none;">
                <div class="panel-heading-row">
                    <div>
                        <h4 style="margin: 0; font-size: 1rem; color: #334155;">Review Invoice Items</h4>
                        <p style="margin: 0.2rem 0 0 0; color: #64748b; font-size: 0.85rem;">Adjust pricing, tax, duration, or dates before creating.</p>
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
                                <th>Dur</th>
                                <th>Freq</th>
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

            <div style="margin-top: 2rem;">
                <button type="submit" class="primary-button create-submit-btn" id="finalSubmitBtn" disabled style="width: 100%; padding: 1rem;">Create Invoice</button>
            </div>
        </div>
    </form>
</section>

<!-- Modal for Editing Invoices -->
<div id="editInvoiceModal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3 id="modalTitle">Edit Invoice</h3>
            <button type="button" class="close-modal-btn">&times;</button>
        </div>
        <div class="modal-body">
            <iframe id="editInvoiceIframe" src="" style="width: 100%; height: 80vh; border: none;"></iframe>
        </div>
    </div>
</div>

<style>
.invoice-meta-card { padding: 1.25rem; border: 1px solid #e2e8f0; border-radius: 12px; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); }
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

.status-pill.paid { background: #dcfce7; color: #166534; }
.status-pill.unpaid { background: #fee2e2; color: #991b1b; }
.status-pill.partially-paid { background: #fef3c7; color: #92400e; }

/* Modal Styles */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.modal-container { background: #fff; width: 90%; max-width: 1200px; border-radius: 14px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; }
.modal-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
.modal-header h3 { margin: 0; font-size: 1.1rem; color: #1e293b; }
.close-modal-btn { background: none; border: none; font-size: 1.75rem; cursor: pointer; color: #64748b; line-height: 1; }
.close-modal-btn:hover { color: #1e293b; }
.modal-body { padding: 0; }

@media (max-width: 1100px) { .manual-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 720px) { .manual-grid { grid-template-columns: 1fr; } }
</style>

<script>
(function () {
    // DOM Elements
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const clientSelect = document.getElementById('clientid');
    const invoiceForm = document.getElementById('invoiceForm');
    const existingInvoicesSection = document.getElementById('existingInvoicesSection');
    const clientInvoicesAccordion = document.getElementById('clientInvoicesAccordion');
    const noInvoicesMessage = document.getElementById('noInvoicesMessage');
    const sourceSelectionSection = document.getElementById('sourceSelectionSection');
    const btnNextToStep2 = document.getElementById('btnNextToStep2');
    const btnBackToStep1 = document.getElementById('btnBackToStep1');
    const sourceRadios = document.querySelectorAll('input[name="invoice_for"]');
    
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
    const finalSubmitBtn = document.getElementById('finalSubmitBtn');
    
    const orderIdInput = document.getElementById('orderid');
    const subtotalInput = document.getElementById('subtotal');
    const taxTotalInput = document.getElementById('tax_total');
    const grandTotalInput = document.getElementById('grand_total');
    const itemsDataInput = document.getElementById('items_data');
    const currencyCodeInput = document.getElementById('currency_code');
    const addManualItemBtn = document.getElementById('addManualItemBtn');

    // Modals
    const editInvoiceModal = document.getElementById('editInvoiceModal');
    const editInvoiceIframe = document.getElementById('editInvoiceIframe');
    const closeModalBtn = document.querySelector('.close-modal-btn');

    // Constants
    const frequencyOptions = ['one-time', 'daily', 'weekly', 'bi-weekly', 'monthly', 'quarterly', 'semi-annually', 'yearly'];
    const frequencyLabels = { 'one-time': 'One-Time', 'daily': 'Daily', 'weekly': 'Weekly', 'bi-weekly': 'Bi-Weekly', 'monthly': 'Monthly', 'quarterly': 'Quarterly', 'semi-annually': 'Semi-Annually', 'yearly': 'Yearly' };
    const taxOptions = @json(($taxes ?? collect())->map(fn ($tax) => ['name' => $tax->tax_name, 'rate' => (float) $tax->rate])->values());

    // State
    let selectedClientId = clientSelect.value || null;
    let clientCurrency = currencyCodeInput.value || 'INR';
    let invoiceItems = [];
    let manualItems = [];
    let manualItemCounter = 0;
    let editingManualItemId = null;

    // Helper Functions
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
        return `<select class="form-input ${inputClass}" ${attributes}>${options.join('')}</select>`;
    }

    function calculateLineTotal(quantity, unitPrice, users, frequency, duration) {
        let total = (Number(quantity) || 0) * (Number(unitPrice) || 0) * Math.max(1, Number(users) || 1);
        if (frequency && frequency !== 'one-time' && duration) {
            const durationNumber = Number(duration) || 0;
            if (durationNumber > 0) total *= durationNumber;
        }
        return total;
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

    function updateFinalSubmitButton() {
        const source = document.querySelector('input[name="invoice_for"]:checked')?.value;
        if (source === 'without_orders') {
            finalSubmitBtn.disabled = manualItems.length === 0;
        } else {
            finalSubmitBtn.disabled = invoiceItems.length === 0;
        }
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

    // Step 1 Functions
    function loadInvoicesForClient(clientId) {
        clientInvoicesAccordion.innerHTML = '<div style="padding: 1rem; text-align: center; color: #94a3b8;">Loading invoices...</div>';
        noInvoicesMessage.style.display = 'none';
        
        fetch(`{{ route('invoices.index') }}?clientid=${clientId}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => response.json())
            .then((data) => {
                const invoices = data.invoices || [];
                if (invoices.length === 0) {
                    clientInvoicesAccordion.innerHTML = '';
                    noInvoicesMessage.style.display = 'block';
                    return;
                }

                let accordionHtml = '';
                invoices.forEach((invoice) => {
                    const paymentStatus = invoice.payment_status || 'unpaid';
                    const paymentStatusClass = paymentStatus.toLowerCase().replace(/\s+/g, '-');
                    const headerTitle = invoice.title ? `${invoice.title} (${invoice.number || ''})` : (invoice.number || 'Untitled Invoice');
                    const issueDate = invoice.issue_date || '-';
                    const dueDate = invoice.due_date || '-';
                    const itemsHtml = (invoice.items || []).map((item) => `
                        <div style="padding: 0.6rem 0; border-bottom: 1px dashed #e2e8f0; font-size: 0.8rem;">
                            <div style="display: flex; justify-content: space-between; gap: 0.75rem;">
                                <span style="color: #334155; font-weight: 600;">${item.name || 'Item'} (x${item.qty || item.quantity || 1})</span>
                                <strong style="color: #1e293b;">${item.total || '-'}</strong>
                            </div>
                            <div style="margin-top: 0.25rem; color: #64748b; font-size: 0.74rem;">
                                Unit: ${item.price || '-'} | Tax: ${item.tax_rate ?? 0}% | Users: ${item.users ?? 1} | Freq: ${item.frequency || '-'} | Dur: ${item.duration || '-'}
                            </div>
                            <div style="margin-top: 0.18rem; color: #94a3b8; font-size: 0.72rem;">
                                Start: ${item.start_date || '-'} | End: ${item.end_date || '-'}
                            </div>
                        </div>
                    `).join('') || '<div style="padding: 0.75rem 0; color: #94a3b8; font-style: italic; font-size: 0.82rem;">No items found</div>';

                    accordionHtml += `
                        <details class="category-accordion">
                            <summary class="accordion-header" style="padding: 0.65rem 0.9rem;">
                                <span style="display: inline-flex; flex-direction: column; gap: 0.1rem;">
                                    <span class="category-title" style="font-size: 0.84rem;">${headerTitle}</span>
                                    <span style="font-size: 0.72rem; color: #64748b; font-weight: 500;">Issue: ${issueDate}</span>
                                </span>
                                <span style="display: inline-flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end;">
                                    <span class="service-count">${invoice.amount || '-'}</span>
                                    <span class="status-pill ${paymentStatusClass}" style="font-size: 0.72rem;">${paymentStatus}</span>
                                    <span class="accordion-icon"></span>
                                </span>
                            </summary>
                            <div class="accordion-content">
                                <div style="padding: 0.65rem 0.9rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 0.8rem; flex-wrap: wrap;">
                                    <span style="font-size: 0.74rem; color: #64748b;">Issue: ${issueDate} | Due: ${dueDate} | Type: ${invoice.invoice_type || '-'} | For: ${invoice.invoice_for || '-'}</span>
                                    <button type="button" class="text-link edit-invoice-btn" data-id="${invoice.record_id}" style="font-size: 0.82rem; font-weight: 600;">Edit Invoice</button>
                                </div>
                                <div style="padding: 0.3rem 0.9rem 0.8rem;">
                                    ${itemsHtml}
                                </div>
                            </div>
                        </details>
                    `;
                });

                clientInvoicesAccordion.innerHTML = accordionHtml;

                // Edit invoice button
                document.querySelectorAll('.edit-invoice-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const invoiceId = btn.dataset.id;
                        editInvoiceIframe.src = `{{ url('/invoices') }}/${invoiceId}/edit`;
                        editInvoiceModal.style.display = 'flex';
                    });
                });
            })
            .catch(() => {
                clientInvoicesAccordion.innerHTML = '<div style="padding: 1rem; text-align: center; color: #ef4444;">Failed to load invoices.</div>';
            });
    }

    // Modal close
    closeModalBtn.addEventListener('click', () => {
        editInvoiceModal.style.display = 'none';
        editInvoiceIframe.src = '';
        if (selectedClientId) loadInvoicesForClient(selectedClientId);
    });

    window.addEventListener('click', (e) => {
        if (e.target === editInvoiceModal) {
            editInvoiceModal.style.display = 'none';
            editInvoiceIframe.src = '';
            if (selectedClientId) loadInvoicesForClient(selectedClientId);
        }
    });

    clientSelect.addEventListener('change', function () {
        selectedClientId = this.value || null;
        if (!selectedClientId) {
            existingInvoicesSection.style.display = 'none';
            sourceSelectionSection.style.display = 'none';
            return;
        }
        clientCurrency = this.options[this.selectedIndex]?.dataset?.currency || 'INR';
        currencyCodeInput.value = clientCurrency;
        existingInvoicesSection.style.display = 'block';
        sourceSelectionSection.style.display = 'block';
        loadInvoicesForClient(selectedClientId);
    });

    btnNextToStep2.addEventListener('click', () => {
        if (!selectedClientId) {
            alert('Please select a client first.');
            return;
        }
        const source = document.querySelector('input[name="invoice_for"]:checked')?.value;
        if (!source) {
            alert('Please choose an invoice source.');
            return;
        }

        step1.style.display = 'none';
        step2.style.display = 'block';
        activateSource(source);
        window.scrollTo(0, 0);
    });

    btnBackToStep1.addEventListener('click', () => {
        step2.style.display = 'none';
        step1.style.display = 'block';
        window.scrollTo(0, 0);
    });

    // Step 2 Functions
    function activateSource(source) {
        ordersSection.style.display = 'none';
        renewalSection.style.display = 'none';
        manualItemsSection.style.display = 'none';
        itemsSection.style.display = 'none';

        if (source === 'orders') {
            ordersSection.style.display = 'block';
            loadOrders();
        } else if (source === 'renewal') {
            renewalSection.style.display = 'block';
            loadRenewals();
        } else if (source === 'without_orders') {
            manualItemsSection.style.display = 'block';
            renderManualItems();
        }
    }

    function loadOrders() {
        ordersBody.innerHTML = '<tr><td colspan="5" class="empty-state">Loading orders...</td></tr>';
        fetch(`{{ route('invoices.client-orders') }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ clientid: selectedClientId }),
        })
            .then(res => res.json())
            .then(orders => {
                ordersBody.innerHTML = '';
                if (orders.length === 0) {
                    noOrdersMessage.style.display = 'block';
                    return;
                }
                noOrdersMessage.style.display = 'none';
                orders.forEach(order => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td><strong>${order.order_number}</strong></td>
                        <td>${order.order_date}</td>
                        <td>${order.currency} ${Number(order.grand_total).toLocaleString()}</td>
                        <td>${order.status}</td>
                        <td><button type="button" class="primary-button select-order-btn" data-id="${order.orderid}" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Select</button></td>
                    `;
                    ordersBody.appendChild(row);
                });
            });
    }

    ordersBody.addEventListener('click', (e) => {
        const btn = e.target.closest('.select-order-btn');
        if (!btn) return;
        const orderId = btn.dataset.id;
        orderIdInput.value = orderId;
        fetch(`{{ url('/invoices/order-items') }}/${orderId}`)
            .then(res => res.json())
            .then(data => {
                invoiceItems = data.items || [];
                renderItems();
                itemsSection.scrollIntoView({ behavior: 'smooth' });
            });
    });

    function loadRenewals() {
        renewalBody.innerHTML = '<tr><td colspan="5" class="empty-state">Loading renewal candidates...</td></tr>';
        fetch(`{{ route('invoices.renewal-invoices') }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ clientid: selectedClientId }),
        })
            .then(res => res.json())
            .then(invoices => {
                renewalBody.innerHTML = '';
                if (invoices.length === 0) {
                    noRenewalMessage.style.display = 'block';
                    return;
                }
                noRenewalMessage.style.display = 'none';
                invoices.forEach(inv => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td><strong>${inv.invoice_number}</strong></td>
                        <td style="color: #ef4444; font-weight: 600;">${inv.expired_items} Expired</td>
                        <td>${inv.currency} ${Number(inv.grand_total).toLocaleString()}</td>
                        <td>${inv.total_items} items</td>
                        <td><button type="button" class="primary-button select-renewal-btn" data-id="${inv.invoiceid}" data-num="${inv.invoice_number}" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Review</button></td>
                    `;
                    renewalBody.appendChild(row);
                });
            });
    }

    renewalBody.addEventListener('click', (e) => {
        const btn = e.target.closest('.select-renewal-btn');
        if (!btn) return;
        const invId = btn.dataset.id;
        fetch(`{{ url('/invoices/renewal-items') }}/${invId}`)
            .then(res => res.json())
            .then(data => {
                showRenewalPicker(btn.dataset.num, data.items || []);
                renewalPicker.scrollIntoView({ behavior: 'smooth' });
            });
    });

    function showRenewalPicker(invNum, items) {
        const expired = items.filter(i => i.is_expired);
        renewalPicker.style.display = 'block';
        renewalPicker.innerHTML = `
            <div style="border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; overflow: hidden;">
                <div style="padding: 1rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                    <h5 style="margin: 0;">Renew from ${invNum}</h5>
                </div>
                <table class="data-table" style="margin: 0; font-size: 0.8rem;">
                    <thead><tr><th></th><th>Item</th><th>End Date</th><th>Price</th></tr></thead>
                    <tbody>
                        ${expired.map((i, idx) => `
                            <tr>
                                <td><input type="checkbox" class="renewal-check" data-idx="${idx}" checked></td>
                                <td>${i.item_name}</td>
                                <td style="color: #ef4444;">${i.end_date}</td>
                                <td>${formatMoney(i.line_total)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                <div style="padding: 1rem; border-top: 1px solid #e2e8f0; text-align: right;">
                    <button type="button" id="btnConfirmRenewal" class="primary-button">Add Selected Items</button>
                </div>
            </div>
        `;
        document.getElementById('btnConfirmRenewal').addEventListener('click', () => {
            const checks = document.querySelectorAll('.renewal-check:checked');
            invoiceItems = Array.from(checks).map(c => {
                const item = { ...expired[c.dataset.idx] };
                item.start_date = document.getElementById('issue_date').value;
                if (item.frequency && item.duration) item.end_date = calculateEndDate(item.start_date, item.frequency, item.duration);
                return item;
            });
            renderItems();
        });
    }

    function renderItems() {
        itemsBody.innerHTML = '';
        let subtotal = 0, taxTotal = 0;
        invoiceItems.forEach((item, idx) => {
            const lineTotal = calculateLineTotal(item.quantity, item.unit_price, item.no_of_users, item.frequency, item.duration);
            const lineTax = lineTotal * (Number(item.tax_rate || 0) / 100);
            subtotal += lineTotal;
            taxTotal += lineTax;
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>${item.item_name}</strong></td>
                <td><input type="number" class="form-input item-edit" data-idx="${idx}" data-field="quantity" value="${item.quantity}" min="0.01" step="0.01"></td>
                <td><input type="number" class="form-input item-edit" data-idx="${idx}" data-field="unit_price" value="${item.unit_price}"></td>
                <td>${renderTaxSelect(item.tax_rate, 'item-edit', `data-idx="${idx}" data-field="tax_rate"`)}</td>
                <td><input type="number" class="form-input item-edit" data-idx="${idx}" data-field="duration" value="${item.duration || ''}"></td>
                <td>
                    <select class="form-input item-edit" data-idx="${idx}" data-field="frequency">
                        <option value="">None</option>
                        ${frequencyOptions.map(f => `<option value="${f}" ${item.frequency === f ? 'selected' : ''}>${frequencyLabels[f] || f}</option>`).join('')}
                    </select>
                </td>
                <td><input type="number" class="form-input item-edit" data-idx="${idx}" data-field="no_of_users" value="${item.no_of_users || 1}"></td>
                <td><input type="date" class="form-input item-edit" data-idx="${idx}" data-field="start_date" value="${item.start_date || ''}"></td>
                <td><input type="date" class="form-input item-edit" data-idx="${idx}" data-field="end_date" value="${item.end_date || ''}"></td>
                <td><strong>${formatMoney(lineTotal + lineTax)}</strong></td>
                <td><button type="button" class="icon-action-btn delete remove-item" data-idx="${idx}"><i class="fas fa-trash"></i></button></td>
            `;
            itemsBody.appendChild(row);
        });
        setTotals(subtotal, taxTotal);
        itemsSection.style.display = invoiceItems.length ? 'block' : 'none';
        updateFinalSubmitButton();
    }

    itemsBody.addEventListener('input', (e) => {
        const input = e.target.closest('.item-edit');
        if (!input) return;
        const idx = input.dataset.idx;
        const field = input.dataset.field;
        invoiceItems[idx][field] = input.type === 'number' ? Number(input.value) : input.value;
        if (['start_date', 'frequency', 'duration'].includes(field)) {
            invoiceItems[idx].end_date = calculateEndDate(invoiceItems[idx].start_date, invoiceItems[idx].frequency, invoiceItems[idx].duration);
        }
        renderItems();
    });

    itemsBody.addEventListener('click', (e) => {
        const btn = e.target.closest('.remove-item');
        if (!btn) return;
        invoiceItems.splice(btn.dataset.idx, 1);
        renderItems();
    });

    // Manual Items
    document.getElementById('manual_item_itemid').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        document.getElementById('manual_item_unit_price').value = opt.dataset.sellingPrice || '';
        document.getElementById('manual_item_tax_rate').value = opt.dataset.taxRate || '0';
    });

    addManualItemBtn.addEventListener('click', () => {
        const select = document.getElementById('manual_item_itemid');
        if (!select.value) return alert('Select an item');
        const opt = select.options[select.selectedIndex];
        const item = {
            id: ++manualItemCounter,
            itemid: select.value,
            item_name: opt.text.split(' (')[0],
            quantity: Number(document.getElementById('manual_item_quantity').value),
            unit_price: Number(document.getElementById('manual_item_unit_price').value),
            tax_rate: Number(document.getElementById('manual_item_tax_rate').value),
            frequency: document.getElementById('manual_item_frequency').value,
            duration: document.getElementById('manual_item_duration').value,
            no_of_users: Number(document.getElementById('manual_item_users').value),
            start_date: document.getElementById('manual_item_start_date').value,
            end_date: document.getElementById('manual_item_end_date').value,
        };
        manualItems.push(item);
        renderManualItems();
        // Reset inputs
        select.value = '';
        document.getElementById('manual_item_unit_price').value = '';
    });

    function renderManualItems() {
        manualItemsBody.innerHTML = '';
        let subtotal = 0, taxTotal = 0;
        manualItems.forEach((item, idx) => {
            const lineTotal = calculateLineTotal(item.quantity, item.unit_price, item.no_of_users, item.frequency, item.duration);
            const lineTax = lineTotal * (item.tax_rate / 100);
            subtotal += lineTotal;
            taxTotal += lineTax;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.item_name}</td>
                <td><input type="number" class="form-input manual-edit" data-idx="${idx}" data-field="quantity" value="${item.quantity}"></td>
                <td><input type="number" class="form-input manual-edit" data-idx="${idx}" data-field="unit_price" value="${item.unit_price}"></td>
                <td>${item.frequency || '-'}</td><td>${item.duration || '-'}</td><td>${item.no_of_users}</td>
                <td>${item.start_date || '-'}</td><td>${item.end_date || '-'}</td>
                <td><strong>${formatMoney(lineTotal + lineTax)}</strong></td>
                <td><button type="button" class="icon-action-btn delete remove-manual" data-idx="${idx}"><i class="fas fa-trash"></i></button></td>
            `;
            manualItemsBody.appendChild(row);
        });
        manualItemsTable.style.display = manualItems.length ? 'table' : 'none';
        manualItemsEmpty.style.display = manualItems.length ? 'none' : 'block';
        manualSummary.style.display = manualItems.length ? 'block' : 'none';
        setTotals(subtotal, taxTotal);
        updateFinalSubmitButton();
    }

    manualItemsBody.addEventListener('input', (e) => {
        const input = e.target.closest('.manual-edit');
        if (!input) return;
        manualItems[input.dataset.idx][input.dataset.field] = Number(input.value);
        renderManualItems();
    });

    manualItemsBody.addEventListener('click', (e) => {
        const btn = e.target.closest('.remove-manual');
        if (!btn) return;
        manualItems.splice(btn.dataset.idx, 1);
        renderManualItems();
    });

    invoiceForm.addEventListener('submit', (e) => {
        const source = document.querySelector('input[name="invoice_for"]:checked').value;
        const items = source === 'without_orders' ? manualItems : invoiceItems;
        itemsDataInput.value = JSON.stringify(items.map(i => ({ ...i, line_total: calculateLineTotal(i.quantity, i.unit_price, i.no_of_users, i.frequency, i.duration) })));
    });

    // Auto-calculate end date for manual form
    ['manual_item_start_date', 'manual_item_frequency', 'manual_item_duration'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => {
            document.getElementById('manual_item_end_date').value = calculateEndDate(
                document.getElementById('manual_item_start_date').value,
                document.getElementById('manual_item_frequency').value,
                document.getElementById('manual_item_duration').value
            );
        });
    });

    if (selectedClientId) {
        existingInvoicesSection.style.display = 'block';
        sourceSelectionSection.style.display = 'block';
        loadInvoicesForClient(selectedClientId);
    }
})();
</script>

{{-- Add Tax Modal --}}
@if($account->allow_multi_taxation)
<div class="modal fade" id="addTaxModalInvoice" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header" style="padding: 0.75rem 1.25rem; border-bottom: 1px solid #e5e7eb;">
                <h5 class="modal-title" style="font-size: 1rem; font-weight: 600;">
                    <i class="fas fa-receipt" style="margin-right: 0.5rem; color: #64748b;"></i>Add Tax
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 1.25rem;">
                <form method="POST" action="{{ route('taxes.store') }}" id="quick-tax-form-invoice">
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
    const taxModalEl = document.getElementById('addTaxModalInvoice');
    const openTaxModalLink = document.getElementById('open-tax-modal-invoice');
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

@endsection
