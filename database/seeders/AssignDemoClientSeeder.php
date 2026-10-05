<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use App\Services\ClientContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Safety net that makes sure nothing seeded is left without a client.
 *
 * DemoClientSeeder already runs as the current client, so the BelongsToClient
 * creating hook assigns client_id as rows are inserted and client_id is NOT
 * NULL on the client-owned tables. That means this seeder normally has nothing
 * to do; it exists so a seeder that writes outside the client context cannot
 * silently leave rows unassigned.
 */
class AssignDemoClientSeeder extends Seeder
{
    /**
     * Client-owned tables that must always carry a client.
     *
     * `users` is handled separately because platform staff keep a null
     * client_id. `launch_subscribers` is excluded on purpose: those are
     * pre-launch signups captured from anonymous visitors, so they are
     * platform-level rather than tenant data. `languages` and the
     * framework/Spatie tables are excluded too (see plan.md §4.2).
     *
     * @var array<int, string>
     */
    protected array $tables = [
        'job_adverts',
        'locations',
        'branches',
        'departments',
        'designations',
        'contract_types',
        'employees',
        'attendances',
        'leave_requests',
        'well_being_responses',
        'payrolls',
        'payroll_allowances',
        'payroll_deductions',
        'employee_loans',
        'payslips',
        'loan_repayments',
        'notifications',
        'applications',
        'audits',
    ];

    /**
     * Roles whose holders are platform staff and therefore have no client.
     *
     * @var array<int, string>
     */
    protected array $platformRoles = ['Developer', 'System Admin'];

    public function run(): void
    {
        $client = Client::where('slug', DemoClientSeeder::SLUG)->first();

        if (! $client) {
            $this->command?->error('Demo client not found. Run DemoClientSeeder first.');

            return;
        }

        foreach ($this->tables as $table) {
            DB::table($table)
                ->whereNull('client_id')
                ->update(['client_id' => $client->id]);
        }

        $platformUserIds = $this->platformUserIds();

        $this->assignUsers($client, $platformUserIds);
        $this->syncPermissionTeams($client, $platformUserIds);
    }

    /**
     * Ids of users holding a platform role.
     *
     * Read straight from the pivot rather than through the roles relation: with
     * Spatie's teams enabled that relation is filtered to the current team, so a
     * platform assignment stored on the reserved platform team would be invisible
     * and its holder would be mistaken for an ordinary unassigned user.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    protected function platformUserIds(): \Illuminate\Support\Collection
    {
        $roleIds = Role::whereIn('name', $this->platformRoles)->pluck('id');

        if ($roleIds->isEmpty()) {
            return collect();
        }

        return DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->whereIn('role_id', $roleIds)
            ->pluck('model_id')
            ->unique();
    }

    /**
     * Assign the demo client to unassigned users, leaving platform staff alone.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $platformUserIds
     */
    protected function assignUsers(Client $client, \Illuminate\Support\Collection $platformUserIds): void
    {
        $query = User::acrossClients()->whereNull('client_id');

        if ($platformUserIds->isNotEmpty()) {
            $query->whereNotIn('id', $platformUserIds);
        }

        $query->update(['client_id' => $client->id]);
    }

    /**
     * Make sure every role/permission assignment sits on the right team.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $platformUserIds
     */
    protected function syncPermissionTeams(Client $client, \Illuminate\Support\Collection $platformUserIds): void
    {
        $platformIds = $platformUserIds->all() ?: [0];

        foreach (['model_has_roles', 'model_has_permissions'] as $pivot) {
            DB::table($pivot)
                ->where('model_type', User::class)
                ->whereNotIn('model_id', $platformIds)
                ->update(['client_id' => $client->id]);

            DB::table($pivot)
                ->where('model_type', User::class)
                ->whereIn('model_id', $platformIds)
                ->update(['client_id' => ClientContext::PLATFORM_TEAM_ID]);
        }

        // The permission cache is keyed by team, so it must be rebuilt.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}