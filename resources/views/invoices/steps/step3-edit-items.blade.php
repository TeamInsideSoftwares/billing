@php
    $normalizeTaxState = static fn ($value) => preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $value)));
    $selectedInvoiceClient = $clients->firstWhere('clientid', request('clientid', request('c')));
    $selectedClientCurrency = optional($selectedInvoiceClient)->currency ?? 'INR';
    $invoiceClientState = $normalizeTaxState(optional($selectedInvoiceClient)->state ?? '');
    $invoiceAccountState = $normalizeTaxState(optional($account)->state ?? '');
    $sameStateGstForInvoice = $invoiceClientState !== '' && $invoiceAccountState !== '' && $invoiceClientState === $invoiceAccountState;
@endphp
<!-- Step 3: Edit Items (For Orders & Renewal) -->
<div id="step3" class="invoice-step">
    <div class="invoice-step-toolbar">
        <button type="button" id="btnBackToStep2" class="secondary-button" style="padding: 0.5rem 1rem;">&larr; Back</button>
        <div class="invoice-side-meta">
            <span class="invoice-meta-label">PI</span>
            <strong class="invoice-meta-value">{{ $nextInvoiceNumber }}</strong>
        </div>
    </div>

    <div style="margin-bottom: 1.5rem;">
        <label for="invoice_title" class="field-label">Invoice Title</label>
        <input type="text" id="invoice_title" name="invoice_title" class="form-input" placeholder="e.g. Website Development - Monthly Subscription" required>
        <div id="invoiceTitleError" style="display:none; margin-top: 0.35rem; color: #b91c1c; font-size: 0.8rem; font-weight: 600;">Invoice title is required.</div>
    </div>

    <input type="hidden" name="clientid" value="{{ request('clientid', request('c')) }}">
    <input type="hidden" name="orderid" value="{{ request('orderid', '') }}">
    <input type="hidden" name="subtotal" id="subtotal" value="0.00">
    <input type="hidden" name="tax_total" id="tax_total" value="0.00">
    <input type="hidden" name="discount_total" id="discount_total" value="0.00">
    <input type="hidden" name="grand_total" id="grand_total" value="0.00">
    <input type="hidden" name="items_data" id="items_data" value="">
    <input type="hidden" name="currency_code" id="currency_code" value="{{ $selectedClientCurrency }}">

    <div id="itemsSection" class="workflow-panel">
        <div class="panel-heading-row">
            <div>
                <h4 style="margin: 0; font-size: 1rem; color: #111827;">
                    @if(request('orderid'))
                        Edit Items from Order
                    @else
                        Edit Invoice Items
                    @endif
                </h4>
                <p style="margin: 0.2rem 0 0 0; color: #6b7280; font-size: 0.85rem;">Adjust quantity, pricing, tax, and other details before proceeding.</p>
            </div>
        </div>

        @if(request('orderid'))
        <div id="orderSummaryInline" style="display: none; margin-bottom: 1rem; padding: 0.85rem 1rem; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px;">
            <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                <div>
                    <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.04em;">Source Order</div>
                    <div id="orderSummaryTitle" style="margin-top: 0.25rem; font-size: 0.95rem; font-weight: 600; color: #111827;">Source Order Details</div>
                </div>
                <div id="orderSummaryDetails" style="display: flex; gap: 0.6rem; flex-wrap: wrap; align-items: center;"></div>
            </div>
        </div>
        @endif

        <div class="table-shell">
            <table class="data-table" id="itemsTable" style="margin: 0; font-size: 0.83rem;">
                <thead id="itemsTableHead">
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th id="itemsUsersHeader" style="display:none;">Users</th>
                        <th>Disc %</th>
                        @if($account->allow_multi_taxation)
                        <th>Tax %</th>
                        @endif
                        <th>Freq</th>
                        <th id="itemsDurationHeader">Dur</th>
                        <th id="itemsStartHeader" style="display:none;">Start Date</th>
                        <th id="itemsEndHeader" style="display:none;">End Date</th>
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
                <div class="total-row"><span>Discount</span><strong id="discountDisplay">INR 0.00</strong></div>
                <div id="step3TaxRow" class="total-row">
                    <span id="step3TaxLabel">{{ $sameStateGstForInvoice ? 'Tax (CGST + SGST)' : 'Tax (IGST)' }}</span>
                    <strong id="taxDisplay">INR 0.00</strong>
                </div>
                <div class="total-row total-row-grand"><span>Grand Total</span><strong id="grandTotalDisplay">INR 0.00</strong></div>
            </div>
        </div>
    </div>

    <div style="margin-top: 2rem;">
        <button type="button" class="primary-button" id="btnNextToStep4" style="width: 100%; padding: 1rem;">Review & Terms &rarr;</button>
    </div>
</div>

<script>
(function() {
    const clientId = "{{ request('clientid', request('c')) }}";
    const invoiceFor = "{{ request('invoice_for') }}";
    const orderId = "{{ request('orderid', '') }}";
    const accountHasUsers = @json((bool) ($account->have_users ?? false));
    const sameStateGstForInvoice = @json($sameStateGstForInvoice);
    const itemsBody = document.getElementById('itemsBody');
    const currencyCodeInput = document.getElementById('currency_code');
    const btnNextToStep4 = document.getElementById('btnNextToStep4');
    const btnBackToStep2 = document.getElementById('btnBackToStep2');
    const itemsDataInput = document.getElementById('items_data');
    const invoiceTitleInput = document.getElementById('invoice_title');
    const invoiceTitleError = document.getElementById('invoiceTitleError');

    let invoiceItems = [];

    function getCurrencyCode() {
        return currencyCodeInput.value || '{{ $selectedClientCurrency }}';
    }

    function formatCurrency(amount) {
        return `${getCurrencyCode()} ${Number(amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }

    function updateTaxDisplay(taxTotal) {
        const taxRow = document.getElementById('step3TaxRow');
        const taxLabel = document.getElementById('step3TaxLabel');
        const taxDisplay = document.getElementById('taxDisplay');

        if (taxRow) taxRow.style.display = '';
        if (taxLabel) taxLabel.textContent = sameStateGstForInvoice ? 'Tax (CGST + SGST)' : 'Tax (IGST)';
        if (taxDisplay) taxDisplay.textContent = formatCurrency(taxTotal);
    }

    function itemSupportsUserFields(item) {
        return accountHasUsers && Boolean(item && item.requires_user_fields);
    }

    function itemHasRecurringFrequency(item) {
        return Boolean(item && item.frequency) && item.frequency !== 'one-time';
    }

    function calculateEndDate(startDate, frequency, duration) {
        if (!startDate || !frequency || frequency === 'one-time' || !duration) {
            return '';
        }

        const date = new Date(startDate);
        const steps = Math.max(0, Number(duration) || 0);

        if (steps <= 0 || Number.isNaN(date.getTime())) {
            return '';
        }

        switch (frequency) {
            case 'daily':
                date.setDate(date.getDate() + steps);
                break;
            case 'weekly':
                date.setDate(date.getDate() + (steps * 7));
                break;
            case 'bi-weekly':
                date.setDate(date.getDate() + (steps * 14));
                break;
            case 'monthly':
                date.setMonth(date.getMonth() + steps);
                break;
            case 'quarterly':
                date.setMonth(date.getMonth() + (steps * 3));
                break;
            case 'semi-annually':
                date.setMonth(date.getMonth() + (steps * 6));
                break;
            case 'yearly':
                date.setFullYear(date.getFullYear() + steps);
                break;
            default:
                return '';
        }

        return date.toISOString().split('T')[0];
    }

    function normalizeItem(item) {
        const normalizedItem = {
            ...item,
            quantity: Math.max(1, Math.round(Number(item.quantity || 1))),
            unit_price: Number(item.unit_price || 0),
            tax_rate: Number(item.tax_rate || 0),
            discount_percent: Number(item.discount_percent || 0),
            discount_amount: Number(item.discount_amount || 0),
            duration: item.duration ?? '',
            frequency: item.frequency ?? '',
            line_total: Number(item.line_total || 0),
            requires_user_fields: accountHasUsers && Boolean(item && item.requires_user_fields),
            no_of_users: null,
            start_date: item.start_date || '',
            end_date: item.end_date || '',
        };

        if (itemSupportsUserFields(normalizedItem)) {
            normalizedItem.no_of_users = Math.max(1, Number(item.no_of_users || 1));
        }

        if (itemHasRecurringFrequency(normalizedItem) && !normalizedItem.end_date && normalizedItem.start_date && normalizedItem.duration) {
            normalizedItem.end_date = calculateEndDate(normalizedItem.start_date, normalizedItem.frequency, normalizedItem.duration);
        }

        if (!itemHasRecurringFrequency(normalizedItem)) {
            normalizedItem.start_date = '';
            normalizedItem.end_date = '';
        }

        return normalizedItem;
    }

    function calculateLineInputTotal(item) {
        const qty = Math.max(1, Math.round(Number(item.quantity || 1)));
        const price = Number(item.unit_price || 0);
        const users = itemSupportsUserFields(item) ? Math.max(1, Number(item.no_of_users || 1)) : 1;
        const durationMultiplier = (item.frequency && item.frequency !== 'one-time' && Number(item.duration || 0) > 0)
            ? Number(item.duration || 0)
            : 1;
        return qty * price * users * durationMultiplier;
    }

    function roundTaxUp(value) {
        return Math.ceil(Math.max(0, Number(value) || 0));
    }

    function roundDiscountDown(value) {
        return Math.floor(Math.max(0, Number(value) || 0));
    }

    function normalizeItemAmounts(item) {
        const taxRate = Number(item.tax_rate || 0);
        const discountPercent = Math.min(100, Math.max(0, Number(item.discount_percent || 0)));
        const lineTotal = calculateLineInputTotal(item);
        const discountAmount = roundDiscountDown(lineTotal * (discountPercent / 100));
        const taxAmount = roundTaxUp(Math.max(0, lineTotal - discountAmount) * (taxRate / 100));

        item.line_total = lineTotal;
        item.discount_percent = discountPercent;
        item.discount_amount = discountAmount;
        item.tax_amount = taxAmount;
    }

    function syncConditionalHeaders() {
        const showUserColumns = invoiceItems.some(itemSupportsUserFields);
        const showDurationColumns = invoiceItems.some(itemHasRecurringFrequency);
        const showDateColumns = invoiceItems.some(itemHasRecurringFrequency);

        document.getElementById('itemsUsersHeader').style.display = showUserColumns ? '' : 'none';
        document.getElementById('itemsDurationHeader').style.display = showDurationColumns ? '' : 'none';
        document.getElementById('itemsStartHeader').style.display = showDateColumns ? '' : 'none';
        document.getElementById('itemsEndHeader').style.display = showDateColumns ? '' : 'none';
    }

    function renderOrderSummary(order) {
        const summaryInline = document.getElementById('orderSummaryInline');
        if (!summaryInline || !order) {
            return;
        }

        const verifiedBadge = String(order.is_verified).toLowerCase() === 'yes'
            ? '<span style="padding: 0.2rem 0.5rem; background: #dcfce7; color: #166534; border-radius: 4px; font-size: 0.75rem; font-weight: 600; margin-left: 0.5rem;">Verified</span>'
            : '<span style="padding: 0.2rem 0.5rem; background: #fee2e2; color: #991b1b; border-radius: 4px; font-size: 0.75rem; font-weight: 600; margin-left: 0.5rem;">Unverified</span>';

        summaryInline.style.display = 'block';
        currencyCodeInput.value = order.currency || getCurrencyCode();
        document.getElementById('orderSummaryTitle').innerHTML = `<strong>${order.order_title || 'Untitled Order'}</strong> ${verifiedBadge}`;
        document.getElementById('orderSummaryDetails').innerHTML =
            `<span class="invoice-step-badge" style="background:#eef2ff; color:#3730a3;">#${order.order_number}</span>` +
            `<span class="invoice-step-badge" style="background:#f8fafc; color:#475569;">${order.order_date}</span>` +
            `<span class="invoice-step-badge" style="background:#f8fafc; color:#475569;">${order.item_count || 0} item(s)</span>` +
            `<span class="invoice-step-badge" style="background:#f8fafc; color:#475569;">${order.currency} ${Number(order.grand_total).toLocaleString('en-IN', {minimumFractionDigits: 2})}</span>`;
    }

    function loadItems() {
        fetch("{{ route('invoices.get-draft', ['clientid' => '__CLIENTID__']) }}".replace('__CLIENTID__', clientId))
            .then(response => response.json())
            .then(data => {
                const draftItems = Array.isArray(data && data.draft && data.draft.items) ? data.draft.items : [];
                const draftTitle = data && data.draft ? (data.draft.invoice_title || '') : '';

                if (draftTitle) {
                    document.getElementById('invoice_title').value = draftTitle;
                }

                if (invoiceFor === 'orders' && orderId) {
                    loadOrderItems(orderId);
                } else if (draftItems.length > 0) {
                    invoiceItems = draftItems.map(normalizeItem);
                    renderItems();
                }

                if (orderId) {
                    fetch(`{{ route('invoices.order-items', ['orderid' => '__ORDERID__']) }}`.replace('__ORDERID__', orderId))
                        .then(response => response.json())
                        .then(orderData => {
                            if (orderData && orderData.order) {
                                renderOrderSummary(orderData.order);
                            }
                        })
                        .catch(() => {});
                }
            })
            .catch(() => {
                @if(request('invoice_for') === 'orders' && request('orderid'))
                loadOrderItems("{{ request('orderid') }}");
                @endif
            });
    }

    @if(request('invoice_for') === 'orders' && request('orderid'))
    function loadOrderItems(selectedOrderId) {
        fetch(`{{ route('invoices.order-items', ['orderid' => '__ORDERID__']) }}`.replace('__ORDERID__', selectedOrderId))
            .then(response => response.json())
            .then(data => {
                if (data && data.order) {
                    renderOrderSummary(data.order);
                }
                invoiceItems = (data.items || []).map(normalizeItem);
                renderItems();
            })
            .catch(() => {
                console.error('Failed to load order items');
            });
    }
    @endif

    function renderItems() {
        itemsBody.innerHTML = '';
        let subtotal = 0;
        let taxTotal = 0;
        let discountTotal = 0;
        const showUserColumns = invoiceItems.some(itemSupportsUserFields);
        const showDurationColumns = invoiceItems.some(itemHasRecurringFrequency);
        const showDateColumns = invoiceItems.some(itemHasRecurringFrequency);

        syncConditionalHeaders();

        invoiceItems.forEach((item, index) => {
            normalizeItemAmounts(item);
            const lineDiscount = Number(item.discount_amount || 0);
            const lineTax = Number(item.tax_amount || 0);
            subtotal += item.line_total;
            discountTotal += lineDiscount;
            taxTotal += lineTax;
            const showUsersForRow = itemSupportsUserFields(item);
            const showDatesForRow = itemHasRecurringFrequency(item);

            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" class="form-input item-name" data-index="${index}" value="${item.item_name}" style="min-width: 150px;"></td>
                <td><input type="number" class="form-input item-quantity" data-index="${index}" value="${item.quantity}" min="1" step="1" style="width: 80px;"></td>
                <td><input type="number" class="form-input item-price" data-index="${index}" value="${item.unit_price}" min="0" step="0.01" style="width: 100px;"></td>
                <td style="display:${showUserColumns ? '' : 'none'};">
                    ${showUsersForRow
                        ? `<input type="number" class="form-input item-users" data-index="${index}" value="${item.no_of_users || 1}" min="1" step="1" style="width: 70px;">`
                        : '<span style="color:#9ca3af;">-</span>'}
                </td>
                <td><input type="number" class="form-input item-discount" data-index="${index}" value="${item.discount_percent || 0}" min="0" max="100" step="0.01" style="width: 85px;"></td>
                @if($account->allow_multi_taxation)
                <td>
                    <select class="form-input item-tax-rate" data-index="${index}" style="width: 90px;">
                        <option value="0" ${!item.tax_rate ? 'selected' : ''}>0%</option>
                        @foreach($taxes as $tax)
                        <option value="{{ $tax->rate }}" ${item.tax_rate == {{ $tax->rate }} ? 'selected' : ''}>{{ $tax->rate }}%</option>
                        @endforeach
                    </select>
                </td>
                @endif
                <td>
                    <select class="form-input item-frequency" data-index="${index}" style="width: 100px;">
                        <option value="">None</option>
                        @foreach(['one-time', 'daily', 'weekly', 'bi-weekly', 'monthly', 'quarterly', 'semi-annually', 'yearly'] as $freq)
                        <option value="{{ $freq }}" ${(item.frequency || '') === '{{ $freq }}' ? 'selected' : ''}>{{ ucfirst(str_replace('-', ' ', $freq)) }}</option>
                        @endforeach
                    </select>
                </td>
                <td style="display:${showDurationColumns ? '' : 'none'};">
                    ${showDatesForRow
                        ? `<input type="number" class="form-input item-duration" data-index="${index}" value="${item.duration || ''}" min="0" step="1" style="width: 70px;" placeholder="-">`
                        : '<span style="color:#9ca3af;">-</span>'}
                </td>
                <td style="display:${showDateColumns ? '' : 'none'};">
                    ${showDatesForRow
                        ? `<input type="date" class="form-input item-start-date" data-index="${index}" value="${item.start_date || ''}" style="min-width: 135px;">`
                        : '<span style="color:#9ca3af;">-</span>'}
                </td>
                <td style="display:${showDateColumns ? '' : 'none'};">
                    ${showDatesForRow
                        ? `<input type="date" class="form-input item-end-date" data-index="${index}" value="${item.end_date || ''}" style="min-width: 135px;">`
                        : '<span style="color:#9ca3af;">-</span>'}
                </td>
                <td style="text-align: right; font-weight: 600;" class="item-total" data-index="${index}">${formatCurrency(Math.max(0, Number(item.line_total || 0) - Number(item.discount_amount || 0)))}</td>
                <td>
                    <button type="button" class="remove-item-btn" data-index="${index}" title="Remove" style="background: none; border: none; color: #ef4444; cursor: pointer;"><i class="fas fa-trash"></i></button>
                </td>
            `;
            itemsBody.appendChild(row);
        });

        document.querySelectorAll('.item-quantity, .item-price, .item-discount, .item-tax-rate, .item-users, .item-frequency, .item-duration, .item-start-date, .item-end-date').forEach(input => {
            input.addEventListener('change', recalculateItems);
        });

        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                invoiceItems.splice(parseInt(this.dataset.index, 10), 1);
                renderItems();
            });
        });

        const roundedDiscountTotal = roundDiscountDown(discountTotal);
        const roundedTaxTotal = roundTaxUp(taxTotal);

        document.getElementById('subtotalDisplay').textContent = formatCurrency(subtotal);
        document.getElementById('discountDisplay').textContent = formatCurrency(roundedDiscountTotal);
        updateTaxDisplay(roundedTaxTotal);
        document.getElementById('grandTotalDisplay').textContent = formatCurrency(subtotal - roundedDiscountTotal + roundedTaxTotal);

        document.getElementById('subtotal').value = subtotal.toFixed(2);
        document.getElementById('discount_total').value = roundedDiscountTotal.toFixed(2);
        document.getElementById('tax_total').value = roundedTaxTotal.toFixed(2);
        document.getElementById('grand_total').value = (subtotal - roundedDiscountTotal + roundedTaxTotal).toFixed(2);
        itemsDataInput.value = JSON.stringify(invoiceItems);
    }

    function recalculateItems() {
        document.querySelectorAll('.item-quantity, .item-price, .item-discount, .item-tax-rate, .item-users, .item-frequency, .item-duration, .item-start-date, .item-end-date').forEach(input => {
            const index = parseInt(input.dataset.index, 10);
            const field = input.className.includes('quantity') ? 'quantity' :
                input.className.includes('price') ? 'unit_price' :
                input.className.includes('discount') ? 'discount_percent' :
                input.className.includes('tax-rate') ? 'tax_rate' :
                input.className.includes('users') ? 'no_of_users' :
                input.className.includes('frequency') ? 'frequency' :
                input.className.includes('start-date') ? 'start_date' :
                input.className.includes('end-date') ? 'end_date' :
                input.className.includes('duration') ? 'duration' : null;

            if (!field || !invoiceItems[index]) {
                return;
            }

            if (field === 'frequency' || field === 'duration' || field === 'start_date' || field === 'end_date') {
                invoiceItems[index][field] = input.value;
            } else if (field === 'quantity') {
                const qty = Math.max(1, Math.round(Number(input.value) || 1));
                invoiceItems[index][field] = qty;
                input.value = qty;
            } else {
                invoiceItems[index][field] = parseFloat(input.value) || 0;
            }

            if (!itemSupportsUserFields(invoiceItems[index])) {
                invoiceItems[index].no_of_users = null;
            } else {
                invoiceItems[index].no_of_users = Math.max(1, Number(invoiceItems[index].no_of_users || 1));
            }

            if (!itemHasRecurringFrequency(invoiceItems[index])) {
                invoiceItems[index].duration = '';
                invoiceItems[index].start_date = '';
                invoiceItems[index].end_date = '';
            } else if (field === 'start_date' || field === 'frequency' || field === 'duration') {
                invoiceItems[index].end_date = calculateEndDate(
                    invoiceItems[index].start_date,
                    invoiceItems[index].frequency,
                    invoiceItems[index].duration
                );
            }
            normalizeItemAmounts(invoiceItems[index]);
        });

        renderItems();
    }

    btnNextToStep4.addEventListener('click', function() {
        const invoiceTitle = invoiceTitleInput.value;
        if (!invoiceTitle.trim()) {
            invoiceTitleError.style.display = 'block';
            invoiceTitleInput.focus();
            return;
        }

        invoiceTitleError.style.display = 'none';

        if (invoiceItems.length === 0) {
            alert('No items to invoice.');
            return;
        }

        fetch("{{ route('invoices.save-draft') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                clientid: clientId,
                invoice_for: invoiceFor,
                invoice_title: invoiceTitle,
                items_data: itemsDataInput.value
            })
        })
        .then(response => response.json())
        .then(() => {
            let nextUrl = "{{ route('invoices.create') }}?step=4&invoice_for=" + invoiceFor + "&clientid=" + clientId;
            if (orderId) {
                nextUrl += "&orderid=" + orderId;
            }
            window.location.href = nextUrl;
        });
    });

    btnBackToStep2.addEventListener('click', function() {
        window.location.href = "{{ route('invoices.create') }}?step=2&invoice_for=" + invoiceFor + "&clientid=" + clientId;
    });

    invoiceTitleInput.addEventListener('input', function() {
        if (this.value.trim()) {
            invoiceTitleError.style.display = 'none';
        }
    });

    loadItems();
})();
</script>
