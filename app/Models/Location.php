<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $address
 * @property string|null $city
 * @property string|null $country
 */
class Location extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name', 'code', 'address', 'city', 'country'
    ];
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }
}
