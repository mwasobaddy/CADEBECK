<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - CADEBECK HR</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background-color: #f8fafc; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 30px 20px; }
        .section { margin-bottom: 25px; }
        .section h2 { color: #1f2937; font-size: 18px; margin-bottom: 15px; border-bottom: 2px solid #e5e7eb; padding-bottom: 8px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 8px 12px; border-bottom: 1px solid #e5e7eb; }
        .info-table td:first-child { font-weight: 600; color: #374151; width: 140px; }
        .amount-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; background: #f9fafb; border-radius: 8px; overflow: hidden; }
        .amount-table th, .amount-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .amount-table th { background: #f3f4f6; font-weight: 600; color: #374151; }
        .amount-table td:last-child { text-align: right; font-weight: 600; }
        .total-row { background: #ecfdf5 !important; font-weight: bold; }
        .total-row td { color: #065f46; }
        .net-pay { text-align: center; background: #ecfdf5; padding: 25px; margin: 20px 0; border-radius: 8px; border: 2px solid #10b981; }
        .net-pay .amount { font-size: 32px; font-weight: bold; color: #059669; margin-bottom: 5px; }
        .net-pay .label { font-size: 14px; color: #065f46; text-transform: uppercase; letter-spacing: 1px; }
        .button { text-align: center; margin: 30px 0; }
        .button a { display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500; }
        .footer { text-align: center; padding: 20px; background: #f9fafb; color: #6b7280; font-size: 14px; border-top: 1px solid #e5e7eb; }
        .emoji { font-size: 20px; margin-right: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><span class="emoji">📄</span>Your Payslip</h1>
        </div>

        <div class="content">
            <div class="section">
                <p>Hello <strong>{{ $employee->user ? trim(($employee->user->first_name ?? '') . ' ' . ($employee->user->other_names ?? '')) : 'Employee' }}</strong>,</p>
                <p>Your payslip for the period <strong>{{ $payroll->payroll_period ?? 'N/A' }}</strong> is ready. Please find your salary details below.</p>
            </div>

            <div class="section">
                <h2><span class="emoji">👤</span>Employee Information</h2>
                <table class="info-table">
                    <tr><td>Employee ID</td><td>{{ $employee->staff_number ?? 'N/A' }}</td></tr>
                    <tr><td>Name</td><td>{{ $employee->user ? trim(($employee->user->first_name ?? '') . ' ' . ($employee->user->other_names ?? '')) : 'N/A' }}</td></tr>
                    <tr><td>Department</td><td>{{ $employee->department->name ?? 'N/A' }}</td></tr>
                    <tr><td>Designation</td><td>{{ $employee->designation->name ?? 'N/A' }}</td></tr>
                </table>
            </div>

            <div class="section">
                <h2><span class="emoji">💰</span>Earnings Breakdown</h2>
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
                            <td><strong>KES {{ number_format($payroll->basic_salary ?? 0, 2) }}</strong></td>
                        </tr>
                        @if(($payroll->house_allowance ?? 0) > 0)
                        <tr>
                            <td>House Allowance</td>
                            <td>KES {{ number_format($payroll->house_allowance ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->transport_allowance ?? 0) > 0)
                        <tr>
                            <td>Transport Allowance</td>
                            <td>KES {{ number_format($payroll->transport_allowance ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->medical_allowance ?? 0) > 0)
                        <tr>
                            <td>Medical Allowance</td>
                            <td>KES {{ number_format($payroll->medical_allowance ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->overtime_amount ?? 0) > 0)
                        <tr>
                            <td>Overtime Allowance</td>
                            <td>KES {{ number_format($payroll->overtime_amount ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->bonus_amount ?? 0) > 0)
                        <tr>
                            <td>Bonus</td>
                            <td>KES {{ number_format($payroll->bonus_amount ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->other_allowances ?? 0) > 0)
                        <tr>
                            <td>Other Allowances</td>
                            <td>KES {{ number_format($payroll->other_allowances ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="total-row">
                            <td><strong>Total Earnings</strong></td>
                            <td><strong>KES {{ number_format(($payroll->gross_pay ?? 0), 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h2><span class="emoji">📉</span>Deductions Breakdown</h2>
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
                            <td>KES {{ number_format($payroll->paye_tax ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->nhif_deduction ?? 0) > 0)
                        <tr>
                            <td>NHIF Contribution</td>
                            <td>KES {{ number_format($payroll->nhif_deduction ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->nssf_deduction ?? 0) > 0)
                        <tr>
                            <td>NSSF Contribution</td>
                            <td>KES {{ number_format($payroll->nssf_deduction ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->insurance_deduction ?? 0) > 0)
                        <tr>
                            <td>Insurance Premium</td>
                            <td>KES {{ number_format($payroll->insurance_deduction ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->loan_deduction ?? 0) > 0)
                        <tr>
                            <td>Loan Repayment</td>
                            <td>KES {{ number_format($payroll->loan_deduction ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        @if(($payroll->other_deductions ?? 0) > 0)
                        <tr>
                            <td>Other Deductions</td>
                            <td>KES {{ number_format($payroll->other_deductions ?? 0, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="total-row">
                            <td><strong>Total Deductions</strong></td>
                            <td><strong>KES {{ number_format((($payroll->paye_tax ?? 0) + ($payroll->nhif_deduction ?? 0) + ($payroll->nssf_deduction ?? 0) + ($payroll->insurance_deduction ?? 0) + ($payroll->loan_deduction ?? 0) + ($payroll->other_deductions ?? 0)), 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="net-pay">
                <div class="amount">KES {{ number_format($payroll->net_pay ?? 0, 2) }}</div>
                <div class="label">Net Pay</div>
            </div>

            <div class="section">
                <p><strong>⚠️ Important:</strong> This payslip is confidential and intended only for the named employee. If you have any questions about your salary, please contact the HR department.</p>
            </div>

            <div class="button">
                <a href="mailto:hr@cadebeck.com">Contact HR Department</a>
            </div>
        </div>

        <div class="footer">
            <p>This is an automated message from <strong>CADEBECK HR Management System</strong>.<br>
            Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
