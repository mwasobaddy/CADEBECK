<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\Employee;
use App\Models\PayrollAllowance;
use App\Models\PayrollDeduction;
use App\Models\EmployeeLoan;
use App\Models\LoanRepayment;
use App\Notifications\PayrollProcessedNotification;
use App\Notifications\PayrollPaidNotification;
use App\Services\PayslipService;
use App\Services\TaxCalculationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollProcessingService
{
    protected TaxCalculationService $taxService;
    protected PayslipService $payslipService;

    public function __construct(
        TaxCalculationService $taxService,
        PayslipService $payslipService
    ) {
        $this->taxService = $taxService;
        $this->payslipService = $payslipService;
    }

    /**
     * Process payroll for a specific period
     */
    public function processPayroll(string $period, ?Carbon $payDate = null): array
    {
        $payDate = $payDate ?? Carbon::now();

        DB::beginTransaction();
        try {
            $employees = Employee::active()->get();
            $processedPayrolls = [];
            $errors = [];

            foreach ($employees as $employee) {
                try {
                    $payroll = $this->processEmployeePayroll($employee, $period, $payDate);
                    $processedPayrolls[] = $payroll;
                } catch (\Exception $e) {
                    $errors[] = [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->first_name . ' ' . $employee->other_names,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            return [
                'success' => true,
                'processed_count' => count($processedPayrolls),
                'error_count' => count($errors),
                'payrolls' => $processedPayrolls,
                'errors' => $errors,
                'period' => $period,
                'pay_date' => $payDate->format('Y-m-d'),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process payroll for a single employee
     */
    public function processEmployeePayroll(Employee $employee, string $period, Carbon $payDate): Payroll
    {
        // Check if payroll already exists for this period
        $existingPayroll = Payroll::where('employee_id', $employee->id)
            ->where('payroll_period', $period)
            ->first();

        if ($existingPayroll) {
            throw new \Exception("Payroll already exists for employee {$employee->employee_number} for period {$period}");
        }

        // Get employee allowances and deductions
        $allowances = $this->getEmployeeAllowances($employee, $period);
        $deductions = $this->getEmployeeDeductions($employee, $period);

        // Calculate totals
        $totalAllowances = $allowances->sum('amount');
        $totalDeductions = $deductions->sum('amount');

        // Calculate taxes
        $taxCalculation = $this->taxService->calculateAllTaxes(
            $employee->basic_salary,
            $totalAllowances,
            $totalDeductions
        );

        // Create payroll record
        $payroll = Payroll::create([
            'employee_id' => $employee->id,
            'payroll_period' => $period,
            'pay_date' => $payDate,
            'basic_salary' => $employee->basic_salary,
            'house_allowance' => $allowances->where('allowance_type', 'house')->sum('amount'),
            'transport_allowance' => $allowances->where('allowance_type', 'transport')->sum('amount'),
            'medical_allowance' => $allowances->where('allowance_type', 'medical')->sum('amount'),
            'other_allowances' => $allowances->where('allowance_type', 'other')->sum('amount'),
            'total_allowances' => $totalAllowances,
            'overtime_amount' => $allowances->where('allowance_type', 'overtime')->sum('amount'),
            'bonus_amount' => $allowances->where('allowance_type', 'bonus')->sum('amount'),
            'gross_pay' => $taxCalculation['gross_pay'],
            'paye_tax' => $taxCalculation['paye']['paye_tax'],
            'nhif_deduction' => $taxCalculation['nhif']['nhif_contribution'],
            'nssf_deduction' => $taxCalculation['nssf']['total_nssf'],
            'insurance_deduction' => $deductions->where('deduction_type', 'insurance')->sum('amount'),
            'loan_deduction' => $deductions->where('deduction_type', 'loan')->sum('amount'),
            'other_deductions' => $deductions->where('deduction_type', 'other')->sum('amount'),
            'total_deductions' => $totalDeductions + $taxCalculation['total_statutory_deductions'],
            'net_pay' => $taxCalculation['net_pay'],
            'taxable_income' => $taxCalculation['taxable_income'],
            'personal_relief' => $taxCalculation['paye']['personal_relief'],
            'total_relief' => $taxCalculation['paye']['total_relief'],
            'status' => 'draft',
            'calculation_details' => $taxCalculation,
        ]);

        // Link allowances and deductions to payroll
        $this->linkAllowancesToPayroll($allowances, $payroll);
        $this->linkDeductionsToPayroll($deductions, $payroll);

        // Process loan repayments
        $this->processLoanRepayments($employee, $payroll);

        return $payroll;
    }

    /**
     * Get active allowances for employee
     */
    protected function getEmployeeAllowances(Employee $employee, string $period): Collection
    {
        return PayrollAllowance::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->where(function ($query) use ($period) {
                $query->where('is_recurring', true)
                      ->orWhere(function ($subQuery) use ($period) {
                          $subQuery->where('is_recurring', false)
                                   ->where('effective_date', '<=', Carbon::createFromFormat('m/Y', $period)->endOfMonth());
                      });
            })
            ->get();
    }

    /**
     * Get active deductions for employee
     */
    protected function getEmployeeDeductions(Employee $employee, string $period): Collection
    {
        return PayrollDeduction::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->where(function ($query) use ($period) {
                $query->where('is_recurring', true)
                      ->orWhere(function ($subQuery) use ($period) {
                          $subQuery->where('is_recurring', false)
                                   ->where('effective_date', '<=', Carbon::createFromFormat('m/Y', $period)->endOfMonth());
                      });
            })
            ->get();
    }

    /**
     * Link allowances to payroll record
     */
    protected function linkAllowancesToPayroll(Collection $allowances, Payroll $payroll): void
    {
        foreach ($allowances as $allowance) {
            $allowance->update(['payroll_id' => $payroll->id]);
        }
    }

    /**
     * Link deductions to payroll record
     */
    protected function linkDeductionsToPayroll(Collection $deductions, Payroll $payroll): void
    {
        foreach ($deductions as $deduction) {
            $deduction->update(['payroll_id' => $payroll->id]);
        }
    }

    /**
     * Process loan repayments for employee
     */
    protected function processLoanRepayments(Employee $employee, Payroll $payroll): void
    {
        $activeLoans = EmployeeLoan::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->where('remaining_balance', '>', 0)
            ->get();

        foreach ($activeLoans as $loan) {
            $repaymentAmount = min($loan->monthly_installment, $loan->remaining_balance);

            if ($repaymentAmount > 0) {
                // Calculate interest and principal portions
                $interestAmount = ($loan->remaining_balance * $loan->interest_rate / 100) / 12;
                $principalAmount = $repaymentAmount - $interestAmount;

                // Create repayment record
                LoanRepayment::create([
                    'employee_loan_id' => $loan->id,
                    'payroll_id' => $payroll->id,
                    'installment_number' => $loan->paid_installments + 1,
                    'amount' => $repaymentAmount,
                    'principal_amount' => $principalAmount,
                    'interest_amount' => $interestAmount,
                    'balance_before' => $loan->remaining_balance,
                    'balance_after' => $loan->remaining_balance - $repaymentAmount,
                    'payment_date' => $payroll->pay_date,
                    'status' => 'paid',
                ]);

                // Update loan balance
                $loan->update([
                    'remaining_balance' => $loan->remaining_balance - $repaymentAmount,
                    'paid_installments' => $loan->paid_installments + 1,
                ]);

                // Check if loan is fully paid
                if ($loan->remaining_balance <= 0) {
                    $loan->complete();
                }
            }
        }
    }

    /**
     * Approve payroll for payment
     */
    public function approvePayroll(Payroll $payroll, $user): void
    {
        $payroll->markAsProcessed($user);
    }

    /**
     * Mark payroll as paid
     */
    public function markPayrollAsPaid(Payroll $payroll): void
    {
        // Validate that payroll is in processed status before marking as paid
        if ($payroll->status !== 'processed') {
            throw new \Exception("Cannot mark payroll as paid. Current status: {$payroll->status}. Only 'processed' payrolls can be marked as paid.");
        }

        $payroll->update(['status' => 'paid']);

        // Generate payslip
        $this->payslipService->generatePayslip($payroll);
    }

    /**
     * Get payroll summary for a period
     */
    public function getPayrollSummary(string $period): array
    {
        $payrolls = Payroll::with('employee')
            ->where('payroll_period', $period)
            ->get();

        return [
            'period' => $period,
            'total_employees' => $payrolls->count(),
            'total_gross_pay' => $payrolls->sum('gross_pay'),
            'total_deductions' => $payrolls->sum('total_deductions'),
            'total_net_pay' => $payrolls->sum('net_pay'),
            'total_paye' => $payrolls->sum('paye_tax'),
            'total_nhif' => $payrolls->sum('nhif_deduction'),
            'total_nssf' => $payrolls->sum('nssf_deduction'),
            'processed_count' => $payrolls->where('status', 'processed')->count(),
            'paid_count' => $payrolls->where('status', 'paid')->count(),
        ];
    }

    /**
     * Bulk approve payrolls
     */
    public function bulkApprovePayrolls(Collection $payrolls, $user, bool $sendEmails = true): array
    {
        $approved = [];
        $errors = [];
        $notificationsSent = 0;
        $notificationErrors = 0;

        foreach ($payrolls as $payroll) {
            try {
                $this->approvePayroll($payroll, $user);
                $approved[] = $payroll;

                // Send notification to employee synchronously (only if enabled)
                if ($sendEmails) {
                    try {
                        if ($payroll->employee && $payroll->employee->user && $payroll->employee->user->email) {
                            // Create database notification first (always succeeds)
                            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                                'id' => \Illuminate\Support\Str::uuid(),
                                'type' => 'App\\Notifications\\PayrollApprovalNotification',
                                'notifiable_type' => 'App\\Models\\User',
                                'notifiable_id' => $payroll->employee->user->id,
                                'data' => json_encode([
                                    'payroll_id' => $payroll->id,
                                    'payroll_period' => $payroll->payroll_period,
                                    'subject' => 'Payroll Processing Initiated',
                                    'message' => 'Your payroll has been initiated for processing and will be reviewed shortly.',
                                    'type' => 'payroll_approval',
                                    'action_url' => route('employee.payroll-history'),
                                ]),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            // Send email notification synchronously (notify the related User)
                            try {
                                // Set mail configuration timeout for this specific send
                                config(['mail.mailers.smtp.timeout' => 10]); // 10 second timeout

                                $payroll->employee->user->notify(new \App\Notifications\PayrollApprovalNotification(
                                    $payroll,
                                    'Payroll Processing Initiated',
                                    'Your payroll has been initiated for processing and will be reviewed shortly.'
                                ));

                                $notificationsSent++;

                                \Log::info('Bulk approval notification sent', [
                                    'payroll_id' => $payroll->id,
                                    'employee_id' => $payroll->employee->id,
                                    'user_email' => $payroll->employee->user->email,
                                    'notification_type' => 'payroll_approval'
                                ]);

                                // Add delay to avoid Mailtrap rate limiting (5 seconds between emails)
                                if ($notificationsSent < count($payrolls)) {
                                    sleep(5);
                                }

                            } catch (\Exception $emailException) {
                                \Log::warning('Failed to send payroll approval email notification', [
                                    'payroll_id' => $payroll->id,
                                    'employee_id' => $payroll->employee->id,
                                    'user_email' => $payroll->employee->user->email,
                                    'error' => $emailException->getMessage()
                                ]);
                                // Continue processing - database notification was already created
                                $notificationErrors++;
                            }

                        } else {
                            \Log::warning('Employee missing user or email for bulk approval notification', [
                                'payroll_id' => $payroll->id,
                                'employee_id' => $payroll->employee->id ?? null,
                                'has_user' => $payroll->employee && $payroll->employee->user ? 'yes' : 'no',
                                'has_email' => $payroll->employee && $payroll->employee->user && $payroll->employee->user->email ? 'yes' : 'no'
                            ]);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Failed to send bulk approval notification', [
                            'payroll_id' => $payroll->id,
                            'employee_id' => $payroll->employee->id ?? null,
                            'user_email' => $payroll->employee->user->email ?? 'N/A',
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        $notificationErrors++;
                    }
                } else {
                    \Log::info('Email notifications disabled for bulk approval', [
                        'payroll_id' => $payroll->id,
                        'sendEmails' => false
                    ]);
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'payroll_id' => $payroll->id,
                    'employee_name' => $payroll->employee->first_name . ' ' . $payroll->employee->other_names,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'approved_count' => count($approved),
            'error_count' => count($errors),
            'notifications_sent' => $notificationsSent,
            'notification_errors' => $notificationErrors,
            'approved' => $approved,
            'errors' => $errors,
        ];
    }

    /**
     * Bulk mark payrolls as paid
     */
    public function bulkMarkAsPaid(Collection $payrolls, bool $sendEmails = true): array
    {
        $paid = [];
        $errors = [];
        $notificationsSent = 0;
        $notificationErrors = 0;

        // Load relationships for all payrolls to avoid N+1 queries
        $payrolls->load(['employee.user', 'employee.department', 'employee.designation']);

        foreach ($payrolls as $payroll) {
            try {
                $this->markPayrollAsPaid($payroll);
                $paid[] = $payroll;

                // Send payroll paid notification to employee synchronously (only if enabled)
                if ($sendEmails) {
                    try {
                        if ($payroll->employee && $payroll->employee->user && $payroll->employee->user->email) {
                            // Create database notification first (always succeeds)
                            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                                'id' => \Illuminate\Support\Str::uuid(),
                                'type' => 'App\\Notifications\\PayrollPaidNotification',
                                'notifiable_type' => 'App\\Models\\User',
                                'notifiable_id' => $payroll->employee->user->id,
                                'data' => json_encode([
                                    'payroll_id' => $payroll->id,
                                    'payroll_period' => $payroll->payroll_period,
                                    'subject' => 'Your Payroll Has Been Paid',
                                    'message' => 'Your payroll for ' . $payroll->payroll_period . ' has been paid. Your complete payslip details are shown below.',
                                    'type' => 'payroll_paid',
                                    'action_url' => route('employee.payroll-history'),
                                ]),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            // Send email notification synchronously
                            try {
                                // Set mail configuration timeout for this specific send
                                config(['mail.mailers.smtp.timeout' => 10]); // 10 second timeout

                                $payroll->employee->user->notify(new \App\Notifications\PayrollPaidNotification(
                                    $payroll,
                                    'Your Payroll Has Been Paid',
                                    'Your payroll for ' . $payroll->payroll_period . ' has been paid. Your complete payslip details are shown below.'
                                ));

                                $notificationsSent++;

                                \Log::info('Bulk payroll paid notification sent', [
                                    'payroll_id' => $payroll->id,
                                    'employee_id' => $payroll->employee->id,
                                    'user_email' => $payroll->employee->user->email,
                                    'notification_type' => 'payroll_paid'
                                ]);

                                // Add delay to avoid Mailtrap rate limiting (2 seconds between emails)
                                if ($notificationsSent < count($payrolls)) {
                                    sleep(2);
                                }

                            } catch (\Exception $emailException) {
                                \Log::warning('Failed to send payroll paid email notification', [
                                    'payroll_id' => $payroll->id,
                                    'employee_id' => $payroll->employee->id,
                                    'user_email' => $payroll->employee->user->email,
                                    'error' => $emailException->getMessage()
                                ]);
                                // Continue processing - database notification was already created
                                $notificationErrors++;
                            }
                        } else {
                            \Log::warning('Employee missing user or email for notification', [
                                'payroll_id' => $payroll->id,
                                'employee_id' => $payroll->employee->id ?? null,
                                'has_employee' => $payroll->employee ? 'yes' : 'no',
                                'has_user' => $payroll->employee && $payroll->employee->user ? 'yes' : 'no',
                                'has_email' => $payroll->employee && $payroll->employee->user && $payroll->employee->user->email ? 'yes' : 'no'
                            ]);
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Failed to queue payroll paid notification', [
                            'payroll_id' => $payroll->id,
                            'employee_id' => $payroll->employee->id ?? null,
                            'error' => $e->getMessage()
                        ]);
                        $notificationErrors++;
                    }
                } else {
                    \Log::info('Email notifications disabled for bulk payroll paid sending', [
                        'payroll_id' => $payroll->id,
                        'sendEmails' => false
                    ]);
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'payroll_id' => $payroll->id,
                    'employee_name' => $payroll->employee->first_name . ' ' . $payroll->employee->other_names,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'paid_count' => count($paid),
            'error_count' => count($errors),
            'notifications_sent' => $notificationsSent,
            'notification_errors' => $notificationErrors,
            'paid' => $paid,
            'errors' => $errors,
        ];
    }
}
