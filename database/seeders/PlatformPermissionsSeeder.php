<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeds the platform-level permissions described in plan.md §4.4.
 *
 * These are the permissions that let platform staff act across clients. They
 * are never exposed in the per-client role builder, so a client can never
 * grant itself cross-client access.
 *
 * The System Admin and Client Admin role bundles themselves are created in
 * Phase 3; this seeder only establishes the permissions and grants the
 * Developer the cross-client access it needs to keep working.
 */
class PlatformPermissionsSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    protected array $platformPermissions = [
        'clients.view',
        'clients.create',
        'clients.edit',
        'clients.suspend',
        'reports.cross_client',
        'access_all_clients',
        'impersonate_client',
    ];

    public function run(): void
    {
        foreach ($this->platformPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $developer = Role::where('name', 'Developer')->whereNull('client_id')->first();

        if ($developer) {
            // Additive: keeps the existing 150 permissions intact.
            $developer->givePermissionTo($this->platformPermissions);
        }
    }
}
