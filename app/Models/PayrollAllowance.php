<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAllowance extends Model
{
    protected $fillable = [
        'employee_id',
        'payroll_id',
        'allowance_type',
        'amount',
        'is_recurring',
        'effective_date',
        'end_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_recurring' => 'boolean',
        'effective_date' => 'date',
        'end_date' => 'date',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public function scopeOneTime($query)
    {
        return $query->where('is_recurring', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('allowance_type', $type);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isRecurring(): bool
    {
        return $this->is_recurring;
    }

    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    public function deactivate(): void
    {
        $this->update(['status' => 'inactive']);
    }

    // Static methods for allowance types
    public static function getAllowanceTypes(): array
    {
        return [
            'house' => 'House Allowance',
            'transport' => 'Transport Allowance',
            'medical' => 'Medical Allowance',
            'overtime' => 'Overtime Allowance',
            'bonus' => 'Bonus',
            'other' => 'Other Allowance',
        ];
    }
}
