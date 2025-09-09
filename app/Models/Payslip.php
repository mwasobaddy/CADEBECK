<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    protected $fillable = [
        'payroll_id',
        'employee_id',
        'payslip_number',
        'payslip_data',
        'pdf_path',
        'email_sent',
        'email_sent_at',
        'viewed_at',
        'downloaded_at',
        'generated_at',
        'generated_by',
    ];

    protected $casts = [
        'payslip_data' => 'array',
        'email_sent' => 'boolean',
        'email_sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'downloaded_at' => 'datetime',
        'generated_at' => 'datetime',
    ];

    // Relationships
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // Scopes
    public function scopeEmailed($query)
    {
        return $query->where('email_sent', true);
    }

    public function scopeNotEmailed($query)
    {
        return $query->where('email_sent', false);
    }

    public function scopeViewed($query)
    {
        return $query->whereNotNull('viewed_at');
    }

    public function scopeDownloaded($query)
    {
        return $query->whereNotNull('downloaded_at');
    }

    // Helper methods
    public function isEmailed(): bool
    {
        return $this->email_sent;
    }

    public function isViewed(): bool
    {
        return !is_null($this->viewed_at);
    }

    public function isDownloaded(): bool
    {
        return !is_null($this->downloaded_at);
    }

    public function markAsEmailed(): void
    {
        $this->update([
            'email_sent' => true,
            'email_sent_at' => now(),
        ]);
    }

    public function markAsViewed(): void
    {
        if (!$this->viewed_at) {
            $this->update(['viewed_at' => now()]);
        }
    }

    public function markAsDownloaded(): void
    {
        if (!$this->downloaded_at) {
            $this->update(['downloaded_at' => now()]);
        }
    }

    public function getPdfUrl(): string
    {
        return asset('storage/' . $this->pdf_path);
    }

    public function generatePayslipNumber(): string
    {
        return 'PSL-' . date('Y') . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    // Static methods
    public static function generateUniquePayslipNumber(): string
    {
        do {
            $number = 'PSL-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('payslip_number', $number)->exists());

        return $number;
    }
}
