<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'payroll_period',
        'pay_date',
        'basic_salary',
        'house_allowance',
        'transport_allowance',
        'medical_allowance',
        'other_allowances',
        'total_allowances',
        'overtime_hours',
        'overtime_rate',
        'overtime_amount',
        'bonus_amount',
        'gross_pay',
        'tax_code',
        'paye_tax',
        'national_insurance',
        'student_loan_deduction',
        'pension_contribution',
        'employer_pension_contribution',
        'insurance_deduction',
        'loan_deduction',
        'other_deductions',
        'total_deductions',
        'net_pay',
        'taxable_income',
        'nic_category',
        'student_loan_plan',
        'status',
        'notes',
        'calculation_details',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'pay_date' => 'date',
        'processed_at' => 'datetime',
        'calculation_details' => 'array',
        'basic_salary' => 'decimal:2',
        'house_allowance' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'medical_allowance' => 'decimal:2',
        'other_allowances' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'paye_tax' => 'decimal:2',
        'national_insurance' => 'decimal:2',
        'student_loan_deduction' => 'decimal:2',
        'pension_contribution' => 'decimal:2',
        'employer_pension_contribution' => 'decimal:2',
        'insurance_deduction' => 'decimal:2',
        'loan_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'taxable_income' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function payslip(): HasOne
    {
        return $this->hasOne(Payslip::class);
    }

    public function allowances(): HasMany
    {
        return $this->hasMany(PayrollAllowance::class);
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class);
    }

    public function scopeForPeriod($query, $period)
    {
        return $query->where('payroll_period', $period);
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function markAsProcessed(?User $user = null): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
            'processed_by' => $user ? $user->id : null,
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => 'paid']);
    }

    public function calculateTotalAllowances(): float
    {
        return (float) ($this->house_allowance ?? 0) +
               (float) ($this->transport_allowance ?? 0) +
               (float) ($this->medical_allowance ?? 0) +
               (float) ($this->other_allowances ?? 0) +
               (float) ($this->overtime_amount ?? 0) +
               (float) ($this->bonus_amount ?? 0);
    }

    public function calculateTotalDeductions(): float
    {
        return (float) ($this->paye_tax ?? 0) +
               (float) ($this->national_insurance ?? 0) +
               (float) ($this->student_loan_deduction ?? 0) +
               (float) ($this->pension_contribution ?? 0) +
               (float) ($this->insurance_deduction ?? 0) +
               (float) ($this->loan_deduction ?? 0) +
               (float) ($this->other_deductions ?? 0);
    }

    public function calculateGrossPay(): float
    {
        return (float) $this->basic_salary + $this->calculateTotalAllowances();
    }

    public function calculateNetPay(): float
    {
        return $this->calculateGrossPay() - $this->calculateTotalDeductions();
    }

    public function calculateTaxableIncome(): float
    {
        return (float) $this->basic_salary + $this->calculateTotalAllowances();
    }
}
