<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $documentType }} - {{ $invoice->invoice_number }}</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 10pt;
            color: #000;
            background: #fff;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 9pt;
            margin-bottom: 12pt;
            padding-bottom: 10pt;
            border-bottom: 2px solid #000;
        }

        .from-block {
            flex: 1 1 auto;
            min-width: 0;
            max-width: 56%;
        }

        .company-name {
            margin: 4pt 0 1pt 0;
            font-size: 11pt;
            font-weight: 700;
            color: #000;
        }

        .address {
            margin: 2pt 0;
            font-size: 9pt;
            color: #000;
            line-height: 1.45;
        }

        .gstin {
            margin: 1pt 0;
            font-size: 9pt;
            color: #000;
        }

        .right-block {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4pt;
            min-width: 240px;
            margin-left: auto;
            text-align: right;
        }

        .doc-title {
            font-size: 20pt;
            font-weight: 800;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            margin-bottom: 6pt;
        }

        .logo {
            max-width: 160px;
            max-height: 64px;
            margin-bottom: 8pt;
            object-fit: contain;
            display: block;
        }

        .meta-box {
            text-align: right;
        }

        .meta-row {
            margin: 1pt 0;
            font-size: 9pt;
        }

        .bill-to-section {
            display: flex;
            align-items: flex-start;
            gap: 9pt;
            margin-bottom: 9pt;
        }

        .bill-to-block {
            flex: 1;
            min-width: 0;
            padding: 7pt 9pt 7pt 0;
        }

        .bill-to-label {
            margin-bottom: 3pt;
            font-size: 6pt;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .client-name {
            margin: 1pt 0;
            font-size: 10pt;
            font-weight: 700;
            color: #000;
        }

        .client-detail {
            margin: 2pt 0;
            font-size: 9pt;
            color: #000;
            line-height: 1.45;
        }

        .client-gstin {
            margin: 1pt 0;
            font-size: 9pt;
            color: #000;
        }

        .invoice-title-note {
            flex-shrink: 0;
            max-width: 45%;
            padding-top: 2pt;
            font-size: 10pt;
            font-weight: 700;
            color: #000;
            text-align: right;
        }

        table {
            width: 100%;
            margin-bottom: 14pt;
            border-collapse: collapse;
        }

        thead {
            color: #000;
            background: #fff;
        }

        th {
            padding: 5pt 5pt;
            border: 1px solid #000;
            font-size: 9pt;
            font-weight: 600;
            text-align: left;
        }

        th.center,
        td.center {
            text-align: center;
        }

        th.right,
        td.right {
            text-align: right;
        }

        td {
            padding: 5pt 5pt;
            border: 1px solid #000;
            font-size: 9pt;
            color: #000;
            vertical-align: top;
        }

        .item-name {
            font-weight: 600;
            color: #000;
        }

        .item-desc {
            margin-top: 1pt;
            font-size: 8pt;
            color: #000;
            white-space: pre-wrap;
        }

        .totals-wrap {
            display: flex;
            justify-content: flex-end;
        }

        .totals-box {
            min-width: 260px;
            padding: 4pt 5pt;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 2pt 0;
            border-bottom: 1px solid #000;
            font-size: 9pt;
            color: #000;
        }

        .total-row:last-child {
            border-bottom: none;
        }

        .total-grand {
            font-size: 10pt;
            font-weight: 700;
            color: #000;
        }

        .notes-section {
            margin-top: 9pt;
            padding-top: 7pt;
            border-top: 1px solid #000;
            font-size: 9pt;
            color: #000;
            white-space: pre-wrap;
        }

        .terms-section {
            margin-top: 9pt;
            padding: 7pt 0;
            border-top: 1px solid #000;
        }

        .terms-title {
            margin: 0 0 3pt 0;
            font-size: 9pt;
            font-weight: 600;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .terms-list {
            margin: 0;
            padding-left: 11pt;
            font-size: 9pt;
            line-height: 1.5;
            color: #000;
            list-style: disc;
        }

        .terms-list li {
            margin-bottom: 2pt;
        }

        .signatory {
            display: flex;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        .signatory-box {
            min-width: 220px;
            text-align: right;
        }

        .sig-img {
            display: block;
            max-width: 130px;
            max-height: 52px;
            margin-left: auto;
            margin-bottom: 0.25rem;
            object-fit: contain;
        }

        .sig-line {
            padding-top: 0.3rem;
            border-top: 1px solid #000;
            font-size: 9pt;
            color: #000;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="from-block">
            @if($account->logo_path)
                @php
                    $logoSrc = (str_starts_with($account->logo_path, 'http://') || str_starts_with($account->logo_path, 'https://'))
                        ? $account->logo_path
                        : public_path(str_starts_with($account->logo_path, 'storage/')
                            ? $account->logo_path
                            : 'storage/' . ltrim($account->logo_path, '/'));
                @endphp
                <img src="{{ $logoSrc }}" class="logo" alt="Logo">
            @endif

            <div class="company-name">
                {{ $accountBillingDetail->billing_name ?? $account->name }}
            </div>

            @php
                $addrParts = array_filter([
                    $accountBillingDetail->address ?? '',
                    implode(', ', array_filter([
                        $accountBillingDetail->city ?? '',
                        $accountBillingDetail->state ?? '',
                    ])),
                    $accountBillingDetail->postal_code ?? '',
                    $accountBillingDetail->country ?? '',
                ]);
            @endphp

            @if(count($addrParts))
                <div class="address">
                    {!! implode('<br>', $addrParts) !!}
                </div>
            @endif

            @if(!empty($accountBillingDetail->gstin))
                <div class="gstin">
                    <strong>GSTIN:</strong> {{ $accountBillingDetail->gstin }}
                </div>
            @endif
        </div>

        <div class="right-block">
            <div class="doc-title">
                {{ $documentType }}
            </div>

            <div class="meta-box">
                <div class="meta-row">
                    <strong>{{ $isTaxInvoice ? 'Tax No:' : 'Proforma No:' }}</strong>
                    {{ $isTaxInvoice ? $invoice->ti_number : $invoice->pi_number }}
                </div>

                <div class="meta-row">
                    <strong>Issue Date:</strong>
                    {{ optional($invoice->issue_date)->format('d M Y') ?? '-' }}
                </div>

                <div class="meta-row">
                    <strong>Due Date:</strong>
                    {{ optional($invoice->due_date)->format('d M Y') ?? '-' }}
                </div>
            </div>
        </div>
    </div>

    <div class="bill-to-section">
        <div class="bill-to-block">
            <div class="bill-to-label">
                Bill To
            </div>

            <div class="client-name">
                {{ $invoice->client->business_name ?? $invoice->client->contact_name ?? 'Client' }}
            </div>

            @php
                $cb = optional($invoice->client)->billingDetail;

                $clientAddrParts = array_filter([
                    optional($cb)->address_line_1 ?? '',
                    implode(', ', array_filter([
                        optional($cb)->city ?? '',
                        optional($cb)->state ?? '',
                    ])),
                    optional($cb)->postal_code ?? '',
                    optional($cb)->country ?? '',
                ]);
            @endphp

            @if(count($clientAddrParts))
                <div class="address">
                    {!! implode('<br>', $clientAddrParts) !!}
                </div>
            @endif

            @if(!empty(optional($cb)->gstin))
                <div class="client-gstin">
                    <strong>GSTIN:</strong> {{ $cb->gstin }}
                </div>
            @endif

            @if(!empty($invoice->order?->po_number))
                <div class="client-gstin">
                    <strong>PO Number:</strong> {{ $invoice->order->po_number }}
                </div>
            @endif

            @if(!empty($invoice->order?->po_date))
                <div class="client-gstin">
                    <strong>PO Date:</strong> {{ optional($invoice->order->po_date)->format('d M Y') ?? '-' }}
                </div>
            @endif
        </div>

        @if(!empty($invoice->invoice_title))
            <div class="invoice-title-note">
                {{ $invoice->invoice_title }}
            </div>
        @endif
    </div>

    @php
        $hasRecurring = $invoice->items->some(fn($i) => !empty($i->frequency) && $i->frequency !== 'One-Time');
        $currency = $invoice->client->currency ?? 'INR';
        $hasUsersColumn = $invoice->items->contains(fn($i) => !empty($i->no_of_users) && (int) $i->no_of_users > 0);

        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        foreach ($invoice->items as $item) {
            $lt = (float)($item->line_total ?? 0);
            $da = (float)($item->discount_amount ?? 0);
            $ta = ceil(max(0, $lt - $da) * ((float)($item->tax_rate ?? 0) / 100));

            $subtotal += $lt;
            $discountTotal += $da;
            $taxTotal += $ta;
        }

        $discountTotal = floor($discountTotal);
        $taxTotal = ceil($taxTotal);
        $grandTotal = $subtotal - $discountTotal + $taxTotal;

        $cgst = $sameStateGst ? $taxTotal / 2 : 0;
        $sgst = $sameStateGst ? $taxTotal - $cgst : 0;
        $igst = $sameStateGst ? 0 : $taxTotal;
    @endphp

    <table>
        <thead>
            <tr>
                <th style="width:24px">#</th>
                <th>Description</th>
                <th class="center" style="width:130px">Duration</th>

                @if($hasUsersColumn)
                    <th class="center" style="width:50px">Users</th>
                @endif

                <th class="center" style="width:40px">Qty</th>
                <th class="right" style="width:80px">Rate ({{ $currency }})</th>
                <th class="right" style="width:90px">Total ({{ $currency }})</th>
            </tr>
        </thead>

        <tbody>
            @foreach($invoice->items as $idx => $item)
                @php
                    $freq = $item->frequency ?? '';
                    $dur = $item->duration ?? null;
                    $durationLabel = ($freq && $freq !== 'One-Time' && $dur)
                        ? "$dur $freq"
                        : ($freq ?: 'One-Time');
                @endphp

                <tr>
                    <td>{{ $idx + 1 }}</td>

                    <td>
                        <div class="item-name">
                            {{ $item->item_name }}
                        </div>

                       @if(!empty($item->item_description))
                            <div class="item-desc">{{ trim($item->item_description) }}</div>
                        @endif
                    </td>

                    <td class="center">
                        <div>{{ $durationLabel }}</div>
                        @if(!empty($item->start_date) && !empty($item->end_date))
                            <div style="font-size: 8pt; color: #000; margin-top: 2pt;">
                                {{ $item->start_date->format('d M Y') }} - {{ $item->end_date->format('d M Y') }}
                            </div>
                        @endif
                    </td>

                    @if($hasUsersColumn)
                        <td class="center">
                            {{ !empty($item->no_of_users) ? (int) $item->no_of_users : '-' }}
                        </td>
                    @endif

                    <td class="center">
                        {{ (int)($item->quantity ?? 1) }}
                    </td>

                    <td class="right">
                        {{ number_format((float)($item->unit_price ?? 0), 0) }}
                    </td>

                    <td class="right">
                        {{ number_format((float)($item->line_total ?? 0), 0) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-top: -9pt; border-top: none;">
        <tr style="font-size: 8.5pt; background: #fff;">
            <td class="right" style="width: 12.5%; padding: 4pt 5pt; vertical-align: middle; border-top: 1px solid #000;"><strong>CGST</strong></td>
            <td class="center" style="width: 12.5%; padding: 4pt 5pt; vertical-align: middle; border-top: 1px solid #000;">{{ $cgst > 0 ? number_format($cgst, 0) : '-' }}</td>
            <td class="right" style="width: 12.5%; padding: 4pt 5pt; vertical-align: middle; border-top: 1px solid #000;"><strong>SGST</strong></td>
            <td class="center" style="width: 12.5%; padding: 4pt 5pt; vertical-align: middle; border-top: 1px solid #000;">{{ $sgst > 0 ? number_format($sgst, 0) : '-' }}</td>
            <td class="right" style="width: 12.5%; padding: 4pt 5pt; vertical-align: middle; border-top: 1px solid #000;"><strong>IGST</strong></td>
            <td class="center" style="width: 12.5%; padding: 4pt 5pt; vertical-align: middle; border-top: 1px solid #000;">{{ $igst > 0 ? number_format($igst, 0) : '-' }}</td>
            <td class="right" style="width: 12.5%; padding: 4pt 5pt; vertical-align: middle; border-top: 1px solid #000;"><strong>Tax Total</strong></td>
            <td class="right" style="width: 12.5%; padding: 4pt 5pt; vertical-align: middle; border-top: 1px solid #000;">{{ number_format($taxTotal, 0) }}</td>
        </tr>
    </table>

    <div class="totals-wrap">
        <div class="totals-box">
            <div class="total-row total-grand">
                <span>Grand Total:</span>
                <span>{{ number_format($grandTotal, 0) }}</span>
            </div>
        </div>
    </div>

    @if(!empty($invoice->notes))
        <div class="notes-section">{{ trim($invoice->notes) }}</div>
    @endif

    @if(!empty($invoiceTerms) && is_array($invoiceTerms))
        <div class="terms-section">
            <div class="terms-title">
                Terms &amp; Conditions
            </div>

            <ul class="terms-list">
                @foreach(array_filter($invoiceTerms) as $term)
                    <li>{{ trim($term) }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="signatory">
        <div class="signatory-box">
            @if(!empty($signatureUrl))
                <img src="{{ $signatureUrl }}" class="sig-img" alt="Signature">
            @endif

            <div class="sig-line">
                {{ $accountBillingDetail->authorize_signatory ?? $accountBillingDetail->billing_name ?? $account->name }}
            </div>
        </div>
    </div>
</body>
</html>
