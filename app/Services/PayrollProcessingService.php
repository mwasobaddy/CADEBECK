<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\Employee;
use App\Models\PayrollAllowance;
use App\Models\PayrollDeduction;
use App\Models\EmployeeLoan;
use App\Models\LoanRepayment;
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
                        'employee_name' => $employee->first_name . ' ' . $employee->last_name,
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
        $payroll->markAsPaid();

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
    public function bulkApprovePayrolls(Collection $payrolls, $user): array
    {
        $approved = [];
        $errors = [];

        foreach ($payrolls as $payroll) {
            try {
                $this->approvePayroll($payroll, $user);
                $approved[] = $payroll;
            } catch (\Exception $e) {
                $errors[] = [
                    'payroll_id' => $payroll->id,
                    'employee_name' => $payroll->employee->first_name . ' ' . $payroll->employee->last_name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'approved_count' => count($approved),
            'error_count' => count($errors),
            'approved' => $approved,
            'errors' => $errors,
        ];
    }

    /**
     * Bulk mark payrolls as paid
     */
    public function bulkMarkAsPaid(Collection $payrolls): array
    {
        $paid = [];
        $errors = [];

        foreach ($payrolls as $payroll) {
            try {
                $this->markPayrollAsPaid($payroll);
                $paid[] = $payroll;
            } catch (\Exception $e) {
                $errors[] = [
                    'payroll_id' => $payroll->id,
                    'employee_name' => $payroll->employee->first_name . ' ' . $payroll->employee->last_name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'paid_count' => count($paid),
            'error_count' => count($errors),
            'paid' => $paid,
            'errors' => $errors,
        ];
    }
}
