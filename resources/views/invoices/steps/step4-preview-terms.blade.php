@php
    $normalizeTaxState = static fn ($value) => preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $value)));
    $selectedInvoiceClient = $clients->firstWhere('clientid', request('c', request('clientid')));
    $selectedClientCurrency = optional($selectedInvoiceClient)->currency ?? 'INR';
    $selectedClientName = $selectedInvoiceClient ? ($selectedInvoiceClient->business_name ?? $selectedInvoiceClient->contact_name ?? 'Unknown Client') : 'No Client Selected';
    $selectedClientEmail = $selectedInvoiceClient->email ?? '';
    $invoiceClientState = $normalizeTaxState(optional($selectedInvoiceClient)->state ?? '');
    $invoiceAccountState = $normalizeTaxState(optional($account)->state ?? '');
    $sameStateGstForInvoice = $invoiceClientState !== '' && $invoiceAccountState !== '' && $invoiceClientState === $invoiceAccountState;
    $isTaxInvoiceStep4 = (request('tax_invoice', 0) == 1) || !empty($invoice?->ti_number);
    $initialHeaderNumber = $isTaxInvoiceStep4
        ? ($invoice?->ti_number ?: ($nextTaxInvoiceNumber ?? $nextInvoiceNumber))
        : ($invoice?->pi_number ?: $nextInvoiceNumber);
@endphp
<!-- Step 4: Preview & Terms (For Orders & Renewal, and Without Orders Step 3) -->
<div id="step4" class="invoice-step">
    {{-- Client Info Header with Back Button --}}
    <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <button type="button" id="btnBackToPrev" class="secondary-button" style="padding: 0.4rem 0.65rem; flex-shrink: 0; font-size: 0.85rem;">
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
            <div style="text-align: right; flex-shrink: 0;">
                <div id="piNumberBadge" style="display: inline-block; padding: 0.35rem 0.75rem; background: #eef2ff; color: #4f46e5; border-radius: 6px; font-size: 0.85rem; font-weight: 700; border: 1px solid #c7d2fe;">
                    {{ $initialHeaderNumber }}
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="clientid" value="{{ request('c', request('clientid')) }}">
    <input type="hidden" name="invoice_for" value="{{ request('invoice_for') }}">
    <input type="hidden" name="orderid" value="{{ request('o', request('orderid', '')) === '0' ? '' : request('o', request('orderid', '')) }}">
    <input type="hidden" name="invoiceid" id="step4_invoiceid" value="{{ request('d', '') }}">
    <input type="hidden" name="renewed_item_ids" id="step4_renewed_item_ids" value="">
    <input type="hidden" name="invoice_number" id="step4_invoice_number" value="{{ $invoice?->pi_number ?? $nextInvoiceNumber }}">
    <input type="hidden" name="issue_date" id="step4_issue_date" value="{{ date('Y-m-d') }}">
    <input type="hidden" name="due_date" id="step4_due_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}">
    <input type="hidden" name="items_data" id="step4_items_data" value="">
    <input type="hidden" name="currency_code" id="step4_currency_code" value="{{ $selectedClientCurrency }}">
    <input type="hidden" name="notes" id="step4_notes" value="">

    <div class="row g-3 align-items-start">
        <div class="col-12 col-md-3" style="min-width: 0;">
            <div class="panel-card" style="padding: 0.85rem; border: 1px solid #e5e7eb; background: #fff; position: relative; height: 100%; overflow: hidden;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem; padding-bottom: 0.35rem; border-bottom: 1px solid #e5e7eb;">
                    <h5 style="margin: 0; font-size: 0.9rem; color: #111827;">Terms & Conditions</h5>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <button type="button" id="btnApplyTC" class="primary-button" style="padding: 0.28rem 0.65rem; font-size: 0.72rem; display: none;">Apply</button>
                        <button type="button" id="btnAddTC" class="text-link" style="font-size: 0.75rem; font-weight: 600;">+ Add</button>
                    </div>
                </div>
                <div class="modal fade" id="addTermModal" tabindex="-1">
                    <div class="modal-dialog modal-sm modal-dialog-centered" style="max-width: 420px;">
                        <div class="modal-content" class="rounded-panel">
                            <div class="modal-header" class="modal-header-custom">
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
                <div id="termsList" style="padding-right: 0.2rem;">
                    @foreach($billingTerms as $term)
                    <div style="margin-bottom: 0.55rem; padding: 0; width: 100%; max-width: 100%; box-sizing: border-box;" class="term-item-row">
                        <label class="custom-checkbox" style="display: flex; align-items: flex-start; gap: 0.45rem; cursor: pointer; margin-bottom: 0.2rem; width: 100%; max-width: 100%; box-sizing: border-box;">
                            <input type="checkbox" class="term-checkbox" data-tc-id="{{ $term->tc_id }}" data-content="{{ $term->content }}" value="{{ $term->content }}" style="width: 14px; height: 14px; cursor: pointer; flex-shrink: 0;">
                            <div style="min-width: 0; width: 100%; box-sizing: border-box; word-break: break-word; overflow-wrap: anywhere; white-space: normal;">
                                <p style="margin: 0; font-size: 0.78rem; color: #4b5563; line-height: 1.45; word-break: break-word; overflow-wrap: anywhere; white-space: normal;">{{ $term->content }}</p>
                            </div>
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-12 col-md-9" style="min-width: 0;">
            <!-- Invoice Preview -->
            <div class="panel-card" style="padding: 0; border: 1px solid #e5e7eb; overflow: hidden; background: #fff; margin-bottom: 0; min-width: 0;">
                <div style="background: #fafafa; padding: 0.7rem 0.9rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <h5 style="margin: 0; font-size: 0.95rem; color: #111827;">
                        <i class="fas fa-file-pdf" style="color: #374151; margin-right: 0.5rem;"></i>
                        {{ $isTaxInvoiceStep4 ? 'Tax Invoice Preview' : 'Invoice Preview' }}
                    </h5>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <span style="font-size: 0.75rem; color: #6b7280; font-weight: 500;">
                            <i class="fas fa-circle" style="color: #10b981; font-size: 0.5rem; margin-right: 0.3rem;"></i>
                            Live Preview
                        </span>
                        <button type="button" id="btnEditPreview" class="secondary-button" style="padding: 0.35rem 0.7rem; font-size: 0.8rem;">
                            <i class="fas fa-edit" class="icon-spaced-sm"></i>Edit
                        </button>
                    </div>
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
        </div>
    </div>

    <div style="margin-top: 0.9rem; display: flex; justify-content: flex-end; gap: 0.75rem; flex-wrap: wrap;">
        <a id="btnDownloadPI" href="#" target="_blank" class="secondary-button" style="padding: 0.75rem 1.5rem; font-size: 0.95rem; display: none; align-items: center; gap: 0.4rem; text-decoration: none;">
            <i class="fas fa-file-download"></i> Download PI
        </a>
        <button type="button" id="btnDownloadTaxInvoice" class="secondary-button" style="padding: 0.75rem 1.5rem; font-size: 0.95rem; display: none; align-items: center; gap: 0.4rem;">
            <i class="fas fa-file-invoice-dollar"></i> Download Tax Invoice
        </button>
        <button type="button" class="text-button" id="digitalSignBtn" disabled style="padding: 0.75rem 1.5rem; font-size: 0.95rem; opacity: 0.5;">
            <i class="fas fa-signature" style="margin-right: 0.5rem;"></i>Download Signed
        </button>
        <button type="button" class="secondary-button" id="btnExit" style="padding: 0.75rem 1.5rem; font-size: 0.95rem;">
            <i class="fas fa-times" style="margin-right: 0.5rem;"></i>Exit
        </button>
    </div>
</div>

<script>
(function() {
    const clientId = "{{ request('c', request('clientid')) }}";
    const invoiceFor = "{{ request('invoice_for') }}";
    const orderId = "{{ request('o', request('orderid', '')) }}";
    const draftId = "{{ request('d', '') }}";
    const isTaxInvoice = @json($isTaxInvoiceStep4);
    const hasOrderId = orderId && orderId !== '0';
    const btnBackToPrev = document.getElementById('btnBackToPrev');
    const finalSubmitBtn = document.getElementById('finalSubmitBtn');
    const createTaxInvoiceBtn = document.getElementById('createTaxInvoiceBtn');
    const digitalSignBtn = document.getElementById('digitalSignBtn');
    const btnDownloadPI = document.getElementById('btnDownloadPI');
    const btnDownloadTaxPI = document.getElementById('btnDownloadTaxPI');
    const btnDownloadTaxInvoice = document.getElementById('btnDownloadTaxInvoice');
    const previewContent = document.getElementById('previewContent');
    const termsList = document.getElementById('termsList');
    const btnAddTC = document.getElementById('btnAddTC');
    const btnApplyTC = document.getElementById('btnApplyTC');
    const addTermModal = document.getElementById('addTermModal');
    const btnCloseTermModal = document.getElementById('btnCloseTermModal');
    const btnCancelTermModal = document.getElementById('btnCancelTermModal');
    const saveTermBtn = document.getElementById('saveTermBtn');
    const newTermContent = document.getElementById('newTermContent');
    const addTermError = document.getElementById('addTermError');
    const addTermBootstrapModal = addTermModal ? new bootstrap.Modal(addTermModal) : null;
    const piNumberBadge = document.getElementById('piNumberBadge');
    const itemsDataInput = document.getElementById('step4_items_data');
    const invoiceNumberInput = document.getElementById('step4_invoice_number');
    const invoiceidInput = document.getElementById('step4_invoiceid');
    const currencyCodeInput = document.getElementById('step4_currency_code');
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
            'logo' => ($account && $account->logo_path)
                ? (str_starts_with($account->logo_path, 'http') ? $account->logo_path : asset($account->logo_path))
                : null,
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
    let draftInvoiceNumber = invoiceNumberInput.value || '{{ $invoice?->pi_number ?? $nextInvoiceNumber }}';
    let draftPiNumber = '';
    let draftTiNumber = '';
    let draftIssueDate = '';
    let draftDueDate = '';
    let draftNotes = '';
    let draftPoNumber = '';
    let draftPoDate = '';
    let appliedTerms = [];
    const fallbackPiNumber = "{{ $nextInvoiceNumber }}";
    const fallbackTiNumber = "{{ $nextTaxInvoiceNumber ?? $nextInvoiceNumber }}";

    function getHeaderDocumentNumber() {
        if (draftTiNumber) {
            return draftTiNumber;
        }
        if (isTaxInvoice) {
            return draftTiNumber || fallbackTiNumber;
        }
        return draftPiNumber || draftInvoiceNumber || invoiceNumberInput.value || fallbackPiNumber;
    }

    function updateHeaderNumberBadge() {
        if (piNumberBadge) {
            piNumberBadge.textContent = getHeaderDocumentNumber();
        }
    }

    function getCurrencyCode() {
        return currencyCodeInput.value || '{{ $selectedClientCurrency }}';
    }

    function formatMoney(amount) {
        const currency = getCurrencyCode();
        return `${currency} ${Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
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
            draftUrl.searchParams.set('o', orderId);
        }
        if (draftId) {
            draftUrl.searchParams.set('d', draftId);
        }

        fetch(draftUrl.toString())
        .then(response => response.json())
        .then(data => {
            console.log('Draft data loaded:', data);
            if (data.draft) {
                if (data.draft.items) {
                    invoiceItems = data.draft.items;
                    itemsDataInput.value = JSON.stringify(invoiceItems);
                }
                
                draftInvoiceTitle = data.draft.invoice_title || '';
                draftInvoiceNumber = data.draft.invoice_number || draftInvoiceNumber;
                draftPiNumber = data.draft.pi_number || '';
                draftTiNumber = data.draft.ti_number || '';
                draftIssueDate = data.draft.issue_date || '';
                draftDueDate = data.draft.due_date || '';
                draftNotes = data.draft.notes || '';
                
                if (data.draft.issue_date) document.getElementById('step4_issue_date').value = data.draft.issue_date;
                if (data.draft.due_date) document.getElementById('step4_due_date').value = data.draft.due_date;
                if (data.draft.notes) document.getElementById('step4_notes').value = data.draft.notes;
                if (data.draft.currency_code) {
                    currencyCodeInput.value = data.draft.currency_code;
                }
                
                draftPoNumber = data.draft.po_number || '';
                draftPoDate = data.draft.po_date || '';

                if (data.draft.terms) {
                    appliedTerms = Array.isArray(data.draft.terms) ? data.draft.terms : [];
                    const checkboxes = document.querySelectorAll('.term-checkbox');
                    checkboxes.forEach(cb => {
                        const val = cb.value.trim();
                        cb.checked = appliedTerms.some(t => String(t).trim() === val);
                    });
                }

                invoiceNumberInput.value = draftInvoiceNumber;
                invoiceidInput.value = data.draft.invoiceid || '';
                if (btnApplyTC && data.draft.invoiceid) btnApplyTC.style.display = 'inline-block';
                
                updateHeaderNumberBadge();
                
                updateDownloadButtons(data.draft.invoiceid, data.draft.invoice_number);
                
                updateTotals();
                updateInvoicePreview();
            } else {
                console.log('No draft found');
                updateHeaderNumberBadge();
                updateInvoicePreview();
            }
        })
        .catch(error => {
            console.error('Failed to load draft items:', error);
            updateHeaderNumberBadge();
            updateInvoicePreview();
        });
    }

    function roundTaxUp(value) {
        return Math.ceil(Math.max(0, Number(value) || 0));
    }

    function roundDiscountDown(value) {
        return Math.floor(Math.max(0, Number(value) || 0));
    }

    function updateDownloadButtons(invoiceid, invoiceNumber) {
        if (!invoiceid) return;
        const base = "{{ url('invoices') }}/" + invoiceid + "/pdf";
        if (btnDownloadPI) {
            btnDownloadPI.href = base + '?type=pi';
            btnDownloadPI.style.display = 'inline-flex';
        }
        if (btnDownloadTaxInvoice) {
            btnDownloadTaxInvoice.style.display = 'inline-flex';
            btnDownloadTaxInvoice.onclick = function() {
                if (draftTiNumber) {
                    window.open(base + '?type=tax_invoice', '_blank');
                } else {
                    createTaxInvoice();
                }
            };
        }
        if (digitalSignBtn) {
            digitalSignBtn.disabled = false;
            digitalSignBtn.style.opacity = '1';
            digitalSignBtn.onclick = () => window.open(base + '?type=pi&signed=1', '_blank');
        }
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
    }

    function updateInvoicePreview() {
        const invoiceNumber = draftInvoiceNumber || invoiceNumberInput.value || "{{ $nextInvoiceNumber }}";
        const issueDate = draftIssueDate || document.getElementById('step4_issue_date')?.value || '-';
        const dueDate = draftDueDate || document.getElementById('step4_due_date')?.value || '-';
        const invoiceTitle = draftInvoiceTitle || document.getElementById('invoice_title')?.value || 'Invoice';
        const notes = draftNotes || document.getElementById('step4_notes')?.value || '';

        // Get terms — live checkbox state
        const terms = Array.from(document.querySelectorAll('.term-checkbox:checked'))
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
            'One-Time': 'One-Time',
            'Day(s)': 'Day(s)',
            'Week(s)': 'Week(s)',
            'Month(s)': 'Month(s)',
            'Quarter(s)': 'Quarter(s)',
            'Year(s)': 'Year(s)'
        };
        
        const hasRecurring = invoiceItems.some(item => item.frequency && item.frequency !== 'one-time');
        const showDates = hasRecurring;

        const formatDate = (dateValue) => {
            if (!dateValue) return '-';
            const d = new Date(dateValue);
            if (Number.isNaN(d.getTime())) return dateValue;
            return d.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });
        };

        const formatFrequencyDuration = (frequency, duration) => {
            if (!frequency || frequency === 'One-Time') return 'One-Time';
            return duration ? `${duration} ${frequency}` : frequency;
        };

        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');

        // Build items table
        let itemsHtml = '';
        let totalItemsLineTotal = 0;
        let totalItemsDiscountTotal = 0;
        let totalItemsTaxTotal = 0;

        invoiceItems.forEach((item, index) => {
            const qty = Math.max(1, Math.round(Number(item.quantity || 1)));
            const unitPrice = Number(item.unit_price || 0);
            const lineTotal = Number(item.line_total || 0);
            const discountPercent = Number(item.discount_percent || 0);
            const discountAmount = roundDiscountDown(item.discount_amount || (lineTotal * (discountPercent / 100)) || 0);
            const taxableAmount = Math.max(0, lineTotal - discountAmount);
            const taxRate = Number(item.tax_rate || 0);
            const taxAmount = roundTaxUp(taxableAmount * (taxRate / 100));

            totalItemsLineTotal += lineTotal;
            totalItemsDiscountTotal += discountAmount;
            totalItemsTaxTotal += taxAmount;

            const discountedUnitPrice = qty > 0 ? (lineTotal / qty) : unitPrice; // Showing raw rate
            
            const users = item.no_of_users ? Number(item.no_of_users) : null;
            const frequency = item.frequency ? (frequencyLabelMap[item.frequency] || item.frequency) : '-';
            const duration = item.duration ? Number(item.duration) : null;
            const itemName = escapeHtml(item.item_name || 'Item');
            const itemDescription = escapeHtml(item.item_description || '').trim();

            itemsHtml += `
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 0.5rem 0.5rem; border: 1px solid #e5e7eb; font-size: 0.75rem;">${index + 1}</td>
                    <td style="padding: 0.5rem 0.5rem; border: 1px solid #e5e7eb; font-size: 0.76rem; color: #111827;">
                        <div style="font-weight: 600;">${itemName}</div>
                        ${itemDescription ? `<div style="margin-top: 0.1rem; font-size: 0.72rem; color: #6b7280; white-space: pre-wrap;">${itemDescription}</div>` : ''}
                    </td>
                    <td style="padding: 0.5rem 0.5rem; text-align: center; border: 1px solid #e5e7eb; font-size: 0.75rem;">${qty}</td>
                    <td style="padding: 0.5rem 0.5rem; text-align: center; border: 1px solid #e5e7eb; font-size: 0.75rem;">${users || '-'}</td>
                    <td style="padding: 0.5rem 0.5rem; text-align: center; border: 1px solid #e5e7eb; font-size: 0.75rem;">${formatFrequencyDuration(frequency, duration)}</td>
                    ${showDates ? `
                    <td style="padding: 0.5rem 0.5rem; text-align: center; border: 1px solid #e5e7eb; font-size: 0.75rem;">${formatDate(item.start_date)}</td>
                    <td style="padding: 0.5rem 0.5rem; text-align: center; border: 1px solid #e5e7eb; font-size: 0.75rem;">${formatDate(item.end_date)}</td>
                    ` : ''}
                    <td style="padding: 0.5rem 0.5rem; text-align: right; border: 1px solid #e5e7eb; font-size: 0.75rem;">${unitPrice.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0})}</td>
                    <td style="padding: 0.5rem 0.5rem; text-align: right; border: 1px solid #e5e7eb; font-weight: 600; font-size: 0.75rem;">${lineTotal.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0})}</td>
                </tr>
            `;
        });

        const subtotal = totalItemsLineTotal;
        const discountTotal = roundDiscountDown(totalItemsDiscountTotal);
        const taxTotal = roundTaxUp(totalItemsTaxTotal);
        const grandTotal = subtotal - discountTotal + taxTotal;

        const cgstAmount = taxTotal / 2;
        const sgstAmount = taxTotal - cgstAmount;
        const taxRowsHtml = sameStateGstForInvoice
            ? `
                    <div style="display: flex; justify-content: space-between; padding: 0.25rem 0; border-bottom: 1px solid #e5e7eb;">
                        <span>Tax (CGST):</span><strong>${(cgstAmount).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.25rem 0; border-bottom: 1px solid #e5e7eb;">
                        <span>Tax (SGST):</span><strong>${(sgstAmount).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}</strong>
                    </div>
              `
            : `
                    <div style="display: flex; justify-content: space-between; padding: 0.25rem 0; border-bottom: 1px solid #e5e7eb;">
                        <span>Tax (IGST):</span><strong>${(taxTotal).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}</strong>
                    </div>
              `;


        previewContent.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 2px solid #111827; gap: 1rem;">
                <div style="flex: 1 1 auto; min-width: 0; max-width: 56%;">
                    <p style="margin: 0 0 0.12rem 0; font-size: 0.92rem; font-weight: 700; color: #111827;">${accountData.billing.name || accountData.name || 'Company Name'}</p>
                    ${companyAddressLine ? `<p style="margin: 0.2rem 0; font-size: 0.8rem; color: #4b5563; line-height: 1.45;">${companyAddressLine}</p>` : ''}
                    ${accountData.billing.gstin ? `<p style="margin: 0.15rem 0; font-size: 0.78rem; color: #374151;"><strong>GSTIN:</strong> ${accountData.billing.gstin}</p>` : ''}
                </div>
                <div style="text-align: right; min-width: 240px; margin-left: auto; display: flex; flex-direction: column; align-items: flex-end; gap: 0.45rem;">
                    <div style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; margin-bottom: 0.25rem;">${draftTiNumber ? 'Tax Invoice' : 'Proforma Invoice'}</div>
                    ${accountData.logo ? `<img src="${accountData.logo}" style="max-width: 140px; max-height: 56px; object-fit: contain;">` : ''}
                    <div class="text-right">
                        <p style="margin: 0.1rem 0; font-size: 0.8rem;"><strong>${draftTiNumber ? 'Tax No:' : 'Proforma No:'}</strong> ${draftTiNumber || draftPiNumber || invoiceNumber}</p>
                        <p style="margin: 0.1rem 0; font-size: 0.8rem;"><strong>Issue Date:</strong> ${formatDate(issueDate)}</p>
                        <p style="margin: 0.1rem 0; font-size: 0.8rem;"><strong>Due Date:</strong> ${formatDate(dueDate)}</p>
                        ${draftPoNumber ? `<p style="margin: 0.1rem 0; font-size: 0.8rem;"><strong>PO Number:</strong> ${draftPoNumber}</p>` : ''}
                        ${draftPoDate ? `<p style="margin: 0.1rem 0; font-size: 0.8rem;"><strong>PO Date:</strong> ${formatDate(draftPoDate)}</p>` : ''}
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: flex-start;">
                <div style="flex: 1; min-width: 0; padding: 0.8rem 0.95rem 0.8rem 0;">
                    <div style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; margin-bottom: 0.35rem;">Bill To</div>
                    <p style="margin: 0.12rem 0; font-size: 0.92rem; font-weight: 700; color: #111827;">${clientData.billing.name || clientData.name || 'Client'}</p>
                    ${clientAddressLine ? `<p style="margin: 0.2rem 0; font-size: 0.8rem; color: #4b5563; line-height: 1.45;">${clientAddressLine}</p>` : ''}
                    ${clientData.billing.gstin ? `<p style="margin: 0.15rem 0; font-size: 0.78rem; color: #374151;"><strong>GSTIN:</strong> ${clientData.billing.gstin}</p>` : ''}
                </div>
                ${invoiceTitle ? `<div style="flex-shrink: 0; max-width: 45%; text-align: right; padding-top: 0.2rem;"><span style="font-size: 0.95rem; font-weight: 700; color: #111827;">${invoiceTitle}</span></div>` : ''}
            </div>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 1.5rem;">
                <thead style="background: #f3f4f6; color: #111827;">
                    <tr>
                        <th style="padding: 0.5rem 0.5rem; text-align: left; border: 1px solid #d1d5db; font-size: 0.75rem;">#</th>
                        <th style="padding: 0.5rem 0.5rem; text-align: left; border: 1px solid #d1d5db; font-size: 0.75rem;">Description</th>
                        <th style="padding: 0.5rem 0.5rem; text-align: center; border: 1px solid #d1d5db; font-size: 0.75rem;">Qty</th>
                        <th style="padding: 0.5rem 0.5rem; text-align: center; border: 1px solid #d1d5db; font-size: 0.75rem;">Users</th>
                        <th style="padding: 0.5rem 0.5rem; text-align: center; border: 1px solid #d1d5db; font-size: 0.75rem;">Duration</th>
                        ${showDates ? `
                        <th style="padding: 0.5rem 0.5rem; text-align: center; border: 1px solid #d1d5db; font-size: 0.75rem;">Start</th>
                        <th style="padding: 0.5rem 0.5rem; text-align: center; border: 1px solid #d1d5db; font-size: 0.75rem;">End</th>
                        ` : ''}
                        <th style="padding: 0.5rem 0.5rem; text-align: right; border: 1px solid #d1d5db; font-size: 0.75rem;">Rate ({{ $selectedClientCurrency }})</th>
                        <th style="padding: 0.5rem 0.5rem; text-align: right; border: 1px solid #d1d5db; font-size: 0.75rem;">Amount ({{ $selectedClientCurrency }})</th>
                    </tr>
                </thead>
                <tbody>${itemsHtml}</tbody>
            </table>

            <div style="display: flex; justify-content: flex-end;">
                <div style="min-width: 260px; padding: 0.4rem 0.5rem;">
                    <div style="display: flex; justify-content: space-between; padding: 0.25rem 0; border-bottom: 1px solid #e5e7eb;">
                        <span>Subtotal:</span><strong>${(subtotal - discountTotal).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}</strong>
                    </div>
                    ${taxRowsHtml}
                    <div style="display: flex; justify-content: space-between; padding: 0.25rem 0; font-size: 0.95rem; font-weight: 700; color: #111827;">
                        <span>Grand Total:</span><span>${grandTotal.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}</span>
                    </div>
                </div>
            </div>

            ${notes ? `<div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb; font-size: 0.78rem; color: #6b7280; white-space: pre-wrap;">${notes}</div>` : ''}

            ${terms.length ? `<div style="margin-top: 1rem; padding: 0.75rem 0; border-top: 1px solid #e5e7eb;"><h4 style="margin: 0 0 0.35rem 0; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em; color: #374151;">Terms & Conditions</h4><ul style="margin: 0; padding-left: 1.25rem; font-size: 0.78rem; line-height: 1.5; color: #4b5563; list-style: disc;">${terms.map(term => `<li style="margin-bottom: 0.25rem;">${term.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</li>`).join('')}</ul></div>` : ''}

            <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
                <div style="text-align: right; min-width: 220px;">
                    ${accountData.billing.signature ? `<img src="${accountData.billing.signature}" style="display:block; margin-left:auto; max-width:130px; max-height:52px; object-fit:contain; margin-bottom:0.25rem;">` : ''}
                    <div style="border-top: 1px solid #6b7280; padding-top: 0.3rem; font-size: 0.78rem; color: #374151; text-align:right;">
                        ${(accountData.billing.signatory || '').trim() || accountData.billing.name || accountData.name || 'Authorized Signatory'}
                    </div>
                </div>
            </div>
        `;
    }

    // Terms checkboxes
    document.getElementById('termsList').addEventListener('change', (e) => {
        if (e.target.classList.contains('term-checkbox')) {
            const allCheckboxes = document.querySelectorAll('.term-checkbox');
            const anyChecked = Array.from(allCheckboxes).some(cb => cb.checked);
            if (finalSubmitBtn) finalSubmitBtn.disabled = !anyChecked;
            if (createTaxInvoiceBtn) createTaxInvoiceBtn.disabled = !invoiceidInput.value;
            if (btnApplyTC) btnApplyTC.style.display = invoiceidInput.value ? 'inline-block' : 'none';
            
            updateInvoicePreview();
        }
    });

    if (btnApplyTC) {
        btnApplyTC.addEventListener('click', function () {
            const invoiceid = invoiceidInput.value;
            if (!invoiceid) {
                alert('Save the invoice first before applying terms.');
                return;
            }
            const selectedTerms = Array.from(document.querySelectorAll('.term-checkbox'))
                .filter(cb => cb.checked)
                .map(cb => cb.value.trim())
                .filter(Boolean);

            btnApplyTC.disabled = true;
            btnApplyTC.textContent = 'Applying...';

            fetch(`{{ url('invoices') }}/${invoiceid}/terms`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ terms: selectedTerms }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    appliedTerms = selectedTerms;
                    updateInvoicePreview();
                    btnApplyTC.textContent = 'Applied ✓';
                    btnApplyTC.style.background = '#d1fae5';
                    btnApplyTC.style.color = '#065f46';
                    setTimeout(() => {
                        btnApplyTC.textContent = 'Apply';
                        btnApplyTC.style.background = '';
                        btnApplyTC.style.color = '';
                        btnApplyTC.disabled = false;
                    }, 2000);
                } else {
                    throw new Error('Failed');
                }
            })
            .catch(() => {
                btnApplyTC.textContent = 'Apply';
                btnApplyTC.disabled = false;
                alert('Failed to apply terms. Please try again.');
            });
        });
    }

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
                    row.style.marginBottom = '0.55rem';
                    row.className = 'term-item-row';
                    row.innerHTML = `
                        <label class="custom-checkbox" style="display: flex; align-items: flex-start; gap: 0.45rem; cursor: pointer; margin-bottom: 0.2rem; width: 100%; max-width: 100%; box-sizing: border-box;">
                            <input type="checkbox" class="term-checkbox" checked data-tc-id="${term.id}" data-content="${term.content.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\"/g, '&quot;')}" value="${term.content.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\"/g, '&quot;')}" style="width: 14px; height: 14px; cursor: pointer; flex-shrink: 0;">
                            <div style="min-width: 0; width: 100%; box-sizing: border-box; word-break: break-word; overflow-wrap: anywhere; white-space: normal;">
                                <p style="margin: 0; font-size: 0.78rem; color: #4b5563; line-height: 1.45; word-break: break-word; overflow-wrap: anywhere; white-space: normal;">${term.content.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</p>
                            </div>
                        </label>
                    `;

                    termsList.prepend(row);
                    if (finalSubmitBtn) finalSubmitBtn.disabled = false;
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
        let prevUrl = "{{ route('invoices.create') }}?step=" + prevStep + "&invoice_for=" + encodeURIComponent(invoiceFor) + "&c=" + clientToken;
        if (hasOrderId) {
            const orderToken = encodeURIComponent(orderId);
            prevUrl += "&o=" + orderToken;
        }
        if (isTaxInvoice) {
            prevUrl += "&tax_invoice=1";
        }
        if (draftId) {
            prevUrl += "&d=" + encodeURIComponent(draftId);
        }
        window.location.href = prevUrl;
    });

    // Edit button
    document.getElementById('btnEditPreview')?.addEventListener('click', function() {
        const clientToken = encodeURIComponent(clientId);
        const editStep = invoiceFor === 'without_orders' ? 2 : 3;
        let editUrl = "{{ route('invoices.create') }}?step=" + editStep + "&invoice_for=" + encodeURIComponent(invoiceFor) + "&c=" + clientToken;
        if (hasOrderId) {
            const orderToken = encodeURIComponent(orderId);
            editUrl += "&o=" + orderToken;
        }
        if (isTaxInvoice) {
            editUrl += "&tax_invoice=1";
        }
        if (draftId) {
            editUrl += "&d=" + encodeURIComponent(draftId);
        }
        window.location.href = editUrl;
    });

    // Exit button
    document.getElementById('btnExit')?.addEventListener('click', function() {
        window.location.href = "{{ route('invoices.index') }}?c=" + encodeURIComponent(clientId);
    });

    // Digital Signed button
    digitalSignBtn?.addEventListener('click', function() {
        const invoiceid = invoiceidInput.value;
        if (!invoiceid) return;
        const base = "{{ url('invoices') }}/" + invoiceid + "/pdf";
        window.open(base + '?type=pi&signed=1', '_blank');
    });

    // Create Tax Invoice function
    function createTaxInvoice() {
        if (!invoiceidInput.value) {
            alert('Please create PI first.');
            return;
        }

        if (!confirm('This will generate a Tax Invoice number and mark this invoice as a Tax Invoice. Continue?')) {
            return;
        }

        fetch("{{ route('invoices.create-tax-invoice') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                invoiceid: invoiceidInput.value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                draftTiNumber = data.ti_number;
                updateHeaderNumberBadge();
                updateInvoicePreview();
                const base = "{{ url('invoices') }}/" + invoiceidInput.value + "/pdf";
                window.open(base + '?type=tax_invoice', '_blank');
            } else {
                alert(data.message || 'Failed to create tax invoice.');
            }
        })
        .catch(error => {
            console.error('Failed to create tax invoice:', error);
            alert('Failed to create tax invoice. Please try again.');
        });
    }

    // Initialize
    if (draftId) {
        updateDownloadButtons(draftId, null);
        if (btnApplyTC) btnApplyTC.style.display = 'inline-block';
    }
    loadItems();
})();
</script>
