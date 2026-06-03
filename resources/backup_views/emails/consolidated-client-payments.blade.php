<div
    style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #333333; max-width: 750px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px; background-color: #ffffff;">
    @php
        $formatAmount = function ($value) {
            $amount = (float) $value;
            $formatted = number_format($amount, 2, '.', ',');
            $formatted = rtrim(rtrim($formatted, '0'), '.');

            return $formatted === '' ? '0' : $formatted;
        };
    @endphp

    <p style="font-size: 16px; margin-top: 0;">Hi <strong>{{ $clientName }}</strong>,</p>

    <p style="margin-bottom: 25px;">
        Here is the consolidated summary of your outstanding payments as of <strong>{{ $today }}</strong>. Please
        review the details below.
    </p>

    <table style="width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 15px; margin-bottom: 25px;">
        <colgroup>
            <col style="width: 22%;">
            <col style="width: 38%;">
            <col style="width: 18%;">
            <col style="width: 22%;">
        </colgroup>
        <thead>
            <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                <th
                    style="padding: 12px 10px; text-align: left; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; border: 1px solid #e2e8f0; word-break: break-word;">
                    Invoice #</th>
                <th
                    style="padding: 12px 10px; text-align: left; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; border: 1px solid #e2e8f0; word-break: break-word;">
                    Invoice Title</th>
                <th
                    style="padding: 12px 10px; text-align: center; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; border: 1px solid #e2e8f0; word-break: break-word;">
                    Overdue</th>
                <th
                    style="padding: 12px 10px; text-align: right; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; border: 1px solid #e2e8f0; word-break: break-word;">
                    Outstanding</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $row)
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td
                        style="padding: 12px 10px; font-size: 13px; color: #0f172a; border: 1px solid #e2e8f0; word-break: break-word;">
                        <strong
                            style="display:block; font-size: 14px; color: #0f172a;">{{ $row['invoice_number'] }}</strong>
                        <div style="font-size: 11px; margin-top: 3px;">
                            <a href="{{ $row['pdf_link'] }}" target="_blank"
                                style="color: #64748b; text-decoration: underline;">View</a>
                        </div>
                    </td>
                    <td
                        style="padding: 12px 10px; font-size: 13px; text-align: left; color: #0f172a; border: 1px solid #e2e8f0; word-break: break-word;">
                        @if (!empty($row['invoice_title']))
                            <strong
                                style="display:block; font-size: 14px; color: #0f172a;">{{ $row['invoice_title'] }}</strong>
                        @else
                            <span style="color: #64748b;">-</span>
                        @endif
                    </td>
                    <td
                        style="padding: 12px 10px; font-size: 13px; text-align: center; color: #dc2626; font-weight: 700; border: 1px solid #e2e8f0; white-space: nowrap;">
                        @php
                            $overdueDays = (int) ($row['overdue_days'] ?? 0);
                            $overdueLabel = $overdueDays === 0 ? '0 days overdue' : $overdueDays . ' days overdue';
                        @endphp
                        {{ $overdueLabel }}
                    </td>
                    <td
                        style="padding: 12px 10px; text-align: right; font-size: 13px; color: #dc2626; font-weight: 800; border: 1px solid #e2e8f0; white-space: nowrap;">
                        {{ $currency }} {{ $formatAmount($row['balance_due'] ?? 0) }}
                    </td>
                </tr>
            @endforeach
            <tr style="background-color: #fef2f2; font-weight: bold; border-top: 2px solid #fecaca;">
                <td colspan="3"
                    style="padding: 12px 10px; font-size: 13px; text-align: right; color: #991b1b; border: 1px solid #e2e8f0;">
                    Total Outstanding Balance:</td>
                <td
                    style="padding: 12px 8px; font-size: 13px; text-align: right; color: #dc2626; font-weight: 800; border: 1px solid #e2e8f0; white-space: nowrap;">
                    {{ $currency }} {{ $formatAmount($totalOverdueAmount) }}
                </td>
            </tr>
        </tbody>
    </table>

    <p style="margin-top: 20px; font-size: 13px; color: #475569;">
        Please check the download links to obtain copies of individual invoices.
    </p>

    <div style="margin-top: 35px; padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 13px; color: #475569;">
        <p style="margin: 0; font-weight: 600; color: #1e293b;">Best regards,</p>
        <p style="margin: 3px 0 10px 0; font-weight: 600; color: #0f172a; font-size: 14px;">{{ $senderName ?? 'Team' }}
        </p>

        @if (!empty($senderAddressLine))
            <div style="margin-bottom: 3px; font-size: 12px;">{{ $senderAddressLine }}</div>
        @endif
        @if (!empty($senderPhone))
            <div style="margin-bottom: 3px; font-size: 12px;"><strong>Mob:</strong> {{ $senderPhone }}</div>
        @endif
        @if (!empty($senderEmail))
            <div style="font-size: 12px;"><strong>E-mail:</strong> <a href="mailto:{{ $senderEmail }}"
                    style="color: #2563eb; text-decoration: none;">{{ $senderEmail }}</a></div>
        @endif
    </div>
</div>
