<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Client-aware access to roles and the permission catalogue.
 *
 * Roles live either globally (client_id null - the five default bundles) or
 * owned by a single client (custom roles). The rules enforced here are:
 *
 *  - a client user sees the global roles plus its own client's roles
 *  - a client user may only create, edit and delete its own client's roles,
 *    and may never modify a global role, since that would affect every client
 *  - a client user may only assign permissions from the non-platform catalogue,
 *    so it cannot grant itself access_all_clients or impersonate_client
 *  - platform staff (holding access_all_clients) are unrestricted
 */
class RoleCatalog
{
    /**
     * Roles the current user is allowed to see: global roles plus the roles of
     * their own client.
     */
    public function visibleRoles(): \Illuminate\Database\Eloquent\Builder
    {
        $clientId = ClientContext::currentClientId();

        return Role::query()->where(function ($query) use ($clientId) {
            $query->whereNull('client_id');

            if ($clientId !== null) {
                $query->orWhere('client_id', $clientId);
            }
        });
    }

    /**
     * Resolve a role the current user is allowed to manage, or fail.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findManageableOrFail(int|string $id): Role
    {
        $role = $this->visibleRoles()->findOrFail($id);

        // Global roles are shared by every client, so only platform staff may
        // change or remove them.
        if ($role->client_id !== null) {
            return $role;
        }

        abort_unless($this->userIsPlatformStaff(), 403, 'Only platform staff can modify shared roles.');

        return $role;
    }

    /**
     * Whether the current user may create, edit or delete the given role.
     */
    public function canManage(Role $role): bool
    {
        if ($this->userIsPlatformStaff()) {
            return true;
        }

        return $role->client_id !== null
            && $role->client_id === ClientContext::currentClientId();
    }

    /**
     * The client a newly created role should belong to.
     *
     * Client users always create roles inside their own client; only platform
     * staff may create a shared (global) role.
     */
    public function newRoleClientId(): ?int
    {
        if ($this->userIsPlatformStaff()) {
            return null;
        }

        return ClientContext::currentClientId();
    }

    /**
     * Ensure a role name is free among the roles this user can see.
     *
     * Spatie only rejects duplicate names in Role::create(), not on rename, and
     * the database index treats NULL client_id as distinct. Without this, a
     * client could rename a role onto a shared role's name, leaving two roles
     * with the same name visible to that client and making name-based role
     * assignment ambiguous.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function assertNameAvailable(string $name, ?Role $ignore = null): void
    {
        $clash = $this->visibleRoles()
            ->where('name', $name)
            ->when($ignore, fn ($query) => $query->where($query->qualifyColumn('id'), '!=', $ignore->getKey()))
            ->exists();

        if ($clash) {
            throw ValidationException::withMessages([
                'form.name' => [__('A role with this name already exists.')],
            ]);
        }
    }

    /**
     * Whether a role is a platform-level role.
     *
     * A role counts as platform-level when it carries any platform permission,
     * rather than when it is called "Developer" - naming would break the moment
     * a role is renamed or a client creates a similarly named role.
     */
    public function isPlatformRole(Role $role): bool
    {
        return $role->permissions()
            ->whereIn('name', $this->platformPermissions())
            ->exists();
    }

    /**
     * Roles the current user may assign to somebody.
     *
     * Client users get the shared roles plus their own client's roles, minus any
     * platform-level role, so a Client Admin cannot promote anybody (or
     * themselves) to Developer or System Admin. Platform staff may assign
     * everything they can see.
     */
    public function assignableRoles(): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->visibleRoles();

        if ($this->userIsPlatformStaff()) {
            return $query;
        }

        $platform = $this->platformPermissions();

        return $query->where(function ($builder) use ($platform) {
            foreach ($platform as $permission) {
                $builder->whereDoesntHave('permissions', fn ($q) => $q->where('name', $permission));
            }
        });
    }

    /**
     * Whether the current user may assign the given role.
     */
    public function canAssign(Role $role): bool
    {
        if ($this->userIsPlatformStaff()) {
            return true;
        }

        return ! $this->isPlatformRole($role)
            && $this->visibleRoles()->whereKey($role->getKey())->exists();
    }

    /**
     * Resolve a role by name that the current user is allowed to assign.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function findAssignableByName(string $name): Role
    {
        $role = $this->visibleRoles()->where('name', $name)->first();

        if (! $role || ! $this->canAssign($role)) {
            throw ValidationException::withMessages([
                'form.role' => [__('The selected role is invalid.')],
            ]);
        }

        return $role;
    }

    /**
     * The permissions the current user may assign to a role.
     */
    public function assignablePermissions(): SupportCollection
    {
        if ($this->userIsPlatformStaff()) {
            return Permission::query()->get();
        }

        return Permission::query()
            ->whereNotIn('name', $this->platformPermissions())
            ->get();
    }

    /**
     * Strip any permission the current user is not allowed to assign.
     *
     * Used before syncPermissions() so a crafted request cannot grant a client
     * access to the platform-only permissions.
     *
     * @param  array<int, string>  $names
     * @return array<int, string>
     */
    public function filterAssignable(array $names): array
    {
        $allowed = $this->assignablePermissions()->pluck('name')->all();

        return array_values(array_intersect($names, $allowed));
    }

    public function isPlatformOnlyPermission(string $name): bool
    {
        return in_array($name, $this->platformPermissions(), true);
    }

    protected function userIsPlatformStaff(): bool
    {
        return ClientContext::canAccessAllClients();
    }

    /**
     * @return array<int, string>
     */
    protected function platformPermissions(): array
    {
        return class_exists(\Database\Seeders\RoleBundlesSeeder::class)
            ? \Database\Seeders\RoleBundlesSeeder::PLATFORM_PERMISSIONS
            : [
                'clients.view', 'clients.create', 'clients.edit', 'clients.suspend',
                'reports.cross_client', 'access_all_clients', 'impersonate_client',
            ];
    }
}