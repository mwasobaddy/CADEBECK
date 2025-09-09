<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRepayment extends Model
{
    protected $fillable = [
        'employee_loan_id',
        'payroll_id',
        'installment_number',
        'amount',
        'principal_amount',
        'interest_amount',
        'balance_before',
        'balance_after',
        'payment_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // Relationships
    public function employeeLoan(): BelongsTo
    {
        return $this->belongsTo(EmployeeLoan::class);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    // Scopes
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByInstallment($query, $number)
    {
        return $query->where('installment_number', $number);
    }

    // Helper methods
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => 'paid']);
    }

    public function getFormattedPaymentDate(): string
    {
        return $this->payment_date ? $this->payment_date->format('M d, Y') : 'Not set';
    }
}
