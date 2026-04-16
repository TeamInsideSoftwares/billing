@php($selectedClientCurrency = optional($clients->firstWhere('clientid', request('clientid')))->currency ?? 'INR')
<!-- Step 4: Preview & Terms (For Orders & Renewal, and Without Orders Step 3) -->
<div id="step4" class="invoice-step">
    <div class="invoice-step-toolbar">
        <button type="button" id="btnBackToPrev" class="secondary-button" style="padding: 0.5rem 1rem;">&larr; Back</button>
        <div class="invoice-side-meta">
            <span class="invoice-meta-label">PI</span>
            <strong class="invoice-meta-value">{{ $nextInvoiceNumber }}</strong>
        </div>
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
    <input type="hidden" name="discount_total" id="discount_total" value="0.00">
    <input type="hidden" name="grand_total" id="grand_total" value="0.00">
    <input type="hidden" name="items_data" id="items_data" value="">
    <input type="hidden" name="currency_code" id="currency_code" value="{{ $selectedClientCurrency }}">
    <input type="hidden" name="notes" id="notes" value="">

    <!-- PI Preview -->
    <div class="panel-card" style="padding: 0; border: 1px solid #e5e7eb; overflow: hidden; background: #fff; margin-bottom: 1.5rem;">
        <div style="background: #f9fafb; padding: 0.85rem 1.25rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <h5 style="margin: 0; font-size: 0.95rem; color: #111827;">
                <i class="fas fa-file-pdf" style="color: #4f46e5; margin-right: 0.5rem;"></i>
                PI Preview
            </h5>
            <span style="font-size: 0.75rem; color: #6b7280; font-weight: 500;">
                <i class="fas fa-circle" style="color: #4f46e5; font-size: 0.5rem; margin-right: 0.3rem;"></i>
                Live Preview
            </span>
        </div>
        <div id="invoicePreviewContainer" style="padding: 1.5rem; background: #f3f4f6; max-height: 750px; overflow-y: auto;">
            <div id="previewContent" style="background: white; padding: 2.5rem; width: 100%; min-height: 842px; border: 1px solid #e5e7eb; border-radius: 8px; font-family: 'Inter', sans-serif; color: #1e293b;">
                <div style="text-align: center; color: #6b7280; padding-top: 100px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Generating preview...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms & Conditions Below -->
    <div class="panel-card" style="padding: 1rem; border: 1px solid #e5e7eb; background: #fff;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; padding-bottom: 0.4rem; border-bottom: 1px solid #e5e7eb;">
            <h5 style="margin: 0; font-size: 0.9rem; color: #111827;">Terms & Conditions</h5>
            <button type="button" id="btnAddTC" class="text-link" style="font-size: 0.75rem; font-weight: 600;">+ Add</button>
        </div>
        <div id="termsList" style="max-height: 400px; overflow-y: auto; padding-right: 0.25rem;">
            @foreach($billingTerms as $term)
            <div style="margin-bottom: 0.4rem; padding: 0.65rem; border-radius: 8px; border: 1px solid #e5e7eb; background: #f9fafb; transition: all 0.2s;" class="term-item-row">
                <label class="custom-checkbox" style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" class="term-checkbox" data-tc-id="{{ $term->tc_id }}" data-content="{{ $term->content }}" value="{{ $term->content }}" style="margin-top: 0.15rem; width: 14px; height: 14px; cursor: pointer; flex-shrink: 0;">
                    <div style="flex: 1;">
                        <p style="margin: 0; font-size: 0.78rem; color: #4b5563; line-height: 1.4;">{{ $term->content }}</p>
                    </div>
                </label>
            </div>
            @endforeach
        </div>
    </div>

    <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="primary-button create-submit-btn" id="finalSubmitBtn" disabled style="padding: 1rem 4rem; font-size: 1.1rem;">
            <i class="fas fa-file-invoice" style="margin-right: 0.5rem;"></i>Create Proforma Invoice
        </button>
    </div>
</div>

<script>
(function() {
    const clientId = "{{ request('clientid') }}";
    const invoiceFor = "{{ request('invoice_for') }}";
    const orderId = "{{ request('orderid', '') }}";
    const btnBackToPrev = document.getElementById('btnBackToPrev');
    const finalSubmitBtn = document.getElementById('finalSubmitBtn');
    const previewContent = document.getElementById('previewContent');
    const termsList = document.getElementById('termsList');
    const itemsDataInput = document.getElementById('items_data');
    const currencyCodeInput = document.getElementById('currency_code');

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

    function getCurrencyCode() {
        return currencyCodeInput.value || '{{ $selectedClientCurrency }}';
    }

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
        let subtotal = 0, taxTotal = 0, discountTotal = 0;
        invoiceItems.forEach(item => {
            const lineTotal = parseFloat(item.line_total || 0);
            const lineDiscount = parseFloat(item.discount_amount || 0);
            subtotal += lineTotal;
            discountTotal += lineDiscount;
            taxTotal += (Math.max(0, lineTotal - lineDiscount) * (parseFloat(item.tax_rate || 0) / 100));
        });

        document.getElementById('subtotal').value = subtotal.toFixed(2);
        document.getElementById('discount_total').value = discountTotal.toFixed(2);
        document.getElementById('tax_total').value = taxTotal.toFixed(2);
        document.getElementById('grand_total').value = (subtotal - discountTotal + taxTotal).toFixed(2);
    }

    function updateInvoicePreview() {
        const invoiceNumber = "{{ $nextInvoiceNumber }}";
        const issueDate = document.getElementById('issue_date').value;
        const dueDate = document.getElementById('due_date').value;
        const currency = getCurrencyCode();

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
                    <td style="padding: 0.85rem 0.75rem;">${index + 1}</td>
                    <td style="padding: 0.85rem 0.75rem;"><strong>${item.item_name}</strong></td>
                    <td style="padding: 0.85rem 0.75rem; text-align: center;">${item.quantity}</td>
                    <td style="padding: 0.85rem 0.75rem; text-align: right;">${currency} ${parseFloat(item.unit_price).toLocaleString()}</td>
                    <td style="padding: 0.85rem 0.75rem; text-align: right; font-weight: 600;">${currency} ${Math.max(0, parseFloat(item.line_total || 0) - parseFloat(item.discount_amount || 0)).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                </tr>
            `;
        });

        let subtotal = 0, taxTotal = 0, discountTotal = 0, grandTotal = 0;
        invoiceItems.forEach(item => {
            const lineTotal = parseFloat(item.line_total || 0);
            const lineDiscount = parseFloat(item.discount_amount || 0);
            subtotal += lineTotal;
            discountTotal += lineDiscount;
            taxTotal += (Math.max(0, lineTotal - lineDiscount) * (parseFloat(item.tax_rate || 0) / 100));
        });
        grandTotal = subtotal - discountTotal + taxTotal;

        previewContent.innerHTML = `
            <div style="display: flex; justify-content: space-between; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                <div>
                    <h1 style="margin: 0 0 0.5rem 0; font-size: 2rem; color: #111827;">PROFORMA INVOICE</h1>
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
                <h3 style="color: #111827; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem;">Bill To:</h3>
                <p style="margin: 0.25rem 0; font-size: 0.9rem;"><strong>[Client Name]</strong></p>
            </div>
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                <thead style="background: #f9fafb; color: #374151;">
                    <tr>
                        <th style="padding: 0.85rem 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb;">#</th>
                        <th style="padding: 0.85rem 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb;">Description</th>
                        <th style="padding: 0.85rem 0.75rem; text-align: center; border-bottom: 1px solid #e5e7eb;">Qty</th>
                        <th style="padding: 0.85rem 0.75rem; text-align: right; border-bottom: 1px solid #e5e7eb;">Unit Price</th>
                        <th style="padding: 0.85rem 0.75rem; text-align: right; border-bottom: 1px solid #e5e7eb;">Total</th>
                    </tr>
                </thead>
                <tbody>${itemsHtml}</tbody>
            </table>
            
            <div style="display: flex; justify-content: flex-end;">
                <div style="min-width: 280px;">
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;">
                        <span>Subtotal:</span><strong>${currency} ${subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb; color: #dc2626;">
                        <span>Discount:</span><strong>-${currency} ${discountTotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;">
                        <span>Tax:</span><strong>${currency} ${taxTotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; font-size: 1.2rem; font-weight: 700; color: #111827;">
                        <span>Grand Total:</span><span>${currency} ${grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}</span>
                    </div>
                </div>
            </div>
            
            ${terms ? `<div style="margin-top: 2rem; padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;"><h4 style="margin: 0 0 0.5rem 0;">Terms & Conditions:</h4><p style="font-size: 0.85rem; line-height: 1.6;">${terms}</p></div>` : ''}
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
        let prevUrl = "{{ route('invoices.create') }}?step=" + prevStep + "&invoice_for=" + invoiceFor + "&clientid=" + clientId;
        if (orderId) {
            prevUrl += "&orderid=" + orderId;
        }
        window.location.href = prevUrl;
    });

    // Initialize
    loadItems();
})();
</script>
