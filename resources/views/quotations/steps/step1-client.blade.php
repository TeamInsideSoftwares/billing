<div class="quotation-centered-card">
    <div class="quotation-step3-header">
        <div class="quotation-step3-header-row">
            <div class="quotation-step3-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="quotation-step3-client min-w-0">
                <div class="quotation-step3-client-name">Select Client</div>
                <div class="quotation-step3-client-email">Choose the client to continue the quotation flow.</div>
            </div>
            <div class="quotation-step3-tools">
                <div class="invoice-compact-steps invoice-compact-steps--right" aria-label="Step progress">
                    <span class="invoice-compact-step is-active">1</span>
                    <span class="invoice-compact-step">2</span>
                    <span class="invoice-compact-step">3</span>
                    <span class="invoice-compact-step">4</span>
                </div>
            </div>
        </div>
    </div>

    <div class="soft-panel soft-panel--padded">
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
</div>
