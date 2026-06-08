<style>
    #clientShowModalTabs {
        border-bottom: 1px solid #dee2e6;
    }

    #clientShowModalTabs .nav-link {
        color: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.6) !important;
        border: none;
        border-bottom: 2px solid transparent;
        background: transparent;
        padding: 0.5rem 1rem;
    }

    #clientShowModalTabs .nav-link:hover {
        color: var(--bs-primary, #0d6efd) !important;
        border-bottom-color: transparent;
    }

    #clientShowModalTabs .nav-link.active {
        color: var(--bs-primary, #0d6efd) !important;
        border-bottom: 2px solid var(--bs-primary, #0d6efd) !important;
        background-color: transparent !important;
    }
</style>

<div class="client-modal-content">
    {{-- Header Section --}}
    <div class="row g-3 align-items-center mb-4">
        <div class="col-auto text-start">
            @if($client->logo_path)
            <div class="tablePrifix bg-white border rounded-circle d-flex align-items-center justify-content-center"
                style="width: 64px; height: 64px; overflow: hidden;">
                <img src="{{ $client->logo_path }}" alt="Logo" class="img-fluid"
                    style="max-height: 100%; object-fit: contain;">
            </div>
            @else
            <div class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold d-flex align-items-center justify-content-center fs-4"
                style="width: 64px; height: 64px;">
                {{ strtoupper(substr($client->business_name ?? $client->contact_name, 0, 2)) }}
            </div>
            @endif
        </div>
        <div class="col text-start">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h4 class="fw-semibold text-dark mb-0">{{ $client->business_name ?? $client->contact_name }}</h4>
                <span
                    class="badge {{ strtolower($client->status) === 'active' ? 'bg-success-subtle text-success' : (strtolower($client->status) === 'review' ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary') }} rounded-pill px-2 py-1 small">
                    {{ ucfirst($client->status ?? 'Active') }}
                </span>
                @if($client->type === 'trial')
                <span class="badge bg-info-subtle text-info rounded-pill px-2 py-1 small">Trial</span>
                @endif
            </div>
            <p class="text-muted mb-0 small text-start">
                <i class="fas fa-envelope me-1"></i> {{ $client->primary_email ?? $client->email }}
                @if($client->group)
                <span class="mx-2 text-muted">|</span>
                <i class="fas fa-layer-group me-1"></i> {{ $client->group->group_name }}
                @endif
            </p>
        </div>
        <div class="col-12 col-md-auto text-md-end text-start">
            <div class="bg-danger-subtle text-danger border border-danger-subtle rounded-3 p-3 d-inline-block text-start animate-fade-in"
                style="min-width: 180px;">
                <div class="small text-danger-emphasis fw-medium text-uppercase mb-1"
                    style="font-size: 0.75rem; letter-spacing: 0.05em;">Outstanding Balance</div>
                <h4 class="fw-bolder mb-0 text-danger">{{ $client->currency ?? 'INR' }} {{ number_format($outstanding ??
                    0, 2) }}</h4>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3" id="clientShowModalTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold rounded-0 px-3" id="client-info-tab" data-bs-toggle="tab"
                data-bs-target="#client-info-pane" type="button" role="tab" aria-controls="client-info-pane"
                aria-selected="true">
                <i class="fas fa-info-circle me-1"></i> Overview
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold rounded-0 px-3" id="client-docs-tab" data-bs-toggle="tab"
                data-bs-target="#client-docs-pane" type="button" role="tab" aria-controls="client-docs-pane"
                aria-selected="false">
                <i class="fas fa-folder-open me-1"></i> Documents ({{ $client->documents->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold rounded-0 px-3" id="client-invoices-tab" data-bs-toggle="tab"
                data-bs-target="#client-invoices-pane" type="button" role="tab" aria-controls="client-invoices-pane"
                aria-selected="false">
                <i class="fas fa-file-invoice me-1"></i> Invoices ({{ $client->invoices->count() }})
            </button>
        </li>
    </ul>

    <div class="tab-content" id="clientShowModalTabsContent">
        {{-- Overview Pane --}}
        <div class="tab-pane fade bg-light p-3 show active" id="client-info-pane" role="tabpanel"
            aria-labelledby="client-info-tab">
            <div class="row g-3">
                <div class="col-12 col-lg-6 text-start">
                    <div class="card border border-light rounded-3 p-3 h-100 shadow-sm bg-white">
                        <h5 class="fw-bold text-dark mb-3 text-start">
                            <i class="fas fa-id-card text-secondary me-2"></i> Profile & Contacts
                        </h5>
                        <div class="d-flex flex-column gap-2 text-start">
                            <div class="row">
                                <div class="col-4 fw-semibold text-secondary small">Business Name</div>
                                <div class="col-8 text-dark">{{ $client->business_name ?? '—' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-4 fw-semibold text-secondary small">Contact Person</div>
                                <div class="col-8 text-dark">{{ $client->contact_name ?? '—' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-4 fw-semibold text-secondary small">Group</div>
                                <div class="col-8 text-dark">{{ $client->group?->group_name ?? '—' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-4 fw-semibold text-secondary small">Primary Email</div>
                                <div class="col-8 text-dark text-break">{{ $client->primary_email ?? '—' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-4 fw-semibold text-secondary small">Secondary Emails</div>
                                <div class="col-8 text-dark text-break">{{ $client->email ?? '—' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-4 fw-semibold text-secondary small">Phone Number</div>
                                <div class="col-8 text-dark">{{ $client->phone ?? '—' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-4 fw-semibold text-secondary small">WhatsApp</div>
                                <div class="col-8 text-dark">{{ $client->whatsapp_number ?? '—' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-4 fw-semibold text-secondary small">Currency</div>
                                <div class="col-8 text-dark"><span class="badge bg-secondary-light text-secondary">{{
                                        $client->currency ?? 'INR' }}</span></div>
                            </div>
                            <div class="row">
                                <div class="col-4 fw-semibold text-secondary small">Address</div>
                                <div class="col-8 text-dark">
                                    {{ $client->address_line_1 ?: '—' }}
                                    @if($client->address_line_2)
                                    , {{ $client->address_line_2 }}
                                    @endif
                                    <br>
                                    @if($client->city || $client->state || $client->postal_code)
                                    {{ $client->city ?? '' }}{{ $client->state ? ', ' . $client->state : '' }} {{
                                    $client->postal_code ?? '' }}<br>
                                    @endif
                                    {{ $client->country ?? '' }}
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-4 fw-semibold text-secondary small">Created Date</div>
                                <div class="col-8 text-dark">{{ $client->created_at?->format('d M Y') ?? '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6 text-start">
                    <div class="card border border-light rounded-3 p-3 h-100 shadow-sm bg-white">
                        <h5 class="fw-bold text-dark mb-3 text-start">
                            <i class="fas fa-file-invoice-dollar text-secondary me-2"></i> Billing Details
                        </h5>
                        @if($client->billingDetail)
                        <div class="d-flex flex-column gap-2 text-start">
                            <div class="row">
                                <div class="col-4 fw-semibold text-secondary small">Business Name</div>
                                <div class="col-8 text-dark">{{ $client->billingDetail->business_name ?? '—' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-4 fw-semibold text-secondary small">GSTIN</div>
                                <div class="col-8 text-primary fw-semibold font-monospace">{{
                                    $client->billingDetail->gstin ?? '—' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-4 fw-semibold text-secondary small">Billing Email</div>
                                <div class="col-8 text-dark text-break">{{ $client->billingDetail->billing_email ??
                                    $client->billing_email ?? '—' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-4 fw-semibold text-secondary small">Billing Phone</div>
                                <div class="col-8 text-dark">{{ $client->billingDetail->billing_phone ?? '—' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-4 fw-semibold text-secondary small">Address</div>
                                <div class="col-8 text-dark">
                                    {{ $client->billingDetail->address_line_1 ?: '—' }}
                                    @if($client->billingDetail->address_line_2)
                                    , {{ $client->billingDetail->address_line_2 }}
                                    @endif
                                    <br>
                                    @if($client->billingDetail->city || $client->billingDetail->state ||
                                    $client->billingDetail->postal_code)
                                    {{ $client->billingDetail->city ?? '' }}{{ $client->billingDetail->state ? ', ' .
                                    $client->billingDetail->state : '' }} {{ $client->billingDetail->postal_code ?? ''
                                    }}<br>
                                    @endif
                                    {{ $client->billingDetail->country ?? '' }}
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-file-invoice text-muted mb-2 fs-3 opacity-50"></i>
                            <p class="small mb-0">No billing details defined.</p>
                        </div>
                        @endif
                    </div>
                </div>

                @if(!empty($client->notes))
                <div class="col-12 text-start">
                    <div class="card border border-light-subtle rounded-3 p-3 shadow-sm bg-light">
                        <h5 class="fw-bold text-dark mb-2 text-start">
                            <i class="fas fa-sticky-note text-secondary me-2"></i> Notes & Special Instructions
                        </h5>
                        <p class="mb-0 text-secondary small pre-wrap text-start">{{ $client->notes }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Documents Pane --}}
        <div class="tab-pane fade bg-light p-3" id="client-docs-pane" role="tabpanel" aria-labelledby="client-docs-tab">
            @if($client->documents->count())
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="table-responsive">
                    <table class="table mainTable border align-middle mb-0 text-start">
                        <thead class="table-light text-start">
                            <tr>
                                <th width="20%">Type</th>
                                <th width="50%">Document Title & Number</th>
                                <th width="20%">Document Date</th>
                                <th class="text-end" width="10%">File</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($client->documents->sortByDesc('created_at') as $document)
                            <tr>
                                <td>
                                    @if(strtolower($document->type) === 'po')
                                    <span
                                        class="border border-primary rounded-pill small lh-sm px-2 py-1 bg-primary text-white">PO</span>
                                    @else
                                    <span class="border rounded-pill small lh-sm px-2 py-1 text-white"
                                        style="background-color: #346739; border-color: #346739;">Agreement</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="d-block fw-semibold text-dark">{{ $document->title ?: '—' }}</span>
                                    @if($document->document_number)
                                    <span class="d-block text-muted small fw-normal">#{{ $document->document_number
                                        }}</span>
                                    @endif
                                </td>
                                <td>{{ $document->document_date?->format('d M Y') ?? '—' }}</td>
                                <td class="text-end">
                                    @if($document->file_path)
                                    <div class="tableActionButton d-inline-flex gap-1">
                                        <a href="{{ route('clients.documents.file', ['client' => $client->clientid, 'document' => $document->client_docid]) }}"
                                            target="_blank" class="bg01 color01 text-decoration-none">View</a>
                                    </div>
                                    @else
                                    —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="text-center py-5 text-muted bg-light border border-light-subtle rounded-3">
                <i class="fas fa-folder-open text-muted mb-2 fs-2 opacity-50"></i>
                <p class="text-muted small mb-0">No documents uploaded for this client.</p>
            </div>
            @endif
        </div>

        {{-- Invoices Pane --}}
        <div class="tab-pane fade bg-light p-3" id="client-invoices-pane" role="tabpanel"
            aria-labelledby="client-invoices-tab">
            @if(isset($allInvoices) && $allInvoices->count())
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="table-responsive">
                    <table class="table mainTable border align-middle mb-0 text-start">
                        <thead class="table-light text-start">
                            <tr>
                                <th>Invoice #</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allInvoices as $invoice)
                            <tr>
                                <td><strong>{{ $invoice->invoice_number }}</strong></td>
                                <td>
                                    <span class="fw-semibold text-dark">
                                        <span class="currency-code-small text-muted">{{ $client->currency ?? 'INR'
                                            }}</span> {{ number_format($invoice->grand_total ?? 0, 2) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="status-pill {{ strtolower($invoice->status ?? 'draft') }}">
                                        {{ ucfirst($invoice->status ?? 'Draft') }}
                                    </span>
                                </td>
                                <td>{{ $invoice->created_at?->format('d M Y') ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="text-center py-5 text-muted bg-light border border-light-subtle rounded-3">
                <i class="fas fa-file-invoice text-muted mb-2 fs-2 opacity-50"></i>
                <p class="text-muted small mb-0">No invoices generated for this client.</p>
            </div>
            @endif
        </div>
    </div>
</div>
