<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;

class TaxCalculationService
{
    protected string $taxYear;

    public function __construct()
    {
        $this->taxYear = '2024-2025';
    }

    /**
     * Calculate UK PAYE (Pay As You Earn) income tax
     * Based on UK tax bands for 2024/2025 tax year
     */
    public function calculatePAYE(float $grossPay, ?string $taxCode = null): array
    {
        $personalAllowance = 12570;
        $basicRateLimit = 37700;
        $higherRateLimit = 125140;

        $taxCode = $taxCode ?? '1257L';
        $taxCodeAllowance = $this->parseTaxCode($taxCode, $personalAllowance);

        $taxableIncome = max(0, $grossPay - $taxCodeAllowance);

        $taxDetails = [];
        $totalTax = 0;

        if ($taxableIncome <= $basicRateLimit) {
            $totalTax = $taxableIncome * 0.20;
            $taxDetails[] = [
                'band' => 'Basic Rate (20%)',
                'taxable_amount' => $taxableIncome,
                'rate' => '20%',
                'tax_amount' => round($totalTax, 2),
            ];
        } else {
            $basicTax = $basicRateLimit * 0.20;
            $totalTax += $basicTax;
            $taxDetails[] = [
                'band' => 'Basic Rate (20%)',
                'taxable_amount' => $basicRateLimit,
                'rate' => '20%',
                'tax_amount' => round($basicTax, 2),
            ];

            if ($taxableIncome <= $higherRateLimit) {
                $higherIncome = $taxableIncome - $basicRateLimit;
                $higherTax = $higherIncome * 0.40;
                $totalTax += $higherTax;
                $taxDetails[] = [
                    'band' => 'Higher Rate (40%)',
                    'taxable_amount' => $higherIncome,
                    'rate' => '40%',
                    'tax_amount' => round($higherTax, 2),
                ];
            } else {
                $higherTax = ($higherRateLimit - $basicRateLimit) * 0.40;
                $totalTax += $higherTax;
                $taxDetails[] = [
                    'band' => 'Higher Rate (40%)',
                    'taxable_amount' => $higherRateLimit - $basicRateLimit,
                    'rate' => '40%',
                    'tax_amount' => round($higherTax, 2),
                ];

                $additionalIncome = $taxableIncome - $higherRateLimit;
                $additionalTax = $additionalIncome * 0.45;
                $totalTax += $additionalTax;
                $taxDetails[] = [
                    'band' => 'Additional Rate (45%)',
                    'taxable_amount' => $additionalIncome,
                    'rate' => '45%',
                    'tax_amount' => round($additionalTax, 2),
                ];
            }
        }

        return [
            'gross_pay' => $grossPay,
            'tax_code' => $taxCode,
            'tax_free_allowance' => $taxCodeAllowance,
            'taxable_income' => $taxableIncome,
            'total_tax' => round(max(0, $totalTax), 2),
            'tax_details' => $taxDetails,
        ];
    }

    /**
     * Parse UK tax code to extract allowance
     */
    protected function parseTaxCode(string $taxCode, int $defaultAllowance): float
    {
        $numericCode = preg_replace('/[^0-9]/', '', $taxCode);
        if (empty($numericCode)) {
            return $defaultAllowance;
        }
        return (float) $numericCode * 10;
    }

    /**
     * Calculate UK National Insurance (Employee's contribution)
     * Based on 2024/2025 thresholds
     */
    public function calculateNIC(float $grossPay, string $nicCategory = 'A'): array
    {
        $primaryThreshold = 12570;
        $upperThreshold = 50270;

        $weeklyThreshold = $primaryThreshold / 52;
        $weeklyUpper = $upperThreshold / 52;

        $rates = [
            'A' => [0.08, 0.02],
            'B' => [0.057, 0.02],
            'C' => [0.00, 0.00],
            'H' => [0.06, 0.02],
            'M' => [0.08, 0.02],
            'Z' => [0.0585, 0.02],
        ];

        $categoryRates = $rates[$nicCategory] ?? $rates['A'];

        $weeklyPay = $grossPay / 52;
        $employeeNi = 0;

        if ($weeklyPay > $weeklyThreshold) {
            $mainRate = min($weeklyPay, $weeklyUpper) - $weeklyThreshold;
            $employeeNi = $mainRate * $categoryRates[0];

            if ($weeklyPay > $weeklyUpper) {
                $upperRate = $weeklyPay - $weeklyUpper;
                $employeeNi += $upperRate * $categoryRates[1];
            }
        }

        return [
            'gross_pay' => $grossPay,
            'nic_category' => $nicCategory,
            'primary_threshold' => $primaryThreshold,
            'upper_threshold' => $upperThreshold,
            'employee_contribution' => round($employeeNi * 52, 2),
        ];
    }

    /**
     * Calculate employer National Insurance contribution
     */
    public function calculateEmployerNI(float $grossPay): array
    {
        $secondaryThreshold = 9580;
        $upperSecondaryThreshold = 50960;

        $monthlyThreshold = $secondaryThreshold / 12;
        $monthlyUpper = $upperSecondaryThreshold / 12;

        $monthlyPay = $grossPay / 12;
        $employerNi = 0;

        if ($monthlyPay > $monthlyThreshold) {
            $niAtMainRate = min($monthlyPay, $monthlyUpper) - $monthlyThreshold;
            $employerNi = $niAtMainRate * 0.138;

            if ($monthlyPay > $monthlyUpper) {
                $niAtUpperRate = $monthlyPay - $monthlyUpper;
                $employerNi += $niAtUpperRate * 0.049;
            }
        }

        return [
            'gross_pay' => $grossPay,
            'secondary_threshold' => $secondaryThreshold,
            'employer_contribution' => round($employerNi * 12, 2),
        ];
    }

    /**
     * Calculate Student Loan repayment (Plan 1 or Plan 2)
     */
    public function calculateStudentLoan(float $grossPay, string $plan = 'plan1'): array
    {
        $thresholds = [
            'plan1' => ['threshold' => 25365, 'rate' => 0.09],
            'plan2' => ['threshold' => 27295, 'rate' => 0.09],
            'postgrad' => ['threshold' => 21000, 'rate' => 0.06],
        ];

        $planConfig = $thresholds[$plan] ?? $thresholds['plan1'];
        $repayment = max(0, ($grossPay - $planConfig['threshold']) * $planConfig['rate']);

        return [
            'gross_pay' => $grossPay,
            'plan' => $plan,
            'threshold' => $planConfig['threshold'],
            'repayment' => round($repayment / 12, 2),
        ];
    }

    /**
     * Calculate workplace pension contribution (auto-enrolment)
     */
    public function calculatePension(float $grossPay, float $employeeRate = 0.05, float $employerRate = 0.03): array
    {
        $qualifyingEarningsMin = 6080;
        $qualifyingEarningsMax = 60960;

        $annualQualifyingEarnings = max(0, min($grossPay, $qualifyingEarningsMax) - $qualifyingEarningsMin);

        $employeeContribution = $annualQualifyingEarnings * $employeeRate;
        $employerContribution = $annualQualifyingEarnings * $employerRate;

        return [
            'gross_pay' => $grossPay,
            'qualifying_earnings' => round($annualQualifyingEarnings, 2),
            'employee_contribution' => round($employeeContribution / 12, 2),
            'employer_contribution' => round($employerContribution / 12, 2),
            'total_contribution' => round(($employeeContribution + $employerContribution) / 12, 2),
        ];
    }

    /**
     * Calculate all UK taxes and deductions for an employee
     */
    public function calculateAllTaxes(
        float $basicSalary,
        float $totalAllowances,
        float $totalDeductions = 0,
        ?string $taxCode = '1257L',
        string $nicCategory = 'A',
        ?string $studentLoanPlan = null,
        bool $includePension = true
    ): array {
        $grossPay = $basicSalary + $totalAllowances;

        $payeResult = $this->calculatePAYE($grossPay, $taxCode);
        $nicResult = $this->calculateNIC($grossPay, $nicCategory);
        $employerNiResult = $this->calculateEmployerNI($grossPay);

        $studentLoanResult = null;
        if ($studentLoanPlan) {
            $studentLoanResult = $this->calculateStudentLoan($grossPay, $studentLoanPlan);
        }

        $pensionResult = null;
        if ($includePension) {
            $pensionResult = $this->calculatePension($grossPay);
        }

        $totalStatutory = $payeResult['total_tax'] 
            + $nicResult['employee_contribution']
            + ($studentLoanResult['repayment'] ?? 0)
            + ($pensionResult['employee_contribution'] ?? 0);

        $netPay = $grossPay - $totalStatutory - $totalDeductions;

        return [
            'gross_pay' => round($grossPay, 2),
            'tax_code' => $taxCode,
            'paye' => $payeResult,
            'national_insurance' => $nicResult,
            'employer_national_insurance' => $employerNiResult,
            'student_loan' => $studentLoanResult,
            'pension' => $pensionResult,
            'total_statutory_deductions' => round($totalStatutory, 2),
            'other_deductions' => round($totalDeductions, 2),
            'net_pay' => round(max(0, $netPay), 2),
            'tax_calculation_date' => Carbon::now()->toDateString(),
            'tax_year' => $this->taxYear,
        ];
    }

    /**
     * Get tax summary for reporting
     */
    public function getTaxSummary(Employee $employee, string $period): array
    {
        return [
            'employee_id' => $employee->id,
            'employee_name' => $employee->first_name . ' ' . $employee->other_names,
            'period' => $period,
            'tax_summary' => [
                'total_gross_pay' => 0,
                'total_paye' => 0,
                'total_national_insurance' => 0,
                'total_employer_ni' => 0,
                'total_pension' => 0,
                'net_tax_liability' => 0,
            ],
        ];
    }
}
