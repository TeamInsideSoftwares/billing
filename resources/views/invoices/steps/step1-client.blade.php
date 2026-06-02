<!-- Step 1: Client Selection -->
<div id="step1" class="invoice-step">
    <div class="quotation-centered-card">
        <div class="quotation-step3-header">
            <div class="quotation-step3-header-row">
                <div class="quotation-step3-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="quotation-step3-client min-w-0">
                    <div class="quotation-step3-client-name">Select Client</div>
                    <div class="quotation-step3-client-email">Choose the client to continue the invoice flow.</div>
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
            <label for="clientid" class="field-label">Client</label>
            <select id="clientid" name="clientid" required class="form-input">
                <option value="">Choose client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->clientid }}" data-currency="{{ $client->currency ?? 'INR' }}" {{ old('clientid', request('c')) == $client->clientid ? 'selected' : '' }}>
                        {{ $client->business_name ?? $client->contact_name }}
                    </option>
                @endforeach
            </select>
            <div class="mt-3 d-flex justify-content-end">
                <button type="button" id="btnNextToStep2" class="primary-button">Next</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const clientSelect = document.getElementById('clientid');
        const btnNext = document.getElementById('btnNextToStep2');

        function syncClientInUrl(clientId) {
            const currentUrl = new URL(window.location.href);
            if (clientId) {
                currentUrl.searchParams.set('c', clientId);
            } else {
                currentUrl.searchParams.delete('c');
            }
            window.history.replaceState({}, '', currentUrl.toString());
        }

        const urlClientId = "{{ request('c', request('clientid', '')) }}";
        if (urlClientId) {
            clientSelect.value = urlClientId;
            syncClientInUrl(urlClientId);
        }

        clientSelect.addEventListener('change', function () {
            syncClientInUrl(this.value);
        });

        btnNext.addEventListener('click', function () {
            const selectedClientId = clientSelect.value;
            if (!selectedClientId) {
                alert('Please select a client first.');
                clientSelect.focus();
                return;
            }

            const clientToken = encodeURIComponent(selectedClientId);
            window.location.href = "{{ route('invoices.create') }}?step=2&c=" + clientToken;
        });
    })();
</script>
