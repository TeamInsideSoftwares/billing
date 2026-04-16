<!-- Step 2: Select Renewal Items -->
<div id="step2" class="invoice-step">
    <div class="invoice-step-toolbar">
        <button type="button" id="btnBackToStep1" class="secondary-button" style="padding: 0.5rem 1rem;">&larr; Back</button>
        <div class="invoice-side-meta">
            <span class="invoice-meta-label">PI</span>
            <strong class="invoice-meta-value">{{ $nextInvoiceNumber }}</strong>
        </div>
    </div>

    <input type="hidden" name="clientid" value="{{ request('clientid') }}">
    <input type="hidden" name="proformaid" id="proformaid" value="">
    <input type="hidden" name="renewed_item_ids" id="renewed_item_ids" value="">
    <input type="hidden" name="items_data" id="items_data" value="">

    <div id="renewalSection" class="workflow-panel">
        <div class="panel-heading-row" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h4 style="margin: 0; font-size: 1rem; color: #111827;">Renewal Candidates</h4>
                <p style="margin: 0.2rem 0 0 0; color: #6b7280; font-size: 0.85rem;">Select items from previous invoices to renew.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <label style="font-size: 0.82rem; color: #6b7280; font-weight: 500;">Show upcoming:</label>
                <select id="renewalDaysFilter" class="form-input" style="width: auto; min-width: 160px;">
                    <option value="1" selected>Tomorrow</option>
                    <option value="7">Next 7 Days</option>
                    <option value="14">Next 14 Days</option>
                    <option value="30">Next 30 Days</option>
                    <option value="60">Next 60 Days</option>
                    <option value="90">Next 90 Days</option>
                </select>
            </div>
        </div>
        <div class="table-shell">
            <table class="data-table" id="renewalTable" style="font-size: 0.85rem; margin: 0;">
                <thead>
                    <tr>
                        <th>Select</th>
                        <th>Invoice #</th>
                        <th>Expired Items</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody id="renewalBody"></tbody>
            </table>
            <div id="noRenewalMessage" class="empty-state" style="display: none;">No renewal-ready invoices were found for this client.</div>
        </div>
    </div>

    <div style="margin-top: 2rem;">
        <button type="button" class="primary-button" id="btnNextToStep3" disabled style="width: 100%; padding: 1rem;">Continue to Edit Items &rarr;</button>
    </div>
</div>

<script>
(function() {
    const clientId = "{{ request('clientid') }}";
    const renewalBody = document.getElementById('renewalBody');
    const noRenewalMessage = document.getElementById('noRenewalMessage');
    const btnNextToStep3 = document.getElementById('btnNextToStep3');
    const btnBackToStep1 = document.getElementById('btnBackToStep1');
    const itemsDataInput = document.getElementById('items_data');
    const renewedItemIdsInput = document.getElementById('renewed_item_ids');
    const renewalDaysFilter = document.getElementById('renewalDaysFilter');

    let selectedRenewalItems = [];

    function loadRenewals() {
        const days = renewalDaysFilter.value;
        fetch("{{ route('invoices.renewal-invoices') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ clientid: clientId, days: days })
        })
        .then(response => response.json())
        .then(invoices => {
            if (invoices.length === 0) {
                noRenewalMessage.style.display = 'block';
                return;
            }

            renewalBody.innerHTML = '';
            invoices.forEach(invoice => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td style="text-align: center;">
                        <label class="custom-checkbox">
                            <input type="checkbox" class="renewal-checkbox" data-invoice-id="${invoice.proformaid}" data-items='${JSON.stringify(invoice.items || [])}' 
                            style="cursor: pointer;">
                        </label>
                    </td>

                    <td>${invoice.invoice_number}</td>
                    <td>${invoice.expired_items} expired, ${invoice.upcoming_items} upcoming</td>
                    <td>${invoice.currency} ${Number(invoice.grand_total).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                `;
                renewalBody.appendChild(row);
            });

            // Add event listeners
            document.querySelectorAll('.renewal-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateSelectedItems();
                });
            });
        })
        .catch(error => {
            console.error('Error loading renewals:', error);
            noRenewalMessage.style.display = 'block';
        });
    }

    function updateSelectedItems() {
        selectedRenewalItems = [];
        const renewedIds = [];

        document.querySelectorAll('.renewal-checkbox:checked').forEach(checkbox => {
            const invoiceId = checkbox.dataset.invoiceId;
            const items = JSON.parse(checkbox.dataset.items);
            
            items.forEach(item => {
                if (item.is_expired || item.is_upcoming) {
                    selectedRenewalItems.push({
                        ...item,
                        renewed_from_proformaitemid: item.proformaitemid
                    });
                    renewedIds.push(item.proformaitemid);
                }
            });
        });

        itemsDataInput.value = JSON.stringify(selectedRenewalItems);
        renewedItemIdsInput.value = JSON.stringify(renewedIds);
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
            window.location.href = "{{ route('invoices.create') }}?step=3&invoice_for=renewal&clientid=" + clientId;
        });
    });

    btnBackToStep1.addEventListener('click', function() {
        window.location.href = "{{ route('invoices.create') }}?step=1&clientid=" + clientId;
    });

    renewalDaysFilter.addEventListener('change', loadRenewals);

    // Initialize
    loadRenewals();
})();
</script>
