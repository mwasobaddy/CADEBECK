<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, BelongsToClient;

    /**
     * Scope to users who hold a permission, through a role or directly.
     *
     * Used where authorization has to be expressed inside a relation query,
     * e.g. "employees supervised by somebody who can view direct reports".
     * Checking the permission rather than a role name keeps this working for
     * custom roles created by a client.
     */
    public function scopeWithPermission(Builder $query, string $permission): Builder
    {
        return $query->where(function (Builder $q) use ($permission) {
            $q->whereHas('roles.permissions', fn ($roleQuery) => $roleQuery->where('name', $permission))
                ->orWhereHas('permissions', fn ($permissionQuery) => $permissionQuery->where('name', $permission));
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'first_name',
        'other_names',
        'email',
        'password',
        'lang',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
    return Str::of(trim($this->first_name . ' ' . $this->other_names))
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the user's full name
     */
    public function getNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->other_names);
    }

    /**
     * Get the employee record associated with this user
     */
    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Get the language preference for this user
     */
    public function language()
    {
        return $this->belongsTo(Language::class, 'lang', 'code');
    }
}
