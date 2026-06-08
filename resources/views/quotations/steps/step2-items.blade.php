@php
    $quotationDateBounds = $quotationDateBounds ?? [
        'min_date' => date('Y-m-d'),
        'max_date' => date('Y-m-d'),
        'issue_max_date' => date('Y-m-d'),
        'due_max_date' => date('Y-m-d'),
        'default_issue_date' => '',
        'default_due_date' => '',
    ];
    $accountHasUsers = (bool) ($account->have_users ?? false);
@endphp
<div id="step2" class="row g-3 align-items-stretch">
    <div class="col-12">
        <div class="bg-light p-4 rounded-3 border">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <a href="{{ route('quotations.create', ['step' => 1, 'c' => $clientId]) }}" class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium">
                    <i class="fas fa-arrow-left btn-icon"></i> Back
                </a>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span id="quoNumberBadge" class="badge text-bg-secondary">{{ old('quo_number', $nextQuotationNumber) }}</span>
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge text-bg-primary">1</span>
                        <span class="badge text-bg-primary">2</span>
                        <span class="badge text-bg-light border text-dark">3</span>
                        <span class="badge text-bg-light border text-dark">4</span>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <h5 class="fw-semibold text-black mb-0">{{ $selectedClientName }}</h5>
                @if($selectedClientEmail)
                    <div class="small text-muted">{{ $selectedClientEmail }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="bg-light p-4 rounded-3 border">
            <div class="row g-3">
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Quotation Title</label>
                    <input type="text" id="quo_title" class="form-control" placeholder="e.g. Annual Software Subscription" required>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Issue Date</label>
                    <input type="date" id="issue_date" class="form-control"
                        min="{{ $quotationDateBounds['min_date'] }}"
                        max="{{ $quotationDateBounds['issue_max_date'] ?? $quotationDateBounds['max_date'] }}"
                        value="{{ old('issue_date', $quotationDateBounds['default_issue_date'] ?? date('Y-m-d')) }}" required>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Due Date</label>
                    <input type="date" id="due_date" class="form-control"
                        min="{{ $quotationDateBounds['min_date'] }}"
                        max="{{ $quotationDateBounds['due_max_date'] ?? $quotationDateBounds['max_date'] }}"
                        value="{{ old('due_date', $quotationDateBounds['default_due_date'] ?? date('Y-m-d', strtotime('+7 days'))) }}">
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Notes</label>
                    <textarea id="notes" class="form-control" rows="1" placeholder="Optional notes">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div id="manualItemsSection" class="bg-light p-4 rounded-3 border">
            <div class="mb-3">
                <h5 class="fw-semibold text-black mb-0">Select Items</h5>
                <p class="small text-muted mb-0">Items are loaded from this client's orders.</p>
            </div>
            <div class="bg-white rounded-3 border p-3">
            <div class="manual-grid manual-grid-add-items">
                <div class="invoice-span-2">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Item</label>
                    <select id="itemid" class="form-select">
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
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Qty</label>
                    <input type="number" id="quantity" min="1" step="1" value="1" class="form-control">
                </div>
                <div>
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Unit Price</label>
                    <input type="number" id="unit_price" min="0" step="0.01" class="form-control">
                </div>
                <div>
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Disc %</label>
                    <input type="number" id="discount_percent" min="0" max="100" step="0.01" value="0" class="form-control">
                </div>
                <div id="usersWrap" class="{{ $accountHasUsers ? '' : 'd-none' }}">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Users</label>
                    <input type="number" id="no_of_users" min="1" step="1" value="1" class="form-control">
                </div>
                <div>
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Freq</label>
                    <select id="frequency" class="form-select">
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
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Dur</label>
                    <input type="number" id="duration" min="1" step="1" class="form-control" value="1">
                </div>
                <div id="startDateWrap">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Start</label>
                    <input type="date" id="start_date" class="form-control">
                </div>
                <div id="endDateWrap">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">End</label>
                    <input type="date" id="end_date" class="form-control">
                </div>
            </div>
            <div class="d-flex flex-column flex-md-row gap-2 mt-3">
                <textarea id="item_description" class="form-control" rows="1" placeholder="Description (optional)"></textarea>
                <button type="button" id="addItem" class="btn btn-outline-primary btn-primary text-white fw-medium">Add</button>
            </div>
            </div>

        <div class="card border-0 shadow-sm overflow-hidden mt-3">
            <table class="table mainTable align-middle mb-0 invoice-items-table d-none" id="itemsTable">
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
            <div id="itemsEmpty" class="alert alert-light border mb-0">No items added yet.</div>
        </div>

        <div id="quoteSummary" class="bg-light rounded-3 border p-3 mt-3 ms-auto d-none" style="max-width: 320px;">
            <div class="d-flex justify-content-between"><span>Subtotal</span><strong id="summarySubtotal">0</strong></div>
            <div class="d-flex justify-content-between"><span>Discount</span><strong id="summaryDiscount">0</strong></div>
            <div class="d-flex justify-content-between"><span>Tax</span><strong id="summaryTax">0</strong></div>
            <div class="d-flex justify-content-between fw-semibold border-top pt-2 mt-2"><span>Total</span><strong id="summaryTotal">0</strong></div>
        </div>
    </div>

    <div class="col-12">
        <button type="button" class="btn btn-outline-primary btn-primary text-white fw-medium w-100" id="toStep3">Review & Terms</button>
    </div>
</div>
