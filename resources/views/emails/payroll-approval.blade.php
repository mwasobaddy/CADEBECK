<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Payroll Approval Initiated') }} - {{ config('app.name') }}</title>
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
        .payroll-details {
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
        .earnings-table, .deductions-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: #f8fafc;
            border-radius: 8px;
            overflow: hidden;
        }
        .earnings-table th, .earnings-table td, .deductions-table th, .deductions-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .earnings-table th, .deductions-table th {
            background: #f3f4f6;
            font-weight: 600;
            color: #374151;
        }
        .earnings-table td:last-child, .deductions-table td:last-child {
            text-align: right;
            font-weight: 600;
        }
        .total-row {
            background: #ecfdf5 !important;
            font-weight: bold;
        }
        .total-row td {
            color: #065f46;
        }
        .net-pay {
            text-align: center;
            background: #ecfdf5;
            padding: 25px;
            margin: 20px 0;
            border-radius: 8px;
            border: 2px solid #10b981;
        }
        .net-pay .amount {
            font-size: 32px;
            font-weight: bold;
            color: #059669;
            margin-bottom: 5px;
        }
        .net-pay .label {
            font-size: 14px;
            color: #065f46;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .status-box {
            text-align: center;
            background: #fef3c7;
            padding: 25px;
            margin: 20px 0;
            border-radius: 8px;
            border: 2px solid #f59e0b;
        }
        .status-box .status {
            font-size: 24px;
            font-weight: bold;
            color: #d97706;
            margin-bottom: 5px;
        }
        .status-box .label {
            font-size: 14px;
            color: #92400e;
            text-transform: uppercase;
            letter-spacing: 1px;
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
            <h2 style="color: #1e40af; margin-bottom: 15px;">{{ __('Dear :name,', ['name' => $employee->user ? trim(($employee->user->first_name ?? '') . ' ' . ($employee->user->other_names ?? '')) : 'Employee']) }}</h2>

            <p>{{ __('We hope this message finds you well. We are writing to inform you that your payroll has been initiated for processing.') }}</p>

            <div class="payroll-details">
                <h3 style="margin-top: 0; color: #374151;">{{ __('Payroll Details') }}</h3>
                <div class="detail-row">
                    <span class="detail-label">{{ __('Payroll Period:') }}</span>
                    <span class="detail-value">{{ $payroll->payroll_period ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">{{ __('Employee ID:') }}</span>
                    <span class="detail-value">{{ $employee->staff_number ?? 'N/A' }}</span>
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

            <div class="alert">
                <strong>{{ __('Important:') }}</strong> {{ __('You will receive another notification once your payroll has been fully processed and your payment has been initiated.') }}
            </div>

            <h3 style="color: #374151;">{{ __('Earnings Breakdown') }}</h3>
            <table class="earnings-table">
                <thead>
                    <tr>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>{{ __('Basic Salary') }}</strong></td>
                        <td><strong>USD {{ number_format($payroll->basic_salary ?? 0, 2) }}</strong></td>
                    </tr>
                    @if(($payroll->house_allowance ?? 0) > 0)
                    <tr>
                        <td>{{ __('House Allowance') }}</td>
                        <td>USD {{ number_format($payroll->house_allowance ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->transport_allowance ?? 0) > 0)
                    <tr>
                        <td>{{ __('Transport Allowance') }}</td>
                        <td>USD {{ number_format($payroll->transport_allowance ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->medical_allowance ?? 0) > 0)
                    <tr>
                        <td>{{ __('Medical Allowance') }}</td>
                        <td>USD {{ number_format($payroll->medical_allowance ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->overtime_amount ?? 0) > 0)
                    <tr>
                        <td>{{ __('Overtime Allowance') }}</td>
                        <td>USD {{ number_format($payroll->overtime_amount ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->bonus_amount ?? 0) > 0)
                    <tr>
                        <td>{{ __('Bonus') }}</td>
                        <td>USD {{ number_format($payroll->bonus_amount ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->other_allowances ?? 0) > 0)
                    <tr>
                        <td>{{ __('Other Allowances') }}</td>
                        <td>USD {{ number_format($payroll->other_allowances ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td><strong>{{ __('Total Earnings') }}</strong></td>
                        <td><strong>USD {{ number_format(($payroll->gross_pay ?? 0), 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <h3 style="color: #374151;">{{ __('Deductions Breakdown') }}</h3>
            <table class="deductions-table">
                <thead>
                    <tr>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if(($payroll->paye_tax ?? 0) > 0)
                    <tr>
                        <td>{{ __('PAYE Tax') }}</td>
                        <td>USD {{ number_format($payroll->paye_tax ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->nhif_deduction ?? 0) > 0)
                    <tr>
                        <td>{{ __('NHIF Contribution') }}</td>
                        <td>USD {{ number_format($payroll->nhif_deduction ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->nssf_deduction ?? 0) > 0)
                    <tr>
                        <td>{{ __('NSSF Contribution') }}</td>
                        <td>USD {{ number_format($payroll->nssf_deduction ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->insurance_deduction ?? 0) > 0)
                    <tr>
                        <td>{{ __('Insurance Premium') }}</td>
                        <td>USD {{ number_format($payroll->insurance_deduction ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->loan_deduction ?? 0) > 0)
                    <tr>
                        <td>{{ __('Loan Repayment') }}</td>
                        <td>USD {{ number_format($payroll->loan_deduction ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->other_deductions ?? 0) > 0)
                    <tr>
                        <td>{{ __('Other Deductions') }}</td>
                        <td>USD {{ number_format($payroll->other_deductions ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td><strong>{{ __('Total Deductions') }}</strong></td>
                        <td><strong>USD {{ number_format((($payroll->paye_tax ?? 0) + ($payroll->nhif_deduction ?? 0) + ($payroll->nssf_deduction ?? 0) + ($payroll->insurance_deduction ?? 0) + ($payroll->loan_deduction ?? 0) + ($payroll->other_deductions ?? 0)), 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="net-pay">
                <div class="amount">USD {{ number_format($payroll->net_pay ?? 0, 2) }}</div>
                <div class="label">{{ __('Net Pay') }}</div>
            </div>

            <div class="status-box">
                <div class="status">{{ __('Under Review') }}</div>
                <div class="label">{{ __('Current Status') }}</div>
            </div>

            <p><strong>{{ __('What happens next?') }}</strong></p>
            <ul>
                <li>{{ __('Your payroll details will be reviewed by the HR team') }}</li>
                <li>{{ __('Any necessary adjustments will be made') }}</li>
                <li>{{ __('You will receive a final notification once processing is complete') }}</li>
                <li>{{ __('Payment will be initiated according to your payment schedule') }}</li>
            </ul>

            <p>{{ __('If you have any questions about your payroll or need to discuss any details, please contact the HR department.') }}</p>

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