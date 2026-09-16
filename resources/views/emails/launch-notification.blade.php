<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — We're Live!</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8fafc;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 20px;
        }
        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #16a34a;
            margin: 0;
        }
        .subtitle {
            color: #64748b;
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        .badge {
            display: inline-block;
            background: #ecfdf5;
            color: #047857;
            font-weight: 600;
            font-size: 13px;
            padding: 6px 14px;
            border-radius: 999px;
            border: 1px solid #a7f3d0;
            margin-bottom: 24px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section h2 {
            color: #374151;
            font-size: 18px;
            margin-bottom: 12px;
        }
        .button {
            text-align: center;
            margin: 30px 0;
        }
        .button a {
            display: inline-block;
            background: #16a34a;
            color: white;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            margin: 6px;
        }
        .button a:hover {
            background: #15803d;
        }
        .button .secondary {
            background: transparent;
            color: #059669;
            border: 2px solid #059669;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 14px;
            color: #64748b;
            text-align: center;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="company-name">{{ config('app.name') }}</h1>
            <p class="subtitle">HR Management Software</p>
        </div>

        <div style="text-align:center;">
            <span class="badge">Great news!</span>
        </div>

        <div class="section">
            <h2>We're live!</h2>
            <p>Thank you for subscribing to the CADEBECK HR launch list. We're excited to let you know that the wait is over — CADEBECK HR is now live and ready to help you manage your people with ease.</p>
            <p>From employee records and HR administration to payroll, attendance, recruitment and onboarding, everything you need to manage your workforce is now available in one simple, secure cloud platform.</p>
        </div>

        <div class="section">
            <h2>What you can do now</h2>
            <ul>
                <li>Explore the features and see how CADEBECK HR works</li>
                <li>Request a free demo to see it in action</li>
                <li>Set up your account and get started</li>
            </ul>
        </div>

        <div class="button">
            <a href="{{ url('/') }}">Visit CADEBECK HR</a>
            <a href="{{ url('/contact') }}" class="secondary">Request a Demo</a>
        </div>

        <div class="section">
            <p>If you have any questions, our team is happy to help. Just reply to this email or contact us at <a href="mailto:info@cadebeckhr.com" style="color:#059669;">info@cadebeckhr.com</a>.</p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p style="font-size:12px;">You are receiving this email because you subscribed to the CADEBECK HR launch list.</p>
        </div>
    </div>
</body>
</html>