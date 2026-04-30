@php
    $selectedClient = $clients->firstWhere('clientid', request('c', request('clientid')));
    $selectedClientName = $selectedClient ? ($selectedClient->business_name ?? $selectedClient->contact_name ?? 'Unknown Client') : 'No Client Selected';
    $selectedClientEmail = $selectedClient->email ?? '';
@endphp
<!-- Step 2: Select Renewal Items -->
<div id="step2" class="invoice-step">
    {{-- Client Info Header with Back Button --}}
    <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <button type="button" id="btnBackToStep1" class="secondary-button" style="padding: 0.4rem 0.65rem; flex-shrink: 0; font-size: 0.85rem;">
                <i class="fas fa-arrow-left" class="text-sm"></i>
            </button>
            <div style="width: 1px; height: 32px; background: #d1d5db; flex-shrink: 0;"></div>
            <div style="width: 36px; height: 36px; border-radius: 8px; background: #e0e7ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas fa-user"></i>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 0.9rem; font-weight: 600; color: #111827; margin-top: 0.1rem;">{{ $selectedClientName }}</div>
                @if($selectedClientEmail)
                <div style="font-size: 0.78rem; color: #64748b; margin-top: 0.05rem;">{{ $selectedClientEmail }}</div>
                @endif
            </div>
        </div>
    </div>

    <input type="hidden" name="clientid" value="{{ request('c', request('clientid')) }}">
    <input type="hidden" name="invoiceid" id="invoiceid" value="{{ request('d', '') }}">
    <input type="hidden" name="renewed_item_ids" id="renewed_item_ids" value="">
    <input type="hidden" name="items_data" id="items_data" value="">
    <input type="hidden" name="pi_number" id="pi_number" value="{{ $invoice?->pi_number ?? $nextInvoiceNumber }}">
    <input type="hidden" name="issue_date" id="step2_select_renewal_issue_date" value="{{ date('Y-m-d') }}">
    <input type="hidden" name="due_date" id="step2_select_renewal_due_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}">
    <input type="hidden" name="notes" id="step2_select_renewal_notes" value="">

    <div id="renewalSection" class="workflow-panel">
        <div class="panel-heading-row" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h4 style="margin: 0; font-size: 1rem; color: #111827;">Renewal Candidates</h4>
                <p style="margin: 0.2rem 0 0 0; color: #6b7280; font-size: 0.85rem;">Showing only expired recurring items from tax invoices</p>
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
                        <th class="text-right">Amount ({{ $selectedClientCurrency }})</th>
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
    const clientId = "{{ request('c', request('clientid')) }}";
    const renewalBody = document.getElementById('renewalBody');
    const noRenewalMessage = document.getElementById('noRenewalMessage');
    const btnNextToStep3 = document.getElementById('btnNextToStep3');
    const btnBackToStep1 = document.getElementById('btnBackToStep1');
    const itemsDataInput = document.getElementById('items_data');
    const renewedItemIdsInput = document.getElementById('renewed_item_ids');
    const renewalItemStore = new Map();

    let selectedRenewalItems = [];
    let latestRenewalRequestId = 0;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function loadRenewals() {
        const requestId = ++latestRenewalRequestId;
        renewalBody.innerHTML = '';
        noRenewalMessage.style.display = 'none';
        noRenewalMessage.textContent = 'No expired renewal items found from tax invoices for this client.';
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
            body: JSON.stringify({ clientid: clientId })
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
                    if (!item || !item.is_expired) {
                        return;
                    }

                    const itemKey = `${invoice.invoiceid}::${item.invoice_itemid}`;
                    if (renderedItemKeys.has(itemKey)) {
                        return;
                    }
                    renderedItemKeys.add(itemKey);

                    renewalItemStore.set(itemKey, {
                        ...item,
                        source_invoice_id: invoice.invoiceid,
                        source_invoice_number: invoice.invoice_number,
                    });
                    const itemName = escapeHtml(item.item_name || 'Item');
                    const itemDescription = escapeHtml(item.item_description || '').trim();

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
                                <a href="{{ url('invoices') }}/${invoice.invoiceid}/edit"
                                   style="font-size: 0.72rem; padding: 0.26rem 0.52rem; background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; border-radius: 6px; font-weight: 500; display: inline-flex; align-items: center; gap: 0.2rem; text-decoration: none;"
                                   title="Edit source invoice">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #111827;">${itemName}</div>
                            ${itemDescription ? `<div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.1rem; white-space: pre-wrap;">${itemDescription}</div>` : ''}
                            <div style="font-size: 0.78rem; color: #6b7280; margin-top: 0.1rem;">
                                Qty ${Math.max(1, Math.round(Number(item.quantity || 1)))}${item.frequency ? ` • ${item.frequency}` : ''}
                            </div>
                        </td>
                        <td>
                            <span class="status-pill cancelled" style="font-size: 0.65rem; padding: 0.12rem 0.45rem;">
                                Expired
                            </span>
                            <div style="font-size: 0.78rem; color: #6b7280; margin-top: 0.15rem;">Ends: ${item.end_date || '-'}</div>
                        </td>
                        <td style="text-align: right; font-weight: 600;">
                            ${invoice.currency || 'INR'} ${Math.max(0, Number(item.line_total || 0) - Number(item.discount_amount || ((Number(item.line_total || 0) * Number(item.discount_percent || 0)) / 100) || 0)).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0})}
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
            noRenewalMessage.textContent = 'No expired renewal items found from tax invoices for this client.';
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
                renewed_from_invoice_itemid: selectedItem.invoice_itemid
            });
            renewedIds.add(selectedItem.invoice_itemid);
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

        const issueDateValue = document.getElementById('step2_select_renewal_issue_date')?.value || '';
        const dueDateValue = document.getElementById('step2_select_renewal_due_date')?.value || '';
        const notesValue = document.getElementById('step2_select_renewal_notes')?.value || '';

        fetch("{{ route('invoices.save-draft') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                invoiceid: "{{ request('d', '') }}" || undefined,
                invoice_for: 'renewal',
                clientid: clientId,
                issue_date: issueDateValue,
                due_date: dueDateValue,
                notes: notesValue,
                items_data: JSON.stringify(selectedRenewalItems)
            })
        })
        .then(async (response) => {
            if (!response.ok) {
                const text = await response.text();
                throw new Error(text || 'Failed to save draft.');
            }
            const contentType = response.headers.get('content-type') || '';
            return contentType.includes('application/json') ? response.json() : {};
        })
        .then((data) => {
            const clientToken = encodeURIComponent(clientId);
            let nextUrl = "{{ route('invoices.create') }}?step=3&invoice_for=renewal&c=" + clientToken;
            if (data && data.invoiceid) {
                nextUrl += "&d=" + encodeURIComponent(data.invoiceid);
            }
            window.location.href = nextUrl;
        })
        .catch((error) => {
            console.error('Error saving draft:', error);
            alert('Unable to save draft right now. Please try again.');
        });
    });

    btnBackToStep1.addEventListener('click', function() {
        const clientToken = encodeURIComponent(clientId);
        window.location.href = "{{ route('invoices.create') }}?step=1&c=" + clientToken;
    });

    // Load draft items when editing
    function loadItems() {
        const draftId = "{{ request('d', '') }}";

        if (!draftId) return;

        const draftUrl = new URL("{{ route('invoices.get-draft', ['clientid' => '__CLIENTID__']) }}".replace('__CLIENTID__', clientId), window.location.origin);
        draftUrl.searchParams.set('invoice_for', 'renewal');
        draftUrl.searchParams.set('d', draftId);

        fetch(draftUrl.toString())
            .then(response => response.json())
            .then(data => {
                if (data.draft) {
                    if (data.draft.items && data.draft.items.length > 0) {
                        selectedRenewalItems = data.draft.items;
                        itemsDataInput.value = JSON.stringify(selectedRenewalItems);

                        // Update checkboxes based on loaded items
                        renewalItemStore.forEach((item, key) => {
                            const checkbox = document.querySelector(`.renewal-item-checkbox[data-item-key="${key}"]`);
                            if (checkbox) {
                                const isSelected = selectedRenewalItems.some(si => si.invoice_itemid === item.invoice_itemid);
                                checkbox.checked = isSelected;
                            }
                        });

                        updateSelectedItems();
                    }

                    if (data.draft.issue_date) {
                        const issueDateField = document.getElementById('issue_date');
                        if (issueDateField) {
                            issueDateField.value = data.draft.issue_date;
                        }
                        document.getElementById('step2_select_renewal_issue_date').value = data.draft.issue_date;
                    }
                    if (data.draft.due_date) {
                        const dueDateField = document.getElementById('due_date');
                        if (dueDateField) {
                            dueDateField.value = data.draft.due_date;
                        }
                        document.getElementById('step2_select_renewal_due_date').value = data.draft.due_date;
                    }
                    if (data.draft.notes) {
                        const notesField = document.getElementById('notes');
                        if (notesField) {
                            notesField.value = data.draft.notes;
                        }
                        document.getElementById('step2_select_renewal_notes').value = data.draft.notes;
                    }
                }
            })
            .catch(error => {
                console.error('Failed to load draft items:', error);
            });
    }

    // Initialize
    loadItems();
    loadRenewals();
})();
</script>
