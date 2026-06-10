<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Common\Exception\IOException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PayrollImportService
{
    protected TaxCalculationService $taxService;

    public function __construct(TaxCalculationService $taxService)
    {
        $this->taxService = $taxService;
    }

    public function import(string $filePath, string $mode, string $period, ?Carbon $payDate = null): array
    {
        $payDate = $payDate ?? Carbon::now()->endOfMonth();

        $options = new CsvOptions();

        $reader = new Reader($options);
        $reader->open($filePath);

        $headers = [];
        $imported = [];
        $errors = [];
        $rowIndex = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowIndex++;
                $cells = [];
                foreach ($row->cells as $cell) {
                    $cells[] = (string) $cell->getValue();
                }

                if ($rowIndex === 1) {
                    $headers = $cells;
                    continue;
                }

                $data = array_combine($headers, $cells);

                if ($mode === 'bulk') {
                    $result = $this->importBulkRow($data, $period, $payDate);
                } else {
                    $result = $this->importPrecomputedRow($data, $period, $payDate);
                }

                if ($result['success']) {
                    $imported[] = $result['payroll'];
                } else {
                    $errors[] = [
                        'row' => $rowIndex,
                        'employee' => $data['employee_code'] ?? 'unknown',
                        'errors' => $result['errors'],
                    ];
                }
            }
        }

        $reader->close();

        return [
            'total' => $rowIndex - 1,
            'imported' => count($imported),
            'errors' => count($errors),
            'error_details' => $errors,
            'mode' => $mode,
            'period' => $period,
            'pay_date' => $payDate->format('Y-m-d'),
        ];
    }

    protected function importBulkRow(array $data, string $period, Carbon $payDate): array
    {
        $employee = Employee::where('staff_number', $data['employee_code'] ?? '')->first();
        if (!$employee) {
            return ['success' => false, 'errors' => ['Employee not found with staff_number: ' . ($data['employee_code'] ?? '')]];
        }

        $basicSalary = (float) ($data['basic_salary'] ?? 0);
        $houseAllowance = (float) ($data['house_allowance'] ?? 0);
        $transportAllowance = (float) ($data['transport_allowance'] ?? 0);
        $medicalAllowance = (float) ($data['medical_allowance'] ?? 0);
        $otherAllowances = (float) ($data['other_allowances'] ?? 0);
        $totalAllowances = $houseAllowance + $transportAllowance + $medicalAllowance + $otherAllowances;
        $overtimeHours = (float) ($data['overtime_hours'] ?? 0);
        $overtimeRate = (float) ($data['overtime_rate'] ?? 0);
        $overtimeAmount = (float) ($data['overtime_amount'] ?? ($overtimeHours * $overtimeRate));
        $bonusAmount = (float) ($data['bonus_amount'] ?? 0);
        $grossPay = $basicSalary + $totalAllowances + $overtimeAmount + $bonusAmount;
        $otherDeductions = (float) ($data['other_deductions'] ?? 0);

        $taxResult = $this->taxService->calculateAllTaxes(
            basicSalary: $basicSalary * 12,
            totalAllowances: ($totalAllowances + $overtimeAmount + $bonusAmount) * 12,
            totalDeductions: $otherDeductions,
            taxCode: $employee->tax_code ?? '1257L',
            nicCategory: $employee->nic_category ?? 'A',
            studentLoanPlan: $employee->student_loan_plan,
            includePension: $employee->include_pension ?? true,
        );

        if (Payroll::where('employee_id', $employee->id)->where('payroll_period', $period)->exists()) {
            return ['success' => false, 'errors' => ['Payroll already exists for this period']];
        }

        try {
            $payroll = DB::transaction(function () use ($employee, $period, $payDate, $basicSalary, $houseAllowance, $transportAllowance, $medicalAllowance, $otherAllowances, $totalAllowances, $overtimeHours, $overtimeRate, $overtimeAmount, $bonusAmount, $grossPay, $taxResult, $otherDeductions, $data) {
                return Payroll::create([
                    'employee_id' => $employee->id,
                    'payroll_period' => $period,
                    'pay_date' => $payDate,
                    'basic_salary' => $basicSalary,
                    'house_allowance' => $houseAllowance,
                    'transport_allowance' => $transportAllowance,
                    'medical_allowance' => $medicalAllowance,
                    'other_allowances' => $otherAllowances,
                    'total_allowances' => $totalAllowances,
                    'overtime_hours' => $overtimeHours,
                    'overtime_rate' => $overtimeRate,
                    'overtime_amount' => $overtimeAmount,
                    'bonus_amount' => $bonusAmount,
                    'gross_pay' => $grossPay,
                    'tax_code' => $taxResult['tax_code'],
                    'paye_tax' => $taxResult['paye']['total_tax'],
                    'national_insurance' => $taxResult['national_insurance']['employee_contribution'],
                    'student_loan_deduction' => $taxResult['student_loan']['repayment'] ?? 0,
                    'pension_contribution' => $taxResult['pension']['employee_contribution'] ?? 0,
                    'employer_pension_contribution' => $taxResult['pension']['employer_contribution'] ?? 0,
                    'nic_category' => $taxResult['national_insurance']['nic_category'] ?? $employee->nic_category,
                    'student_loan_plan' => $employee->student_loan_plan,
                    'other_deductions' => $otherDeductions,
                    'total_deductions' => $taxResult['total_statutory_deductions'] + $otherDeductions,
                    'net_pay' => $taxResult['net_pay'],
                    'taxable_income' => $taxResult['paye']['taxable_income'] ?? $grossPay,
                    'status' => 'draft',
                    'notes' => $data['notes'] ?? 'Imported via bulk CSV upload',
                    'calculation_details' => json_encode($taxResult),
                ]);
            });

            return ['success' => true, 'payroll' => $payroll];
        } catch (\Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    protected function importPrecomputedRow(array $data, string $period, Carbon $payDate): array
    {
        $employee = Employee::where('staff_number', $data['employee_code'] ?? '')->first();
        if (!$employee) {
            return ['success' => false, 'errors' => ['Employee not found with staff_number: ' . ($data['employee_code'] ?? '')]];
        }

        if (Payroll::where('employee_id', $employee->id)->where('payroll_period', $period)->exists()) {
            return ['success' => false, 'errors' => ['Payroll already exists for this period']];
        }

        $validator = Validator::make($data, [
            'basic_salary' => 'required|numeric|min:0',
            'gross_pay' => 'required|numeric|min:0',
            'net_pay' => 'required|numeric|min:0',
            'paye_tax' => 'nullable|numeric|min:0',
            'national_insurance' => 'nullable|numeric|min:0',
            'student_loan_deduction' => 'nullable|numeric|min:0',
            'pension_contribution' => 'nullable|numeric|min:0',
            'total_deductions' => 'nullable|numeric|min:0',
            'employer_pension_contribution' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'errors' => $validator->errors()->all()];
        }

        try {
            $totalDeductions = (float) ($data['total_deductions'] ?? 0);
            $totalAllowances = (float) ($data['house_allowance'] ?? 0)
                + (float) ($data['transport_allowance'] ?? 0)
                + (float) ($data['medical_allowance'] ?? 0)
                + (float) ($data['other_allowances'] ?? 0);

            $payroll = DB::transaction(function () use ($employee, $period, $payDate, $data, $totalDeductions, $totalAllowances) {
                return Payroll::create([
                    'employee_id' => $employee->id,
                    'payroll_period' => $period,
                    'pay_date' => $payDate,
                    'basic_salary' => (float) ($data['basic_salary'] ?? 0),
                    'house_allowance' => (float) ($data['house_allowance'] ?? 0),
                    'transport_allowance' => (float) ($data['transport_allowance'] ?? 0),
                    'medical_allowance' => (float) ($data['medical_allowance'] ?? 0),
                    'other_allowances' => (float) ($data['other_allowances'] ?? 0),
                    'total_allowances' => $totalAllowances,
                    'overtime_hours' => (float) ($data['overtime_hours'] ?? 0),
                    'overtime_rate' => (float) ($data['overtime_rate'] ?? 0),
                    'overtime_amount' => (float) ($data['overtime_amount'] ?? 0),
                    'bonus_amount' => (float) ($data['bonus_amount'] ?? 0),
                    'gross_pay' => (float) $data['gross_pay'],
                    'paye_tax' => (float) ($data['paye_tax'] ?? 0),
                    'national_insurance' => (float) ($data['national_insurance'] ?? 0),
                    'student_loan_deduction' => (float) ($data['student_loan_deduction'] ?? 0),
                    'pension_contribution' => (float) ($data['pension_contribution'] ?? 0),
                    'employer_pension_contribution' => (float) ($data['employer_pension_contribution'] ?? 0),
                    'nic_category' => $data['nic_category'] ?? ($employee->nic_category ?? 'A'),
                    'student_loan_plan' => $data['student_loan_plan'] ?? $employee->student_loan_plan,
                    'other_deductions' => 0,
                    'total_deductions' => $totalDeductions,
                    'net_pay' => (float) $data['net_pay'],
                    'taxable_income' => (float) $data['gross_pay'],
                    'status' => 'processed',
                    'notes' => $data['notes'] ?? 'Imported via pre-computed CSV upload',
                ]);
            });

            return ['success' => true, 'payroll' => $payroll];
        } catch (\Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    public function generateTemplate(string $mode): string
    {
        $headers = $mode === 'bulk'
            ? ['employee_code', 'basic_salary', 'house_allowance', 'transport_allowance', 'medical_allowance', 'other_allowances', 'overtime_hours', 'overtime_rate', 'bonus_amount', 'other_deductions', 'notes']
            : ['employee_code', 'basic_salary', 'house_allowance', 'transport_allowance', 'medical_allowance', 'other_allowances', 'overtime_hours', 'overtime_amount', 'bonus_amount', 'gross_pay', 'paye_tax', 'national_insurance', 'student_loan_deduction', 'pension_contribution', 'employer_pension_contribution', 'total_deductions', 'net_pay', 'nic_category', 'student_loan_plan', 'notes'];

        $path = tempnam(sys_get_temp_dir(), 'payroll_template_') . '.csv';

        $file = fopen($path, 'w');
        fputcsv($file, $headers);
        fclose($file);

        return $path;
    }
}
