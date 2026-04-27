<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $documentType }} - {{ $invoice->invoice_number }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 12px; color: #1f2937; background: #fff; padding: 32px; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 2px solid #111827; gap: 1rem; }
    .from-block { flex: 1 1 auto; min-width: 0; max-width: 56%; }
    .company-name { margin: 0 0 0.12rem 0; font-size: 0.92rem; font-weight: 700; color: #111827; }
    .address { margin: 0.2rem 0; font-size: 0.8rem; color: #4b5563; line-height: 1.45; }
    .gstin { margin: 0.15rem 0; font-size: 0.78rem; color: #374151; }
    .right-block { text-align: right; min-width: 240px; margin-left: auto; display: flex; flex-direction: column; align-items: flex-end; gap: 0.45rem; }
    .doc-type-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; margin-bottom: 0.25rem; }
    .logo { max-width: 140px; max-height: 56px; object-fit: contain; }
    .meta-box { text-align: right; }
    .meta-row { margin: 0.1rem 0; font-size: 0.8rem; }
    .bill-to-section { display: flex; gap: 1rem; margin-bottom: 1rem; align-items: flex-start; }
    .bill-to-block { flex: 1; min-width: 0; padding: 0.8rem 0.95rem 0.8rem 0; }
    .bill-to-label { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; margin-bottom: 0.35rem; }
    .client-name { margin: 0.12rem 0; font-size: 0.92rem; font-weight: 700; color: #111827; }
    .client-detail { margin: 0.2rem 0; font-size: 0.8rem; color: #4b5563; line-height: 1.45; }
    .client-gstin { margin: 0.15rem 0; font-size: 0.78rem; color: #374151; }
    .invoice-title-note { flex-shrink: 0; max-width: 45%; text-align: right; padding-top: 0.2rem; font-size: 0.95rem; font-weight: 700; color: #111827; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; }
    thead { background: #f3f4f6; color: #111827; }
    th { padding: 0.5rem 0.5rem; text-align: left; border: 1px solid #d1d5db; font-size: 0.75rem; font-weight: 600; }
    th.center, td.center { text-align: center; }
    th.right, td.right { text-align: right; }
    td { padding: 0.5rem 0.5rem; border: 1px solid #e5e7eb; font-size: 0.75rem; color: #1f2937; vertical-align: top; }
    .item-name { font-weight: 600; color: #111827; }
    .item-desc { margin-top: 0.1rem; font-size: 0.72rem; color: #6b7280; white-space: pre-wrap; }
    .totals-wrap { display: flex; justify-content: flex-end; }
    .totals-box { min-width: 260px; padding: 0.4rem 0.5rem; }
    .total-row { display: flex; justify-content: space-between; padding: 0.25rem 0; border-bottom: 1px solid #e5e7eb; font-size: 0.75rem; color: #4b5563; }
    .total-row:last-child { border-bottom: none; }
    .total-grand { font-size: 0.95rem; font-weight: 700; color: #111827; }
    .notes-section { margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb; font-size: 0.78rem; color: #6b7280; white-space: pre-wrap; }
    .terms-section { margin-top: 1rem; padding: 0.75rem 0; border-top: 1px solid #e5e7eb; }
    .terms-title { margin: 0 0 0.35rem 0; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em; color: #374151; font-weight: 600; }
    .terms-list { margin: 0; padding-left: 1.25rem; font-size: 0.78rem; line-height: 1.5; color: #4b5563; list-style: disc; }
    .terms-list li { margin-bottom: 0.25rem; }
    .signatory { margin-top: 1.5rem; display: flex; justify-content: flex-end; }
    .signatory-box { text-align: right; min-width: 220px; }
    .sig-img { display: block; margin-left: auto; max-width: 130px; max-height: 52px; object-fit: contain; margin-bottom: 0.25rem; }
    .sig-line { border-top: 1px solid #6b7280; padding-top: 0.3rem; font-size: 0.78rem; color: #374151; }
</style>
</head>
<body>

<div class="header">
    <div class="from-block">
        <div class="company-name">{{ $accountBillingDetail->billing_name ?? $account->name }}</div>
        @php
            $addrParts = array_filter([
                $accountBillingDetail->address ?? '',
                implode(', ', array_filter([$accountBillingDetail->city ?? '', $accountBillingDetail->state ?? ''])),
                $accountBillingDetail->postal_code ?? '',
                $accountBillingDetail->country ?? '',
            ]);
        @endphp
        @if(count($addrParts))
        <div class="address">{!! implode('<br>', $addrParts) !!}</div>
        @endif
        @if(!empty($accountBillingDetail->gstin))
        <div class="gstin"><strong>GSTIN:</strong> {{ $accountBillingDetail->gstin }}</div>
        @endif
    </div>
    <div class="right-block">
        <div class="doc-type-label">{{ $documentType }}</div>
        @if($account->logo_path)
        @php
            $logoSrc = (str_starts_with($account->logo_path, 'http://') || str_starts_with($account->logo_path, 'https://'))
                ? $account->logo_path
                : public_path(str_starts_with($account->logo_path, 'storage/') ? $account->logo_path : 'storage/' . ltrim($account->logo_path, '/'));
        @endphp
        <img src="{{ $logoSrc }}" class="logo" alt="Logo">
        @endif
        <div class="meta-box">
            <div class="meta-row"><strong>{{ $isTaxInvoice ? 'Tax No:' : 'Proforma No:' }}</strong> {{ $isTaxInvoice ? $invoice->ti_number : $invoice->pi_number }}</div>
            <div class="meta-row"><strong>Issue Date:</strong> {{ optional($invoice->issue_date)->format('d M Y') ?? '-' }}</div>
            <div class="meta-row"><strong>Due Date:</strong> {{ optional($invoice->due_date)->format('d M Y') ?? '-' }}</div>
            @if(!empty($invoice->order?->po_number))
            <div class="meta-row"><strong>PO Number:</strong> {{ $invoice->order->po_number }}</div>
            @endif
            @if(!empty($invoice->order?->po_date))
            <div class="meta-row"><strong>PO Date:</strong> {{ optional($invoice->order->po_date)->format('d M Y') ?? '-' }}</div>
            @endif
        </div>
    </div>
</div>

<div class="bill-to-section">
    <div class="bill-to-block">
        <div class="bill-to-label">Bill To</div>
        <div class="client-name">{{ $invoice->client->business_name ?? $invoice->client->contact_name ?? 'Client' }}</div>
        @php
            $cb = $invoice->client->billingDetail;
            $clientAddrParts = array_filter([
                $cb->address_line_1 ?? '',
                implode(', ', array_filter([$cb->city ?? '', $cb->state ?? ''])),
                $cb->postal_code ?? '',
                $cb->country ?? '',
            ]);
        @endphp
        @if(count($clientAddrParts))
        <div class="address">{!! implode('<br>', $clientAddrParts) !!}</div>
        @endif
        @if(!empty($cb->gstin))
        <div class="client-gstin"><strong>GSTIN:</strong> {{ $cb->gstin }}</div>
        @endif
    </div>
    @if(!empty($invoice->invoice_title))
    <div class="invoice-title-note">{{ $invoice->invoice_title }}</div>
    @endif
</div>

@php
    $hasRecurring = $invoice->items->some(fn($i) => !empty($i->frequency) && $i->frequency !== 'One-Time');
    $currency = $invoice->client->currency ?? 'INR';
    $subtotal = 0; $discountTotal = 0; $taxTotal = 0;
    foreach ($invoice->items as $item) {
        $lt = (float)($item->line_total ?? 0);
        $da = (float)($item->discount_amount ?? 0);
        $ta = ceil(max(0, $lt - $da) * ((float)($item->tax_rate ?? 0) / 100));
        $subtotal += $lt; $discountTotal += $da; $taxTotal += $ta;
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
            <th class="center" style="width:40px">Qty</th>
            <th class="center" style="width:50px">Users</th>
            <th class="center" style="width:80px">Duration</th>
            @if($hasRecurring)
            <th class="center" style="width:70px">Start</th>
            <th class="center" style="width:70px">End</th>
            @endif
            <th class="right" style="width:80px">Rate ({{ $currency }})</th>
            <th class="right" style="width:80px">Amount ({{ $currency }})</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $idx => $item)
        @php
            $freq = $item->frequency ?? '';
            $dur = $item->duration ?? null;
            $durationLabel = ($freq && $freq !== 'One-Time' && $dur) ? "$dur $freq" : ($freq ?: 'One-Time');
        @endphp
        <tr>
            <td>{{ $idx + 1 }}</td>
            <td>
                <div class="item-name">{{ $item->item_name }}</div>
                @if(!empty($item->item_description))
                <div class="item-desc">{{ $item->item_description }}</div>
                @endif
            </td>
            <td class="center">{{ (int)($item->quantity ?? 1) }}</td>
            <td class="center">{{ $item->no_of_users ?? '-' }}</td>
            <td class="center">{{ $durationLabel }}</td>
            @if($hasRecurring)
            <td class="center">{{ optional($item->start_date)->format('d M Y') ?? '-' }}</td>
            <td class="center">{{ optional($item->end_date)->format('d M Y') ?? '-' }}</td>
            @endif
            <td class="right">{{ number_format((float)($item->unit_price ?? 0), 0) }}</td>
            <td class="right">{{ number_format((float)($item->line_total ?? 0), 0) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="totals-wrap">
    <div class="totals-box">
        <div class="total-row"><span>Subtotal:</span><strong>{{ number_format($subtotal - $discountTotal, 0) }}</strong></div>
        @if($sameStateGst)
        <div class="total-row"><span>Tax (CGST):</span><strong>{{ number_format($cgst, 0) }}</strong></div>
        <div class="total-row"><span>Tax (SGST):</span><strong>{{ number_format($sgst, 0) }}</strong></div>
        @else
        <div class="total-row"><span>Tax (IGST):</span><strong>{{ number_format($igst, 0) }}</strong></div>
        @endif
        <div class="total-row total-grand"><span>Grand Total:</span><span>{{ number_format($grandTotal, 0) }}</span></div>
    </div>
</div>

@if(!empty($invoice->notes))
<div class="notes-section">{{ $invoice->notes }}</div>
@endif

@if(!empty($invoice->terms) && is_array($invoice->terms))
<div class="terms-section">
    <div class="terms-title">Terms &amp; Conditions</div>
    <ul class="terms-list">
        @foreach(array_filter($invoice->terms) as $term)
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
        <div class="sig-line">{{ $accountBillingDetail->authorize_signatory ?? $accountBillingDetail->billing_name ?? $account->name }}</div>
    </div>
</div>

</body>
</html>
