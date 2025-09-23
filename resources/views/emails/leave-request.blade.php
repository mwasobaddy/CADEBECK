<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('New Leave Request') }} - {{ config('app.name') }}</title>
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
        .leave-details {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .leave-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .leave-row:last-child {
            border-bottom: none;
        }
        .status-change {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .message-content {
            font-size: 16px;
            line-height: 1.7;
            margin: 20px 0;
        }
        .alert {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .alert strong {
            color: #92400e;
        }
        .button {
            text-align: center;
            margin: 30px 0;
        }
        .button a {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
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
            .detail-row, .leave-row {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="company-name">{{ config('app.name') }}</h1>
            <p class="subtitle">{{ __('Human Resources Department') }}</p>
        </div>

        <div class="message-content">
            <h2 style="color: #1e40af; margin-bottom: 15px;">{{ __('Dear :name,', ['name' => $employee->supervisor && $employee->supervisor->user ? trim(($employee->supervisor->user->first_name ?? '') . ' ' . ($employee->supervisor->user->other_names ?? '')) : 'Supervisor']) }}</h2>

            <p>{{ __('A new leave request has been submitted by :employee. Please review the details below and take appropriate action.', ['employee' => $employee->user ? trim(($employee->user->first_name ?? '') . ' ' . ($employee->user->other_names ?? '')) : 'Employee']) }}</p>

            <div class="alert">
                <strong>{{ __('Action Required:') }}</strong> {{ __('Review and approve or reject this leave request in the HR system to ensure timely processing.') }}
            </div>

            <div class="application-details">
                <h3 style="margin-top: 0; color: #374151;">{{ __('Employee Information') }}</h3>
                <div class="detail-row">
                    <span class="detail-label">{{ __('Employee ID:') }}</span>
                    <span class="detail-value">{{ $employee->staff_number ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">{{ __('Name:') }}</span>
                    <span class="detail-value">{{ $employee->user ? trim(($employee->user->first_name ?? '') . ' ' . ($employee->user->other_names ?? '')) : 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">{{ __('Department:') }}</span>
                    <span class="detail-value">{{ $employee->department->name ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">{{ __('Designation:') }}</span>
                    <span class="detail-value">{{ $employee->designation->name ?? 'N/A' }}</span>
                </div>
            </div>

            <div class="leave-details">
                <h3 style="margin-top: 0; color: #374151;">{{ __('Leave Request Details') }}</h3>
                <div class="leave-row">
                    <span class="detail-label">{{ __('Leave Type:') }}</span>
                    <span class="detail-value">{{ ucfirst($leaveRequest->leave_type ?? 'N/A') }}</span>
                </div>
                <div class="leave-row">
                    <span class="detail-label">{{ __('Start Date:') }}</span>
                    <span class="detail-value">{{ $leaveRequest->start_date ? $leaveRequest->start_date->format('M d, Y') : 'N/A' }}</span>
                </div>
                <div class="leave-row">
                    <span class="detail-label">{{ __('End Date:') }}</span>
                    <span class="detail-value">{{ $leaveRequest->end_date ? $leaveRequest->end_date->format('M d, Y') : 'N/A' }}</span>
                </div>
                <div class="leave-row">
                    <span class="detail-label">{{ __('Days Requested:') }}</span>
                    <span class="detail-value">{{ $leaveRequest->days_requested ?? 'N/A' }}</span>
                </div>
                <div class="leave-row">
                    <span class="detail-label">{{ __('Reason:') }}</span>
                    <span class="detail-value">{{ $leaveRequest->reason ?? 'N/A' }}</span>
                </div>
            </div>

            <div class="status-change">
                <h3 style="margin-top: 0; color: #374151;">{{ __('Current Status') }}</h3>
                <p style="margin: 10px 0;">{{ __('Your application status has been updated:') }}</p>
                <div style="font-size: 24px; font-weight: bold; color: #d97706; margin-bottom: 5px;">{{ __('Pending Approval') }}</div>
                <div style="font-size: 14px; color: #92400e; text-transform: uppercase; letter-spacing: 1px;">{{ __('Status') }}</div>
            </div>

            <p><strong>{{ __('What happens next?') }}</strong></p>
            <ul>
                <li>{{ __('Review the leave request for conflicts or balance issues') }}</li>
                <li>{{ __('Approve or reject the request in the system') }}</li>
                <li>{{ __('The employee will be notified of the decision') }}</li>
                <li>{{ __('Approved leave will be deducted from the employee\'s balance') }}</li>
            </ul>

            <div class="button">
                <a href="{{ $editUrl }}">{{ __('Review Leave Request') }}</a>
            </div>

            <p>{{ __('If you have any questions or need additional information, please contact the HR department.') }}</p>

            <p style="margin-top: 30px;">{{ __('Best regards,') }}<br>
            <strong>{{ config('app.name') }} {{ __('HR Team') }}</strong></p>
        </div>

        <div class="footer">
            <p>{{ __('This is an automated message. Please do not reply directly to this email.') }}</p>
            <p>© {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
        </div>
    </div>
</body>
</html>