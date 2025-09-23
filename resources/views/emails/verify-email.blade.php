<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Verify Your Email Address') }}</title>
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
        .message-content {
            font-size: 16px;
            line-height: 1.7;
            margin: 20px 0;
        }
        .verify-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #16a34a;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            margin: 20px 0;
            text-align: center;
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
            .verify-button {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="company-name">{{ config('app.name') }}</h1>
            <p class="subtitle">Human Resources Department</p>
        </div>

        <div class="message-content">
            <h2 style="color: #1e40af; margin-bottom: 15px;">{{ __('Verify Your Email Address') }}</h2>
            
            <p>{{ __('Please click the button below to verify your email address.') }}</p>
            
            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="verify-button">{{ __('Verify Email Address') }}</a>
            </div>
            
            <p>{{ __('If you did not create an account, no further action is required.') }}</p>

            <p style="margin-top: 30px;">{{ __('Thanks,') }}<br>
            <strong>{{ config('app.name') }} HR Team</strong></p>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply directly to this email.</p>
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
