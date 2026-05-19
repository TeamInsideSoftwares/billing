<!-- Step 1: Client Selection -->
<div id="step1" class="invoice-step">
    <style>
        .invoice-step1-card {
            max-width: 760px;
            margin: 0 auto;
            padding: 1.1rem 1rem;
        }

        .invoice-step1-head {
            margin-bottom: 0.85rem;
        }

        .invoice-step1-head-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .invoice-step1-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #111827;
        }

        .invoice-step1-subtitle {
            margin: 0.3rem 0 0 0;
            font-size: 0.88rem;
            color: #6b7280;
        }

        .invoice-compact-steps {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .invoice-compact-step {
            width: 1.6rem;
            height: 1.6rem;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            color: #6b7280;
            font-size: 0.78rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
        }

        .invoice-compact-step.is-active {
            border-color: #2563eb;
            background: #2563eb;
            color: #fff;
        }

        .invoice-step1-actions {
            margin-top: 1rem;
            display: flex;
            justify-content: flex-end;
        }

        @media (max-width: 767px) {
            .invoice-step1-card {
                padding: 0.85rem 0.75rem;
            }

            .invoice-step1-head-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .invoice-step1-actions {
                justify-content: stretch;
            }

            .invoice-step1-next-btn {
                width: 100%;
            }
        }
    </style>

    <div class="invoice-meta-card invoice-step1-card">
        <div class="invoice-step1-head">
            <div class="invoice-step1-head-row">
                <h4 class="invoice-step1-title">Select Client</h4>
                <div class="invoice-compact-steps" aria-label="Step progress">
                    <span class="invoice-compact-step is-active">1</span>
                    <span class="invoice-compact-step">2</span>
                    <span class="invoice-compact-step">3</span>
                    <span class="invoice-compact-step">4</span>
                </div>
            </div>
            <p class="invoice-step1-subtitle">Choose the client to continue and add invoice items.</p>
        </div>

        <div>
            <label for="clientid" class="field-label">Client</label>
            <select id="clientid" name="clientid" required class="form-input">
                <option value="">Choose a client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->clientid }}" data-currency="{{ $client->currency ?? 'INR' }}" {{ old('clientid', request('c')) == $client->clientid ? 'selected' : '' }}>
                        {{ $client->business_name ?? $client->contact_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="invoice-step1-actions">
            <button type="button" id="btnNextToStep2" class="primary-button invoice-step1-next-btn">Next &rarr;</button>
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
