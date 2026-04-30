<!-- Step 1: Client & Source Selection -->
<div id="step1" class="invoice-step">
    <div class="invoice-meta-card" style="margin-bottom: 0.75rem;">
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

    <div id="existingInvoicesSection" style="display: none; margin-bottom: 0.75rem;">
        <h4 style="margin: 0 0 0.6rem 0; font-size: 0.92rem; color: #334155;">Existing Invoices</h4>
        <div id="clientInvoicesAccordion" class="services-accordion-container"></div>
        <div id="noInvoicesMessage" class="empty-state" style="display: none;">No invoices found for this client yet.</div>
    </div>

    <div id="sourceSelectionSection" style="display: none; margin-bottom: 0.75rem;">
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

        <div style="margin-top: 0.75rem; display: flex; justify-content: flex-end;">
            <button type="button" id="btnNextToStep2" class="primary-button" style="padding: 0.55rem 1.35rem; font-size: 0.85rem;">Next Step &rarr;</button>
        </div>
    </div>
</div>

<script>
(function() {
    const clientSelect = document.getElementById('clientid');
    const existingSection = document.getElementById('existingInvoicesSection');
    const sourceSection = document.getElementById('sourceSelectionSection');
    const accordion = document.getElementById('clientInvoicesAccordion');
    const noMsg = document.getElementById('noInvoicesMessage');
    const btnNext = document.getElementById('btnNextToStep2');
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
            existingSection.style.display = 'none';
            sourceSection.style.display = 'none';
            return;
        }
        existingSection.style.display = 'block';
        sourceSection.style.display = 'block';
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
        accordion.innerHTML = '<div style="padding: 1rem; text-align: center; color: #9ca3af;">Loading...</div>';
        noMsg.style.display = 'none';

        try {
            const res = await fetch(`{{ route('invoices.index') }}?c=${clientId}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            const invoices = data.invoices || [];

            if (invoices.length === 0) {
                accordion.innerHTML = '';
                noMsg.style.display = 'block';
                return;
            }

            accordion.innerHTML = invoices.map(inv => {
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
                        <div style="padding: 0.55rem 0; border-bottom: 1px dashed #e5e7eb; font-size: 0.78rem;">
                            <div style="display: flex; justify-content: space-between; gap: 0.75rem;">
                                <span style="color: #374151; font-weight: 600;">${item.name || 'Item'} (x${Math.max(1, Math.round(Number(item.qty || item.quantity || 1)))})</span>
                                <strong style="color: #111827;">${item.total || '-'}</strong>
                            </div>
                            ${details.length ? `<div style="margin-top: 0.15rem; color: #6b7280; font-size: 0.72rem;">${details.join(' | ')}</div>` : ''}
                            ${dates.length ? `<div style="margin-top: 0.1rem; color: #9ca3af; font-size: 0.7rem;">${dates.join(' | ')}</div>` : ''}
                        </div>`;
                }).join('') || '<div style="padding: 0.5rem 0; color: #9ca3af; font-style: italic; font-size: 0.78rem;">No items found</div>';

                return `
                    <details class="category-accordion">
                        <summary class="category-accordion-header" 
                            style="padding: 0.8rem 1rem; cursor: pointer; display: flex; align-items: center; justify-content: space-between;">
                            <span style="display: inline-flex; flex-direction: column; gap: 0.05rem;">
                                <span style="font-size: 0.84rem; font-weight: 600; color: #111827;">${title}</span>
                                <span style="font-size: 0.72rem; color: #6b7280;">Issue: ${inv.issue_date || '-'} | Due: ${inv.due_date || '-'}</span>
                            </span>
                            <span style="display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">
                                <a href="{{ url('invoices') }}/${inv.record_id}/edit" style="font-size: 0.72rem; padding: 0.28rem 0.56rem; background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; border-radius: 6px; font-weight: 500; display: inline-flex; align-items: center; gap: 0.2rem; text-decoration: none;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <span style="font-size: 0.62rem; padding: 0.16rem 0.42rem; background: #f3f4f6; color: #374151; border-radius: 999px; font-weight: 600;">${inv.ti_number ? 'Tax Invoice' : 'Proforma Invoice'}</span>
                                <span class="status-pill ${statusClass}" style="font-size: 0.65rem; padding: 0.12rem 0.4rem;">${statusLabel}</span>
                                <span style="font-size: 0.75rem; font-weight: 600; color: #111827;">${inv.amount || '-'}</span>
                            </span>
                        </summary>
                        <div class="accordion-content" style="padding: 0.5rem 1rem 0.75rem; background: #fbfcfe; border-top: 1px solid #e5e7eb;">
                            <div style="padding: 0.2rem 0;" class="items-display">
                                ${itemsHtml}
                            </div>
                        </div>
                    </details>
                `;
            }).join('');
        } catch (err) {
            accordion.innerHTML = '';
            noMsg.style.display = 'block';
        }
    }

    btnNext.addEventListener('click', function() {
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
