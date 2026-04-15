<!-- Step 4: Preview & Terms (For Orders & Renewal, and Without Orders Step 3) -->
<div id="step4" class="invoice-step">
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <button type="button" id="btnBackToPrev" class="secondary-button" style="padding: 0.5rem 1rem;">&larr; Back</button>
        <h4 style="margin: 0; font-size: 1.1rem; color: #334155;">Final Review</h4>
    </div>

    <input type="hidden" name="clientid" value="{{ request('clientid') }}">
    <input type="hidden" name="invoice_for" value="{{ request('invoice_for') }}">
    <input type="hidden" name="orderid" value="{{ request('orderid', '') }}">
    <input type="hidden" name="proformaid" id="proformaid" value="">
    <input type="hidden" name="renewed_item_ids" id="renewed_item_ids" value="">
    <input type="hidden" name="invoice_number" value="{{ $nextInvoiceNumber }}">
    <input type="hidden" name="issue_date" id="issue_date" value="{{ date('Y-m-d') }}">
    <input type="hidden" name="due_date" id="due_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}">
    <input type="hidden" name="subtotal" id="subtotal" value="0.00">
    <input type="hidden" name="tax_total" id="tax_total" value="0.00">
    <input type="hidden" name="grand_total" id="grand_total" value="0.00">
    <input type="hidden" name="items_data" id="items_data" value="">
    <input type="hidden" name="currency_code" id="currency_code" value="INR">
    <input type="hidden" name="notes" id="notes" value="">

    <!-- Proforma Invoice Preview -->
    <div class="panel-card" style="padding: 0; border: 1px solid #e2e8f0; overflow: hidden; background: #fff; margin-bottom: 1.5rem;">
        <div style="background: #f8fafc; padding: 0.75rem 1.25rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h5 style="margin: 0; font-size: 0.95rem; color: #1e293b;">
                <i class="fas fa-file-pdf" style="color: #ef4444; margin-right: 0.5rem;"></i>
                Proforma Invoice Preview
            </h5>
            <span style="font-size: 0.75rem; color: #64748b; font-weight: 500;">
                <i class="fas fa-circle" style="color: #f59e0b; font-size: 0.5rem; margin-right: 0.3rem;"></i>
                Live Preview
            </span>
        </div>
        <div id="invoicePreviewContainer" style="padding: 2rem; background: #94a3b8; max-height: 750px; overflow-y: auto;">
            <div id="previewContent" style="background: white; padding: 2.5rem; width: 100%; min-height: 842px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border-radius: 4px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1e293b;">
                <div style="text-align: center; color: #64748b; padding-top: 100px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Generating preview...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms & Conditions Below -->
    <div class="panel-card" style="padding: 1rem; border: 1px solid #e2e8f0; background: #fff;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; padding-bottom: 0.4rem; border-bottom: 1px solid #e2e8f0;">
            <h5 style="margin: 0; font-size: 0.9rem; color: #1e293b;">Terms & Conditions</h5>
            <button type="button" id="btnAddTC" class="text-link" style="font-size: 0.75rem; font-weight: 600;">+ Add</button>
        </div>
        <div id="termsList" style="max-height: 400px; overflow-y: auto; padding-right: 0.25rem;">
            @foreach($billingTerms as $term)
            <div style="margin-bottom: 0.4rem; padding: 0.5rem; border-radius: 6px; border: 1px solid #e2e8f0; background: #f8fafc; transition: all 0.2s;" class="term-item-row">
                <label class="custom-checkbox" style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" class="term-checkbox" data-tc-id="{{ $term->tc_id }}" data-content="{{ $term->content }}" value="{{ $term->content }}" style="margin-top: 0.15rem; width: 14px; height: 14px; cursor: pointer; flex-shrink: 0;">
                    <div style="flex: 1;">
                        <p style="margin: 0; font-size: 0.78rem; color: #475569; line-height: 1.4;">{{ $term->content }}</p>
                    </div>
                </label>
            </div>
            @endforeach
        </div>
    </div>

    <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="primary-button create-submit-btn" id="finalSubmitBtn" disabled style="padding: 1rem 4rem; font-size: 1.1rem; box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.4);">
            <i class="fas fa-file-invoice" style="margin-right: 0.5rem;"></i>Create Proforma Invoice
        </button>
    </div>
</div>

<script>
(function() {
    const clientId = "{{ request('clientid') }}";
    const invoiceFor = "{{ request('invoice_for') }}";
    const btnBackToPrev = document.getElementById('btnBackToPrev');
    const finalSubmitBtn = document.getElementById('finalSubmitBtn');
    const previewContent = document.getElementById('previewContent');
    const termsList = document.getElementById('termsList');
    const itemsDataInput = document.getElementById('items_data');

    @php
        $accountDataArr = [
            'name' => optional($account)->name,
            'logo' => ($account && $account->logo_path) ? asset($account->logo_path) : null,
            'billing' => [
                'name' => optional($accountBillingDetail)->billing_name ?? optional($account)->name,
                'address' => optional($accountBillingDetail)->address ?? '',
                'city' => optional($accountBillingDetail)->city ?? '',
                'state' => optional($accountBillingDetail)->state ?? '',
                'postal_code' => optional($accountBillingDetail)->postal_code ?? '',
                'country' => optional($accountBillingDetail)->country ?? '',
                'gstin' => optional($accountBillingDetail)->gstin ?? '',
                'signatory' => optional($accountBillingDetail)->authorize_signatory ?? '',
                'signature' => (optional($accountBillingDetail)->signature_upload) ? asset($accountBillingDetail->signature_upload) : null,
            ]
        ];
    @endphp
    const accountData = {!! json_encode($accountDataArr) !!};

    let invoiceItems = [];

    // Load items
    function loadItems() {
        fetch("{{ route('invoices.get-draft', ['clientid' => '__CLIENTID__']) }}".replace('__CLIENTID__', clientId))
        .then(response => response.json())
        .then(data => {
            if (data.draft && data.draft.items) {
                invoiceItems = data.draft.items;
                itemsDataInput.value = JSON.stringify(invoiceItems);
                updateTotals();
                updateInvoicePreview();
            }
        })
        .catch(() => {
            console.error('Failed to load draft items');
        });
    }

    function updateTotals() {
        let subtotal = 0, taxTotal = 0;
        invoiceItems.forEach(item => {
            subtotal += parseFloat(item.line_total || 0);
            taxTotal += (parseFloat(item.line_total || 0) * (parseFloat(item.tax_rate || 0) / 100));
        });

        document.getElementById('subtotal').value = subtotal.toFixed(2);
        document.getElementById('tax_total').value = taxTotal.toFixed(2);
        document.getElementById('grand_total').value = (subtotal + taxTotal).toFixed(2);
    }

    function updateInvoicePreview() {
        const invoiceNumber = "{{ $nextInvoiceNumber }}";
        const issueDate = document.getElementById('issue_date').value;
        const dueDate = document.getElementById('due_date').value;
        const currency = 'INR';

        // Get terms
        const terms = Array.from(document.querySelectorAll('.term-checkbox'))
            .filter(cb => cb.checked)
            .map(cb => cb.value.replace(/\n/g, '<br>'))
            .join('<br><br>');

        // Build items table
        let itemsHtml = '';
        invoiceItems.forEach((item, index) => {
            itemsHtml += `
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 0.75rem 0.5rem; border-right: 1px solid #e5e7eb;">${index + 1}</td>
                    <td style="padding: 0.75rem 0.5rem; border-right: 1px solid #e5e7eb;"><strong>${item.item_name}</strong></td>
                    <td style="padding: 0.75rem 0.5rem; text-align: center; border-right: 1px solid #e5e7eb;">${item.quantity}</td>
                    <td style="padding: 0.75rem 0.5rem; text-align: right; border-right: 1px solid #e5e7eb;">${currency} ${parseFloat(item.unit_price).toLocaleString()}</td>
                    <td style="padding: 0.75rem 0.5rem; text-align: right; font-weight: 600;">${currency} ${parseFloat(item.line_total).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                </tr>
            `;
        });

        let subtotal = 0, taxTotal = 0, grandTotal = 0;
        invoiceItems.forEach(item => {
            subtotal += parseFloat(item.line_total || 0);
            taxTotal += (parseFloat(item.line_total || 0) * (parseFloat(item.tax_rate || 0) / 100));
        });
        grandTotal = subtotal + taxTotal;

        previewContent.innerHTML = `
            <div style="display: flex; justify-content: space-between; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 2px solid #f59e0b;">
                <div>
                    <h1 style="margin: 0 0 0.5rem 0; font-size: 2rem; color: #f59e0b;">PROFORMA INVOICE</h1>
                    <p style="margin: 0.25rem 0; font-size: 0.9rem;"><strong>Invoice #:</strong> ${invoiceNumber}</p>
                    <p style="margin: 0.25rem 0; font-size: 0.9rem;"><strong>Date:</strong> ${issueDate}</p>
                    <p style="margin: 0.25rem 0; font-size: 0.9rem;"><strong>Due Date:</strong> ${dueDate}</p>
                </div>
                <div style="text-align: right;">
                    ${accountData.logo ? `<img src="${accountData.logo}" style="max-width: 150px; max-height: 80px; margin-bottom: 1rem;">` : ''}
                    <h3 style="margin: 0.5rem 0; color: #1e293b;">${accountData.name || 'Company Name'}</h3>
                </div>
            </div>
            
            <div style="margin-bottom: 2rem;">
                <h3 style="color: #f59e0b; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">Bill To:</h3>
                <p style="margin: 0.25rem 0; font-size: 0.9rem;"><strong>[Client Name]</strong></p>
            </div>
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                <thead style="background: #f59e0b; color: white;">
                    <tr>
                        <th style="padding: 0.75rem 0.5rem; text-align: left;">#</th>
                        <th style="padding: 0.75rem 0.5rem; text-align: left;">Description</th>
                        <th style="padding: 0.75rem 0.5rem; text-align: center;">Qty</th>
                        <th style="padding: 0.75rem 0.5rem; text-align: right;">Unit Price</th>
                        <th style="padding: 0.75rem 0.5rem; text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>${itemsHtml}</tbody>
            </table>
            
            <div style="display: flex; justify-content: flex-end;">
                <div style="min-width: 280px;">
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;">
                        <span>Subtotal:</span><strong>${currency} ${subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;">
                        <span>Tax:</span><strong>${currency} ${taxTotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; font-size: 1.2rem; font-weight: 700; color: #f59e0b;">
                        <span>Grand Total:</span><span>${currency} ${grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}</span>
                    </div>
                </div>
            </div>
            
            ${terms ? `<div style="margin-top: 2rem; padding: 1rem; background: #f8fafc; border-radius: 8px;"><h4 style="margin: 0 0 0.5rem 0;">Terms & Conditions:</h4><p style="font-size: 0.85rem; line-height: 1.6;">${terms}</p></div>` : ''}
        `;
    }

    // Terms checkboxes
    document.getElementById('termsList').addEventListener('change', (e) => {
        if (e.target.classList.contains('term-checkbox')) {
            updateInvoicePreview();
            const allCheckboxes = document.querySelectorAll('.term-checkbox');
            const anyChecked = Array.from(allCheckboxes).some(cb => cb.checked);
            finalSubmitBtn.disabled = !anyChecked;
        }
    });

    // Back button
    btnBackToPrev.addEventListener('click', function() {
        const prevStep = invoiceFor === 'without_orders' ? 2 : 3;
        window.location.href = "{{ route('invoices.create') }}?step=" + prevStep + "&invoice_for=" + invoiceFor + "&clientid=" + clientId;
    });

    // Initialize
    loadItems();
})();
</script>
