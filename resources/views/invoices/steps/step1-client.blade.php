<!-- Step 1: Client Selection -->
<div id="step1" class="position-relative d-flex align-items-center justify-content-center"
    style="min-height: calc(100vh - 160px);">
    <div class="row w-100">
        <div class="col-12 col-md-3 mx-auto">
            <div class="bg-white p-4 rounded-3 mx-auto mb-5">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-1">
                    <div class="min-w-0">
                        <h5 class="fw-semibold text-black mb-0">Manage Invoices</h5>
                        <p class="text-dark mb-0">Choose client first</p>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-12">
                        <label for="clientid" class="form-label small lh-sm fw-semibold text-dark mb-1">Clients ({{
                            $clients->count() }}) <span class="text-danger">*</span></label>
                        <select id="clientid" name="clientid" required class="form-select">
                            <option value="">Choose client</option>
                            @foreach($clients as $client)
                            <option value="{{ $client->clientid }}" data-currency="{{ $client->currency ?? 'INR' }}" {{
                                old('clientid', request('c'))==$client->clientid ? 'selected' : '' }}>
                                {{ $client->business_name ?? $client->contact_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2 mt-3">
                    <button type="button" id="btnNextToStep2"
                        class="btn btn-outline-primary btn-primary text-white fw-medium">
                        Create Invoice <i class="fas fa-arrow-right btn-icon ms-1"></i>
                    </button>
                </div>
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
            const createRoute = "{{ route('invoices.create') }}";
            const createPath = createRoute.startsWith('http') ? new URL(createRoute).pathname : createRoute;
            window.location.href = createPath + "?step=2&c=" + clientToken;
        });
    })();
</script>
