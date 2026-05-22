<form method="POST" action="{{ route('quotations.store') }}" id="quotationForm">
    @csrf
    <input type="hidden" name="clientid" value="{{ $clientId }}">
    <input type="hidden" name="quotationid" id="quotationid_hidden" value="{{ $draftQuotation->quotationid ?? '' }}">
    <input type="hidden" name="items_data" id="items_data">
    <input type="hidden" name="quo_title" id="quo_title_hidden">
    <input type="hidden" name="quo_number" id="quo_number_hidden" value="{{ $draftQuotation->quo_number ?? $nextQuotationNumber }}">
    <input type="hidden" name="issue_date" id="issue_date_hidden">
    <input type="hidden" name="due_date" id="due_date_hidden">
    <input type="hidden" name="notes" id="notes_hidden">
    <div class="quotation-step3-header">
        <div class="quotation-step3-header-row">
            <a href="{{ route('quotations.create', ['step' => 2, 'c' => $clientId, 'd' => $draftQuotation->quotationid ?? null]) }}" class="secondary-button quotation-step3-back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="quotation-step3-divider"></div>
            <div class="quotation-step3-user-icon">
                <i class="fas fa-user"></i>
            </div>
            <div class="quotation-step3-client-block">
                <div class="fw-semibold" id="reviewQuoTitle">{{ $selectedClientName }}</div>
                @if($selectedClientEmail)
                    <div class="text-muted small">{{ $selectedClientEmail }}</div>
                @endif
            </div>
            <div class="text-end quotation-step3-right">
                <span class="invoice-number-badge" id="reviewQuoNumber">{{ $draftQuotation->quo_number ?? $nextQuotationNumber }}</span>
                <div class="invoice-compact-steps mt-1">
                    <span class="invoice-compact-step">1</span>
                    <span class="invoice-compact-step">2</span>
                    <span class="invoice-compact-step is-active">3</span>
                    <span class="invoice-compact-step">4</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 align-items-start">
        <div class="col-12 col-md-3 quotation-min-width-0">
            <div class="panel-card quotation-step3-terms-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0 quotation-step3-section-title">Quotation T&C</h5>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="primary-button small" id="btnApplyTC">Apply</button>
                        <button type="button" class="text-link" id="btnAddTC">+ Add</button>
                    </div>
                </div>
                <input type="hidden" name="terms" id="termsInput" value="[]">
                <div id="termsList" class="quotation-step3-terms-list">
                    @foreach(($quotationTerms ?? collect()) as $term)
                        <label class="custom-checkbox d-flex align-items-start gap-2 mb-2 quotation-term-row">
                            <input
                                type="checkbox"
                                class="term-checkbox"
                                data-content="{{ e($term->content) }}"
                                {{ !empty($term->is_default) ? 'checked' : '' }}
                            >
                            <span class="quotation-term-text">{!! $term->content !!}</span>
                        </label>
                    @endforeach
                    @if(($quotationTerms ?? collect())->isEmpty())
                        <div class="small text-muted">No quotation T&amp;C found. Click `+ Add` to create one.</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-md-9 quotation-min-width-0">
            <div class="panel-card quotation-step3-preview-card">
                <div class="quotation-step3-preview-toolbar">
                    <h5 class="mb-0 quotation-step3-section-title">Quotation PDF Preview</h5>
                    <div class="quotation-step3-toolbar-actions">
                        <span class="text-muted small">Live Preview</span>
                        <a href="#" id="btnDownloadQuotationPdf" target="_blank" class="secondary-button quotation-step3-small-btn">Download Quotation PDF</a>
                        <button type="button" id="btnEditPreview" class="secondary-button quotation-step3-small-btn">Edit</button>
                    </div>
                </div>
                <div class="quotation-step3-preview-frame-wrap">
                    <div class="quotation-step3-preview-frame-shell">
                        <iframe id="quotationPdfPreviewFrame" title="Quotation Preview" src="{{ !empty($draftQuotation?->quotationid) ? route('quotations.pdf', $draftQuotation->quotationid) . '?preview=1&_t=' . time() : 'about:blank' }}" class="quotation-step3-preview-iframe"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-3 d-flex justify-content-end">
        <button type="submit" class="primary-button" id="createQuotationBtn">Save & Go To Email Compose</button>
    </div>
</form>

<div class="modal fade" id="addTermModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered quotation-step3-term-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Terms & Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="field-label">Term</label>
                <textarea id="newTermContent" class="form-input" rows="5" placeholder="Enter the term text"></textarea>
                <div id="addTermError" class="text-danger small mt-2 d-none"></div>
                <div class="mt-3 d-flex align-items-center gap-2">
                    <button type="button" id="saveTermBtn" class="primary-button small">Save Term</button>
                    <button type="button" class="text-link small" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>
