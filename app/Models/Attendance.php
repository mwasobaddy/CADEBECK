<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'user_id',
        'date',
        'clock_in_time',
        'clock_out_time',
        'total_hours',
        'status',
        'notes',
        'location_data',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in_time' => 'datetime',
        'clock_out_time' => 'datetime',
        'total_hours' => 'decimal:2',
        'location_data' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeToday($query)
    {
        return $query->where('date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('date', now()->month)
                    ->whereYear('date', now()->year);
    }

    public function isClockedIn(): bool
    {
        return !is_null($this->clock_in_time) && is_null($this->clock_out_time);
    }

    public function isClockedOut(): bool
    {
        return !is_null($this->clock_in_time) && !is_null($this->clock_out_time);
    }

    public function calculateTotalHours()
    {
        if ($this->clock_in_time && $this->clock_out_time) {
            $hours = $this->clock_in_time->diffInMinutes($this->clock_out_time) / 60;
            return round($hours, 2);
        }
        return 0;
    }
}
