@extends('layouts.app')

@section('header_actions')
<div class="d-flex align-items-center gap-2 flex-wrap">
    <a href="{{ route('payments.index', $selectedClientId !== '' ? ['c' => $selectedClientId] : []) }}"
        class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-arrow-left btn-icon"></i> Back to Payments
    </a>
    <a href="{{ route('payments.create', $selectedClientId !== '' ? ['c' => $selectedClientId] : []) }}"
        class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-plus btn-icon"></i> Record Payment
    </a>
</div>
@endsection

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

<div class="position-relative bg-white p-3 rounded-3 shadow-sm">
    <!-- Filters Card -->
    <div class="position-relative bg-light border p-3 rounded-3 mb-2">
        <form method="GET" action="{{ route('payments.ledger') }}" class="mainForm">
            <div class="row g-2">
                <div class="col-12 col-md-2">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                        for="ledger_client_filter">Client</label>
                    <select name="c" id="ledger_client_filter" class="form-select">
                        <option value="all" {{ $selectedClientId==='' || $selectedClientId==='all' ? 'selected' : '' }}>
                            All Clients</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->clientid }}" {{ (string) $selectedClientId===(string) $client->
                            clientid ? 'selected' : '' }}>
                            {{ $client->business_name ?? $client->contact_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1" for="ledger_fy_filter">Financial
                        Year</label>
                    <select name="fy" id="ledger_fy_filter" class="form-select">
                        <option value="all" {{ $selectedFyId==='all' ? 'selected' : '' }}>All</option>
                        @foreach($financialYears as $fy)
                        <option value="{{ $fy->fy_id }}" {{ (string) $selectedFyId===(string) $fy->fy_id ? 'selected' :
                            '' }}>
                            {{ $fy->financial_year }}{{ $fy->default ? ' (Default)' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 mt-auto d-flex gap-2">
                    <a href="{{ route('payments.ledger') }}"
                        class="btn btn-outline-primary bg-white text-primary fw-medium w-100 text-center justify-content-center">
                        <i class="fas fa-sync-alt btn-icon me-1"></i> Reset
                    </a>
                    <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium w-100">
                        Apply <i class="fas fa-arrow-right btn-icon ms-1"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Table View -->
    @if($ledgerEntries->isEmpty())
    <div class="card border-0 shadow-sm py-5 text-center text-muted mb-3">
        <div class="card-body">
            <i class="fas fa-book mb-3 text-secondary fs-1 opacity-50"></i>
            <p class="fw-semibold text-dark mb-1">No ledger entries found</p>
            <p class="small text-muted mb-0">Try widening the filters or record invoices and payments first.</p>
        </div>
    </div>
    @else

    <!-- Table Card -->
    <div class="card border-0 shadow-sm overflow-hidden mb-3">
        <div class="table-responsive p-3 bg-white">
            <table id="ledgerDataTable" class="table mainTable border align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Narration</th>
                        <th scope="col">Reference</th>
                        <th scope="col" class="text-end">Billed</th>
                        <th scope="col" class="text-end">Received</th>
                        <th scope="col" class="text-end">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ledgerEntries as $entry)
                    <tr>
                        <td data-order="{{ $entry['raw_date'] }}">{{ $entry['date'] }}</td>
                        <td>
                            @if(($entry['entry_kind'] ?? '') === 'tds')
                            <span class="status-pill payments-status-partly ledger-kind-badge">TDS</span>
                            @endif
                            {{ $entry['description'] !== '' ? $entry['description'] : '-' }}
                        </td>
                        <td>
                            @if($entry['reference_url'])
                            @php
                            $previewUrl = $entry['entry_kind'] === 'invoice'
                            ? $entry['reference_url']
                            : $entry['reference_url'] . (str_contains($entry['reference_url'], '?') ? '&' : '?') .
                            'preview=1';
                            @endphp
                            <a href="{{ $entry['reference_url'] }}"
                                class="text-decoration-none fw-medium js-ledger-preview-link"
                                data-preview-url="{{ $previewUrl }}"
                                data-preview-title="{{ $entry['entry_kind'] === 'invoice' ? 'Invoice PDF Preview' : 'Payment Preview' }}">
                                {{ $entry['reference_label'] }}
                            </a>
                            @else
                            <span class="fw-medium text-dark">{{ $entry['reference_label'] }}</span>
                            @endif
                            @if(!empty($entry['reference_meta']))
                            <div class="text-muted small mt-1">{{ $entry['reference_meta'] }}</div>
                            @endif
                        </td>
                        <td class="text-end fw-semibold text-dark">
                            {{ $entry['debit'] > 0 ? number_format($entry['debit'], 0, '.', ',') : '-' }}
                        </td>
                        <td class="text-end fw-semibold text-dark">
                            {{ $entry['credit'] > 0 ? number_format($entry['credit'], 0, '.', ',') : '-' }}
                        </td>
                        <td class="text-end fw-bold text-dark">
                            {{ number_format($entry['balance'], 0, '.', ',') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light border-top">
                    <tr>
                        <th colspan="5" class="text-end fw-semibold text-muted">Closing Balance</th>
                        <th class="text-end fw-bold text-primary">{{ number_format($closingBalance, 0, '.', ',') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif
</div>

<div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-white border-bottom py-2">
                <h5 class="modal-title fw-semibold" id="pdfViewerModalLabel">Invoice PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 85vh;">
                <iframe id="pdfViewerFrame" src="" style="width: 100%; height: 100%; border: 0;"></iframe>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery && jQuery.fn.DataTable && document.getElementById('ledgerDataTable')) {
            jQuery('#ledgerDataTable').DataTable({
                pageLength: 25,
                order: [[0, 'asc']],
                dom: "<'row align-items-center g-2 mb-2'<'col-md-7'B><'col-md-5'f>>" +
                    "<'row'<'col-12'tr>>" +
                    "<'row mt-2'<'col-md-5'i><'col-md-7'p>>",
                buttons: [
                    { extend: 'excelHtml5', text: 'Excel' },
                    {
                        extend: 'pdfHtml5',
                        text: 'PDF',
                        orientation: 'landscape',
                        pageSize: 'A4',
                        exportOptions: { columns: ':visible' },
                        customize: function (doc) {
                            doc.pageMargins = [26, 28, 26, 28];
                            doc.defaultStyle = {
                                fontSize: 10,
                                lineHeight: 1.25
                            };
                            doc.styles.tableHeader = {
                                fontSize: 10.5,
                                bold: true,
                                fillColor: '#f8fafc',
                                color: '#334155',
                                margin: [0, 5, 0, 5]
                            };

                            if (doc.content && doc.content[1] && doc.content[1].table) {
                                doc.content[1].layout = {
                                    hLineWidth: function () { return 0.6; },
                                    vLineWidth: function () { return 0.4; },
                                    hLineColor: function () { return '#dbe2ea'; },
                                    vLineColor: function () { return '#e2e8f0'; },
                                    paddingLeft: function () { return 8; },
                                    paddingRight: function () { return 8; },
                                    paddingTop: function () { return 6; },
                                    paddingBottom: function () { return 6; }
                                };
                            }
                        }
                    },
                    { extend: 'print', text: 'Print' }
                ],
                columnDefs: [
                    { orderable: false, targets: [1, 2] }
                ]
            });
        }

        const modalEl = document.getElementById('pdfViewerModal');
        const frameEl = document.getElementById('pdfViewerFrame');
        const titleEl = document.getElementById('pdfViewerModalLabel');
        if (!modalEl || !frameEl || typeof bootstrap === 'undefined') return;

        const previewModal = new bootstrap.Modal(modalEl);
        document.querySelectorAll('.js-ledger-preview-link').forEach((link) => {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                const url = this.dataset.previewUrl || this.getAttribute('href') || '';
                const title = this.dataset.previewTitle || 'Invoice PDF';
                if (!url) return;
                titleEl.textContent = title;
                frameEl.src = url;
                previewModal.show();
            });
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            frameEl.src = 'about:blank';
        });
    });
</script>
@endsection
