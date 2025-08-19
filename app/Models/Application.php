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
        'job_advert_id', 'name', 'email', 'phone', 'cv_blob', 'cover_letter', 'status', 'submitted_at'
    ];

    /**
     * The job advert this application belongs to.
     */
    public function jobAdvert(): BelongsTo
    {
        return $this->belongsTo(JobAdvert::class);
    }
}
