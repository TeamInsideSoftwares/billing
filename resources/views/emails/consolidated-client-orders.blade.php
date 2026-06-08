<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.5;color:#111;">
    <p>Hi {{ $clientName }},</p>

    <p>
        Here is your consolidated order summary as of {{ $today }}.
    </p>

    <table style="width:75%;border-collapse:collapse;margin-top:10px;">
        <thead>
            <tr>
                <th
                    style="border:1px solid #ddd;padding:8px;text-align:center;vertical-align:middle;background:#f8f8f8;">
                    Order Number</th>
                <th
                    style="border:1px solid #ddd;padding:8px;text-align:center;vertical-align:middle;background:#f8f8f8;">
                    Item</th>
                <th
                    style="border:1px solid #ddd;padding:8px;text-align:center;vertical-align:middle;background:#f8f8f8;">
                    Qty</th>
                <th
                    style="border:1px solid #ddd;padding:8px;text-align:center;vertical-align:middle;background:#f8f8f8;">
                    End Date</th>
                <th
                    style="border:1px solid #ddd;padding:8px;text-align:center;vertical-align:middle;background:#f8f8f8;">
                    Days</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $row)
                <tr>
                    <td style="border:1px solid #ddd;padding:8px;text-align:center;vertical-align:middle;">
                        {{ $row['order_number'] }}</td>
                    <td style="border:1px solid #ddd;padding:8px;text-align:left;vertical-align:middle;">
                        <div><strong>{{ $row['item_name'] }}</strong></div>
                        <div style="font-size:12px;color:#555;margin-top:2px;">
                            {{ $row['item_description'] !== '' ? $row['item_description'] : '-' }}</div>
                    </td>
                    <td style="border:1px solid #ddd;padding:8px;text-align:center;vertical-align:middle;">
                        {{ $row['qty'] }}</td>
                    <td style="border:1px solid #ddd;padding:8px;text-align:center;vertical-align:middle;">
                        {{ $row['end_date'] }}</td>
                    @php
                        $isExpired = str_contains(strtolower((string) ($row['days_label'] ?? '')), 'ago');
                        $daysColor = $isExpired ? '#dc2626' : '#16a34a';
                    @endphp
                    <td
                        style="border:1px solid #ddd;padding:8px;text-align:center;vertical-align:middle;color:{{ $daysColor }};font-weight:600;">
                        {{ $row['days_label'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top:14px;">Please review and plan renewals wherever required.</p>

    <p>Best regards,<br>{{ $senderName ?? 'Team' }}</p>
    <p style="margin:0;">
        @if (!empty($senderAddressLine))
            {{ $senderAddressLine }}<br>
        @endif
        @if (!empty($senderPhone))
            Mob: {{ $senderPhone }}<br>
        @endif
        @if (!empty($senderEmail))
            E-mail: {{ $senderEmail }}
        @endif
    </p>
</div>
