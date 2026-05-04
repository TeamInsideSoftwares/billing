@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('payments.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left" class="icon-spaced"></i>Back to Payments
    </a>
@endsection

@section('content')
<section class="panel-card">
    @php
        $defaultClientId = old('clientid', isset($payment) ? $payment->clientid : ($selectedClientId ?? ''));
        $defaultInvoiceId = old('invoiceid', isset($payment) ? $payment->invoiceid : ($selectedInvoiceId ?? ''));
        $defaultCurrency = $selectedCurrency ?? (isset($payment) ? ($payment->client->currency ?? 'INR') : 'INR');
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
    @endphp
    <form method="POST" action="{{ isset($payment) ? route('payments.update', $payment) : route('payments.store') }}" class="client-form">
        @isset($payment)
            @method('PUT')
        @endisset
        @csrf
        <div class="form-grid">
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
            <div>
                <label for="invoiceid">Related Invoice (Optional)</label>
                <select id="invoiceid" name="invoiceid">
                    <option value="">-- No Specific Invoice --</option>
                    @foreach($invoices as $invoice)
                        <option value="{{ $invoice->invoiceid }}" data-clientid="{{ $invoice->clientid }}" {{ $defaultInvoiceId == $invoice->invoiceid ? 'selected' : '' }}>
                            {{ $invoice->invoice_number }} ({{ $invoice->client->business_name ?? 'Client' }})
                        </option>
                    @endforeach
                </select>
                <div id="invoiceGrandTotalHint" class="text-muted mt-1"></div>
            </div>
            <div>
                <label for="received_amount">Received Amount * (<span id="currencyLabel">{{ $defaultCurrency }}</span>)</label>
                <input type="number" step="0.01" id="received_amount" name="received_amount" value="{{ old('received_amount', isset($payment) ? $payment->received_amount : '') }}" min="0.01" required>
                @error('received_amount') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                @php
                    $defaultTds = old('tds_amount', isset($payment) ? ($payment->tds_amount ?? 0) : 0);
                    $tdsChecked = (float) $defaultTds > 0;
                @endphp
                <label class="custom-checkbox">
                    <input type="checkbox" id="has_tds" {{ $tdsChecked ? 'checked' : '' }}>
                    <span class="checkbox-label">TDS Deducted?</span>
                </label>
                <div id="tdsAmountWrap" class="mt-2" style="{{ $tdsChecked ? '' : 'display:none;' }}">
                    <label for="tds_amount">TDS Amount (<span id="currencyLabel2">{{ $defaultCurrency }}</span>)</label>
                    <input type="number" step="0.01" id="tds_amount" name="tds_amount" value="{{ $defaultTds }}" min="0">
                    @error('tds_amount') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div>
                <label for="payment_date">Payment Date *</label>
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
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-button">{{ isset($payment) ? 'Update Payment' : 'Record Payment' }}</button>
            <a href="{{ route('payments.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
<script>
(function() {
    const clientCurrencies = @json($clientCurrencies);
    const invoiceTotals = @json($invoiceTotals);
    const clientSelect = document.getElementById('clientid');
    const invoiceSelect = document.getElementById('invoiceid');
    const currencyLabel = document.getElementById('currencyLabel');
    const currencyLabel2 = document.getElementById('currencyLabel2');
    const invoiceGrandTotalHint = document.getElementById('invoiceGrandTotalHint');
    const hasTds = document.getElementById('has_tds');
    const tdsAmountWrap = document.getElementById('tdsAmountWrap');
    const tdsAmountInput = document.getElementById('tds_amount');

    function setCurrencyFromClient() {
        const clientId = clientSelect?.value || '';
        const currency = clientCurrencies[clientId] || 'INR';
        if (currencyLabel) currencyLabel.textContent = currency;
        if (currencyLabel2) currencyLabel2.textContent = currency;
    }

    function syncClientFromInvoice() {
        const selectedOption = invoiceSelect?.options[invoiceSelect.selectedIndex];
        const invoiceClientId = selectedOption?.dataset?.clientid || '';
        const invoiceId = invoiceSelect?.value || '';
        const invoiceMeta = invoiceTotals[invoiceId];
        if (invoiceClientId && clientSelect && clientSelect.value !== invoiceClientId) {
            clientSelect.value = invoiceClientId;
        }
        if (invoiceGrandTotalHint) {
            if (invoiceMeta) {
                const grandTotal = Number(invoiceMeta.grand_total || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
                invoiceGrandTotalHint.textContent = `Invoice Grand Total: ${invoiceMeta.currency} ${grandTotal}`;
            } else {
                invoiceGrandTotalHint.textContent = '';
            }
        }
        setCurrencyFromClient();
    }

    clientSelect?.addEventListener('change', setCurrencyFromClient);
    invoiceSelect?.addEventListener('change', syncClientFromInvoice);
    hasTds?.addEventListener('change', function () {
        const checked = !!hasTds.checked;
        if (tdsAmountWrap) tdsAmountWrap.style.display = checked ? '' : 'none';
        if (!checked && tdsAmountInput) tdsAmountInput.value = '0';
    });
    syncClientFromInvoice();
    setCurrencyFromClient();
})();
</script>
@endsection
