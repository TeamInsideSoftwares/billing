@extends('layouts.app')

@section('header_actions')
<div class="d-flex align-items-center gap-2 flex-wrap">
    <a href="{{ route('payments.index', $selectedClientId !== '' ? ['c' => $selectedClientId] : []) }}"
        class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-list btn-icon"></i> Payment List
    </a>
    @if(auth()->user()->hasPermission('payments.create'))
    <a href="{{ route('payments.create', $selectedClientId !== '' ? ['c' => $selectedClientId] : []) }}"
        class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
        Record Payment <i class="fas fa-arrow-right btn-icon ms-1"></i>
    </a>
    @endif
</div>
@endsection

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<style>
    #ledgerDataTable {
        width: 100% !important;
        table-layout: fixed;
    }
    #ledgerDataTable td {
        word-break: break-word;
        overflow-wrap: break-word;
    }
</style>

<div class="position-relative bg-white p-2 rounded-3">
    <!-- Filters Card -->
    <div class="position-relative bg-DarkLight p-2 rounded-3 mb-2">
        <form method="GET" action="{{ route('payments.ledger') }}" class="mainForm">
            <div class="row g-2">
                <div class="col-12 col-md-2">
                    <select name="c" id="ledger_client_filter" class="form-select" onchange="this.form.submit()">
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
                    <select name="fy" id="ledger_fy_filter" class="form-select" onchange="this.form.submit()">
                        <option value="all" {{ $selectedFyId==='all' ? 'selected' : '' }}>All</option>
                        @foreach($financialYears as $fy)
                        <option value="{{ $fy->fy_id }}" {{ (string) $selectedFyId===(string) $fy->fy_id ? 'selected' :
                            '' }}>
                            {{ $fy->financial_year }}{{ $fy->default ? ' (Default)' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- Table View -->
    @if($ledgerEntries->isEmpty() && ($openingBalance ?? 0) == 0)
    <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3 mb-3">
        <div class="card-body bg-white rounded-3 py-5 text-center text-muted">
            <i class="fas fa-book mb-3 text-secondary fs-1 opacity-50"></i>
            <p class="fw-semibold text-dark mb-1">No ledger entries found</p>
            <p class="small text-muted mb-0">Try widening the filters or record invoices and payments first.</p>
        </div>
    </div>
    @else

    <!-- Table Card -->
    <div class="card position-relative overflow-hidden p-2 border-0 bg-DarkLight rounded-3 mb-3">
        <div class="table-responsive p-2 bg-white rounded-3">
            <div id="balanceToggleContainer" class="d-inline-flex align-items-center me-3 d-none">
                <div class="bg-white px-2 py-1 rounded-pill border" style="cursor:pointer;">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="toggleBalanceCol" style="cursor: pointer;">
                        <label class="form-check-label small fw-semibold text-dark" for="toggleBalanceCol" style="cursor: pointer; user-select: none;">
                            Show Row Balance
                        </label>
                    </div>
                </div>
            </div>
            <table id="ledgerDataTable" class="table table-striped mainTable align-middle mb-0" style="width: 100%;">
                <thead class="table-light">
                    <tr>
                        <th width="10%">Date</th>
                        <th class="text-end" width="10%">Billed</th>
                        <th class="text-end" width="10%">Received</th>
                        <th class="text-end" width="10%">Balance</th>
                        <th>Reference</th>
                        <th>Narration</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ledgerEntries as $entry)
                    <tr>
                        <td data-order="{{ $entry['raw_date'] }}">{{ $entry['date'] }}</td>
                        <td class="text-end fw-semibold text-dark">
                            {{ $entry['debit'] > 0 ? number_format($entry['debit'], 0, '.', ',') : '-' }}
                        </td>
                        <td class="text-end fw-semibold text-dark">
                            {{ $entry['credit'] > 0 ? number_format($entry['credit'], 0, '.', ',') : '-' }}
                        </td>
                        <td class="text-end fw-bold text-dark">
                            {{ number_format($entry['balance'], 0, '.', ',') }}
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
                        <td>
                            @if(($entry['entry_kind'] ?? '') === 'tds')
                            <span class="status-pill payments-status-partly ledger-kind-badge">TDS</span>
                            @endif
                            {{ $entry['description'] !== '' ? $entry['description'] : '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    @if($selectedFyId !== 'all' && $selectedClientId !== '' && $selectedClientId !== 'all')
                    <tr>
                        <td></td>
                        <td class="text-end fw-medium text-muted">Opening Balance</td>
                        <td class="text-end fw-semibold text-dark val-opening-received" data-val="{{ number_format($openingBalance ?? 0, 0, '.', ',') }}">{{ number_format($openingBalance ?? 0, 0, '.', ',') }}</td>
                        <td class="text-end fw-semibold text-dark val-opening-balance" data-val="{{ number_format($openingBalance ?? 0, 0, '.', ',') }}"></td>
                        <td></td>
                        <td></td>
                    </tr>
                    @endif
                    <tr>
                        <td></td>
                        <td class="text-end fw-semibold small lh-sm text-dark">Closing Balance</td>
                        <td class="text-end fw-bold fs-6 lh-sm text-dark val-closing-received" data-val="{{ number_format($closingBalance ?? 0, 0, '.', ',') }}">{{ number_format($closingBalance ?? 0, 0, '.', ',') }}</td>
                        <td class="text-end fw-bold fs-6 lh-sm text-dark val-closing-balance" data-val="{{ number_format($closingBalance ?? 0, 0, '.', ',') }}"></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif
</div>

<div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold" id="pdfViewerModalLabel">Invoice PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2" style="height: 80vh;">
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
            const table = jQuery('#ledgerDataTable').DataTable({
                paging: false,
                info: false,
                autoWidth: false,
                order: [[0, 'asc']],

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
                    { orderable: false, targets: [1, 2] },
                    { visible: false, targets: 3 }
                ]
            });

            // Move toggle container to DataTable filter area before the search input
            const toggleContainer = jQuery('#balanceToggleContainer');
            if (toggleContainer.length) {
                const filterWrapper = jQuery('#ledgerDataTable_filter');
                filterWrapper.parent().addClass('d-flex align-items-center justify-content-md-end flex-wrap gap-2');
                filterWrapper.before(toggleContainer.removeClass('d-none'));
                filterWrapper.find('label').addClass('mb-0');
            }

            // Change listener to show/hide column
            jQuery('#toggleBalanceCol').on('change', function () {
                const isChecked = this.checked;
                table.column(3).visible(isChecked);
                
                if (isChecked) {
                    jQuery('.val-closing-received, .val-opening-received').text('');
                    jQuery('.val-closing-balance').text(function() { return jQuery(this).data('val'); });
                    jQuery('.val-opening-balance').text(function() { return jQuery(this).data('val'); });
                } else {
                    jQuery('.val-closing-received').text(function() { return jQuery(this).data('val'); });
                    jQuery('.val-opening-received').text(function() { return jQuery(this).data('val'); });
                    jQuery('.val-closing-balance, .val-opening-balance').text('');
                }
                
                // Redraw table header/footer sizes if needed
                table.columns.adjust();
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
