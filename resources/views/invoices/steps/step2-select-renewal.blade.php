<!-- Step 2: Select Renewal Items -->
<div id="step2" class="invoice-step">
    <div class="invoice-step-toolbar">
        <button type="button" id="btnBackToStep1" class="secondary-button" style="padding: 0.5rem 1rem;">&larr; Back</button>
        <div class="invoice-side-meta">
            <span class="invoice-meta-label">PI</span>
            <strong class="invoice-meta-value">{{ $nextInvoiceNumber }}</strong>
        </div>
    </div>

    <input type="hidden" name="clientid" value="{{ request('clientid', request('c')) }}">
    <input type="hidden" name="proformaid" id="proformaid" value="">
    <input type="hidden" name="renewed_item_ids" id="renewed_item_ids" value="">
    <input type="hidden" name="items_data" id="items_data" value="">

    <div id="renewalSection" class="workflow-panel">
        <div class="panel-heading-row" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h4 style="margin: 0; font-size: 1rem; color: #111827;">Renewal Candidates</h4>
                <p style="margin: 0.2rem 0 0 0; color: #6b7280; font-size: 0.85rem;">Default shows items due till today. Enter days (e.g. 70, 100) to include upcoming renewals.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 0.65rem;">
                <label for="renewalDaysFilter" style="font-size: 0.82rem; color: #6b7280; font-weight: 500;">Upcoming days:</label>
                <input type="number" id="renewalDaysFilter" class="form-input" min="0" step="1" value="" placeholder="0" style="width: 140px; max-width: 100%;">
            </div>
        </div>
        <div class="table-shell">
            <table class="data-table" id="renewalTable" style="font-size: 0.85rem; margin: 0;">
                <thead>
                    <tr>
                        <th>Select</th>
                        <th>Invoice #</th>
                        <th>Item</th>
                        <th>Renewal Window</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody id="renewalBody"></tbody>
            </table>
            <div id="noRenewalMessage" class="empty-state" style="display: none;">No renewal-ready items were found for this client.</div>
        </div>
    </div>

    <div style="margin-top: 2rem;">
        <button type="button" class="primary-button" id="btnNextToStep3" disabled style="width: 100%; padding: 1rem;">Continue to Edit Items &rarr;</button>
    </div>
</div>

<script>
(function() {
    const clientId = "{{ request('clientid', request('c')) }}";
    const renewalBody = document.getElementById('renewalBody');
    const noRenewalMessage = document.getElementById('noRenewalMessage');
    const btnNextToStep3 = document.getElementById('btnNextToStep3');
    const btnBackToStep1 = document.getElementById('btnBackToStep1');
    const itemsDataInput = document.getElementById('items_data');
    const renewedItemIdsInput = document.getElementById('renewed_item_ids');
    const renewalDaysFilter = document.getElementById('renewalDaysFilter');
    const renewalItemStore = new Map();

    let selectedRenewalItems = [];
    let latestRenewalRequestId = 0;

    function loadRenewals() {
        const requestId = ++latestRenewalRequestId;
        const rawDays = renewalDaysFilter ? renewalDaysFilter.value.trim() : '';
        const parsedDays = rawDays === '' ? 0 : Math.max(0, parseInt(rawDays, 10) || 0);
        renewalBody.innerHTML = '';
        noRenewalMessage.style.display = 'none';
        noRenewalMessage.textContent = 'No renewal-ready items were found for this client.';
        selectedRenewalItems = [];
        renewalItemStore.clear();
        itemsDataInput.value = '[]';
        renewedItemIdsInput.value = '[]';
        btnNextToStep3.disabled = true;

        fetch("{{ route('invoices.renewal-invoices') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ clientid: clientId, days: parsedDays })
        })
        .then(response => response.json())
        .then(invoices => {
            // Ignore stale responses from older requests (typing quickly can trigger races).
            if (requestId !== latestRenewalRequestId) {
                return;
            }

            if (!Array.isArray(invoices) || invoices.length === 0) {
                noRenewalMessage.style.display = 'block';
                return;
            }

            let totalRows = 0;
            const renderedItemKeys = new Set();

            invoices.forEach(invoice => {
                const items = Array.isArray(invoice.items) ? invoice.items : [];

                items.forEach(item => {
                    if (!item || (!item.is_expired && !item.is_upcoming)) {
                        return;
                    }

                    const itemKey = `${invoice.proformaid}::${item.proformaitemid}`;
                    if (renderedItemKeys.has(itemKey)) {
                        return;
                    }
                    renderedItemKeys.add(itemKey);

                    renewalItemStore.set(itemKey, {
                        ...item,
                        source_invoice_id: invoice.proformaid,
                        source_invoice_number: invoice.invoice_number,
                    });

                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td style="text-align: center;">
                            <label class="custom-checkbox">
                                <input type="checkbox" class="renewal-item-checkbox" data-item-key="${itemKey}" style="cursor: pointer;">
                            </label>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.45rem;">
                                <span>${invoice.invoice_number || '-'}</span>
                                <a href="{{ url('invoices') }}/${invoice.proformaid}/edit"
                                   style="font-size: 0.72rem; padding: 0.26rem 0.52rem; background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; border-radius: 6px; font-weight: 500; display: inline-flex; align-items: center; gap: 0.2rem; text-decoration: none;"
                                   title="Edit source invoice">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #111827;">${item.item_name || 'Item'}</div>
                            <div style="font-size: 0.78rem; color: #6b7280; margin-top: 0.1rem;">
                                Qty ${Math.max(1, Math.round(Number(item.quantity || 1)))}${item.frequency ? ` • ${item.frequency}` : ''}
                            </div>
                        </td>
                        <td>
                            <span class="status-pill ${item.is_expired ? 'unpaid' : 'partially-paid'}" style="font-size: 0.65rem; padding: 0.12rem 0.45rem;">
                                ${item.is_expired ? 'Expired' : 'Upcoming'}
                            </span>
                            <div style="font-size: 0.78rem; color: #6b7280; margin-top: 0.15rem;">Ends: ${item.end_date || '-'}</div>
                        </td>
                        <td style="text-align: right; font-weight: 600;">
                            ${(invoice.currency || 'INR')} ${Number(item.line_total || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                        </td>
                    `;
                    renewalBody.appendChild(row);
                    totalRows++;
                });
            });

            if (totalRows === 0) {
                noRenewalMessage.style.display = 'block';
                return;
            }

            document.querySelectorAll('.renewal-item-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateSelectedItems();
                });
            });
        })
        .catch(error => {
            if (requestId !== latestRenewalRequestId) {
                return;
            }
            console.error('Error loading renewals:', error);
            noRenewalMessage.textContent = 'No renewal-ready items were found for this client.';
            noRenewalMessage.style.display = 'block';
        });
    }

    function updateSelectedItems() {
        selectedRenewalItems = [];
        const renewedIds = new Set();

        document.querySelectorAll('.renewal-item-checkbox:checked').forEach(checkbox => {
            const selectedItem = renewalItemStore.get(checkbox.dataset.itemKey);
            if (!selectedItem) {
                return;
            }

            selectedRenewalItems.push({
                ...selectedItem,
                renewed_from_proformaitemid: selectedItem.proformaitemid
            });
            renewedIds.add(selectedItem.proformaitemid);
        });

        itemsDataInput.value = JSON.stringify(selectedRenewalItems);
        renewedItemIdsInput.value = JSON.stringify(Array.from(renewedIds));
        btnNextToStep3.disabled = selectedRenewalItems.length === 0;
    }

    btnNextToStep3.addEventListener('click', function() {
        if (selectedRenewalItems.length === 0) {
            alert('Please select at least one item to renew.');
            return;
        }

        fetch("{{ route('invoices.save-draft') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                clientid: clientId,
                invoice_for: 'renewal',
                items_data: itemsDataInput.value,
                renewed_item_ids: renewedItemIdsInput.value
            })
        })
        .then(response => response.json())
        .then(() => {
            window.location.href = "{{ route('invoices.create') }}?step=3&invoice_for=renewal&c=" + clientId;
        });
    });

    btnBackToStep1.addEventListener('click', function() {
        window.location.href = "{{ route('invoices.create') }}?step=1&c=" + clientId;
    });

    renewalDaysFilter.addEventListener('input', loadRenewals);

    // Initialize
    loadRenewals();
})();
</script>
