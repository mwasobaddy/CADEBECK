<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WellBeingResponse extends Model
{
    protected $fillable = [
        'employee_id',
        'user_id',
        'response_date',
        'stress_level',
        'work_life_balance',
        'job_satisfaction',
        'support_level',
        'comments',
        'additional_metrics',
    ];

    protected $casts = [
        'response_date' => 'date',
        'additional_metrics' => 'array',
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
        return $query->where('response_date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('response_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('response_date', now()->month)
                    ->whereYear('response_date', now()->year);
    }

    public function scopeHighStress($query)
    {
        return $query->where('stress_level', '>=', 7);
    }

    public function scopeLowSatisfaction($query)
    {
        return $query->where('job_satisfaction', '<=', 3);
    }

    public function getOverallWellbeingScore(): float
    {
        $metrics = [
            $this->stress_level,
            $this->work_life_balance,
            $this->job_satisfaction,
            $this->support_level,
        ];

        // Invert stress level (lower stress = higher wellbeing)
        $metrics[0] = 11 - $metrics[0];

        return round(array_sum($metrics) / count($metrics), 1);
    }

    public function getWellbeingStatus(): string
    {
        $score = $this->getOverallWellbeingScore();

        if ($score >= 8) return 'excellent';
        if ($score >= 6) return 'good';
        if ($score >= 4) return 'fair';
        return 'needs_attention';
    }

    public function getStressLevelText(): string
    {
        if ($this->stress_level <= 3) return 'Low';
        if ($this->stress_level <= 6) return 'Moderate';
        return 'High';
    }

    public function getSatisfactionText(): string
    {
        if ($this->job_satisfaction <= 3) return 'Low';
        if ($this->job_satisfaction <= 6) return 'Moderate';
        return 'High';
    }
}
