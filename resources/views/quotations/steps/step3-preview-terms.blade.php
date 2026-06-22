@php
$quotationDateBounds = $quotationDateBounds ?? [
    'default_issue_date' => '',
    'default_due_date' => '',
];
$selectedQuotationClient = $clients->firstWhere('clientid', request('c', request('clientid', $draftQuotation?->clientid ?? '')));
$selectedClientCurrency = optional($selectedQuotationClient)->currency ?? 'INR';
$selectedClientName = $selectedQuotationClient
    ? $selectedQuotationClient->business_name ?? ($selectedQuotationClient->contact_name ?? 'Unknown Client')
    : 'No Client Selected';
$selectedClientEmail = optional($selectedQuotationClient)->email ?? '';
$initialHeaderNumber = $draftQuotation?->quo_number ?: $nextQuotationNumber;

$termsByType = [
    'quotation' => ($quotationTerms ?? collect())->map(function ($term) {
        return [
            'id' => $term->tc_id,
            'content' => $term->content,
            'is_default' => (int) ($term->is_default ?? 0),
        ];
    })->values()->all(),
];

$clientBilling = optional($selectedQuotationClient)->billingDetail;
$clientDataArr = [
    'name' => optional($selectedQuotationClient)->business_name ?? (optional($selectedQuotationClient)->contact_name ?? 'Client'),
    'contact_name' => optional($selectedQuotationClient)->contact_name ?? '',
    'email' => optional($selectedQuotationClient)->email ?? '',
    'phone' => optional($selectedQuotationClient)->phone ?? '',
    'billing' => [
        'name' => optional($clientBilling)->business_name ?? (optional($selectedQuotationClient)->business_name ?? ''),
        'address_line_1' => optional($clientBilling)->address_line_1 ?? '',
        'city' => optional($clientBilling)->city ?? '',
        'state' => optional($clientBilling)->state ?? '',
        'postal_code' => optional($clientBilling)->postal_code ?? '',
        'country' => optional($clientBilling)->country ?? '',
        'gstin' => optional($clientBilling)->gstin ?? '',
    ],
];
@endphp

<div id="step3"
    data-client-data="{{ json_encode($clientDataArr) }}"
    data-terms-by-type="{{ json_encode($termsByType) }}">

    <form method="POST" action="{{ route('quotations.store') }}" id="quotationForm" class="mainForm">
        @csrf
        <input type="hidden" name="clientid" value="{{ request('c', request('clientid', $draftQuotation?->clientid ?? '')) }}">
        <input type="hidden" name="quotationid" id="step3_quotationid" value="{{ request('d', $draftQuotation?->quotationid ?? '') }}">
        <input type="hidden" name="quo_number" id="step3_quo_number" value="{{ $draftQuotation?->quo_number ?? $nextQuotationNumber }}">
        <input type="hidden" name="quo_title" id="step3_quo_title" value="{{ $draftQuotation?->quo_title ?? '' }}">
        <input type="hidden" name="issue_date" id="step3_issue_date" value="{{ $draftQuotation?->issue_date?->format('Y-m-d') ?? ($quotationDateBounds['default_issue_date'] ?? '') }}">
        <input type="hidden" name="due_date" id="step3_due_date" value="{{ $draftQuotation?->due_date?->format('Y-m-d') ?? ($quotationDateBounds['default_due_date'] ?? '') }}">
        <input type="hidden" name="items_data" id="step3_items_data" value="">
        <input type="hidden" name="notes" id="step3_notes" value="{{ $draftQuotation?->notes ?? '' }}">
        <input type="hidden" name="terms" id="step3_terms" value="[]">

        <div class="row g-2 align-items-stretch">
            <div class="col-12 col-md-3 min-w-0 d-flex flex-column gap-2">
                <!-- Client Info Card -->
                <div class="bg-secondary p-2 rounded-3 text-white">
                    <div class="d-flex align-items-center gap-2">
                        <div class="col-12 col-md-12">
                            <select id="clientid" class="form-select" disabled>
                                <option value="">Choose client</option>
                                @foreach($clients as $client)
                                <option value="{{ $client->clientid }}" {{ (string)request('c', request('clientid', $draftQuotation?->clientid ?? ''))===(string)$client->clientid ? 'selected' : '' }}>
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
                        <h5 class="fw-semibold small lh-sm text-primary align-self-end mb-0">Quotation Terms & Conditions</h5>
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
                                    <div class="row g-2">
                                        <div class="col-12 col-md-12">
                                            <textarea id="newTermContent" name="newTermContent" placeholder="Enter the term text" rows="5" class="form-control"></textarea>
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
                        <!-- Populate dynamically or via backend loop fallback -->
                        @foreach (($quotationTerms ?? collect()) as $term)
                        <div class="mb-2">
                            <label class="d-flex align-items-start gap-2" style="cursor:pointer;">
                                <input type="checkbox" class="form-check-input mt-1 flex-shrink-0"
                                    data-term="true"
                                    data-tc-id="{{ $term->tc_id }}"
                                    data-is-default="{{ (int) ($term->is_default ?? 0) }}"
                                    data-content="{{ e($term->content) }}"
                                    value="{{ e($term->content) }}"
                                    {{ !empty($term->is_default) ? 'checked' : '' }}>
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
                <!-- Quotation Preview -->
                <div class="bg-DarkLight p-2 rounded-3 h-100">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <h5 class="fw-semibold text-dark mb-0 align-items-center gap-2">
                            Quotation PDF Preview <br />
                            <span id="quoNumberBadge" class="text-muted small lh-sm">{{ $initialHeaderNumber }}</span>
                        </h5>
                        <div class="d-flex gap-2 align-items-center align-self-end flex-wrap justify-content-end">
                            <span class="small text-muted fw-medium">
                                <i class="fas fa-circle text-success small me-1"></i>
                                Live Preview
                            </span>
                            <a id="btnDownloadQuotationPdf" href="#" target="_blank"
                                class="btn btn-outline-primary bg-white text-primary fw-medium btn-sm d-inline-flex align-items-center gap-1 d-none h-auto">
                                Download Quotation PDF
                            </a>
                            <button type="button" id="btnEditPreview"
                                class="btn btn-outline-primary bg-white text-primary fw-medium btn-sm d-inline-flex align-items-center gap-1 h-auto">
                                Edit
                            </button>
                            <button type="submit"
                                class="btn btn-outline-primary bg-primary text-white fw-medium btn-sm d-inline-flex align-items-center gap-1 h-auto"
                                id="createQuotationBtn">
                                Save &amp; Go To Email Compose <i class="fas fa-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>
                    <div id="quotationPreviewContainer" class="card overflow-hidden">
                        <div id="previewContent" class="bg-white" style="min-height:650px;">
                            <iframe id="quotationPdfPreviewFrame" title="Quotation PDF Preview" src="about:blank"
                                class="w-100 border-0" style="min-height:650px;"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    (function () {
        const step3El = document.getElementById('step3');
        const clientId = "{{ request('c', request('clientid', $draftQuotation?->clientid ?? '')) }}";
        const draftId = "{{ request('d', $draftQuotation?->quotationid ?? '') }}";
        const btnDownloadQuotationPdf = document.getElementById('btnDownloadQuotationPdf');
        const termsList = document.getElementById('termsList');
        const btnAddTC = document.getElementById('btnAddTC');
        const btnApplyTC = document.getElementById('btnApplyTC');
        const addTermModal = document.getElementById('addTermModal');
        const saveTermBtn = document.getElementById('saveTermBtn');
        const newTermContent = document.getElementById('newTermContent');
        const addTermError = document.getElementById('addTermError');
        const addTermBootstrapModal = addTermModal ? new bootstrap.Modal(addTermModal) : null;
        const quoNumberBadge = document.getElementById('quoNumberBadge');
        
        const itemsDataInput = document.getElementById('step3_items_data');
        const quotationNumberInput = document.getElementById('step3_quo_number');
        const quotationIdHidden = document.getElementById('step3_quotationid');
        const termsHiddenInput = document.getElementById('step3_terms');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const termsByType = JSON.parse(step3El.getAttribute('data-terms-by-type') || '{}');
        const serverDraft = @json($draftQuotation);

        const quotationItems = serverDraft && Array.isArray(serverDraft.items) ? serverDraft.items : [];
        if (itemsDataInput) {
            itemsDataInput.value = JSON.stringify(quotationItems);
        }

        let draftQuotationNumber = quotationNumberInput.value || '{{ $draftQuotation?->quo_number ?? $nextQuotationNumber }}';
        let appliedTerms = [];

        function getDefaultTerms() {
            return (termsByType['quotation'] || [])
                .filter(t => Number(t.is_default || 0) === 1)
                .map(t => String(t.content || '').trim())
                .filter(Boolean);
        }

        function escapeHtmlAttr(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function renderTermsList(selectedTerms = null) {
            if (!termsList) return;
            const terms = termsByType['quotation'] || [];
            const defaults = getDefaultTerms();
            const chosen = Array.isArray(selectedTerms) ? selectedTerms : defaults;
            termsList.innerHTML = '';
            
            if (terms.length === 0) {
                termsList.innerHTML = '<div class="small text-muted">No quotation T&amp;C found. Click `Add T&C` to create one.</div>';
                return;
            }

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
            syncTermsHiddenInput();
        }

        function syncTermsHiddenInput() {
            const chosen = Array.from(document.querySelectorAll('[data-term]'))
                .filter(cb => cb.checked)
                .map(cb => cb.value.trim())
                .filter(Boolean);
            if (termsHiddenInput) {
                termsHiddenInput.value = JSON.stringify(chosen);
            }
        }

        function getHeaderDocumentNumber() {
            return draftQuotationNumber || quotationNumberInput.value || '{{ $nextQuotationNumber }}';
        }

        function updateHeaderNumberBadge() {
            if (quoNumberBadge) {
                quoNumberBadge.textContent = getHeaderDocumentNumber();
            }
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
                        menubar: false,
                        height: 220,
                        plugins: 'lists link table code autoresize',
                        toolbar: 'undo redo | blocks | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | removeformat code',
                        setup: function (editor) {
                            editor.on('change', function () {
                                editor.save();
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

        function updateDownloadButtons(quotationId) {
            if (!quotationId) return;
            const baseRoute = "{{ url('quotations') }}";
            const basePath = baseRoute.startsWith('http') ? new URL(baseRoute).pathname : baseRoute;
            const base = basePath + "/" + quotationId + "/pdf";
            if (btnDownloadQuotationPdf) {
                btnDownloadQuotationPdf.href = base;
                btnDownloadQuotationPdf.classList.remove('d-none');
            }
        }

        function updateQuotationPreview() {
            const previewFrame = document.getElementById('quotationPdfPreviewFrame');
            const quotationId = quotationIdHidden.value || draftId;

            if (!previewFrame) return;

            if (!quotationId) {
                previewFrame.srcdoc = `
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <div class="d-flex align-items-center justify-content-center text-secondary" style="min-height:640px;font-family:system-ui,sans-serif;">
                    <div class="text-center">
                        <i class="fas fa-info-circle d-block mb-2" style="font-size:1.6rem;"></i>
                        <p class="mb-0">Save the quotation draft to load PDF preview.</p>
                    </div>
                </div>
            `;
                return;
            }

            const baseRoute = "{{ url('quotations') }}";
            const basePath = baseRoute.startsWith('http') ? new URL(baseRoute).pathname : baseRoute;
            const base = basePath + "/" + encodeURIComponent(quotationId) + "/pdf";
            previewFrame.src = `${base}?preview=1&_t=${Date.now()}`;
        }

        // Terms checkboxes
        document.getElementById('termsList').addEventListener('change', (e) => {
            if (e.target.matches('[data-term]')) {
                syncTermsHiddenInput();
                if (btnApplyTC) btnApplyTC.classList.toggle('d-none', !quotationIdHidden.value);
            }
        });

        if (btnApplyTC) {
            btnApplyTC.addEventListener('click', function () {
                const quotationId = quotationIdHidden.value;
                if (!quotationId) {
                    alert('Save the quotation first before applying terms.');
                    return;
                }
                const selectedTerms = Array.from(document.querySelectorAll('[data-term]'))
                    .filter(cb => cb.checked)
                    .map(cb => cb.value.trim())
                    .filter(Boolean);

                btnApplyTC.disabled = true;
                btnApplyTC.innerHTML = 'Applying... <i class="fas fa-spinner fa-spin ms-1"></i>';

                const applyTermsRoute = `{{ url('quotations') }}/${quotationId}/terms`;
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
                    }),
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.ok) {
                            appliedTerms = selectedTerms;
                            updateQuotationPreview();
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
                        btnApplyTC.innerHTML = 'Apply Now <i class="fas fa-arrow-right ms-1"></i>';
                        btnApplyTC.disabled = false;
                        alert('Failed to apply terms. Please try again.');
                    });
            });
        }

        if (btnAddTC && addTermModal) {
            btnAddTC.addEventListener('click', openTermModal);
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
                        type: 'quotation'
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
                        if (!termsByType['quotation']) {
                            termsByType['quotation'] = [];
                        }
                        termsByType['quotation'].unshift({
                            id: term.id,
                            content: term.content,
                            is_default: 0,
                        });
                        const escapedContent = escapeHtmlAttr(term.content);
                        const row = document.createElement('div');
                        row.className = 'mb-2';
                        row.innerHTML = `
                        <label class="d-flex align-items-start gap-2" style="cursor:pointer;">
                            <input type="checkbox" class="form-check-input mt-1 flex-shrink-0" data-term="true" checked data-tc-id="${term.id}" data-content="${escapedContent}" value="${escapedContent}">
                            <div class="text-dark" style="word-break:break-word;overflow-wrap:anywhere;">${term.content}</div>
                        </label>
                    `;

                        // If "No terms found" placeholder is visible, clear it
                        if (termsList.querySelector('.text-muted')) {
                            termsList.innerHTML = '';
                        }

                        termsList.prepend(row);
                        syncTermsHiddenInput();
                        closeTermModal();
                    })
                    .catch(error => {
                        addTermError.textContent = error.message || 'Unable to save term.';
                        addTermError.classList.remove('d-none');
                    });
            });
        }

        // Edit button
        document.getElementById('btnEditPreview')?.addEventListener('click', function () {
            const currentQuotationId = quotationIdHidden.value || draftId;
            const clientToken = encodeURIComponent(clientId);
            const editStep = 2;
            const createRoute = "{{ route('quotations.create') }}";
            const createPath = createRoute.startsWith('http') ? new URL(createRoute).pathname : createRoute;
            let editUrl = createPath + "?step=" + editStep + "&c=" + clientToken;
            if (currentQuotationId) {
                editUrl += "&d=" + encodeURIComponent(currentQuotationId);
            }
            window.location.href = editUrl;
        });

        // Initialize terms list and preview
        const draftTerms = serverDraft && Array.isArray(serverDraft.terms) ? serverDraft.terms : [];
        const defaultTerms = getDefaultTerms();
        appliedTerms = draftTerms.length > 0 ? draftTerms : defaultTerms;
        renderTermsList(appliedTerms);
        updateHeaderNumberBadge();

        if (draftId) {
            updateDownloadButtons(draftId);
            if (btnApplyTC) btnApplyTC.classList.remove('d-none');
        }
        updateQuotationPreview();

        const form = document.getElementById('quotationForm');
        form?.addEventListener('submit', function (e) {
            // if (!quotationItems.length) {
            //     e.preventDefault();
            //     alert('No items found. Go back and add items.');
            //     return;
            // }
            const titleHidden = document.getElementById('step3_quo_title');
            if (titleHidden && !titleHidden.value.trim()) {
                titleHidden.value = serverDraft ? serverDraft.quo_title : 'Quotation';
            }
            syncTermsHiddenInput();
        });
    })();
</script>
