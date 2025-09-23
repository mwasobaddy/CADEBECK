<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $job_advert_id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $cover_letter
 * @property string $status
 * @property string $submitted_at
 */
class Application extends Model
{
    protected $fillable = [
        'job_advert_id', 'name', 'email', 'phone', 'cv_blob', 'cover_letter', 'status', 'submitted_at', 'status_changed_at'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'status_changed_at' => 'datetime',
    ];

    /**
     * Check if the application is in cooldown period (30 seconds)
     */
    public function isInCooldown(): bool
    {
        if (!$this->status_changed_at) {
            return false;
        }
        
        return $this->status_changed_at->diffInSeconds(now()) < 30;
    }

    /**
     * Get remaining cooldown time in seconds
     */
    public function getCooldownRemainingSeconds(): int
    {
        if (!$this->status_changed_at) {
            return 0;
        }
        
        $elapsed = $this->status_changed_at->diffInSeconds(now());
        return max(0, 30 - $elapsed);
    }

    /**
     * The job advert this application belongs to.
     */
    public function jobAdvert(): BelongsTo
    {
        return $this->belongsTo(JobAdvert::class);
    }
}
