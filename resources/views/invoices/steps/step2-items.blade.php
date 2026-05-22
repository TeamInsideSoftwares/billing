@php
    $selectedClientCurrency =
        optional($clients->firstWhere('clientid', request('c', request('clientid'))))->currency ?? 'INR';
    $selectedClient = $clients->firstWhere('clientid', request('c', request('clientid')));
    $selectedClientName = $selectedClient
        ? $selectedClient->business_name ?? ($selectedClient->contact_name ?? 'Unknown Client')
        : 'No Client Selected';
    $selectedClientEmail = $selectedClient->email ?? '';
    $isTaxInvoiceStep2 = request('tax_invoice', 0) == 1 || !empty($invoice?->ti_number);
    $initialHeaderNumberStep2 = $isTaxInvoiceStep2
        ? ($invoice?->ti_number ?:
        $nextTaxInvoiceNumber ?? $nextInvoiceNumber)
        : ($invoice?->pi_number ?:
        $nextInvoiceNumber);
    $orderItemsFlat = collect($orderItemsForClient ?? [])->values();
@endphp
<style>
    .invoice-compact-steps {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .invoice-compact-steps--right {
        margin-top: 0.35rem;
        justify-content: flex-end;
    }

    .invoice-compact-step {
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 999px;
        border: 1px solid #d1d5db;
        color: #6b7280;
        font-size: 0.74rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fff;
    }

    .invoice-compact-step.is-active {
        border-color: #2563eb;
        background: #2563eb;
        color: #fff;
    }
</style>
<!-- Step 2: Select Items -->
<div id="step2" class="invoice-step">
    {{-- Client Info Header with Back Button --}}
    <div class="invoice-client-header">
        <div class="invoice-client-header__row">
            <button type="button" id="btnBackToStep1" class="secondary-button invoice-back-btn">
                <i class="fas fa-arrow-left" class="text-sm"></i>
            </button>
            <div class="invoice-client-header__divider"></div>
            <div class="invoice-client-header__icon">
                <i class="fas fa-user"></i>
            </div>
            <div class="invoice-client-header__body">
                <div class="invoice-client-header__name">{{ $selectedClientName }}</div>
                @if ($selectedClientEmail)
                    <div class="invoice-client-header__email">{{ $selectedClientEmail }}</div>
                @endif
            </div>
            <div class="invoice-client-header__right">
                <div id="piNumberBadge" class="invoice-number-badge">
                    {{ $initialHeaderNumberStep2 }}
                </div>
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
            <label for="invoice_title" class="field-label">Invoice Title</label>
            <input type="text" id="invoice_title" name="invoice_title" class="form-input"
                placeholder="e.g. Website Development - Monthly Subscription" required>
            <div id="invoiceTitleError" class="invoice-field-error is-hidden">Invoice title is required.</div>
        </div>
        <div>
            <label for="issue_date" class="field-label">Issue Date</label>
            <input type="date" id="issue_date" name="issue_date" class="form-input" required
                value="{{ old('issue_date', request('d') && $invoice ? $invoice->issue_date?->format('Y-m-d') : date('Y-m-d')) }}">
        </div>
        <div>
            <label for="due_date" class="field-label">Due Date</label>
            <input type="date" id="due_date" name="due_date" class="form-input" required
                value="{{ old('due_date', request('d') && $invoice ? $invoice->due_date?->format('Y-m-d') : date('Y-m-d', strtotime('+7 days'))) }}">
        </div>
        <div>
            <label for="notes" class="field-label">Notes</label>
            <textarea id="notes" name="notes" rows="1" class="form-input invoice-notes-textarea"
                placeholder="Optional notes">{{ old('notes', request('d') && $invoice ? $invoice->notes : '') }}</textarea>
        </div>
    </div>

    <input type="hidden" name="clientid" value="{{ request('c', request('clientid')) }}">
    <input type="hidden" name="orderid" id="orderid" value="">
    <input type="hidden" name="invoice_number" value="{{ $initialHeaderNumberStep2 }}">
    <input type="hidden" name="items_data" id="items_data" value="">

    <div id="manualItemsSection" class="workflow-panel">
        <div class="panel-heading-row">
            <div>
                <h4 class="panel-heading-title">Select Items</h4>
                <p class="panel-heading-subtitle">Items are loaded from this client's orders.</p>
            </div>
            <div>
                <button type="button" id="toggleAddItemFormBtn" class="text-link invoice-add-item-btn">
                    <i class="fas fa-plus invoice-add-item-btn__icon"></i>
                    <span class="invoice-add-item-btn__text">Add More Items</span>
                </button>
            </div>
        </div>

        <div class="builder-card invoice-builder-card" id="addItemFormCard">
            <div class="manual-grid manual-grid-add-items">
                <div class="invoice-span-2">
                    <label for="manual_item_itemid" class="field-label small">Item</label>
                    <div style="display: flex; gap: 0.35rem; align-items: center;">
                        <select id="manual_item_itemid" class="form-input" style="flex: 1;">
                            <option value="">Select item</option>

                            @foreach ($orderItemsFlat as $orderItem)
                                <option value="{{ $orderItem['itemid'] }}"
                                    data-orderid="{{ $orderItem['orderid'] }}"
                                    data-selling-price="{{ $orderItem['unit_price'] ?? 0 }}"
                                    data-tax-rate="{{ $orderItem['tax_rate'] ?? 0 }}"
                                    data-user-wise="{{ (int) ($orderItem['requires_user_fields'] ?? 0) }}"
                                    data-description="{{ $orderItem['item_description'] ?? '' }}"
                                    data-item-name="{{ $orderItem['item_name'] ?? '' }}">
                                    {{ $orderItem['display_order_number'] ?? $orderItem['order_number'] ?? $orderItem['orderid'] ?? 'Order' }} -
                                    {{ $orderItem['item_name'] ?? 'Item' }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" id="openAddOrderModalBtn" class="secondary-button"
                            style="padding: 0 0.75rem; height: 38px; display: inline-flex; align-items: center; justify-content: center;"
                            title="Create new order">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label for="manual_item_quantity" class="field-label small">Qty</label>
                    <input type="number" id="manual_item_quantity" class="form-input" value="1"
                        min="1" step="1">
                </div>
                <div>
                    <label for="manual_item_unit_price" class="field-label small">Unit Price</label>
                    <input type="number" id="manual_item_unit_price" class="form-input" min="0"
                        step="0.01">
                </div>
                <div>
                    <label for="manual_item_discount" class="field-label small">Disc %</label>
                    <input type="number" id="manual_item_discount" class="form-input" min="0"
                        max="100" step="0.01" value="0">
                </div>
                @if ($account->allow_multi_taxation)
                    <div>
                        <label for="manual_item_tax_rate" class="field-label small">Tax</label>
                        <select id="manual_item_tax_rate" class="form-input">
                            <option value="0">No Tax</option>
                            @foreach ($taxes as $tax)
                                <option value="{{ $tax->rate }}">{{ $tax->tax_name }}
                                    ({{ number_format($tax->rate, 0) }}%)
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" id="manual_item_tax_rate" value="{{ $account->fixed_tax_rate ?? 0 }}">
                @endif
                <div id="manual_item_users_wrap" class="is-hidden">
                    <label for="manual_item_users" class="field-label small">Users</label>
                    <input type="number" id="manual_item_users" class="form-input" value="1" min="1"
                        step="1">
                </div>
                <div>
                    <label for="manual_item_frequency" class="field-label small">Freq</label>
                    <select id="manual_item_frequency" class="form-input">
                        <option value="">None</option>
                        <option value="One-Time">One-Time</option>
                        <option value="Day(s)">Day(s)</option>
                        <option value="Week(s)">Week(s)</option>
                        <option value="Month(s)">Month(s)</option>
                        <option value="Quarter(s)">Quarter(s)</option>
                        <option value="Year(s)">Year(s)</option>
                    </select>
                </div>
                <div id="manual_item_duration_wrap" class="is-hidden">
                    <label for="manual_item_duration" class="field-label small">Dur</label>
                    <input type="number" id="manual_item_duration" class="form-input" min="0"
                        step="1" placeholder="e.g. 12">
                </div>
                <div id="manual_item_start_date_wrap">
                    <label for="manual_item_start_date" class="field-label small">Start</label>
                    <input type="date" id="manual_item_start_date" class="form-input">
                </div>
                <div id="manual_item_end_date_wrap">
                    <label for="manual_item_end_date" class="field-label small">End</label>
                    <input type="date" id="manual_item_end_date" class="form-input">
                </div>
            </div>
            <div class="invoice-item-desc-row">
                <textarea id="manual_item_description" class="form-input invoice-item-desc-input" rows="1"
                    placeholder="Description (optional)"></textarea>
                <button type="button" id="addManualItemBtn" class="primary-button invoice-item-add-btn">Add</button>
            </div>
        </div>

        <div class="table-shell mt-3">
            <table class="data-table m-0 invoice-items-table is-hidden" id="manualItemsTable">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Price ({{ $selectedClientCurrency }})</th>
                        <th class="text-center">Disc %</th>
                        @if ($account->allow_multi_taxation)
                            <th class="text-center">Tax %</th>
                        @endif
                        <th id="manualUsersHeader" class="is-hidden text-center">Users</th>
                        <th>Freq</th>
                        <th id="manualDurationHeader" class="is-hidden text-center">Dur</th>
                        <th id="manualStartHeader" class="is-hidden">Start</th>
                        <th id="manualEndHeader" class="is-hidden">End</th>
                        <th class="text-right">Total ({{ $selectedClientCurrency }})</th>
                        <th class="text-center"></th>
                    </tr>
                </thead>
                <tbody id="manualItemsBody"></tbody>
            </table>
            <div id="manualItemsEmpty" class="empty-state">No items added yet.</div>
        </div>

        <div id="manualOrderSummary" class="totals-card totals-card--narrow is-hidden mt-3 ms-auto">
            <div class="total-row"><span>Subtotal</span><strong id="manualSubtotal">0</strong></div>
            <div class="total-row"><span>Discount</span><strong id="manualDiscountTotal">0</strong></div>
            <div class="total-row"><span>Tax</span><strong id="manualTaxTotal">0</strong></div>
            <div class="total-row total-row-grand"><span>Total</span><strong id="manualGrandTotal">0</strong></div>
        </div>
    </div>

    <div class="mt-4">
        <button type="button" class="primary-button w-100 invoice-next-btn" id="btnNextToStep3" disabled>Review &
            Terms
            &rarr;</button>
    </div>
</div>

<script>
    (function() {
        const addManualItemBtn = document.getElementById('addManualItemBtn');
        const manualItemsBody = document.getElementById('manualItemsBody');
        const manualItemsTable = document.getElementById('manualItemsTable');
        const manualItemsEmpty = document.getElementById('manualItemsEmpty');
        const manualSummary = document.getElementById('manualOrderSummary');
        const btnNextToStep3 = document.getElementById('btnNextToStep3');
        const toggleAddItemFormBtn = document.getElementById('toggleAddItemFormBtn');
        const addItemFormCard = document.getElementById('addItemFormCard');
        const btnBackToStep1 = document.getElementById('btnBackToStep1');
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
        const isTaxInvoice = @json($isTaxInvoiceStep2);
        const draftId = "{{ request('d', '') }}";
        const clientId = "{{ request('c', request('clientid', $invoice?->clientid ?? '')) }}";
        const fallbackPiNumber = "{{ $nextInvoiceNumber }}";
        const fallbackTiNumber = "{{ $nextTaxInvoiceNumber ?? $nextInvoiceNumber }}";
        const accountHasUsers = @json((bool) ($account->have_users ?? false));
        const ONE_TIME_MAX_END_DATE = '2099-12-31';
        const orderItemsRouteTemplate = @json(route('invoices.order-items', ['orderid' => '__ORDERID__']));
        const TAX_READY_TOAST_KEY = 'invoice_tax_ready_toast';
        let draftPiNumber = '';
        let draftTiNumber = '';
        const orderDatePrefillCache = {};
        let currentOrderDatePrefill = null;

        function showSuccessToast(message) {
            const text = String(message || '').trim();
            if (!text) return;
            let container = document.getElementById('app-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'app-toast-container';
                container.className = 'app-toast-container';
                document.body.appendChild(container);
            }
            const toast = document.createElement('div');
            toast.className = 'app-toast app-toast-success';
            toast.innerHTML = '<i class="fas fa-check-circle toast-icon"></i><span></span>';
            const label = toast.querySelector('span');
            if (label) label.textContent = text;
            toast.addEventListener('click', () => toast.remove());
            container.appendChild(toast);
            window.setTimeout(() => {
                toast.classList.add('app-toast-leaving');
                window.setTimeout(() => toast.remove(), 220);
            }, 4200);
        }

        function consumeTaxReadyToast() {
            try {
                const message = window.localStorage.getItem(TAX_READY_TOAST_KEY);
                if (!message) return;
                window.localStorage.removeItem(TAX_READY_TOAST_KEY);
                showSuccessToast(message);
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

        function renderItemCell(item) {
            const name = escapeHtml(item.item_name || 'Item');
            const description = escapeHtml(item.item_description || '').trim();
            return description ? `
            <div class="invoice-item-cell-title">${name}</div>
            <div class="invoice-item-cell-desc">${description}</div>
        ` : `<div class="invoice-item-cell-title">${name}</div>`;
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

        function applyOrderDatePrefill(prefill) {
            if (!prefill || editingManualItemIndex !== null) return;
            if (!manualStartInput || !manualEndInput) return;

            const hasManualStart = Boolean((manualStartInput.value || '').trim());
            const hasManualEnd = Boolean((manualEndInput.value || '').trim());
            if (hasManualStart || hasManualEnd) return;

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
        document.getElementById('manual_item_itemid').addEventListener('change', async function() {
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
            applyOrderDatePrefill(prefill);
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
                wrap.classList.add('is-hidden');
                usersInput.value = 1;
                return;
            }
            const show = isManualItemUserWise();
            wrap.classList.toggle('is-hidden', !show);
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
            const normalized = value || '';
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
            setDateInputValue(manualEndInput, nextValue);
        }

        function scheduleManualEndDateSync() {
            window.requestAnimationFrame(syncManualEndDateFromInputs);
        }

        function toggleManualRecurringFields() {
            if (!manualDurationWrap || !manualDurationInput || !manualStartWrap || !manualEndWrap || !
                manualStartInput || !manualEndInput) return;
            const showRecurring = isRecurringFrequency(manualFrequencyInput?.value || '');
            manualDurationWrap.classList.toggle('is-hidden', !showRecurring);
            manualStartWrap.classList.remove('is-hidden');
            manualEndWrap.classList.remove('is-hidden');
            if (showRecurring) {
                const durationValue = Number(manualDurationInput.value || 0);
                if (!manualDurationInput.value || durationValue <= 0) {
                    manualDurationInput.value = '1';
                }
            } else {
                manualDurationInput.value = '';
            }
        }

        manualFrequencyInput?.addEventListener('change', function() {
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
            const durationHeader = document.getElementById('manualDurationHeader');
            const startHeader = document.getElementById('manualStartHeader');
            const endHeader = document.getElementById('manualEndHeader');
            if (usersHeader) usersHeader.classList.toggle('is-hidden', !showUserColumns);
            if (durationHeader) durationHeader.classList.toggle('is-hidden', !showRecurringColumns);
            if (startHeader) startHeader.classList.toggle('is-hidden', !showRecurringColumns);
            if (endHeader) endHeader.classList.toggle('is-hidden', !showRecurringColumns);

            return {
                showRecurringColumns,
                showUserColumns
            };
        }

        function resetManualItemForm() {
            editingManualItemIndex = null;
            currentOrderDatePrefill = null;
            addManualItemBtn.textContent = 'Add';
            document.getElementById('manual_item_itemid').value = '';
            document.getElementById('manual_item_quantity').value = '1';
            document.getElementById('manual_item_unit_price').value = '';
            document.getElementById('manual_item_discount').value = '0';
            document.getElementById('manual_item_frequency').value = '';
            document.getElementById('manual_item_duration').value = '';
            document.getElementById('manual_item_description').value = '';
            setDateInputValue(manualStartInput, '');
            setDateInputValue(manualEndInput, '');
            toggleManualRecurringFields();
            @if ($account->have_users)
                toggleManualUsersField();
            @endif
        }

        function openAddItemForm() {
            if (addItemFormCard) {
                addItemFormCard.classList.remove('is-hidden');
            }
            if (toggleAddItemFormBtn) {
                toggleAddItemFormBtn.innerHTML =
                    '<i class="fas fa-times invoice-add-item-btn__icon"></i><span class="invoice-add-item-btn__text">Cancel</span>';
            }
        }

        addManualItemBtn.addEventListener('click', function() {
            const itemId = document.getElementById('manual_item_itemid').value;
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

            if (!itemId) {
                alert('Please select an item.');
                return;
            }

            if (quantity <= 0) {
                alert('Quantity must be greater than 0.');
                return;
            }

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
                manualItems[editingManualItemIndex] = newItem;
            } else {
                manualItems.push(newItem);
            }
            renderManualItems();

            resetManualItemForm();
        });

        function renderManualItems() {
            if (manualItems.length === 0) {
                manualItemsTable.classList.add('is-hidden');
                manualItemsEmpty.classList.remove('is-hidden');
                manualSummary.classList.add('is-hidden');
                btnNextToStep3.disabled = true;
                syncManualHeaders();
                return;
            }

            manualItemsTable.classList.remove('is-hidden');
            manualItemsEmpty.classList.add('is-hidden');
            manualSummary.classList.remove('is-hidden');
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
                <td class="text-center">${formatCurrency(item.unit_price)}</td>
                <td class="text-center">${Number(item.discount_percent || 0).toFixed(0)}%</td>
                @if ($account->allow_multi_taxation)
                    <td class="text-center">${item.tax_rate}%</td>
                @endif
                <td class="text-center ${showUserColumns ? '' : 'is-hidden'}">${rowUsers ? Math.max(1, Number(item.no_of_users || 1)) : '-'}</td>
                <td>${item.frequency ? (frequencyLabels[item.frequency] || item.frequency) : '-'}</td>
                <td class="text-center ${showRecurringColumns ? '' : 'is-hidden'}">${rowRecurring ? (item.duration || '-') : '-'}</td>
                <td class="text-center ${showRecurringColumns ? '' : 'is-hidden'}">${(rowRecurring || rowHasDates) ? (item.start_date || '-') : '-'}</td>
                <td class="text-center ${showRecurringColumns ? '' : 'is-hidden'}">${(rowRecurring || rowHasDates) ? (item.end_date || '-') : '-'}</td>
                <td class="text-center">${formatCurrency(Math.max(0, Number(item.discount_amount || 0) || Number(item.line_total || 0)))}</td>
                <td class="text-center text-nowrap">
                    <button type="button" class="text-action-btn edit edit-item-btn" data-index="${index}">Edit</button>
                    <button type="button" class="text-action-btn delete remove-item-btn" data-index="${index}">Delete</button>
                </td>
            `;
                manualItemsBody.appendChild(row);
            });

            document.querySelectorAll('.edit-item-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const index = Number(this.dataset.index);
                    const item = manualItems[index];
                    if (!item) return;

                    openAddItemForm();
                    editingManualItemIndex = index;
                    addManualItemBtn.textContent = 'Update';

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
                btn.addEventListener('click', function() {
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
            document.getElementById('manualDiscountTotal').textContent = formatCurrency(roundedDiscountTotal);
            document.getElementById('manualTaxTotal').textContent = formatCurrency(roundedTaxTotal);
            document.getElementById('manualGrandTotal').textContent = formatCurrency(subtotal -
                roundedDiscountTotal + roundedTaxTotal);

            itemsDataInput.value = JSON.stringify(manualItems);
            const hiddenOrderIdInput = document.getElementById('orderid');
            if (hiddenOrderIdInput) {
                hiddenOrderIdInput.value = manualItems[0]?.orderid || '';
            }
        }

        btnNextToStep3.addEventListener('click', function() {
            if (manualItems.length === 0) {
                alert('Please add at least one item.');
                return;
            }

            const clientId = "{{ request('c', request('clientid')) }}";
            if (!clientId) {
                alert('Please select a client before continuing.');
                return;
            }

            const invoiceTitle = invoiceTitleInput.value;
            if (!invoiceTitle.trim()) {
                invoiceTitleError.style.display = 'block';
                invoiceTitleInput.focus();
                return;
            }

            invoiceTitleError.style.display = 'none';

            // Save items to hidden input
            itemsDataInput.value = JSON.stringify(manualItems);

            const issueDateValue = document.getElementById('issue_date')?.value || '';
            const dueDateValue = document.getElementById('due_date')?.value || '';
            const notesValue = document.getElementById('notes')?.value || '';

            fetch("{{ route('invoices.save-draft') }}", {
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
                    alert('Unable to update invoice right now. Please try again.');
                });
        });

        btnBackToStep1.addEventListener('click', function() {
            const clientId = "{{ request('c', request('clientid')) }}";
            const clientToken = encodeURIComponent(clientId);
            let backUrl = "{{ route('invoices.create') }}?step=1&c=" + clientToken;
            if (isTaxInvoice) {
                backUrl += "&tax_invoice=1";
            }
            window.location.href = backUrl;
        });

        invoiceTitleInput.addEventListener('input', function() {
            if (this.value.trim()) {
                invoiceTitleError.style.display = 'none';
            }
        });

        // Load invoice items when editing
        function loadItems() {
            if (!draftId) return;

            const draftUrl = new URL("{{ route('invoices.get-draft', ['clientid' => '__CLIENTID__']) }}".replace(
                '__CLIENTID__', clientId), window.location.origin);
            draftUrl.searchParams.set('d', draftId);

            fetch(draftUrl.toString())
                .then(response => response.json())
                .then(data => {
                    console.log('Invoice data loaded:', data);
                    if (data.draft) {
                        if (data.draft.items && data.draft.items.length > 0) {
                            manualItems = data.draft.items.map(item => ({
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
                addItemFormCard.classList.toggle('is-hidden');
            }
            if (toggleAddItemFormBtn) {
                const isHidden = addItemFormCard ? addItemFormCard.classList.contains('is-hidden') : true;
                toggleAddItemFormBtn.innerHTML = isHidden ?
                    '<i class="fas fa-plus invoice-add-item-btn__icon"></i><span class="invoice-add-item-btn__text">Add More Items</span>' :
                    '<i class="fas fa-times invoice-add-item-btn__icon"></i><span class="invoice-add-item-btn__text">Cancel</span>';
            }
        }

        if (toggleAddItemFormBtn) {
            toggleAddItemFormBtn.addEventListener('click', toggleAddItemForm);
        }

        // Initialize
        if (toggleAddItemFormBtn) {
            toggleAddItemFormBtn.classList.add('is-hidden');
        }
        if (addItemFormCard) {
            addItemFormCard.classList.remove('is-hidden');
        }
        loadItems();
        updateStep2HeaderNumber();

        // Modal & AJAX Order Creation integration
        const openAddOrderModalBtn = document.getElementById('openAddOrderModalBtn');
        if (openAddOrderModalBtn) {
            openAddOrderModalBtn.addEventListener('click', function() {
                if (!clientId) {
                    alert('Please select a client before continuing.');
                    return;
                }
                const iframe = document.getElementById('addOrderIframe');
                if (iframe) {
                    iframe.src = "{{ route('orders.create') }}?c=" + encodeURIComponent(clientId) +
                        "&iframe=1";
                }
                const modalEl = document.getElementById('addOrderModal');
                if (modalEl) {
                    let modal = bootstrap.Modal.getInstance(modalEl);
                    if (!modal) {
                        modal = new bootstrap.Modal(modalEl);
                    }
                    modal.show();
                }
            });
        }

        window.onOrderCreated = function(data) {
            const modalEl = document.getElementById('addOrderModal');
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
            fetchUpdatedOrderItems();
        };

        function fetchUpdatedOrderItems() {
            if (!clientId) return;

            const url = `{{ route('invoices.client-order-items') }}?clientid=${encodeURIComponent(clientId)}`;

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

<!-- Modal for creating a new order -->
<div class="modal fade" id="addOrderModal" tabindex="-1" aria-labelledby="addOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 90%; width: 1000px;">
        <div class="modal-content rounded-panel"
            style="border: none; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);">
            <div class="modal-header modal-header-custom"
                style="padding: 1rem 1.5rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                <h5 class="modal-title" id="addOrderModalLabel"
                    style="font-weight: 600; color: #1e293b; margin: 0; font-size: 1.1rem;">Create New Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 0; height: 70vh; background: #fff;">
                <iframe id="addOrderIframe" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>
