@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('payments.index', !empty($selectedClientId) ? ['c' => $selectedClientId] : []) }}"
        class="secondary-button">
        <i class="fas fa-arrow-left" class="icon-spaced"></i>Back to Payments
    </a>
@endsection

@section('content')
    <section class="panel-card panel-card-lg">
        @php
            $isEditingPayment = isset($payment);
            $defaultClientId = $isEditingPayment
                ? (string) ($payment->clientid ?? '')
                : old('clientid', $selectedClientId ?? '');
            $defaultCurrency = $selectedCurrency ?? (isset($payment) ? $payment->client->currency ?? 'INR' : 'INR');
            $defaultInvoiceIds = old('invoice_ids', $selectedInvoiceIds ?? []);
            if (!is_array($defaultInvoiceIds)) {
                $defaultInvoiceIds = [$defaultInvoiceIds];
            }
            $selectedClient = $isEditingPayment
                ? $payment->client ?? null
                : collect($clients ?? [])->firstWhere('clientid', $defaultClientId);
            $selectedClientName =
                (string) ($selectedClient->business_name ?? ($selectedClient->contact_name ?? 'Select Client'));
            $selectedClientEmail = (string) ($selectedClient->primary_email ?? ($selectedClient->email ?? ''));
            $clientCurrencies = collect($clients ?? [])
                ->mapWithKeys(
                    fn($client) => [
                        (string) $client->clientid => (string) ($client->currency ?? 'INR'),
                    ],
                )
                ->all();
            $invoiceTotals = collect($invoices ?? [])
                ->mapWithKeys(function ($invoice) {
                    return [
                        (string) $invoice->invoiceid => [
                            'grand_total' => (float) ($invoice->grand_total ?? 0),
                            'amount_paid' => (float) ($invoice->amount_paid ?? 0),
                            'balance_due' =>
                                (float) ($invoice->balance_due ??
                                    max(
                                        0,
                                        (float) ($invoice->grand_total ?? 0) - (float) ($invoice->amount_paid ?? 0),
                                    )),
                            'amount_without_tax' => (float) ($invoice->subtotal - $invoice->discount_total),
                            'currency' => (string) ($invoice->client->currency ?? 'INR'),
                            'issue_date' => optional($invoice->issue_date)->format('d M Y') ?? '',
                            'due_date' => optional($invoice->due_date)->format('d M Y') ?? '',
                            'tax_rate' => (float) ($invoice->invoiceItems->first()?->tax_rate ?? 0),
                        ],
                    ];
                })
                ->all();
            $invoiceOptions = collect($invoices ?? [])
                ->map(function ($invoice) {
                    return [
                        'invoiceid' => (string) $invoice->invoiceid,
                        'invoice_number' => (string) ($invoice->invoice_number ?? ''),
                        'invoice_title' => (string) ($invoice->invoice_title ?? ''),
                        'clientid' => (string) ($invoice->clientid ?? ''),
                        'client_name' => (string) ($invoice->client->business_name ?? 'Client'),
                        'issue_date' => optional($invoice->issue_date)->format('d M Y') ?? '',
                        'due_date' => optional($invoice->due_date)->format('d M Y') ?? '',
                    ];
                })
                ->values()
                ->all();
            $paymentDetailsMap = isset($payment)
                ? $payment->paymentDetails
                    ->mapWithKeys(
                        fn($detail) => [
                            (string) $detail->invoiceid => [
                                'received_amount' => (float) $detail->received_amount,
                                'tds_amount' => (float) $detail->tds_amount,
                            ],
                        ],
                    )
                    ->all()
                : [];
        @endphp
        <form method="POST" action="{{ isset($payment) ? route('payments.update', $payment) : route('payments.store') }}"
            class="client-form payments-form-shell">
            @isset($payment)
                @method('PUT')
            @endisset
            @csrf
            <div class="invoice-client-header mb-3">
                <div class="invoice-client-header__row">
                    <div class="invoice-client-header__icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="invoice-client-header__body" style="min-width: 0; flex: 1;">
                        @if (!$isEditingPayment)
                            <label for="clientid" class="field-label" style="margin-bottom: 0.25rem;">Select Client
                                *</label>
                            <select id="clientid" name="clientid" class="form-control" required>
                                <option value="">Select Client</option>
                                @php
                                    $clientsByType = collect($clients ?? [])->groupBy(function ($client) {
                                        return strtolower((string) ($client->type ?? 'regular')) === 'trial'
                                            ? 'trial'
                                            : 'regular';
                                    });
                                @endphp
                                @foreach (['regular' => 'Regular Clients', 'trial' => 'Trial Clients'] as $typeKey => $typeLabel)
                                    @if (($clientsByType[$typeKey] ?? collect())->isNotEmpty())
                                        <optgroup label="{{ $typeLabel }}">
                                            @foreach ($clientsByType[$typeKey] as $client)
                                                <option value="{{ $client->clientid }}"
                                                    {{ $defaultClientId == $client->clientid ? 'selected' : '' }}>
                                                    {{ $client->business_name ?? $client->contact_name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" id="clientid" name="clientid" value="{{ $defaultClientId }}">
                            <div class="invoice-client-header__name" id="selectedClientName">{{ $selectedClientName }}</div>
                            <div class="invoice-client-header__email" id="selectedClientEmail"
                                style="{{ $selectedClientEmail ? '' : 'display:none;' }}">{{ $selectedClientEmail }}</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="form-grid">
                <div>
                    <label for="received_amount">Amount * (<span id="currencyLabel">{{ $defaultCurrency }}</span>)</label>
                    <input type="text" id="received_amount" name="received_amount"
                        value="{{ old('received_amount', isset($payment) ? $payment->received_amount : '') }}" required>
                    @error('received_amount')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label for="tds_amount">TDS Amount (<span id="currencyLabelTds">{{ $defaultCurrency }}</span>)</label>
                    <input type="text" id="tds_amount" name="tds_amount"
                        value="{{ old('tds_amount', isset($payment) ? $payment->tds_amount ?? '' : '') }}">
                    @error('tds_amount')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </div>
                <div style="grid-column: 1 / -1;">
                    <label>Related Invoices (Optional)</label>
                    <div id="invoice-list-wrap"
                        style="border: 1px solid #dbe3ea; border-radius: 10px; background: #fff; padding: 0.75rem;">
                        <div id="invoice-list" style="display: flex; flex-direction: column; gap: 0.5rem;"></div>
                    </div>
                </div>
                <div>
                    <label for="payment_date">Date *</label>
                    <input type="date" id="payment_date" name="payment_date"
                        value="{{ old('payment_date', isset($payment) ? optional($payment->payment_date)->format('Y-m-d') : date('Y-m-d')) }}"
                        required>
                    @error('payment_date')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label for="mode">Mode *</label>
                    <select id="mode" name="mode" required>
                        <option value="Bank Transfer"
                            {{ old('mode', isset($payment) ? $payment->mode : '') == 'Bank Transfer' ? 'selected' : '' }}>
                            Bank Transfer</option>
                        <option value="Online"
                            {{ old('mode', isset($payment) ? $payment->mode : '') == 'Online' ? 'selected' : '' }}>Online
                        </option>
                        <option value="Cash"
                            {{ old('mode', isset($payment) ? $payment->mode : '') == 'Cash' ? 'selected' : '' }}>Cash
                        </option>
                    </select>
                    @error('mode')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label for="reference_number">Reference Number</label>
                    <input type="text" id="reference_number" name="reference_number"
                        value="{{ old('reference_number', isset($payment) ? $payment->reference_number : '') }}">
                    @error('reference_number')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="2">{{ old('description', isset($payment) ? $payment->description : '') }}</textarea>
                    @error('description')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="form-actions">
                <button type="submit"
                    class="primary-button">{{ $isEditingPayment ? 'Update Payment' : 'Record Payment' }}</button>
                <a href="{{ route('payments.index', !empty($selectedClientId) ? ['c' => $selectedClientId] : []) }}"
                    class="text-link">Cancel</a>
            </div>
        </form>
    </section>
    <script>
        (function() {
            const clientCurrencies = @json($clientCurrencies);
            const invoiceTotals = @json($invoiceTotals);
            const invoiceOptions = @json($invoiceOptions);
            const clientSelect = document.getElementById('clientid');
            const isEditingPayment = @json(isset($payment));
            const currencyLabel = document.getElementById('currencyLabel');
            const currencyLabelTds = document.getElementById('currencyLabelTds');
            const invoiceList = document.getElementById('invoice-list');
            const selectedClientName = document.getElementById('selectedClientName');
            const selectedClientEmail = document.getElementById('selectedClientEmail');
            const paymentDetailsMap = @json($paymentDetailsMap);
            const clientEmailMap = @json(collect($clients ?? [])->mapWithKeys(fn($client) => [
                            (string) $client->clientid => (string) ($client->primary_email ?? ($client->email ?? '')),
                        ])->all());
            let selectedInvoiceIds = @json(array_values(array_map('strval', $defaultInvoiceIds)));
            let manualAllocations = {};

            const receivedAmountInput = document.getElementById('received_amount');
            const tdsAmountInput = document.getElementById('tds_amount');
            const submitBtn = document.querySelector('button[type="submit"]');

            // Add percentage badge below TDS input field
            const tdsGroupDiv = tdsAmountInput.closest('div');
            const tdsPercentBadge = document.createElement('div');
            tdsPercentBadge.id = 'tds_percent_badge';
            tdsPercentBadge.style.marginTop = '0.35rem';
            tdsPercentBadge.style.fontSize = '0.85rem';
            tdsPercentBadge.style.fontWeight = '600';
            tdsPercentBadge.style.color = '#4f46e5';
            tdsPercentBadge.textContent = 'Calculated TDS: 0%';
            tdsGroupDiv.appendChild(tdsPercentBadge);

            function formatAmount(value) {
                return Number(value || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });
            }

            function setCurrencyFromClient() {
                const clientId = clientSelect?.value || '';
                const currency = clientCurrencies[clientId] || 'INR';
                if (currencyLabel) currencyLabel.textContent = currency;
                if (currencyLabelTds) currencyLabelTds.textContent = currency;
            }

            function updateSelectedClientHeader() {
                if (isEditingPayment) return;

                const clientId = clientSelect?.value || '';
                const clientOption = Array.from(clientSelect?.options || []).find((option) => option.value ===
                    clientId);
                const selectedText = (clientOption?.textContent || '').trim();
                const email = clientEmailMap[clientId] || '';

                if (selectedClientName) {
                    selectedClientName.textContent = selectedText || 'Select Client';
                }
                if (selectedClientEmail) {
                    if (email) {
                        selectedClientEmail.textContent = email;
                        selectedClientEmail.style.display = '';
                    } else {
                        selectedClientEmail.textContent = '';
                        selectedClientEmail.style.display = 'none';
                    }
                }
            }

            function updateTdsPercentage() {
                const received = parseFloat(receivedAmountInput.value) || 0;
                const tds = parseFloat(tdsAmountInput.value) || 0;
                const totalSettlement = received + tds;
                const percent = totalSettlement > 0 ? (tds / totalSettlement) * 100 : 0;

                const displayPercent = percent % 1 === 0 ? percent.toFixed(0) : percent.toFixed(2);
                tdsPercentBadge.textContent = `Calculated TDS: ${displayPercent}%`;
                return percent;
            }

            function recalculateAll(isMainChange) {
                const received = parseFloat(receivedAmountInput.value) || 0;
                const tds = parseFloat(tdsAmountInput.value) || 0;
                const totalSettlement = received + tds;

                const tdsPercent = totalSettlement > 0 ? (tds / totalSettlement) * 100 : 0;
                updateTdsPercentage();

                const clientId = clientSelect?.value || '';
                const currency = clientCurrencies[clientId] || 'INR';

                const rows = Array.from(invoiceList.querySelectorAll('.addon-option'));
                let checkedRows = rows.filter(row => {
                    const checkbox = row.querySelector('.invoice-option-checkbox');
                    return checkbox && checkbox.checked;
                });

                if (checkedRows.length === 0) {
                    return;
                }

                if (totalSettlement === 0) {
                    checkedRows.forEach(row => {
                        const checkbox = row.querySelector('.invoice-option-checkbox');
                        const baseInput = row.querySelector('.invoice-base-amount');
                        const tdsDisplay = row.querySelector('.invoice-tds-display');
                        const receivedDisplay = row.querySelector('.invoice-received-display');
                        const receivedHidden = row.querySelector('.invoice-received-amount-hidden');
                        const tdsHidden = row.querySelector('.invoice-tds-amount-hidden');

                        const invoiceId = checkbox.value;

                        baseInput.disabled = false;
                        row.style.background = '#eef4ff';

                        const totals = invoiceTotals[invoiceId] || {
                            amount_without_tax: 0,
                            amount_paid: 0
                        };
                        let savedAlloc = 0;
                        if (paymentDetailsMap && paymentDetailsMap[invoiceId]) {
                            savedAlloc = paymentDetailsMap[invoiceId].received_amount + paymentDetailsMap[
                                invoiceId].tds_amount;
                        }

                        let prefilledBaseAmount = totals.amount_without_tax;
                        if (isEditingPayment && paymentDetailsMap && paymentDetailsMap[invoiceId]) {
                            prefilledBaseAmount = savedAlloc;
                        }

                        baseInput.value = prefilledBaseAmount % 1 === 0 ? prefilledBaseAmount.toFixed(0) : prefilledBaseAmount.toFixed(2);
                        if (tdsDisplay) tdsDisplay.textContent = `0`;
                        if (receivedDisplay) receivedDisplay.textContent = `0`;
                        if (receivedHidden) receivedHidden.value = 0;
                        if (tdsHidden) tdsHidden.value = 0;
                    });
                    return;
                }

                let remainingSettlement = totalSettlement;
                let remainingReceived = received;
                let remainingTds = tds;

                checkedRows.forEach((row, index) => {
                    const checkbox = row.querySelector('.invoice-option-checkbox');
                    const baseInput = row.querySelector('.invoice-base-amount');
                    const tdsDisplay = row.querySelector('.invoice-tds-display');
                    const receivedDisplay = row.querySelector('.invoice-received-display');
                    const receivedHidden = row.querySelector('.invoice-received-amount-hidden');
                    const tdsHidden = row.querySelector('.invoice-tds-amount-hidden');

                    const invoiceId = checkbox.value;
                    const isLast = (index === checkedRows.length - 1);

                    baseInput.disabled = false;
                    row.style.background = '#eef4ff';

                    let allocation = 0;
                    let rowReceived = 0;
                    let rowTds = 0;

                    // Available amount without tax for this invoice
                    const totals = invoiceTotals[invoiceId] || {
                        amount_without_tax: 0,
                        amount_paid: 0
                    };
                    let savedAlloc = 0;
                    if (paymentDetailsMap && paymentDetailsMap[invoiceId]) {
                        savedAlloc = paymentDetailsMap[invoiceId].received_amount + paymentDetailsMap[invoiceId]
                            .tds_amount;
                    }
                    const previousAllocations = totals.amount_paid - savedAlloc;
                    const availableLimit = Math.max(0, totals.amount_without_tax - previousAllocations);

                    if (isLast) {
                        allocation = Math.min(availableLimit, remainingSettlement);
                        if (allocation < remainingSettlement) {
                            rowTds = Math.round(allocation * (tds / totalSettlement));
                            rowTds = Math.min(rowTds, remainingTds);
                            rowReceived = allocation - rowTds;

                            if (rowReceived > remainingReceived) {
                                rowReceived = remainingReceived;
                                rowTds = allocation - rowReceived;
                            }
                        } else {
                            rowReceived = remainingReceived;
                            rowTds = remainingTds;
                        }
                    } else {
                        if (manualAllocations[invoiceId] !== undefined) {
                            allocation = manualAllocations[invoiceId];
                        } else {
                            allocation = Math.min(availableLimit, remainingSettlement);
                        }

                        allocation = Math.min(allocation, remainingSettlement);

                        rowTds = Math.round(allocation * (tds / totalSettlement));
                        rowTds = Math.min(rowTds, remainingTds);
                        rowReceived = allocation - rowTds;

                        if (rowReceived > remainingReceived) {
                            rowReceived = remainingReceived;
                            rowTds = allocation - rowReceived;
                        }
                    }

                    // If it is a main change (load, client select, main input change)
                    // and the allocation calculated is 0, we uncheck and disable it!
                    if (isMainChange && allocation === 0) {
                        checkbox.checked = false;
                        baseInput.disabled = true;
                        baseInput.value = '0';
                        row.style.background = '#fff';
                        if (tdsDisplay) tdsDisplay.textContent = `0`;
                        if (receivedDisplay) receivedDisplay.textContent = `0`;
                        if (receivedHidden) receivedHidden.value = '0';
                        if (tdsHidden) tdsHidden.value = '0';

                        allocation = 0;
                        rowReceived = 0;
                        rowTds = 0;
                    } else {
                        baseInput.value = allocation % 1 === 0 ? allocation.toFixed(0) : allocation.toFixed(2);

                        if (tdsDisplay) tdsDisplay.textContent = `${formatAmount(rowTds)}`;
                        if (receivedDisplay) receivedDisplay.textContent =
                            `${formatAmount(rowReceived)}`;
                        if (receivedHidden) receivedHidden.value = rowReceived;
                        if (tdsHidden) tdsHidden.value = rowTds;
                    }

                    remainingSettlement = Math.max(0, remainingSettlement - allocation);
                    remainingReceived = Math.max(0, remainingReceived - rowReceived);
                    remainingTds = Math.max(0, remainingTds - rowTds);
                });

                // Reset unchecked rows
                rows.forEach(row => {
                    const checkbox = row.querySelector('.invoice-option-checkbox');
                    const baseInput = row.querySelector('.invoice-base-amount');
                    const tdsDisplay = row.querySelector('.invoice-tds-display');
                    const receivedDisplay = row.querySelector('.invoice-received-display');
                    const receivedHidden = row.querySelector('.invoice-received-amount-hidden');
                    const tdsHidden = row.querySelector('.invoice-tds-amount-hidden');

                    if (!checkbox || !baseInput) return;

                    if (!checkbox.checked) {
                        baseInput.disabled = true;
                        baseInput.value = '0';
                        row.style.background = '#fff';
                        if (tdsDisplay) tdsDisplay.textContent = `0`;
                        if (receivedDisplay) receivedDisplay.textContent = `0`;
                        if (receivedHidden) receivedHidden.value = '0';
                        if (tdsHidden) tdsHidden.value = '0';
                    }
                });
            }

            function renderInvoiceList() {
                const clientId = clientSelect?.value || '';

                if (!clientId) {
                    selectedInvoiceIds = [];
                    if (invoiceList) {
                        invoiceList.innerHTML = '<p class="addons-empty">Select a client first</p>';
                    }
                    return;
                }

                const clientInvoices = invoiceOptions.filter((invoice) => invoice.clientid === clientId);

                if (!clientInvoices.length) {
                    selectedInvoiceIds = [];
                    if (invoiceList) {
                        invoiceList.innerHTML = '<p class="addons-empty">No invoices for this client</p>';
                    }
                    return;
                }

                if (!isEditingPayment) {
                    selectedInvoiceIds = clientInvoices.map(invoice => invoice.invoiceid);
                } else {
                    selectedInvoiceIds = selectedInvoiceIds.filter((invoiceId) => (
                        clientInvoices.some((invoice) => invoice.invoiceid === invoiceId)
                    ));
                }

                if (invoiceList) {
                    const currency = clientCurrencies[clientId] || 'INR';

                    // Populate manualAllocations from paymentDetailsMap initially
                    if (isEditingPayment && Object.keys(manualAllocations).length === 0) {
                        clientInvoices.forEach((invoice) => {
                            if (selectedInvoiceIds.includes(invoice.invoiceid) && paymentDetailsMap &&
                                paymentDetailsMap[invoice.invoiceid]) {
                                const detail = paymentDetailsMap[invoice.invoiceid];
                                manualAllocations[invoice.invoiceid] = detail.received_amount + detail
                                    .tds_amount;
                            }
                        });
                    }

                    invoiceList.innerHTML = clientInvoices.map((invoice) => {
                        const checked = selectedInvoiceIds.includes(invoice.invoiceid) ? 'checked' : '';
                        const invoiceNumber = (invoice.invoice_number || '').trim();
                        const invoiceTitle = (invoice.invoice_title || '').trim();
                        const displayTitle = invoiceTitle || invoiceNumber || 'Invoice';

                        const totals = invoiceTotals[invoice.invoiceid] || {
                            grand_total: 0,
                            amount_without_tax: 0,
                            balance_due: 0,
                            amount_paid: 0
                        };

                        let savedAlloc = 0;
                        if (paymentDetailsMap && paymentDetailsMap[invoice.invoiceid]) {
                            savedAlloc = paymentDetailsMap[invoice.invoiceid].received_amount +
                                paymentDetailsMap[invoice.invoiceid].tds_amount;
                        }

                        let prefilledBaseAmount = totals.amount_without_tax;
                        if (isEditingPayment && paymentDetailsMap && paymentDetailsMap[invoice.invoiceid]) {
                            prefilledBaseAmount = savedAlloc;
                        }

                        const formattedBaseAmount = prefilledBaseAmount % 1 === 0 ? prefilledBaseAmount.toFixed(
                            0) : prefilledBaseAmount.toFixed(2);

                        return `
                            <div class="addon-option" style="display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-radius: 8px; background: ${checked ? '#eef4ff' : '#fff'}; margin-bottom: 0.5rem;">
                                <label class="custom-checkbox" style="display: flex; align-items: flex-start; gap: 0.75rem; flex: 1; min-width: 250px; cursor: pointer; margin-bottom: 0; white-space: normal;">
                                    <input type="checkbox" name="invoice_ids[]" class="invoice-option-checkbox" value="${invoice.invoiceid}" ${checked}>
                                    <span class="checkbox-label" style="display:block; width: 100%; white-space: normal;">
                                        <strong style="display:block; color:#0f172a;">${displayTitle}</strong>
                                        <small style="display:block; color:#64748b; font-weight:500; margin-top:2px;">
                                            ${invoiceNumber ? `#${invoiceNumber}` : ''}
                                            ${invoiceNumber && (invoice.issue_date || invoice.due_date) ? ' · ' : ''}
                                            ${invoice.issue_date ? `Issued ${invoice.issue_date}` : ''}
                                            ${invoice.issue_date && invoice.due_date ? ' · ' : ''}
                                            ${invoice.due_date ? `Due ${invoice.due_date}` : ''}
                                        </small>
                                        <div style="font-size: 0.75rem; color: #475569; margin-top: 4px;">
                                            Amount: <strong>${formatAmount(totals.grand_total)}</strong> ·
                                            Tax: <strong>${totals.tax_rate}%</strong>
                                        </div>
                                    </span>
                                </label>

                                <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                        <label style="font-size: 0.7rem; font-weight: 600; color: #475569; margin-bottom: 0;">Base Amount (Without Tax)</label>
                                        <input type="text" class="invoice-base-amount" data-invoiceid="${invoice.invoiceid}" value="${formattedBaseAmount}" style="width: 140px; padding: 0.25rem 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem;" ${checked ? '' : 'disabled'}>
                                    </div>

                                    <div style="font-size: 0.8rem; color: #475569; min-width: 140px;">
                                        <div style="display: flex; justify-content: space-between; gap: 0.5rem;">
                                            <span>TDS:</span>
                                            <strong class="invoice-tds-display">0</strong>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; gap: 0.5rem;">
                                            <span>Net Received:</span>
                                            <strong class="invoice-received-display">0</strong>
                                        </div>
                                    </div>

                                    <input type="hidden" name="invoice_received_amounts[${invoice.invoiceid}]" class="invoice-received-amount-hidden" value="0">
                                    <input type="hidden" name="invoice_tds_amounts[${invoice.invoiceid}]" class="invoice-tds-amount-hidden" value="0">
                                </div>
                            </div>
                        `;
                    }).join('');

                    invoiceList.querySelectorAll('.invoice-option-checkbox').forEach((checkbox) => {
                        checkbox.addEventListener('change', function() {
                            if (this.checked) {
                                if (!selectedInvoiceIds.includes(this.value)) {
                                    selectedInvoiceIds.push(this.value);
                                }
                            } else {
                                selectedInvoiceIds = selectedInvoiceIds.filter((invoiceId) =>
                                    invoiceId !== this.value);
                            }
                            manualAllocations = {};
                            recalculateAll(false);
                        });
                    });

                    invoiceList.querySelectorAll('.invoice-base-amount').forEach((input) => {
                        input.addEventListener('input', function() {
                            const invoiceId = this.dataset.invoiceid;
                            const val = parseFloat(this.value);
                            if (isNaN(val)) {
                                delete manualAllocations[invoiceId];
                            } else {
                                manualAllocations[invoiceId] = val;
                            }
                            recalculateAll(false);
                        });
                    });

                    recalculateAll(true);
                }
            }

            if (!isEditingPayment) {
                clientSelect?.addEventListener('change', function() {
                    setCurrencyFromClient();
                    updateSelectedClientHeader();
                    selectedInvoiceIds = [];
                    manualAllocations = {};
                    renderInvoiceList();
                });
            }

            receivedAmountInput?.addEventListener('input', () => {
                manualAllocations = {};
                recalculateAll(false);
            });
            receivedAmountInput?.addEventListener('change', () => {
                recalculateAll(true);
            });

            tdsAmountInput?.addEventListener('input', () => {
                manualAllocations = {};
                recalculateAll(false);
            });
            tdsAmountInput?.addEventListener('change', () => {
                recalculateAll(true);
            });

            const form = document.querySelector('.payments-form-shell');
            form?.addEventListener('submit', function(e) {
                recalculateAll(true);
                const received = parseFloat(receivedAmountInput.value) || 0;
                const tds = parseFloat(tdsAmountInput.value) || 0;
                const totalSettlement = received + tds;

                const checkedRows = Array.from(invoiceList.querySelectorAll(
                '.invoice-option-checkbox:checked'));
                if (checkedRows.length > 0) {
                    let sumAllocations = 0;
                    let sumReceived = 0;
                    let sumTds = 0;
                    let exceedsRowLimit = false;

                    checkedRows.forEach(checkbox => {
                        const row = checkbox.closest('.addon-option');
                        const baseInput = row.querySelector('.invoice-base-amount');
                        const receivedHidden = row.querySelector('.invoice-received-amount-hidden');
                        const tdsHidden = row.querySelector('.invoice-tds-amount-hidden');
                        const invoiceId = checkbox.value;

                        const totals = invoiceTotals[invoiceId] || {
                            amount_without_tax: 0,
                            amount_paid: 0
                        };
                        let savedAlloc = 0;
                        if (paymentDetailsMap && paymentDetailsMap[invoiceId]) {
                            savedAlloc = paymentDetailsMap[invoiceId].received_amount +
                                paymentDetailsMap[invoiceId].tds_amount;
                        }
                        const previousAllocations = totals.amount_paid - savedAlloc;
                        const availableLimit = Math.max(0, totals.amount_without_tax -
                            previousAllocations);

                        const allocation = parseFloat(baseInput.value) || 0;
                        if (allocation > (availableLimit + 0.1)) {
                            exceedsRowLimit = true;
                        }

                        sumAllocations += allocation;
                        sumReceived += parseFloat(receivedHidden.value) || 0;
                        sumTds += parseFloat(tdsHidden.value) || 0;
                    });

                    const diffReceived = Math.abs(received - sumReceived);
                    const diffTds = Math.abs(tds - sumTds);

                    if (sumAllocations > (totalSettlement + 0.1)) {
                        e.preventDefault();
                        alert('Error: Total invoice allocations exceed the Total Settlement Amount.');
                        return false;
                    }

                    if (exceedsRowLimit) {
                        e.preventDefault();
                        alert(
                            "Error: One or more invoice allocations exceed the invoice's available amount without tax.");
                        return false;
                    }

                    if (diffReceived > 0.1 || diffTds > 0.1) {
                        e.preventDefault();
                        alert(
                            'Error: The sum of invoice allocations does not match the main Received Amount and TDS Amount exactly.');
                        return false;
                    }
                }
            });

            setCurrencyFromClient();
            updateSelectedClientHeader();
            renderInvoiceList();
        })();
    </script>
@endsection
