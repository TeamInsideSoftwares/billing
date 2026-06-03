@php
    $quotationDateBounds = $quotationDateBounds ?? [
        'default_issue_date' => '',
        'default_due_date' => '',
    ];
@endphp
<form method="POST" action="{{ route('quotations.store') }}" id="quotationForm">
    @csrf
    <input type="hidden" name="clientid" value="{{ $clientId }}">
    <input type="hidden" name="quotationid" id="quotationid_hidden" value="{{ $draftQuotation->quotationid ?? '' }}">
    <input type="hidden" name="items_data" id="items_data">
    <input type="hidden" name="quo_title" id="quo_title_hidden">
    <input type="hidden" name="quo_number" id="quo_number_hidden" value="{{ $draftQuotation->quo_number ?? $nextQuotationNumber }}">
    <input type="hidden" name="issue_date" id="issue_date_hidden" value="{{ $quotationDateBounds['default_issue_date'] ?? '' }}">
    <input type="hidden" name="due_date" id="due_date_hidden" value="{{ $quotationDateBounds['default_due_date'] ?? '' }}">
    <input type="hidden" name="notes" id="notes_hidden">
    <div class="quotation-step3-header">
        <div class="quotation-step3-header-row">
            <a href="{{ route('quotations.create', ['step' => 2, 'c' => $clientId, 'd' => $draftQuotation->quotationid ?? null]) }}" class="secondary-button quotation-step3-back-btn">
                <i class="fas fa-arrow-left text-sm"></i>
            </a>
            <div class="quotation-step3-divider"></div>
            <div class="quotation-step3-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="quotation-step3-client min-w-0">
                <div class="quotation-step3-client-name" id="reviewClientName">{{ $selectedClientName }}</div>
                @if($selectedClientEmail)
                    <div class="quotation-step3-client-email">{{ $selectedClientEmail }}</div>
                @endif
            </div>
            <div class="quotation-step3-tools">
                <div class="invoice-number-badge" id="reviewQuoNumber">{{ $draftQuotation->quo_number ?? $nextQuotationNumber }}</div>
                <div class="invoice-compact-steps invoice-compact-steps--right" aria-label="Step progress">
                    <span class="invoice-compact-step">1</span>
                    <span class="invoice-compact-step">2</span>
                    <span class="invoice-compact-step is-active">3</span>
                    <span class="invoice-compact-step">4</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 align-items-start">
        <div class="col-12 col-md-3 min-w-0">
            <div class="soft-panel soft-panel--compact quotation-step3-side-panel">
                <div class="soft-panel__header">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="soft-panel__title">Quotation T&C</h5>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="primary-button quotation-step3-small-btn" id="btnApplyTC">Apply</button>
                        <button type="button" class="text-link quotation-step3-add-btn" id="btnAddTC">+ Add</button>
                    </div>
                </div>
                <input type="hidden" name="terms" id="termsInput" value="[]">
                <div id="termsList" class="quotation-step3-terms-list">
                    @foreach(($quotationTerms ?? collect()) as $term)
                        <div class="term-item-row quotation-step3-term-row">
                            <label class="custom-checkbox quotation-step3-term-label">
                                <input
                                    type="checkbox"
                                    class="term-checkbox quotation-step3-term-checkbox"
                                    data-content="{{ e($term->content) }}"
                                    {{ !empty($term->is_default) ? 'checked' : '' }}
                                >
                                <div class="quotation-step3-term-content">
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
            <div class="soft-panel quotation-step3-preview-panel">
                <div class="quotation-step3-preview-header">
                    <h5 class="soft-panel__title soft-panel__title--lg">
                        <i class="fas fa-file-pdf quotation-step3-preview-icon"></i>
                        Quotation PDF Preview
                    </h5>
                    <div class="quotation-step3-preview-actions">
                        <span class="quotation-step3-live-label">
                            <i class="fas fa-circle quotation-step3-live-dot"></i>
                            Live Preview
                        </span>
                        <a href="#" id="btnDownloadQuotationPdf" target="_blank" class="secondary-button quotation-step3-toolbar-btn">
                            Download Quotation PDF
                        </a>
                        <button type="button" id="btnEditPreview" class="secondary-button quotation-step3-toolbar-btn">Edit</button>
                    </div>
                </div>
                <div class="quotation-step3-preview-frame-wrap">
                    <div class="quotation-step3-preview-frame-shell">
                        <iframe id="quotationPdfPreviewFrame" title="Quotation Preview" src="{{ !empty($draftQuotation?->quotationid) ? route('quotations.pdf', $draftQuotation->quotationid) . '?preview=1&_t=' . time() : 'about:blank' }}" class="quotation-step3-preview-frame"></iframe>
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
