@extends('layouts.app')

@section('header_actions')
<div class="d-flex align-items-center gap-2 flex-wrap">
    @if(auth()->user()->hasPermission('quotations.create'))
    <a href="{{ route('quotations.create', $selectedClientId ? ['c' => $selectedClientId] : []) }}"
        class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
        Create Quotation <i class="fas fa-arrow-right btn-icon"></i>
    </a>
    @endif
</div>
@endsection

@section('content')
<div class="position-relative bg-white p-2 rounded-3">
    <!-- Filters Card -->
    <div class="position-relative bg-DarkLight p-2 rounded-3 mb-2">
        <form action="{{ route('quotations.index') }}" method="GET" class="mainForm">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-2">
                    <select name="c" id="quotation_client_filter" class="form-select" onchange="this.form.submit()">
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
                <div class="col-12 col-md-10 d-flex justify-content-end align-items-center gap-2 mt-auto">
                    <div class="btn-group shadow-sm" role="group" aria-label="View Toggle">
                        <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 h-auto"
                            style="font-size:0.875rem;" id="btn-grid-view">
                            <i class="fas fa-th-large toggle-icon"></i> Grid
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 h-auto"
                            style="font-size:0.875rem;" id="btn-list-view">
                            <i class="fas fa-list toggle-icon"></i> List
                        </button>
                    </div>
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
    <!-- Quotations List View (Table View) -->
    <div id="quotations-list-view" class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3 mb-0">
        <div class="table-responsive">
            <table class="table table-striped mainTable align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="10%">Issue Date</th>
                        <th width="25%">Client</th>
                        <th width="20%">Quotation Details</th>
                        <th class="text-center" width="10%">Due Date</th>
                        <th class="text-end" width="10%">Amount</th>
                        <th class="text-end" width="25%">Actions</th>
                    </tr> 
                </thead>
                <tbody id="quotation-items-accordion">
                    @foreach ($quotations as $quotation)
                    <tr>
                        <td>{{ $quotation['issue_date'] }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold">
                                    <span class="d-block position-absolute">{{ strtoupper(substr($quotation['client'], 0, 2)) }}</span>
                                </div>
                                <div>
                                    <span class="d-block fw-semibold">{!! $searchTerm
                                        ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">' . $searchTerm . '</mark>', $quotation['client'])
                                        : $quotation['client'] !!}</span>
                                    @if ($quotation['client_email'])
                                    <span class="d-block text-dark small lh-sm">{{ $quotation['client_email'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">
                                {!! $searchTerm ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">'.$searchTerm.'</mark>',
                                    $quotation['title']) : $quotation['title'] !!}
                            </div>
                            <small class="text-dark d-block">{!! $searchTerm ? str_ireplace($searchTerm,
                                '<mark class="bg-warning-subtle p-0">'.$searchTerm.'</mark>', $quotation['number']) : $quotation['number'] !!}</small>
                        </td>
                        <td class="text-center">{{ $quotation['due'] }}</td>
                        <td class="text-end">
                            <span class="fw-semibold text-dark">
                                {{ $quotation['amount'] }}
                                <span class="currency-code-small text-muted d-block">{{ $quotation['currency'] }}</span>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="tableActionButton d-inline-flex gap-1 align-items-center">
                                @if(auth()->user()->hasPermission('quotations.view'))
                                <button type="button" class="bg01 color01 border-0 view-pdf-btn"
                                    data-pdf-url="{{ route('quotations.pdf', $quotation['record_id']) }}">
                                    View
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('quotations.edit'))
                                <a href="{{ route('quotations.create', ['step' => 2, 'c' => $quotation['client_id'] ?? $selectedClientId, 'd' => $quotation['record_id']]) }}"
                                    class="bg03 color03">Edit</a>
                                @endif
                                @if(auth()->user()->hasPermission('quotations.create'))
                                <button type="button" class="bg02 color02 border-0 js-open-quotation-copy"
                                    data-copy-url="{{ route('quotations.copy', ['quotation' => $quotation['record_id']]) }}"
                                    data-copy-client-id="{{ $quotation['client_id'] ?? $selectedClientId }}"
                                    data-copy-client-name="{{ $quotation['client'] }}">Copy</button>
                                @endif
                                @if(auth()->user()->hasPermission('quotations.view'))
                                <a href="{{ route('quotations.email-compose', $quotation['record_id']) }}"
                                    class="bg03 color03">Send</a>
                                @endif
                                @if(auth()->user()->hasPermission('quotations.cancel'))
                                <form method="POST" class="d-inline"
                                    action="{{ route('quotations.destroy', ['quotation' => $quotation['record_id'], 'c' => $selectedClientId]) }}"
                                    onsubmit="return confirm(@js('Cancel ' . $quotation['number'] . '?'))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg04 color04 border-0">Cancel</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quotations Grid View -->
    <div id="quotations-grid-view"
        class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-2 p-1 pb-3 mt-2 bg-DarkLight rounded-3 d-none mb-3">
        @foreach ($quotations as $quotation)
        <div class="col">
            <div class="card h-100 border-0 overflow-hidden">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div>
                        <!-- Header with Dates -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="text-muted small" style="font-size: 13px;">Issue: <span
                                    class="text-dark fw-semibold">{{ $quotation['issue_date'] }}</span></div>
                            <div class="text-dark small" style="font-size: 13px;">Due: <span
                                    class="text-dark fw-semibold">{{ $quotation['due'] }}</span></div>
                        </div>

                        <!-- Client details -->
                        <div class="d-flex align-items-center gap-2 mb-3 pb-3 border-bottom">
                            <div
                                class="tablePrifix position-relative align-self-center bg-primary-subtle text-primary rounded-circle fw-semibold flex-shrink-0">
                                <span class="d-block position-absolute">{{ strtoupper(substr($quotation['client'], 0, 2)) }}</span>
                            </div>
                            <div class="flex-grow-1 min-w-0 ps-2">
                                <h6 class="fw-semibold text-dark mb-1 text-truncate lh-sm"
                                    title="{{ $quotation['client'] }}">
                                    {!! $searchTerm
                                    ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">' . $searchTerm . '</mark>', $quotation['client'])
                                    : $quotation['client'] !!}
                                </h6>
                                @if ($quotation['client_email'])
                                <span class="d-block text-dark lh-sm text-break grid-text-medium"
                                    title="{{ $quotation['client_email'] }}">{{ $quotation['client_email'] }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Quotation Info -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <strong class="text-dark text-truncate lh-sm"
                                    title="{{ $quotation['title'] }}">
                                    {!! $searchTerm
                                    ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">' . $searchTerm . '</mark>', $quotation['title'])
                                    : $quotation['title'] !!}
                                </strong>
                                @if(strtolower($quotation['status']) === 'draft')
                                <span
                                    class="status-pill d-inline-block partial bg-warning-subtle text-dark border border-warning-subtle fw-semibold rounded-pill py-0.5 px-2"
                                    style="font-size: 11px;line-height:18px;">Draft</span>
                                @elseif(strtolower($quotation['status']) === 'cancelled')
                                <span
                                    class="status-pill d-inline-block overdue bg-danger-subtle text-danger fw-semibold rounded-pill py-0.5 px-2"
                                    style="font-size: 11px;line-height:18px;">Cancelled</span>
                                @endif
                            </div>
                            <div class="invoice-number-line mt-1.5 d-flex align-items-center gap-2">
                                <span class="text-dark small"># {!! $searchTerm
                                    ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">' . $searchTerm . '</mark>', $quotation['number'])
                                    : $quotation['number'] !!}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Amount Area -->
                    <div
                        class="bg-light rounded-3 px-3 py-2 mt-auto d-flex justify-content-between align-items-center mb-2 text-dark">
                        <span class="text-muted small fw-medium">Quotation Amt</span>
                        <div class="text-end">
                            <span class="text-dark fs-6 lh-sm fw-semibold">{{ $quotation['amount'] }}</span>
                            <span class="currency-code-small text-muted d-block">{{ $quotation['currency'] }}</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="tableActionButton d-flex flex-wrap gap-1 mt-2">
                        @if(auth()->user()->hasPermission('quotations.view'))
                        <button type="button" class="bg01 color01 flex-grow-1 text-center border-0 view-pdf-btn"
                            data-pdf-url="{{ route('quotations.pdf', $quotation['record_id']) }}">
                            View
                        </button>
                        @endif
                        @if(auth()->user()->hasPermission('quotations.edit'))
                        <a href="{{ route('quotations.create', ['step' => 2, 'c' => $quotation['client_id'] ?? $selectedClientId, 'd' => $quotation['record_id']]) }}"
                            class="bg03 color03 flex-grow-1 text-center">Edit</a>
                        @endif
                        @if(auth()->user()->hasPermission('quotations.create'))
                        <button type="button" class="bg02 color02 flex-grow-1 text-center border-0 js-open-quotation-copy"
                            data-copy-url="{{ route('quotations.copy', ['quotation' => $quotation['record_id']]) }}"
                            data-copy-client-id="{{ $quotation['client_id'] ?? $selectedClientId }}"
                            data-copy-client-name="{{ $quotation['client'] }}">Copy</button>
                        @endif
                        @if(auth()->user()->hasPermission('quotations.view'))
                        <a href="{{ route('quotations.email-compose', $quotation['record_id']) }}"
                            class="bg03 color03 flex-grow-1 text-center">Send</a>
                        @endif
                        @if(auth()->user()->hasPermission('quotations.cancel'))
                        <form method="POST" class="d-inline flex-grow-1"
                            action="{{ route('quotations.destroy', ['quotation' => $quotation['record_id'], 'c' => $selectedClientId]) }}"
                            onsubmit="return confirm(@js('Cancel ' . $quotation['number'] . '?'))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg04 color04 w-100 text-center border-0">Cancel</button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

<div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold">Quotation PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2" style="height: 80vh;">
                <iframe id="pdfViewerFrame" src="" style="width: 100%; height: 100%; border: 0;"></iframe>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="quotationCopyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <form method="POST" id="quotationCopyForm" class="mainForm">
                @csrf
                <div class="modal-header bg-DarkLight py-2 border-0">
                    <h5 class="modal-title fw-semibold mb-0">Copy Quotation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-white p-2">
                    <div class="bg-DarkLight p-2 rounded-3">
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
                        <div class="d-flex align-items-center justify-content-end mt-3">
                            <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                                Copy <i class="fas fa-arrow-right btn-icon ms-1"></i>
                            </button>
                        </div>
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

        // View Toggle Logic
        const btnList = document.getElementById('btn-list-view');
        const btnGrid = document.getElementById('btn-grid-view');
        const listView = document.getElementById('quotations-list-view');
        const gridView = document.getElementById('quotations-grid-view');

        function setView(viewType) {
            if (viewType === 'grid') {
                if (listView) listView.classList.add('d-none');
                if (gridView) gridView.classList.remove('d-none');
                if (btnList) {
                    btnList.classList.remove('active', 'btn-primary');
                    btnList.classList.add('btn-outline-primary');
                }
                if (btnGrid) {
                    btnGrid.classList.add('active', 'btn-primary');
                    btnGrid.classList.remove('btn-outline-primary');
                }
                localStorage.setItem('quotations_view_preference', 'grid');
            } else {
                if (listView) listView.classList.remove('d-none');
                if (gridView) gridView.classList.add('d-none');
                if (btnList) {
                    btnList.classList.add('active', 'btn-primary');
                    btnList.classList.remove('btn-outline-primary');
                }
                if (btnGrid) {
                    btnGrid.classList.remove('active', 'btn-primary');
                    btnGrid.classList.add('btn-outline-primary');
                }
                localStorage.setItem('quotations_view_preference', 'list');
            }
        }

        if (btnList && btnGrid) {
            btnList.addEventListener('click', () => setView('list'));
            btnGrid.addEventListener('click', () => setView('grid'));

            const savedPref = localStorage.getItem('quotations_view_preference');
            if (savedPref === 'grid') {
                setView('grid');
            } else {
                setView('list');
            }
        }
    });
</script>
@endpush
@endsection
