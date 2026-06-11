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
<!-- Step 2: Select Items -->
<div id="step2">
    {{-- Client Info Header with Back Button --}}
    <div class="d-flex align-items-center bg-light p-3 rounded-3 border mb-3 gap-3">
        <a href="{{ route('quotations.create', ['step' => 1, 'c' => $clientId]) }}" class="btn btn-outline-primary bg-white text-primary fw-medium">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="vr"></div>
        <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-2 p-2 flex-shrink-0">
            <i class="fas fa-user"></i>
        </div>
        <div class="flex-grow-1 min-w-0">
            <div class="fw-semibold text-dark">{{ $selectedClientName }}</div>
            @if ($selectedClientEmail)
                <div class="small text-secondary-emphasis">{{ $selectedClientEmail }}</div>
            @endif
        </div>
        <div class="d-flex align-items-center gap-3 flex-shrink-0 text-end">
            <span id="quoNumberBadge" class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-bold rounded-1 px-3 py-2">
                {{ old('quo_number', $nextQuotationNumber) }}
            </span>
            <div class="d-flex align-items-center gap-1" aria-label="Step progress">
                @foreach ([1, 2, 3, 4] as $s)
                    <span @class([
                        'd-inline-flex align-items-center justify-content-center rounded-circle fw-bold',
                        'bg-primary text-white border-0' => $s === 2,
                        'bg-white text-secondary border' => $s !== 2,
                    ]) style="width:1.5rem;height:1.5rem;font-size:0.74rem;">{{ $s }}</span>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Quotation Details (Full Width Card) -->
    <div class="bg-light p-2 rounded-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label for="quo_title" class="form-label small lh-sm fw-semibold text-dark mb-1">Quotation Title</label>
                <input type="text" id="quo_title" name="quo_title" class="form-control"
                    placeholder="e.g. Annual Software Subscription" required>
            </div>
            <div class="col-6 col-md-2">
                <label for="issue_date" class="form-label small lh-sm fw-semibold text-dark mb-1">Issue Date</label>
                <input type="date" id="issue_date" name="issue_date" class="form-control" required
                    min="{{ $quotationDateBounds['min_date'] }}"
                    max="{{ $quotationDateBounds['issue_max_date'] ?? $quotationDateBounds['max_date'] }}"
                    value="{{ old('issue_date', $quotationDateBounds['default_issue_date'] ?? date('Y-m-d')) }}">
            </div>
            <div class="col-6 col-md-2">
                <label for="due_date" class="form-label small lh-sm fw-semibold text-dark mb-1">Due Date</label>
                <input type="date" id="due_date" name="due_date" class="form-control" required
                    min="{{ $quotationDateBounds['min_date'] }}"
                    max="{{ $quotationDateBounds['due_max_date'] ?? $quotationDateBounds['max_date'] }}"
                    value="{{ old('due_date', $quotationDateBounds['default_due_date'] ?? date('Y-m-d', strtotime('+7 days'))) }}">
            </div>
            <div class="col-12 col-md-3">
                <label for="notes" class="form-label small lh-sm fw-semibold text-dark mb-1">Notes</label>
                <input type="text" id="notes" name="notes" class="form-control" placeholder="Optional notes"
                    value="{{ old('notes') }}">
            </div>
        </div>
    </div>

    <div class="row g-2">
        <!-- Left Column: col-12 col-lg-3 -->
        <div class="col-12 col-lg-3">
            <!-- Select/Add Items Form -->
            <div class="bg-DarkLight p-2 rounded-3 h-100" id="addItemFormCard">
                <div class="mb-1">
                    <h5 class="fw-semibold small lh-sm text-primary mb-0" id="addItemFormTitle">Add Items</h5>
                </div>
                <div class="row g-2">
                    <div class="col-12">
                        <label for="itemid" class="form-label small lh-sm fw-semibold text-dark mb-1">Item</label>
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

                    <div class="col-12">
                        <label for="item_description" class="form-label small lh-sm fw-semibold text-dark mb-1">Description</label>
                        <textarea id="item_description" class="form-control" rows="3" placeholder="Description (optional)"></textarea>
                    </div>

                    <div class="col-4">
                        <label for="quantity" class="form-label small lh-sm fw-semibold text-dark mb-1">Qty</label>
                        <input type="number" id="quantity" class="form-control" value="1" min="1" step="1">
                    </div>
                    <div class="col-4">
                        <label for="unit_price" class="form-label small lh-sm fw-semibold text-dark mb-1">Price</label>
                        <input type="number" id="unit_price" class="form-control" min="0" step="0.01">
                    </div>
                    <div class="col-4">
                        <label for="discount_percent" class="form-label small lh-sm fw-semibold text-dark mb-1">Disc %</label>
                        <input type="number" id="discount_percent" class="form-control" min="0" max="100" step="0.01" value="0">
                    </div>

                    <div id="usersWrap" class="col-6 {{ $accountHasUsers ? '' : 'd-none' }}">
                        <label for="no_of_users" class="form-label small lh-sm fw-semibold text-dark mb-1">Users</label>
                        <input type="number" id="no_of_users" class="form-control" value="1" min="1" step="1">
                    </div>

                    <div class="col-6">
                        <label for="frequency" class="form-label small lh-sm fw-semibold text-dark mb-1">Freq</label>
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

                    <div id="durationWrap" class="col-6">
                        <label for="duration" class="form-label small lh-sm fw-semibold text-dark mb-1">Dur</label>
                        <input type="number" id="duration" class="form-control" min="1" step="1" value="1">
                    </div>

                    <div id="startDateWrap" class="col-6">
                        <label for="start_date" class="form-label small lh-sm fw-semibold text-dark mb-1">Start</label>
                        <input type="date" id="start_date" class="form-control">
                    </div>

                    <div id="endDateWrap" class="col-6">
                        <label for="end_date" class="form-label small lh-sm fw-semibold text-dark mb-1">End</label>
                        <input type="date" id="end_date" class="form-control">
                    </div>

                    <div class="col-12 d-flex justify-content-end mt-2">
                        <button type="button" id="addItem" class="btn btn-outline-primary btn-primary text-white fw-medium w-100">
                            Add Item <i class="fas fa-arrow-right btn-icon ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: col-12 col-lg-9 -->
        <div class="col-12 col-lg-9">
            <div id="quotationItemsTableWrap" class="order-create-table-wrap bg-DarkLight p-3 h-100 rounded-3 mt-0">
                <div class="card overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-striped mainTable align-middle mb-0 d-none" id="itemsTable">
                            <thead class="table-light">
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
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody"></tbody>
                        </table>
                    </div>
                </div>

                <div id="itemsEmpty" class="alert alert-light border mt-3 mb-0">No items added yet.</div>

                <div id="quoteSummary" class="bg-light border rounded-3 p-3 d-none mt-3 ms-auto"
                    style="max-width: 320px;">
                    <div class="d-flex justify-content-between small mb-1 text-secondary"><span>Subtotal</span><strong
                            id="summarySubtotal">0</strong></div>
                    <div class="d-flex justify-content-between small mb-1 text-secondary"><span>Discount</span><strong
                            id="summaryDiscount">0</strong></div>
                    <div class="d-flex justify-content-between small mb-1 text-secondary"><span>Tax</span><strong
                            id="summaryTax">0</strong></div>
                    <div class="d-flex justify-content-between small border-top pt-2 fw-bold text-dark">
                        <span>Total</span><strong id="summaryTotal">0</strong></div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mt-3">
        <a href="{{ route('quotations.create', ['step' => 1, 'c' => $clientId]) }}" class="btn btn-outline-primary bg-white text-primary fw-medium">
            <i class="fas fa-times btn-icon me-1"></i> Back
        </a>
        <button type="button" id="toStep3" class="btn btn-outline-primary btn-primary text-white fw-medium">
            Review & Terms <i class="fas fa-arrow-right btn-icon ms-1"></i>
        </button>
    </div>
</div>
