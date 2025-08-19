<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property string $deadline
 * @property string $status
 * @property int $posted_by
 */
class JobAdvert extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'deadline', 'status', 'posted_by'
    ];

    /**
     * The user who posted the job advert.
     */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * Applications for this job advert.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
