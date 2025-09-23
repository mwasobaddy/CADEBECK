<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status Update</title>
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
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin: 10px 5px;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-shortlisted {
            background-color: #dcfce7;
            color: #16a34a;
        }
        .status-rejected {
            background-color: #fecaca;
            color: #dc2626;
        }
        .status-invited {
            background-color: #dcfce7;
            color: #16a34a;
        }
        .status-change {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .application-details {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #475569;
        }
        .detail-value {
            color: #0f5132;
        }
        .message-content {
            font-size: 16px;
            line-height: 1.7;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 14px;
            color: #64748b;
            text-align: center;
        }
        .arrow {
            font-size: 20px;
            color: #64748b;
            margin: 0 10px;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 20px;
            }
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
            .status-badge {
                display: block;
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="company-name">{{ $companyName }}</h1>
            <p class="subtitle">Human Resources Department</p>
        </div>

        <div class="message-content">
            <h2 style="color: #1e40af; margin-bottom: 15px;">Dear {{ $applicantName }},</h2>
            
            <p>We hope this message finds you well. We are writing to inform you about an update regarding your job application.</p>
            
            <div class="application-details">
                <h3 style="margin-top: 0; color: #374151;">Application Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Position:</span>
                    <span class="detail-value">{{ $jobTitle }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Application Date:</span>
                    <span class="detail-value">{{ $applicationDate }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Application ID:</span>
                    <span class="detail-value">#{{ $application->id }}</span>
                </div>
            </div>

            <div class="status-change">
                <h3 style="margin-top: 0; color: #374151;">Status Update</h3>
                <p style="margin: 10px 0;">Your application status has been updated:</p>
                
                <div style="display: flex; align-items: center; justify-content: center; flex-wrap: wrap; margin: 15px 0;">
                    <span class="status-badge status-{{ strtolower($oldStatus) }}">{{ $oldStatus }}</span>
                    <span class="arrow">→</span>
                    <span class="status-badge status-{{ strtolower($newStatus) }}">{{ $newStatus }}</span>
                </div>
            </div>

            @if($newStatus === 'Shortlisted')
                <div style="background-color: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; border-radius: 4px;">
                    <p style="margin: 0; color: #1e40af;">
                        <strong>Congratulations!</strong> You have been shortlisted for this position. Our team will contact you soon with next steps in the selection process.
                    </p>
                </div>
            @elseif($newStatus === 'Invited')
                <div style="background-color: #dcfce7; border-left: 4px solid #16a34a; padding: 15px; margin: 20px 0; border-radius: 4px;">
                    <p style="margin: 0; color: #16a34a;">
                        <strong>Excellent news!</strong> You have been invited for an interview. Please check your email and phone for further instructions from our HR team.
                    </p>
                </div>
            @elseif($newStatus === 'Rejected')
                <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0; border-radius: 4px;">
                    <p style="margin: 0; color: #dc2626;">
                        Thank you for your interest in this position. While we were impressed with your qualifications, we have decided to move forward with other candidates who more closely match our current requirements. We encourage you to apply for future opportunities that align with your skills and experience.
                    </p>
                </div>
            @else
                <p>Your application is currently under review. We will keep you informed of any further updates.</p>
            @endif

            <p>Thank you for your interest in joining our team. If you have any questions, please don't hesitate to contact our Human Resources department.</p>

            <p style="margin-top: 30px;">Best regards,<br>
            <strong>{{ $companyName }} HR Team</strong></p>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply directly to this email.</p>
            <p>© {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>