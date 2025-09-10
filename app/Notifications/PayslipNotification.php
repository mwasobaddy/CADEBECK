<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;

class PayslipNotification extends Notification
{
    use Queueable;

    public Payslip $payslip;
    public string $subject;
    public string $message;

    public function __construct(Payslip $payslip, ?string $subject = null, ?string $message = null)
    {
        $this->payslip = $payslip;
        $this->subject = $subject ?? __('Your Payslip for') . ' ' . $payslip->payroll_period;
        $this->message = $message ?? __('Your payslip has been generated and is attached to this email.');
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $payslip = $this->payslip;
        $payroll = $payslip->payroll;
        $employee = $payroll->employee;

        try {
            // Generate the full payslip PDF
            $pdf = $this->generatePayslipPDF($payroll);
            $filename = $this->generatePayslipFilename($payroll);

            $mail = (new MailMessage)
                ->subject($this->subject)
                ->view('emails.payslip-modern', [
                    'payslip' => $payslip,
                    'payroll' => $payroll,
                    'employee' => $employee,
                ]);

            // Attach the PDF payslip
            $mail->attachData($pdf->output(), $filename, [
                'mime' => 'application/pdf',
            ]);

            \Log::info('Payslip PDF attachment added to email', ['filename' => $filename]);

            return $mail;

        } catch (\Exception $e) {
            \Log::error('Failed to generate or attach PDF for payslip', [
                'error' => $e->getMessage(),
                'payslip_id' => $payslip->id,
                'payroll_id' => $payroll->id,
                'employee_id' => $employee->id
            ]);

            // Return email without attachment if PDF generation fails
            return (new MailMessage)
                ->subject($this->subject)
                ->view('emails.payslip-modern', [
                    'payslip' => $payslip,
                    'payroll' => $payroll,
                    'employee' => $employee,
                ]);
        }
    }

    public function toArray($notifiable)
    {
        return [
            'payslip_id' => $this->payslip->id,
            'payroll_period' => $this->payslip->payroll_period,
            'subject' => $this->subject,
            'message' => $this->message,
        ];
    }

    /**
     * Generate payslip PDF for notification
     */
    protected function generatePayslipPDF($payroll)
    {
        try {
            // Load employee with all necessary relationships
            $payroll->load(['employee.user', 'employee.department', 'employee.designation', 'employee.branch']);

            // Calculate tax details if not already calculated
            if (!$payroll->paye_tax || !$payroll->nhif_deduction || !$payroll->nssf_deduction) {
                $this->calculateTaxes($payroll);
            }

            $data = $this->getPayslipData($payroll);

            \Log::info('Generating PDF with data for payslip notification', [
                'payroll_id' => $payroll->id,
                'employee_id' => $payroll->employee->id,
                'data_keys' => array_keys($data)
            ]);

            $pdf = Pdf::loadView('payslips.template', $data)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => false,
                    'isFontSubsettingEnabled' => true,
                    'defaultMediaType' => 'print',
                ]);

            \Log::info('PDF generated successfully for payslip notification', [
                'payroll_id' => $payroll->id,
                'pdf_size' => strlen($pdf->output())
            ]);

            return $pdf;

        } catch (\Exception $e) {
            \Log::error('PDF generation failed for payslip notification', [
                'error' => $e->getMessage(),
                'payroll_id' => $payroll->id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Calculate taxes for payroll if not already calculated
     */
    protected function calculateTaxes($payroll): void
    {
        $taxService = app(\App\Services\TaxCalculationService::class);
        $taxCalculation = $taxService->calculateAllTaxes(
            $payroll->basic_salary,
            $payroll->calculateTotalAllowances(),
            $payroll->calculateTotalDeductions(),
            0 // dependents - can be added later
        );

        $payroll->update([
            'paye_tax' => $taxCalculation['paye']['paye_tax'],
            'nhif_deduction' => $taxCalculation['nhif']['nhif_contribution'],
            'nssf_deduction' => $taxCalculation['nssf']['total_nssf'],
            'taxable_income' => $taxCalculation['taxable_income'],
            'personal_relief' => $taxCalculation['paye']['personal_relief'],
            'total_relief' => $taxCalculation['paye']['total_relief'],
            'gross_pay' => $taxCalculation['gross_pay'],
            'net_pay' => $taxCalculation['net_pay'],
            'calculation_details' => $taxCalculation,
        ]);
    }

    /**
     * Get payslip data for PDF generation
     */
    protected function getPayslipData($payroll): array
    {
        $employee = $payroll->employee;

        $company = [
            'name' => 'CADEBECK HR Management',
            'address' => 'Nairobi, Kenya',
            'phone' => '+254 XXX XXX XXX',
            'email' => 'hr@cadebeck.com',
        ];

        $employeeData = [
            'id' => $employee?->id ?? 'N/A',
            'name' => $employee?->user ? trim(($employee->user->first_name ?? '') . ' ' . ($employee->user->other_names ?? '')) : 'N/A',
            'employee_number' => $employee?->staff_number ?? 'N/A',
            'department' => $employee?->department?->name ?? 'N/A',
            'designation' => $employee?->designation?->name ?? 'N/A',
            'branch' => $employee?->branch?->name ?? 'N/A',
        ];

        return [
            'company' => $company,
            'employee' => $employeeData,
            'payroll' => [
                'period' => $payroll->payroll_period,
                'pay_date' => $payroll->pay_date?->format('M d, Y') ?? 'N/A',
                'basic_salary' => $payroll->basic_salary,
                'allowances' => [
                    'house' => $payroll->house_allowance,
                    'transport' => $payroll->transport_allowance,
                    'medical' => $payroll->medical_allowance,
                    'other' => $payroll->other_allowances,
                    'overtime' => $payroll->overtime_amount,
                    'bonus' => $payroll->bonus_amount,
                    'total' => $payroll->calculateTotalAllowances(),
                ],
                'deductions' => [
                    'paye' => $payroll->paye_tax,
                    'nhif' => $payroll->nhif_deduction,
                    'nssf' => $payroll->nssf_deduction,
                    'insurance' => $payroll->insurance_deduction,
                    'loan' => $payroll->loan_deduction,
                    'other' => $payroll->other_deductions,
                    'total' => $payroll->calculateTotalDeductions(),
                ],
                'gross_pay' => $payroll->gross_pay,
                'net_pay' => $payroll->net_pay,
            ],
            'generated_at' => now()->format('M d, Y H:i'),
            'payslip_number' => $this->generatePayslipNumber($payroll),
        ];
    }

    /**
     * Generate unique payslip filename
     */
    protected function generatePayslipFilename($payroll): string
    {
        $employee = $payroll->employee;
        $period = str_replace('/', '-', $payroll->payroll_period);
        $timestamp = now()->format('Y-m-d_H-i-s');

        return "payslip_{$employee->staff_number}_{$period}_{$timestamp}.pdf";
    }

    /**
     * Generate unique payslip number
     */
    protected function generatePayslipNumber($payroll): string
    {
        $employee = $payroll->employee;
        $period = str_replace('/', '-', $payroll->payroll_period);
        $uniqueId = strtoupper(\Illuminate\Support\Str::random(6));

        return "PSL-{$employee->staff_number}-{$period}-{$uniqueId}";
    }
}
