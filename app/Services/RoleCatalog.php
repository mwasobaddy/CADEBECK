<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
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