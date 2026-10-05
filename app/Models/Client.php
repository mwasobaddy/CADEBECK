<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant;

class Client extends Tenant
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * The package default is "tenants"; this application calls them clients.
     *
     * @var string
     */
    protected $table = 'clients';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'status',
        'contact_email',
        'contact_phone',
        'plan',
        'max_users',
        'timezone',
    ];

    /**
     * Get the database name for the tenant.
     *
     * The package implementation reads a "database" column because it targets
     * one-database-per-tenant. This application uses a single shared database,
     * so the current connection's database is always the correct answer.
     */
    public function getDatabaseName(): string
    {
        return DB::connection($this->getConnectionName())->getDatabaseName();
    }

    /**
     * The users belonging to this client.
     *
     * Platform staff (Developer, System Admin) have a null client_id and are
     * therefore not included here.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * The employees belonging to this client.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
