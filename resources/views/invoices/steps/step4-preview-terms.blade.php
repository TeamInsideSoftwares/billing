@php
    $normalizeTaxState = static fn ($value) => preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $value)));
    $selectedInvoiceClient = $clients->firstWhere('clientid', request('clientid', request('c')));
    $selectedClientCurrency = optional($selectedInvoiceClient)->currency ?? 'INR';
    $invoiceClientState = $normalizeTaxState(optional($selectedInvoiceClient)->state ?? '');
    $invoiceAccountState = $normalizeTaxState(optional($account)->state ?? '');
    $sameStateGstForInvoice = $invoiceClientState !== '' && $invoiceAccountState !== '' && $invoiceClientState === $invoiceAccountState;
@endphp
<!-- Step 4: Preview & Terms (For Orders & Renewal, and Without Orders Step 3) -->
<div id="step4" class="invoice-step">
    <div class="invoice-step-toolbar">
        <button type="button" id="btnBackToPrev" class="secondary-button" style="padding: 0.4rem 0.8rem;">&larr; Back</button>
        <div class="invoice-side-meta">
            <span class="invoice-meta-label">PI</span>
            <strong class="invoice-meta-value" id="piNumberBadge">{{ $nextInvoiceNumber }}</strong>
        </div>
    </div>

    <input type="hidden" name="clientid" value="{{ request('clientid', request('c')) }}">
    <input type="hidden" name="invoice_for" value="{{ request('invoice_for') }}">
    <input type="hidden" name="orderid" value="{{ request('orderid', request('o', '')) === '0' ? '' : request('orderid', request('o', '')) }}">
    <input type="hidden" name="proformaid" id="proformaid" value="">
    <input type="hidden" name="renewed_item_ids" id="renewed_item_ids" value="">
    <input type="hidden" name="invoice_number" id="invoice_number" value="{{ $nextInvoiceNumber }}">
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
    <div class="panel-card" style="padding: 0; border: 1px solid #e5e7eb; overflow: hidden; background: #fff; margin-bottom: 0.9rem;">
        <div style="background: #fafafa; padding: 0.7rem 0.9rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <h5 style="margin: 0; font-size: 0.95rem; color: #111827;">
                <i class="fas fa-file-pdf" style="color: #374151; margin-right: 0.5rem;"></i>
                PI Preview
            </h5>
            <span style="font-size: 0.75rem; color: #6b7280; font-weight: 500;">
                <i class="fas fa-circle" style="color: #10b981; font-size: 0.5rem; margin-right: 0.3rem;"></i>
                Live Preview
            </span>
        </div>
        <div id="invoicePreviewContainer" style="padding: 1rem; background: #f5f5f5; max-height: 620px; overflow-y: auto;">
            <div id="previewContent" style="background: white; padding: 1.5rem; width: 100%; min-height: 640px; border: 1px solid #dddddd; border-radius: 8px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1f2937;">
                <div style="text-align: center; color: #6b7280; padding-top: 100px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Generating preview...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms & Conditions Below -->
    <div class="panel-card" style="padding: 0.85rem; border: 1px solid #e5e7eb; background: #fff; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem; padding-bottom: 0.35rem; border-bottom: 1px solid #e5e7eb;">
            <h5 style="margin: 0; font-size: 0.9rem; color: #111827;">Terms & Conditions</h5>
            <button type="button" id="btnAddTC" class="text-link" style="font-size: 0.75rem; font-weight: 600;">+ Add</button>
        </div>
        <div class="modal fade" id="addTermModal" tabindex="-1">
            <div class="modal-dialog modal-sm modal-dialog-centered" style="max-width: 420px;">
                <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
                    <div class="modal-header" style="padding: 0.75rem 1.25rem; border-bottom: 1px solid #e5e7eb;">
                        <h5 class="modal-title" style="font-size: 1rem; font-weight: 600;">
                            <i class="fas fa-file-signature" style="margin-right: 0.5rem; color: #64748b;"></i>Add Terms & Conditions
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="padding: 1.25rem;">
                        @csrf
                        <div style="margin-bottom: 0.85rem;">
                            <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.25rem; color: #374151;">Terms & Conditions</label>
                            <textarea id="newTermContent" name="content" rows="5" placeholder="Enter the term text" required style="width: 100%; padding: 0.85rem 0.95rem; border: 1px solid #d1d5db; border-radius: 10px; font-size: 0.9rem; outline: none; resize: vertical; min-height: 140px;"></textarea>
                        </div>
                        <div id="addTermError" style="display: none; margin-bottom: 0.85rem; padding: 0.65rem 0.8rem; border-radius: 10px; background: #fef2f2; color: #b91c1c; font-size: 0.85rem;"></div>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <button type="button" id="saveTermBtn" class="primary-button small">Save Term</button>
                            <button type="button" class="text-link small" id="btnCancelTermModal" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="termsList" style="max-height: 280px; overflow-y: auto; padding-right: 0.2rem;">
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

    <div style="margin-top: 0.9rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="primary-button create-submit-btn" id="finalSubmitBtn" disabled style="padding: 0.75rem 2.4rem; font-size: 0.95rem;">
            <i class="fas fa-file-invoice" style="margin-right: 0.5rem;"></i>Create Proforma Invoice
        </button>
    </div>
</div>

<script>
(function() {
    const clientId = "{{ request('clientid', request('c')) }}";
    const invoiceFor = "{{ request('invoice_for') }}";
    const orderId = "{{ request('orderid', request('o', '')) }}";
    const hasOrderId = orderId && orderId !== '0';
    const btnBackToPrev = document.getElementById('btnBackToPrev');
    const finalSubmitBtn = document.getElementById('finalSubmitBtn');
    const previewContent = document.getElementById('previewContent');
    const termsList = document.getElementById('termsList');
    const btnAddTC = document.getElementById('btnAddTC');
    const addTermModal = document.getElementById('addTermModal');
    const btnCloseTermModal = document.getElementById('btnCloseTermModal');
    const btnCancelTermModal = document.getElementById('btnCancelTermModal');
    const saveTermBtn = document.getElementById('saveTermBtn');
    const newTermContent = document.getElementById('newTermContent');
    const addTermError = document.getElementById('addTermError');
    const addTermBootstrapModal = addTermModal ? new bootstrap.Modal(addTermModal) : null;
    const piNumberBadge = document.getElementById('piNumberBadge');
    const itemsDataInput = document.getElementById('items_data');
    const invoiceNumberInput = document.getElementById('invoice_number');
    const proformaidInput = document.getElementById('proformaid');
    const currencyCodeInput = document.getElementById('currency_code');
    const sameStateGstForInvoice = @json($sameStateGstForInvoice);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    @php
        $signatureUploadPath = optional($accountBillingDetail)->signature_upload;
        $signatureUploadUrl = null;
        if (!empty($signatureUploadPath)) {
            if (str_starts_with($signatureUploadPath, 'http://') || str_starts_with($signatureUploadPath, 'https://')) {
                $signatureUploadUrl = $signatureUploadPath;
            } else {
                $signatureUploadUrl = asset(str_starts_with($signatureUploadPath, 'storage/') ? $signatureUploadPath : 'storage/' . ltrim($signatureUploadPath, '/'));
            }
        }

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
                'signature' => $signatureUploadUrl,
            ]
        ];
    @endphp
    const accountData = {!! json_encode($accountDataArr) !!};

    @php
        $clientBilling = optional($selectedInvoiceClient)->billingDetail;
        $clientDataArr = [
            'name' => optional($selectedInvoiceClient)->business_name ?? optional($selectedInvoiceClient)->contact_name ?? 'Client',
            'contact_name' => optional($selectedInvoiceClient)->contact_name ?? '',
            'email' => optional($selectedInvoiceClient)->email ?? '',
            'phone' => optional($selectedInvoiceClient)->phone ?? '',
            'billing' => [
                'name' => optional($clientBilling)->business_name ?? optional($selectedInvoiceClient)->business_name ?? '',
                'address_line_1' => optional($clientBilling)->address_line_1 ?? '',
                'city' => optional($clientBilling)->city ?? '',
                'state' => optional($clientBilling)->state ?? '',
                'postal_code' => optional($clientBilling)->postal_code ?? '',
                'country' => optional($clientBilling)->country ?? '',
                'gstin' => optional($clientBilling)->gstin ?? '',
            ],
        ];
    @endphp
    const clientData = {!! json_encode($clientDataArr) !!};

    let invoiceItems = [];
    let draftInvoiceTitle = '';
    let draftInvoiceNumber = invoiceNumberInput.value || '{{ $nextInvoiceNumber }}';

    function getCurrencyCode() {
        return currencyCodeInput.value || '{{ $selectedClientCurrency }}';
    }

    function openTermModal() {
        addTermError.style.display = 'none';
        addTermError.textContent = '';
        newTermContent.value = '';
        if (addTermBootstrapModal) {
            addTermBootstrapModal.show();
        }
        setTimeout(() => newTermContent.focus(), 0);
    }

    function closeTermModal() {
        if (addTermBootstrapModal) {
            addTermBootstrapModal.hide();
        }
    }

    // Load items
    function loadItems() {
        const draftUrl = new URL("{{ route('invoices.get-draft', ['clientid' => '__CLIENTID__']) }}".replace('__CLIENTID__', clientId), window.location.origin);
        if (invoiceFor) {
            draftUrl.searchParams.set('invoice_for', invoiceFor);
        }
        if (hasOrderId) {
            draftUrl.searchParams.set('orderid', orderId);
        }

        fetch(draftUrl.toString())
        .then(response => response.json())
        .then(data => {
            if (data.draft && data.draft.items) {
                invoiceItems = data.draft.items;
                draftInvoiceTitle = data.draft.invoice_title || '';
                draftInvoiceNumber = data.draft.invoice_number || draftInvoiceNumber;
                invoiceNumberInput.value = draftInvoiceNumber;
                proformaidInput.value = data.draft.proformaid || '';
                if (piNumberBadge) {
                    piNumberBadge.textContent = draftInvoiceNumber;
                }
                itemsDataInput.value = JSON.stringify(invoiceItems);
                updateTotals();
                updateInvoicePreview();
            }
        })
        .catch(() => {
            console.error('Failed to load draft items');
        });
    }

    function roundTaxUp(value) {
        return Math.ceil(Math.max(0, Number(value) || 0));
    }

    function roundDiscountDown(value) {
        return Math.floor(Math.max(0, Number(value) || 0));
    }

    function updateTotals() {
        let subtotal = 0, taxTotal = 0, discountTotal = 0;
        invoiceItems.forEach(item => {
            const lineTotal = parseFloat(item.line_total || 0);
            const lineDiscount = roundDiscountDown(parseFloat(item.discount_amount || 0));
            subtotal += lineTotal;
            discountTotal += lineDiscount;
            taxTotal += roundTaxUp(Math.max(0, lineTotal - lineDiscount) * (parseFloat(item.tax_rate || 0) / 100));
        });

        discountTotal = roundDiscountDown(discountTotal);
        taxTotal = roundTaxUp(taxTotal);

        document.getElementById('subtotal').value = subtotal.toFixed(2);
        document.getElementById('discount_total').value = discountTotal.toFixed(2);
        document.getElementById('tax_total').value = taxTotal.toFixed(2);
        document.getElementById('grand_total').value = (subtotal - discountTotal + taxTotal).toFixed(2);
    }

    function updateInvoicePreview() {
        const invoiceNumber = draftInvoiceNumber || invoiceNumberInput.value || "{{ $nextInvoiceNumber }}";
        const issueDate = document.getElementById('issue_date').value;
        const dueDate = document.getElementById('due_date').value;
        const currency = getCurrencyCode();
        const invoiceTitle = draftInvoiceTitle || 'Proforma Invoice';

        // Get terms
        const terms = Array.from(document.querySelectorAll('.term-checkbox'))
            .filter(cb => cb.checked)
            .map(cb => cb.value.trim())
            .filter(Boolean);

        const companyAddressLine = [
            accountData.billing.address,
            [accountData.billing.city, accountData.billing.state].filter(Boolean).join(', '),
            accountData.billing.postal_code,
            accountData.billing.country,
        ].filter(Boolean).join('<br>');

        const clientAddressLine = [
            clientData.billing.address_line_1,
            [clientData.billing.city, clientData.billing.state].filter(Boolean).join(', '),
            clientData.billing.postal_code,
            clientData.billing.country,
        ].filter(Boolean).join('<br>');

        const frequencyLabelMap = {
            'one-time': 'One-Time',
            'daily': 'Daily',
            'weekly': 'Weekly',
            'bi-weekly': 'Bi-Weekly',
            'monthly': 'Monthly',
            'quarterly': 'Quarterly',
            'semi-annually': 'Semi-Annually',
            'yearly': 'Yearly'
        };

        const formatDate = (dateValue) => {
            if (!dateValue) return '-';
            const d = new Date(dateValue);
            if (Number.isNaN(d.getTime())) return dateValue;
            return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        };

        // Build items table
        let itemsHtml = '';
        invoiceItems.forEach((item, index) => {
            const qty = Math.max(1, Math.round(Number(item.quantity || 1)));
            const unitPrice = Number(item.unit_price || 0);
            const lineTotal = Number(item.line_total || 0);
            const discountPercent = Number(item.discount_percent || 0);
            const discountAmount = Number(item.discount_amount || 0);
            const users = item.no_of_users ? Number(item.no_of_users) : null;
            const frequency = item.frequency ? (frequencyLabelMap[item.frequency] || item.frequency) : '-';
            const duration = item.duration ? Number(item.duration) : null;
            const taxableAmount = Math.max(0, lineTotal - discountAmount);

            itemsHtml += `
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 0.75rem 0.65rem; border: 1px solid #e5e7eb; font-size: 0.79rem;">${index + 1}</td>
                    <td style="padding: 0.75rem 0.65rem; border: 1px solid #e5e7eb; font-size: 0.8rem; font-weight: 600; color: #111827;">${item.item_name || 'Item'}</td>
                    <td style="padding: 0.75rem 0.65rem; text-align: center; border: 1px solid #e5e7eb; font-size: 0.79rem;">${qty}</td>
                    <td style="padding: 0.75rem 0.65rem; text-align: center; border: 1px solid #e5e7eb; font-size: 0.79rem;">${frequency}</td>
                    <td style="padding: 0.75rem 0.65rem; text-align: center; border: 1px solid #e5e7eb; font-size: 0.79rem;">${users || '-'}</td>
                    <td style="padding: 0.75rem 0.65rem; text-align: center; border: 1px solid #e5e7eb; font-size: 0.79rem;">${duration || '-'}</td>
                    <td style="padding: 0.75rem 0.65rem; text-align: center; border: 1px solid #e5e7eb; font-size: 0.79rem;">${formatDate(item.start_date)}</td>
                    <td style="padding: 0.75rem 0.65rem; text-align: center; border: 1px solid #e5e7eb; font-size: 0.79rem;">${formatDate(item.end_date)}</td>
                    <td style="padding: 0.75rem 0.65rem; text-align: right; border: 1px solid #e5e7eb; font-size: 0.79rem;">${currency} ${unitPrice.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td style="padding: 0.75rem 0.65rem; text-align: right; border: 1px solid #e5e7eb; font-size: 0.79rem;">${discountPercent.toLocaleString('en-IN', {maximumFractionDigits: 2})}% (${currency} ${discountAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})})</td>
                    <td style="padding: 0.75rem 0.65rem; text-align: right; border: 1px solid #e5e7eb; font-weight: 600; font-size: 0.79rem;">${currency} ${taxableAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                </tr>
            `;
        });

        let subtotal = 0, taxTotal = 0, discountTotal = 0, grandTotal = 0;
        invoiceItems.forEach(item => {
            const lineTotal = parseFloat(item.line_total || 0);
            const lineDiscount = roundDiscountDown(parseFloat(item.discount_amount || 0));
            subtotal += lineTotal;
            discountTotal += lineDiscount;
            taxTotal += roundTaxUp(Math.max(0, lineTotal - lineDiscount) * (parseFloat(item.tax_rate || 0) / 100));
        });
        discountTotal = roundDiscountDown(discountTotal);
        taxTotal = roundTaxUp(taxTotal);
        grandTotal = subtotal - discountTotal + taxTotal;
        const taxRowsHtml = sameStateGstForInvoice
            ? `
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;">
                        <span>Tax (CGST + SGST):</span><strong>${currency} ${taxTotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong>
                    </div>
              `
            : `
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;">
                        <span>Tax (IGST):</span><strong>${currency} ${taxTotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong>
                    </div>
              `;

        previewContent.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.75rem; padding-bottom: 1rem; border-bottom: 2px solid #111827;">
                <div style="max-width: 56%;">
                    <div style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; margin-bottom: 0.25rem;">Proforma Invoice</div>
                    <h1 style="margin: 0; font-size: 1.7rem; color: #111827; font-weight: 700;">${invoiceTitle}</h1>
                </div>
                <div style="text-align: right; min-width: 230px;">
                    ${accountData.logo ? `<img src="${accountData.logo}" style="max-width: 140px; max-height: 56px; margin-bottom: 0.65rem; object-fit: contain;">` : ''}
                    <div style="display: inline-block; text-align: left; border: 1px solid #e5e7eb; border-radius: 8px; padding: 0.65rem 0.8rem; background: #fafafa;">
                        <p style="margin: 0.1rem 0; font-size: 0.8rem;"><strong>No:</strong> ${invoiceNumber}</p>
                        <p style="margin: 0.1rem 0; font-size: 0.8rem;"><strong>Issue:</strong> ${issueDate}</p>
                        <p style="margin: 0.1rem 0; font-size: 0.8rem;"><strong>Due:</strong> ${dueDate}</p>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; margin-bottom: 1.5rem;">
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 0.8rem 0.95rem; background: #fcfcfc;">
                    <div style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; margin-bottom: 0.35rem;">From</div>
                    <p style="margin: 0.12rem 0; font-size: 0.92rem; font-weight: 700; color: #111827;">${accountData.billing.name || accountData.name || 'Company Name'}</p>
                    ${companyAddressLine ? `<p style="margin: 0.2rem 0; font-size: 0.8rem; color: #4b5563; line-height: 1.45;">${companyAddressLine}</p>` : ''}
                    ${accountData.billing.gstin ? `<p style="margin: 0.15rem 0; font-size: 0.78rem; color: #374151;"><strong>GSTIN:</strong> ${accountData.billing.gstin}</p>` : ''}
                </div>
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 0.8rem 0.95rem; background: #fcfcfc;">
                    <div style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; margin-bottom: 0.35rem;">Bill To</div>
                    <p style="margin: 0.12rem 0; font-size: 0.92rem; font-weight: 700; color: #111827;">${clientData.billing.name || clientData.name || 'Client'}</p>
                    ${clientAddressLine ? `<p style="margin: 0.2rem 0; font-size: 0.8rem; color: #4b5563; line-height: 1.45;">${clientAddressLine}</p>` : ''}
                    ${clientData.email ? `<p style="margin: 0.15rem 0; font-size: 0.78rem; color: #374151;"><strong>Email:</strong> ${clientData.email}</p>` : ''}
                    ${clientData.phone ? `<p style="margin: 0.15rem 0; font-size: 0.78rem; color: #374151;"><strong>Phone:</strong> ${clientData.phone}</p>` : ''}
                    ${clientData.billing.gstin ? `<p style="margin: 0.15rem 0; font-size: 0.78rem; color: #374151;"><strong>GSTIN:</strong> ${clientData.billing.gstin}</p>` : ''}
                </div>
            </div>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                <thead style="background: #f3f4f6; color: #111827;">
                    <tr>
                        <th style="padding: 0.75rem 0.65rem; text-align: left; border: 1px solid #d1d5db; font-size: 0.79rem;">#</th>
                        <th style="padding: 0.75rem 0.65rem; text-align: left; border: 1px solid #d1d5db; font-size: 0.79rem;">Description</th>
                        <th style="padding: 0.75rem 0.65rem; text-align: center; border: 1px solid #d1d5db; font-size: 0.79rem;">Qty</th>
                        <th style="padding: 0.75rem 0.65rem; text-align: center; border: 1px solid #d1d5db; font-size: 0.79rem;">Frequency</th>
                        <th style="padding: 0.75rem 0.65rem; text-align: center; border: 1px solid #d1d5db; font-size: 0.79rem;">Users</th>
                        <th style="padding: 0.75rem 0.65rem; text-align: center; border: 1px solid #d1d5db; font-size: 0.79rem;">Duration</th>
                        <th style="padding: 0.75rem 0.65rem; text-align: center; border: 1px solid #d1d5db; font-size: 0.79rem;">Start Date</th>
                        <th style="padding: 0.75rem 0.65rem; text-align: center; border: 1px solid #d1d5db; font-size: 0.79rem;">End Date</th>
                        <th style="padding: 0.75rem 0.65rem; text-align: right; border: 1px solid #d1d5db; font-size: 0.79rem;">Unit Price</th>
                        <th style="padding: 0.75rem 0.65rem; text-align: right; border: 1px solid #d1d5db; font-size: 0.79rem;">Discount</th>
                        <th style="padding: 0.75rem 0.65rem; text-align: right; border: 1px solid #d1d5db; font-size: 0.79rem;">Amount</th>
                    </tr>
                </thead>
                <tbody>${itemsHtml}</tbody>
            </table>

            <div style="display: flex; justify-content: flex-end;">
                <div style="min-width: 300px; border: 1px solid #d1d5db; border-radius: 8px; padding: 0.65rem 0.8rem; background: #fafafa;">
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;">
                        <span>Subtotal:</span><strong>${currency} ${subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb; color: #dc2626;">
                        <span>Discount:</span><strong>-${currency} ${discountTotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong>
                    </div>
                    ${taxRowsHtml}
                    <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; font-size: 1.05rem; font-weight: 700; color: #111827;">
                        <span>Grand Total:</span><span>${currency} ${grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}</span>
                    </div>
                </div>
            </div>

            ${terms.length ? `<div style="margin-top: 1.6rem; padding: 0.9rem 0; border-top: 1px solid #e5e7eb;"><h4 style="margin: 0 0 0.45rem 0; font-size: 0.86rem; text-transform: uppercase; letter-spacing: 0.06em; color: #374151;">Terms & Conditions</h4><ul style="margin: 0; padding-left: 1.25rem; font-size: 0.82rem; line-height: 1.65; color: #4b5563; list-style: disc;">${terms.map(term => `<li style="margin-bottom: 0.35rem;">${term.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</li>`).join('')}</ul></div>` : ''}

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
                <div style="text-align: right; min-width: 220px;">
                    ${accountData.billing.signature ? `<img src="${accountData.billing.signature}" style="max-width: 130px; max-height: 52px; object-fit: contain; margin-bottom: 0.25rem;">` : ''}
                    <div style="border-top: 1px solid #6b7280; padding-top: 0.3rem; font-size: 0.78rem; color: #374151;">
                        ${(accountData.billing.signatory || '').trim() || accountData.billing.name || accountData.name || 'Authorized Signatory'}
                    </div>
                </div>
            </div>
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

    if (btnAddTC && addTermModal) {
        btnAddTC.addEventListener('click', openTermModal);
    }

    if (btnCancelTermModal) {
        btnCancelTermModal.addEventListener('click', closeTermModal);
    }

    if (btnCloseTermModal) {
        btnCloseTermModal.addEventListener('click', closeTermModal);
    }

    if (saveTermBtn) {
        saveTermBtn.addEventListener('click', function () {
            const content = newTermContent.value.trim();

            if (!content) {
                addTermError.textContent = 'Please enter the term content.';
                addTermError.style.display = 'block';
                return;
            }

            addTermError.style.display = 'none';
            addTermError.textContent = '';

            fetch("{{ route('invoices.terms.billing.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ title: '', content }),
            })
                .then(response => response.json().then(data => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok || !data.ok) {
                        throw new Error(data.message || 'Unable to save term.');
                    }

                    const term = data.term;
                    const row = document.createElement('div');
                    row.style.marginBottom = '0.4rem';
                    row.style.padding = '0.65rem';
                    row.style.borderRadius = '8px';
                    row.style.border = '1px solid #e5e7eb';
                    row.style.background = '#f9fafb';
                    row.className = 'term-item-row';
                    row.innerHTML = `
                        <label class="custom-checkbox" style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" class="term-checkbox" data-tc-id="${term.id}" data-content="${term.content.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\"/g, '&quot;')}" value="${term.content.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\"/g, '&quot;')}" style="margin-top: 0.15rem; width: 14px; height: 14px; cursor: pointer; flex-shrink: 0;">
                            <div style="flex: 1;"><p style="margin: 0; font-size: 0.78rem; color: #4b5563; line-height: 1.4;">${term.content.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</p></div>
                        </label>
                    `;

                    termsList.prepend(row);
                    closeTermModal();
                    updateInvoicePreview();
                })
                .catch(error => {
                    addTermError.textContent = error.message || 'Unable to save term.';
                    addTermError.style.display = 'block';
                });
        });
    }

    // Back button
    btnBackToPrev.addEventListener('click', function() {
        const prevStep = invoiceFor === 'without_orders' ? 2 : 3;
        const clientToken = encodeURIComponent(clientId);
        let prevUrl = "{{ route('invoices.create') }}?step=" + prevStep + "&invoice_for=" + encodeURIComponent(invoiceFor) + "&c=" + clientToken + "&clientid=" + clientToken;
        if (hasOrderId) {
            const orderToken = encodeURIComponent(orderId);
            prevUrl += "&o=" + orderToken + "&orderid=" + orderToken;
        }
        window.location.href = prevUrl;
    });

    // Initialize
    loadItems();
})();
</script>
