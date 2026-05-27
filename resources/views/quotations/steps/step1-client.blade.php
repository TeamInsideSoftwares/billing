<div class="invoice-meta-card quotation-centered-card">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h4 class="quotation-step1-title">Select Client</h4>
        <div class="invoice-compact-steps">
            <span class="invoice-compact-step is-active">1</span>
            <span class="invoice-compact-step">2</span>
            <span class="invoice-compact-step">3</span>
            <span class="invoice-compact-step">4</span>
        </div>
    </div>
    <label class="field-label" for="clientid">Client</label>
    <select id="clientid" class="form-input" required>
        <option value="">Choose client</option>
        @foreach($clients as $client)
            <option value="{{ $client->clientid }}" {{ $clientId == $client->clientid ? 'selected' : '' }}>
                {{ $client->business_name ?? $client->contact_name }}
            </option>
        @endforeach
    </select>
    <div class="mt-3 d-flex justify-content-end">
        <button type="button" class="primary-button" id="toStep2">Next</button>
    </div>
</div>
