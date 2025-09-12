<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;

class TaxCalculationService
{
    /**
     * Calculate PAYE (Pay As You Earn) tax for Kenya
     * Based on KRA tax brackets for 2024/2025
     */
    public function calculatePAYE(float $taxableIncome, int $dependents = 0): array
    {
        // Personal relief: USD 2,400 per month
        $personalRelief = 2400;

        // Insurance relief: Up to USD 5,000 per month
        $insuranceRelief = 0; // This will be calculated separately

        // Total relief
        $totalRelief = $personalRelief + $insuranceRelief;

        // Tax brackets for monthly income (2024/2025)
        $taxBrackets = [
            ['min' => 0, 'max' => 24000, 'rate' => 0.10, 'fixed' => 0],
            ['min' => 24001, 'max' => 32333, 'rate' => 0.25, 'fixed' => 2400],
            ['min' => 32334, 'max' => 40333, 'rate' => 0.30, 'fixed' => 5280],
            ['min' => 40334, 'max' => 48333, 'rate' => 0.32, 'fixed' => 7920],
            ['min' => 48334, 'max' => 100000, 'rate' => 0.34, 'fixed' => 10440],
            ['min' => 100001, 'max' => 200000, 'rate' => 0.36, 'fixed' => 28960],
            ['min' => 200001, 'max' => PHP_FLOAT_MAX, 'rate' => 0.37, 'fixed' => 64960],
        ];

        $taxableAfterRelief = max(0, $taxableIncome - $totalRelief);

        $payeTax = 0;
        $taxDetails = [];

        foreach ($taxBrackets as $bracket) {
            if ($taxableAfterRelief > $bracket['min']) {
                $taxableInBracket = min($taxableAfterRelief, $bracket['max']) - $bracket['min'];
                $taxInBracket = ($taxableInBracket * $bracket['rate']) + $bracket['fixed'];

                if ($taxableInBracket > 0) {
                    $taxDetails[] = [
                        'bracket' => $bracket['min'] . ' - ' . ($bracket['max'] == PHP_FLOAT_MAX ? 'Above' : $bracket['max']),
                        'taxable_amount' => $taxableInBracket,
                        'rate' => $bracket['rate'] * 100 . '%',
                        'tax_amount' => $taxInBracket,
                    ];
                }

                $payeTax += $taxInBracket;

                if ($taxableAfterRelief <= $bracket['max']) {
                    break;
                }
            }
        }

        return [
            'taxable_income' => $taxableIncome,
            'personal_relief' => $personalRelief,
            'insurance_relief' => $insuranceRelief,
            'total_relief' => $totalRelief,
            'taxable_after_relief' => $taxableAfterRelief,
            'paye_tax' => round($payeTax, 2),
            'tax_details' => $taxDetails,
        ];
    }

    /**
     * Calculate NHIF deduction
     * Based on NHIF contribution rates for 2024
     */
    public function calculateNHIF(float $grossPay): array
    {
        // NHIF contribution brackets (monthly)
        $nhifBrackets = [
            ['min' => 0, 'max' => 5999, 'contribution' => 150],
            ['min' => 6000, 'max' => 7999, 'contribution' => 300],
            ['min' => 8000, 'max' => 11999, 'contribution' => 400],
            ['min' => 12000, 'max' => 14999, 'contribution' => 500],
            ['min' => 15000, 'max' => 19999, 'contribution' => 600],
            ['min' => 20000, 'max' => 24999, 'contribution' => 750],
            ['min' => 25000, 'max' => 29999, 'contribution' => 850],
            ['min' => 30000, 'max' => 34999, 'contribution' => 900],
            ['min' => 35000, 'max' => 39999, 'contribution' => 950],
            ['min' => 40000, 'max' => 44999, 'contribution' => 1000],
            ['min' => 45000, 'max' => 49999, 'contribution' => 1100],
            ['min' => 50000, 'max' => 59999, 'contribution' => 1200],
            ['min' => 60000, 'max' => 69999, 'contribution' => 1300],
            ['min' => 70000, 'max' => 79999, 'contribution' => 1400],
            ['min' => 80000, 'max' => 89999, 'contribution' => 1500],
            ['min' => 90000, 'max' => 99999, 'contribution' => 1600],
            ['min' => 100000, 'max' => PHP_FLOAT_MAX, 'contribution' => 1700],
        ];

        $nhifContribution = 0;
        $bracket = null;

        foreach ($nhifBrackets as $nhifBracket) {
            if ($grossPay >= $nhifBracket['min'] && $grossPay <= $nhifBracket['max']) {
                $nhifContribution = $nhifBracket['contribution'];
                $bracket = $nhifBracket;
                break;
            }
        }

        return [
            'gross_pay' => $grossPay,
            'nhif_contribution' => $nhifContribution,
            'bracket' => $bracket ? $bracket['min'] . ' - ' . ($bracket['max'] == PHP_FLOAT_MAX ? 'Above' : $bracket['max']) : 'Not found',
        ];
    }

    /**
     * Calculate NSSF deduction
     * Based on NSSF rates for 2024 (Tier I and II)
     */
    public function calculateNSSF(float $basicSalary): array
    {
        // NSSF rates for 2024
        $tier1Rate = 0.06; // 6% of pensionable earnings
        $tier2Rate = 0.06; // 6% of pensionable earnings
        $tier1Max = 7000; // Maximum pensionable earnings for Tier I
        $tier2Max = 36000; // Maximum pensionable earnings for Tier II

        // Employee contribution (6% of basic salary, capped)
        $tier1Contribution = min($basicSalary, $tier1Max) * $tier1Rate;
        $tier2Contribution = max(0, min($basicSalary, $tier2Max) - $tier1Max) * $tier2Rate;

        $totalNSSF = $tier1Contribution + $tier2Contribution;

        return [
            'basic_salary' => $basicSalary,
            'tier1_contribution' => round($tier1Contribution, 2),
            'tier2_contribution' => round($tier2Contribution, 2),
            'total_nssf' => round($totalNSSF, 2),
            'tier1_max' => $tier1Max,
            'tier2_max' => $tier2Max,
        ];
    }

    /**
     * Calculate all taxes for an employee
     */
    public function calculateAllTaxes(float $basicSalary, float $totalAllowances, float $totalDeductions = 0, int $dependents = 0): array
    {
        $grossPay = $basicSalary + $totalAllowances;
        $taxableIncome = $grossPay;

        // Calculate individual taxes
        $payeResult = $this->calculatePAYE($taxableIncome, $dependents);
        $nhifResult = $this->calculateNHIF($grossPay);
        $nssfResult = $this->calculateNSSF($basicSalary);

        // Total statutory deductions
        $totalStatutory = $payeResult['paye_tax'] + $nhifResult['nhif_contribution'] + $nssfResult['total_nssf'];

        // Net pay calculation
        $netPay = $grossPay - $totalStatutory - $totalDeductions;

        return [
            'gross_pay' => round($grossPay, 2),
            'taxable_income' => round($taxableIncome, 2),
            'paye' => $payeResult,
            'nhif' => $nhifResult,
            'nssf' => $nssfResult,
            'total_statutory_deductions' => round($totalStatutory, 2),
            'other_deductions' => round($totalDeductions, 2),
            'net_pay' => round($netPay, 2),
            'tax_calculation_date' => Carbon::now()->toDateString(),
        ];
    }

    /**
     * Calculate housing levy (1.5% of basic salary)
     */
    public function calculateHousingLevy(float $basicSalary): float
    {
        $housingLevyRate = 0.015; // 1.5%
        return round($basicSalary * $housingLevyRate, 2);
    }

    /**
     * Get tax summary for reporting
     */
    public function getTaxSummary(Employee $employee, string $period): array
    {
        // This would typically fetch payroll data for the period
        // For now, return a template structure
        return [
            'employee_id' => $employee->id,
            'employee_name' => $employee->first_name . ' ' . $employee->other_names,
            'period' => $period,
            'tax_summary' => [
                'total_gross_pay' => 0,
                'total_paye' => 0,
                'total_nhif' => 0,
                'total_nssf' => 0,
                'total_housing_levy' => 0,
                'net_tax_liability' => 0,
            ],
        ];
    }
}
