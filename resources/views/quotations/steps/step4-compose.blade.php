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

<div class="quotation-centered-card">
    <div class="quotation-step3-header">
        <div class="quotation-step3-header-row">
            <a href="{{ route('quotations.create', ['step' => 3, 'c' => $draftQuotation->clientid ?? $clientId, 'd' => $draftQuotation->quotationid ?? null]) }}" class="secondary-button quotation-step3-back-btn">
                <i class="fas fa-arrow-left text-sm"></i>
            </a>
            <div class="quotation-step3-divider"></div>
            <div class="quotation-step3-avatar">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="quotation-step3-client min-w-0">
                <div class="quotation-step3-client-name">{{ $composeClientName }}</div>
                @if($composeClientEmail)
                    <div class="quotation-step3-client-email">{{ $composeClientEmail }}</div>
                @endif
            </div>
            <div class="quotation-step3-tools">
                @if($displayDocNumber !== '')
                    <div class="invoice-number-badge">{{ $displayDocNumber }}</div>
                @endif
                <div class="invoice-compact-steps invoice-compact-steps--right" aria-label="Step progress">
                    <span class="invoice-compact-step">1</span>
                    <span class="invoice-compact-step">2</span>
                    <span class="invoice-compact-step">3</span>
                    <span class="invoice-compact-step is-active">4</span>
                </div>
            </div>
        </div>
    </div>

    <div class="soft-panel soft-panel--padded">
        <h4 class="mb-2">Step 4: Compose & Send</h4>
        <p class="text-slate-500 mb-3">Quotation is ready. Continue to email compose.</p>
        @if(!empty($draftQuotation?->quotationid))
            <a href="{{ route('quotations.email-compose', $draftQuotation->quotationid) }}" class="primary-button">Go To Email Compose</a>
        @else
            <p class="text-xs text-slate-500">Create and save quotation first.</p>
        @endif
    </div>
</div>
