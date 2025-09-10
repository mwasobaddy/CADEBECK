{{-- Payroll Processed Email Template --}}
{{-- Header --}}
<table class="header" width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td class="header-logo" style="padding: 20px;">
            <img src="{{ asset('images/cadebeck-logo.svg') }}" alt="CADEBECK HR" style="height: 48px;">
        </td>
    </tr>
</table>

{{-- Greeting --}}
<h1 style="color: #2563eb; margin-bottom: 20px;">Payroll Processed Successfully</h1>

<p style="margin-bottom: 20px;">
    Dear {{ $employee->user->first_name ?? 'Employee' }} {{ $employee->user->other_names ?? '' }},
</p>

<p style="margin-bottom: 20px;">
    Your payroll has been processed for the period <strong>{{ $payroll->payroll_period ?? 'N/A' }}</strong>.
</p>

{{-- Payroll Details Panel --}}
<table class="panel" width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #bfdbfe; background-color: #eff6ff; margin-bottom: 20px;">
    <tr>
        <td style="padding: 20px;">
            <h2 style="color: #1e40af; margin-bottom: 15px;">Payroll Details</h2>
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="padding: 5px 10px;"><strong>Payroll Period:</strong></td>
                    <td style="padding: 5px 10px;">{{ $payroll->payroll_period ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 10px;"><strong>Pay Date:</strong></td>
                    <td style="padding: 5px 10px;">{{ $payroll->pay_date ? $payroll->pay_date->format('M d, Y') : 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 10px;"><strong>Gross Pay:</strong></td>
                    <td style="padding: 5px 10px; color: #16a34a; font-weight: bold;">KES {{ number_format($payroll->gross_pay ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 10px;"><strong>Net Pay:</strong></td>
                    <td style="padding: 5px 10px; color: #16a34a; font-weight: bold;">KES {{ number_format($payroll->net_pay ?? 0, 2) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- PDF Attachment Notice --}}
<table class="attachment" width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #10b981; background-color: #f0fdf4; margin-bottom: 20px;">
    <tr>
        <td style="padding: 15px;">
            <strong style="color: #059669;">📋 Your complete payslip details are below:</strong>
        </td>
    </tr>
</table>

{{-- Employee Details --}}
<table width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #e5e7eb; background-color: #f9fafb; margin-bottom: 20px;">
    <tr>
        <td style="padding: 20px;">
            <h3 style="color: #1e40af; margin-bottom: 15px; font-size: 16px;">Employee Information</h3>
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="padding: 5px 10px; width: 120px;"><strong>Employee ID:</strong></td>
                    <td style="padding: 5px 10px;">{{ $employee->staff_number ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 10px;"><strong>Name:</strong></td>
                    <td style="padding: 5px 10px;">{{ $employee->user ? trim(($employee->user->first_name ?? '') . ' ' . ($employee->user->other_names ?? '')) : 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 10px;"><strong>Department:</strong></td>
                    <td style="padding: 5px 10px;">{{ $employee->department->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 10px;"><strong>Designation:</strong></td>
                    <td style="padding: 5px 10px;">{{ $employee->designation->name ?? 'N/A' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- Earnings Section --}}
<table width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #10b981; background-color: #f0fdf4; margin-bottom: 20px;">
    <tr>
        <td style="padding: 20px;">
            <h3 style="color: #065f46; margin-bottom: 15px; font-size: 16px;">Earnings</h3>
            <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #dcfce7;">
                        <th style="padding: 10px; border: 1px solid #bbf7d0; text-align: left; font-weight: bold; color: #065f46;">Description</th>
                        <th style="padding: 10px; border: 1px solid #bbf7d0; text-align: right; font-weight: bold; color: #065f46;">Amount (KES)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 8px 10px; border: 1px solid #bbf7d0;">Basic Salary</td>
                        <td style="padding: 8px 10px; border: 1px solid #bbf7d0; text-align: right; font-weight: bold;">{{ number_format($payroll->basic_salary ?? 0, 2) }}</td>
                    </tr>
                    @if(($payroll->house_allowance ?? 0) > 0)
                    <tr>
                        <td style="padding: 8px 10px; border: 1px solid #bbf7d0;">House Allowance</td>
                        <td style="padding: 8px 10px; border: 1px solid #bbf7d0; text-align: right; font-weight: bold;">{{ number_format($payroll->house_allowance ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->transport_allowance ?? 0) > 0)
                    <tr>
                        <td style="padding: 8px 10px; border: 1px solid #bbf7d0;">Transport Allowance</td>
                        <td style="padding: 8px 10px; border: 1px solid #bbf7d0; text-align: right; font-weight: bold;">{{ number_format($payroll->transport_allowance ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->medical_allowance ?? 0) > 0)
                    <tr>
                        <td style="padding: 8px 10px; border: 1px solid #bbf7d0;">Medical Allowance</td>
                        <td style="padding: 8px 10px; border: 1px solid #bbf7d0; text-align: right; font-weight: bold;">{{ number_format($payroll->medical_allowance ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->overtime_amount ?? 0) > 0)
                    <tr>
                        <td style="padding: 8px 10px; border: 1px solid #bbf7d0;">Overtime Allowance</td>
                        <td style="padding: 8px 10px; border: 1px solid #bbf7d0; text-align: right; font-weight: bold;">{{ number_format($payroll->overtime_amount ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->bonus_amount ?? 0) > 0)
                    <tr>
                        <td style="padding: 8px 10px; border: 1px solid #bbf7d0;">Bonus</td>
                        <td style="padding: 8px 10px; border: 1px solid #bbf7d0; text-align: right; font-weight: bold;">{{ number_format($payroll->bonus_amount ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->other_allowances ?? 0) > 0)
                    <tr>
                        <td style="padding: 8px 10px; border: 1px solid #bbf7d0;">Other Allowances</td>
                        <td style="padding: 8px 10px; border: 1px solid #bbf7d0; text-align: right; font-weight: bold;">{{ number_format($payroll->other_allowances ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    <tr style="background-color: #bbf7d0; font-weight: bold;">
                        <td style="padding: 10px; border: 1px solid #10b981; color: #065f46;"><strong>Total Earnings</strong></td>
                        <td style="padding: 10px; border: 1px solid #10b981; text-align: right; color: #065f46;"><strong>{{ number_format(($payroll->gross_pay ?? 0), 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>

{{-- Deductions Section --}}
<table width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #ef4444; background-color: #fef2f2; margin-bottom: 20px;">
    <tr>
        <td style="padding: 20px;">
            <h3 style="color: #991b1b; margin-bottom: 15px; font-size: 16px;">Deductions</h3>
            <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #fecaca;">
                        <th style="padding: 10px; border: 1px solid #fca5a5; text-align: left; font-weight: bold; color: #991b1b;">Description</th>
                        <th style="padding: 10px; border: 1px solid #fca5a5; text-align: right; font-weight: bold; color: #991b1b;">Amount (KES)</th>
                    </tr>
                </thead>
                <tbody>
                    @if(($payroll->paye_tax ?? 0) > 0)
                    <tr>
                        <td style="padding: 8px 10px; border: 1px solid #fca5a5;">PAYE Tax</td>
                        <td style="padding: 8px 10px; border: 1px solid #fca5a5; text-align: right; font-weight: bold;">{{ number_format($payroll->paye_tax ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->nhif_deduction ?? 0) > 0)
                    <tr>
                        <td style="padding: 8px 10px; border: 1px solid #fca5a5;">NHIF Contribution</td>
                        <td style="padding: 8px 10px; border: 1px solid #fca5a5; text-align: right; font-weight: bold;">{{ number_format($payroll->nhif_deduction ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->nssf_deduction ?? 0) > 0)
                    <tr>
                        <td style="padding: 8px 10px; border: 1px solid #fca5a5;">NSSF Contribution</td>
                        <td style="padding: 8px 10px; border: 1px solid #fca5a5; text-align: right; font-weight: bold;">{{ number_format($payroll->nssf_deduction ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->insurance_deduction ?? 0) > 0)
                    <tr>
                        <td style="padding: 8px 10px; border: 1px solid #fca5a5;">Insurance Premium</td>
                        <td style="padding: 8px 10px; border: 1px solid #fca5a5; text-align: right; font-weight: bold;">{{ number_format($payroll->insurance_deduction ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->loan_deduction ?? 0) > 0)
                    <tr>
                        <td style="padding: 8px 10px; border: 1px solid #fca5a5;">Loan Repayment</td>
                        <td style="padding: 8px 10px; border: 1px solid #fca5a5; text-align: right; font-weight: bold;">{{ number_format($payroll->loan_deduction ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if(($payroll->other_deductions ?? 0) > 0)
                    <tr>
                        <td style="padding: 8px 10px; border: 1px solid #fca5a5;">Other Deductions</td>
                        <td style="padding: 8px 10px; border: 1px solid #fca5a5; text-align: right; font-weight: bold;">{{ number_format($payroll->other_deductions ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    <tr style="background-color: #fca5a5; font-weight: bold;">
                        <td style="padding: 10px; border: 1px solid #dc2626; color: #991b1b;"><strong>Total Deductions</strong></td>
                        <td style="padding: 10px; border: 1px solid #dc2626; text-align: right; color: #991b1b;"><strong>{{ number_format((($payroll->paye_tax ?? 0) + ($payroll->nhif_deduction ?? 0) + ($payroll->nssf_deduction ?? 0) + ($payroll->insurance_deduction ?? 0) + ($payroll->loan_deduction ?? 0) + ($payroll->other_deductions ?? 0)), 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>

{{-- Net Pay Section --}}
<table width="100%" cellspacing="0" cellpadding="0" style="border: 2px solid #059669; background-color: #ecfdf5; margin-bottom: 20px;">
    <tr>
        <td style="padding: 30px; text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: #059669; margin-bottom: 10px;">KES {{ number_format($payroll->net_pay ?? 0, 2) }}</div>
            <div style="font-size: 14px; color: #065f46; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">NET PAY</div>
        </td>
    </tr>
</table>

<p style="margin-bottom: 20px;">
    Your complete payslip details are shown above, including all earnings, deductions, and your net pay for this payroll period.
</p>

{{-- Warning --}}
<table class="warning" width="100%" cellspacing="0" cellpadding="0" style="border-left: 4px solid #f59e0b; background-color: #fefce8; margin-bottom: 20px;">
    <tr>
        <td style="padding: 15px;">
            <strong style="color: #92400e;">Important:</strong>
            <span style="color: #92400e;">If you notice any discrepancies in your payroll details, please contact the HR department immediately.</span>
        </td>
    </tr>
</table>

<p style="margin-bottom: 30px;">
    If you have any questions about your payroll or need assistance, please don't hesitate to contact our HR team.
</p>

{{-- Button --}}
<table class="button" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 30px;">
    <tr>
        <td align="center">
            <a href="mailto:hr@cadebeck.com" style="background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">Contact HR Department</a>
        </td>
    </tr>
</table>

{{-- Footer --}}
<table class="footer" width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center" style="padding: 20px; color: #6b7280; font-size: 12px;">
            This is an automated message from CADEBECK HR Management System.<br>
            Please do not reply to this email.
        </td>
    </tr>
</table>
