<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Payment' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite(['resources/css/app.css'])
    <style>
        body.payment-preview-body {
            margin: 0;
            padding: 0;
            background: #ffffff;
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

<div class="p-0">
    <!-- Payment Overview & Info Pane -->
    <div class="bg-DarkLight p-2 rounded-3 mb-3">
        <!-- Summary Card -->
        <div class="bg-white rounded-3 p-2 mb-2 d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <small class="text-muted small lh-sm fw-semibold text-uppercase">{{ $summaryLabel }}</small>
                <h2 class="fw-bold text-dark mb-1">{{ $currency }} {{ number_format($summaryAmount) }}</h2>
                <p class="text-dark small mb-0">
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
                    <small class="d-block text-muted small fw-semibold text-uppercase">Receipt Number</small>
                    <span class="badge text-bg-primary">{{ $receiptNumber }}</span>
                </div>
            @endif
        </div>

        <!-- Details Table Card -->
        <div class="card border-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table mainTable border-0 align-middle mb-0">
                    <tbody>
                        <tr>
                            <td class="text-dark border-0 py-1" width="30%">Client</td>
                            <td class="border-0 fw-bold text-dark py-1">{{ $clientName }}</td>
                        </tr>
                        <tr>
                            <td class="text-dark border-0 py-1">Payment Date</td>
                            <td class="border-0 fw-bold text-dark py-1">{{ $paymentDate ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-dark border-0 py-1">Payment Mode</td>
                            <td class="border-0 fw-bold text-dark py-1">
                                <span class="badge bg-light  text-primary border text-uppercase fw-bold">{{ $paymentMode }}</span>
                            </td>
                        </tr>
                        @if($invoiceSummary !== '')
                            <tr>
                                <td class="text-dark border-0 py-1">Invoices</td>
                                <td class="border-0 py-1"><span class="fw-bold text-dark">{{ $invoiceSummary }}</span></td>
                            </tr>
                        @endif
                        @if($payment->reference_number)
                            <tr>
                                <td class="text-dark border-0 py-1">Reference Number</td>
                                <td class="border-0 fw-bold text-dark py-1"><span class="font-monospace text-dark">{{ $payment->reference_number }}</span></td>
                            </tr>
                        @endif
                        @if(!empty($payment->description))
                            <tr>
                                <td class="text-dark border-0 py-1">Description</td>
                                <td class="border-0 fw-bold text-dark py-1">{{ $payment->description }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Invoice Breakdown Pane -->
    @if($payment->paymentDetails->isNotEmpty())
        <div class="bg-DarkLight p-2 rounded-3">
            <h6 class="fw-semibold text-dark mb-2 px-1">Invoice Breakdown</h6>
            <div class="card border-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-striped mainTable border align-middle mb-0">
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
                                                {{ $dispTitle }} <i class="fa-solid fa-up-right-from-square ms-1" style="font-size: 0.75rem;"></i>
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
        </div>
    @endif
</div>
</body>
</html>
