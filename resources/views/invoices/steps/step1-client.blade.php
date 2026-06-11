<!-- Step 1: Client Selection -->
<div id="step1">
    <div class="bg-light p-4 rounded-3 border">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="d-flex align-items-center justify-content-center bg-white rounded-circle border" style="width: 40px; height: 40px;">
                <i class="fas fa-user text-primary"></i>
            </div>
            <div class="min-w-0">
                <div class="fw-semibold text-dark">Select Client</div>
                <div class="text-muted small">Choose the client to continue the invoice flow.</div>
            </div>
            <div class="ms-auto">
                <div class="d-inline-flex align-items-center gap-1" aria-label="Step progress">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white fw-semibold" style="width: 26px; height: 26px; font-size: 0.75rem;">1</span>
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-muted border fw-semibold" style="width: 26px; height: 26px; font-size: 0.75rem;">2</span>
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-muted border fw-semibold" style="width: 26px; height: 26px; font-size: 0.75rem;">3</span>
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-muted border fw-semibold" style="width: 26px; height: 26px; font-size: 0.75rem;">4</span>
                </div>
            </div>
        </div>

        <div class="row g-2">
            <div class="col-12">
                <label for="clientid" class="form-label small lh-sm fw-semibold text-dark mb-1">Client</label>
                <select id="clientid" name="clientid" required class="form-select">
                    <option value="">Choose client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->clientid }}" data-currency="{{ $client->currency ?? 'INR' }}" {{ old('clientid', request('c')) == $client->clientid ? 'selected' : '' }}>
                            {{ $client->business_name ?? $client->contact_name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-end gap-2 mt-3">
            <button type="button" id="btnNextToStep2" class="btn btn-outline-primary btn-primary text-white fw-medium">Next</button>
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
            const createRoute = "{{ route('invoices.create') }}";
            const createPath = createRoute.startsWith('http') ? new URL(createRoute).pathname : createRoute;
            window.location.href = createPath + "?step=2&c=" + clientToken;
        });
    })();
</script>
