<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollAllowance;
use App\Models\PayrollDeduction;
use App\Models\Payslip;
use App\Models\User;
use App\Services\TaxCalculationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class PayrollSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::all();
        $totalPayrolls = 150;
        $payrollPeriods = $this->generatePayrollPeriods();

        // Distribute payrolls across employees (some will have multiple periods)
        $payrollsCreated = 0;

        foreach ($employees as $employee) {
            // Determine how many payrolls this employee should have
            $employeePayrolls = $this->calculateEmployeePayrolls($employees->count(), $totalPayrolls, $payrollsCreated);
            $remainingPayrolls = $totalPayrolls - $payrollsCreated;

            if ($employeePayrolls > count($payrollPeriods)) {
                $employeePayrolls = count($payrollPeriods);
            }

            // Select random periods for this employee
            $selectedPeriods = collect($payrollPeriods)->random(min($employeePayrolls, count($payrollPeriods)));

            foreach ($selectedPeriods as $period) {
                $this->createPayrollForEmployee($employee, $period);
                $payrollsCreated++;

                if ($payrollsCreated >= $totalPayrolls) {
                    break 2;
                }
            }
        }
    }

    /**
     * Generate payroll periods from 2024-01 to current month
     */
    private function generatePayrollPeriods(): array
    {
        $periods = [];
        $start = Carbon::create(2024, 1, 1);
        $end = Carbon::now();

        while ($start <= $end) {
            $periods[] = $start->format('Y-m');
            $start->addMonth();
        }

        return array_reverse($periods); // Most recent first
    }

    /**
     * Calculate how many payrolls an employee should have
     */
    private function calculateEmployeePayrolls(int $totalEmployees, int $totalPayrolls, int $created): int
    {
        $remainingEmployees = $totalEmployees - ($created > 0 ? 1 : 0);
        $remainingPayrolls = $totalPayrolls - $created;

        if ($remainingEmployees <= 0) return 0;

        // Distribute remaining payrolls, with some variation
        $basePayrolls = intdiv($remainingPayrolls, $remainingEmployees);
        $extraPayrolls = $remainingPayrolls % $remainingEmployees;

        // Add some randomness (1-3 payrolls per employee)
        $randomPayrolls = rand(1, 3);

        return min($basePayrolls + ($extraPayrolls > 0 ? 1 : 0) + $randomPayrolls, 12); // Max 12 months
    }

    /**
     * Create a complete payroll record with allowances, deductions, and payslip
     */
    private function createPayrollForEmployee(Employee $employee, string $period): void
    {
        // Skip if payroll already exists for this employee/period
        if (Payroll::where('employee_id', $employee->id)->where('payroll_period', $period)->exists()) {
            return;
        }

        $payDate = Carbon::createFromFormat('Y-m', $period)->endOfMonth();
        $basicSalary = $employee->basic_salary ?: $this->generateBasicSalary();

        // Generate allowances
        $allowances = $this->generateAllowances($employee, $basicSalary);
        $totalAllowances = collect($allowances)->sum(fn ($val) => is_numeric($val) ? $val : 0);

        // Generate deductions
        $deductions = $this->generateDeductions($employee, $basicSalary);
        $totalDeductions = collect($deductions)->sum(fn ($val) => is_numeric($val) ? $val : 0);

        // UK tax settings
        $taxCode = $employee->tax_code ?? collect(['1257L', '1250L', 'K1000', '0T'])->random();
        $nicCategory = $employee->nic_category ?? collect(['A', 'B', 'C', 'H', 'M', 'Z'])->random();
        $studentLoanPlan = rand(0, 3) === 0 ? collect(['plan1', 'plan2', 'postgrad'])->random() : null;
        $includePension = (bool) rand(0, 1);

        // Calculate UK taxes
        $taxService = new TaxCalculationService();
        $taxCalculation = $taxService->calculateAllTaxes(
            $basicSalary,
            $totalAllowances,
            $totalDeductions,
            $taxCode,
            $nicCategory,
            $studentLoanPlan,
            $includePension
        );

        // Create payroll record
        $payroll = Payroll::create([
            'employee_id' => $employee->id,
            'payroll_period' => $period,
            'pay_date' => $payDate,
            'basic_salary' => $basicSalary,
            'house_allowance' => $allowances['house'] ?? 0,
            'transport_allowance' => $allowances['transport'] ?? 0,
            'medical_allowance' => $allowances['medical'] ?? 0,
            'other_allowances' => ($allowances['overtime'] ?? 0) + ($allowances['bonus'] ?? 0),
            'total_allowances' => $totalAllowances,
            'overtime_hours' => $allowances['overtime_hours'] ?? 0,
            'overtime_rate' => $allowances['overtime_rate'] ?? 0,
            'overtime_amount' => $allowances['overtime'] ?? 0,
            'bonus_amount' => $allowances['bonus'] ?? 0,
            'gross_pay' => $taxCalculation['gross_pay'],
            'tax_code' => $taxCalculation['tax_code'],
            'paye_tax' => $taxCalculation['paye']['total_tax'] ?? 0,
            'national_insurance' => $taxCalculation['national_insurance']['employee_contribution'] ?? 0,
            'student_loan_deduction' => $taxCalculation['student_loan']['repayment'] ?? 0,
            'pension_contribution' => $taxCalculation['pension']['employee_contribution'] ?? 0,
            'employer_pension_contribution' => $taxCalculation['pension']['employer_contribution'] ?? 0,
            'nic_category' => $taxCalculation['national_insurance']['nic_category'] ?? 'A',
            'student_loan_plan' => $taxCalculation['student_loan']['plan'] ?? null,
            'insurance_deduction' => $deductions['insurance'] ?? 0,
            'loan_deduction' => $deductions['loan'] ?? 0,
            'other_deductions' => $deductions['other'] ?? 0,
            'total_deductions' => $totalDeductions + $taxCalculation['total_statutory_deductions'],
            'net_pay' => $taxCalculation['net_pay'],
            'taxable_income' => $taxCalculation['paye']['taxable_income'] ?? $taxCalculation['gross_pay'],
            'status' => collect(['draft', 'processed', 'paid'])->random(),
            'notes' => 'Generated by seeder',
            'calculation_details' => $taxCalculation,
            'processed_at' => $payDate,
            'processed_by' => User::whereHas('roles', function($q) {
                $q->whereIn('name', ['Manager N-1', 'Executive']);
            })->inRandomOrder()->first()?->id,
        ]);

        // Create allowance records
        foreach ($allowances as $type => $amount) {
            if ($amount > 0 && !in_array($type, ['overtime_hours', 'overtime_rate'])) {
                PayrollAllowance::create([
                    'employee_id' => $employee->id,
                    'payroll_id' => $payroll->id,
                    'allowance_type' => $type,
                    'description' => ucfirst($type) . ' allowance',
                    'amount' => $amount,
                    'is_recurring' => collect([true, false])->random(),
                    'effective_date' => $payDate,
                    'status' => 'active',
                    'notes' => ucfirst($type) . ' allowance for ' . $period,
                ]);
            }
        }

        // Create deduction records
        foreach ($deductions as $type => $amount) {
            if ($amount > 0) {
                PayrollDeduction::create([
                    'employee_id' => $employee->id,
                    'payroll_id' => $payroll->id,
                    'deduction_type' => $type,
                    'description' => ucfirst($type) . ' deduction',
                    'amount' => $amount,
                    'is_recurring' => collect([true, false])->random(),
                    'effective_date' => $payDate,
                    'status' => 'active',
                    'notes' => ucfirst($type) . ' deduction for ' . $period,
                ]);
            }
        }

        // Create payslip record
        $this->createPayslip($payroll, $employee, $period);
    }

    /**
     * Generate realistic basic salary
     */
    private function generateBasicSalary(): float
    {
        // Kenyan salary ranges (USD)
        $salaryRanges = [
            ['min' => 15000, 'max' => 30000], // Entry level
            ['min' => 30000, 'max' => 50000], // Junior
            ['min' => 50000, 'max' => 80000], // Mid level
            ['min' => 80000, 'max' => 150000], // Senior
            ['min' => 150000, 'max' => 300000], // Management
        ];

        $range = collect($salaryRanges)->random();
        return rand($range['min'], $range['max']);
    }

    /**
     * Generate allowances for an employee
     */
    private function generateAllowances(Employee $employee, float $basicSalary): array
    {
        $allowances = [];

        // House allowance (10-20% of basic salary)
        $allowances['house'] = rand(0, 1) ? round($basicSalary * (rand(10, 20) / 100), 2) : 0;

        // Transport allowance (5-10% of basic salary)
        $allowances['transport'] = rand(0, 1) ? round($basicSalary * (rand(5, 10) / 100), 2) : 0;

        // Medical allowance (2-5% of basic salary)
        $allowances['medical'] = rand(0, 1) ? round($basicSalary * (rand(2, 5) / 100), 2) : 0;

        // Overtime (random hours at 1.5x rate)
        if (rand(0, 2) === 0) { // 33% chance
            $overtimeHours = rand(5, 40);
            $hourlyRate = $basicSalary / 160; // Assuming 160 working hours per month
            $allowances['overtime_hours'] = $overtimeHours;
            $allowances['overtime_rate'] = round($hourlyRate * 1.5, 2);
            $allowances['overtime'] = round($overtimeHours * $allowances['overtime_rate'], 2);
        } else {
            $allowances['overtime_hours'] = 0;
            $allowances['overtime_rate'] = 0;
            $allowances['overtime'] = 0;
        }

        // Bonus (0-15% of basic salary, 20% chance)
        $allowances['bonus'] = rand(0, 4) === 0 ? round($basicSalary * (rand(5, 15) / 100), 2) : 0;

        return $allowances;
    }

    /**
     * Generate deductions for an employee
     */
    private function generateDeductions(Employee $employee, float $basicSalary): array
    {
        $deductions = [];

        // Insurance deduction (1-3% of basic salary, 50% chance)
        $deductions['insurance'] = rand(0, 1) ? round($basicSalary * (rand(1, 3) / 100), 2) : 0;

        // Loan deduction (5-15% of basic salary, 30% chance)
        $deductions['loan'] = rand(0, 2) === 0 ? round($basicSalary * (rand(5, 15) / 100), 2) : 0;

        // Other deductions (small random amount, 20% chance)
        $deductions['other'] = rand(0, 4) === 0 ? rand(100, 1000) : 0;

        return $deductions;
    }

    /**
     * Create payslip record for the payroll
     */
    private function createPayslip(Payroll $payroll, Employee $employee, string $period): void
    {
        $payslipNumber = Payslip::generateUniquePayslipNumber();

        // Generate file path (simulated)
        $fileName = $payslipNumber . '.pdf';
        $filePath = 'temp/payslips/' . $fileName;

        Payslip::create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employee->id,
            'payslip_number' => $payslipNumber,
            'payroll_period' => $period,
            'pay_date' => $payroll->pay_date,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'payslip_data' => [
                'employee' => [
                    'name' => $employee->user->first_name . ' ' . $employee->user->other_names,
                    'staff_number' => $employee->staff_number,
                    'department' => $employee->department?->name,
                ],
                'payroll' => [
                    'period' => $period,
                    'basic_salary' => $payroll->basic_salary,
                    'allowances' => [
                        'house' => $payroll->house_allowance,
                        'transport' => $payroll->transport_allowance,
                        'medical' => $payroll->medical_allowance,
                        'overtime' => $payroll->overtime_amount,
                        'bonus' => $payroll->bonus_amount,
                        'total' => $payroll->total_allowances,
                    ],
                'deductions' => [
                    'paye' => $payroll->paye_tax,
                    'national_insurance' => $payroll->national_insurance,
                    'student_loan' => $payroll->student_loan_deduction,
                    'pension' => $payroll->pension_contribution,
                    'insurance' => $payroll->insurance_deduction,
                    'loan' => $payroll->loan_deduction,
                    'other' => $payroll->other_deductions,
                    'total' => $payroll->total_deductions,
                ],
                    'gross_pay' => $payroll->gross_pay,
                    'net_pay' => $payroll->net_pay,
                ],
            ],
            'is_emailed' => (bool) rand(0, 1),
            'emailed_at' => rand(0, 1) ? $payroll->pay_date : null,
            'is_downloaded' => (bool) rand(0, 1),
            'downloaded_at' => rand(0, 1) ? $payroll->pay_date : null,
        ]);
    }
}
