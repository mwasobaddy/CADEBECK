<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\Employee;
use App\Notifications\PayslipNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PayslipService
{
    protected TaxCalculationService $taxService;

    public function __construct(TaxCalculationService $taxService)
    {
        $this->taxService = $taxService;
    }

    /**
     * Generate payslip PDF for a payroll record
     */
    public function generatePayslip(Payroll $payroll): Payslip
    {
        // Load employee with all necessary relationships
        $payroll->load(['employee.user', 'employee.department', 'employee.designation', 'employee.branch']);

        // Calculate tax details if not already calculated
        if (!$payroll->paye_tax || !$payroll->nhif_deduction || !$payroll->nssf_deduction) {
            $this->calculateTaxes($payroll);
        }

        // Generate PDF
        $pdf = $this->createPayslipPDF($payroll);

        // Generate unique filename
        $filename = $this->generatePayslipFilename($payroll);

        // Store PDF file
        $filePath = $this->storePayslipPDF($pdf, $filename);

        // Create or update payslip record
        $payslip = Payslip::updateOrCreate(
            ['payroll_id' => $payroll->id],
            [
                'employee_id' => $payroll->employee_id,
                'payslip_number' => $this->generatePayslipNumber($payroll),
                'payroll_period' => $payroll->payroll_period,
                'pay_date' => $payroll->pay_date,
                'file_path' => $filePath,
                'file_name' => $filename,
                'payslip_data' => $this->getPayslipData($payroll),
                'generated_at' => now(),
            ]
        );

        return $payslip;
    }

    /**
     * Calculate taxes for payroll if not already calculated
     */
    protected function calculateTaxes(Payroll $payroll): void
    {
        $taxCalculation = $this->taxService->calculateAllTaxes(
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
     * Create payslip PDF using DomPDF
     */
    protected function createPayslipPDF(Payroll $payroll)
    {
        $data = $this->getPayslipData($payroll);

        // Debug: Log the data being passed to the template
        \Log::info('Payslip PDF Data:', $data);

        $pdf = Pdf::loadView('PDF-Templates.payslip', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'isFontSubsettingEnabled' => true,
                'defaultMediaType' => 'print',
            ]);

        return $pdf;
    }

    /**
     * Get payslip data for PDF generation
     */
    protected function getPayslipData(Payroll $payroll): array
    {
        $employee = $payroll->employee()->with('user', 'department', 'designation', 'branch')->first();

        // Debug logging
        \Log::info('Employee data for payslip:', [
            'employee_id' => $employee?->id,
            'employee_staff_number' => $employee?->staff_number,
            'user_exists' => $employee?->user ? 'yes' : 'no',
            'user_first_name' => $employee?->user?->first_name,
            'user_other_names' => $employee?->user?->other_names,
            'department' => $employee?->department?->name,
            'designation' => $employee?->designation?->name,
        ]);

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

        \Log::info('Employee data array:', $employeeData);

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
    protected function generatePayslipFilename(Payroll $payroll): string
    {
        $employee = $payroll->employee;
        $period = str_replace('/', '-', $payroll->payroll_period);
        $timestamp = now()->format('Y-m-d_H-i-s');

        return "payslip_{$employee->employee_number}_{$period}_{$timestamp}.pdf";
    }

    /**
     * Generate unique payslip number
     */
    protected function generatePayslipNumber(Payroll $payroll): string
    {
        $employee = $payroll->employee;
        $period = str_replace('/', '-', $payroll->payroll_period);
        $uniqueId = strtoupper(Str::random(6));

        return "PSL-{$employee->staff_number}-{$period}-{$uniqueId}";
    }

    /**
     * Store payslip PDF file in temporary location
     */
    protected function storePayslipPDF($pdf, string $filename): string
    {
        // Store in temp/payslips folder for temporary access
        $path = "temp/payslips/{$filename}";
        Storage::disk('public')->put($path, $pdf->output());

        // Schedule cleanup after 24 hours
        $this->scheduleTempFileCleanup($path);

        // Return the temporary path
        return $path;
    }

    /**
     * Send payslip via email
     */
    public function sendPayslipEmail(Payslip $payslip, ?string $subject = null, ?string $message = null): bool
    {
        try {
            $employee = $payslip->payroll->employee;
            $user = $employee->user;

            if (!$user || !$user->email) {
                throw new \Exception(__('Employee does not have an associated user account or email address.'));
            }

            // Send notification
            $user->notify(new \App\Notifications\PayslipNotification(
                $payslip,
                $subject,
                $message
            ));

            // Update payslip record
            $payslip->update([
                'email_sent_at' => now(),
                'email_status' => 'sent',
                'email_recipient' => $user->email,
            ]);

            // Log the email sending
            \App\Models\Audit::create([
                'actor_id' => auth()->id(),
                'action' => 'payslip_email_sent',
                'target_type' => Payslip::class,
                'target_id' => $payslip->id,
                'details' => json_encode([
                    'email_sent_at' => now(),
                    'recipient_email' => $user->email,
                ]),
            ]);

            return true;
        } catch (\Exception $e) {
            // Update payslip record with error
            $payslip->update([
                'email_status' => 'failed',
                'email_error' => $e->getMessage(),
                'email_sent_at' => now(),
            ]);

            // Log the error
            \App\Models\Audit::create([
                'actor_id' => auth()->id(),
                'action' => 'payslip_email_failed',
                'target_type' => Payslip::class,
                'target_id' => $payslip->id,
                'details' => json_encode([
                    'error_message' => $e->getMessage(),
                ]),
            ]);

            return false;
        }
    }

    /**
     * Get payslip download URL
     */
    public function getPayslipUrl(Payslip $payslip): string
    {
        return Storage::disk('public')->url($payslip->file_path);
    }

    /**
     * Delete payslip file
     */
    public function deletePayslipFile(Payslip $payslip): bool
    {
        if (Storage::disk('public')->exists($payslip->file_path)) {
            Storage::disk('public')->delete($payslip->file_path);
            return true;
        }

        return false;
    }

    /**
     * Regenerate payslip PDF
     */
    public function regeneratePayslip(Payslip $payslip): Payslip
    {
        // Delete old file
        $this->deletePayslipFile($payslip);

        // Get payroll with all necessary relationships
        $payroll = $payslip->payroll()->with(['employee.user', 'employee.department', 'employee.designation', 'employee.branch'])->first();

        // Generate new PDF
        return $this->generatePayslip($payroll);
    }

    /**
     * Schedule cleanup of temp file after 24 hours
     */
    protected function scheduleTempFileCleanup(string $filePath): void
    {
        // Use Laravel's queue to schedule cleanup
        \Illuminate\Support\Facades\Queue::later(
            now()->addDay(),
            new \App\Jobs\DeleteTempPayslipFile($filePath)
        );
    }

    /**
     * Check if payslip file exists, regenerate if not
     */
    public function ensurePayslipFileExists(Payslip $payslip): string
    {
        if (Storage::disk('public')->exists($payslip->file_path)) {
            return $payslip->file_path;
        }

        // File doesn't exist, regenerate it
        $regeneratedPayslip = $this->regeneratePayslip($payslip);

        return $regeneratedPayslip->file_path;
    }

    /**
     * Clean up old temp files (can be called by a scheduled job)
     */
    public function cleanupOldTempFiles(int $daysOld = 1): int
    {
        // Always use temp/payslips as the base path
        $tempPath = 'temp/payslips';
        $files = Storage::disk('public')->files($tempPath);
        $deletedCount = 0;

        foreach ($files as $file) {
            $filePath = storage_path('app/public/' . $file);
            if (file_exists($filePath) && filemtime($filePath) < now()->subDays($daysOld)->timestamp) {
                Storage::disk('public')->delete($file);
                $deletedCount++;
            }
        }

        return $deletedCount;
    }
}
