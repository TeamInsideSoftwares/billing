<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #334155; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8fafc; padding-bottom: 60px; padding-top: 40px; }
        .main { margin: 0 auto; width: 100%; max-width: 600px; border-spacing: 0; color: #0f172a; background-color: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .top-bar { height: 6px; background-color: #0d6efd; width: 100%; }
        .content { padding: 27px 20px; }
        .logo { font-size: 16px; font-weight: 800; color: #0d6efd; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 24px; text-decoration: none; display: inline-block; }
        .email-title { font-size: 26px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 16px; line-height: 1.3; }
        .email-badge { display: inline-block; background-color: #eff6ff; color: #3b82f6; border-radius: 20px; padding: 6px 14px; font-size: 13px; font-weight: 600; margin-bottom: 24px; border: 1px solid #dbeafe; }
        .email-text { font-size: 15px; line-height: 1.6; color: #334155; margin-top: 0; margin-bottom: 20px; }
        .info-card { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 24px; }
        .info-title { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-top: 0; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; }
        .info-row { font-size: 14px; color: #334155; margin-bottom: 8px; }
        .info-row:last-child { margin-bottom: 0; }
        .info-row strong { color: #0f172a; }
        .btn-wrapper { text-align: center; margin: 32px 0; }
        .primary-btn { display: inline-block; background-color: #0d6efd; color: #ffffff !important; text-decoration: none; font-weight: 600; font-size: 15px; padding: 14px 28px; border-radius: 6px; box-shadow: 0 4px 6px -1px rgba(13, 110, 253, 0.2); }
        .fallback-text { font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 8px; margin-top: 32px; }
        .fallback-link { font-size: 13px; color: #3b82f6; word-break: break-all; }
        .footer { padding-top: 24px; border-top: 1px solid #e2e8f0; margin-top: 32px; font-size: 12px; color: #94a3b8; line-height: 1.5; }
        @media only screen and (max-width: 600px) {
            .content { padding: 24px 16px !important; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <table class="main" align="center">
            <tr>
                <td class="top-bar" style="height: 6px; line-height: 6px; font-size: 6px; background-color: #0d6efd;">&nbsp;</td>
            </tr>
            <tr>
                <td class="content">
                    <a href="{{ config('app.url') }}" class="logo">{{ $senderName ?? config('app.name', 'Skoolready') }}</a>
                    
                    @yield('content')
                    
                    <div class="footer">
                        @yield('footer', 'This is an automated message from ' . ($senderName ?? config('app.name', 'Skoolready')) . '. If you were not expecting this, please ignore this email.')
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
