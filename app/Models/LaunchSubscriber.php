<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Model;

class LaunchSubscriber extends Model
{

    use BelongsToClient;
    protected $fillable = ['client_id', 'email'];
}