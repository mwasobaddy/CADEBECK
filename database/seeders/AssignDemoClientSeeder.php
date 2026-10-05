<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Services\ClientContext;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;

class AssignDemoClientSeeder extends Seeder
{
    /**
     * Client-owned tables that receive the demo client_id.
     *
     * `users` is handled separately below because platform staff keep a null
     * client_id. `languages` and the framework/Spatie tables are intentionally
     * excluded (see plan.md §4.2).
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
        'launch_subscribers',
    ];

    /**
     * Roles whose users are platform staff and therefore have no client.
     *
     * @var array<int, string>
     */
    protected array $platformRoles = ['Developer', 'System Admin'];

    /**
     * Assign every piece of seeded demo data to the demo client.
     *
     * Runs last in DatabaseSeeder, after all data has been created.
     */
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

        $this->assignUsers($client);
    }

    /**
     * Assign the demo client to client users, leaving platform staff unassigned.
     */
    protected function assignUsers(Client $client): void
    {
        $existingPlatformRoles = Role::whereIn('name', $this->platformRoles)->pluck('name');

        $query = User::whereNull('client_id');

        if ($existingPlatformRoles->isNotEmpty()) {
            $query->whereDoesntHave('roles', function ($roles) use ($existingPlatformRoles) {
                $roles->whereIn('name', $existingPlatformRoles);
            });
        }

        $query->update(['client_id' => $client->id]);

        $this->syncPermissionTeams();
    }

    /**
     * Re-point role and permission assignments at the correct team.
     *
     * Spatie's teams feature stores the team id on the pivots, but at the time
     * the role seeders run there is no client context yet, so every assignment
     * lands on the reserved platform id. Now that client_id is populated we can
     * correct them.
     */
    protected function syncPermissionTeams(): void
    {
        $client = Client::where('slug', DemoClientSeeder::SLUG)->first();

        $clientUserIds = User::where('client_id', $client->id)->pluck('id');
        $platformUserIds = User::whereNull('client_id')->pluck('id');

        foreach (['model_has_roles', 'model_has_permissions'] as $pivot) {
            if ($clientUserIds->isNotEmpty()) {
                DB::table($pivot)
                    ->where('model_type', User::class)
                    ->whereIn('model_id', $clientUserIds)
                    ->update(['client_id' => $client->id]);
            }

            if ($platformUserIds->isNotEmpty()) {
                DB::table($pivot)
                    ->where('model_type', User::class)
                    ->whereIn('model_id', $platformUserIds)
                    ->update(['client_id' => ClientContext::PLATFORM_TEAM_ID]);
            }
        }

        // The permission cache is keyed by team, so it must be rebuilt.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
