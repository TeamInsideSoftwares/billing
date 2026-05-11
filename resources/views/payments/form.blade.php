@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('payments.index', !empty($selectedClientId) ? ['c' => $selectedClientId] : []) }}" class="secondary-button">
        <i class="fas fa-arrow-left" class="icon-spaced"></i>Back to Payments
    </a>
@endsection

@section('content')
<style>
    .payments-form-panel {
        overflow: visible !important;
        min-height: 520px; /* Ensure enough space for the absolute dropdown */
    }

    #invoice-dropdown-wrap {
        max-width: 100%;
        width: 100%;
        position: relative; /* Ensure the absolute dropdown is positioned correctly */
    }

    .payments-form-shell,
    .payments-form-shell .client-form,
    .payments-form-shell .form-grid {
        overflow: visible !important;
    }

    #invoice-dropdown {
        z-index: 2000;
        max-height: 250px;
        overflow-y: auto;
        overflow-x: hidden;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
</style>
<section class="panel-card payments-form-panel">
    @php
        $defaultClientId = old('clientid', isset($payment) ? $payment->clientid : ($selectedClientId ?? ''));
        $defaultInvoiceId = old('invoiceid', isset($payment) ? $payment->invoiceid : ($selectedInvoiceId ?? ''));
        $defaultCurrency = $selectedCurrency ?? (isset($payment) ? ($payment->client->currency ?? 'INR') : 'INR');
        $defaultPaymentType = old('type', isset($payment) ? ($payment->type ?? 'payment') : 'payment');
        $clientCurrencies = collect($clients ?? [])->mapWithKeys(fn($client) => [
            (string) $client->clientid => (string) ($client->currency ?? 'INR'),
        ])->all();
        $invoiceTotals = collect($invoices ?? [])->mapWithKeys(function ($invoice) {
            return [
                (string) $invoice->invoiceid => [
                    'grand_total' => (float) ($invoice->grand_total ?? 0),
                    'currency' => (string) ($invoice->client->currency ?? 'INR'),
                ],
            ];
        })->all();
        $invoiceOptions = collect($invoices ?? [])->map(function ($invoice) {
            return [
                'invoiceid' => (string) $invoice->invoiceid,
                'invoice_number' => (string) ($invoice->invoice_number ?? ''),
                'invoice_title' => (string) ($invoice->invoice_title ?? ''),
                'clientid' => (string) ($invoice->clientid ?? ''),
                'client_name' => (string) ($invoice->client->business_name ?? 'Client'),
            ];
        })->values()->all();
    @endphp
    <form method="POST" action="{{ isset($payment) ? route('payments.update', $payment) : route('payments.store') }}" class="client-form payments-form-shell">
        @isset($payment)
            @method('PUT')
        @endisset
        @csrf
        <div class="form-grid">
            <div style="grid-column: 1 / -1; display: flex; align-items: center; gap: 1.25rem; margin-bottom: 0.5rem; background: #f8fafc; padding: 0.65rem 1rem; border-radius: 8px; border: 1px solid #e5e7eb;">
                <label style="font-weight: 700; font-size: 0.85rem; color: #475569; margin: 0;">Entry Type:</label>
                <div style="display: flex; gap: 1rem;">
                    <label class="custom-radio">
                        <input type="radio" name="type" value="payment" {{ $defaultPaymentType !== 'tds' ? 'checked' : '' }}>
                        <span class="radio-label">Standard Payment</span>
                    </label>
                    <label class="custom-radio">
                        <input type="radio" name="type" value="tds" {{ $defaultPaymentType === 'tds' ? 'checked' : '' }}>
                        <span class="radio-label">TDS (Tax Deducted at Source)</span>
                    </label>
                </div>
                @error('type') <span class="error" style="margin-left: auto;">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="clientid">Select Client *</label>
                <select id="clientid" name="clientid" required>
                    <option value="">-- Choose Client --</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->clientid }}" {{ $defaultClientId == $client->clientid ? 'selected' : '' }}>
                            {{ $client->business_name ?? $client->contact_name }}
                        </option>
                    @endforeach
                </select>
                @error('clientid') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div style="grid-column: span 2;">
                <label for="invoiceid">Related Invoice (Optional)</label>
                <div class="addons-wrap" id="invoice-dropdown-wrap">
                    <button type="button" class="secondary-button addons-toggle" id="invoice-toggle" disabled>
                        <span id="invoice-selected-label">Select invoice</span>
                        <span aria-hidden="true">&#9662;</span>
                    </button>
                    <div id="invoice-dropdown" class="addons-dropdown" style="display: none;">
                        <p class="addons-empty">Select a client first</p>
                    </div>
                </div>
                <input type="hidden" id="invoiceid" name="invoiceid" value="{{ $defaultInvoiceId }}">
                <div id="invoiceGrandTotalHint" class="text-muted mt-1"></div>
            </div>
            <div>
                <label for="received_amount">Amount * (<span id="currencyLabel">{{ $defaultCurrency }}</span>)</label>
                <input type="text" id="received_amount" name="received_amount" value="{{ old('received_amount', isset($payment) ? $payment->received_amount : '') }}" required>
                @error('received_amount') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="payment_date">Date *</label>
                <input type="date" id="payment_date" name="payment_date" value="{{ old('payment_date', isset($payment) ? optional($payment->payment_date)->format('Y-m-d') : date('Y-m-d')) }}" required>
                @error('payment_date') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="mode">Mode *</label>
                <select id="mode" name="mode" required>
                    <option value="Bank Transfer" {{ old('mode', isset($payment) ? $payment->mode : '') == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="Online" {{ old('mode', isset($payment) ? $payment->mode : '') == 'Online' ? 'selected' : '' }}>Online</option>
                    <option value="Cash" {{ old('mode', isset($payment) ? $payment->mode : '') == 'Cash' ? 'selected' : '' }}>Cash</option>
                </select>
                @error('mode') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="reference_number">Reference Number</label>
                <input type="text" id="reference_number" name="reference_number" value="{{ old('reference_number', isset($payment) ? $payment->reference_number : '') }}">
                @error('reference_number') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="2">{{ old('description', isset($payment) ? $payment->description : '') }}</textarea>
                @error('description') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-button">{{ isset($payment) ? 'Update Payment' : 'Record Payment' }}</button>
            <a href="{{ route('payments.index', !empty($selectedClientId) ? ['c' => $selectedClientId] : []) }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
<script>
(function() {
    const clientCurrencies = @json($clientCurrencies);
    const invoiceTotals = @json($invoiceTotals);
    const invoiceOptions = @json($invoiceOptions);
    const clientSelect = document.getElementById('clientid');
    const invoiceHidden = document.getElementById('invoiceid');
    const currencyLabel = document.getElementById('currencyLabel');
    const invoiceGrandTotalHint = document.getElementById('invoiceGrandTotalHint');
    const invoiceDropdownWrap = document.getElementById('invoice-dropdown-wrap');
    const invoiceToggle = document.getElementById('invoice-toggle');
    const invoiceDropdown = document.getElementById('invoice-dropdown');
    const invoiceSelectedLabel = document.getElementById('invoice-selected-label');
    let selectedInvoiceId = invoiceHidden?.value || '';

    function setCurrencyFromClient() {
        const clientId = clientSelect?.value || '';
        const currency = clientCurrencies[clientId] || 'INR';
        if (currencyLabel) currencyLabel.textContent = currency;
    }

    function syncInvoiceHint() {
        const invoiceMeta = invoiceTotals[selectedInvoiceId];

        if (invoiceGrandTotalHint) {
            if (invoiceMeta) {
                const grandTotal = Number(invoiceMeta.grand_total || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
                invoiceGrandTotalHint.textContent = `Invoice Grand Total: ${invoiceMeta.currency} ${grandTotal}`;
            } else {
                invoiceGrandTotalHint.textContent = '';
            }
        }
    }

    function renderInvoiceDropdown() {
        const clientId = clientSelect?.value || '';
        if (!clientId) {
            invoiceToggle.disabled = true;
            invoiceDropdown.innerHTML = '<p class="addons-empty">Select a client first</p>';
            selectedInvoiceId = '';
            if (invoiceHidden) invoiceHidden.value = '';
            invoiceSelectedLabel.textContent = 'Select invoice';
            syncInvoiceHint();
            return;
        }

        invoiceToggle.disabled = false;
        const clientInvoices = invoiceOptions.filter((invoice) => invoice.clientid === clientId);

        if (clientInvoices.length === 0) {
            invoiceDropdown.innerHTML = '<p class="addons-empty">No invoices for this client</p>';
            selectedInvoiceId = '';
            if (invoiceHidden) invoiceHidden.value = '';
            invoiceSelectedLabel.textContent = 'Select invoice';
            syncInvoiceHint();
            return;
        }

        // Clear stale selection if it belongs to another client.
        const selectedForClient = clientInvoices.some((invoice) => invoice.invoiceid === selectedInvoiceId);
        if (!selectedForClient) {
            selectedInvoiceId = '';
            if (invoiceHidden) invoiceHidden.value = '';
        }

        const html = clientInvoices.map((invoice) => {
            const checked = selectedInvoiceId === invoice.invoiceid ? 'checked' : '';
            const title = (invoice.invoice_title || '').trim();
            const primary = title !== '' ? title : invoice.invoice_number;
            const numberText = (invoice.invoice_number || '').trim();
            return `
                <label class="custom-checkbox addon-option">
                    <input type="checkbox" class="invoice-option-checkbox" value="${invoice.invoiceid}" ${checked}>
                    <span class="checkbox-label">
                        <strong>${primary}</strong>
                        <small style="display:block; color:#64748b; font-weight:500; margin-top:2px;">${numberText}</small>
                    </span>
                </label>
            `;
        }).join('');

        invoiceDropdown.innerHTML = html;

        invoiceDropdown.querySelectorAll('.invoice-option-checkbox').forEach((checkbox) => {
            checkbox.addEventListener('change', function () {
                if (!this.checked) {
                    selectedInvoiceId = '';
                    if (invoiceHidden) invoiceHidden.value = '';
                    invoiceSelectedLabel.textContent = 'Select invoice';
                    syncInvoiceHint();
                    return;
                }

                selectedInvoiceId = this.value;
                if (invoiceHidden) invoiceHidden.value = selectedInvoiceId;
                invoiceSelectedLabel.textContent = this.closest('label')?.querySelector('.checkbox-label')?.textContent || '1 invoice selected';
                invoiceDropdown.querySelectorAll('.invoice-option-checkbox').forEach((cb) => {
                    if (cb !== this) cb.checked = false;
                });
                syncInvoiceHint();
                invoiceDropdown.style.display = 'none';
            });
        });

        if (selectedInvoiceId) {
            const picked = clientInvoices.find((invoice) => invoice.invoiceid === selectedInvoiceId);
            invoiceSelectedLabel.textContent = picked ? picked.invoice_number : 'Select invoice';
        } else {
            invoiceSelectedLabel.textContent = 'Select invoice';
        }

        syncInvoiceHint();
    }

    clientSelect?.addEventListener('change', function () {
        setCurrencyFromClient();
        renderInvoiceDropdown();
    });

    invoiceToggle?.addEventListener('click', function () {
        if (invoiceToggle.disabled) return;
        invoiceDropdown.style.display = invoiceDropdown.style.display === 'none' ? 'block' : 'none';
    });

    document.addEventListener('click', function (e) {
        if (!invoiceDropdownWrap?.contains(e.target)) {
            invoiceDropdown.style.display = 'none';
        }
    });

    setCurrencyFromClient();
    renderInvoiceDropdown();
})();
</script>
@endsection
