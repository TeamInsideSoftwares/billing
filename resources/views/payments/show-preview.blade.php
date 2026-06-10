<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Payment' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        body.payment-preview-body {
            margin: 0;
            padding: 0.75rem;
            background: var(--bs-light, #f8f9fa);
        }
    </style>
</head>
<body class="payment-preview-body">
@php
    $client = $payment->client;
    $invoices = $payment->invoices ?? collect();
    $invoice = $payment->invoice;
    $currency = $client->currency ?? 'INR';
    $amount = (float) ($payment->received_amount ?? 0);
    $tdsAmount = (float) ($payment->paymentDetails->sum('tds_amount') ?? 0);
    $summaryLabel = $amount > 0 && $tdsAmount > 0
        ? 'Total Settlement'
        : ($amount > 0 ? 'Received Amount' : 'TDS Amount');
    $summaryAmount = $amount > 0 ? $amount : $tdsAmount;
    $clientName = $client->business_name ?? $client->contact_name ?? 'Client';
    $paymentDate = optional($payment->payment_date)->format('d M Y');
    $paymentMode = strtoupper($payment->mode ?? '-');
    $receiptNumber = trim((string) ($payment->receipt_number ?? ''));
    $invoiceSummary = $invoices->map(function ($item) {
        return $item->ti_number ?: $item->pi_number ?: $item->invoice_number;
    })->filter()->implode(', ');
    if ($invoiceSummary === '' && $invoice) {
        $invoiceSummary = $invoice->ti_number ?: $invoice->pi_number ?: $invoice->invoice_number ?: '';
    }
@endphp

<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="card-body p-4">
        <div class="bg-light rounded-3 p-4 mb-4 d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="text-muted small fw-semibold text-uppercase">{{ $summaryLabel }}</div>
                <h2 class="fw-bold text-dark mb-1">{{ $currency }} {{ number_format($summaryAmount) }}</h2>
                <p class="text-muted small mb-0">
                    @if($amount > 0 && $tdsAmount > 0)
                        Received <strong class="text-success">{{ $currency }} {{ number_format($amount) }}</strong> &middot;
                        TDS <strong class="text-danger">{{ $currency }} {{ number_format($tdsAmount) }}</strong>
                    @elseif($amount > 0)
                        Received in bank/cash: <strong class="text-success">{{ $currency }} {{ number_format($amount) }}</strong>
                    @else
                        TDS deducted: <strong class="text-danger">{{ $currency }} {{ number_format($tdsAmount) }}</strong>
                    @endif
                </p>
            </div>
            @if($receiptNumber !== '')
                <div class="text-end">
                    <div class="text-muted small fw-semibold text-uppercase">Receipt Number</div>
                    <span class="badge bg-primary-subtle text-primary fs-6 fw-semibold px-3 py-2">{{ $receiptNumber }}</span>
                </div>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-striped mainTable border align-middle mb-0">
                <tbody>
                    <tr>
                        <th class="bg-light" style="width: 160px;">Client</th>
                        <td class="fw-semibold text-dark">{{ $clientName }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Payment Date</th>
                        <td>{{ $paymentDate ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Payment Mode</th>
                        <td>
                            <span class="badge bg-light text-dark border text-uppercase fw-medium">{{ $paymentMode }}</span>
                        </td>
                    </tr>
                    @if($invoiceSummary !== '')
                        <tr>
                            <th class="bg-light">Invoices</th>
                            <td><span class="fw-semibold text-dark">{{ $invoiceSummary }}</span></td>
                        </tr>
                    @endif
                    @if($payment->reference_number)
                        <tr>
                            <th class="bg-light">Reference Number</th>
                            <td><span class="font-monospace text-muted">{{ $payment->reference_number }}</span></td>
                        </tr>
                    @endif
                    @if(!empty($payment->description))
                        <tr>
                            <th class="bg-light">Description</th>
                            <td>{{ $payment->description }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if($payment->paymentDetails->isNotEmpty())
            <div class="mt-4 pt-3 border-top">
                <h5 class="fw-bold text-muted text-uppercase small mb-3">Invoice Breakdown</h5>
                <div class="table-responsive">
                    <table class="table mainTable border align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th class="text-end">TDS Amount</th>
                                <th class="text-end">Received Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payment->paymentDetails as $detail)
                                @php
                                    $inv = $detail->invoice;
                                    $invNumber = $inv ? ($inv->ti_number ?: $inv->pi_number ?: $inv->invoice_number) : '';
                                    $invTitle = $inv ? $inv->invoice_title : '';
                                    $dispTitle = $invTitle ?: ($invNumber ? "#$invNumber" : "Invoice #{$detail->invoiceid}");
                                @endphp
                                <tr>
                                    <td class="fw-medium text-dark">
                                        @if($inv)
                                            <a href="{{ route('invoices.pdf', ['invoice' => $inv->invoiceid, 'type' => trim((string) ($inv->ti_number ?? '')) !== '' ? 'tax_invoice' : 'pi']) }}"
                                                target="_blank" class="text-decoration-none fw-semibold text-primary">
                                                {{ $dispTitle }}
                                            </a>
                                        @else
                                            {{ $dispTitle }}
                                        @endif
                                    </td>
                                    <td class="text-end text-danger fw-medium">{{ $currency }} {{ number_format((float)$detail->tds_amount) }}</td>
                                    <td class="text-end text-success fw-semibold">{{ $currency }} {{ number_format((float)$detail->received_amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
</body>
</html>
