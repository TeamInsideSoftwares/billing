@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('payments.index', $selectedClientId !== '' ? ['c' => $selectedClientId] : []) }}" class="secondary-button">
        <i class="fas fa-arrow-left icon-spaced"></i>Back to Payments
    </a>
    <a href="{{ route('payments.create', $selectedClientId !== '' ? ['c' => $selectedClientId] : []) }}" class="primary-button">
        <i class="fas fa-plus icon-spaced"></i>Record Payment
    </a>
@endsection

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

    <div class="ledger-shell">
        <section class="panel-card module-filter-panel filter-panel-regular">
            <form method="GET" action="{{ route('payments.ledger') }}" class="module-filter-grid">
                <div class="module-filter-field">
                    <label class="module-filter-label" for="ledger_client_filter">Client</label>
                    <select name="c" id="ledger_client_filter" class="form-control">
                        <option value="all" {{ $selectedClientId === '' || $selectedClientId === 'all' ? 'selected' : '' }}>All Clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->clientid }}" {{ (string) $selectedClientId === (string) $client->clientid ? 'selected' : '' }}>
                                {{ $client->business_name ?? $client->contact_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="module-filter-field">
                    <label class="module-filter-label" for="ledger_fy_filter">Financial Year</label>
                    <select name="fy" id="ledger_fy_filter" class="form-control">
                        <option value="all" {{ $selectedFyId === 'all' ? 'selected' : '' }}>All</option>
                        @foreach($financialYears as $fy)
                            <option value="{{ $fy->fy_id }}" {{ (string) $selectedFyId === (string) $fy->fy_id ? 'selected' : '' }}>
                                {{ $fy->financial_year }}{{ $fy->default ? ' (Default)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="module-filter-actions">
                    <button type="submit" class="primary-button">Apply</button>
                    <a href="{{ route('payments.ledger') }}" class="secondary-button">Reset</a>
                </div>
            </form>
        </section>

        <section class="panel-card ledger-table-card">
            @if($ledgerEntries->isEmpty())
                <div class="no-records-cell">
                    <i class="fas fa-book empty-state-icon"></i>
                    <p class="no-empty-state-text">No ledger entries found</p>
                    <p class="small-text">Try widening the filters or record invoices and payments first.</p>
                </div>
            @else
                <div class="ledger-table-toolbar">
                    <div>
                        <strong class="ledger-table-title">Ledger Entries</strong>
                        <div class="ledger-table-subtitle">{{ $ledgerEntries->count() }} row(s) in statement view</div>
                    </div>
                </div>
                <div class="ledger-table-wrap">
                    <table id="ledgerDataTable" class="data-table ledger-table">
                        <thead>
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
                                    <td class="ledger-date-cell ledger-cell-text" data-order="{{ $entry['raw_date'] }}">{{ $entry['date'] }}</td>
                                    <td>
                                        @if(($entry['entry_kind'] ?? '') === 'tds')
                                            <span class="status-pill payments-status-partly ledger-kind-badge">TDS</span>
                                        @endif
                                        {{ $entry['description'] !== '' ? $entry['description'] : '-' }}
                                    </td>
                                    <td class="ledger-cell-text">
                                        @if($entry['reference_url'])
                                            @php
                                                $previewUrl = $entry['entry_kind'] === 'invoice'
                                                    ? $entry['reference_url']
                                                    : $entry['reference_url'] . (str_contains($entry['reference_url'], '?') ? '&' : '?') . 'preview=1';
                                            @endphp
                                            <a href="{{ $entry['reference_url'] }}"
                                               class="ledger-ref-link js-ledger-preview-link"
                                               data-preview-url="{{ $previewUrl }}"
                                               data-preview-title="{{ $entry['entry_kind'] === 'invoice' ? 'Invoice PDF Preview' : 'Payment Preview' }}">
                                                {{ $entry['reference_label'] }}
                                            </a>
                                        @else
                                            <span class="ledger-ref-link">{{ $entry['reference_label'] }}</span>
                                        @endif
                                        @if(!empty($entry['reference_meta']))
                                            <div class="ledger-ref-meta">{{ $entry['reference_meta'] }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end ledger-amount-cell">
                                        {{ $entry['debit'] > 0 ? number_format($entry['debit'], 0, '.', ',') : '-' }}
                                    </td>
                                    <td class="text-end ledger-amount-cell">
                                        {{ $entry['credit'] > 0 ? number_format($entry['credit'], 0, '.', ',') : '-' }}
                                    </td>
                                    <td class="text-end ledger-balance-cell">
                                        {{ number_format($entry['balance'], 0, '.', ',') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5">Closing Balance</th>
                                <th class="text-end">{{ number_format($closingBalance, 0, '.', ',') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <div class="offcanvas offcanvas-end ledger-preview-canvas" tabindex="-1" id="ledgerPreviewCanvas" aria-labelledby="ledgerPreviewCanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="ledgerPreviewCanvasLabel">Preview</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <iframe id="ledgerPreviewFrame" class="ledger-preview-frame" src="about:blank" title="Ledger Preview"></iframe>
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

            const panelEl = document.getElementById('ledgerPreviewCanvas');
            const frameEl = document.getElementById('ledgerPreviewFrame');
            const titleEl = document.getElementById('ledgerPreviewCanvasLabel');
            if (!panelEl || !frameEl || typeof bootstrap === 'undefined') return;

            const previewPanel = new bootstrap.Offcanvas(panelEl);
            document.querySelectorAll('.js-ledger-preview-link').forEach((link) => {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    const url = this.dataset.previewUrl || this.getAttribute('href') || '';
                    const title = this.dataset.previewTitle || 'Preview';
                    if (!url) return;
                    titleEl.textContent = title;
                    frameEl.src = url;
                    previewPanel.show();
                });
            });

            panelEl.addEventListener('hidden.bs.offcanvas', function () {
                frameEl.src = 'about:blank';
            });
        });
    </script>
@endsection
