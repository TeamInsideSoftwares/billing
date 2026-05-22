<div class="panel-card quotation-centered-card">
    <h4 class="mb-2">Step 4: Compose & Send</h4>
    <p class="text-muted mb-3">Quotation is ready. Continue to email compose.</p>
    @if(!empty($draftQuotation?->quotationid))
        <a href="{{ route('quotations.email-compose', $draftQuotation->quotationid) }}" class="primary-button">Go To Email Compose</a>
    @else
        <p class="small text-muted">Create and save quotation first.</p>
    @endif
</div>
