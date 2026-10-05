<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property int $location_id
 */
class Branch extends Model
{
    use SoftDeletes, BelongsToClient;
    protected $fillable = [
        'name', 'code', 'location_id',
        'client_id',
    ];
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
    public function departments()
    {
        return $this->hasMany(Department::class);
    }
}
