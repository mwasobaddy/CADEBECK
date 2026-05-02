<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Payslip extends Model
{
    protected $fillable = [
        'payroll_id',
        'employee_id',
        'payslip_number',
        'payroll_period',
        'pay_date',
        'file_path',
        'file_name',
        'payslip_data',
        'is_emailed',
        'emailed_at',
        'is_downloaded',
        'downloaded_at',
        'is_external',
        'external_file_path',
        'external_file_name',
        'external_uploaded_by',
        'external_uploaded_at',
    ];

    protected $casts = [
        'payslip_data' => 'array',
        'pay_date' => 'date',
        'is_emailed' => 'boolean',
        'emailed_at' => 'datetime',
        'is_downloaded' => 'boolean',
        'downloaded_at' => 'datetime',
        'is_external' => 'boolean',
        'external_uploaded_at' => 'datetime',
    ];

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

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'external_uploaded_by');
    }

    public function scopeEmailed($query)
    {
        return $query->where('is_emailed', true);
    }

    public function scopeNotEmailed($query)
    {
        return $query->where('is_emailed', false);
    }

    public function scopeViewed($query)
    {
        return $query->whereNotNull('viewed_at');
    }

    public function scopeDownloaded($query)
    {
        return $query->whereNotNull('downloaded_at');
    }

    public function scopeGenerated($query)
    {
        return $query->where('is_external', false);
    }

    public function scopeExternal($query)
    {
        return $query->where('is_external', true);
    }

    public function isEmailed(): bool
    {
        return $this->is_emailed;
    }

    public function isViewed(): bool
    {
        return !is_null($this->viewed_at);
    }

    public function isDownloaded(): bool
    {
        return $this->is_downloaded;
    }

    public function isExternal(): bool
    {
        return $this->is_external;
    }

    public function markAsEmailed(): void
    {
        $this->update([
            'is_emailed' => true,
            'emailed_at' => now(),
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
        if (!$this->is_downloaded) {
            $this->update(['is_downloaded' => true, 'downloaded_at' => now()]);
        }
    }

    public function getPdfUrl(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function getExternalFileUrl(): string
    {
        if ($this->external_file_path) {
            return asset('storage/' . $this->external_file_path);
        }
        return null;
    }

    public function getDownloadUrl(): string
    {
        if ($this->is_external && $this->external_file_path) {
            return $this->getExternalFileUrl();
        }
        return $this->getPdfUrl();
    }

    public function deleteExternalFile(): bool
    {
        if ($this->external_file_path && Storage::disk('public')->exists($this->external_file_path)) {
            return Storage::disk('public')->delete($this->external_file_path);
        }
        return false;
    }

    public function generatePayslipNumber(): string
    {
        return 'PSL-' . date('Y') . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    public static function generateUniquePayslipNumber(): string
    {
        do {
            $number = 'PSL-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('payslip_number', $number)->exists());

        return $number;
    }
}
