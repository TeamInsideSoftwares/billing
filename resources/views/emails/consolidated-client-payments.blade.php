@extends('emails.layout')

@section('title', 'Outstanding Payments Summary')

@section('content')
    @php
        $formatAmount = function ($value) {
            $amount = (float) $value;
            $formatted = number_format($amount, 2, '.', ',');
            $formatted = rtrim(rtrim($formatted, '0'), '.');
            return $formatted === '' ? '0' : $formatted;
        };
    @endphp

    <h1 class="email-title">Outstanding Payments Summary</h1>
    <p class="email-text">Hi <strong>{{ $clientName }}</strong>,</p>
    
    <p class="email-text">Here is the consolidated summary of your outstanding payments as of <strong>{{ $today }}</strong>. Please review the details below.</p>

    <div style="overflow-x: auto; margin-bottom: 24px;">
        <table style="width:100%;border-collapse:collapse;margin-top:10px;font-size:14px;color:#334155;border: 1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
            <thead>
                <tr>
                    <th style="border-bottom:1px solid #e2e8f0;padding:12px;text-align:left;vertical-align:middle;background:#f8fafc;font-weight:600;">Invoice #</th>
                    <th style="border-bottom:1px solid #e2e8f0;padding:12px;text-align:left;vertical-align:middle;background:#f8fafc;font-weight:600;">Invoice Title</th>
                    <th style="border-bottom:1px solid #e2e8f0;padding:12px;text-align:center;vertical-align:middle;background:#f8fafc;font-weight:600;">Overdue</th>
                    <th style="border-bottom:1px solid #e2e8f0;padding:12px;text-align:right;vertical-align:middle;background:#f8fafc;font-weight:600;">Outstanding</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $row)
                    <tr>
                        <td style="border-bottom:1px solid #e2e8f0;padding:12px;text-align:left;vertical-align:middle;">
                            <strong style="display:block; color:#0f172a;">{{ $row['invoice_number'] }}</strong>
                            <div style="font-size: 12px; margin-top: 4px;">
                                <a href="{{ $row['pdf_link'] }}" target="_blank" style="color:#0d6efd; text-decoration: none;">View Invoice</a>
                            </div>
                        </td>
                        <td style="border-bottom:1px solid #e2e8f0;padding:12px;text-align:left;vertical-align:middle;color:#0f172a;">
                            @if (!empty($row['invoice_title']))
                                <strong>{{ $row['invoice_title'] }}</strong>
                            @else
                                <span style="color:#64748b;">-</span>
                            @endif
                        </td>
                        <td style="border-bottom:1px solid #e2e8f0;padding:12px;text-align:center;vertical-align:middle;color:#ef4444;font-weight:600;">
                            @php
                                $overdueDays = (int) ($row['overdue_days'] ?? 0);
                                $overdueLabel = $overdueDays === 0 ? '0 days' : $overdueDays . ' days';
                            @endphp
                            {{ $overdueLabel }}
                        </td>
                        <td style="border-bottom:1px solid #e2e8f0;padding:12px;text-align:right;vertical-align:middle;color:#ef4444;font-weight:700;">
                            {{ $currency }} {{ $formatAmount($row['balance_due'] ?? 0) }}
                        </td>
                    </tr>
                @endforeach
                <tr style="background-color: #fef2f2;">
                    <td colspan="3" style="padding: 12px; text-align: right; color: #991b1b; font-weight: 600;">Total Outstanding Balance:</td>
                    <td style="padding: 12px; text-align: right; color: #dc2626; font-weight: 800;">
                        {{ $currency }} {{ $formatAmount($totalOverdueAmount) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="email-text">Please check the download links to obtain copies of individual invoices.</p>

    <p class="email-text" style="margin-top: 35px;">
        Best regards,<br>
        <strong>{{ $senderName ?? 'Team' }}</strong>
    </p>

    @if (!empty($senderAddressLine) || !empty($senderPhone) || !empty($senderEmail))
        <div style="margin-top: 10px;">
            @if (!empty($senderAddressLine))
                <div class="info-row" style="font-size: 13px;">{{ $senderAddressLine }}</div>
            @endif
            @if (!empty($senderPhone))
                <div class="info-row" style="font-size: 13px;"><strong>Mob:</strong> {{ $senderPhone }}</div>
            @endif
            @if (!empty($senderEmail))
                <div class="info-row" style="font-size: 13px;"><strong>E-mail:</strong> <a href="mailto:{{ $senderEmail }}" style="color: #0d6efd; text-decoration: none;">{{ $senderEmail }}</a></div>
            @endif
        </div>
    @endif
@endsection
