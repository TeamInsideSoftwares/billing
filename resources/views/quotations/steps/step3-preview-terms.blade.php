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

    <div class="flex flex-col md:flex-row items-start gap-4">
        <div class="w-full md:w-1/4 min-w-0">
            <div class="soft-panel soft-panel--compact quotation-step3-side-panel">
                <div class="soft-panel__header">
                    <div class="flex items-center gap-2">
                        <h5 class="soft-panel__title">Quotation T&C</h5>
                    </div>
                    <div class="flex gap-2 items-center">
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
                        <div class="small text-slate-500">No quotation T&amp;C found. Click `+ Add` to create one.</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="w-full md:w-3/4 min-w-0">
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
    <div class="mt-4 flex justify-end">
        <button type="submit" class="primary-button" id="createQuotationBtn">Save & Go To Email Compose</button>
    </div>
</form>

<div id="addTermModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <!-- Backdrop overlay -->
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm modal-close-overlay" onclick="closeModal('addTermModal')"></div>
    
    <!-- Dialog container -->
    <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden z-10 flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-slate-100 bg-slate-50">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-file-signature text-slate-400"></i> Add Terms & Conditions
            </h3>
            <button type="button" class="text-slate-400 hover:text-slate-655 text-lg font-bold" onclick="closeModal('addTermModal')">&times;</button>
        </div>
        <!-- Body -->
        <div class="p-6 overflow-y-auto flex-1 text-left space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Term</label>
                <textarea id="newTermContent" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" rows="5" placeholder="Enter the term text"></textarea>
            </div>
            <div id="addTermError" class="text-xs text-red-600 bg-red-50 p-2 rounded hidden"></div>
        </div>
        <!-- Footer -->
        <div class="flex justify-end items-center gap-2 p-4 border-t border-slate-100 bg-slate-50">
            <button type="button" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-xs font-semibold" onclick="closeModal('addTermModal')">Cancel</button>
            <button type="button" id="saveTermBtn" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold shadow-sm transition-colors">Save Term</button>
        </div>
    </div>
</div>
