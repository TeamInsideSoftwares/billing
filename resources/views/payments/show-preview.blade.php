<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Payment Preview' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="app-shell payment-preview-body">
@php
    $client = $payment->client;
    $invoice = $payment->invoice;
    $currency = $client->currency ?? 'INR';
    $amount = (float) ($payment->received_amount ?? 0);
    $clientName = $client->business_name ?? $client->contact_name ?? 'Client';
    $paymentDate = optional($payment->payment_date)->format('d M Y');
    $paymentMode = strtoupper($payment->mode ?? '-');
    $paymentType = strtoupper($payment->type ?? 'payment');
@endphp

<section class="panel-card payment-show-card">
    <div class="payment-show-amount mb-3">
        <div class="text-muted small">Received Amount</div>
        <h3 class="mb-0">{{ $currency }} {{ number_format($amount) }}</h3>
    </div>

    <div class="table-shell">
    <table class="data-table m-0">
        <tbody>
            <tr>
                <th>Client</th>
                <td>{{ $clientName }}</td>
            </tr>
            <tr>
                <th>Payment Date</th>
                <td>{{ $paymentDate ?: '-' }}</td>
            </tr>
            <tr>
                <th>Payment Mode</th>
                <td>{{ $paymentMode }}</td>
            </tr>
            <tr>
                <th>Payment Type</th>
                <td>{{ $paymentType }}</td>
            </tr>
            @if($invoice)
                <tr>
                    <th>Invoice</th>
                    <td>{{ $invoice->invoice_number ?? '-' }}</td>
                </tr>
            @endif
            @if($payment->reference_number)
                <tr>
                    <th>Reference Number</th>
                    <td>{{ $payment->reference_number }}</td>
                </tr>
            @endif
            @if(!empty($payment->description))
                <tr>
                    <th>Description</th>
                    <td>{{ $payment->description }}</td>
                </tr>
            @endif
            <tr>
                <th>Total Received</th>
                <td><strong>{{ $currency }} {{ number_format($amount) }}</strong></td>
            </tr>
        </tbody>
    </table>
    </div>
</section>

<style>
    html,
    body.payment-preview-body {
        margin: 0;
        padding: 0.75rem;
        min-height: 0 !important;
        height: auto !important;
        overflow: hidden;
        background: var(--bg);
    }

    .payment-show-card {
        max-width: 760px;
        margin: 0 auto;
    }

    .payment-show-amount {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.18rem;
    }
</style>
</body>
</html>
