<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Welcome to SkoolReady</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f3f6fb;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
        }

        table {
            border-collapse: collapse;
        }

        .wrapper {
            width: 100%;
            padding: 24px 12px;
            background: #f3f6fb;
        }

        .card {
            width: 100%;
            max-width: 620px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
        }

        .hero {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #ffffff;
            padding: 22px 24px;
        }

        .hero h1 {
            margin: 0;
            font-size: 24px;
            line-height: 1.3;
        }

        .hero p {
            margin: 8px 0 0 0;
            font-size: 14px;
            line-height: 1.5;
            color: rgba(255, 255, 255, 0.95);
        }

        .content {
            padding: 24px;
        }

        .content p {
            margin: 0 0 14px 0;
            font-size: 14px;
            line-height: 1.6;
            color: #111827;
        }

        .meta-box {
            margin: 18px 0;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #f9fafb;
            padding: 14px 16px;
        }

        .meta-row {
            margin: 0 0 9px 0;
            font-size: 14px;
            line-height: 1.5;
            color: #1f2937;
            word-break: break-word;
        }

        .meta-row:last-child {
            margin-bottom: 0;
        }

        .meta-row span {
            display: inline-block;
            min-width: 145px;
            color: #6b7280;
            font-weight: 700;
        }

        .btn-wrap {
            margin: 22px 0 18px 0;
        }

        .btn {
            display: inline-block;
            background: #f97316;
            color: #ffffff !important;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            line-height: 1;
            padding: 13px 20px;
            border-radius: 10px;
        }

        @media only screen and (max-width: 600px) {
            .wrapper {
                padding: 14px 8px;
            }

            .hero {
                padding: 18px 16px;
            }

            .hero h1 {
                font-size: 21px;
            }

            .content {
                padding: 16px;
            }

            .meta-row span {
                display: block;
                min-width: 0;
                margin-bottom: 2px;
            }

            .btn {
                display: block;
                text-align: center;
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>

<body>
    <table role="presentation" class="wrapper" width="100%">
        <tr>
            <td align="center">
                <table role="presentation" class="card" width="100%">
                    <tr>
                        <td class="hero">
                            <h1>Welcome to SkoolReady</h1>
                            <p>Your {{ $trialDays }}-day trial is active and ready to use.</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="content">
                            <p>Hello {{ $name }},</p>
                            <p>Your trial account has been created successfully. Use the credentials below to sign in
                                and get started.</p>

                            <div class="meta-box">
                                <p class="meta-row"><span>Login Email</span>{{ $email }}</p>
                                <p class="meta-row"><span>Password</span>{{ $temporaryPassword }}</p>
                            </div>

                            <div class="btn-wrap">
                                <a href="{{ $loginUrl }}" class="btn">Login to SkoolReady</a>
                            </div>

                            <p>Please change your password after your first login for better security.</p>

                            <p>Thanks,<br>SkoolReady Team</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
