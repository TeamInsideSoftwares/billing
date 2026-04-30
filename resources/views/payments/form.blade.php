@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('payments.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left" class="icon-spaced"></i>Back to Payments
    </a>
@endsection

@section('content')
<section class="panel-card">
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
                        <option value="{{ $client->clientid }}" {{ old('clientid', isset($payment) ? $payment->clientid : '') == $client->clientid ? 'selected' : '' }}>
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
                        <option value="{{ $invoice->invoiceid }}" {{ old('invoiceid', isset($payment) ? $payment->invoiceid : '') == $invoice->invoiceid ? 'selected' : '' }}>
                            {{ $invoice->invoice_number }} ({{ $invoice->client->business_name ?? 'Client' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="amount">Payment Amount (Rs) *</label>
                <input type="number" step="0.01" id="amount" name="amount" value="{{ old('amount', isset($payment) ? $payment->amount : '') }}" required>
                @error('amount') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="paid_at">Payment Date *</label>
                <input type="date" id="paid_at" name="paid_at" value="{{ old('paid_at', isset($payment) ? (is_string($payment->paid_at) ? $payment->paid_at : $payment->paid_at->format('Y-m-d')) : date('Y-m-d')) }}" required>
                @error('paid_at') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="method">Payment Method *</label>
                <select id="method" name="method" required>
                    <option value="Bank Transfer" {{ old('method', isset($payment) ? $payment->method : '') == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="UPI" {{ old('method', isset($payment) ? $payment->method : '') == 'UPI' ? 'selected' : '' }}>UPI</option>
                    <option value="Cash" {{ old('method', isset($payment) ? $payment->method : '') == 'Cash' ? 'selected' : '' }}>Cash</option>
                    <option value="Cheque" {{ old('method', isset($payment) ? $payment->method : '') == 'Cheque' ? 'selected' : '' }}>Cheque</option>
                </select>
            </div>
            <div>
                <label for="reference">Reference Number</label>
                <input type="text" id="reference" name="reference" value="{{ old('reference', isset($payment) ? $payment->reference : '') }}">
            </div>
            <div class="col-span-2">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3">{{ old('notes', isset($payment) ? $payment->notes : '') }}</textarea>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="primary-button">{{ isset($payment) ? 'Update Payment' : 'Record Payment' }}</button>
            <a href="{{ route('payments.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>
@endsection
