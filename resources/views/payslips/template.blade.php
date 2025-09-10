<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $payslip_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #1f2937;
            margin: 0;
            padding: 20px;
            background-color: #ffffff;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 30px 20px;
            border-radius: 12px;
            margin: -20px -20px 30px -20px;
        }

        .company-name {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .company-details {
            font-size: 12px;
            opacity: 0.9;
        }

        .payslip-title {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            color: #3b82f6;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .payslip-info {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .info-section {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .info-section h4 {
            margin: 0 0 15px 0;
            color: #1e40af;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
            align-items: center;
        }

        .info-label {
            width: 100px;
            font-weight: 600;
            color: #374151;
            font-size: 12px;
        }

        .info-value {
            flex: 1;
            color: #111827;
            font-size: 12px;
            font-weight: 500;
            word-wrap: break-word;
        }
        

        .salary-section {
            margin-bottom: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 2px solid #3b82f6;
        }

        .salary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .salary-table th,
        .salary-table td {
            padding: 12px 20px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .salary-table th {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            font-weight: 600;
            color: #374151;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .salary-table td {
            font-size: 12px;
            color: #111827;
        }

        .amount-column {
            text-align: right;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }

        .total-row {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            font-weight: bold;
            border-top: 2px solid #f59e0b;
        }

        .total-row td {
            border-top: 2px solid #d97706;
            color: #92400e;
        }

        .net-pay-section {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin-top: 30px;
            border: 2px solid #10b981;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .net-pay-amount {
            font-size: 32px;
            font-weight: bold;
            color: #059669;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            font-family: 'Courier New', monospace;
        }

        .net-pay-label {
            font-size: 16px;
            color: #065f46;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 11px;
            color: #6b7280;
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            padding: 20px;
            border-radius: 8px;
        }

        .disclaimer {
            margin-top: 15px;
            font-style: italic;
            color: #9ca3af;
            background: #fef2f2;
            padding: 10px;
            border-radius: 6px;
            border-left: 4px solid #ef4444;
        }

        .earnings-section {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        }

        .deductions-section {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        }

        .earnings-section .section-title {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            color: #065f46;
            border-bottom-color: #10b981;
        }

        .deductions-section .section-title {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #991b1b;
            border-bottom-color: #ef4444;
        }

        .earnings-section .total-row {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            border-top-color: #16a34a;
        }

        .deductions-section .total-row {
            background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
            border-top-color: #dc2626;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">{{ $company['name'] }}</div>
        <div class="company-details">
            {{ $company['address'] }} | {{ $company['phone'] }} | {{ $company['email'] }}
        </div>
    </div>

    <!-- Payslip Title -->
    <div class="payslip-title">
        PAYSLIP - {{ $payroll['period'] }}
    </div>

    <!-- Payslip Information -->
    <div class="payslip-info">
        <div class="info-section">
            <h4>Employee Details</h4>
            <div class="info-row">
                <span class="info-label">Employee ID:</span>
                <span class="info-value">{{ $employee['employee_number'] ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span class="info-value">{{ $employee['name'] ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Department:</span>
                <span class="info-value">{{ $employee['department'] ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Designation:</span>
                <span class="info-value">{{ $employee['designation'] ?? 'N/A' }}</span>
            </div>
        </div>

        <div class="info-section">
            <h4>Payroll Details</h4>
            <div class="info-row">
                <span class="info-label">Pay Period:</span>
                <span class="info-value">{{ $payroll['period'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Pay Date:</span>
                <span class="info-value">{{ $payroll['pay_date'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Payslip No:</span>
                <span class="info-value">{{ $payslip_number }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Generated:</span>
                <span class="info-value">{{ $generated_at }}</span>
            </div>
        </div>
    </div>

    <!-- Earnings Section -->
    <div class="salary-section earnings-section">
        <h3 class="section-title">Earnings</h3>
        <table class="salary-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right;">Amount (KES)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Salary</td>
                    <td class="amount-column">{{ number_format($payroll['basic_salary'], 2) }}</td>
                </tr>
                @if($payroll['allowances']['house'] > 0)
                <tr>
                    <td>House Allowance</td>
                    <td class="amount-column">{{ number_format($payroll['allowances']['house'], 2) }}</td>
                </tr>
                @endif
                @if($payroll['allowances']['transport'] > 0)
                <tr>
                    <td>Transport Allowance</td>
                    <td class="amount-column">{{ number_format($payroll['allowances']['transport'], 2) }}</td>
                </tr>
                @endif
                @if($payroll['allowances']['medical'] > 0)
                <tr>
                    <td>Medical Allowance</td>
                    <td class="amount-column">{{ number_format($payroll['allowances']['medical'], 2) }}</td>
                </tr>
                @endif
                @if($payroll['allowances']['overtime'] > 0)
                <tr>
                    <td>Overtime Allowance</td>
                    <td class="amount-column">{{ number_format($payroll['allowances']['overtime'], 2) }}</td>
                </tr>
                @endif
                @if($payroll['allowances']['bonus'] > 0)
                <tr>
                    <td>Bonus</td>
                    <td class="amount-column">{{ number_format($payroll['allowances']['bonus'], 2) }}</td>
                </tr>
                @endif
                @if($payroll['allowances']['other'] > 0)
                <tr>
                    <td>Other Allowances</td>
                    <td class="amount-column">{{ number_format($payroll['allowances']['other'], 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td><strong>Total Earnings</strong></td>
                    <td class="amount-column"><strong>{{ number_format($payroll['allowances']['total'] + $payroll['basic_salary'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Deductions Section -->
    <div class="salary-section deductions-section">
        <h3 class="section-title">Deductions</h3>
        <table class="salary-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right;">Amount (KES)</th>
                </tr>
            </thead>
            <tbody>
                @if($payroll['deductions']['paye'] > 0)
                <tr>
                    <td>PAYE Tax</td>
                    <td class="amount-column">{{ number_format($payroll['deductions']['paye'], 2) }}</td>
                </tr>
                @endif
                @if($payroll['deductions']['nhif'] > 0)
                <tr>
                    <td>NHIF Contribution</td>
                    <td class="amount-column">{{ number_format($payroll['deductions']['nhif'], 2) }}</td>
                </tr>
                @endif
                @if($payroll['deductions']['nssf'] > 0)
                <tr>
                    <td>NSSF Contribution</td>
                    <td class="amount-column">{{ number_format($payroll['deductions']['nssf'], 2) }}</td>
                </tr>
                @endif
                @if($payroll['deductions']['insurance'] > 0)
                <tr>
                    <td>Insurance Premium</td>
                    <td class="amount-column">{{ number_format($payroll['deductions']['insurance'], 2) }}</td>
                </tr>
                @endif
                @if($payroll['deductions']['loan'] > 0)
                <tr>
                    <td>Loan Repayment</td>
                    <td class="amount-column">{{ number_format($payroll['deductions']['loan'], 2) }}</td>
                </tr>
                @endif
                @if($payroll['deductions']['other'] > 0)
                <tr>
                    <td>Other Deductions</td>
                    <td class="amount-column">{{ number_format($payroll['deductions']['other'], 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td><strong>Total Deductions</strong></td>
                    <td class="amount-column"><strong>{{ number_format($payroll['deductions']['total'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Net Pay Section -->
    <div class="net-pay-section">
        <div class="net-pay-amount">
            KES {{ number_format($payroll['net_pay'], 2) }}
        </div>
        <div class="net-pay-label">
            NET PAY
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>This is a system-generated payslip and does not require a signature.</div>
        <div class="disclaimer">
            This payslip contains confidential information and is intended only for the named employee.
            If you are not the intended recipient, please delete this document immediately.
        </div>
        <div style="margin-top: 10px;">
            Generated on {{ $generated_at }} by CADEBECK HR Management System
        </div>
    </div>
</body>
</html>
