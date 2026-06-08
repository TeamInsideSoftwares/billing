<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation - {{ $quotation->quo_number }}</title>

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
            margin-bottom: 6pt;
            padding-bottom: 5pt;
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
            flex-shrink: 0;
            max-width: 45%;
            padding-top: 2pt;
            font-size: 10pt;
            font-weight: 700;
            color: #000;
            text-align: right;
        }

        .invoice-title-text {
            margin-bottom: 6pt;
        }

        table {
            width: 100%;
            margin-bottom: 8pt;
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
            font-size: 10pt;
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
            margin-top: 9pt;
            padding: 7pt 0;
            border-top: 1px solid #000;
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
            line-height: 1;
            color: #000;
            list-style: disc;
        }

        .terms-list li {
            margin-bottom: 2pt;
        }

        .signatory {
            display: flex;
            justify-content: flex-end;
        }

        .signatory-box {
            min-width: 220px;
            text-align: right;
            display: inline-block;

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
            position: relative;
            padding-top: 0.45rem;
            font-size: 9pt;
            color: #000;
        }

        .sig-line::before {
            content: "";
            position: absolute;
            top: 0;
            left: 66%;
            width: 150px;
            height: 0.6px;
            background: #000;
            transform: translateX(-50%);
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="from-block">
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
        </div>

        <div class="right-block">
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
        </div>
    </div>

    <div class="bill-to-section">
        <div class="bill-to-block">
            <div class="bill-to-label">
                Bill To
            </div>

            <div class="client-name">
                {{ $quotation->client->business_name ?? ($quotation->client->contact_name ?? 'Client') }}
            </div>

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
        </div>

        @if (!empty($quotation->quo_title))
            <div class="invoice-title-note">
                <div class="invoice-title-text">{{ $quotation->quo_title }}</div>
            </div>
        @endif
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

    <table>
        <thead>
            <tr>
                <th style="width:2%">#</th>
                <th style="width:50%">Description</th>
                <th class="center" style="width:20%">Duration</th>

                @if ($hasUsersColumn)
                    <th class="center" style="width:5%">User</th>
                @endif

                <th class="center" style="width:5%">Qty</th>
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
                        <div class="item-name">
                            {{ $item->item_name }}
                        </div>

                        @if (!empty($item->item_description))
                            <div class="item-desc">{{ trim($item->item_description) }}</div>
                        @endif
                    </td>

                    <td class="center" style="vertical-align: middle;">
                        <b>{{ $durationLabel }}</b>
                        @if (!empty($item->start_date) && !empty($item->end_date))
                            <div style="font-size: 7pt; color: #000; margin-top: 2pt;">
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

                <td class="right" style="vertical-align: middle;">
                    <b>{{ number_format($discountedSubtotal, 0) }}</b>
                </td>
            </tr>
            <tr style="font-size: 8.5pt; background: #fff;">
                <td class="right" colspan="{{ $hasUsersColumn ? 5 : 4 }}"
                    style="width: 12.5%; padding: 4pt 5pt; vertical-align: middle; border-top: 1px solid #000;">
                    <strong>CGST</strong>

                    {{ $cgst > 0 ? number_format($cgst, 0) : '0' }}
                    <strong>SGST</strong>

                    {{ $sgst > 0 ? number_format($sgst, 0) : '0' }}
                    <strong>IGST</strong>

                    {{ $igst > 0 ? number_format($igst, 0) : '0' }}
                </td>
                <td class="right" style="padding: 4pt 5pt; vertical-align: middle; border-top: 1px solid #000;">
                    <strong>GST</strong>
                </td>
                <td class="right" style="padding: 4pt 5pt; vertical-align: middle; border-top: 1px solid #000;">
                    <b> {{ number_format($taxTotal, 0) }}</b>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="totals-wrap">
        <div class="totals-box">
            <div class="total-row total-grand" style="font-size: 14pt;">
                <span>Total Payable: </span>
                <span>{{ number_format($grandTotal, 0) }}</span>
            </div>
        </div>
    </div>

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

    <div class="signatory">
        <div class="signatory-box">
            @if (!empty($signatureUrl))
                <img src="{{ $signatureUrl }}" class="sig-img" alt="Signature">
            @endif

            <div class="sig-line">
                {{ $accountBillingDetail->authorize_signatory ?? '' }}
            </div>
            <div>
                {{ $accountBillingDetail->billing_name ?? $account->name }}
            </div>
        </div>
    </div>
</body>

</html>
