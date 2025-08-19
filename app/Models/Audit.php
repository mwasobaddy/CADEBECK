<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $actor_id
 * @property string $action
 * @property string $target_type
 * @property int $target_id
 * @property string $details
 */
class Audit extends Model
{
    protected $fillable = [
        'actor_id', 'action', 'target_type', 'target_id', 'details'
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
