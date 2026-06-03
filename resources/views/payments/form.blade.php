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
            $paymentDateBounds = $paymentDateBounds ?? [
                'min_date' => date('Y-01-01'),
                'max_date' => date('Y-12-31'),
                'default_date' => date('Y-m-d'),
                'label' => '',
            ];
            $selectedClient = $isEditingPayment
                ? $payment->client ?? null
                : collect($clients ?? [])->firstWhere('clientid', $defaultClientId);
            $selectedClientName =
                (string) ($selectedClient->business_name ?? ($selectedClient->contact_name ?? 'Select Client'));
            $selectedClientEmail = (string) ($selectedClient->primary_email ?? ($selectedClient->email ?? ''));
            $displayReceiptNumber = isset($payment) ? (string) ($payment->receipt_number ?? '') : '';
            $clientCurrencies = collect($clients ?? [])
                ->mapWithKeys(
                    fn($client) => [
                        (string) $client->clientid => (string) ($client->currency ?? 'INR'),
                    ],
                )
                ->all();
            $invoiceTotals = collect($invoices ?? [])
                ->mapWithKeys(function ($invoice) {
                    $amountPaid = (float) ($invoice->amount_paid ?? 0);
                    $grandTotal = (float) ($invoice->grand_total ?? 0);
                    $balanceDue = (float) ($invoice->balance_due ?? max(0, $grandTotal - $amountPaid));
                    $paymentStatus = strtolower(trim((string) ($invoice->payment_status ?? '')));
                    if (!in_array($paymentStatus, ['paid', 'partly_paid', 'unpaid'], true)) {
                        $paymentStatus = 'unpaid';
                        if ($amountPaid > 0 && $balanceDue <= 0.1 && $grandTotal > 0) {
                            $paymentStatus = 'paid';
                        } elseif ($amountPaid > 0) {
                            $paymentStatus = 'partly_paid';
                        }
                    }

                    return [
                        (string) $invoice->invoiceid => [
                            'grand_total' => $grandTotal,
                            'amount_paid' => $amountPaid,
                            'balance_due' => $balanceDue,
                            'amount_without_tax' => (float) ($invoice->subtotal - $invoice->discount_total),
                            'currency' => (string) ($invoice->client->currency ?? 'INR'),
                            'issue_date' => optional($invoice->issue_date)->format('d M Y') ?? '',
                            'due_date' => optional($invoice->due_date)->format('d M Y') ?? '',
                            'tax_rate' => (float) ($invoice->invoiceItems->first()?->tax_rate ?? 0),
                            'payment_status' => $paymentStatus,
                        ],
                    ];
                })
                ->all();
            $invoiceOptions = collect($invoices ?? [])
                ->map(function ($invoice) use ($invoiceTotals) {
                    $invoiceId = (string) $invoice->invoiceid;
                    $totals = $invoiceTotals[$invoiceId] ?? [];

                    return [
                        'invoiceid' => $invoiceId,
                        'invoice_number' => (string) ($invoice->invoice_number ?? ''),
                        'invoice_title' => (string) ($invoice->invoice_title ?? ''),
                        'clientid' => (string) ($invoice->clientid ?? ''),
                        'client_name' => (string) ($invoice->client->business_name ?? 'Client'),
                        'issue_date' => optional($invoice->issue_date)->format('d M Y') ?? '',
                        'due_date' => optional($invoice->due_date)->format('d M Y') ?? '',
                        'payment_status' => (string) ($totals['payment_status'] ?? 'unpaid'),
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
            $existingTdsTotal = collect($paymentDetailsMap)->sum(fn($row) => (float) ($row['tds_amount'] ?? 0));
            if ($existingTdsTotal <= 0 && isset($payment)) {
                $existingTdsTotal = (float) ($payment->tds_amount ?? 0);
            }
            $defaultPaymentFlow = old('payment_flow');
            if (!$defaultPaymentFlow) {
                $defaultPaymentFlow = isset($payment) && $existingTdsTotal > 0 ? 'tds' : 'standard';
            }
            $defaultTdsInputType = old('tds_input_type', $defaultTdsInputType ?? 'percent');
            $defaultTdsDisplayValue = old('tds_amount', $defaultTdsDisplayValue ?? '');
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
                    <div class="invoice-client-header__body payments-client-header-body">
                        @if (!$isEditingPayment)
                            <label for="clientid" class="field-label payments-client-label">Select Client
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
                            <div class="invoice-client-header__email {{ $selectedClientEmail ? '' : 'is-hidden' }}"
                                id="selectedClientEmail">{{ $selectedClientEmail }}</div>
                        @endif
                    </div>
                    <div class="invoice-client-header__right">
                        @if ($displayReceiptNumber !== '')
                            <div class="invoice-number-badge">
                                {{ $displayReceiptNumber }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="form-grid">
                <div>
                    <label for="payment_flow">Payment Type *</label>
                    <select id="payment_flow" name="payment_flow" class="form-control">
                        <option value="standard" {{ $defaultPaymentFlow === 'standard' ? 'selected' : '' }}>Invoice Payment</option>
                        <option value="tds" {{ $defaultPaymentFlow === 'tds' ? 'selected' : '' }}>TDS Deduction</option>
                    </select>
                </div>
                <div id="received-amount-wrap">
                    <label for="received_amount">Amount * (<span id="currencyLabel">{{ $defaultCurrency }}</span>)</label>
                    @php
                        $receivedAmountDisplay = old('received_amount', isset($payment) ? $payment->received_amount : '');
                        if ($receivedAmountDisplay !== '' && is_numeric($receivedAmountDisplay)) {
                            $receivedAmountDisplay = (float) $receivedAmountDisplay;
                            if (abs($receivedAmountDisplay - round($receivedAmountDisplay)) < 0.000001) {
                                $receivedAmountDisplay = (string) (int) round($receivedAmountDisplay);
                            }
                        }
                    @endphp
                    <input type="text" id="received_amount" name="received_amount"
                        value="{{ $receivedAmountDisplay }}" required>
                    @error('received_amount')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </div>
                <div id="tds-amount-wrap">
                    <label for="tds_amount">TDS Amount (<span id="currencyLabelTds">{{ $defaultCurrency }}</span>)</label>
                    <div class="payments-tds-group">
                        <select id="tds_input_type" name="tds_input_type" class="payments-tds-type">
                            <option value="percent" {{ $defaultTdsInputType === 'percent' ? 'selected' : '' }}>%</option>
                            <option value="amount" {{ $defaultTdsInputType === 'amount' ? 'selected' : '' }}>Amount</option>
                        </select>
                        <input type="text" id="tds_amount"
                            value="{{ $defaultTdsDisplayValue }}">
                    </div>
                    <input type="hidden" id="tds_amount_hidden" name="tds_amount"
                        value="{{ old('tds_amount', $existingTdsTotal > 0 ? $existingTdsTotal : '') }}">
                    @error('tds_amount')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </div>
                <div class="payments-full-span">
                    <label>Invoices</label>
                    <div id="invoice-list-wrap" class="payments-invoice-list-wrap">
                        <div id="invoice-list" class="payments-invoice-list"></div>
                    </div>
                </div>
                <div>
                    <label for="payment_date">Date *</label>
                    <input type="date" id="payment_date" name="payment_date"
                        min="{{ $paymentDateBounds['min_date'] }}"
                        max="{{ $paymentDateBounds['max_date'] }}"
                        value="{{ old('payment_date', isset($payment) ? optional($payment->payment_date)->format('Y-m-d') : $paymentDateBounds['default_date']) }}"
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
        document.addEventListener('DOMContentLoaded', function () {
            const paymentDate = document.getElementById('payment_date');
            if (!paymentDate) {
                return;
            }

            const minDate = paymentDate.getAttribute('min');
            const maxDate = paymentDate.getAttribute('max');

            const clampDate = () => {
                if (!paymentDate.value) {
                    paymentDate.setCustomValidity('');
                    return;
                }

                if (minDate && paymentDate.value < minDate) {
                    paymentDate.value = minDate;
                    paymentDate.setCustomValidity('Payment date cannot be before the financial year start.');
                    return;
                }

                if (maxDate && paymentDate.value > maxDate) {
                    paymentDate.value = maxDate;
                    paymentDate.setCustomValidity('Payment date cannot be after the allowed date.');
                    return;
                }

                paymentDate.setCustomValidity('');
            };

            paymentDate.addEventListener('change', clampDate);
            paymentDate.addEventListener('blur', clampDate);
            paymentDate.addEventListener('input', function () {
                paymentDate.setCustomValidity('');
            });
        });
    </script>
    <script>
        (function() {
            const clientCurrencies = @json($clientCurrencies);
            const invoiceTotals = @json($invoiceTotals);
            const invoiceOptions = @json($invoiceOptions);
            const paymentDetailsMap = @json($paymentDetailsMap);
            const isEditingPayment = @json(isset($payment));
            const defaultTdsInputType = @json($defaultTdsInputType);
            const defaultTdsDisplayValue = @json($defaultTdsDisplayValue);
            const selectedClientName = document.getElementById('selectedClientName');
            const selectedClientEmail = document.getElementById('selectedClientEmail');
            const clientEmailMap = @json(collect($clients ?? [])->mapWithKeys(fn($client) => [
                    (string) $client->clientid => (string) ($client->primary_email ?? ($client->email ?? '')),
                ])->all());

            const clientSelect = document.getElementById('clientid');
            const invoiceList = document.getElementById('invoice-list');
            const receivedAmountInput = document.getElementById('received_amount');
            const tdsAmountInput = document.getElementById('tds_amount');
            const tdsAmountHidden = document.getElementById('tds_amount_hidden');
            const tdsInputType = document.getElementById('tds_input_type');
            const tdsWrap = document.getElementById('tds-amount-wrap');
            const receivedWrap = document.getElementById('received-amount-wrap');
            const currencyLabel = document.getElementById('currencyLabel');
            const currencyLabelTds = document.getElementById('currencyLabelTds');
            let checkboxSelectionCounter = 0;
            let standardCheckedInvoiceIds = [];
            let tdsCheckedInvoiceIds = [];

            function formatAmount(value) {
                return Number(value || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });
            }

            function formatInputNumber(value) {
                const numeric = Number(value || 0);
                if (!Number.isFinite(numeric)) return '';
                if (Math.abs(numeric - Math.round(numeric)) < 0.000001) {
                    return String(Math.round(numeric));
                }
                return numeric.toFixed(2).replace(/\.?0+$/, '');
            }

            function parseLooseNumber(value) {
                if (typeof value !== 'string') return parseFloat(value || 0) || 0;
                const normalized = value.replace(/,/g, '').replace(/\s+/g, '');
                const parsed = parseFloat(normalized);
                return Number.isFinite(parsed) ? parsed : 0;
            }

            function normalizeAllocationInput(input) {
                if (!input) return;
                input.value = formatInputNumber(parseLooseNumber(input.value));
            }

            function selectedPaymentFlow() {
                const selected = document.getElementById('payment_flow');
                return selected ? selected.value : 'standard';
            }

            function setCurrencyFromClient() {
                const clientId = clientSelect ? clientSelect.value : '';
                const currency = clientCurrencies[clientId] || 'INR';
                if (currencyLabel) currencyLabel.textContent = currency;
                if (currencyLabelTds) currencyLabelTds.textContent = currency;
            }

            function updateSelectedClientHeader() {
                if (isEditingPayment || !clientSelect) return;
                const clientId = clientSelect.value || '';
                const option = Array.from(clientSelect.options || []).find((row) => row.value === clientId);
                const email = clientEmailMap[clientId] || '';
                if (selectedClientName) selectedClientName.textContent = (option?.textContent || '').trim() || 'Select Client';
                if (selectedClientEmail) {
                    selectedClientEmail.textContent = email;
                    selectedClientEmail.style.display = email ? '' : 'none';
                }
            }

            function getAvailableToCollect(invoiceId) {
                const totals = invoiceTotals[invoiceId] || {};
                let available = parseFloat(totals.balance_due || 0);
                if (isEditingPayment && paymentDetailsMap[invoiceId]) {
                    available += parseFloat(paymentDetailsMap[invoiceId].received_amount || 0);
                    available += parseFloat(paymentDetailsMap[invoiceId].tds_amount || 0);
                }
                return Math.max(0, available);
            }

            function getAvailableWithoutTax(invoiceId) {
                const totals = invoiceTotals[invoiceId] || {};
                return Math.max(0, parseFloat(totals.amount_without_tax || 0));
            }

            function renderInvoiceList() {
                const clientId = clientSelect ? clientSelect.value : '';
                if (!clientId) {
                    invoiceList.innerHTML = '<p class="addons-empty">Select a client first</p>';
                    return;
                }
                const clientInvoices = invoiceOptions.filter((invoice) => invoice.clientid === clientId);
                if (!clientInvoices.length) {
                    invoiceList.innerHTML = '<p class="addons-empty">No invoices for this client</p>';
                    return;
                }

                const visibleInvoices = clientInvoices.filter((invoice) => {
                    const totals = invoiceTotals[invoice.invoiceid] || {};
                    const statusKey = (totals.payment_status || 'unpaid');
                    const isChecked = isEditingPayment && !!paymentDetailsMap[invoice.invoiceid];

                    return statusKey !== 'paid' || isChecked;
                });

                if (!visibleInvoices.length) {
                    invoiceList.innerHTML = '<p class="addons-empty">No unpaid invoices for this client</p>';
                    return;
                }

                invoiceList.innerHTML = visibleInvoices.map((invoice) => {
                    const totals = invoiceTotals[invoice.invoiceid] || {};
                    const available = getAvailableToCollect(invoice.invoiceid);
                    const availableWithoutTax = getAvailableWithoutTax(invoice.invoiceid);
                    const amountWithoutTax = parseFloat(totals.amount_without_tax || 0);
                    const taxRate = parseFloat(totals.tax_rate || 0);
                    const taxAmount = amountWithoutTax * (taxRate / 100);
                    const amountBreakup = `${Math.round(amountWithoutTax).toLocaleString('en-US')} + ${Math.round(taxAmount).toLocaleString('en-US')}`;
                    const isChecked = isEditingPayment && !!paymentDetailsMap[invoice.invoiceid];
                    const savedTdsAmount = isEditingPayment && paymentDetailsMap[invoice.invoiceid]
                        ? (parseFloat(paymentDetailsMap[invoice.invoiceid].tds_amount || 0) || 0)
                        : 0;
                    const status = (totals.payment_status || 'unpaid').replace('_', ' ');
                    const statusKey = (totals.payment_status || 'unpaid');
                    const statusClass = statusKey === 'paid'
                        ? 'payments-status-paid'
                        : (statusKey === 'partly_paid' ? 'payments-status-partly' : 'payments-status-unpaid');
                    const title = (invoice.invoice_title || invoice.invoice_number || 'Invoice').trim();

                    return `
                        <div class="addon-option payments-invoice-row">
                            <label class="custom-checkbox payments-invoice-check-wrap">
                                <input type="checkbox" class="invoice-option-checkbox" name="invoice_ids[]" value="${invoice.invoiceid}" ${isChecked ? 'checked' : ''}>
                                <span class="checkbox-label payments-invoice-check-label">
                                    <strong class="payments-invoice-title">${title}</strong>
                                    <small class="payments-invoice-number">${invoice.invoice_number ? '#' + invoice.invoice_number : ''}</small>
                                    <div class="payments-invoice-meta">
                                        <span class="status-pill ${statusClass} payments-status-pill">${status}</span>
                                        <span>Amount: <strong>${amountBreakup}</strong></span>
                                        <span>Due: <strong>${Math.round(available).toLocaleString('en-US')}</strong></span>
                                    </div>
                                </span>
                            </label>
                            <input type="hidden" class="invoice-collectible-input" data-due="${available}" data-without-tax="${availableWithoutTax}" value="${Math.round(available).toLocaleString('en-US')}">
                            <div class="payments-alloc-wrap">
                                <div class="payments-alloc-row">
                                    <span class="invoice-allocated-label">Amount:</span>
                                </div>
                                <input type="text" class="invoice-allocated-input payments-tds-row-input" value="0">
                                <input type="text" class="invoice-tds-input payments-tds-row-input" value="${savedTdsAmount > 0 ? formatInputNumber(savedTdsAmount) : ''}">
                                <div class="invoice-live-state payments-live-state">
                                    Not allocated yet
                                </div>
                            </div>
                            <input type="hidden" class="invoice-received-amount-hidden" name="invoice_received_amounts[${invoice.invoiceid}]" value="0">
                            <input type="hidden" class="invoice-tds-amount-hidden" name="invoice_tds_amounts[${invoice.invoiceid}]" value="0">
                        </div>
                    `;
                }).join('');

                // Preserve predictable priority in edit mode.
                invoiceList.querySelectorAll('.invoice-option-checkbox:checked').forEach((checkbox) => {
                    checkboxSelectionCounter += 1;
                    checkbox.dataset.selectionOrder = String(checkboxSelectionCounter);
                });

                // Restore mode-specific checked state after render.
                const flow = selectedPaymentFlow();
                const preferredChecked = flow === 'tds' ? tdsCheckedInvoiceIds : standardCheckedInvoiceIds;
                if (preferredChecked.length > 0) {
                    invoiceList.querySelectorAll('.invoice-option-checkbox').forEach((checkbox) => {
                        checkbox.checked = preferredChecked.includes(checkbox.value);
                    });
                }

                recalculateStandardAllocations();
                recalculateTdsAllocations();
            }

            function recalculateStandardAllocations() {
                if (selectedPaymentFlow() !== 'standard') return;
                let remaining = parseLooseNumber(receivedAmountInput.value || 0);
                if (isNaN(remaining) || remaining < 0) remaining = 0;

                const rows = Array.from(invoiceList.querySelectorAll('.addon-option'));
                const checkedRows = rows
                    .filter((row) => row.querySelector('.invoice-option-checkbox')?.checked)
                    .sort((a, b) => {
                        const aOrder = parseInt(a.querySelector('.invoice-option-checkbox')?.dataset.selectionOrder || '0', 10);
                        const bOrder = parseInt(b.querySelector('.invoice-option-checkbox')?.dataset.selectionOrder || '0', 10);
                        return aOrder - bOrder;
                    });
                const uncheckedRows = rows.filter((row) => !row.querySelector('.invoice-option-checkbox')?.checked);

                [...checkedRows, ...uncheckedRows].forEach((row) => {
                    const checkbox = row.querySelector('.invoice-option-checkbox');
                    const allocatedInput = row.querySelector('.invoice-allocated-input');
                    const receivedHidden = row.querySelector('.invoice-received-amount-hidden');
                    const tdsHidden = row.querySelector('.invoice-tds-amount-hidden');
                    const liveState = row.querySelector('.invoice-live-state');
                    const invoiceId = checkbox ? checkbox.value : '';
                    let allocated = 0;
                    let nowRemaining = 0;

                    if (checkbox && checkbox.checked) {
                        const available = getAvailableToCollect(invoiceId);
                        const typed = parseLooseNumber(allocatedInput?.value || '');
                        if (!isNaN(typed) && typed > 0) {
                            allocated = Math.min(available, remaining, typed);
                        } else {
                            allocated = Math.min(available, remaining);
                        }
                        nowRemaining = Math.max(0, available - allocated);
                        remaining = Math.max(0, remaining - allocated);
                    }

                    if (allocatedInput) {
                        allocatedInput.readOnly = !(checkbox && checkbox.checked);
                        if (document.activeElement !== allocatedInput) {
                            allocatedInput.value = formatInputNumber(allocated);
                        }
                    }
                    if (receivedHidden) receivedHidden.value = allocated.toFixed(2);
                    if (tdsHidden) tdsHidden.value = '0';
                    row.style.background = checkbox && checkbox.checked ? '#eef4ff' : '#fff';

                    if (liveState) {
                        if (!checkbox || !checkbox.checked) {
                            liveState.textContent = '';
                            liveState.style.color = '#64748b';
                        } else if (allocated <= 0) {
                            liveState.textContent = `New Balance: ${Math.round(getAvailableToCollect(invoiceId)).toLocaleString('en-US')}`;
                            liveState.style.color = '#92400e';
                        } else if (nowRemaining <= 0.1) {
                            liveState.textContent = 'Fully Paid';
                            liveState.style.color = '#047857';
                        } else {
                            liveState.textContent = `New Balance: ${Math.round(nowRemaining).toLocaleString('en-US')}`;
                            liveState.style.color = '#92400e';
                        }
                    }
                });

            }

            function recalculateTdsAllocations() {
                if (selectedPaymentFlow() !== 'tds') return;
                const rows = Array.from(invoiceList.querySelectorAll('.addon-option'));
                const checkedRows = rows
                    .filter((row) => row.querySelector('.invoice-option-checkbox')?.checked)
                    .sort((a, b) => {
                        const aOrder = parseInt(a.querySelector('.invoice-option-checkbox')?.dataset.selectionOrder || '0', 10);
                        const bOrder = parseInt(b.querySelector('.invoice-option-checkbox')?.dataset.selectionOrder || '0', 10);
                        return aOrder - bOrder;
                    });
                const uncheckedRows = rows.filter((row) => !row.querySelector('.invoice-option-checkbox')?.checked);

                const mode = tdsInputType?.value || 'amount';
                const percent = parseLooseNumber(tdsAmountInput?.value || '0') || 0;
                let computedMainTds = 0;

                if (mode === 'percent') {
                    computedMainTds = checkedRows.reduce((sum, row) => {
                        const input = row.querySelector('.invoice-collectible-input');
                        const base = parseLooseNumber(input?.dataset.withoutTax || '0') || 0;
                        return sum + (base * percent / 100);
                    }, 0);
                    if (tdsAmountHidden) tdsAmountHidden.value = computedMainTds.toFixed(2);
                }

                [...checkedRows, ...uncheckedRows].forEach((row) => {
                    const checkbox = row.querySelector('.invoice-option-checkbox');
                    const receivedDisplay = row.querySelector('.invoice-received-display');
                    const receivedHidden = row.querySelector('.invoice-received-amount-hidden');
                    const tdsHidden = row.querySelector('.invoice-tds-amount-hidden');
                    const tdsInput = row.querySelector('.invoice-tds-input');
                    const liveState = row.querySelector('.invoice-live-state');
                    const input = row.querySelector('.invoice-collectible-input');
                    const withoutTax = parseLooseNumber(input?.dataset.withoutTax || '0') || 0;
                    let rowTds = 0;

                    if (checkbox && checkbox.checked) {
                        if (mode === 'percent') {
                            rowTds = withoutTax * percent / 100;
                        } else {
                            const typed = parseLooseNumber(tdsInput?.value || '0') || 0;
                            rowTds = Math.min(withoutTax, Math.max(0, typed));
                        }
                    }

                    if (receivedDisplay) receivedDisplay.textContent = formatAmount(rowTds);
                    if (receivedHidden) receivedHidden.value = '0';
                    if (tdsHidden) tdsHidden.value = rowTds.toFixed(2);
                    if (tdsInput) {
                        if (mode === 'percent') {
                            tdsInput.value = formatInputNumber(rowTds);
                            tdsInput.readOnly = true;
                        } else {
                            tdsInput.readOnly = !(checkbox && checkbox.checked);
                            if (!checkbox || !checkbox.checked) {
                                tdsInput.value = '';
                            } else if (tdsInput.value === '') {
                                tdsInput.value = '0';
                            }
                        }
                    }
                    row.style.background = checkbox && checkbox.checked ? '#eef4ff' : '#fff';

                    if (liveState) {
                        if (!checkbox || !checkbox.checked) {
                            liveState.textContent = 'Not selected';
                            liveState.style.color = '#64748b';
                        } else {
                            liveState.textContent = `TDS: ${Math.round(rowTds).toLocaleString('en-US')}`;
                            liveState.style.color = '#92400e';
                        }
                    }
                });

                if (mode === 'amount') {
                    const sumTypedTds = checkedRows.reduce((sum, row) => {
                        const tdsInput = row.querySelector('.invoice-tds-input');
                        const input = row.querySelector('.invoice-collectible-input');
                        const withoutTax = parseLooseNumber(input?.dataset.withoutTax || '0') || 0;
                        const typed = parseLooseNumber(tdsInput?.value || '0') || 0;
                        return sum + Math.min(withoutTax, Math.max(0, typed));
                    }, 0);
                    if (tdsAmountHidden) tdsAmountHidden.value = sumTypedTds.toFixed(2);
                }
            }

            function applyPaymentFlowUi() {
                const flow = selectedPaymentFlow();
                const isStandard = flow === 'standard';
                const currentlyChecked = Array.from(invoiceList.querySelectorAll('.invoice-option-checkbox:checked'))
                    .map((checkbox) => checkbox.value);
                if (flow === 'standard') {
                    standardCheckedInvoiceIds = currentlyChecked;
                } else {
                    tdsCheckedInvoiceIds = currentlyChecked;
                }
                if (tdsWrap) tdsWrap.style.display = isStandard ? 'none' : '';
                if (receivedWrap) receivedWrap.style.display = isStandard ? '' : 'none';
                if (tdsInputType) tdsInputType.style.display = isStandard ? 'none' : '';
                const isPercentMode = !isStandard && (tdsInputType?.value === 'percent');
                if (tdsAmountInput) tdsAmountInput.readOnly = false;
                if (tdsAmountInput) tdsAmountInput.style.background = '#fff';
                if (tdsAmountInput) tdsAmountInput.placeholder = isPercentMode ? 'TDS %' : '';
                if (tdsAmountInput) tdsAmountInput.style.display = isStandard ? 'none' : (isPercentMode ? 'block' : 'none');
                invoiceList.querySelectorAll('.addon-option').forEach((row) => {
                    const allocLabel = row.querySelector('.invoice-allocated-label');
                    const allocatedInput = row.querySelector('.invoice-allocated-input');
                    const tdsInput = row.querySelector('.invoice-tds-input');
                    if (isStandard) {
                        if (allocLabel) allocLabel.textContent = 'Allocated:';
                        if (allocatedInput) allocatedInput.style.display = 'block';
                        if (tdsInput) tdsInput.style.display = 'none';
                    } else {
                        if (allocLabel) allocLabel.textContent = 'TDS Amount:';
                        if (allocatedInput) allocatedInput.style.display = 'none';
                        if (tdsInput) tdsInput.style.display = 'block';
                    }
                });
                const checkedForFlow = isStandard ? standardCheckedInvoiceIds : tdsCheckedInvoiceIds;
                invoiceList.querySelectorAll('.invoice-option-checkbox').forEach((checkbox) => {
                    checkbox.checked = checkedForFlow.includes(checkbox.value);
                });
                if (isStandard) {
                    if (tdsAmountInput) tdsAmountInput.value = '0';
                    if (tdsAmountHidden) tdsAmountHidden.value = '0';
                    recalculateStandardAllocations();
                } else {
                    if (receivedAmountInput) receivedAmountInput.value = '0';
                    recalculateTdsAllocations();
                }
            }

            if (!isEditingPayment && clientSelect) {
                clientSelect.addEventListener('change', () => {
                    setCurrencyFromClient();
                    updateSelectedClientHeader();
                    renderInvoiceList();
                    applyPaymentFlowUi();
                });
            }

            document.getElementById('payment_flow')?.addEventListener('change', applyPaymentFlowUi);
            receivedAmountInput.addEventListener('input', recalculateStandardAllocations);
            tdsAmountInput.addEventListener('input', recalculateTdsAllocations);
            tdsInputType?.addEventListener('change', () => {
                const isPercentMode = tdsInputType.value === 'percent';
                if (tdsAmountInput) tdsAmountInput.readOnly = false;
                if (tdsAmountInput) tdsAmountInput.style.background = '#fff';
                if (tdsAmountInput) tdsAmountInput.placeholder = isPercentMode ? 'TDS %' : '';
                if (tdsAmountInput) tdsAmountInput.style.display = isPercentMode ? 'block' : 'none';
                invoiceList.querySelectorAll('.invoice-tds-input').forEach((input) => {
                    input.value = '';
                });
                if (tdsAmountInput) {
                    tdsAmountInput.value = '';
                    if (!isPercentMode && tdsAmountHidden) tdsAmountHidden.value = '0';
                    tdsAmountInput.focus();
                }
                recalculateTdsAllocations();
            });

            invoiceList.addEventListener('input', (event) => {
                if (event.target && event.target.classList.contains('invoice-tds-input')) {
                    recalculateTdsAllocations();
                }
                if (event.target && event.target.classList.contains('invoice-allocated-input')) {
                    recalculateStandardAllocations();
                }
            });

            invoiceList.addEventListener('blur', (event) => {
                if (event.target && event.target.classList.contains('invoice-allocated-input')) {
                    normalizeAllocationInput(event.target);
                    recalculateStandardAllocations();
                }
                if (event.target && event.target.classList.contains('invoice-tds-input')) {
                    normalizeAllocationInput(event.target);
                    recalculateTdsAllocations();
                }
            }, true);

            invoiceList.addEventListener('change', (event) => {
                if (event.target && event.target.classList.contains('invoice-option-checkbox')) {
                    if (event.target.checked) {
                        checkboxSelectionCounter += 1;
                        event.target.dataset.selectionOrder = String(checkboxSelectionCounter);
                    } else {
                        event.target.dataset.selectionOrder = '';
                    }
                    const flow = selectedPaymentFlow();
                    const checkedNow = Array.from(invoiceList.querySelectorAll('.invoice-option-checkbox:checked'))
                        .map((checkbox) => checkbox.value);
                    if (flow === 'standard') {
                        standardCheckedInvoiceIds = checkedNow;
                    } else {
                        tdsCheckedInvoiceIds = checkedNow;
                    }
                    recalculateStandardAllocations();
                    recalculateTdsAllocations();
                }
            });

            setCurrencyFromClient();
            updateSelectedClientHeader();
            renderInvoiceList();
            if (tdsInputType && defaultTdsInputType) {
                tdsInputType.value = defaultTdsInputType;
            }
            if (tdsAmountInput && defaultTdsDisplayValue !== null && defaultTdsDisplayValue !== undefined) {
                tdsAmountInput.value = defaultTdsDisplayValue;
            }
            applyPaymentFlowUi();
        })();
    </script>
@endsection
