@php
    $normalizeTaxState = static fn ($value) => preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $value)));
    $selectedInvoiceClient = $clients->firstWhere('clientid', request('c', request('clientid')));
    $selectedClientCurrency = optional($selectedInvoiceClient)->currency ?? 'INR';
    $selectedClientName = $selectedInvoiceClient ? ($selectedInvoiceClient->business_name ?? $selectedInvoiceClient->contact_name ?? 'Unknown Client') : 'No Client Selected';
    $selectedClientEmail = optional($selectedInvoiceClient)->email ?? '';
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

    <input type="hidden" name="clientid" value="{{ request('c', request('clientid', $invoice?->clientid ?? '')) }}">
    <input type="hidden" name="invoice_for" value="{{ request('invoice_for', $invoice?->invoice_for ?? '') }}">
    <input type="hidden" name="orderid" value="{{ request('o', request('orderid', '')) === '0' ? '' : request('o', request('orderid', '')) }}">
    <input type="hidden" name="invoiceid" id="step4_invoiceid" value="{{ request('d', '') }}">
    <input type="hidden" name="renewed_item_ids" id="step4_renewed_item_ids" value="">
    <input type="hidden" name="invoice_number" id="step4_invoice_number" value="{{ $isTaxInvoiceStep4 ? ($invoice?->ti_number ?: ($nextTaxInvoiceNumber ?? $nextInvoiceNumber)) : ($invoice?->pi_number ?? $nextInvoiceNumber) }}">
    <input type="hidden" name="issue_date" id="step4_issue_date" value="{{ date('Y-m-d') }}">
    <input type="hidden" name="due_date" id="step4_due_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}">
    <input type="hidden" name="items_data" id="step4_items_data" value="">
    <input type="hidden" name="currency_code" id="step4_currency_code" value="{{ $selectedClientCurrency }}">
    <input type="hidden" name="notes" id="step4_notes" value="">

    <div class="row g-3 align-items-start">
        <div class="col-12 col-md-3" style="min-width: 0;">
            <div class="panel-card" style="padding: 0.85rem; border: 1px solid #e5e7eb; background: #fff; position: relative; height: 100%; overflow: hidden;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem; padding-bottom: 0.35rem; border-bottom: 1px solid #e5e7eb;">
                    <div style="display: flex; align-items: center; gap: 0.45rem;">
                        <h5 style="margin: 0; font-size: 0.9rem; color: #111827;">{{ $isTaxInvoiceStep4 ? 'Tax T&C' : 'Proforma T&C' }}</h5>
                        {{-- <span id="tcTypeBadge" style="font-size: 0.68rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 999px; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                            {{ $isTaxInvoiceStep4 ? 'Tax T&C' : 'Proforma T&C' }}
                        </span> --}}
                    </div>
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
                    @php
                        $initialTermsForStep4 = $isTaxInvoiceStep4 ? ($billingTerms ?? collect()) : ($proformaTerms ?? collect());
                    @endphp
                    @foreach($initialTermsForStep4 as $term)
                    <div style="margin-bottom: 0.55rem; padding: 0; width: 100%; max-width: 100%; box-sizing: border-box;" class="term-item-row">
                        <label class="custom-checkbox" style="display: flex; align-items: flex-start; gap: 0.45rem; cursor: pointer; margin-bottom: 0.2rem; width: 100%; max-width: 100%; box-sizing: border-box;">
                            <input type="checkbox" class="term-checkbox" data-tc-id="{{ $term->tc_id }}" data-is-default="{{ (int) ($term->is_default ?? 0) }}" data-content="{{ $term->content }}" value="{{ $term->content }}" {{ !empty($term->is_default) ? 'checked' : '' }} style="width: 14px; height: 14px; cursor: pointer; flex-shrink: 0;">
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
                        <a id="btnDownloadPI" href="#" target="_blank" class="secondary-button" style="padding: 0.35rem 0.7rem; font-size: 0.8rem; display: none; align-items: center; gap: 0.35rem; text-decoration: none;">
                            <i class="fas fa-file-download"></i>Download PI
                        </a>
                        <button type="button" class="text-button" id="digitalSignBtn" disabled style="padding: 0.35rem 0.7rem; font-size: 0.8rem; opacity: 0.5;">
                            <i class="fas fa-signature"></i>Download Signed
                        </button>
                        <button type="button" id="createTaxInvoiceBtn" class="secondary-button" style="padding: 0.35rem 0.7rem; font-size: 0.8rem; display: none;">
                            <i class="fas fa-check-double" class="icon-spaced-sm"></i>Convert to Tax Invoice
                        </button>
                        <button type="button" id="btnDownloadTaxInvoice" class="secondary-button" style="padding: 0.35rem 0.7rem; font-size: 0.8rem; display: none;">
                            <i class="fas fa-file-invoice-dollar" class="icon-spaced-sm"></i>Download Tax Invoice
                        </button>
                        <button type="button" id="btnEditPreview" class="secondary-button" style="padding: 0.35rem 0.7rem; font-size: 0.8rem;">
                            <i class="fas fa-edit" class="icon-spaced-sm"></i>Edit
                        </button>
                    </div>
                </div>
                <div id="invoicePreviewContainer" style="padding: 1rem; background: #f5f5f5;">
                    <div id="previewContent" style="background: white; padding: 0; width: 100%; min-height: 640px; border: 1px solid #dddddd; border-radius: 8px; overflow: hidden;">
                        <iframe
                            id="invoicePdfPreviewFrame"
                            title="Invoice PDF Preview"
                            src="about:blank"
                            style="width: 100%; min-height: 640px; border: 0; background: #fff;"
                        ></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end align-items-center flex-wrap gap-2 mt-3">
        <button type="button" class="primary-button" id="btnSendEmail">
            <i class="fas fa-envelope me-2"></i>Send Email
        </button>
    </div>
</div>

<script>
(function() {
    const clientId = "{{ request('c', request('clientid', $invoice?->clientid ?? '')) }}";
    const invoiceFor = "{{ request('invoice_for', $invoice?->invoice_for ?? '') }}";
    const orderId = "{{ request('o', request('orderid', '')) }}";
    const draftId = "{{ request('d', '') }}";
    const isTaxInvoice = @json($isTaxInvoiceStep4);
    const hasOrderId = orderId && orderId !== '0';
    const btnBackToPrev = document.getElementById('btnBackToPrev');
    const finalSubmitBtn = document.getElementById('finalSubmitBtn');
    const createTaxInvoiceBtn = document.getElementById('createTaxInvoiceBtn');
    const digitalSignBtn = document.getElementById('digitalSignBtn');
    const btnDownloadPI = document.getElementById('btnDownloadPI');
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
    const tcTypeBadge = document.getElementById('tcTypeBadge');
    const currencyCodeInput = document.getElementById('step4_currency_code');
    const pdfVersionsList = document.getElementById('pdfVersionsList');
    const pdfVersionsMeta = document.getElementById('pdfVersionsMeta');
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
    let draftPiNumber = "{{ $invoice?->pi_number ?? '' }}";
    let draftTiNumber = "{{ $invoice?->ti_number ?? '' }}";
    let draftIssueDate = '';
    let draftDueDate = '';
    let draftNotes = '';
    let draftPoNumber = '';
    let draftPoDate = '';
    let appliedTerms = [];
    let currentTermType = isTaxInvoice ? 'billing' : 'proforma';
    const termsByType = {
        proforma: @json(($proformaTerms ?? collect())->map(fn ($term) => ['id' => $term->tc_id, 'content' => $term->content, 'is_default' => (int) ($term->is_default ?? 0)])->values()),
        billing: @json(($billingTerms ?? collect())->map(fn ($term) => ['id' => $term->tc_id, 'content' => $term->content, 'is_default' => (int) ($term->is_default ?? 0)])->values()),
    };
    const defaultTerms = Array.from(document.querySelectorAll('.term-checkbox'))
        .filter(cb => cb.dataset.isDefault === '1')
        .map(cb => cb.value.trim())
        .filter(Boolean);
    const fallbackPiNumber = "{{ $nextInvoiceNumber }}";
    const fallbackTiNumber = "{{ $nextTaxInvoiceNumber ?? $nextInvoiceNumber }}";

    function getDefaultTermsForType(type) {
        return (termsByType[type] || [])
            .filter(t => Number(t.is_default || 0) === 1)
            .map(t => String(t.content || '').trim())
            .filter(Boolean);
    }

    function normalizeDraftTermsByType(rawTerms) {
        if (!rawTerms || typeof rawTerms !== 'object') {
            return { proforma: [], billing: [] };
        }

        const hasBuckets = Object.prototype.hasOwnProperty.call(rawTerms, 'proforma')
            || Object.prototype.hasOwnProperty.call(rawTerms, 'billing');

        if (hasBuckets) {
            return {
                proforma: Array.isArray(rawTerms.proforma) ? rawTerms.proforma.map(t => String(t || '').trim()).filter(Boolean) : [],
                billing: Array.isArray(rawTerms.billing) ? rawTerms.billing.map(t => String(t || '').trim()).filter(Boolean) : [],
            };
        }

        const legacyTerms = Array.isArray(rawTerms) ? rawTerms.map(t => String(t || '').trim()).filter(Boolean) : [];
        return {
            proforma: legacyTerms,
            billing: [],
        };
    }

    function renderTermsList(type, selectedTerms = null) {
        if (!termsList) return;
        const terms = termsByType[type] || [];
        const defaults = getDefaultTermsForType(type);
        const chosen = Array.isArray(selectedTerms) ? selectedTerms : defaults;
        termsList.innerHTML = '';
        terms.forEach((term) => {
            const safeContent = String(term.content || '').trim();
            if (!safeContent) return;
            const checked = chosen.some(t => String(t).trim() === safeContent);
            const row = document.createElement('div');
            row.className = 'term-item-row';
            row.style.marginBottom = '0.55rem';
            row.style.padding = '0';
            row.style.width = '100%';
            row.style.maxWidth = '100%';
            row.style.boxSizing = 'border-box';
            row.innerHTML = `
                <label class="custom-checkbox" style="display: flex; align-items: flex-start; gap: 0.45rem; cursor: pointer; margin-bottom: 0.2rem; width: 100%; max-width: 100%; box-sizing: border-box;">
                    <input type="checkbox" class="term-checkbox" data-tc-id="${term.id}" data-is-default="${Number(term.is_default || 0)}" data-content="${safeContent.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\"/g, '&quot;')}" value="${safeContent.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\"/g, '&quot;')}" ${checked ? 'checked' : ''} style="width: 14px; height: 14px; cursor: pointer; flex-shrink: 0;">
                    <div style="min-width: 0; width: 100%; box-sizing: border-box; word-break: break-word; overflow-wrap: anywhere; white-space: normal;">
                        <p style="margin: 0; font-size: 0.78rem; color: #4b5563; line-height: 1.45; word-break: break-word; overflow-wrap: anywhere; white-space: normal;">${safeContent.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</p>
                    </div>
                </label>
            `;
            termsList.appendChild(row);
        });
        const anyChecked = Array.from(document.querySelectorAll('.term-checkbox')).some(cb => cb.checked);
        if (finalSubmitBtn) finalSubmitBtn.disabled = !anyChecked;
    }

    function syncTermsTypeWithInvoiceStage() {
        const nextType = (draftTiNumber || isTaxInvoice) ? 'billing' : 'proforma';
        if (nextType === currentTermType) {
            updateTcTypeBadge();
            return;
        }
        currentTermType = nextType;
        renderTermsList(currentTermType, getDefaultTermsForType(currentTermType));
        updateTcTypeBadge();
        updateInvoicePreview();
    }

    function updateTcTypeBadge() {
        if (!tcTypeBadge) return;
        const isBillingType = currentTermType === 'billing';
        tcTypeBadge.textContent = isBillingType ? 'Tax T&C' : 'Proforma T&C';
        tcTypeBadge.style.background = isBillingType ? '#ecfeff' : '#eff6ff';
        tcTypeBadge.style.color = isBillingType ? '#155e75' : '#1d4ed8';
        tcTypeBadge.style.borderColor = isBillingType ? '#a5f3fc' : '#bfdbfe';
    }

    function getHeaderDocumentNumber() {
        if (draftTiNumber) {
            return draftTiNumber;
        }
        if (draftTiNumber || isTaxInvoice) {
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
                syncTermsTypeWithInvoiceStage();

                const draftTermsByType = normalizeDraftTermsByType(data.draft.terms_by_type ?? data.draft.terms ?? []);
                const typedDraftTerms = currentTermType === 'billing'
                    ? draftTermsByType.billing
                    : draftTermsByType.proforma;
                const currentDefaults = getDefaultTermsForType(currentTermType);
                appliedTerms = typedDraftTerms.length > 0 ? typedDraftTerms : currentDefaults;
                renderTermsList(currentTermType, appliedTerms);

                const checkboxes = document.querySelectorAll('.term-checkbox');
                const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                if (finalSubmitBtn) finalSubmitBtn.disabled = !anyChecked;

                invoiceNumberInput.value = draftInvoiceNumber;
                invoiceidInput.value = data.draft.invoiceid || '';
                if (btnApplyTC && data.draft.invoiceid) btnApplyTC.style.display = 'inline-block';

                updateHeaderNumberBadge();

                updateDownloadButtons(data.draft.invoiceid, data.draft.invoice_number);
                loadPdfVersions(data.draft.invoiceid);

                updateTotals();
                updateInvoicePreview();
            } else {
                console.log('No draft found');
                syncTermsTypeWithInvoiceStage();
                const checkboxes = document.querySelectorAll('.term-checkbox');
                const currentDefaults = getDefaultTermsForType(currentTermType);
                checkboxes.forEach(cb => {
                    cb.checked = currentDefaults.some(t => t === cb.value.trim());
                });
                const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                if (finalSubmitBtn) finalSubmitBtn.disabled = !anyChecked;
                updateHeaderNumberBadge();
                loadPdfVersions(invoiceidInput.value || draftId);
                updateInvoicePreview();
            }
        })
        .catch(error => {
            console.error('Failed to load draft items:', error);
            syncTermsTypeWithInvoiceStage();
            const checkboxes = document.querySelectorAll('.term-checkbox');
            const currentDefaults = getDefaultTermsForType(currentTermType);
            checkboxes.forEach(cb => {
                cb.checked = currentDefaults.some(t => t === cb.value.trim());
            });
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            if (finalSubmitBtn) finalSubmitBtn.disabled = !anyChecked;
            updateHeaderNumberBadge();
            loadPdfVersions(invoiceidInput.value || draftId);
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
        syncTaxInvoiceButtons(invoiceid);
        if (digitalSignBtn) {
            digitalSignBtn.disabled = false;
            digitalSignBtn.style.opacity = '1';
            digitalSignBtn.onclick = () => window.open(base + '?type=pi&signed=1', '_blank');
        }
    }

    function renderPdfVersions(versions) {
        if (!pdfVersionsList) return;
        const list = Array.isArray(versions) ? versions : [];
        pdfVersionsList.innerHTML = '';

        if (!list.length) {
            const empty = document.createElement('span');
            empty.style.fontSize = '0.74rem';
            empty.style.color = '#9ca3af';
            empty.textContent = 'No saved versions yet';
            pdfVersionsList.appendChild(empty);
            if (pdfVersionsMeta) pdfVersionsMeta.textContent = '';
            return;
        }

        list.forEach((item) => {
            const chip = document.createElement('a');
            chip.href = item.url;
            chip.target = '_blank';
            chip.rel = 'noopener';
            chip.style.textDecoration = 'none';
            chip.style.padding = '0.2rem 0.55rem';
            chip.style.border = '1px solid #d1d5db';
            chip.style.borderRadius = '999px';
            chip.style.fontSize = '0.73rem';
            chip.style.fontWeight = '600';
            chip.style.color = '#374151';
            chip.style.background = '#f9fafb';
            chip.textContent = `${String(item.type || '').toUpperCase()} v${item.version}`;
            pdfVersionsList.appendChild(chip);
        });

        if (pdfVersionsMeta) {
            pdfVersionsMeta.textContent = `${list.length} version${list.length === 1 ? '' : 's'}`;
        }
    }

    function loadPdfVersions(invoiceid) {
        if (!invoiceid) {
            renderPdfVersions([]);
            return;
        }

        fetch(`{{ url('invoices') }}/${invoiceid}/pdf-versions`, {
            headers: { 'Accept': 'application/json' },
        })
            .then(r => r.json())
            .then(data => renderPdfVersions(data && data.ok ? data.versions : []))
            .catch(() => renderPdfVersions([]));
    }

    function syncTaxInvoiceButtons(invoiceid) {
        if (!createTaxInvoiceBtn || !btnDownloadTaxInvoice) return;
        if (!invoiceid) {
            createTaxInvoiceBtn.style.display = 'none';
            btnDownloadTaxInvoice.style.display = 'none';
            return;
        }

        const base = "{{ url('invoices') }}/" + invoiceid + "/pdf";
        if (draftTiNumber) {
            createTaxInvoiceBtn.style.display = 'none';
            btnDownloadTaxInvoice.style.display = 'inline-flex';
            btnDownloadTaxInvoice.onclick = function() {
                window.open(base + '?type=tax_invoice', '_blank');
            };
        } else {
            btnDownloadTaxInvoice.style.display = 'none';
            createTaxInvoiceBtn.style.display = 'inline-flex';
            createTaxInvoiceBtn.disabled = false;
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
        const previewFrame = document.getElementById('invoicePdfPreviewFrame');
        const invoiceid = invoiceidInput.value || draftId;

        if (!previewFrame) return;

        if (!invoiceid) {
            previewFrame.srcdoc = `
                <div style="display:flex;align-items:center;justify-content:center;min-height:640px;color:#6b7280;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
                    <div style="text-align:center;">
                        <i class="fas fa-info-circle" style="font-size:1.6rem; margin-bottom:0.5rem;"></i>
                        <p>Save the invoice draft to load PDF preview.</p>
                    </div>
                </div>
            `;
            return;
        }

        const type = (draftTiNumber || isTaxInvoice) ? 'tax_invoice' : 'pi';
        const base = "{{ url('invoices') }}/" + encodeURIComponent(invoiceid) + "/pdf";
        previewFrame.src = `${base}?type=${type}&preview=1&_t=${Date.now()}`;
    }

    // Terms checkboxes
    document.getElementById('termsList').addEventListener('change', (e) => {
        if (e.target.classList.contains('term-checkbox')) {
            const allCheckboxes = document.querySelectorAll('.term-checkbox');
            const anyChecked = Array.from(allCheckboxes).some(cb => cb.checked);
            if (finalSubmitBtn) finalSubmitBtn.disabled = !anyChecked;
            if (createTaxInvoiceBtn) createTaxInvoiceBtn.disabled = !invoiceidInput.value;
            if (btnApplyTC) btnApplyTC.style.display = invoiceidInput.value ? 'inline-block' : 'none';
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
                    loadPdfVersions(invoiceid);
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
                body: JSON.stringify({ content, type: currentTermType }),
            })
                .then(response => response.json().then(data => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok || !data.ok) {
                        throw new Error(data.message || 'Unable to save term.');
                    }

                    const term = data.term;
                    const savedType = (term && term.type) ? term.type : currentTermType;
                    if (!termsByType[savedType]) {
                        termsByType[savedType] = [];
                    }
                    termsByType[savedType].unshift({
                        id: term.id,
                        content: term.content,
                        is_default: 0,
                    });
                    currentTermType = savedType;
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
                })
                .catch(error => {
                    addTermError.textContent = error.message || 'Unable to save term.';
                    addTermError.style.display = 'block';
                });
        });
    }

    // Back button
    btnBackToPrev.addEventListener('click', function() {
        const currentDraftId = invoiceidInput.value || draftId;
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
        if (currentDraftId) {
            prevUrl += "&d=" + encodeURIComponent(currentDraftId);
        }
        window.location.href = prevUrl;
    });

    // Edit button
    document.getElementById('btnEditPreview')?.addEventListener('click', function() {
        const currentInvoiceId = invoiceidInput.value || draftId;
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
        if (currentInvoiceId) {
            editUrl += "&d=" + encodeURIComponent(currentInvoiceId);
        }
        window.location.href = editUrl;
    });

    // Send Email button
    document.getElementById('btnSendEmail')?.addEventListener('click', function() {
        const invoiceid = invoiceidInput.value;
        if (!invoiceid) {
            alert('Please save the invoice first.');
            return;
        }

        window.location.href = "{{ url('/invoices') }}/" + invoiceid + "/email-compose";
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
	            alert('Please create invoice first.');
	            return;
	        }

        if (!confirm('This will generate a Tax Invoice number and mark this invoice as a Tax Invoice. Continue?')) {
            return;
        }

	        fetch("{{ route('invoices.create-tax-invoice') }}", {
	            method: 'POST',
	            headers: {
	                'Content-Type': 'application/json',
	                'Accept': 'application/json',
	                'X-Requested-With': 'XMLHttpRequest',
	                'X-CSRF-TOKEN': csrfToken
	            },
	            body: JSON.stringify({
	                invoiceid: invoiceidInput.value
	            })
	        })
	        .then(async (response) => {
	            // Controller returns JSON only when wantsJson()/ajax(). We enforce that via headers above,
	            // but keep a safe fallback to avoid false "Failed" while conversion actually succeeds.
	            let data = null;
	            try {
	                data = await response.json();
	            } catch (e) {
	                data = null;
	            }
	            return { ok: response.ok, data };
	        })
		        .then(({ ok, data }) => {
		            if (ok && data && data.success) {
		                draftTiNumber = data.ti_number;
                        syncTermsTypeWithInvoiceStage();
		                updateHeaderNumberBadge();
		                syncTaxInvoiceButtons(invoiceidInput.value);
		                updateInvoicePreview();
                        loadPdfVersions(invoiceidInput.value);
		                const base = "{{ url('invoices') }}/" + invoiceidInput.value + "/pdf";
		                window.open(base + '?type=tax_invoice', '_blank');
		                return;
		            }

		            // If server returned non-JSON but status is OK, treat as success and reload draft state.
		            if (ok && !data) {
		                loadItems();
                        loadPdfVersions(invoiceidInput.value);
		                const base = "{{ url('invoices') }}/" + invoiceidInput.value + "/pdf";
		                window.open(base + '?type=tax_invoice', '_blank');
		                return;
		            }

		            alert((data && data.message) ? data.message : 'Failed to create tax invoice.');
		        })
		        .catch(error => {
		            console.error('Failed to create tax invoice:', error);
		            alert('Failed to create tax invoice. Please try again.');
		        });
		    }

    // Initialize
    createTaxInvoiceBtn?.addEventListener('click', createTaxInvoice);

    if (draftId) {
        updateDownloadButtons(draftId, null);
        if (btnApplyTC) btnApplyTC.style.display = 'inline-block';
        loadPdfVersions(draftId);
    }
    syncTaxInvoiceButtons(invoiceidInput.value || draftId);
    loadItems();
})();
</script>
