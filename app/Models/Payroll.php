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
        'paye_tax',
        'nhif_deduction',
        'nssf_deduction',
        'insurance_deduction',
        'loan_deduction',
        'other_deductions',
        'total_deductions',
        'net_pay',
        'taxable_income',
        'personal_relief',
        'insurance_relief',
        'total_relief',
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
        'nhif_deduction' => 'decimal:2',
        'nssf_deduction' => 'decimal:2',
        'insurance_deduction' => 'decimal:2',
        'loan_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'taxable_income' => 'decimal:2',
        'personal_relief' => 'decimal:2',
        'insurance_relief' => 'decimal:2',
        'total_relief' => 'decimal:2',
    ];

    // Relationships
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

    // Scopes
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

    // Helper methods
    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function markAsProcessed(User $user = null): void
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

    // Calculation methods
    public function calculateTotalAllowances(): float
    {
        return $this->house_allowance +
               $this->transport_allowance +
               $this->medical_allowance +
               $this->other_allowances +
               $this->overtime_amount +
               $this->bonus_amount;
    }

    public function calculateTotalDeductions(): float
    {
        return $this->paye_tax +
               $this->nhif_deduction +
               $this->nssf_deduction +
               $this->insurance_deduction +
               $this->loan_deduction +
               $this->other_deductions;
    }

    public function calculateGrossPay(): float
    {
        return $this->basic_salary + $this->calculateTotalAllowances();
    }

    public function calculateNetPay(): float
    {
        return $this->calculateGrossPay() - $this->calculateTotalDeductions();
    }

    public function calculateTaxableIncome(): float
    {
        return $this->basic_salary + $this->calculateTotalAllowances();
    }
}
