@php
    $accountHasUsers = (bool) ($account->have_users ?? false);
@endphp
<div id="step2" class="invoice-step">
    <div class="invoice-client-header">
        <div class="invoice-client-header__row">
            <a href="{{ route('quotations.create', ['step' => 1, 'c' => $clientId]) }}" class="secondary-button invoice-back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="invoice-client-header__divider"></div>
            <div class="invoice-client-header__icon">
                <i class="fas fa-user"></i>
            </div>
            <div class="invoice-client-header__body">
                <div class="invoice-client-header__name">{{ $selectedClientName }}</div>
                @if($selectedClientEmail)
                    <div class="invoice-client-header__email">{{ $selectedClientEmail }}</div>
                @endif
            </div>
            <div class="invoice-client-header__right">
                <div id="quoNumberBadge" class="invoice-number-badge">{{ old('quo_number', $nextQuotationNumber) }}</div>
                <div class="invoice-compact-steps invoice-compact-steps--right" aria-label="Step progress">
                    <span class="invoice-compact-step">1</span>
                    <span class="invoice-compact-step is-active">2</span>
                    <span class="invoice-compact-step">3</span>
                    <span class="invoice-compact-step">4</span>
                </div>
            </div>
        </div>
    </div>

    <div class="invoice-grid-4 mb-3">
        <div class="overflow-visible">
            <label class="field-label">Quotation Title</label>
            <input type="text" id="quo_title" class="form-input" placeholder="e.g. Annual Software Subscription" required>
        </div>
        <div>
            <label class="field-label">Issue Date</label>
            <input type="date" id="issue_date" class="form-input" value="{{ old('issue_date', date('Y-m-d')) }}" required>
        </div>
        <div>
            <label class="field-label">Due Date</label>
            <input type="date" id="due_date" class="form-input" value="{{ old('due_date', date('Y-m-d', strtotime('+7 days'))) }}">
        </div>
        <div>
            <label class="field-label">Notes</label>
            <textarea id="notes" class="form-input invoice-notes-textarea" rows="1" placeholder="Optional notes">{{ old('notes') }}</textarea>
        </div>
    </div>

    <div id="manualItemsSection" class="workflow-panel">
        <div class="panel-heading-row">
            <div>
                <h4 class="panel-heading-title">Select Items</h4>
                <p class="panel-heading-subtitle">Items are loaded from this client's orders.</p>
            </div>
        </div>

        <div class="builder-card invoice-builder-card">
            <div class="manual-grid manual-grid-add-items">
                <div class="invoice-span-2">
                    <label class="field-label small">Item</label>
                    <select id="itemid" class="form-input">
                        <option value="">Select item</option>
                        @php
                            $servicesByCategory = $services->groupBy(function ($service) {
                                return trim((string) ($service->category->name ?? 'Uncategorized')) ?: 'Uncategorized';
                            });
                        @endphp
                        @foreach($servicesByCategory as $categoryName => $categoryServices)
                            <optgroup label="{{ $categoryName }}">
                                @foreach($categoryServices as $service)
                                    @php
                                        $costing = $service->costings->firstWhere('costing_type', 'selling_price') ?? $service->costings->first();
                                    @endphp
                                    <option
                                        value="{{ $service->itemid }}"
                                        data-name="{{ $service->name }}"
                                        data-category="{{ $service->category->name ?? '' }}"
                                        data-description="{{ $service->description ?? '' }}"
                                        data-unit-price="{{ $costing->selling_price ?? 0 }}"
                                        data-tax-rate="{{ $costing->tax_rate ?? 0 }}"
                                        data-user-wise="{{ (int) ($service->user_wise ?? 0) }}"
                                    >
                                        {{ $service->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label small">Qty</label>
                    <input type="number" id="quantity" min="1" step="1" value="1" class="form-input">
                </div>
                <div>
                    <label class="field-label small">Unit Price</label>
                    <input type="number" id="unit_price" min="0" step="0.01" class="form-input">
                </div>
                <div>
                    <label class="field-label small">Disc %</label>
                    <input type="number" id="discount_percent" min="0" max="100" step="0.01" value="0" class="form-input">
                </div>
                <div id="usersWrap" class="{{ $accountHasUsers ? '' : 'd-none' }}">
                    <label class="field-label small">Users</label>
                    <input type="number" id="no_of_users" min="1" step="1" value="1" class="form-input">
                </div>
                <div>
                    <label class="field-label small">Freq</label>
                    <select id="frequency" class="form-input">
                        <option value="">None</option>
                        <option value="One-Time">One-Time</option>
                        <option value="Day(s)">Day(s)</option>
                        <option value="Week(s)">Week(s)</option>
                        <option value="Month(s)">Month(s)</option>
                        <option value="Quarter(s)">Quarter(s)</option>
                        <option value="Year(s)">Year(s)</option>
                    </select>
                </div>
                <div id="durationWrap">
                    <label class="field-label small">Dur</label>
                    <input type="number" id="duration" min="1" step="1" class="form-input" value="1">
                </div>
                <div id="startDateWrap">
                    <label class="field-label small">Start</label>
                    <input type="date" id="start_date" class="form-input">
                </div>
                <div id="endDateWrap">
                    <label class="field-label small">End</label>
                    <input type="date" id="end_date" class="form-input">
                </div>
            </div>
            <div class="invoice-item-desc-row">
                <textarea id="item_description" class="form-input invoice-item-desc-input" rows="1" placeholder="Description (optional)"></textarea>
                <button type="button" id="addItem" class="primary-button invoice-item-add-btn">Add</button>
            </div>
        </div>

        <div class="table-shell mt-3">
            <table class="data-table m-0 invoice-items-table d-none" id="itemsTable">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Price</th>
                        <th class="text-center">Disc %</th>
                        <th class="text-center {{ $accountHasUsers ? '' : 'd-none' }}" id="usersColHeader">Users</th>
                        <th>Freq</th>
                        <th class="text-center">Dur</th>
                        <th>Start</th>
                        <th>End</th>
                        <th class="text-right">Total</th>
                        <th class="text-center"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
            </table>
            <div id="itemsEmpty" class="empty-state">No items added yet.</div>
        </div>

        <div id="quoteSummary" class="totals-card totals-card--narrow mt-3 ms-auto d-none">
            <div class="total-row"><span>Subtotal</span><strong id="summarySubtotal">0</strong></div>
            <div class="total-row"><span>Discount</span><strong id="summaryDiscount">0</strong></div>
            <div class="total-row"><span>Tax</span><strong id="summaryTax">0</strong></div>
            <div class="total-row total-row-grand"><span>Total</span><strong id="summaryTotal">0</strong></div>
        </div>
    </div>

    <div class="mt-4">
        <button type="button" class="primary-button w-100 invoice-next-btn" id="toStep3">Review & Terms &rarr;</button>
    </div>
</div>
