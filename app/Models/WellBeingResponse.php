<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class WellBeingResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'user_id',
        'assessment_type',
        'period_start_date',
        'period_end_date',
        'frequency',
        'stress_level',
        'work_life_balance',
        'job_satisfaction',
        'support_level',
        'comments',
        'additional_metrics',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
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

    public function department(): HasOneThrough
    {
        return $this->hasOneThrough(
            Department::class,
            Employee::class,
            'id',
            'id',
            'employee_id',
            'department_id'
        );
    }

    public function scopeViewableBy(Builder $query, User $user): Builder
    {
        if ($user->hasRole(['Developer', 'Executive'])) {
            return $query;
        }

        if ($user->hasRole('Manager N-1')) {
            return $query->whereHas('employee', function (Builder $employeeQuery) use ($user) {
                $employeeQuery->where(function (Builder $subQuery) use ($user) {
                    $subQuery->whereHas('supervisor.supervisor', function (Builder $supervisorQuery) use ($user) {
                        $supervisorQuery->where('user_id', $user->id);
                    })->orWhere(function (Builder $managerQuery) use ($user) {
                        $managerQuery->whereHas('user.roles', function ($roleQuery) {
                            $roleQuery->where('name', 'Manager N-2');
                        })->whereHas('supervisor', function (Builder $supervisorQuery) use ($user) {
                            $supervisorQuery->where('user_id', $user->id);
                        });
                    });
                });
            });
        }

        if ($user->hasRole('Manager N-2')) {
            return $query->whereHas('employee', function (Builder $employeeQuery) use ($user) {
                $employeeQuery->whereHas('supervisor', function (Builder $supervisorQuery) use ($user) {
                    $supervisorQuery->where('user_id', $user->id);
                });
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeToday($query)
    {
        return $query->where('period_start_date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('period_start_date', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('period_start_date', now()->month)
            ->whereYear('period_start_date', now()->year);
    }

    public function scopeByAssessmentType($query, $type)
    {
        return $query->where('assessment_type', $type);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
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

        if ($score >= 8) {
            return 'excellent';
        }
        if ($score >= 6) {
            return 'good';
        }
        if ($score >= 4) {
            return 'fair';
        }

        return 'needs_attention';
    }

    public function getStressLevelText(): string
    {
        if ($this->stress_level <= 3) {
            return 'Low';
        }
        if ($this->stress_level <= 6) {
            return 'Moderate';
        }

        return 'High';
    }

    public function getSatisfactionText(): string
    {
        if ($this->job_satisfaction <= 3) {
            return 'Low';
        }
        if ($this->job_satisfaction <= 6) {
            return 'Moderate';
        }

        return 'High';
    }
}
