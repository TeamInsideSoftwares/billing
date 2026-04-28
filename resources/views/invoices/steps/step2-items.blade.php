<!-- Step 2: Items & Details -->
<div id="step2" class="invoice-step" style="display: none;">
    <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
        <button type="button" id="btnBackToStep1" class="secondary-button" style="padding: 0.5rem 1rem;">&larr; Back to Step 1</button>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span style="font-size: 0.75rem; padding: 0.3rem 0.7rem; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; border-radius: 20px; font-weight: 600; border: 1px solid #f59e0b;">
                <i class="fas fa-file-invoice" style="margin-right: 0.3rem;"></i>Invoice
            </span>
            <div class="text-right">
                <span class="invoice-meta-label">Invoice Number</span>
                <strong class="invoice-meta-value">{{ $invoice?->pi_number ?? $nextInvoiceNumber }}</strong>
                <input type="hidden" name="invoice_number" value="{{ $invoice?->pi_number ?? $nextInvoiceNumber }}">
            </div>
        </div>
    </div>

    <div class="invoice-grid-4" style="margin-bottom: 1rem;">
        <div class="invoice-span-3">
        <label for="invoice_title" class="field-label">Invoice Title</label>
        <input type="text" id="invoice_title" name="invoice_title" value="{{ old('invoice_title') }}" class="form-input" placeholder="e.g. Website Development - Monthly Subscription">
        </div>
    </div>

    <input type="hidden" name="invoiceid" id="invoiceid" value="{{ request('d', '') }}">
    <input type="hidden" name="orderid" id="orderid" value="{{ old('orderid', '') }}">
    <input type="hidden" name="status" value="active">
    <input type="hidden" name="currency_code" id="currency_code" value="{{ old('currency_code', 'INR') }}">
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
                        <th>Order</th>
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
        <div class="panel-heading-row" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h4 style="margin: 0; font-size: 1rem; color: #334155;">Renewal Candidates</h4>
                <p style="margin: 0.2rem 0 0 0; color: #64748b; font-size: 0.85rem;">Pick a previous invoice, then select the expired or upcoming recurring items to renew.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <label style="font-size: 0.82rem; color: #64748b; font-weight: 500;">Show upcoming:</label>
                <select id="renewalDaysFilter" class="form-input" style="width: auto; min-width: 160px; padding: 0.4rem 0.75rem; font-size: 0.82rem;">
                    <option value="1" selected>Tomorrow</option>
                    <option value="7">Next 7 Days</option>
                    <option value="14">Next 14 Days</option>
                    <option value="30">Next 30 Days</option>
                    <option value="60">Next 60 Days</option>
                    <option value="90">Next 90 Days</option>
                </select>
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
                                    <option value="{{ $service->itemid }}" data-selling-price="{{ $defaultCosting?->selling_price ?? 0 }}" data-tax-rate="{{ $defaultCosting?->tax_rate ?? 0 }}" data-user-wise="{{ (int) ($service->user_wise ?? 0) }}">
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
                @if($account->allow_multi_taxation)
                <div>
                    <label for="manual_item_tax_rate" class="field-label small">Tax <a href="#" id="open-tax-modal-invoice" style="font-size:11px;margin-left:4px;" class="text-link">+ Add</a></label>
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
                <div id="manual_item_start_date_wrap" style="display: none;">
                    <label for="manual_item_start_date" class="field-label small">Start Date</label>
                    <input type="date" id="manual_item_start_date" class="form-input">
                </div>
                <div id="manual_item_end_date_wrap" style="display: none;">
                    <label for="manual_item_end_date" class="field-label small">End Date</label>
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
                        @if($account->allow_multi_taxation)
                        <th>Tax %</th>
                        @endif
                        @if($account->have_users)
                        <th>Users</th>
                        @endif
                        <th>Freq</th>
                        <th>Dur</th>
                        <th id="manualHeaderStart">Start</th>
                        <th id="manualHeaderEnd">End</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="manualItemsBody"></tbody>
            </table>
            <div id="manualItemsEmpty" class="empty-state">No items added yet. Add an item above to get started.</div>
        </div>

        <div id="manualOrderSummary" class="totals-card" style="display: none; margin-top: 1rem;">
            <div class="total-row"><span>Subtotal</span><strong id="manualSubtotal">0</strong></div>
            <div class="total-row"><span>Tax</span><strong id="manualTaxTotal">0</strong></div>
            <div class="total-row total-row-grand"><span>Total</span><strong id="manualGrandTotal">0</strong></div>
        </div>
    </div>

    <div id="itemsSection" class="workflow-panel" style="display: none;">
        <div class="panel-heading-row">
            <div>
                <h4 style="margin: 0; font-size: 1rem; color: #334155;">Review Invoice Items</h4>
                <p style="margin: 0.2rem 0 0 0; color: #64748b; font-size: 0.85rem;">Adjust pricing, tax, duration, or dates before creating.</p>
            </div>
            <button type="button" id="saveStateBtn" class="primary-button" style="padding: 0.6rem 1.5rem; font-size: 0.9rem;">
                <i class="fas fa-save" class="icon-spaced"></i>Save Progress
            </button>
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
                        @if($account->have_users)
                        <th>Users</th>
                        @endif
                        <th>Freq</th>
                        <th>Dur</th>
                        <th id="headerStart">Start</th>
                        <th id="headerEnd">End</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
            </table>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
            <div class="totals-card" style="min-width: 320px;">
                <div class="total-row"><span>Subtotal</span><strong id="subtotalDisplay">0</strong></div>
                <div class="total-row"><span>Tax</span><strong id="taxDisplay">0</strong></div>
                <div class="total-row total-row-grand"><span>Grand Total</span><strong id="grandTotalDisplay">0</strong></div>
            </div>
        </div>
    </div>

    {{-- Issue/Due date hidden for now --}}
    <input type="hidden" id="issue_date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}">
    <input type="hidden" id="due_date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+7 days'))) }}">
    <input type="hidden" id="notes" name="notes" value="{{ old('notes') }}">

    <div style="margin-top: 2rem;">
        <button type="button" class="primary-button" id="btnNextToStep3" disabled style="width: 100%; padding: 1rem;">Review & Terms &rarr;</button>
    </div>
</div>
