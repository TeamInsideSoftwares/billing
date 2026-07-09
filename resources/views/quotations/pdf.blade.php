<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation - {{ $quotation->quo_number }}</title>

    <style>
        @page {
            margin: 40px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Helvetica, sans-serif;
            font-size: 9pt;
            color: #000;
            background: #fff;
            padding: 40px;
        }

        .layout-table {
            width: 100%;
            border-collapse: collapse;
        }

        .layout-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .header {
            margin-bottom: 6pt;
            padding-bottom: 5pt;
            border-bottom: 2px solid #000;
        }

        .from-block {
            width: 56%;
        }

        .company-name {
            margin: 4pt 0 1pt 0;
            font-size: 10pt;
            font-weight: bold;
            color: #000;
        }

        .address {
            margin: 2pt 0;
            font-size: 9pt;
            color: #000;
            line-height: 1pt;
        }

        .gstin {
            margin: 1pt 0;
            font-size: 9pt;
            color: #000;
        }

        .right-block {
            width: 44%;
            text-align: right;
        }

        .doc-title {
            font-size: 16pt;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            margin-bottom: 6pt;
        }

        .logo {
            max-width: 160px;
            max-height: 64px;
            margin-bottom: 8pt;
        }

        .meta-box {
            text-align: right;
        }

        .meta-row {
            margin: 1pt 0;
            font-size: 9pt;
        }

        .bill-to-section {
            margin-bottom: 9pt;
        }

        .bill-to-block {
            padding: 0pt 9pt 2pt 0;
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
            width: 45%;
            padding-top: 2pt;
            font-size: 10pt;
            font-weight: 700;
            color: #000;
            text-align: right;
        }

        .invoice-title-text {
            margin-bottom: 6pt;
        }

        table.items-table {
            width: 100%;
            margin-bottom: 8pt;
            border-collapse: collapse;
            border: 1pt solid #444;
        }

        table.items-table thead {
            color: #000;
            background: #fff;
        }

        table.items-table th {
            padding: 4pt 6pt;
            border: 1pt solid #444;
            border-bottom: 1pt solid #444;
            font-size: 8.5pt;
            font-weight: bold;
        }

        th.left,
        td.left {
            text-align: left!important;
        }  
        th.center,
        td.center {
            text-align: center!important;
        }

        th.right,
        td.right {
            text-align: right!important;
        }

        table.items-table td {
            padding: 4pt 6pt;
            border: 1pt solid #333;
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
            text-align: right;
        }

        .totals-box {
            min-width: 200px;
            padding: 2pt 5pt;
        }

        .total-row {
            text-align: right;
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
            margin-top: 0;
            padding: 2pt 0;
        }

        .terms-title {
            margin: 0;
            font-size: 9pt;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .terms-list {
            margin: 0;
            font-size: 9pt;
            line-height: 1.1;
            color: #000;
        }

        .terms-list ul, 
        .terms-list ol {
            margin-top: 2px;
            margin-bottom: 2px;
            padding-left: 14px;
        }

        .terms-list li {
            margin-bottom: 1px;
        }

        .terms-list p {
            margin: 2px 0;
        }

        .signatory {
            text-align: right;
        }

        .signatory-box {
            min-width: 220px;
            text-align: right;
        }

        .sig-img {
            max-width: 130px;
            max-height: 52px;
            margin-bottom: 0.25rem;
        }

        .sig-line {
            padding-top: 5pt;
            margin-top: 5pt;
            border-top: 1px solid #000;
            font-size: 9pt;
            color: #000;
            display: inline-block;
            min-width: 150px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <table class="layout-table">
            <tr>
                <td class="from-block">
            @if ($account->logo_path)
                @php
                    $logoSrc =
                        str_starts_with($account->logo_path, 'http://') ||
                        str_starts_with($account->logo_path, 'https://')
                            ? $account->logo_path
                            : public_path(
                                str_starts_with($account->logo_path, 'storage/')
                                    ? $account->logo_path
                                    : 'storage/' . ltrim($account->logo_path, '/'),
                            );
                @endphp
                <img src="{{ $logoSrc }}" class="logo" alt="Logo">
            @endif

            <div class="company-name">
                {{ $accountBillingDetail->billing_name ?? $account->name }}
            </div>

            @php
                $addrParts = array_filter([
                    $accountBillingDetail->address ?? '',
                    implode(
                        ', ',
                        array_filter([$accountBillingDetail->city ?? '', $accountBillingDetail->state ?? '']),
                    ),
                    $accountBillingDetail->postal_code ?? '',
                    $accountBillingDetail->country ?? '',
                ]);
            @endphp

            @if (count($addrParts))
                <div class="address">
                    {!! implode(', ', $addrParts) !!}
                </div>
            @endif

            @if (!empty($accountBillingDetail->gstin))
                <div class="gstin">
                    <strong>GSTIN:</strong> {{ $accountBillingDetail->gstin }}
                </div>
            @endif
                </td>
                <td class="right-block">
            <div class="doc-title">
                QUOTATION
            </div>

            <div class="meta-box">
                <div class="meta-row">
                    <strong>Quotation No:</strong>
                    {{ $quotation->quo_number ?: $quotation->quotationid }}
                </div>

                <div class="meta-row">
                    <strong>Issue Date:</strong>
                    {{ optional($quotation->issue_date)->format('d M Y') ?? '-' }}
                </div>

                <div class="meta-row">
                    <strong>Due Date:</strong>
                    {{ optional($quotation->due_date)->format('d M Y') ?? '-' }}
                </div>
            </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="bill-to-section">
        <table class="layout-table" style="width: 100%;">
            <tr>
                <td class="bill-to-block" style="width: 60%; vertical-align: top;">
            <div class="bill-to-label">
                Bill To
            </div>

            <div><b>
                {{ $quotation->client->business_name ?? ($quotation->client->contact_name ?? 'Client') }}
            </b></div>

            @php
                $cb = optional($quotation->client)->billingDetail;

                $clientAddrParts = array_filter([
                    optional($cb)->address_line_1 ?? '',
                    implode(', ', array_filter([optional($cb)->city ?? '', optional($cb)->state ?? ''])),
                    optional($cb)->postal_code ?? '',
                    optional($cb)->country ?? '',
                ]);
            @endphp

            @if (count($clientAddrParts))
                <div class="address">
                    {!! implode(', ', $clientAddrParts) !!}
                </div>
            @endif

            @if (!empty(optional($cb)->gstin))
                <div class="client-gstin">
                    <strong>GSTIN:</strong> {{ $cb->gstin }}
                </div>
            @endif
                </td>

                <td class="invoice-title-note" style="width: 40%; text-align: right; vertical-align: top;">
                @if (!empty($quotation->quo_title))
                    <div class="invoice-title-text">{{ $quotation->quo_title }}</div>
                @endif
                </td>
            </tr>
        </table>
    </div>

    @php
        $hasRecurring = $quotation->items->some(fn($i) => !empty($i->frequency) && $i->frequency !== 'One-Time');
        $currency = $quotation->client->currency ?? 'INR';
        $accountHasUsers = (bool) ($account->have_users ?? false);
        $hasUsersColumn =
            $accountHasUsers &&
            $quotation->items->contains(fn($i) => !empty($i->no_of_users) && (int) $i->no_of_users > 0);

        $subtotal = 0;
        $discountTotal = 0;
        $discountedSubtotal = 0;
        $taxTotal = 0;

        foreach ($quotation->items as $item) {
            $lt = (float) ($item->line_total ?? 0);
            $discountPercent = max(0, min(100, (float) ($item->discount_percent ?? 0)));
            $discountedAmount = max(0, $lt - ($lt * $discountPercent) / 100);

            $ta = ceil($discountedAmount * ((float) ($item->tax_rate ?? 0) / 100));

            $subtotal += $lt;
            $discountedSubtotal += $discountedAmount;
            $discountTotal += max(0, $lt - $discountedAmount);
            $taxTotal += $ta;
        }

        $discountedSubtotal = floor($discountedSubtotal);
        $discountTotal = floor($discountTotal);
        $taxTotal = ceil($taxTotal);

        $grandTotal = $discountedSubtotal + $taxTotal;

        $cgst = $sameStateGst ? $taxTotal / 2 : 0;
        $sgst = $sameStateGst ? $taxTotal - $cgst : 0;
        $igst = $sameStateGst ? 0 : $taxTotal;
    @endphp

    <table class="items-table">
        <thead>
            <tr>
                <th class="center" style="width:5%">#</th>
                <th class="left" style="width:45%">Description</th>
                <th class="center" style="width:20%">Duration</th>

                @if ($hasUsersColumn)
                    <th class="center" style="width:7%">User</th>
                @endif

                <th class="center" style="width:8%">Qty</th>
                <th class="right" style="width:8%">Rate</th>
                <th class="right" style="width:10%">Total</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($quotation->items as $idx => $item)
                @php
                    $freq = $item->frequency ?? '';
                    $dur = $item->duration ?? null;
                    $durationLabel = $freq && $freq !== 'One-Time' && $dur ? "$dur $freq" : ($freq ?: 'One-Time');
                    $quantity = max(1, (int) ($item->quantity ?? 1));
                    $baseLineTotal = max(0, (float) ($item->line_total ?? 0));
                    $discountPercent = max(0, min(100, (float) ($item->discount_percent ?? 0)));
                    $discountedAmount = max(0, $baseLineTotal - ($baseLineTotal * $discountPercent) / 100);
                    $discountedRate = $quantity > 0 ? $discountedAmount / $quantity : 0;
                    $discountedRateLabel = preg_replace('/\.00$/', '', number_format($discountedRate, 2, '.', ''));
                    $discountedAmountLabel = preg_replace('/\.00$/', '', number_format($discountedAmount, 2, '.', ''));
                @endphp

                <tr>
                    <td>{{ $idx + 1 }}</td>

                    <td>
                        <div><b>{{ $item->item_name }}</b></div>
                        @if (!empty($item->item_description))
                            <div class="item-desc">{!! nl2br(e($item->item_description)) !!}</div>
                        @endif
                    </td>

                    <td class="center">
                        <div>{{ $durationLabel }}</div>
                        @if (!empty($item->start_date) && !empty($item->end_date))
                            <div style="font-size: 7pt; color: #555; margin-top: 2pt;">
                                {{ $item->start_date->format('d M Y') }} - {{ $item->end_date->format('d M Y') }}
                            </div>
                        @endif
                    </td>

                    @if ($hasUsersColumn)
                        <td class="center" style="vertical-align: middle;">
                            {{ !empty($item->no_of_users) ? (int) $item->no_of_users : '-' }}
                        </td>
                    @endif

                    <td class="center" style="vertical-align: middle;">
                        {{ $quantity }}
                    </td>

                    <td class="right" style="vertical-align: middle;">
                        {{ $discountedRateLabel }}
                    </td>

                    <td class="right" style="vertical-align: middle;">
                        <b> {{ $discountedAmountLabel }}</b>
                    </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="{{ $hasUsersColumn ? 6 : 5 }}" style="text-align:right;">
                    <b>Total</b>
                </td>

                <td class="right" style="padding-right: 5px;">
                    <b>{{ number_format($discountedSubtotal, 0) }}</b>
                </td>
            </tr>
            <tr style="font-size: 8.5pt; background: #fff;">
                <td class="right" colspan="{{ $hasUsersColumn ? 5 : 4 }}"
                    style="padding: 3pt 4pt; vertical-align: middle; border-top: 1px solid #000;">
                    CGST

                    {{ $cgst > 0 ? number_format($cgst, 0) : '0' }}
                    + SGST

                    {{ $sgst > 0 ? number_format($sgst, 0) : '0' }}
                    + IGST

                    {{ $igst > 0 ? number_format($igst, 0) : '0' }}
                </td>
                <td class="right" style="padding: 3pt 4pt; vertical-align: middle; border-top: 1px solid #000;">
                    <strong>GST</strong>
                </td>
                <td class="right" style="padding: 3pt 4pt; vertical-align: middle; border-top: 1px solid #000;">
                    <b> {{ number_format($taxTotal, 0) }}</b>
                </td>
            </tr>
        </tbody>
    </table>

    <table style="width: 100%; margin-top: 5px;">
        <tr>
            <td style="text-align: right; font-size: 14pt; font-weight: bold; border: none; padding: 0px 0px 5px;">
                Amount Payable: ₹{{ number_format($grandTotal, 0) }}
                <div style="font-size: 9pt; font-weight: normal; color: #000; margin-top: 2px;">
                    Rupees {{ ucwords(\Illuminate\Support\Number::spell($grandTotal)) }} Only
                </div>
            </td>
        </tr>
    </table>
    <div style="border-top: 1px solid #444; width: 100%; margin: 5px 0 2px 0;"></div>

    @if (!empty($quotation->notes))
        <div class="notes-section">{{ trim($quotation->notes) }}</div>
    @endif

    @if (!empty($quotationTerms) && is_array($quotationTerms))
        <div class="terms-section">
            <div class="terms-title">
                Terms
            </div>

            <ul class="terms-list">
                @foreach (array_filter($quotationTerms) as $term)
                    <li>{!! trim($term) !!}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <table style="width: 100%; margin-top: 0px;">
        <tr>
            <td style="width: 60%; border: none;"></td>
            <td style="width: 40%; border: none;" align="right">
                <table style="width: 160px; text-align: center; border: none;">
                    <tr>
                        <td style="border: none; padding-bottom: 5px; height: 60px; vertical-align: bottom;">
                            @if (!empty($signatureUrl))
                                <img src="{{ $signatureUrl }}" style="max-width: 130px; max-height: 52px;" alt="Signature">
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="border: none; padding-top: 5px; font-size: 9pt;">
                            {{ $accountBillingDetail->authorize_signatory ?? '' }}<br>
                            {{ $accountBillingDetail->billing_name ?? $account->name }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
