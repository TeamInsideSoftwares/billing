@extends('layouts.app')

@section('header_actions')
<a href="{{ route('payments.index', !empty($selectedClientId) ? ['c' => $selectedClientId] : []) }}"
    class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
    <i class="fas fa-list btn-icon"></i> Payment List
</a>
@endsection

@section('content')
<div class="position-relative bg-white p-2 rounded-3">
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
    if ($amountPaid > 0 && $balanceDue <= 0.1 && $grandTotal> 0) {
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
        if ($existingTdsTotal <= 0 && isset($payment)) { $existingTdsTotal=(float) ($payment->tds_amount ?? 0);
            }
            $defaultPaymentFlow = old('payment_flow');
            if (!$defaultPaymentFlow) {
            $defaultPaymentFlow = isset($payment) && $existingTdsTotal > 0 ? 'tds' : 'standard';
            }
            $defaultTdsInputType = old('tds_input_type', $defaultTdsInputType ?? 'percent');
            $defaultTdsDisplayValue = old('tds_amount', $defaultTdsDisplayValue ?? '');
            @endphp
            <form method="POST"
                action="{{ isset($payment) ? route('payments.update', $payment) : route('payments.store') }}"
                class="mainForm">
                @isset($payment)
                @method('PUT')
                @endisset
                @csrf

                @if ($errors->any())
                <div class="alert alert-danger mb-4">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                        <li class="small">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="row g-2 align-items-stretch">
                    <div class="col-12 col-lg-3">
                        <div class="d-flex flex-column gap-2">
                            {{-- Client selector — matches orders/create style --}}
                            <div class="bg-secondary p-2 rounded-3">
                                <div class="row g-2">
                                    <div class="col-12">
                                        @if (!$isEditingPayment)
                                        <select id="clientid" name="clientid" class="form-select" required>
                                            <option value="">Select Client</option>
                                            @foreach ($clients ?? [] as $client)
                                            <option value="{{ $client->clientid }}" {{
                                                (string)$defaultClientId===(string)$client->clientid ? 'selected' : ''
                                                }}>
                                                {{ $client->business_name ?? $client->contact_name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @else
                                        <input type="hidden" name="clientid" value="{{ $defaultClientId }}">
                                        <select class="form-select" disabled>
                                            <option value="">Select Client</option>
                                            @foreach ($clients ?? [] as $client)
                                            <option value="{{ $client->clientid }}" {{
                                                (string)$defaultClientId===(string)$client->clientid ? 'selected' : ''
                                                }}>
                                                {{ $client->business_name ?? $client->contact_name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @if ($selectedClientEmail)
                                        <div class="small lh-sm text-white mt-2 ms-1">{{ $selectedClientEmail }}</div>
                                        @endif
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Payment Details card --}}
                            <div class="bg-light p-2 rounded-3 mt-2">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h5 class="fw-semibold text-primary small lh-sm mb-0">Payment Details</h5>
                                    @if ($displayReceiptNumber !== '')
                                    <span class="badge text-bg-primary">{{ $displayReceiptNumber }}</span>
                                    @endif
                                </div>

                                <div class="row g-2">
                                    <div class="col-12">
                                        <label for="payment_flow"
                                            class="form-label small lh-sm fw-semibold text-dark mb-1">Payment Type<span
                                                class="text-danger">*</span></label>
                                        <select id="payment_flow" name="payment_flow" class="form-select">
                                            <option value="standard" {{ $defaultPaymentFlow==='standard' ? 'selected'
                                                : '' }}>
                                                Invoice Payment</option>
                                            <option value="tds" {{ $defaultPaymentFlow==='tds' ? 'selected' : '' }}>TDS
                                                Deduction</option>
                                        </select>
                                    </div>
                                    <div id="received-amount-wrap" class="col-12">
                                        <label for="received_amount"
                                            class="form-label small lh-sm fw-semibold text-dark mb-1">Amount<span
                                                class="text-danger">* </span>(<span id="currencyLabel">{{
                                                $defaultCurrency }}</span>)</label>
                                        @php
                                        $receivedAmountDisplay = old(
                                        'received_amount',
                                        isset($payment) ? $payment->received_amount : '',
                                        );
                                        if ($receivedAmountDisplay !== '' && is_numeric($receivedAmountDisplay)) {
                                        $receivedAmountDisplay = (float) $receivedAmountDisplay;
                                        if (
                                        abs($receivedAmountDisplay - round($receivedAmountDisplay)) < 0.000001 ) {
                                            $receivedAmountDisplay=(string) (int) round($receivedAmountDisplay); } }
                                            @endphp <input type="text" id="received_amount" name="received_amount"
                                            value="{{ $receivedAmountDisplay }}" class="form-control" required>
                                            @error('received_amount')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                    </div>
                                    <div id="tds-amount-wrap" class="col-12">
                                        <label for="tds_amount"
                                            class="form-label small lh-sm fw-semibold text-dark mb-1">TDS Amount (<span
                                                id="currencyLabelTds">{{ $defaultCurrency }}</span>)</label>
                                        <div class="input-group">
                                            <select id="tds_input_type" name="tds_input_type" class="form-select"
                                                style="max-width: 90px;">
                                                <option value="percent" {{ $defaultTdsInputType==='percent' ? 'selected'
                                                    : '' }}>%</option>
                                                <option value="amount" {{ $defaultTdsInputType==='amount' ? 'selected'
                                                    : '' }}>Amount</option>
                                            </select>
                                            <input type="text" id="tds_amount" class="form-control"
                                                value="{{ $defaultTdsDisplayValue }}">
                                        </div>
                                        <input type="hidden" id="tds_amount_hidden" name="tds_amount"
                                            value="{{ old('tds_amount', $existingTdsTotal > 0 ? $existingTdsTotal : '') }}">
                                        @error('tds_amount')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="bg-light p-2 rounded-3 h-100">
                            <div class="mb-2">
                                <h5 class="fw-semibold text-primary small lh-sm mb-0">Invoices</h5>
                            </div>

                            <div id="invoice-list-wrap" class="payments-invoice-list-wrap">
                                <div id="invoice-list" class="payments-invoice-list row g-2"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-3">
                        <div class="bg-light p-2 rounded-3 h-100">
                            <div class="mb-2">
                                <h5 class="fw-semibold text-primary small lh-sm mb-0">Other Details</h5>
                            </div>

                            <div class="row g-2">
                                <div class="col-12">
                                    <label for="payment_date"
                                        class="form-label small lh-sm fw-semibold text-dark mb-1">Date<span
                                            class="text-danger">*</span></label>
                                    <input type="date" id="payment_date" name="payment_date" class="form-control"
                                        min="{{ $paymentDateBounds['min_date'] }}"
                                        max="{{ $paymentDateBounds['max_date'] }}"
                                        value="{{ old('payment_date', isset($payment) ? optional($payment->payment_date)->format('Y-m-d') : $paymentDateBounds['default_date']) }}"
                                        required>
                                    @error('payment_date')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label for="mode"
                                        class="form-label small lh-sm fw-semibold text-dark mb-1">Mode<span
                                            class="text-danger">*</span></label>
                                    <select id="mode" name="mode" class="form-select" required>
                                        <option value="Bank Transfer" {{ old('mode', isset($payment) ? $payment->mode :
                                            '') == 'Bank Transfer' ? 'selected' : '' }}>
                                            Bank Transfer</option>
                                        <option value="Online" {{ old('mode', isset($payment) ? $payment->mode : '') ==
                                            'Online' ? 'selected' : '' }}>
                                            Online</option>
                                        <option value="Cash" {{ old('mode', isset($payment) ? $payment->mode : '') ==
                                            'Cash' ? 'selected' : '' }}>
                                            Cash</option>
                                    </select>
                                    @error('mode')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label for="reference_number"
                                        class="form-label small lh-sm fw-semibold text-dark mb-1">Reference
                                        Number</label>
                                    <input type="text" id="reference_number" name="reference_number"
                                        class="form-control"
                                        value="{{ old('reference_number', isset($payment) ? $payment->reference_number : '') }}">
                                    @error('reference_number')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label for="description"
                                        class="form-label small lh-sm fw-semibold text-dark mb-1">Notes</label>
                                    <textarea id="description" class="form-control" name="description"
                                        rows="4">{{ old('description', isset($payment) ? $payment->description : '') }}</textarea>
                                    @error('description')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2 mt-2">
                    <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                        {{ $isEditingPayment ? 'Update Payment' : 'Record Payment' }}
                        <i class="fas fa-arrow-right btn-icon ms-1"></i>
                    </button>
                </div>
            </form>
</div>
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
<script id="client-currencies-data" type="application/json">@json($clientCurrencies)</script>
<script id="invoice-totals-data" type="application/json">@json($invoiceTotals)</script>
<script id="invoice-options-data" type="application/json">@json($invoiceOptions)</script>
<script id="payment-details-data" type="application/json">@json($paymentDetailsMap)</script>
<script id="is-editing-payment-data" type="application/json">@json(isset($payment))</script>
<script id="default-tds-input-type-data" type="application/json">@json($defaultTdsInputType)</script>
<script id="default-tds-display-value-data" type="application/json">@json($defaultTdsDisplayValue)</script>
<script id="client-email-map-data"
    type="application/json">@json(collect($clients ?? [])->mapWithKeys(fn($client) => [(string) $client->clientid => (string)($client->primary_email ?? ($client->email ?? ''))])->all())</script>

<script>
    (function () {
        const clientCurrencies = JSON.parse(document.getElementById('client-currencies-data').textContent);
        const invoiceTotals = JSON.parse(document.getElementById('invoice-totals-data').textContent);
        const invoiceOptions = JSON.parse(document.getElementById('invoice-options-data').textContent);
        const paymentDetailsMap = JSON.parse(document.getElementById('payment-details-data').textContent);
        const isEditingPayment = JSON.parse(document.getElementById('is-editing-payment-data').textContent);
        const defaultTdsInputType = JSON.parse(document.getElementById('default-tds-input-type-data').textContent);
        const defaultTdsDisplayValue = JSON.parse(document.getElementById('default-tds-display-value-data').textContent);
        const selectedClientName = document.getElementById('selectedClientName');
        const selectedClientEmail = document.getElementById('selectedClientEmail');
        const clientEmailMap = JSON.parse(document.getElementById('client-email-map-data').textContent);

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
            if (selectedClientName) selectedClientName.textContent = (option?.textContent || '').trim() ||
                'Select Client';
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
                invoiceList.innerHTML = '<div class="text-muted w-100 d-flex align-items-center justify-content-center text-center rounded-3 bg-white" style="height: 200px; border: 2px dashed #ccc;">Select a client first</div>';
                return;
            }
            const clientInvoices = invoiceOptions.filter((invoice) => invoice.clientid === clientId);
            if (!clientInvoices.length) {
                invoiceList.innerHTML = '<div class="text-muted w-100 d-flex align-items-center justify-content-center text-center rounded-3 bg-white" style="height: 200px; border: 2px dashed #ccc;">No invoices for this client</div>';
                return;
            }

            const visibleInvoices = clientInvoices.filter((invoice) => {
                const totals = invoiceTotals[invoice.invoiceid] || {};
                const statusKey = (totals.payment_status || 'unpaid');
                const isChecked = isEditingPayment && !!paymentDetailsMap[invoice.invoiceid];

                return statusKey !== 'paid' || isChecked;
            });

            if (!visibleInvoices.length) {
                invoiceList.innerHTML = '<div class="text-muted w-100 d-flex align-items-center justify-content-center text-center rounded-3 bg-white" style="height: 200px; border: 2px dashed #ccc;">No unpaid invoices for this client</div>';
                return;
            }

            invoiceList.innerHTML = visibleInvoices.map((invoice) => {
                const totals = invoiceTotals[invoice.invoiceid] || {};
                const available = getAvailableToCollect(invoice.invoiceid);
                const availableWithoutTax = getAvailableWithoutTax(invoice.invoiceid);
                const amountWithoutTax = parseFloat(totals.amount_without_tax || 0);
                const taxRate = parseFloat(totals.tax_rate || 0);
                const taxAmount = amountWithoutTax * (taxRate / 100);
                const amountBreakup =
                    `${Math.round(amountWithoutTax).toLocaleString('en-US')} + ${Math.round(taxAmount).toLocaleString('en-US')}`;
                const isChecked = isEditingPayment && !!paymentDetailsMap[invoice.invoiceid];
                const savedTdsAmount = isEditingPayment && paymentDetailsMap[invoice.invoiceid] ?
                    (parseFloat(paymentDetailsMap[invoice.invoiceid].tds_amount || 0) || 0) :
                    0;
                const status = (totals.payment_status || 'unpaid').replace('_', ' ');
                const statusKey = (totals.payment_status || 'unpaid');
                const statusClass = statusKey === 'paid' ?
                    'payments-status-paid' :
                    (statusKey === 'partly_paid' ? 'payments-status-partly' : 'payments-status-unpaid');
                const title = (invoice.invoice_title || invoice.invoice_number || 'Invoice').trim();

                return `
                        <div class="col-12">
                        <div class="border-bottom rounded-3 p-3 bg-white invoice-row">
                            <div class="d-flex align-items-start gap-3">
                                <div class="flex-grow-1 min-w-0">
                                    <label class="d-flex align-items-start gap-2 form-check-label" style="cursor: pointer;">
                                        <input type="checkbox" class="form-check-input mt-1 invoice-option-checkbox border-primary border-2" name="invoice_ids[]" value="${invoice.invoiceid}" ${isChecked ? 'checked' : ''}>
                                        <span class="flex-grow-1">
                                            <strong class="d-block text-dark text-truncate">${title}</strong>
                                            <small class="text-dark">${invoice.invoice_number ? '#' + invoice.invoice_number : ''}</small>
                                            <div class="d-flex flex-wrap gap-2 mt-1 small">
                                                <span class="py-1 px-2 fw-semibold small lh-sm rounded-pill text-capitalize ${statusClass === 'payments-status-paid' ? 'text-bg-success' : (statusClass === 'payments-status-partly' ? 'text-bg-warning' : 'text-bg-secondary')}" >${status}</span>
                                                <span class="text-dark">Amt: <strong>${amountBreakup}</strong></span> |
                                                <span class="text-dark">Due: <strong>${Math.round(available).toLocaleString('en-US')}</strong></span>
                                            </div>
                                        </span>
                                    </label>
                                    <input type="hidden" class="invoice-collectible-input" data-due="${available}" data-without-tax="${availableWithoutTax}" value="${Math.round(available).toLocaleString('en-US')}">
                                </div>
                                <div class="d-flex align-self-center align-items-center gap-2 pt-1">
                                    <span class="small lh-sm text-dark invoice-allocated-label text-nowrap">Amount:</span>
                                    <input type="text" class="form-control form-control-sm invoice-allocated-input" style="max-width: 90px; height:30px;" value="0">
                                    <input type="text" class="form-control form-control-sm invoice-tds-input" style="max-width: 90px; display: none;" value="${savedTdsAmount > 0 ? formatInputNumber(savedTdsAmount) : ''}">
                                    <span class="small text-dark d-block invoice-live-state">0</span>
                                </div>
                            </div>
                            <input type="hidden" class="invoice-received-amount-hidden" name="invoice_received_amounts[${invoice.invoiceid}]" value="0">
                            <input type="hidden" class="invoice-tds-amount-hidden" name="invoice_tds_amounts[${invoice.invoiceid}]" value="0">
                        </div>
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

            const rows = Array.from(invoiceList.querySelectorAll('.invoice-row'));
            const checkedRows = rows
                .filter((row) => row.querySelector('.invoice-option-checkbox')?.checked)
                .sort((a, b) => {
                    const aOrder = parseInt(a.querySelector('.invoice-option-checkbox')?.dataset
                        .selectionOrder || '0', 10);
                    const bOrder = parseInt(b.querySelector('.invoice-option-checkbox')?.dataset
                        .selectionOrder || '0', 10);
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
                        liveState.innerHTML = '';
                        liveState.style.color = '#64748b';
                    } else if (allocated <= 0) {
                        liveState.innerHTML =
                            `New Balance: <span class="fw-semibold fs-6 lh-sm">${Math.round(getAvailableToCollect(invoiceId)).toLocaleString('en-US')}</span>`;
                        liveState.style.color = '#92400e';
                    } else if (nowRemaining <= 0.1) {
                        liveState.innerHTML = 'Fully Paid';
                        liveState.style.color = '#047857';
                    } else {
                        liveState.innerHTML =
                            `New Balance: <span class="fw-semibold fs-6 lh-sm">${Math.round(nowRemaining).toLocaleString('en-US')}</span>`;
                        liveState.style.color = '#92400e';
                    }
                }
            });

        }

        function recalculateTdsAllocations() {
            if (selectedPaymentFlow() !== 'tds') return;
            const rows = Array.from(invoiceList.querySelectorAll('.invoice-row'));
            const checkedRows = rows
                .filter((row) => row.querySelector('.invoice-option-checkbox')?.checked)
                .sort((a, b) => {
                    const aOrder = parseInt(a.querySelector('.invoice-option-checkbox')?.dataset
                        .selectionOrder || '0', 10);
                    const bOrder = parseInt(b.querySelector('.invoice-option-checkbox')?.dataset
                        .selectionOrder || '0', 10);
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
            if (tdsAmountInput) tdsAmountInput.style.display = isStandard ? 'none' : (isPercentMode ? 'block' :
                'none');
            invoiceList.querySelectorAll('.invoice-row').forEach((row) => {
                const allocLabel = row.querySelector('.invoice-allocated-label');
                const allocatedInput = row.querySelector('.invoice-allocated-input');
                const tdsInput = row.querySelector('.invoice-tds-input');
                if (isStandard) {
                    if (allocLabel) allocLabel.textContent = 'Amount:';
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
                const checkedNow = Array.from(invoiceList.querySelectorAll(
                    '.invoice-option-checkbox:checked'))
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
