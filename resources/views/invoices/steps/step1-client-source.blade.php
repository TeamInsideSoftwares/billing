<!-- Step 1: Client & Source Selection -->
<div id="step1" class="invoice-step">
    <style>
        #clientInvoicesAccordion {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }

        #clientInvoicesAccordion .category-accordion {
            margin: 0;
        }

        @media (max-width: 991px) {
            #clientInvoicesAccordion {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="invoice-meta-card mb-3">
        <div class="invoice-grid-4">
            <div class="invoice-span-2">
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
        </div>
    </div>

    <div id="existingInvoicesSection" class="is-hidden mb-3">
        <h4 class="invoice-existing-title">Existing Invoices</h4>
        <div id="invoiceLimitInfo" class="small-text text-muted mb-2 is-hidden"></div>
        <div id="clientInvoicesAccordion" class="services-accordion-container"></div>
        <div id="noInvoicesMessage" class="empty-state is-hidden">No invoices found for this client yet.</div>
    </div>

    <div id="sourceSelectionSection" class="is-hidden mb-3">
        <div class="section-title-card">
            <h4>Choose Invoice Source</h4>
            <p>Pick how this invoice should be created.</p>
        </div>

        <div class="source-grid">
            <label class="invoice-source-card">
                <input type="radio" name="invoice_for" value="orders" {{ old('invoice_for', request('invoice_for', session('invoice_for', ''))) === 'orders' ? 'checked' : '' }}>
                <span class="source-icon"><i class="fas fa-shopping-cart"></i></span>
                <strong>From Orders</strong>
            </label>
            <label class="invoice-source-card">
                <input type="radio" name="invoice_for" value="renewal" {{ old('invoice_for', request('invoice_for', session('invoice_for', ''))) === 'renewal' ? 'checked' : '' }}>
                <span class="source-icon"><i class="fas fa-sync-alt"></i></span>
                <strong>Renewal</strong>
            </label>
            <label class="invoice-source-card">
                <input type="radio" name="invoice_for" value="without_orders" {{ old('invoice_for', request('invoice_for', session('invoice_for', ''))) === 'without_orders' ? 'checked' : '' }}>
                <span class="source-icon"><i class="fas fa-pen-ruler"></i></span>
                <strong>Without Orders</strong>
            </label>
        </div>

        <div class="mt-3 d-flex justify-content-end">
            <button type="button" id="btnNextToStep2" class="primary-button invoice-step1-next-btn">Next Step
                &rarr;</button>
        </div>
    </div>
</div>

<script>
    (function () {
        const clientSelect = document.getElementById('clientid');
        const existingSection = document.getElementById('existingInvoicesSection');
        const sourceSection = document.getElementById('sourceSelectionSection');
        const accordion = document.getElementById('clientInvoicesAccordion');
        const invoiceLimitInfo = document.getElementById('invoiceLimitInfo');
        const noMsg = document.getElementById('noInvoicesMessage');
        const btnNext = document.getElementById('btnNextToStep2');
        const MAX_INVOICES_VISIBLE = 5;
        let selectedClientId = clientSelect.value || null;

        // Restore from URL
        const urlClientId = "{{ request('c', request('clientid', '')) }}";
        if (urlClientId) {
            selectedClientId = urlClientId;
            clientSelect.value = urlClientId;
            handleClientChange();
        }

        clientSelect.addEventListener('change', handleClientChange);

        function handleClientChange() {
            selectedClientId = clientSelect.value;
            syncClientInUrl(selectedClientId);
            if (!selectedClientId) {
                existingSection.classList.add('is-hidden');
                sourceSection.classList.add('is-hidden');
                return;
            }
            existingSection.classList.remove('is-hidden');
            sourceSection.classList.remove('is-hidden');
            loadInvoices(selectedClientId);
        }

        function syncClientInUrl(clientId) {
            const currentUrl = new URL(window.location.href);

            if (clientId) {
                currentUrl.searchParams.set('c', clientId);
            } else {
                currentUrl.searchParams.delete('c');
            }

            window.history.replaceState({}, '', currentUrl.toString());
        }

        async function loadInvoices(clientId) {
            accordion.innerHTML = '<div class="invoice-loading">Loading...</div>';
            noMsg.classList.add('is-hidden');

            try {
                const res = await fetch(`{{ route('invoices.index') }}?c=${clientId}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                const invoices = data.invoices || [];
                const visibleInvoices = invoices.slice(0, MAX_INVOICES_VISIBLE);

                if (invoices.length === 0) {
                    accordion.innerHTML = '';
                    noMsg.classList.remove('is-hidden');
                    invoiceLimitInfo.classList.add('is-hidden');
                    return;
                }

                if (invoices.length > MAX_INVOICES_VISIBLE) {
                    invoiceLimitInfo.textContent = `Showing latest ${MAX_INVOICES_VISIBLE} of ${invoices.length} invoices.`;
                    invoiceLimitInfo.classList.remove('is-hidden');
                } else {
                    invoiceLimitInfo.classList.add('is-hidden');
                }

                accordion.innerHTML = visibleInvoices.map(inv => {
                    const statusLabel = String(inv.status || 'active').toLowerCase() === 'cancelled' ? 'Cancelled' : 'Active';
                    const statusClass = statusLabel.toLowerCase();
                    const title = inv.title ? `${inv.title} (${inv.number || ''})` : (inv.number || 'Untitled');

                    // Build items HTML
                    const itemsHtml = (inv.items || []).map(item => {
                        const details = [];
                        // if (item.price && item.price !== '0') details.push(`Unit: ${item.price}`);
                        if (item.tax_rate) details.push(`Tax: ${item.tax_rate}%`);
                        if (item.users && item.users > 1) details.push(`Users: ${item.users}`);
                        if (item.frequency) details.push(`Freq: ${item.frequency}`);
                        if (item.duration) details.push(`Dur: ${item.duration}`);

                        const dates = [];
                        if (item.start_date) dates.push(`Start: ${item.start_date}`);
                        if (item.end_date) dates.push(`End: ${item.end_date}`);

                        return `
                        <div class="inv-item-row">
                            <div class="inv-item-row__top">
                                <span class="inv-item-row__name">${item.name || 'Item'} (x${Math.max(1, Math.round(Number(item.qty || item.quantity || 1)))})</span>
                                <strong class="inv-item-row__total">${item.total || '-'}</strong>
                            </div>
                            ${details.length ? `<div class="inv-item-row__details">${details.join(' | ')}</div>` : ''}
                            ${dates.length ? `<div class="inv-item-row__dates">${dates.join(' | ')}</div>` : ''}
                        </div>`;
                    }).join('') || '<div class="inv-item-row inv-item-row--empty">No items found</div>';

                    return `
                    <details class="category-accordion">
                        <summary class="category-accordion-header invoice-accordion-header">
                            <span class="invoice-accordion-left">
                                <span class="invoice-accordion-title">${title}</span>
                                <span class="invoice-accordion-subtitle">Issue: ${inv.issue_date || '-'} | Due: ${inv.due_date || '-'}</span>
                            </span>
                            <span class="invoice-accordion-right">
                                <a href="{{ url('invoices') }}/${inv.record_id}/edit" class="invoice-accordion-edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <span class="invoice-accordion-type">${inv.ti_number ? 'Tax Invoice' : 'Proforma Invoice'}</span>
                                <span class="badge bg-secondary rounded-pill ${statusClass}">${statusLabel}</span>
                                <span class="invoice-accordion-amount">${inv.amount || '-'}</span>
                            </span>
                        </summary>
                        <div class="accordion-content invoice-accordion-content">
                            <div class="items-display invoice-accordion-items">
                                ${itemsHtml}
                            </div>
                        </div>
                    </details>
                `;
                }).join('');
            } catch (err) {
                accordion.innerHTML = '';
                noMsg.classList.remove('is-hidden');
                invoiceLimitInfo.classList.add('is-hidden');
            }
        }

        btnNext.addEventListener('click', function () {
            if (!selectedClientId) {
                alert('Please select a client first.');
                return;
            }
            const source = document.querySelector('input[name="invoice_for"]:checked');
            if (!source) {
                alert('Please choose an invoice source.');
                return;
            }
            const clientToken = encodeURIComponent(selectedClientId);
            const sourceToken = encodeURIComponent(source.value);
            window.location.href = "{{ route('invoices.create') }}?step=2&invoice_for=" + sourceToken + "&c=" + clientToken;
        });
    })();
</script>
