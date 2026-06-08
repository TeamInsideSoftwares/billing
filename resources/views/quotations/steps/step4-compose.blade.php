@php
    $composeClientName = $draftQuotation?->client->business_name
        ?? $draftQuotation?->client->contact_name
        ?? $selectedClientName
        ?? 'Client';
    $composeClientEmail = $draftQuotation?->client->primary_email
        ?? $draftQuotation?->client->email
        ?? $selectedClientEmail
        ?? '';
    $displayDocNumber = trim((string) ($draftQuotation->quo_number ?? $draftQuotation->quotationid ?? ''));
@endphp

<div class="row g-3 align-items-stretch">
    <div class="col-12">
        <div class="bg-light p-4 rounded-3 border">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <a href="{{ route('quotations.create', ['step' => 3, 'c' => $draftQuotation->clientid ?? $clientId, 'd' => $draftQuotation->quotationid ?? null]) }}" class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium">
                    <i class="fas fa-arrow-left btn-icon"></i> Back
                </a>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if($displayDocNumber !== '')
                        <div class="badge text-bg-secondary">{{ $displayDocNumber }}</div>
                    @endif
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge text-bg-primary">1</span>
                        <span class="badge text-bg-primary">2</span>
                        <span class="badge text-bg-primary">3</span>
                        <span class="badge text-bg-primary">4</span>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <h5 class="fw-semibold text-black mb-0">{{ $composeClientName }}</h5>
                @if($composeClientEmail)
                    <div class="small text-muted">{{ $composeClientEmail }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="bg-light p-4 rounded-3 border">
            <h5 class="fw-semibold text-black mb-2">Step 4: Compose & Send</h5>
            <p class="small text-muted mb-3">Quotation is ready. Continue to email compose.</p>
            @if(!empty($draftQuotation?->quotationid))
                <a href="{{ route('quotations.email-compose', $draftQuotation->quotationid) }}" class="btn btn-outline-primary btn-primary text-white fw-medium">Go To Email Compose</a>
            @else
                <p class="small text-muted">Create and save quotation first.</p>
            @endif
        </div>
    </div>
</div>
