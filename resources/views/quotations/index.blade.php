@extends('layouts.app')

@section('header_actions')
<div class="d-flex align-items-center gap-2 flex-wrap">
    <a href="{{ route('quotations.create', $selectedClientId ? ['c' => $selectedClientId] : []) }}"
        class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-plus btn-icon"></i> Create Quotation
    </a>
</div>
@endsection

@section('content')
<div class="position-relative bg-white p-2 rounded-3">
    <!-- Filters Card -->
    <div class="position-relative bg-DarkLight p-2 rounded-3 mb-2">
        <form action="{{ route('quotations.index') }}" method="GET" class="mainForm">
            <div class="row g-2">
                <div class="col-12 col-md-2">
                    <select name="c" id="quotation_client_filter" class="form-select">
                        <option value="">All Clients</option>
                        @php $groupedClients = $clients->groupBy(fn ($c) => $c->type === 'trial' ? 'trial' : 'regular')
                        @endphp
                        @foreach (['regular', 'trial'] as $group)
                        @if ($groupedClients->has($group))
                        <optgroup label="{{ $group === 'regular' ? 'Regular Clients' : 'Prospect Clients' }}">
                            @foreach ($groupedClients[$group] as $clientOption)
                            <option value="{{ $clientOption->clientid }}" {{ (string) $selectedClientId===(string)
                                $clientOption->clientid ? 'selected' : '' }}>
                                {{ $clientOption->business_name ?? ($clientOption->contact_name ?? 'Client') }}
                            </option>
                            @endforeach
                        </optgroup>
                        @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 mt-auto d-flex gap-2">
                    <a href="{{ route('quotations.index') }}"
                        class="btn btn-outline-primary bg-white text-primary fw-medium">
                        <i class="fas fa-sync-alt btn-icon me-1"></i> Clear
                    </a>
                    <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                        <i class="fas fa-filter btn-icon me-1"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    @if (count($quotations) === 0)
    <div class="card border-0 shadow-sm py-5 text-center text-muted mb-3">
        <div class="card-body">
            <i class="fas fa-file-contract mb-3 text-secondary fs-1 opacity-50"></i>
            <p class="fw-semibold text-dark mb-1">No quotations found.</p>
            <p class="small text-muted mb-0">Choose a client or create a new quotation to get started.</p>
        </div>
    </div>
    @else
    <div class="card border-0 shadow-sm overflow-hidden mb-3">
        <div class="table-responsive">
            <table class="table mainTable border align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 15%;">Issue Date</th>
                        <th style="width: 20%;">Client</th>
                        <th style="width: 20%;">Quotation</th>
                        <th style="width: 15%;">Due Date</th>
                        <th style="width: 10%;">Amount</th>
                        <th style="width: 10%;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="quotation-items-accordion">
                    @foreach ($quotations as $quotation)
                    <tr>
                        <td>{{ $quotation['issue_date'] }}
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">
                                {!! $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>',
                                $quotation['client']) : $quotation['client'] !!}
                            </div>
                        </td>
                        <td>
                            <div class="invoice-row-title">
                                <div class="invoice-row-text">
                                    <strong>{!! $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>',
                                        $quotation['title'] ?? $quotation['number']) : ($quotation['title'] ??
                                        $quotation['number']) !!}</strong>
                                    <div class="text-muted small">{!! $searchTerm ? str_ireplace($searchTerm,
                                        '<mark>'.$searchTerm.'</mark>', $quotation['number']) : $quotation['number'] !!}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $quotation['due'] }}</td>
                        <td><span class="fw-semibold text-dark">{{ $quotation['amount'] }}</span></td>
                        <td class="text-end">
                            <div class="tableActionButton d-inline-flex gap-1">
                                <button type="button" class="bg01 color01 border-0 view-pdf-btn"
                                    data-pdf-url="{{ route('quotations.pdf', $quotation['record_id']) }}">
                                    View
                                </button>
                                <a href="{{ route('quotations.create', ['step' => 2, 'c' => $quotation['client_id'] ?? $selectedClientId, 'd' => $quotation['record_id']]) }}"
                                    class="bg03 color03">Edit</a>
                                <button type="button" class="bg02 color02 border-0 js-open-quotation-copy"
                                    data-copy-url="{{ route('quotations.copy', ['quotation' => $quotation['record_id']]) }}"
                                    data-copy-client-id="{{ $quotation['client_id'] ?? $selectedClientId }}"
                                    data-copy-client-name="{{ $quotation['client'] }}">Copy</button>
                                <a href="{{ route('quotations.email-compose', $quotation['record_id']) }}"
                                    class="bg03 color03">Send</a>
                                <form method="POST" class="d-inline"
                                    action="{{ route('quotations.destroy', ['quotation' => $quotation['record_id'], 'c' => $selectedClientId]) }}"
                                    onsubmit="return confirm(@js('Cancel ' . $quotation['number'] . '?'))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg04 color04">Cancel</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-white border-bottom py-2">
                <h5 class="modal-title fw-semibold">Quotation PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 85vh;">
                <iframe id="pdfViewerFrame" src="" style="width: 100%; height: 100%; border: 0;"></iframe>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="quotationCopyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST" id="quotationCopyForm" class="mainForm">
                @csrf
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title fw-semibold mb-0">Copy Quotation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                </div>
                <div class="modal-body bg-light p-4">
                    <div class="mb-3">
                        <label class="form-label small lh-sm fw-semibold text-dark mb-1" for="copy_clientid"> Choose the
                            client to copy this quotation into.</label>
                        <select id="copy_clientid" name="clientid" class="form-select" required>
                            <option value="">Choose client</option>
                            @php $groupedClients = $clients->groupBy(fn ($c) => $c->type === 'trial' ? 'trial' :
                            'regular') @endphp
                            @foreach (['regular', 'trial'] as $group)
                            @if ($groupedClients->has($group))
                            <optgroup label="{{ $group === 'regular' ? 'Regular Clients' : 'Trial Clients' }}">
                                @foreach ($groupedClients[$group] as $clientOption)
                                <option value="{{ $clientOption->clientid }}">
                                    {{ $clientOption->business_name ?? ($clientOption->contact_name ?? 'Client') }}
                                </option>
                                @endforeach
                            </optgroup>
                            @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-3">
                        <button type="button" class="btn btn-outline-primary bg-white text-primary fw-medium"
                            data-bs-dismiss="modal">
                            <i class="fas fa-times btn-icon me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                            Copy <i class="fas fa-arrow-right btn-icon ms-1"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('quotationCopyModal');
        const formEl = document.getElementById('quotationCopyForm');
        const clientSelect = document.getElementById('copy_clientid');
        const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

        document.querySelectorAll('.js-open-quotation-copy').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!modalEl || !formEl || !clientSelect || !modal) return;
                formEl.action = this.dataset.copyUrl || '#';
                clientSelect.value = this.dataset.copyClientId || '';
                modal.show();
            });
        });

        const pdfModal = new bootstrap.Modal(document.getElementById('pdfViewerModal'));
        const pdfFrame = document.getElementById('pdfViewerFrame');

        document.querySelectorAll('.view-pdf-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                pdfFrame.src = this.dataset.pdfUrl;
                pdfModal.show();
            });
        });

        document.getElementById('pdfViewerModal').addEventListener('hidden.bs.modal', function () {
            pdfFrame.src = '';
        });
    });
</script>
@endpush
@endsection
