<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeLoan extends Model
{
    protected $fillable = [
        'employee_id',
        'loan_type',
        'principal_amount',
        'interest_rate',
        'interest_amount',
        'total_amount',
        'monthly_installment',
        'total_installments',
        'paid_installments',
        'remaining_balance',
        'start_date',
        'end_date',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'monthly_installment' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('loan_type', $type);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFullyPaid(): bool
    {
        return $this->remaining_balance <= 0;
    }

    public function calculateRemainingInstallments(): int
    {
        return $this->total_installments - $this->paid_installments;
    }

    public function calculateProgressPercentage(): float
    {
        if ($this->total_amount <= 0) {
            return 0;
        }

        return (($this->total_amount - $this->remaining_balance) / $this->total_amount) * 100;
    }

    public function approve(User $user): void
    {
        $this->update([
            'status' => 'active',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function updateBalance(float $amount): void
    {
        $this->remaining_balance -= $amount;
        $this->paid_installments += 1;

        if ($this->remaining_balance <= 0) {
            $this->complete();
        } else {
            $this->save();
        }
    }

    // Static methods for loan types
    public static function getLoanTypes(): array
    {
        return [
            'personal' => 'Personal Loan',
            'housing' => 'Housing Loan',
            'vehicle' => 'Vehicle Loan',
            'education' => 'Education Loan',
            'emergency' => 'Emergency Loan',
            'other' => 'Other Loan',
        ];
    }
}
