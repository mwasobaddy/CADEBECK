<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the hierarchy and client-context permissions described in plan.md
 * §4.4 and §4.5, and the System Admin / Client Admin bundles from §4.6.
 *
 * Authorization in this application is permission-based; roles are only named
 * bundles. These bundles therefore exist purely to grant a sensible default set
 * to new users, and a Client Admin holding `manage_client_roles` can build
 * different bundles from the same catalogue.
 */
class RoleBundlesSeeder extends Seeder
{
    /**
     * Platform capabilities, never offered in the per-client role builder.
     *
     * @var array<int, string>
     */
    public const PLATFORM_PERMISSIONS = [
        'clients.view',
        'clients.create',
        'clients.edit',
        'clients.suspend',
        'reports.cross_client',
        'access_all_clients',
        'impersonate_client',
    ];

    /**
     * Capabilities scoped to a client's own data.
     *
     * @var array<int, string>
     */
    public const CLIENT_PERMISSIONS = [
        'manage_client_settings',
        'manage_client_roles',
        'manage_client_users',
        'view_client_reports',
    ];

    /**
     * Row-level visibility, replacing the hardcoded N-1/N-2 role checks.
     *
     * `view_all_*`        - every record in the client (previously Developer/Executive)
     * `view_team_*`       - two reporting levels (previously Manager N-1)
     * `view_direct_reports_*` - direct reports only (previously Manager N-2)
     *
     * @var array<int, string>
     */
    public const HIERARCHY_PERMISSIONS = [
        'view_all_leave_requests',
        'view_team_leave_requests',
        'view_direct_reports_leave_requests',
        'view_all_wellbeing_reports',
        'view_team_wellbeing_reports',
        'view_direct_reports_wellbeing_reports',
    ];

    public function run(): void
    {
        $catalogue = array_merge(
            self::PLATFORM_PERMISSIONS,
            self::CLIENT_PERMISSIONS,
            self::HIERARCHY_PERMISSIONS
        );

        foreach ($catalogue as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->grantHierarchyDefaults();
        $this->createPlatformBundle('System Admin', [
            'clients.view',
            'clients.create',
            'clients.edit',
            'clients.suspend',
            'reports.cross_client',
        ]);

        $this->createClientAdminBundle();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Create the per-client administrator bundle.
     *
     * Defined as "every permission except the platform-only ones", so it stays
     * correct as the catalogue grows and can never hand a client cross-client
     * access. Access is still confined to the client by the global scope.
     */
    protected function createClientAdminBundle(): void
    {
        $permissions = Permission::whereNotIn('name', self::PLATFORM_PERMISSIONS)
            ->pluck('name')
            ->all();

        if ($permissions === []) {
            return;
        }

        $role = Role::firstOrCreate(
            ['name' => 'Client Admin', 'guard_name' => 'web', 'client_id' => null]
        );

        $role->syncPermissions($permissions);
    }

    /**
     * Give the existing default bundles the permissions that replace their
     * former hardcoded role-name checks.
     */
    protected function grantHierarchyDefaults(): void
    {
        $map = [
            'Executive' => ['view_all_leave_requests', 'view_all_wellbeing_reports'],
            'Manager N-1' => ['view_team_leave_requests', 'view_team_wellbeing_reports'],
            'Manager N-2' => ['view_direct_reports_leave_requests', 'view_direct_reports_wellbeing_reports'],
        ];

        foreach ($map as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->whereNull('client_id')->first();

            $role?->givePermissionTo($permissions);
        }
    }

    /**
     * Create a platform-only permission bundle.
     *
     * Deliberately excludes every *_employee / process_payroll style permission:
     * that absence is what stops a System Admin editing client HR data, with no
     * special-case code required.
     *
     * @param  array<int, string>  $permissions
     */
    protected function createPlatformBundle(string $name, array $permissions): void
    {
        $role = Role::firstOrCreate(
            ['name' => $name, 'guard_name' => 'web', 'client_id' => null]
        );

        $role->syncPermissions($permissions);
    }
}
