@extends('layouts.app')


@section('content')
@php
$monthOptions = [
1 => 'January',
2 => 'February',
3 => 'March',
4 => 'April',
5 => 'May',
6 => 'June',
7 => 'July',
8 => 'August',
9 => 'September',
10 => 'October',
11 => 'November',
12 => 'December',
];
@endphp

<div class="position-relative bg-white p-2 rounded-3">
    <!-- Filters Card -->
    <div class="position-relative bg-DarkLight p-2 rounded-3 mb-2">
        <form method="GET" action="{{ route('gst-report.index') }}" class="mainForm">
            @if(!empty($selectedClientId))
            <input type="hidden" name="c" value="{{ $selectedClientId }}">
            @endif
            <div class="row g-2">
                <div class="col-12 col-md-2">
                    <select name="month" id="gst_month_filter" class="form-select">
                        @foreach($monthOptions as $monthValue => $monthLabel)
                        <option value="{{ $monthValue }}" {{ $selectedMonth===$monthValue ? 'selected' : '' }}>
                            {{ $monthLabel }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <select name="year" id="gst_year_filter" class="form-select">
                        @foreach($availableYears as $year)
                        <option value="{{ $year }}" {{ $selectedYear===(int) $year ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 mt-auto d-flex gap-2">
                    <a href="{{ route('gst-report.index', $selectedClientId ? ['c' => $selectedClientId] : []) }}"
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

    <!-- Table View -->
    @if($rows->isEmpty())
    <div class="card border-0 shadow-sm py-5 text-center text-muted mb-3">
        <div class="card-body">
            <i class="fas fa-file-invoice mb-3 text-secondary fs-1 opacity-50"></i>
            <p class="fw-semibold text-dark mb-1">No tax invoices found</p>
            <p class="small text-muted mb-0">Try another month or year.</p>
        </div>
    </div>
    @else


    <!-- Table Card -->
    <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3 mb-0">
        <div class="table-responsive">
            <table class="table table-striped mainTable align-middle mb-0" style="table-layout: fixed; width: 100%;">
                <thead class="table-light">
                    <tr>
                        <th width="10%">Invoice Number</th>
                        <th width="25%">Client</th>
                        <th width="15%">GSTIN</th>
                        <th width="10%" class="text-end">Invoice Amount</th>
                        <th width="10%" class="text-end">IGST</th>
                        <th width="10%" class="text-end">SGST</th>
                        <th width="10%" class="text-end">CGST</th>
                        <th width="10%" class="text-end">Total Tax</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                    <tr>
                        <td>
                            @php
                            $pdfType = 'tax_invoice';
                            $previewUrl = route('invoices.pdf', ['invoice' => $row['invoiceid'], 'type' => $pdfType]) .
                            '?preview=1';
                            @endphp
                            <a href="{{ route('invoices.pdf', ['invoice' => $row['invoiceid'], 'type' => $pdfType]) }}"
                                class="text-decoration-none fw-medium js-gst-preview-link"
                                data-preview-url="{{ $previewUrl }}" data-preview-title="Invoice PDF Preview">
                                {{ $row['ti_number'] }}
                            </a>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold">
                                    <span class="d-block position-absolute">{{ strtoupper(substr($row['client_name'], 0, 2)) }}</span>
                                </div>
                                <div>
                                    <span class="d-block fw-semibold text-dark">{{ $row['client_name'] }}</span>
                                    <span class="d-block text-dark small">{{ $row['state'] }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-dark">{{ $row['gstin'] ?: '-' }}</td> 
                        <td class="text-end fw-semibold text-dark">{{ number_format($row['grand_total'], 0) }}</td>
                        <td class="text-end text-dark">{{ $row['igst'] > 0 ? number_format($row['igst'], 0) : '-' }}
                        </td>
                        <td class="text-end text-dark">{{ $row['sgst'] > 0 ? number_format($row['sgst'], 0) : '-' }}
                        </td>
                        <td class="text-end text-dark">{{ $row['cgst'] > 0 ? number_format($row['cgst'], 0) : '-' }}
                        </td>
                        <td class="text-end fw-bold text-dark">{{ number_format($row['tax_total'], 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="3" class="fw-semibold text-dark text-end">Grand Total</th>
                        <th class="text-end fw-bold text-dark  fs-6 lh-sm">{{ number_format($grandTotalSum, 0) }}</th>
                        <th class="text-end fw-bold text-dark" colspan="3">Total Tax<!-- {{ $igstTotal > 0 ? number_format($igstTotal, 0) : '-'
                            }} --></th>
                        <!-- <th class="text-end fw-bold text-dark">{{ $sgstTotal > 0 ? number_format($sgstTotal, 0) : '-'
                        }} </th>
                        <th class="text-end fw-bold text-dark">{{ $cgstTotal > 0 ? number_format($cgstTotal, 0) : '-'
                        }} </th>-->
                        <th class="text-end fw-bold text-dark fs-6 lh-sm">{{ number_format($taxTotal, 0) }}</th>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('pdfViewerModal');
        const frameEl = document.getElementById('pdfViewerFrame');
        const titleEl = document.getElementById('pdfViewerModalLabel');
        if (!modalEl || !frameEl || typeof bootstrap === 'undefined') return;

        const previewModal = new bootstrap.Modal(modalEl);
        document.querySelectorAll('.js-gst-preview-link').forEach((link) => {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                const url = this.dataset.previewUrl || this.getAttribute('href') || '';
                const title = this.dataset.previewTitle || 'Invoice PDF Preview';
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
