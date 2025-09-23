<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - CADEBECK HR</title>
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
        .section {
            margin-bottom: 25px;
        }
        .section h2 {
            color: #1e40af;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #f8fafc;
            border-radius: 8px;
            overflow: hidden;
        }
        .info-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-table td:first-child {
            font-weight: 600;
            color: #475569;
            width: 140px;
        }
        .amount-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: #f9fafb;
            border-radius: 8px;
            overflow: hidden;
        }
        .amount-table th, .amount-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .amount-table th {
            background: #f3f4f6;
            font-weight: 600;
            color: #374151;
        }
        .amount-table td:last-child {
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
        .message-content {
            font-size: 16px;
            line-height: 1.7;
            margin: 20px 0;
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
            .info-table td, .amount-table th, .amount-table td {
                padding: 8px;
            }
            .net-pay .amount {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="company-name">CADEBECK</h1>
            <p class="subtitle">Human Resources Department</p>
        </div>

        <div class="message-content">
            <h2 style="color: #1e40af; margin-bottom: 15px;">Dear {{ $employee->user ? trim(($employee->user->first_name ?? '') . ' ' . ($employee->user->other_names ?? '')) : 'Employee' }},</h2>
            
            <p>Your payslip for the period <strong>{{ $payroll->payroll_period ?? 'N/A' }}</strong> is ready. Please find your salary details below.</p>
            
            <div class="section">
                <h2>Employee Information</h2>
                <table class="info-table">
                    <tr><td>Employee ID</td><td>{{ $employee->staff_number ?? 'N/A' }}</td></tr>
                    <tr><td>Name</td><td>{{ $employee->user ? trim(($employee->user->first_name ?? '') . ' ' . ($employee->user->other_names ?? '')) : 'N/A' }}</td></tr>
                    <tr><td>Department</td><td>{{ $employee->department->name ?? 'N/A' }}</td></tr>
                    <tr><td>Designation</td><td>{{ $employee->designation->name ?? 'N/A' }}</td></tr>
                </table>
            </div>

            <div class="section">
                <h2>Earnings Breakdown</h2>
                <table class="amount-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Basic Salary</strong></td>
                            <td><strong>USD {{ number_format($payroll->basic_salary ?? 0, 2) }}</strong></td>
                        </tr>
                        @if(($payroll->house_allowance ?? 0) > 0)
                        <tr>
                            <td>House Allowance</td>
                            <td>USD {{ number_format($payroll->house_allowance ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->transport_allowance ?? 0) > 0)
                        <tr>
                            <td>Transport Allowance</td>
                            <td>USD {{ number_format($payroll->transport_allowance ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->medical_allowance ?? 0) > 0)
                        <tr>
                            <td>Medical Allowance</td>
                            <td>USD {{ number_format($payroll->medical_allowance ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->overtime_amount ?? 0) > 0)
                        <tr>
                            <td>Overtime Allowance</td>
                            <td>USD {{ number_format($payroll->overtime_amount ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->bonus_amount ?? 0) > 0)
                        <tr>
                            <td>Bonus</td>
                            <td>USD {{ number_format($payroll->bonus_amount ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->other_allowances ?? 0) > 0)
                        <tr>
                            <td>Other Allowances</td>
                            <td>USD {{ number_format($payroll->other_allowances ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="total-row">
                            <td><strong>Total Earnings</strong></td>
                            <td><strong>USD {{ number_format(($payroll->gross_pay ?? 0), 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h2>Deductions Breakdown</h2>
                <table class="amount-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(($payroll->paye_tax ?? 0) > 0)
                        <tr>
                            <td>PAYE Tax</td>
                            <td>USD {{ number_format($payroll->paye_tax ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->nhif_deduction ?? 0) > 0)
                        <tr>
                            <td>NHIF Contribution</td>
                            <td>USD {{ number_format($payroll->nhif_deduction ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->nssf_deduction ?? 0) > 0)
                        <tr>
                            <td>NSSF Contribution</td>
                            <td>USD {{ number_format($payroll->nssf_deduction ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->insurance_deduction ?? 0) > 0)
                        <tr>
                            <td>Insurance Premium</td>
                            <td>USD {{ number_format($payroll->insurance_deduction ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->loan_deduction ?? 0) > 0)
                        <tr>
                            <td>Loan Repayment</td>
                            <td>USD {{ number_format($payroll->loan_deduction ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->other_deductions ?? 0) > 0)
                        <tr>
                            <td>Other Deductions</td>
                            <td>USD {{ number_format($payroll->other_deductions ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="total-row">
                            <td><strong>Total Deductions</strong></td>
                            <td><strong>USD {{ number_format((($payroll->paye_tax ?? 0) + ($payroll->nhif_deduction ?? 0) + ($payroll->nssf_deduction ?? 0) + ($payroll->insurance_deduction ?? 0) + ($payroll->loan_deduction ?? 0) + ($payroll->other_deductions ?? 0)), 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="net-pay">
                <div class="amount">USD {{ number_format($payroll->net_pay ?? 0, 2) }}</div>
                <div class="label">Net Pay</div>
            </div>

            <p><strong>Important:</strong> This payslip is confidential and intended only for the named employee. If you have any questions about your salary, please contact the HR department.</p>

            <div class="button">
                <a href="mailto:hr@cadebeck.com">Contact HR Department</a>
            </div>

            <p style="margin-top: 30px;">Best regards,<br>
            <strong>CADEBECK HR Team</strong></p>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply directly to this email.</p>
            <p>© {{ date('Y') }} CADEBECK. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
