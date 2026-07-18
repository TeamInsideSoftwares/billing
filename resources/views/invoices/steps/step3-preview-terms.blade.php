@php
$invoiceDateBounds = $invoiceDateBounds ?? [
'default_issue_date' => '',
'default_due_date' => '',
];
$selectedInvoiceClient = $clients->firstWhere('clientid', request('c', request('clientid')));
$selectedClientCurrency = optional($selectedInvoiceClient)->currency ?? 'INR';
$selectedClientName = $selectedInvoiceClient
? $selectedInvoiceClient->business_name ?? ($selectedInvoiceClient->contact_name ?? 'Unknown Client')
: 'No Client Selected';
$selectedClientEmail = optional($selectedInvoiceClient)->email ?? '';
$isTaxInvoiceStep3 = request('tax_invoice', 0) == 1 || !empty($invoice?->ti_number);
$initialHeaderNumber = $isTaxInvoiceStep3
? ($invoice?->ti_number ?: $nextTaxInvoiceNumber ?? $nextInvoiceNumber)
: ($invoice?->pi_number ?: $nextInvoiceNumber);

$signatureUploadPath = optional($accountBillingDetail)->signature_upload;
$signatureUploadUrl = null;
if (!empty($signatureUploadPath)) {
if (str_starts_with($signatureUploadPath, 'http://') || str_starts_with($signatureUploadPath, 'https://')) {
$signatureUploadUrl = $signatureUploadPath;
} else {
$signatureUploadUrl = asset(str_starts_with($signatureUploadPath, 'storage/') ? $signatureUploadPath : 'storage/' .
ltrim($signatureUploadPath, '/'));
}
}

$accountDataArr = [
'name' => optional($account)->name,
'logo' => $account && $account->logo_path ? (str_starts_with($account->logo_path, 'http') ? $account->logo_path :
asset($account->logo_path)) : null,
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
],
];

$clientBilling = optional($selectedInvoiceClient)->billingDetail;
$clientDataArr = [
'name' => optional($selectedInvoiceClient)->business_name ?? (optional($selectedInvoiceClient)->contact_name ??
'Client'),
'contact_name' => optional($selectedInvoiceClient)->contact_name ?? '',
'email' => optional($selectedInvoiceClient)->email ?? '',
'phone' => optional($selectedInvoiceClient)->phone ?? '',
'billing' => [
'name' => optional($clientBilling)->business_name ?? (optional($selectedInvoiceClient)->business_name ?? ''),
'address_line_1' => optional($clientBilling)->address_line_1 ?? '',
'city' => optional($clientBilling)->city ?? '',
'state' => optional($clientBilling)->state ?? '',
'postal_code' => optional($clientBilling)->postal_code ?? '',
'country' => optional($clientBilling)->country ?? '',
'gstin' => optional($clientBilling)->gstin ?? '',
],
];

$termsByType = [
'proforma' => ($proformaTerms ?? collect())->map(function ($term) {
return [
'id' => $term->tc_id,
'content' => $term->content,
'is_default' => (int) ($term->is_default ?? 0),
];
})->values()->all(),
'billing' => ($billingTerms ?? collect())->map(function ($term) {
return [
'id' => $term->tc_id,
'content' => $term->content,
'is_default' => (int) ($term->is_default ?? 0),
];
})->values()->all(),
];
@endphp
<!-- Step 3: Preview & Terms (For Orders & Renewal, and Without Orders Step 3) -->
<div id="step3" data-account-data="{{ json_encode($accountDataArr) }}"
    data-client-data="{{ json_encode($clientDataArr) }}" data-is-tax-invoice="{{ $isTaxInvoiceStep3 ? '1' : '0' }}"
    data-terms-by-type="{{ json_encode($termsByType) }}">

    <input type="hidden" name="clientid" value="{{ request('c', request('clientid', $invoice?->clientid ?? '')) }}">
    <input type="hidden" name="orderid"
        value="{{ request('o', request('orderid', '')) === '0' ? '' : request('o', request('orderid', '')) }}">
    <input type="hidden" name="invoiceid" id="step3_invoiceid" value="{{ request('d', '') }}">
    <input type="hidden" name="renewed_item_ids" id="step3_renewed_item_ids" value="">
    <input type="hidden" name="invoice_number" id="step3_invoice_number"
        value="{{ $isTaxInvoiceStep3 ? ($invoice?->ti_number ?: $nextTaxInvoiceNumber ?? $nextInvoiceNumber) : $invoice?->pi_number ?? $nextInvoiceNumber }}">
    <input type="hidden" name="issue_date" id="step3_issue_date"
        value="{{ $invoiceDateBounds['default_issue_date'] ?? '' }}">
    <input type="hidden" name="due_date" id="step3_due_date" value="{{ $invoiceDateBounds['default_due_date'] ?? '' }}">
    <input type="hidden" name="items_data" id="step3_items_data" value="">
    <input type="hidden" name="currency_code" id="step3_currency_code" value="{{ $selectedClientCurrency }}">
    <input type="hidden" name="notes" id="step3_notes" value="">

    <div class="row g-2 align-items-stretch">
        <div class="col-12 col-md-3 min-w-0 d-flex flex-column gap-2">
            <!-- Client Info Card -->
            <div class="bg-secondary p-2 rounded-3 text-white">
                <div class="d-flex align-items-center gap-2">
                    <div class="col-12 col-md-12">
                        <select id="clientid" class="form-select" disabled>
                            <option value="">Choose client</option>
                            @foreach($clients as $client)
                            <option value="{{ $client->clientid }}" {{ (string)request('c',
                                request('clientid'))===(string)$client->clientid ? 'selected' : '' }}>
                                {{ $client->business_name ?? $client->contact_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- T&C Box -->
            <div class="bg-DarkLight p-2 rounded-3 flex-grow-1">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2 px-1 pt-1">
                    <h5 class="fw-semibold small lh-sm text-primary align-self-end mb-0">{{ $isTaxInvoiceStep3 ? 'Tax
                        Terms & Conditions' : 'Proforma
                        Terms & Conditions' }}</h5>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" id="btnAddTC"
                            class="btn btn-outline-primary bg-white text-primary fw-medium btn-sm d-inline-flex align-items-center gap-1 h-auto">
                            <i class="fas fa-plus"></i> Add T&C
                        </button>
                    </div>
                </div>
                <div class="modal fade" id="addTermModal" tabindex="-1">
                    <div class="modal-dialog modal-md modal-dialog-centered" style="max-width:600px">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header border-0 bg-white py-2">
                                <h5 class="modal-title fw-semibold">Terms & Conditions</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body bg-DarkLight p-3">
                                @csrf
                                <div class="row g-2">
                                    <div class="col-12 col-md-12">
                                        <textarea id="newTermContent" name="content" placeholder="Enter the term text"
                                            row="5" required class="form-control"></textarea>
                                    </div>
                                </div>
                                <div id="addTermError" class="text-danger small mt-2 d-none"></div>
                                <div class="d-flex align-items-center justify-content-end mt-2">
                                    <button type="button" id="saveTermBtn"
                                        class="btn btn-outline-primary bg-primary text-white fw-medium">
                                        Add T&C <i class="fas fa-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="termsList" class="termsListBlock p-3 bg-white rounded-3"
                    style="max-height:420px;overflow-y:auto;padding-right:0.2rem;">
                    @php
                    $initialTermsForStep3 = $isTaxInvoiceStep3
                    ? $billingTerms ?? collect()
                    : $proformaTerms ?? collect();
                    @endphp
                    @foreach ($initialTermsForStep3 as $term)
                    <div class="mb-2">
                        <label class="d-flex align-items-start gap-2" style="cursor:pointer;">
                            <input type="checkbox" class="form-check-input mt-1 flex-shrink-0"
                                data-tc-id="{{ $term->tc_id }}" data-is-default="{{ (int) ($term->is_default ?? 0) }}"
                                data-content="{{ e($term->content) }}" value="{{ e($term->content) }}" {{
                                !empty($term->is_default) ? 'checked' : '' }}>
                            <div style="word-break:break-word;overflow-wrap:anywhere;">
                                {!! $term->content !!}
                            </div>
                        </label>
                    </div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-end mt-2 px-1">
                    <button type="button" id="btnApplyTC"
                        class="btn btn-outline-primary bg-primary text-white fw-medium btn-sm d-none">
                        Apply Now <i class="fas fa-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-9 min-w-0">
            <!-- Invoice Preview -->
            <div class="bg-DarkLight p-2 rounded-3 h-100">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <h5 class="fw-semibold text-dark mb-0 align-items-center gap-2">
                        {{ $isTaxInvoiceStep3 ? 'Tax Invoice Preview' : 'Invoice Preview' }} <br /> <span
                            id="piNumberBadge" class="text-muted small lh-sm">{{
                            $initialHeaderNumber }}</span>
                    </h5>
                    <div class=" d-flex gap-2 align-items-center align-self-end flex-wrap justify-content-end">
                        <span class="small text-muted fw-medium">
                            <i class="fas fa-circle text-success small me-1"></i>
                            Live Preview
                        </span>
                        <a id="btnDownloadPI" href="#" target="_blank"
                            class="btn btn-outline-primary bg-white text-primary fw-medium btn-sm d-inline-flex align-items-center gap-1 d-none h-auto">
                            Download PI
                        </a>
                        <button type="button"
                            class="btn btn-outline-primary bg-white text-primary fw-medium btn-sm d-inline-flex align-items-center gap-1 h-auto"
                            id="digitalSignBtn" disabled>
                            Download Signed
                        </button>
                        <button type="button" id="createTaxInvoiceBtn"
                            class="btn btn-outline-primary bg-white text-primary fw-medium btn-sm d-inline-flex align-items-center gap-1 d-none h-auto">
                            Convert to Tax Invoice
                        </button>
                        <button type="button" id="btnDownloadTaxInvoice"
                            class="btn btn-outline-primary bg-white text-primary fw-medium btn-sm d-inline-flex align-items-center gap-1 d-none h-auto">
                            Download Tax Invoice
                        </button>
                        <button type="button" id="btnEditPreview"
                            class="btn btn-outline-primary bg-white text-primary fw-medium btn-sm d-inline-flex align-items-center gap-1 h-auto">
                            Edit
                        </button>
                        <button type="button"
                            class="btn btn-outline-primary bg-primary text-white fw-medium btn-sm d-inline-flex align-items-center gap-1 h-auto"
                            id="btnSendEmail">
                            Save &amp; Go To Email Compose <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>
                <div id="invoicePreviewContainer" class="card overflow-hidden">
                    <div id="previewContent" class="bg-white position-relative" style="min-height:650px;">
                        <div id="pdfLoader" class="position-absolute top-50 start-50 translate-middle text-center" style="z-index: 10;">
                            <div class="spinner-border text-primary mb-2" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="text-muted small fw-medium mb-0">Generating PDF...</p>
                        </div>
                        <iframe id="invoicePdfPreviewFrame" title="Invoice PDF Preview" src="about:blank"
                            class="w-100 border-0" style="min-height:650px; opacity: 0; transition: opacity 0.3s;" onload="this.style.opacity = '1'; document.getElementById('pdfLoader')?.classList.add('d-none');"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>


</div>

<script>
    (function () {
        const step3El = document.getElementById('step3');
        const clientId = "{{ request('c', request('clientid', $invoice?->clientid ?? '')) }}";
        const orderId = "{{ request('o', request('orderid', '')) }}";
        const draftId = "{{ request('d', '') }}";
        const justCreated = "{{ request('just_created', '') }}" === '1';
        const isTaxInvoice = step3El.getAttribute('data-is-tax-invoice') === '1';
        const hasOrderId = orderId && orderId !== '0';
        const btnBackToPrev = document.getElementById('btnBackToPrev');
        const createTaxInvoiceBtn = document.getElementById('createTaxInvoiceBtn');
        const digitalSignBtn = document.getElementById('digitalSignBtn');
        const btnDownloadPI = document.getElementById('btnDownloadPI');
        const btnDownloadTaxInvoice = document.getElementById('btnDownloadTaxInvoice');
        const termsList = document.getElementById('termsList');
        const btnAddTC = document.getElementById('btnAddTC');
        const btnApplyTC = document.getElementById('btnApplyTC');
        const addTermModal = document.getElementById('addTermModal');
        const btnCancelTermModal = document.getElementById('btnCancelTermModal');
        const saveTermBtn = document.getElementById('saveTermBtn');
        const newTermContent = document.getElementById('newTermContent');
        const addTermError = document.getElementById('addTermError');
        const addTermBootstrapModal = addTermModal ? new bootstrap.Modal(addTermModal) : null;
        const piNumberBadge = document.getElementById('piNumberBadge');
        const itemsDataInput = document.getElementById('step3_items_data');
        const invoiceNumberInput = document.getElementById('step3_invoice_number');
        const invoiceidInput = document.getElementById('step3_invoiceid');
        const renewedItemIdsInput = document.getElementById('step3_renewed_item_ids');
        const currencyCodeInput = document.getElementById('step3_currency_code');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const accountData = JSON.parse(step3El.getAttribute('data-account-data') || '{}');
        const clientData = JSON.parse(step3El.getAttribute('data-client-data') || '{}');
        const termsByType = JSON.parse(step3El.getAttribute('data-terms-by-type') || '{}');

        let invoiceItems = [];
        let draftInvoiceTitle = '';
        let draftInvoiceNumber = invoiceNumberInput.value || '{{ $invoice?->pi_number ?? $nextInvoiceNumber }}';
        let draftPiNumber = "{{ $invoice?->pi_number ?? '' }}";
        let draftTiNumber = "{{ $invoice?->ti_number ?? '' }}";
        let draftIssueDate = '';
        let draftDueDate = '';
        let draftNotes = '';
        let appliedTerms = [];
        let currentTermType = isTaxInvoice ? 'billing' : 'proforma';

        const defaultTerms = Array.from(document.querySelectorAll('[data-term]'))
            .filter(cb => cb.dataset.isDefault === '1')
            .map(cb => cb.value.trim())
            .filter(Boolean);
        const fallbackPiNumber = "{{ $nextInvoiceNumber }}";
        const fallbackTiNumber = "{{ $nextTaxInvoiceNumber ?? $nextInvoiceNumber }}";
        const TAX_READY_TOAST_KEY = 'invoice_tax_ready_toast';
        const INVOICE_COMPOSE_READY_TOAST_KEY = 'invoice_compose_ready_toast';

        function getDefaultTermsForType(type) {
            return (termsByType[type] || [])
                .filter(t => Number(t.is_default || 0) === 1)
                .map(t => String(t.content || '').trim())
                .filter(Boolean);
        }

        function normalizeDraftTermsByType(rawTerms) {
            if (!rawTerms || typeof rawTerms !== 'object') {
                return {
                    proforma: [],
                    billing: []
                };
            }

            const hasBuckets = Object.prototype.hasOwnProperty.call(rawTerms, 'proforma') ||
                Object.prototype.hasOwnProperty.call(rawTerms, 'billing');

            if (hasBuckets) {
                return {
                    proforma: Array.isArray(rawTerms.proforma) ? rawTerms.proforma.map(t => String(t || '').trim())
                        .filter(Boolean) : [],
                    billing: Array.isArray(rawTerms.billing) ? rawTerms.billing.map(t => String(t || '').trim())
                        .filter(Boolean) : [],
                };
            }

            const legacyTerms = Array.isArray(rawTerms) ? rawTerms.map(t => String(t || '').trim()).filter(
                Boolean) : [];
            return {
                proforma: legacyTerms,
                billing: [],
            };
        }

        function escapeHtmlAttr(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
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
                const escapedContent = escapeHtmlAttr(safeContent);
                const row = document.createElement('div');
                row.className = 'mb-2';
                row.innerHTML = `
                <label class="d-flex align-items-start gap-2" style="cursor:pointer;">
                    <input type="checkbox" class="form-check-input mt-1 flex-shrink-0" data-term="true" data-tc-id="${term.id}" data-is-default="${Number(term.is_default || 0)}" data-content="${escapedContent}" value="${escapedContent}" ${checked ? 'checked' : ''}>
                    <div class="text-dark" style="word-break:break-word;overflow-wrap:anywhere;">${safeContent}</div>
                </label>
            `;
                termsList.appendChild(row);
            });
        }

        function syncTermsTypeWithInvoiceStage() {
            const nextType = (draftTiNumber || isTaxInvoice) ? 'billing' : 'proforma';
            if (nextType === currentTermType) {
                return;
            }
            currentTermType = nextType;
            renderTermsList(currentTermType, getDefaultTermsForType(currentTermType));
            updateInvoicePreview();
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

        function syncRenewedItemIdsInput() {
            if (!renewedItemIdsInput) return;
            renewedItemIdsInput.value = '';
        }

        function openTermModal() {
            addTermError.classList.add('d-none');
            addTermError.textContent = '';
            newTermContent.value = '';
            if (addTermBootstrapModal) {
                addTermBootstrapModal.show();
            }
            if (window.tinymce) {
                const editor = tinymce.get('newTermContent');
                if (editor) {
                    editor.setContent('');
                    setTimeout(() => editor.focus(), 100);
                } else {
                    tinymce.init({
                        license_key: 'gpl',
                        selector: '#newTermContent',
                        forced_root_block: false,
                        invalid_elements: 'p',
                        menubar: false,
                        height: 500,
                        plugins: 'lists link table code autoresize',
                        toolbar: 'undo redo | blocks | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | removeformat code',
                        setup: function (editor) {
                            editor.on('change', function () {
                                editor.save();
                            });
                            editor.on('BeforeSetContent', function (e) {
                                e.content = e.content.replace(/<\/?p[^>]*>/gi, '');
                            });
                            editor.on('GetContent', function (e) {
                                e.content = e.content.replace(/<\/?p[^>]*>/gi, '');
                            });
                        },
                        init_instance_callback: function (editor) {
                            setTimeout(() => editor.focus(), 100);
                        }
                    });
                }
            } else {
                setTimeout(() => newTermContent.focus(), 100);
            }
        }

        function closeTermModal() {
            if (addTermBootstrapModal) {
                addTermBootstrapModal.hide();
            }
            if (window.tinymce) {
                const editor = tinymce.get('newTermContent');
                if (editor) {
                    editor.save();
                }
            }
        }

        // Load items
        function loadItems() {
            const routeUrl = "{{ route('invoices.get-draft', ['clientid' => '__CLIENTID__']) }}";
            const urlPath = routeUrl.startsWith('http') ? new URL(routeUrl).pathname : routeUrl;
            const draftUrl = new URL(urlPath.replace('__CLIENTID__', clientId), window.location.origin);
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

                        if (data.draft.issue_date) document.getElementById('step3_issue_date').value = data
                            .draft.issue_date;
                        if (data.draft.due_date) document.getElementById('step3_due_date').value = data.draft
                            .due_date;
                        if (data.draft.notes) document.getElementById('step3_notes').value = data.draft.notes;
                        if (data.draft.currency_code) {
                            currencyCodeInput.value = data.draft.currency_code;
                        }

                        syncTermsTypeWithInvoiceStage();

                        const draftTermsByType = normalizeDraftTermsByType(data.draft.terms_by_type ?? data
                            .draft.terms ?? []);
                        const typedDraftTerms = currentTermType === 'billing' ?
                            draftTermsByType.billing :
                            draftTermsByType.proforma;
                        const currentDefaults = getDefaultTermsForType(currentTermType);
                        appliedTerms = typedDraftTerms.length > 0 ? typedDraftTerms : currentDefaults;
                        renderTermsList(currentTermType, appliedTerms);

                        invoiceNumberInput.value = draftInvoiceNumber;
                        invoiceidInput.value = data.draft.invoiceid || '';
                        if (btnApplyTC && data.draft.invoiceid) btnApplyTC.classList.remove('d-none');

                        updateHeaderNumberBadge();
                        syncRenewedItemIdsInput();

                        updateDownloadButtons(data.draft.invoiceid);

                        updateTotals();
                        updateInvoicePreview();
                    } else {
                        console.log('No draft found');
                        syncTermsTypeWithInvoiceStage();
                        const checkboxes = document.querySelectorAll('[data-term]');
                        const currentDefaults = getDefaultTermsForType(currentTermType);
                        checkboxes.forEach(cb => {
                            cb.checked = currentDefaults.some(t => t === cb.value.trim());
                        });
                        updateHeaderNumberBadge();
                        syncRenewedItemIdsInput();
                        updateInvoicePreview();
                    }
                })
                .catch(error => {
                    console.error('Failed to load draft items:', error);
                    syncTermsTypeWithInvoiceStage();
                    const checkboxes = document.querySelectorAll('[data-term]');
                    const currentDefaults = getDefaultTermsForType(currentTermType);
                    checkboxes.forEach(cb => {
                        cb.checked = currentDefaults.some(t => t === cb.value.trim());
                    });
                    updateHeaderNumberBadge();
                    syncRenewedItemIdsInput();
                    updateInvoicePreview();
                });
        }

        function roundTaxUp(value) {
            return Math.ceil(Math.max(0, Number(value) || 0));
        }

        function roundDiscountDown(value) {
            return Math.floor(Math.max(0, Number(value) || 0));
        }

        function updateDownloadButtons(invoiceid) {
            if (!invoiceid) return;
            const baseRoute = "{{ url('invoices') }}";
            const basePath = baseRoute.startsWith('http') ? new URL(baseRoute).pathname : baseRoute;
            const base = basePath + "/" + invoiceid + "/pdf";
            if (btnDownloadPI) {
                btnDownloadPI.href = base + '?type=pi';
                btnDownloadPI.classList.remove('d-none');
            }
            syncTaxInvoiceButtons(invoiceid);
            if (digitalSignBtn) {
                digitalSignBtn.disabled = false;
                digitalSignBtn.style.opacity = '1';
                digitalSignBtn.onclick = () => window.open(base + '?type=pi&signed=1', '_blank');
            }
        }

        function syncTaxInvoiceButtons(invoiceid) {
            if (!createTaxInvoiceBtn || !btnDownloadTaxInvoice) return;
            if (!invoiceid) {
                createTaxInvoiceBtn.classList.add('d-none');
                btnDownloadTaxInvoice.classList.add('d-none');
                return;
            }

            const baseRoute = "{{ url('invoices') }}";
            const basePath = baseRoute.startsWith('http') ? new URL(baseRoute).pathname : baseRoute;
            const base = basePath + "/" + invoiceid + "/pdf";
            if (draftTiNumber) {
                createTaxInvoiceBtn.classList.add('d-none');
                btnDownloadTaxInvoice.classList.remove('d-none');
                btnDownloadTaxInvoice.onclick = function () {
                    window.open(base + '?type=tax_invoice', '_blank');
                };
            } else {
                btnDownloadTaxInvoice.classList.add('d-none');
                createTaxInvoiceBtn.classList.remove('d-none');
                createTaxInvoiceBtn.disabled = false;
            }
        }

        function updateTotals() {
            let subtotal = 0,
                taxTotal = 0,
                discountTotal = 0;
            invoiceItems.forEach(item => {
                const lineTotal = parseFloat(item.line_total || 0);
                const discountPercent = Math.max(0, Math.min(100, parseFloat(item.discount_percent || 0)));
                const discountedAmount = Math.max(0, lineTotal - ((lineTotal * discountPercent) / 100));
                const lineDiscount = roundDiscountDown(Math.max(0, lineTotal - discountedAmount));
                subtotal += lineTotal;
                discountTotal += lineDiscount;
                taxTotal += roundTaxUp(discountedAmount * (parseFloat(item.tax_rate || 0) / 100));
            });

            discountTotal = roundDiscountDown(discountTotal);
            taxTotal = roundTaxUp(taxTotal);
        }

        function updateInvoicePreview() {
            const previewFrame = document.getElementById('invoicePdfPreviewFrame');
            const loader = document.getElementById('pdfLoader');
            const invoiceid = invoiceidInput.value || draftId;

            if (!previewFrame) return;

            if (loader) loader.classList.remove('d-none');
            previewFrame.style.opacity = '0';

            if (!invoiceid) {
                previewFrame.srcdoc = `
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <div class="d-flex align-items-center justify-content-center text-secondary" style="min-height:640px;font-family:system-ui,sans-serif;">
                    <div class="text-center">
                        <i class="fas fa-info-circle d-block mb-2" style="font-size:1.6rem;"></i>
                        <p class="mb-0">Save the invoice draft to load PDF preview.</p>
                    </div>
                </div>
            `;
                return;
            }

            const type = (draftTiNumber || isTaxInvoice) ? 'tax_invoice' : 'pi';
            const baseRoute = "{{ url('invoices') }}";
            const basePath = baseRoute.startsWith('http') ? new URL(baseRoute).pathname : baseRoute;
            const base = basePath + "/" + encodeURIComponent(invoiceid) + "/pdf";
            previewFrame.src = `${base}?type=${type}&preview=1&_t=${Date.now()}`;
        }

        // Terms checkboxes
        document.getElementById('termsList').addEventListener('change', (e) => {
            if (e.target.matches('[data-term]')) {
                const allCheckboxes = document.querySelectorAll('[data-term]');
                if (createTaxInvoiceBtn) createTaxInvoiceBtn.disabled = !invoiceidInput.value;
                if (btnApplyTC) btnApplyTC.classList.toggle('d-none', !invoiceidInput.value);
            }
        });

        if (btnApplyTC) {
            btnApplyTC.addEventListener('click', function () {
                const invoiceid = invoiceidInput.value;
                if (!invoiceid) {
                    alert('Save the invoice first before applying terms.');
                    return;
                }
                const selectedTerms = Array.from(document.querySelectorAll('[data-term]'))
                    .filter(cb => cb.checked)
                    .map(cb => cb.value.trim())
                    .filter(Boolean);

                btnApplyTC.disabled = true;
                btnApplyTC.innerHTML = 'Applying... <i class="fas fa-spinner fa-spin ms-1"></i>';

                const applyTermsRoute = `{{ url('invoices') }}/${invoiceid}/terms`;
                const applyTermsPath = applyTermsRoute.startsWith('http') ? new URL(applyTermsRoute).pathname : applyTermsRoute;
                fetch(applyTermsPath, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        terms: selectedTerms,
                        renewed_item_ids: renewedItemIdsInput?.value || '[]',
                    }),
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.ok) {
                            appliedTerms = selectedTerms;
                            updateInvoicePreview();
                            btnApplyTC.innerHTML = 'Applied ✓';
                            btnApplyTC.style.background = '#d1fae5';
                            btnApplyTC.style.color = '#065f46';
                            setTimeout(() => {
                                btnApplyTC.innerHTML = 'Apply Now <i class="fas fa-arrow-right ms-1"></i>';
                                btnApplyTC.style.background = '';
                                btnApplyTC.style.color = '';
                                btnApplyTC.disabled = false;
                            }, 2000);
                        } else {
                            throw new Error('Failed');
                        }
                    })
                    .catch(() => {
                        btnApplyTC.innerHTML = 'Apply <i class="fas fa-arrow-right ms-1"></i>';
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

        if (saveTermBtn) {
            saveTermBtn.addEventListener('click', function () {
                if (window.tinymce) {
                    tinymce.triggerSave();
                }
                const content = newTermContent.value.trim();

                if (!content) {
                    addTermError.textContent = 'Please enter the term content.';
                    addTermError.classList.remove('d-none');
                    return;
                }

                addTermError.classList.add('d-none');
                addTermError.textContent = '';

                const storeTermRoute = "{{ route('invoices.terms.billing.store') }}";
                const storeTermPath = storeTermRoute.startsWith('http') ? new URL(storeTermRoute).pathname : storeTermRoute;
                fetch(storeTermPath, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        content,
                        type: currentTermType
                    }),
                })
                    .then(response => response.json().then(data => ({
                        ok: response.ok,
                        data
                    })))
                    .then(({
                        ok,
                        data
                    }) => {
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
                        const escapedContent = escapeHtmlAttr(term.content);
                        const row = document.createElement('div');
                        row.style.marginBottom = '0.55rem';
                        row.className = 'mb-2';
                        row.innerHTML = `
                        <label class="d-flex align-items-start gap-2" style="cursor:pointer;">
                            <input type="checkbox" class="form-check-input mt-1 flex-shrink-0" data-term="true" checked data-tc-id="${term.id}" data-content="${escapedContent}" value="${escapedContent}">
                            <div class="text-dark" style="word-break:break-word;overflow-wrap:anywhere;">${term.content}</div>
                        </label>
                    `;

                        termsList.prepend(row);
                        closeTermModal();
                    })
                    .catch(error => {
                        addTermError.textContent = error.message || 'Unable to save term.';
                        addTermError.classList.remove('d-none');
                    });
            });
        }

        // Back button
        btnBackToPrev?.addEventListener('click', function () {
            const currentDraftId = invoiceidInput.value || draftId;
            const prevStep = 2;
            const clientToken = encodeURIComponent(clientId);
            const createRoute = "{{ route('invoices.create') }}";
            const createPath = createRoute.startsWith('http') ? new URL(createRoute).pathname : createRoute;
            let prevUrl = createPath + "?step=" + prevStep + "&c=" + clientToken;
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
        document.getElementById('btnEditPreview')?.addEventListener('click', function () {
            const currentInvoiceId = invoiceidInput.value || draftId;
            const clientToken = encodeURIComponent(clientId);
            const editStep = 2;
            const createRoute = "{{ route('invoices.create') }}";
            const createPath = createRoute.startsWith('http') ? new URL(createRoute).pathname : createRoute;
            let editUrl = createPath + "?step=" + editStep + "&c=" + clientToken;
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
        document.getElementById('btnSendEmail')?.addEventListener('click', function () {
            const invoiceid = invoiceidInput.value;
            if (!invoiceid) {
                alert('Please save the invoice first.');
                return;
            }

            const composeRoute = "{{ url('/invoices') }}";
            const composePath = composeRoute.startsWith('http') ? new URL(composeRoute).pathname : composeRoute;
            const composeUrl = new URL(composePath + "/" + invoiceid + "/email-compose", window.location.origin);
            if (justCreated) {
                try {
                    window.localStorage.setItem(INVOICE_COMPOSE_READY_TOAST_KEY,
                        'Invoice created. Compose message and send it now.');
                } catch (e) {
                    console.warn('Unable to persist compose-ready toast state', e);
                }
            }
            if ((renewedItemIdsInput?.value || '').trim() !== '') {
                composeUrl.searchParams.set('renewed_item_ids', renewedItemIdsInput.value);
            }
            window.location.href = composeUrl.toString();
        });

        // Digital Signed button
        digitalSignBtn?.addEventListener('click', function () {
            const invoiceid = invoiceidInput.value;
            if (!invoiceid) return;
            const baseRoute = "{{ url('invoices') }}";
            const basePath = baseRoute.startsWith('http') ? new URL(baseRoute).pathname : baseRoute;
            const base = basePath + "/" + invoiceid + "/pdf";
            window.open(base + '?type=pi&signed=1', '_blank');
        });

        // Create Tax Invoice function
        async function createTaxInvoice() {
            if (!invoiceidInput.value) {
                alert('Please create invoice first.');
                return;
            }

            const confirmed = await window.appConfirm(
                'This will generate a Tax Invoice number and mark this invoice as a Tax Invoice. Continue?', {
                title: 'Create Tax Invoice?',
                icon: 'warning',
                confirmButtonText: 'Continue',
                cancelButtonText: 'Cancel',
            });
            if (!confirmed) {
                return;
            }
            const createTaxRoute = "{{ route('invoices.create-tax-invoice') }}";
            const createTaxPath = createTaxRoute.startsWith('http') ? new URL(createTaxRoute).pathname : createTaxRoute;
            fetch(createTaxPath, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    invoiceid: invoiceidInput.value,
                }),
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
                    return {
                        ok: response.ok,
                        data
                    };
                })
                .then(({
                    ok,
                    data
                }) => {
                    if (ok && data && data.success) {
                        draftTiNumber = data.ti_number;
                        try {
                            const toastMessage = data.ti_number ?
                                ('Tax Invoice ready: ' + data.ti_number) :
                                'Tax Invoice ready.';
                            window.localStorage.setItem(TAX_READY_TOAST_KEY, toastMessage);
                        } catch (e) {
                            console.warn('Unable to persist tax-ready toast state', e);
                        }
                        syncTermsTypeWithInvoiceStage();
                        updateHeaderNumberBadge();
                        syncTaxInvoiceButtons(invoiceidInput.value);
                        updateInvoicePreview();
                        if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                            return;
                        }
                        const fallbackClient = encodeURIComponent(clientId || '');
                        const fallbackDraft = encodeURIComponent(invoiceidInput.value || '');
                        const createRoute = "{{ route('invoices.create') }}";
                        const createPath = createRoute.startsWith('http') ? new URL(createRoute).pathname : createRoute;
                        window.location.href = createPath + "?step=2&tax_invoice=1&c=" +
                            fallbackClient + "&d=" + fallbackDraft;
                        return;
                    }

                    // If server returned non-JSON but status is OK, route to Step 2 tax edit flow.
                    if (ok && !data) {
                        loadItems();
                        try {
                            window.localStorage.setItem(TAX_READY_TOAST_KEY, 'Tax Invoice ready.');
                        } catch (e) {
                            console.warn('Unable to persist tax-ready toast state', e);
                        }
                        const fallbackClient = encodeURIComponent(clientId || '');
                        const fallbackDraft = encodeURIComponent(invoiceidInput.value || '');
                        const createRoute = "{{ route('invoices.create') }}";
                        const createPath = createRoute.startsWith('http') ? new URL(createRoute).pathname : createRoute;
                        window.location.href = createPath + "?step=2&tax_invoice=1&c=" +
                            fallbackClient + "&d=" + fallbackDraft;
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
            updateDownloadButtons(draftId);
            if (btnApplyTC) btnApplyTC.classList.remove('d-none');
        }
        syncTaxInvoiceButtons(invoiceidInput.value || draftId);
        syncRenewedItemIdsInput();
        loadItems();
    })();
</script>
