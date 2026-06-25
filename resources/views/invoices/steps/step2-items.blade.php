@php
$invoiceDateBounds = $invoiceDateBounds ?? [
'min_date' => date('Y-m-d'),
'max_date' => date('Y-m-d'),
'issue_max_date' => date('Y-m-d'),
'due_max_date' => date('Y-m-d'),
'default_issue_date' => '',
'default_due_date' => '',
];
$selectedClientCurrency =
optional($clients->firstWhere('clientid', request('c', request('clientid'))))->currency ?? 'INR';
$selectedClient = $clients->firstWhere('clientid', request('c', request('clientid')));
$selectedClientName = $selectedClient
? $selectedClient->business_name ?? ($selectedClient->contact_name ?? 'Unknown Client')
: 'No Client Selected';
$selectedClientEmail = $selectedClient->primary_email ?? $selectedClient->email ?? '';
$isTaxInvoiceStep2 = request('tax_invoice', 0) == 1 || !empty($invoice?->ti_number);
$initialHeaderNumberStep2 = $isTaxInvoiceStep2
? ($invoice?->ti_number ?:
$nextTaxInvoiceNumber ?? $nextInvoiceNumber)
: ($invoice?->pi_number ?:
$nextInvoiceNumber);
$orderItemsFlat = collect($orderItemsForClient ?? [])->values();
@endphp
<!-- Step 2: Select Items -->
<div id="step2">
    <input type="hidden" name="clientid" value="{{ request('c', request('clientid')) }}">
    <input type="hidden" name="orderid" id="orderid" value="">
    <input type="hidden" name="invoice_number" value="{{ $initialHeaderNumberStep2 }}">
    <input type="hidden" name="items_data" id="items_data" value="">

    <div class="row g-2">
        <!-- Left Column: col-12 col-lg-3 -->
        <div class="col-12 col-lg-3">
            <!-- Invoice Details (Full Width Card) -->
            <div class="bg-secondary p-2 rounded-3 mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-12">
                        <select id="clientid" class="form-select" @if(request('d')) disabled @endif required>
                            <option value="">Choose client</option>
                            @foreach($clients as $client)
                            <option value="{{ $client->clientid }}" {{ (string)request('c',
                                request('clientid'))===(string)$client->clientid ? 'selected' : '' }}>
                                {{ $client->business_name ?? $client->contact_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-12">
                        <input type="text" id="invoice_title" name="invoice_title" class="form-control"
                            placeholder="e.g. Website Development - Monthly Subscription" required>
                        <div id="invoiceTitleError" class="text-danger small mt-1 d-none">Invoice title is required.
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="issue_date" class="form-label small lh-sm fw-normal text-white mb-1">Issue
                            Date</label>
                        <div class="input-group">
                            <input type="date" id="issue_date" name="issue_date" class="form-control" required readonly
                                min="{{ $invoiceDateBounds['min_date'] }}"
                                max="{{ $invoiceDateBounds['issue_max_date'] ?? $invoiceDateBounds['max_date'] }}"
                                value="{{ old('issue_date', request('d') && $invoice ? $invoice->issue_date?->format('Y-m-d') : ($invoiceDateBounds['default_issue_date'] ?? date('Y-m-d'))) }}">
                            <span class="input-group-text"><i class="far fa-calendar-alt text-muted"></i></span>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="due_date" class="form-label small lh-sm fw-normal text-white mb-1">Due
                            Date</label>
                        <div class="input-group">
                            <input type="date" id="due_date" name="due_date" class="form-control" required readonly
                                min="{{ $invoiceDateBounds['min_date'] }}"
                                max="{{ $invoiceDateBounds['due_max_date'] ?? $invoiceDateBounds['max_date'] }}"
                                value="{{ old('due_date', request('d') && $invoice ? $invoice->due_date?->format('Y-m-d') : ($invoiceDateBounds['default_due_date'] ?? date('Y-m-d', strtotime('+7 days')))) }}">
                            <span class="input-group-text"><i class="far fa-calendar-alt text-muted"></i></span>
                        </div>
                    </div>
                    <div class="col-12 col-md-12">
                        <input type="text" id="notes" name="notes" class="form-control" placeholder="Notes (Optional)"
                            value="{{ old('notes', request('d') && $invoice ? $invoice->notes : '') }}">
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
                        <div class="input-group">
                            <select id="manual_item_itemid" class="form-select" style="flex: 1;">
                                <option value="">Select item</option>
                                @foreach ($orderItemsFlat as $orderItem)
                                <option value="{{ $orderItem['itemid'] }}" data-orderid="{{ $orderItem['orderid'] }}"
                                    data-selling-price="{{ $orderItem['unit_price'] ?? 0 }}"
                                    data-tax-rate="{{ $orderItem['tax_rate'] ?? 0 }}"
                                    data-user-wise="{{ (int) ($orderItem['requires_user_fields'] ?? 0) }}"
                                    data-description="{{ $orderItem['item_description'] ?? '' }}"
                                    data-item-name="{{ $orderItem['item_name'] ?? '' }}">
                                    {{ $orderItem['display_order_number'] ?? $orderItem['order_number'] ??
                                    $orderItem['orderid'] ?? 'Order' }} -
                                    {{ $orderItem['item_name'] ?? 'Item' }}
                                </option>
                                @endforeach
                            </select>
                            <button type="button" id="openAddOrderModalBtn"
                                class="btn btn-outline-primary bg-primary text-white fw-medium"
                                title="Create new order">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-12 col-md-12">
                        <textarea id="manual_item_description" class="form-control"
                            placeholder="Description (Optional)"></textarea>
                    </div>

                    <div class="col-3 col-md-3">
                        <label for="manual_item_quantity"
                            class="form-label small lh-sm fw-semibold text-dark mb-1">Qty</label>
                        <input type="number" id="manual_item_quantity" class="form-control" value="1" min="1" step="1">
                    </div>
                    <div id="manual_item_users_wrap" class="col-12 col-md-2">
                        <label for="manual_item_users"
                            class="form-label small lh-sm fw-semibold text-dark mb-1">User</label>
                        <input type="number" id="manual_item_users" class="form-control" value="1" min="1" step="1"
                            disabled>
                    </div>
                    <div class="col-12 col-md-5">
                        <label for="manual_item_frequency"
                            class="form-label small lh-sm fw-semibold text-dark mb-1">Frequency</label>
                        <select id="manual_item_frequency" class="form-select">
                            <option value="One-Time">One-Time</option>
                            <option value="Day(s)">Day(s)</option>
                            <option value="Week(s)">Week(s)</option>
                            <option value="Month(s)">Month(s)</option>
                            <option value="Quarter(s)">Quarter(s)</option>
                            <option value="Year(s)">Year(s)</option>
                        </select>
                    </div>

                    <div id="manual_item_duration_wrap" class="col-12 col-md-2">
                        <label for="manual_item_duration"
                            class="form-label small lh-sm fw-semibold text-dark mb-1">Dur</label>
                        <input type="number" id="manual_item_duration" class="form-control" min="0" step="1"
                            placeholder="e.g. 12" disabled>
                    </div>
                    <div id="manual_item_start_date_wrap" class="col-6 col-md-6">
                        <label for="manual_item_start_date"
                            class="form-label small lh-sm fw-semibold text-dark mb-1">Start Date</label>
                        <div class="input-group">
                            <input type="date" id="manual_item_start_date" class="form-control" readonly>
                            <span class="input-group-text"><i class="far fa-calendar-alt text-muted"></i></span>
                        </div>
                    </div>

                    <div id="manual_item_end_date_wrap" class="col-6 col-md-6">
                        <label for="manual_item_end_date"
                            class="form-label small lh-sm fw-semibold text-dark mb-1">Expiry</label>
                        <div class="input-group">
                            <input type="date" id="manual_item_end_date" class="form-control" readonly>
                            <span class="input-group-text"><i class="far fa-calendar-alt text-muted"></i></span>
                        </div>
                    </div>
                    <div class="col-4 col-md-4">
                        <label for="manual_item_unit_price"
                            class="form-label small lh-sm fw-semibold text-dark mb-1">Price</label>
                        <input type="number" id="manual_item_unit_price" class="form-control" min="0" step="0.01">
                    </div>
                    <div class="col-4 col-md-4">
                        <label for="manual_item_discount"
                            class="form-label small lh-sm fw-semibold text-dark mb-1">Discount (%)</label>
                        <input type="number" id="manual_item_discount" class="form-control" min="0" max="100"
                            step="0.01" value="0">
                    </div>

                    @if ($account->allow_multi_taxation)
                    <div class="col-4 col-md-4">
                        <label for="manual_item_tax_rate"
                            class="form-label small lh-sm fw-semibold text-dark mb-1">Tax</label>
                        <select id="manual_item_tax_rate" class="form-select">
                            <option value="0">No Tax</option>
                            @foreach ($taxes as $tax)
                            <option value="{{ $tax->rate }}">{{ $tax->tax_name ?: $tax->type }} ({{ number_format($tax->rate, 0) }}%)</option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <input type="hidden" id="manual_item_tax_rate" value="{{ $account->fixed_tax_rate ?? 0 }}">
                    @endif

                    <div class="col-4 colmd-4 d-flex justify-content-end mt-auto ms-auto pt-2">
                        <button type="button" id="addManualItemBtn"
                            class="btn btn-outline-primary btn-primary text-white fw-medium">
                            Add Item <i class="fas fa-arrow-right btn-icon ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: col-12 col-lg-9 -->
        <div class="col-12 col-lg-9">
            <div id="invoiceItemsTableWrap"
                class="order-create-table-wrap bg-DarkLight p-2 h-100 rounded-3 mt-0">
                <div>
                    <div class="card overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-striped mainTable align-middle mb-0" id="manualItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="25%">Item</th>
                                        <th class="text-center" width="10%">Qty</th>
                                        @if ($account->allow_multi_taxation)
                                        <th class="text-center">Tax %</th>
                                        @endif
                                        <th id="manualUsersHeader" class="d-none text-center" width="10%">Users</th>
                                        <th id="manualFreqDurationHeader" class="d-none text-center" width="10%">
                                            Freq & Dur</th>
                                        <th id="manualStartEndHeader" class="d-none text-center" width="15%">Start & End
                                            Date
                                        </th>
                                        <th class="text-end" width="10%">Price (Disc)</th>
                                        <th class="text-end" width="10%">Total Price</th>
                                        <th class="text-end" width="10%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="manualItemsBody">

                                </tbody>
                                <thead id="manualOrderSummary">
                                    <tr>
                                        <td class="bg-light fw-semibold text-dark text-end py-1" colspan="6">Subtotal
                                        </td>
                                        <td id="manualSubtotal" class="bg-light fw-semibold text-end py-1">0</td>
                                        <td class="bg-light py-1"></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-light fw-semibold text-dark text-end py-1" colspan="6">Discount
                                        </td>
                                        <td id="manualDiscountTotal"
                                            class="bg-light fw-semibold text-success text-end py-1">
                                            - 0
                                        </td>
                                        <td class="bg-light py-1"></td>
                                    </tr>

                                    <tr>
                                        <td class="bg-light fw-semibold text-dark text-end py-1" colspan="6">Tax</td>
                                        <td id="manualTaxTotal" class="bg-light fw-semibold text-end py-1">0</td>
                                        <td class="bg-light py-1"></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-DarkLight fw-semibold text-dark text-end py-1" colspan="6">Grand
                                            Total
                                        </td>
                                        <td id="manualGrandTotal"
                                            class="bg-DarkLight fw-semibold fs-6 lh-sm text-end py-1">0
                                        </td>
                                        <td class="bg-DarkLight py-1"></td>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>

                    <!--<div class="bg-white rounded-3 p-3 d-none mt-3 ms-auto" style="max-width: 320px;">
                        <div class="d-flex justify-content-between small mb-1 text-secondary">
                            <span>Subtotal</span><strong id="manualSubtotal">0</strong>
                        </div>
                        <div class="d-flex justify-content-between small mb-1 text-secondary">
                            <span>Discount</span><strong id="manualDiscountTotal">0</strong>
                        </div>
                        <div class="d-flex justify-content-between small mb-1 text-secondary"><span>Tax</span><strong
                                id="manualTaxTotal">0</strong></div>
                        <div class="d-flex justify-content-between small border-top pt-2 fw-bold text-dark">
                            <span>Total</span><strong id="manualGrandTotal">0</strong>
                        </div>
                    </div>-->
                </div>
                <div class="d-flex align-items-center justify-content-end mt-2">
                    <button type="button" id="btnNextToStep3"
                        class="btn btn-outline-primary bg-primary text-white fw-medium">
                        Save & Continue <i class="fas fa-arrow-right btn-icon ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const addManualItemBtn = document.getElementById('addManualItemBtn');
        const manualItemsBody = document.getElementById('manualItemsBody');
        const manualItemsTable = document.getElementById('manualItemsTable');
        const manualSummary = document.getElementById('manualOrderSummary');
        const btnNextToStep3 = document.getElementById('btnNextToStep3');
        const toggleAddItemFormBtn = document.getElementById('toggleAddItemFormBtn');
        const addItemFormCard = document.getElementById('addItemFormCard');
        const itemsDataInput = document.getElementById('items_data');
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
        const clientSelect = document.getElementById('clientid');
        const isTaxInvoice = "{{ $isTaxInvoiceStep2 ? '1' : '' }}" === "1";
        const draftId = "{{ request('d', '') }}";
        const clientId = "{{ request('c', request('clientid', $invoice?->clientid ?? '')) }}";
        const fallbackPiNumber = "{{ $nextInvoiceNumber }}";
        const fallbackTiNumber = "{{ $nextTaxInvoiceNumber ?? $nextInvoiceNumber }}";
        const accountHasUsers = "{{ ($account->have_users ?? false) ? '1' : '' }}" === "1";
        const ONE_TIME_MAX_END_DATE = '2099-12-31';
        const rawOrderItemsRoute = "{{ route('invoices.order-items', ['orderid' => '__ORDERID__']) }}";
        const orderItemsRouteTemplate = rawOrderItemsRoute.startsWith('http') ? new URL(rawOrderItemsRoute).pathname : rawOrderItemsRoute;
        const TAX_READY_TOAST_KEY = 'invoice_tax_ready_toast';
        let draftPiNumber = '';
        let draftTiNumber = '';
        const orderDatePrefillCache = {};
        let currentOrderDatePrefill = null;



        function consumeTaxReadyToast() {
            try {
                const message = window.localStorage.getItem(TAX_READY_TOAST_KEY);
                if (!message) return;
                window.localStorage.removeItem(TAX_READY_TOAST_KEY);
                showToast('success', message);
            } catch (e) {
                console.warn('Unable to read tax-ready toast state', e);
            }
        }
        consumeTaxReadyToast();

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

        const frequencyLabels = {
            'One-Time': 'One-Time',
            'Day(s)': 'Day(s)',
            'Week(s)': 'Week(s)',
            'Month(s)': 'Month(s)',
            'Quarter(s)': 'Quarter(s)',
            'Year(s)': 'Year(s)'
        };

        let manualItems = [];
        let editingManualItemIndex = null;

        function formatCurrency(amount) {
            const value = Number(amount || 0);
            const hasDecimals = Math.abs(value % 1) > 0;
            return `${value.toLocaleString('en-US', {
                minimumFractionDigits: hasDecimals ? 2 : 0,
                maximumFractionDigits: 2
            })}`;
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

        function formatDateToDisplay(dateStr) {
            if (!dateStr) return '-';
            const parts = dateStr.split('-');
            if (parts.length !== 3) return dateStr;
            const year = parts[0];
            const monthIndex = parseInt(parts[1], 10) - 1;
            const day = parseInt(parts[2], 10);
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            if (monthIndex < 0 || monthIndex > 11 || isNaN(day)) return dateStr;
            const dayStr = String(day).padStart(2, '0');
            return `${dayStr} ${months[monthIndex]} ${year}`;
        }

        function renderItemCell(item) {
            const name = escapeHtml(item.item_name || 'Item');
            const description = escapeHtml(item.item_description || '').trim();
            return description ? `
            <div class="fw-semibold">${name}</div>
            <div class="small-text">${description}</div>
        ` : `<div class="fw-semibold">${name}</div>`;
        }

        async function fetchOrderDatePrefill(orderId) {
            if (!orderId) return null;
            if (orderDatePrefillCache[orderId]) return orderDatePrefillCache[orderId];

            const url = orderItemsRouteTemplate.replace('__ORDERID__', encodeURIComponent(orderId));
            try {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json'
                    }
                });
                if (!response.ok) return null;
                const data = await response.json();
                const prefill = data?.date_prefill || null;
                orderDatePrefillCache[orderId] = prefill;
                return prefill;
            } catch (error) {
                console.error('Failed to fetch order date prefill', error);
                return null;
            }
        }

        function applyOrderDatePrefill(prefill, forceApply = false) {
            if (!prefill || editingManualItemIndex !== null) return;
            if (!manualStartInput || !manualEndInput) return;

            if (!forceApply) {
                const hasManualStart = Boolean((manualStartInput.value || '').trim());
                const hasManualEnd = Boolean((manualEndInput.value || '').trim());
                if (hasManualStart || hasManualEnd) return;
            }

            const source = String(prefill.source || '');
            const suggestedStartDate = prefill.suggested_start_date || '';
            const orderEndDate = prefill.order_end_date || '';

            if (source === 'invoice_items' && suggestedStartDate) {
                setDateInputValue(manualStartInput, suggestedStartDate);
                scheduleManualEndDateSync();
                return;
            }

            if (suggestedStartDate) {
                setDateInputValue(manualStartInput, suggestedStartDate);
            }

            if (orderEndDate) {
                setDateInputValue(manualEndInput, orderEndDate);
                return;
            }

            scheduleManualEndDateSync();
        }

        // Auto-fill price when item selected
        document.getElementById('manual_item_itemid').addEventListener('change', async function () {
            const selected = this.options[this.selectedIndex];
            const price = selected.dataset.sellingPrice || 0;
            const taxRate = selected.dataset.taxRate || 0;
            const orderId = selected?.dataset?.orderid || '';
            document.getElementById('manual_item_unit_price').value = price;
            document.getElementById('manual_item_description').value = selected.dataset.description ||
                '';
            if (document.getElementById('manual_item_tax_rate')) {
                document.getElementById('manual_item_tax_rate').value = taxRate;
            }
            toggleManualUsersField();

            // Switching to another order/item should refresh date prefill from that order.
            // Clear previous selection's dates so stale values do not block new prefill.
            if (editingManualItemIndex === null) {
                setDateInputValue(manualStartInput, '');
                setDateInputValue(manualEndInput, '');
            }

            const prefill = await fetchOrderDatePrefill(orderId);
            currentOrderDatePrefill = prefill;
            // forceApply=true: bypass the 'already has values' guard because we just
            // explicitly cleared the dates above. Without this, a race between the async
            // fetch and scheduleManualEndDateSync can write 2099-12-31 into the end-date
            // input before the response arrives, causing the guard to bail early.
            applyOrderDatePrefill(prefill, true);
        });

        function isManualItemUserWise() {
            const select = document.getElementById('manual_item_itemid');
            const option = select?.options[select.selectedIndex];
            return String(option?.dataset?.userWise ?? '0') === '1';
        }

        function toggleManualUsersField() {
            const wrap = document.getElementById('manual_item_users_wrap');
            const usersInput = document.getElementById('manual_item_users');
            if (!wrap || !usersInput) return;
            if (!accountHasUsers) {
                usersInput.disabled = true;
                usersInput.value = 1;
                return;
            }
            const show = isManualItemUserWise();
            usersInput.disabled = !show;
            if (!show) usersInput.value = 1;
        }
        toggleManualUsersField();

        function normalizeFrequencyValue(frequency) {
            return String(frequency || '').trim();
        }

        function isRecurringFrequency(frequency) {
            const normalized = normalizeFrequencyValue(frequency);
            return ['Day(s)', 'Week(s)', 'Month(s)', 'Quarter(s)', 'Year(s)'].includes(normalized);
        }

        function calculateEndDate(startDate, frequency, duration) {
            if (!startDate || !isRecurringFrequency(frequency) || !duration) {
                return '';
            }
            const parts = String(startDate).split('-');
            const start = new Date(parts[0], (parts[1] || 1) - 1, parts[2] || 1);
            const steps = Number(duration) || 0;
            if (Number.isNaN(start.getTime()) || steps <= 0) {
                return '';
            }
            const end = new Date(start);
            switch (frequency) {
                case 'Day(s)':
                    end.setDate(end.getDate() + steps);
                    break;
                case 'Week(s)':
                    end.setDate(end.getDate() + (steps * 7));
                    break;
                case 'Month(s)':
                    end.setMonth(end.getMonth() + steps);
                    break;
                case 'Quarter(s)':
                    end.setMonth(end.getMonth() + (steps * 3));
                    break;
                case 'Year(s)':
                    end.setFullYear(end.getFullYear() + steps);
                    break;
                default:
                    return '';
            }
            // End date is inclusive across billing cycles.
            end.setDate(end.getDate() - 1);
            const y = end.getFullYear();
            const m = String(end.getMonth() + 1).padStart(2, '0');
            const d = String(end.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        function setDateInputValue(input, value) {
            if (!input) return;
            const normalized = String(value || '');
            if (input.value === normalized && input.getAttribute('value') === normalized) return;
            input.value = normalized;
            input.setAttribute('value', normalized);
            if (input._flatpickr) {
                if (normalized) {
                    input._flatpickr.setDate(normalized, false, 'Y-m-d');
                } else {
                    input._flatpickr.clear();
                }
            }
        }

        function syncManualEndDateFromInputs() {
            if (!manualEndInput) return;
            const selectedFrequency = normalizeFrequencyValue(manualFrequencyInput?.value || '');
            if (selectedFrequency === 'One-Time') {
                setDateInputValue(manualEndInput, ONE_TIME_MAX_END_DATE);
                return;
            }
            const nextValue = calculateEndDate(
                manualStartInput?.value || '',
                manualFrequencyInput?.value || '',
                manualDurationInput?.value || ''
            );
            // For non-recurring frequencies (e.g. None), keep existing end date as-is.
            if (selectedFrequency === '' && !nextValue) {
                return;
            }
            if (manualStartInput) {
                const minVal = manualStartInput.value || '';
                if (minVal) {
                    manualEndInput.min = minVal;
                    if (manualEndInput._flatpickr) {
                        manualEndInput._flatpickr.set('minDate', minVal);
                    }
                }
            }
            setDateInputValue(manualEndInput, nextValue);
        }

        function scheduleManualEndDateSync() {
            window.requestAnimationFrame(syncManualEndDateFromInputs);
        }

        function toggleManualRecurringFields() {
            if (!manualDurationWrap || !manualDurationInput || !manualStartWrap || !manualEndWrap || !
                manualStartInput || !manualEndInput) return;
            const showRecurring = isRecurringFrequency(manualFrequencyInput?.value || '');
            manualDurationInput.disabled = !showRecurring;
            manualStartWrap.classList.remove('d-none');
            manualEndWrap.classList.remove('d-none');
            if (showRecurring) {
                const durationValue = Number(manualDurationInput.value || 0);
                if (!manualDurationInput.value || durationValue <= 0) {
                    manualDurationInput.value = '1';
                }
            } else {
                manualDurationInput.value = '1';
            }
        }

        manualFrequencyInput?.addEventListener('change', function () {
            toggleManualRecurringFields();
            applyOrderDatePrefill(currentOrderDatePrefill);
            scheduleManualEndDateSync();
        });
        [manualStartInput, manualDurationInput, manualFrequencyInput].forEach((input) => {
            input?.addEventListener('change', scheduleManualEndDateSync);
            input?.addEventListener('input', scheduleManualEndDateSync);
        });
        toggleManualRecurringFields();

        function itemHasUsers(item) {
            if (!accountHasUsers) return false;
            return Boolean(item?.requires_user_fields) || Number(item?.no_of_users || 0) > 0;
        }

        function itemIsRecurring(item) {
            return isRecurringFrequency(item?.frequency);
        }

        function itemHasDates(item) {
            return Boolean((item?.start_date || '').trim()) || Boolean((item?.end_date || '').trim());
        }

        function syncManualHeaders() {
            const showRecurringColumns = manualItems.some(item => itemIsRecurring(item) || itemHasDates(item));
            const showUserColumns = manualItems.some(itemHasUsers);
            const usersHeader = document.getElementById('manualUsersHeader');
            const freqDurationHeader = document.getElementById('manualFreqDurationHeader');
            const startEndHeader = document.getElementById('manualStartEndHeader');
            if (usersHeader) usersHeader.classList.toggle('d-none', !showUserColumns);
            if (freqDurationHeader) freqDurationHeader.classList.toggle('d-none', !showRecurringColumns);
            if (startEndHeader) startEndHeader.classList.toggle('d-none', !showRecurringColumns);

            const allowMultiTaxation = "{{ ($account->allow_multi_taxation ?? false) ? '1' : '' }}" === "1";
            let colsBeforeTotalPrice = 3;
            if (allowMultiTaxation) {
                colsBeforeTotalPrice += 1;
            }
            if (showUserColumns) {
                colsBeforeTotalPrice += 1;
            }
            if (showRecurringColumns) {
                colsBeforeTotalPrice += 2;
            }

            document.querySelectorAll('#manualOrderSummary td[colspan]').forEach(td => {
                td.setAttribute('colspan', colsBeforeTotalPrice);
            });

            return {
                showRecurringColumns,
                showUserColumns
            };
        }

        function resetManualItemForm() {
            editingManualItemIndex = null;
            currentOrderDatePrefill = null;
            addManualItemBtn.innerHTML = 'Add Item <i class="fas fa-arrow-right btn-icon ms-1"></i>';
            const formTitle = document.getElementById('addItemFormTitle');
            if (formTitle) formTitle.textContent = 'Add Items';
            document.getElementById('manual_item_itemid').value = '';
            document.getElementById('manual_item_quantity').value = '1';
            document.getElementById('manual_item_unit_price').value = '';
            document.getElementById('manual_item_discount').value = '0';
            document.getElementById('manual_item_frequency').value = 'One-Time';
            document.getElementById('manual_item_duration').value = '';
            document.getElementById('manual_item_description').value = '';
            setDateInputValue(manualStartInput, '');
            setDateInputValue(manualEndInput, '');
            toggleManualRecurringFields();
            if (accountHasUsers) {
                toggleManualUsersField();
            }
        }

        function openAddItemForm() {
            if (addItemFormCard) {
                addItemFormCard.classList.remove('d-none');
            }
            if (toggleAddItemFormBtn) {
                toggleAddItemFormBtn.innerHTML =
                    '<i class="fas fa-times"></i> Cancel';
            }
        }

        addManualItemBtn.addEventListener('click', function () {
            const itemId = document.getElementById('manual_item_itemid').value;
            if (!itemId) {
                showToast('error', 'Please select an item.');
                return;
            }

            const quantityInputVal = Number(document.getElementById('manual_item_quantity').value || 0);
            if (quantityInputVal <= 0) {
                showToast('error', 'Quantity must be greater than 0.');
                return;
            }
            const selectedOption = document.getElementById('manual_item_itemid').options[document
                .getElementById('manual_item_itemid').selectedIndex];
            const selectedOrderId = selectedOption?.dataset?.orderid || '';
            const itemName = selectedOption?.dataset?.itemName || selectedOption?.text || '';
            const itemDescription = (document.getElementById('manual_item_description').value || '').trim();
            const quantity = Math.max(1, Math.round(Number(document.getElementById('manual_item_quantity')
                .value) || 1));
            const unitPrice = parseFloat(document.getElementById('manual_item_unit_price').value) || 0;
            const discountPercent = Math.min(100, Math.max(0, parseFloat(document.getElementById(
                'manual_item_discount').value) || 0));
            const taxRate = parseFloat(document.getElementById('manual_item_tax_rate').value) || 0;
            const isUserWiseItem = isManualItemUserWise();
            const users = isUserWiseItem ? (parseInt(document.getElementById('manual_item_users').value) ||
                1) : 1;
            const usersForStorage = isUserWiseItem ? users : null;
            const frequency = document.getElementById('manual_item_frequency').value;
            const duration = isRecurringFrequency(frequency) ?
                Math.max(1, parseInt(document.getElementById('manual_item_duration').value) || 1) :
                null;
            const startDate = manualStartInput?.value || null;
            const endDate = manualEndInput?.value || null;

            const durationMultiplier = (isRecurringFrequency(frequency) && Number(duration || 0) > 0) ?
                Number(duration) : 1;
            const lineTotal = quantity * unitPrice * Math.max(1, users) * durationMultiplier;
            const discountValue = roundDiscountDown(lineTotal * (discountPercent / 100));
            const discountAmount = Math.max(0, lineTotal - discountValue);
            const taxAmount = roundTaxUp(discountAmount * (taxRate / 100));

            const newItem = {
                orderid: selectedOrderId,
                itemid: itemId,
                item_name: itemName.split('(')[0].trim(),
                item_description: itemDescription,
                quantity,
                unit_price: unitPrice,
                discount_percent: discountPercent,
                discount_amount: discountAmount,
                tax_rate: taxRate,
                no_of_users: usersForStorage,
                requires_user_fields: isUserWiseItem,
                frequency,
                duration,
                start_date: startDate,
                end_date: endDate,
                tax_amount: taxAmount,
                line_total: lineTotal
            };

            if (editingManualItemIndex !== null && manualItems[editingManualItemIndex]) {
                newItem.invoice_itemid = manualItems[editingManualItemIndex].invoice_itemid || null;
                manualItems[editingManualItemIndex] = newItem;
            } else {
                manualItems.push(newItem);
            }
            renderManualItems();

            resetManualItemForm();
        });

        function renderManualItems() {
            if (manualItems.length === 0) {
                manualItemsTable.classList.remove('d-none');
                manualItemsBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            No items added yet. Select an item or add one manually.
                        </td>
                    </tr>
                `;
                manualSummary.classList.add('d-none');
                btnNextToStep3.disabled = true;
                syncManualHeaders();
                return;
            }

            manualItemsTable.classList.remove('d-none');
            manualSummary.classList.remove('d-none');
            btnNextToStep3.disabled = false;
            const headerState = syncManualHeaders();
            const showRecurringColumns = headerState.showRecurringColumns;
            const showUserColumns = headerState.showUserColumns;

            manualItemsBody.innerHTML = '';
            let subtotal = 0;
            let discountTotal = 0;
            let taxTotal = 0;
            manualItems.forEach((item, index) => {
                const quantity = Math.max(1, Math.round(Number(item.quantity || 1)));
                const unitPrice = Number(item.unit_price || 0);
                const users = Math.max(1, Number(item.no_of_users || 1));
                const durationMultiplier = (itemIsRecurring(item) && Number(item.duration || 0) > 0) ?
                    Number(item.duration) : 1;
                item.quantity = quantity;
                item.line_total = quantity * unitPrice * users * durationMultiplier;
                item.discount_percent = Math.min(100, Math.max(0, Number(item.discount_percent || 0)));
                const discountValue = roundDiscountDown(item.line_total * (item.discount_percent / 100));
                item.discount_amount = Math.max(0, item.line_total - discountValue);
                item.tax_amount = roundTaxUp(item.discount_amount * (Number(item.tax_rate || 0) / 100));

                subtotal += item.line_total;
                discountTotal += discountValue;
                taxTotal += item.tax_amount;

                const rowRecurring = itemIsRecurring(item);
                const rowHasDates = itemHasDates(item);
                const rowUsers = itemHasUsers(item);
                const row = document.createElement('tr');
                row.innerHTML = `
                <td>${renderItemCell(item)}</td>
                <td class="text-center">${Math.round(Number(item.quantity) || 0)}</td>
                @if ($account->allow_multi_taxation)
                    <td class="text-center">${item.tax_rate}%</td>
                @endif
                <td class="text-center ${showUserColumns ? '' : 'd-none'}">${rowUsers ? Math.max(1, Number(item.no_of_users || 1)) : '-'}</td>
                <td class="text-center ${showRecurringColumns ? '' : 'd-none'}">
                    <div>${rowRecurring ? `<span class="text-dark">${item.duration || '-'}</span> ` : ''}${item.frequency ? (frequencyLabels[item.frequency] || item.frequency) : '-'}</div>
                </td>
                <td class="text-center ${showRecurringColumns ? '' : 'd-none'}">
                    <div>${(rowRecurring || rowHasDates) ? formatDateToDisplay(item.start_date) : '-'}</div>
                    <div class="text-dark">${(rowRecurring || rowHasDates) ? formatDateToDisplay(item.end_date) : '-'}</div>
                </td>
                <td class="text-end">
                    <div>${formatCurrency(item.unit_price)}</div>
                    <small class="d-block small lh-sm fw-semibold text-success text-uppercase">(${Number(item.discount_percent || 0).toFixed(0)}% Off)</small>
                </td>
                <td class="text-end">${formatCurrency(item.discount_amount)}</td>
                <td class="text-end">
                    <div class="tableActionButton d-inline-flex gap-1">
                        <button type="button" class="bg03 color03 border-0 edit-item-btn" data-index="${index}">Edit</button>
                        <button type="button" class="bg04 color04 border-0 remove-item-btn" data-index="${index}">Delete</button>
                    </div>
                </td>
            `;
                manualItemsBody.appendChild(row);
            });

            document.querySelectorAll('.edit-item-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const index = Number(this.dataset.index);
                    const item = manualItems[index];
                    if (!item) return;

                    openAddItemForm();
                    editingManualItemIndex = index;
                    addManualItemBtn.innerHTML = 'Update Item <i class="fas fa-arrow-right btn-icon ms-1"></i>';
                    const formTitle = document.getElementById('addItemFormTitle');
                    if (formTitle) formTitle.textContent = 'Edit Item';

                    const itemSelect = document.getElementById('manual_item_itemid');
                    itemSelect.value = item.itemid || '';
                    if (itemSelect.value) {
                        itemSelect.dispatchEvent(new Event('change'));
                    }

                    document.getElementById('manual_item_quantity').value = Math.max(1, Math.round(
                        Number(item.quantity || 1)));
                    document.getElementById('manual_item_unit_price').value = Number(item
                        .unit_price || 0);
                    document.getElementById('manual_item_description').value = item
                        .item_description || '';
                    document.getElementById('manual_item_discount').value = Number(item
                        .discount_percent || 0);
                    document.getElementById('manual_item_tax_rate').value = Number(item.tax_rate ||
                        0);
                    document.getElementById('manual_item_frequency').value = item.frequency || '';
                    document.getElementById('manual_item_duration').value = item.duration || '';
                    setDateInputValue(manualStartInput, item.start_date || '');
                    setDateInputValue(manualEndInput, item.end_date || '');

                    toggleManualUsersField();
                    if (item.no_of_users) {
                        document.getElementById('manual_item_users').value = Math.max(1, Number(item
                            .no_of_users || 1));
                    }

                    toggleManualRecurringFields();
                    if (isRecurringFrequency(item.frequency || '') && manualEndInput && !
                        manualEndInput.value) {
                        setDateInputValue(
                            manualEndInput,
                            calculateEndDate(manualStartInput?.value || '', item.frequency ||
                                '', item.duration || '')
                        );
                    }

                    addManualItemBtn.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                });
            });

            document.querySelectorAll('.remove-item-btn').forEach(btn => {
                btn.addEventListener('click', function () {
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
            document.getElementById('manualDiscountTotal').textContent = '- ' + formatCurrency(roundedDiscountTotal);
            document.getElementById('manualTaxTotal').textContent = formatCurrency(roundedTaxTotal);
            document.getElementById('manualGrandTotal').textContent = formatCurrency(subtotal -
                roundedDiscountTotal + roundedTaxTotal);

            itemsDataInput.value = JSON.stringify(manualItems);
            const hiddenOrderIdInput = document.getElementById('orderid');
            if (hiddenOrderIdInput) {
                hiddenOrderIdInput.value = manualItems[0]?.orderid || '';
            }
        }

        btnNextToStep3.addEventListener('click', function (e) {
            e.preventDefault();

            // First check visible HTML5 required validation inside step 2
            const requiredFields = document.querySelectorAll('#step2 [required]');
            let allValid = true;
            for (const field of requiredFields) {
                if (field.disabled) continue;
                if (!field.checkValidity()) {
                    field.reportValidity();
                    allValid = false;
                    break;
                }
            }
            if (!allValid) {
                return;
            }

            if (manualItems.length === 0) {
                showToast('error', 'Please add at least one item.');
                return;
            }

            const clientId = clientSelect?.value || "{{ request('c', request('clientid')) }}";
            if (!clientId) {
                showToast('error', 'Please select a client before continuing.');
                return;
            }

            const invoiceTitle = invoiceTitleInput.value;
            invoiceTitleError.classList.add('d-none');

            // Save items to hidden input
            itemsDataInput.value = JSON.stringify(manualItems);

            const issueDateValue = document.getElementById('issue_date')?.value || '';
            const dueDateValue = document.getElementById('due_date')?.value || '';
            const notesValue = document.getElementById('notes')?.value || '';

            const saveRouteUrl = "{{ route('invoices.save-draft') }}";
            const saveUrlPath = saveRouteUrl.startsWith('http') ? new URL(saveRouteUrl).pathname : saveRouteUrl;
            fetch(saveUrlPath, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                        'content') || ''
                },
                body: JSON.stringify({
                    invoiceid: "{{ request('d', '') }}" || undefined,
                    clientid: clientId,
                    orderid: document.getElementById('orderid')?.value || null,
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
                        throw new Error(text || 'Failed to update invoice.');
                    }
                    const contentType = response.headers.get('content-type') || '';
                    return contentType.includes('application/json') ? response.json() : {};
                })
                .then((data) => {
                    const clientToken = encodeURIComponent(clientId);
                    let nextUrl = "{{ route('invoices.create') }}?step=3&c=" + clientToken;
                    if (isTaxInvoice) {
                        nextUrl += "&tax_invoice=1";
                    }
                    if (data && data.invoiceid) {
                        nextUrl += "&d=" + encodeURIComponent(data.invoiceid);
                    }
                    if (data && data.was_created) {
                        nextUrl += "&just_created=1";
                    }
                    window.location.href = nextUrl;
                })
                .catch((error) => {
                    console.error('Error updating invoice:', error);
                    showToast('error', 'Unable to update invoice right now. Please try again.');
                });
        });


        if (clientSelect) {
            clientSelect.addEventListener('change', function () {
                if (!this.value) return;
                const createRoute = "{{ route('invoices.create') }}";
                const createPath = createRoute.startsWith('http') ? new URL(createRoute).pathname : createRoute;
                let target = createPath + "?step=2&c=" + encodeURIComponent(this.value);
                if (isTaxInvoice) {
                    target += "&tax_invoice=1";
                }
                if (draftId) {
                    target += "&d=" + encodeURIComponent(draftId);
                }
                window.location.href = target;
            });
        }

        invoiceTitleInput.addEventListener('input', function () {
            if (this.value.trim()) {
                invoiceTitleError.classList.add('d-none');
            }
        });

        // Load invoice items when editing
        function loadItems() {
            if (!draftId) return;

            const routeUrl = "{{ route('invoices.get-draft', ['clientid' => '__CLIENTID__']) }}";
            const urlPath = routeUrl.startsWith('http') ? new URL(routeUrl).pathname : routeUrl;
            const draftUrl = new URL(urlPath.replace('__CLIENTID__', clientId), window.location.origin);
            draftUrl.searchParams.set('d', draftId);

            fetch(draftUrl.toString())
                .then(response => response.json())
                .then(data => {
                    console.log('Invoice data loaded:', data);
                    if (data.draft) {
                        if (data.draft.items && data.draft.items.length > 0) {
                            manualItems = data.draft.items.map(item => ({
                                invoice_itemid: item.invoice_itemid || null,
                                orderid: item.orderid || null,
                                itemid: item.itemid,
                                item_name: item.item_name,
                                item_description: item.item_description || '',
                                quantity: item.quantity,
                                unit_price: item.unit_price,
                                discount_percent: item.discount_percent || 0,
                                discount_amount: item.discount_amount || 0,
                                tax_rate: item.tax_rate,
                                no_of_users: item.no_of_users,
                                requires_user_fields: Boolean(item.requires_user_fields),
                                frequency: item.frequency,
                                duration: item.duration,
                                start_date: item.start_date,
                                end_date: item.end_date,
                                sequence: item.sequence || null,
                                tax_amount: item.tax_amount,
                                line_total: item.line_total
                            }));
                            renderManualItems();
                        }

                        if (data.draft.invoice_title) {
                            invoiceTitleInput.value = data.draft.invoice_title;
                            invoiceTitleError.classList.add('d-none');
                        }
                        if (data.draft.orderid) {
                            const hiddenOrderIdInput = document.getElementById('orderid');
                            if (hiddenOrderIdInput) {
                                hiddenOrderIdInput.value = data.draft.orderid;
                            }
                        }

                        if (data.draft.issue_date) {
                            setDateInputValue(document.getElementById('issue_date'), data.draft.issue_date);
                        }
                        if (data.draft.due_date) {
                            setDateInputValue(document.getElementById('due_date'), data.draft.due_date);
                        }
                        if (data.draft.notes) {
                            document.getElementById('notes').value = data.draft.notes;
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

        // Toggle form visibility
        function toggleAddItemForm() {
            if (addItemFormCard) {
                addItemFormCard.classList.toggle('d-none');
            }
            if (toggleAddItemFormBtn) {
                const isHidden = addItemFormCard ? addItemFormCard.classList.contains('d-none') : true;
                toggleAddItemFormBtn.innerHTML = isHidden ?
                    '<i class="fas fa-plus"></i> Add More Items' :
                    '<i class="fas fa-times"></i> Cancel';
            }
        }

        if (toggleAddItemFormBtn) {
            toggleAddItemFormBtn.addEventListener('click', toggleAddItemForm);
        }

        // Initialize
        if (toggleAddItemFormBtn) {
            toggleAddItemFormBtn.classList.add('d-none');
        }
        if (addItemFormCard) {
            addItemFormCard.classList.remove('d-none');
        }

        const invoiceForm = document.getElementById('invoiceForm');
        if (invoiceForm) {
            invoiceForm.addEventListener('submit', function (e) {
                e.preventDefault();
            });
        }

        loadItems();
        renderManualItems();
        updateStep2HeaderNumber();

        // Modal & AJAX Order Creation integration
        const openAddOrderModalBtn = document.getElementById('openAddOrderModalBtn');
        if (openAddOrderModalBtn) {
            openAddOrderModalBtn.addEventListener('click', function () {
                const currentClientId = clientSelect?.value || clientId;
                if (!currentClientId) {
                    showToast('error', 'Please select a client before continuing.');
                    return;
                }
                const modalEl = document.getElementById('editOrderModal');
                if (!modalEl) return;

                const editModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                const editForm = document.getElementById('editOrderForm');
                const editClientidInput = document.getElementById('edit_clientid');
                const editClientNameInput = document.getElementById('editClientName');
                const editOrderNumberEl = document.getElementById('editOrderNumber');
                const editItemSelect = document.getElementById('edit_itemid');
                const editDescriptionInput = document.getElementById('edit_item_description');
                const editQuantityInput = document.getElementById('edit_quantity');
                const editNoOfUsersInput = document.getElementById('edit_no_of_users');
                const editFrequencyInput = document.getElementById('edit_frequency');
                const editDurationInput = document.getElementById('edit_duration');
                const editStartDateInput = document.getElementById('edit_start_date');
                const editEndDateInput = document.getElementById('edit_end_date');
                const editDeliveryDateInput = document.getElementById('edit_delivery_date');
                const editClientDocidSelect = document.getElementById('edit_client_docid');
                const submitBtn = editForm ? editForm.querySelector('button[type="submit"]') : null;
                const methodInput = editForm ? editForm.querySelector('input[name="_method"]') : null;
                const modalTitle = document.getElementById('editOrderModalLabel');

                // Set to Add Mode
                if (editForm) {
                    editForm.action = "{{ route('orders.store') }}";
                    editForm.setAttribute('data-mode', 'add');
                }
                if (methodInput) {
                    methodInput.disabled = true; // disable PUT method input
                }

                if (modalTitle) {
                    modalTitle.innerHTML = 'Add Order';
                }
                if (editOrderNumberEl) {
                    editOrderNumberEl.textContent = '';
                }
                if (submitBtn) {
                    submitBtn.innerHTML = 'Add Order <i class="fas fa-arrow-right btn-icon ms-1"></i>';
                }

                // Prefill Client
                if (editClientidInput) {
                    editClientidInput.value = currentClientId;
                }
                if (editClientNameInput) {
                    if (clientSelect && clientSelect.selectedIndex >= 0) {
                        const selectedOption = clientSelect.options[clientSelect.selectedIndex];
                        editClientNameInput.value = selectedOption && selectedOption.value ? selectedOption.text.trim() : "{{ $selectedClientName }}";
                    } else {
                        editClientNameInput.value = "{{ $selectedClientName }}";
                    }
                }

                // Reset/Enable fields
                if (editItemSelect) {
                    editItemSelect.disabled = false;
                    editItemSelect.value = '';
                }
                if (editDescriptionInput) {
                    editDescriptionInput.value = '';
                }
                if (editQuantityInput) {
                    editQuantityInput.value = 1;
                }
                if (editNoOfUsersInput) {
                    editNoOfUsersInput.value = '';
                }
                if (editFrequencyInput) {
                    editFrequencyInput.value = 'One-Time';
                    editFrequencyInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
                if (editDurationInput) {
                    editDurationInput.value = 1;
                }

                if (editStartDateInput) {
                    editStartDateInput.disabled = false;
                    editStartDateInput.value = "{{ date('Y-m-d') }}";
                    if (editStartDateInput._flatpickr) {
                        editStartDateInput._flatpickr.setDate("{{ date('Y-m-d') }}", false);
                    }
                }
                if (editEndDateInput) {
                    editEndDateInput.value = '2099-12-31';
                    if (editEndDateInput._flatpickr) {
                        editEndDateInput._flatpickr.setDate('2099-12-31', false);
                    }
                }
                if (editDeliveryDateInput) {
                    editDeliveryDateInput.value = '';
                    if (editDeliveryDateInput._flatpickr) {
                        editDeliveryDateInput._flatpickr.clear();
                    }
                }

                // Populate client documents dynamically via AJAX
                if (editClientDocidSelect) {
                    editClientDocidSelect.innerHTML = '<option value="">Select</option>';
                    const documentsRoute = "{{ route('clients.documents.list', ['client' => '__CLIENT__']) }}".replace('__CLIENT__', currentClientId);
                    fetch(documentsRoute)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.documents) {
                                data.documents.forEach(doc => {
                                    const option = document.createElement('option');
                                    option.value = doc.client_docid;
                                    option.textContent = doc.title || doc.document_number || ('Document #' + doc.client_docid);
                                    editClientDocidSelect.appendChild(option);
                                });
                            }
                        })
                        .catch(err => console.error('Error fetching client documents:', err));
                }

                editModal.show();
            });
        }

        window.onOrderCreated = function (data) {
            const modalEl = document.getElementById('editOrderModal');
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
            // Restore PUT method if it was disabled for Add Mode
            const editForm = document.getElementById('editOrderForm');
            const methodInput = editForm ? editForm.querySelector('input[name="_method"]') : null;
            if (methodInput) {
                methodInput.disabled = false;
            }
            fetchUpdatedOrderItems();
        };

        function fetchUpdatedOrderItems() {
            const currentClientId = clientSelect?.value || clientId;
            if (!currentClientId) return;

            const orderItemsRoute = "{{ route('invoices.client-order-items') }}";
            const orderItemsPath = orderItemsRoute.startsWith('http') ? new URL(orderItemsRoute).pathname : orderItemsRoute;
            const url = `${orderItemsPath}?clientid=${encodeURIComponent(currentClientId)}`;

            fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(items => {
                    const selectEl = document.getElementById('manual_item_itemid');
                    if (!selectEl) return;

                    const currentValue = selectEl.value;

                    selectEl.innerHTML = '<option value="">Select item</option>';

                    items.forEach(orderItem => {
                        const option = document.createElement('option');
                        option.value = orderItem.itemid;
                        option.dataset.orderid = orderItem.orderid;
                        option.dataset.sellingPrice = orderItem.unit_price || 0;
                        option.dataset.taxRate = orderItem.tax_rate || 0;
                        option.dataset.userWise = orderItem.requires_user_fields ? '1' : '0';
                        option.dataset.description = orderItem.item_description || '';
                        option.dataset.itemName = orderItem.item_name || '';
                        const orderLabel = orderItem.display_order_number || orderItem.order_number || orderItem.orderid || 'Order';
                        option.textContent = `${orderLabel} - ${orderItem.item_name || 'Item'}`;
                        selectEl.appendChild(option);
                    });

                    selectEl.value = currentValue;
                })
                .catch(err => {
                    console.error('Error fetching updated order items:', err);
                });
        }
    })();
</script>
