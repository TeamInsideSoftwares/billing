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

    <div class="gst-report-shell">
        <section class="panel-card gst-filter-panel">
            <form method="GET" action="{{ route('gst-report.index') }}" class="gst-filter-grid">
                @if(!empty($selectedClientId))
                    <input type="hidden" name="c" value="{{ $selectedClientId }}">
                @endif
                <div>
                    <label class="gst-label" for="gst_month_filter">Month</label>
                    <select name="month" id="gst_month_filter" class="form-control">
                        @foreach($monthOptions as $monthValue => $monthLabel)
                            <option value="{{ $monthValue }}" {{ $selectedMonth === $monthValue ? 'selected' : '' }}>
                                {{ $monthLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="gst-label" for="gst_year_filter">Year</label>
                    <select name="year" id="gst_year_filter" class="form-control">
                        @foreach($availableYears as $year)
                            <option value="{{ $year }}" {{ $selectedYear === (int) $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="gst-filter-actions">
                    <button type="submit" class="primary-button">Apply</button>
                    <a href="{{ route('gst-report.index', $selectedClientId ? ['c' => $selectedClientId] : []) }}" class="secondary-button">Reset</a>
                </div>
            </form>
        </section>

        <section class="panel-card gst-table-card">
            @if($rows->isEmpty())
                <div class="no-records-cell">
                    <i class="fas fa-file-invoice empty-state-icon"></i>
                    <p class="no-empty-state-text">No tax invoices found</p>
                    <p class="small-text">Try another month or year.</p>
                </div>
            @else
                <div class="gst-table-toolbar">
                    <div>
                        <strong class="gst-table-title">GST Report</strong>
                        <div class="gst-table-subtitle">{{ $rows->count() }} invoice row(s) for the selected period</div>
                    </div>
                    <div class="gst-table-highlight">
                        Tax Total: {{ number_format($taxTotal, 0) }}
                    </div>
                </div>
                <div class="gst-table-wrap">
                    <table class="data-table gst-table">
                        <colgroup>
                            <col style="width: 7%;">
                            <col style="width: 14%;">
                            <col style="width: 20%;">
                            <col style="width: 11%;">
                            <col style="width: 14%;">
                            <col style="width: 11%;">
                            <col style="width: 8%;">
                            <col style="width: 8%;">
                            <col style="width: 8%;">
                            <col style="width: 9%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col">S.No</th>
                                <th scope="col">Invoice Number</th>
                                <th scope="col">Client</th>
                                <th scope="col">State</th>
                                <th scope="col">GSTIN</th>
                                <th scope="col" class="text-end">Grand Total</th>
                                <th scope="col" class="text-end">IGST</th>
                                <th scope="col" class="text-end">SGST</th>
                                <th scope="col" class="text-end">CGST</th>
                                <th scope="col" class="text-end">Total Tax</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @php
                                            $pdfType = 'tax_invoice';
                                            $previewUrl = route('invoices.pdf', ['invoice' => $row['invoiceid'], 'type' => $pdfType]) . '?preview=1';
                                        @endphp
                                        <a href="{{ route('invoices.pdf', ['invoice' => $row['invoiceid'], 'type' => $pdfType]) }}"
                                           class="gst-link js-gst-preview-link"
                                           data-preview-url="{{ $previewUrl }}"
                                           data-preview-title="Invoice PDF Preview">
                                            {{ $row['ti_number'] }}
                                        </a>
                                    </td>
                                    <td>{{ $row['client_name'] }}</td>
                                    <td>{{ $row['state'] }}</td>
                                    <td>{{ $row['gstin'] }}</td>
                                    <td class="text-end gst-number">{{ number_format($row['grand_total'], 0) }}</td>
                                    <td class="text-end gst-number">{{ $row['igst'] > 0 ? number_format($row['igst'], 0) : '-' }}</td>
                                    <td class="text-end gst-number">{{ $row['sgst'] > 0 ? number_format($row['sgst'], 0) : '-' }}</td>
                                    <td class="text-end gst-number">{{ $row['cgst'] > 0 ? number_format($row['cgst'], 0) : '-' }}</td>
                                    <td class="text-end gst-number gst-total-cell">{{ number_format($row['tax_total'], 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5">Totals</th>
                                <th class="text-end gst-number">{{ number_format($grandTotalSum, 0) }}</th>
                                <th class="text-end gst-number">{{ $igstTotal > 0 ? number_format($igstTotal, 0) : '-' }}</th>
                                <th class="text-end gst-number">{{ $sgstTotal > 0 ? number_format($sgstTotal, 0) : '-' }}</th>
                                <th class="text-end gst-number">{{ $cgstTotal > 0 ? number_format($cgstTotal, 0) : '-' }}</th>
                                <th class="text-end gst-number gst-total-cell">{{ number_format($taxTotal, 0) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <div class="offcanvas offcanvas-end ledger-preview-canvas" tabindex="-1" id="gstPreviewCanvas" aria-labelledby="gstPreviewCanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="gstPreviewCanvasLabel">Invoice PDF Preview</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <iframe id="gstPreviewFrame" class="ledger-preview-frame" src="about:blank" title="GST Report Invoice Preview"></iframe>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const panelEl = document.getElementById('gstPreviewCanvas');
            const frameEl = document.getElementById('gstPreviewFrame');
            const titleEl = document.getElementById('gstPreviewCanvasLabel');
            if (!panelEl || !frameEl || typeof bootstrap === 'undefined') return;

            const previewPanel = new bootstrap.Offcanvas(panelEl);
            document.querySelectorAll('.js-gst-preview-link').forEach((link) => {
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
