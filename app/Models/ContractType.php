<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 */
class ContractType extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name', 'code', 'description'
    ];
}
