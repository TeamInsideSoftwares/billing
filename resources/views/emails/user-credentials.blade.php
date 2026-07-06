<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div style="background-color: #ffffff; padding: 20px; border-radius: 5px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <h2 style="color: #333333;">Welcome, {{ $user->name }}!</h2>
        
        <p style="color: #555555; font-size: 16px;">
            An account has been created for you. Below are your login credentials:
        </p>

        <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0;">
            <p style="margin: 0 0 10px 0; color: #333;"><strong>Email:</strong> {{ $user->email }}</p>
            <p style="margin: 0; color: #333;"><strong>Password:</strong> {{ $password }}</p>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.team_url') }}/login" style="background-color: #007bff; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">Login to your account</a>
        </div>

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
</body>
</html>
