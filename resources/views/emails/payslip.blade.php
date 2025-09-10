{{-- Payslip Email Template --}}
{{-- Header --}}
<table class="header" width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td class="header-logo" style="padding: 20px;">
            <img src="{{ asset('images/cadebeck-logo.svg') }}" alt="CADEBECK HR" style="height: 48px;">
        </td>
    </tr>
</table>

{{-- Greeting --}}
<h1 style="color: #16a34a; margin-bottom: 20px;">Your Payslip is Ready</h1>

<p style="margin-bottom: 20px;">
    Dear {{ $employee->user->first_name ?? 'Employee' }} {{ $employee->user->other_names ?? '' }},
</p>

<p style="margin-bottom: 20px;">
    Your payslip for the period <strong>{{ $payslip->payroll_period ?? 'N/A' }}</strong> has been generated and is attached to this email.
</p>

{{-- Payroll Summary Panel --}}
<table class="panel" width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #bbf7d0; background-color: #f0fdf4; margin-bottom: 20px;">
    <tr>
        <td style="padding: 20px;">
            <h2 style="color: #166534; margin-bottom: 15px;">Payroll Summary</h2>
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="padding: 5px 10px;"><strong>Payroll Period:</strong></td>
                    <td style="padding: 5px 10px;">{{ $payslip->payroll_period ?? 'N/A' }}</td>
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

{{-- Info Panel --}}
<table class="info" width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #bfdbfe; background-color: #eff6ff; margin-bottom: 20px;">
    <tr>
        <td style="padding: 15px;">
            <strong style="color: #1e40af;">📎 Your payslip PDF is attached to this email for your records.</strong>
        </td>
    </tr>
</table>

<p style="margin-bottom: 30px;">
    Please review your payslip carefully. If you notice any discrepancies or have questions about your payroll, please contact the HR department immediately.
</p>

{{-- Button --}}
<table class="button" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 30px;">
    <tr>
        <td align="center">
            <a href="mailto:hr@cadebeck.com" style="background-color: #16a34a; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">Contact HR Department</a>
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
