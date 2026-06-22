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
<form id="step2" class="mainForm" onsubmit="event.preventDefault();">
    <input type="hidden" name="clientid" value="{{ request('c', request('clientid', $clientId)) }}">
    <input type="hidden" name="quotationid" id="quotationid" value="">
    <input type="hidden" name="items_data" id="items_data" value="">

    <div class="row g-2">
        <!-- Left Column: col-12 col-lg-3 -->
        <div class="col-12 col-lg-3">
            <!-- Quotation Details -->
            <div class="bg-secondary p-2 rounded-3 mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-12">
                        <select id="clientid" class="form-select" @if(request('d')) disabled @endif required>
                            <option value="">Choose client</option>
                            @php
                            $groupedClients = $clients->groupBy(
                            fn ($c) => $c->type === 'trial' ? 'trial' : 'regular'
                            );
                            @endphp
                            @foreach (['regular', 'trial'] as $group)
                            @if ($groupedClients->has($group))
                            <optgroup label="{{ $group === 'regular' ? 'Regular Clients' : 'Trial Clients' }}">
                                @foreach ($groupedClients[$group] as $client)
                                <option value="{{ $client->clientid }}" {{ (string)request('c', request('clientid',
                                    $clientId))===(string)$client->clientid ? 'selected' : '' }}>
                                    {{ $client->business_name ?? $client->contact_name }}
                                </option>
                                @endforeach
                            </optgroup>
                            @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-12">
                        <input type="text" id="quo_title" name="quo_title" class="form-control"
                            placeholder="e.g. Annual Software Subscription" required>
                        <div id="quoTitleError" class="text-danger small mt-1 d-none">Quotation title is required.</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="issue_date" class="form-label small lh-sm fw-normal text-white mb-1">Issue
                            Date</label>
                        <div class="input-group">
                            <input type="date" id="issue_date" name="issue_date" class="form-control" required readonly
                                min="{{ $quotationDateBounds['min_date'] }}"
                                max="{{ $quotationDateBounds['issue_max_date'] ?? $quotationDateBounds['max_date'] }}"
                                value="{{ old('issue_date', $quotationDateBounds['default_issue_date'] ?? date('Y-m-d')) }}">
                            <span class="input-group-text"><i class="far fa-calendar-alt text-muted"></i></span>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="due_date" class="form-label small lh-sm fw-normal text-white mb-1">Due Date</label>
                        <div class="input-group">
                            <input type="date" id="due_date" name="due_date" class="form-control" required readonly
                                min="{{ $quotationDateBounds['min_date'] }}"
                                max="{{ $quotationDateBounds['due_max_date'] ?? $quotationDateBounds['max_date'] }}"
                                value="{{ old('due_date', $quotationDateBounds['default_due_date'] ?? date('Y-m-d', strtotime('+7 days'))) }}">
                            <span class="input-group-text"><i class="far fa-calendar-alt text-muted"></i></span>
                        </div>
                    </div>
                    <div class="col-12 col-md-12">
                        <input type="text" id="notes" name="notes" class="form-control" placeholder="Notes (Optional)"
                            value="{{ old('notes') }}">
                    </div>
                </div>
            </div>

            <!-- Select/Add Items Form -->
            <div class="bg-DarkLight p-2 rounded-3" id="addItemFormCard">
                <div class="row g-2">
                    <div class="col-12 col-md-12">
                        <div class="mb-1">
                            <h5 class="fw-semibold small lh-sm text-primary mb-0" id="addItemFormTitle">Add Items</h5>
                        </div>
                    </div>
                    <div class="col-12 col-md-12">
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
                                $costing = $service->costings->firstWhere('costing_type', 'selling_price') ??
                                $service->costings->first();
                                @endphp
                                <option value="{{ $service->itemid }}" data-name="{{ $service->name }}"
                                    data-category="{{ $service->category->name ?? '' }}"
                                    data-description="{{ $service->description ?? '' }}"
                                    data-unit-price="{{ $costing->selling_price ?? 0 }}"
                                    data-tax-rate="{{ $costing->tax_rate ?? 0 }}"
                                    data-user-wise="{{ (int) ($service->user_wise ?? 0) }}">
                                    {{ $service->name }}
                                </option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-12">
                        <textarea id="item_description" class="form-control"
                            placeholder="Description (Optional)"></textarea>
                    </div>

                    <div class="col-3 col-md-3">
                        <label for="quantity" class="form-label small lh-sm fw-semibold text-dark mb-1">Qty</label>
                        <input type="number" id="quantity" class="form-control" value="1" min="1" step="1">
                    </div>
                    <div id="usersWrap" class="col-12 col-md-2">
                        <label for="no_of_users" class="form-label small lh-sm fw-semibold text-dark mb-1">User</label>
                        <input type="number" id="no_of_users" class="form-control" value="1" min="1" step="1" disabled>
                    </div>
                    <div class="col-12 col-md-5">
                        <label for="frequency"
                            class="form-label small lh-sm fw-semibold text-dark mb-1">Frequency</label>
                        <select id="frequency" class="form-select">
                            <option value="One-Time">One-Time</option>
                            <option value="Day(s)">Day(s)</option>
                            <option value="Week(s)">Week(s)</option>
                            <option value="Month(s)">Month(s)</option>
                            <option value="Quarter(s)">Quarter(s)</option>
                            <option value="Year(s)">Year(s)</option>
                        </select>
                    </div>

                    <div id="durationWrap" class="col-12 col-md-2">
                        <label for="duration" class="form-label small lh-sm fw-semibold text-dark mb-1">Dur</label>
                        <input type="number" id="duration" class="form-control" min="0" step="1" placeholder="e.g. 12"
                            disabled>
                    </div>
                    <div id="startDateWrap" class="col-6 col-md-6">
                        <label for="start_date" class="form-label small lh-sm fw-semibold text-dark mb-1">Start
                            Date</label>
                        <div class="input-group">
                            <input type="date" id="start_date" class="form-control" readonly>
                            <span class="input-group-text"><i class="far fa-calendar-alt text-muted"></i></span>
                        </div>
                    </div>

                    <div id="endDateWrap" class="col-6 col-md-6">
                        <label for="end_date" class="form-label small lh-sm fw-semibold text-dark mb-1">Expiry</label>
                        <div class="input-group">
                            <input type="date" id="end_date" class="form-control" readonly>
                            <span class="input-group-text"><i class="far fa-calendar-alt text-muted"></i></span>
                        </div>
                    </div>
                    <div class="col-4 col-md-4">
                        <label for="unit_price" class="form-label small lh-sm fw-semibold text-dark mb-1">Price</label>
                        <input type="number" id="unit_price" class="form-control" min="0" step="0.01">
                    </div>
                    <div class="col-4 col-md-4">
                        <label for="discount_percent" class="form-label small lh-sm fw-semibold text-dark mb-1">Discount
                            (%)</label>
                        <input type="number" id="discount_percent" class="form-control" min="0" max="100" step="0.01"
                            value="0">
                    </div>

                    <div class="col-4 col-md-4 d-flex justify-content-end mt-auto ms-auto pt-2">
                        <button type="button" id="addItem"
                            class="btn btn-outline-primary btn-primary text-white fw-medium">
                            Add Item <i class="fas fa-arrow-right btn-icon ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: col-12 col-lg-9 -->
        <div class="col-12 col-lg-9">
            <div id="quotationItemsTableWrap"
                class="order-create-table-wrap bg-DarkLight p-2 h-100 rounded-3 mt-0">
                <div>
                    <div class="card overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-striped mainTable align-middle mb-0" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="25%">Item</th>
                                        <th class="text-center" width="10%">Qty</th>
                                        @if ($account->allow_multi_taxation)
                                        <th class="text-center" width="10%">Tax %</th>
                                        @endif
                                        <th id="usersColHeader" class="d-none text-center" width="10%">Users</th>
                                        <th id="freqDurHeader" class="d-none text-center" width="10%">Freq & Dur</th>
                                        <th id="startEndHeader" class="d-none text-center" width="15%">Start & End Date</th>
                                        <th class="text-end" width="15%">Price (Disc)</th>
                                        <th class="text-end" width="15%">Total Price</th>
                                        <th class="text-end" width="10%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <!-- Dynamic Rows -->
                                </tbody>
                                <thead id="quoteSummary" class="d-none">
                                    <tr>
                                        <td class="bg-light fw-semibold text-dark text-end py-1" colspan="{{ $account->allow_multi_taxation ? 7 : 6 }}">Subtotal</td>
                                        <td id="summarySubtotal" class="bg-light fw-semibold text-end py-1">0</td>
                                        <td class="bg-light py-1"></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-light fw-semibold text-dark text-end py-1" colspan="{{ $account->allow_multi_taxation ? 7 : 6 }}">Discount</td>
                                        <td id="summaryDiscount" class="bg-light fw-semibold text-success text-end py-1">- 0</td>
                                        <td class="bg-light py-1"></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-light fw-semibold text-dark text-end py-1" colspan="{{ $account->allow_multi_taxation ? 7 : 6 }}">Tax</td>
                                        <td id="summaryTax" class="bg-light fw-semibold text-end py-1">0</td>
                                        <td class="bg-light py-1"></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-DarkLight fw-semibold text-dark text-end py-1" colspan="{{ $account->allow_multi_taxation ? 7 : 6 }}">Grand Total</td>
                                        <td id="summaryTotal" class="bg-DarkLight fw-semibold fs-6 lh-sm text-end py-1">0</td>
                                        <td class="bg-DarkLight py-1"></td>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-end mt-2">
                    <button type="button" id="toStep3" class="btn btn-outline-primary bg-primary text-white fw-medium">
                        Save & Continue <i class="fas fa-arrow-right btn-icon ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
