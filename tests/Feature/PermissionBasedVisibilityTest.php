<?php

use App\Models\Branch;
use App\Models\Client;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use App\Models\WellBeingResponse;
use App\Services\ClientContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

afterEach(function () {
    ClientContext::flush();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function wellbeingClient(): Client
{
    $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    ClientContext::override($client->id);

    return $client;
}

function wellbeingEmployee(?User $user, ?Employee $supervisor = null): Employee
{
    static $counter = 5000;

    $location = Location::firstOrCreate(['code' => 'LOC-WB'], ['name' => 'HQ', 'city' => 'London', 'country' => 'UK']);
    $branch = Branch::firstOrCreate(['code' => 'BR-WB'], ['name' => 'Main', 'location_id' => $location->id]);
    $department = Department::firstOrCreate(['code' => 'DEP-WB'], ['name' => 'People', 'branch_id' => $branch->id]);
    $designation = Designation::firstOrCreate(['code' => 'DES-WB'], ['name' => 'Staff']);
    $contractType = ContractType::firstOrCreate(['code' => 'CON-WB'], ['name' => 'Full Time']);

    $user ??= User::factory()->create();

    return Employee::create([
        'user_id' => $user->id,
        'staff_number' => 'SN-WB-'.($counter++),
        'location_id' => $location->id,
        'branch_id' => $branch->id,
        'department_id' => $department->id,
        'designation_id' => $designation->id,
        'contract_type_id' => $contractType->id,
        'date_of_join' => now(),
        'supervisor_id' => $supervisor?->id,
        'basic_salary' => 40000,
        'salary_frequency' => 'monthly',
    ]);
}

function wellbeingResponse(Employee $employee): WellBeingResponse
{
    return WellBeingResponse::create([
        'employee_id' => $employee->id,
        'user_id' => $employee->user_id,
        'assessment_type' => 'weekly',
        'period_start_date' => now()->startOfWeek(),
        'period_end_date' => now()->endOfWeek(),
        'frequency' => 'weekly',
        'stress_level' => 5,
        'work_life_balance' => 6,
        'job_satisfaction' => 7,
        'support_level' => 8,
    ]);
}

/** Give a user a bespoke client role carrying the given permissions. */
function customRoleUser(array $permissions, ?Client $client = null): User
{
    $client ??= Client::current();

    $role = Role::create([
        'name' => 'Custom '.implode('-', $permissions),
        'guard_name' => 'web',
        'client_id' => $client?->id,
    ]);

    foreach ($permissions as $name) {
        $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }

    $user = User::factory()->create(['client_id' => $client?->id]);
    $user->assignRole($role);

    return $user;
}

it('grants full visibility to a custom role holding view_all', function () {
    wellbeingClient();

    $viewer = customRoleUser(['view_all_wellbeing_reports']);

    $response = wellbeingResponse(wellbeingEmployee(User::factory()->create()));

    expect(WellBeingResponse::query()->viewableBy($viewer)->pluck('id')->all())
        ->toBe([$response->id]);
});

it('grants direct-report visibility to a custom role holding view_direct_reports', function () {
    wellbeingClient();

    $manager = customRoleUser(['view_direct_reports_wellbeing_reports']);
    $managerEmployee = wellbeingEmployee($manager);

    // A direct report of the manager.
    $report = wellbeingResponse(wellbeingEmployee(User::factory()->create(), $managerEmployee));

    // Someone outside the manager's line of management.
    $unrelated = wellbeingEmployee(User::factory()->create());
    wellbeingResponse($unrelated);

    $visibleEmployeeIds = WellBeingResponse::query()
        ->viewableBy($manager)
        ->pluck('employee_id')
        ->all();

    expect($visibleEmployeeIds)->toBe([$report->employee_id]);
});

it('shows nothing to a user holding no visibility permission', function () {
    wellbeingClient();

    $user = User::factory()->create();

    wellbeingResponse(wellbeingEmployee(User::factory()->create()));

    expect(WellBeingResponse::query()->viewableBy($user)->count())->toBe(0);
});

it('does not throw when the hierarchy permissions are missing entirely', function () {
    wellbeingClient();

    $user = User::factory()->create();

    // No RoleBundlesSeeder has run, so view_* permissions do not exist.
    expect(fn () => WellBeingResponse::query()->viewableBy($user)->count())
        ->not->toThrow(PermissionDoesNotExist::class);
});