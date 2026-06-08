@php
    $quotationDateBounds = $quotationDateBounds ?? [
        'default_issue_date' => '',
        'default_due_date' => '',
    ];
@endphp
<form method="POST" action="{{ route('quotations.store') }}" id="quotationForm" class="mainForm">
    @csrf
    <input type="hidden" name="clientid" value="{{ $clientId }}">
    <input type="hidden" name="quotationid" id="quotationid_hidden" value="{{ $draftQuotation->quotationid ?? '' }}">
    <input type="hidden" name="items_data" id="items_data">
    <input type="hidden" name="quo_title" id="quo_title_hidden">
    <input type="hidden" name="quo_number" id="quo_number_hidden" value="{{ $draftQuotation->quo_number ?? $nextQuotationNumber }}">
    <input type="hidden" name="issue_date" id="issue_date_hidden" value="{{ $quotationDateBounds['default_issue_date'] ?? '' }}">
    <input type="hidden" name="due_date" id="due_date_hidden" value="{{ $quotationDateBounds['default_due_date'] ?? '' }}">
    <input type="hidden" name="notes" id="notes_hidden">
    <div class="bg-light p-4 rounded-3 border mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <a href="{{ route('quotations.create', ['step' => 2, 'c' => $clientId, 'd' => $draftQuotation->quotationid ?? null]) }}" class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium">
                <i class="fas fa-arrow-left text-sm"></i>
            </a>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="invoice-number-badge" id="reviewQuoNumber">{{ $draftQuotation->quo_number ?? $nextQuotationNumber }}</div>
                <div class="invoice-compact-steps invoice-compact-steps--right" aria-label="Step progress">
                    <span class="invoice-compact-step">1</span>
                    <span class="invoice-compact-step">2</span>
                    <span class="invoice-compact-step is-active">3</span>
                    <span class="invoice-compact-step">4</span>
                </div>
            </div>
        </div>
        <div class="mt-3">
            <h5 class="fw-semibold text-black mb-0" id="reviewClientName">{{ $selectedClientName }}</h5>
            @if($selectedClientEmail)
                <div class="small text-muted">{{ $selectedClientEmail }}</div>
            @endif
        </div>
    </div>

    <div class="row g-3 align-items-start">
        <div class="col-12 col-md-3 min-w-0">
            <div class="bg-light p-4 rounded-3 border h-100">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <h5 class="fw-semibold text-black mb-0">Quotation T&C</h5>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-outline-primary btn-primary text-white fw-medium btn-sm" id="btnApplyTC">Apply</button>
                        <button type="button" class="btn btn-outline-primary bg-white text-primary btn-sm" id="btnAddTC">+ Add</button>
                    </div>
                </div>
                <input type="hidden" name="terms" id="termsInput" value="[]">
                <div id="termsList" class="quotation-step3-terms-list">
                    @foreach(($quotationTerms ?? collect()) as $term)
                        <div class="term-item-row quotation-step3-term-row">
                            <label class="form-check d-flex align-items-start gap-2 quotation-step3-term-label">
                                <input
                                    type="checkbox"
                                    class="form-check-input term-checkbox quotation-step3-term-checkbox mt-1"
                                    data-content="{{ e($term->content) }}"
                                    {{ !empty($term->is_default) ? 'checked' : '' }}
                                >
                                <div class="quotation-step3-term-content flex-grow-1">
                                    <div class="quotation-step3-term-text">{!! $term->content !!}</div>
                                </div>
                            </label>
                        </div>
                    @endforeach
                    @if(($quotationTerms ?? collect())->isEmpty())
                        <div class="small text-muted">No quotation T&amp;C found. Click `+ Add` to create one.</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-md-9 min-w-0">
            <div class="bg-light p-4 rounded-3 border h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h5 class="fw-semibold text-black mb-0">
                        <i class="fas fa-file-pdf me-1"></i>
                        Quotation PDF Preview
                    </h5>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="small text-muted">
                            <i class="fas fa-circle text-success" style="font-size: 0.5rem;"></i>
                            Live Preview
                        </span>
                        <a href="#" id="btnDownloadQuotationPdf" target="_blank" class="btn btn-outline-primary bg-white text-primary btn-sm">
                            Download Quotation PDF
                        </a>
                        <button type="button" id="btnEditPreview" class="btn btn-outline-primary bg-white text-primary btn-sm">Edit</button>
                    </div>
                </div>
                <div class="ratio ratio-16x9 border rounded-3 overflow-hidden bg-white">
                    <iframe id="quotationPdfPreviewFrame" title="Quotation Preview" src="{{ !empty($draftQuotation?->quotationid) ? route('quotations.pdf', $draftQuotation->quotationid) . '?preview=1&_t=' . time() : 'about:blank' }}" class="w-100 h-100"></iframe>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-3 d-flex justify-content-end">
        <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium" id="createQuotationBtn">Save & Go To Email Compose</button>
    </div>
</form>

<div class="modal fade" id="addTermModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered quotation-step3-term-modal">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-white border-bottom">
                <h5 class="modal-title fw-semibold">Add Terms & Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light p-4">
                <div class="mb-3">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Term</label>
                    <textarea id="newTermContent" class="form-control" rows="5" placeholder="Enter the term text"></textarea>
                    <div id="addTermError" class="text-danger small mt-2 d-none"></div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <button type="button" class="btn btn-outline-primary bg-white text-primary fw-medium" data-bs-dismiss="modal">
                        <i class="fas fa-times btn-icon me-1"></i> Cancel
                    </button>
                    <button type="button" id="saveTermBtn" class="btn btn-outline-primary btn-primary text-white fw-medium">
                        Save Term <i class="fas fa-arrow-right btn-icon ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
