<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>New Trial Registration</title>
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
            background: linear-gradient(135deg, #3b82f6, #2563eb);
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
                            <h1>New Trial Registration</h1>
                            <p>A new client has started a {{ $trialDays }}-day trial.</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="content">
                            <p>Hello,</p>
                            <p>A new trial registration has been processed through the system. Here are the details:</p>

                            <div class="meta-box">
                                <p class="meta-row"><span>Business Name</span>{{ $businessName }}</p>
                                <p class="meta-row"><span>Contact Name</span>{{ $contactName }}</p>
                                <p class="meta-row"><span>Email</span>{{ $email }}</p>
                                @if($phone)
                                <p class="meta-row"><span>Phone</span>{{ $phone }}</p>
                                @endif
                                <p class="meta-row"><span>Trial Product</span>{{ $trialItemName }}</p>
                            </div>

                            <p>You can view more details in the admin dashboard.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
