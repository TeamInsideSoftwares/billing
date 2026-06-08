<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Reset your password</title>
</head>

<body style="margin:0;padding:0;background:#f4f6f8;font-family:Inter,system-ui,Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center" style="padding:40px 16px;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                    style="max-width:600px;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 24px 80px rgba(15,23,42,0.12);">
                    <tr>
                        <td style="padding:32px 32px 24px;">
                            <p
                                style="margin:0 0 14px;font-size:0.85rem;font-weight:700;color:#2563eb;text-transform:uppercase;letter-spacing:0.08em;">
                                {{ config('app.name') }}</p>
                            <h1 style="margin:0 0 16px;font-size:28px;line-height:1.1;color:#0f172a;">Reset your
                                password</h1>
                            <p style="margin:0;font-size:16px;line-height:1.75;color:#475569;">Hi
                                {{ $notifiable->name ?? $notifiable->email }},</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 32px;">
                            <p style="margin:0 0 24px;font-size:16px;line-height:1.75;color:#475569;">We received a
                                request to reset the password for your account. Tap the button below to choose a new
                                password.</p>
                            <div style="text-align:center;margin:0 0 32px;">
                                <a href="{{ $url }}"
                                    style="display:inline-block;padding:14px 24px;background:#2563eb;color:#ffffff;border-radius:12px;font-weight:700;text-decoration:none;">Reset
                                    Password</a>
                            </div>
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.75;color:#475569;">If the button
                                doesn’t work, copy and paste this link into your browser:</p>
                            <p
                                style="word-break:break-word;margin:0 0 24px;font-size:14px;line-height:1.7;color:#475569;">
                                {{ $url }}</p>
                            <p style="margin:0;font-size:16px;line-height:1.75;color:#475569;">If you did not request
                                this password reset, please ignore this email and your password will remain unchanged.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 32px;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:14px;line-height:1.7;color:#64748b;">
                                Thanks,<br>{{ config('app.name') }} Team</p>
                        </td>
                    </tr>
                </table>
                <p style="margin:24px 0 0;font-size:13px;line-height:1.6;color:#94a3b8;max-width:600px;">If you don’t
                    recognize this request, no action is needed. This email was sent to {{ $notifiable->email }}.</p>
            </td>
        </tr>
    </table>
</body>

</html>
