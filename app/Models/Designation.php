<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 */
class Designation extends Model
{
    use SoftDeletes, BelongsToClient;
    protected $fillable = [
        'name', 'code', 'description',
        'client_id',
    ];
}
