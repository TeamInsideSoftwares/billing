@extends('emails.layout')

@section('title', 'Your Order Summary')

@section('content')
    <h1 class="email-title">Your Order Summary</h1>
    <p class="email-text">Hi <strong>{{ $clientName }}</strong>,</p>
    
    <p class="email-text">Here is your consolidated order summary as on <strong>{{ $today }}</strong>.</p>

    <div style="overflow-x: auto; margin-bottom: 10px;">
        <table style="width:100%;border-collapse:collapse;margin-top:5px;font-size:14px;color:#334155;border: 1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
            <thead>
                <tr>
                    <th style="border-bottom:1px solid #e2e8f0;padding:12px;text-align:left;vertical-align:middle;background:#f8fafc;font-weight:600;">Item</th>
                    <th style="border-bottom:1px solid #e2e8f0;padding:12px;text-align:center;vertical-align:middle;background:#f8fafc;font-weight:600;">Expiry Date</th>
                    <th style="border-bottom:1px solid #e2e8f0;padding:12px;text-align:center;vertical-align:middle;background:#f8fafc;font-weight:600;">Status</th>
                    <th style="border-bottom:1px solid #e2e8f0;padding:12px;text-align:center;vertical-align:middle;background:#f8fafc;font-weight:600;">Days Left</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $row)
                    <tr>
                        
                        <td style="border-bottom:1px solid #e2e8f0;padding:12px;text-align:left;vertical-align:middle;">
                            <div style="color:#0f172a;font-weight:600;">{{ $row['item_name'] }}</div>
                            <div style="font-size:12px;color:#64748b;margin-top:4px;">
                                {{ $row['item_description'] !== '' ? $row['item_description'] : '-' }}</div>
                        </td>
                        <td style="border-bottom:1px solid #e2e8f0;padding:12px;text-align:center;vertical-align:middle;">
                            {{ $row['end_date'] }}</td>
                        @php
                            $isExpired = str_contains(strtolower((string) ($row['days_label'] ?? '')), 'ago');
                            $daysColor = $isExpired ? '#ef4444' : '#10b981';
                        @endphp
                        <td style="border-bottom:1px solid #e2e8f0;padding:12px;text-align:center;vertical-align:middle;color:{{ $daysColor }};font-weight:600;">
                            {{ $isExpired ? 'Expired' : 'Active' }}
                        </td>
                        <td style="border-bottom:1px solid #e2e8f0;padding:12px;text-align:center;vertical-align:middle;color:{{ $daysColor }};">
                            {{ $row['days_label'] }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="email-text">Please review and plan renewals wherever required.</p>

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
                <div class="info-row" style="font-size: 13px;"><strong>E-mail:</strong> <a href="mailto:{{ $senderEmail }}" style="color: #2563eb; text-decoration: none;">{{ $senderEmail }}</a></div>
            @endif
        </div>
    @endif
@endsection
