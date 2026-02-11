<?php

use App\Models\Branch;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use App\Models\WellBeingResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('allows developers to view every wellbeing response', function () {
    $developer = createUserWithRole('Developer');
    $developerEmployee = createEmployeeFor($developer);

    $managerUser = User::factory()->create();
    $managerEmployee = createEmployeeFor($managerUser);

    $teamUser = User::factory()->create();
    $teamEmployee = createEmployeeFor($teamUser, $managerEmployee);

    createWellBeingResponseFor($developerEmployee);
    createWellBeingResponseFor($managerEmployee);
    createWellBeingResponseFor($teamEmployee);

    $visibleEmployeeIds = WellBeingResponse::query()
        ->viewableBy($developer)
        ->pluck('employee_id')
        ->toArray();

    expect($visibleEmployeeIds)
        ->toContain($developerEmployee->id)
        ->toContain($managerEmployee->id)
        ->toContain($teamEmployee->id);
});

it('limits manager n-2 visibility to direct subordinates', function () {
    $managerN2 = createUserWithRole('Manager N-2');
    $managerEmployee = createEmployeeFor($managerN2);

    $directReport = createEmployeeFor(User::factory()->create(), $managerEmployee);
    $outsideEmployee = createEmployeeFor(User::factory()->create());

    createWellBeingResponseFor($directReport);
    createWellBeingResponseFor($outsideEmployee);

    $visibleEmployeeIds = WellBeingResponse::query()
        ->viewableBy($managerN2)
        ->pluck('employee_id')
        ->toArray();

    expect($visibleEmployeeIds)
        ->toContain($directReport->id)
        ->not->toContain($outsideEmployee->id);
});

it('allows manager n-1 to view manager n-2 teams', function () {
    $managerN1 = createUserWithRole('Manager N-1');
    $managerN1Employee = createEmployeeFor($managerN1);

    $managerN2 = createUserWithRole('Manager N-2');
    $managerN2Employee = createEmployeeFor($managerN2, $managerN1Employee);
    $teamMember = createEmployeeFor(User::factory()->create(), $managerN2Employee);

    $externalManager = createUserWithRole('Manager N-2');
    $externalManagerEmployee = createEmployeeFor($externalManager);
    $externalTeamMember = createEmployeeFor(User::factory()->create(), $externalManagerEmployee);

    createWellBeingResponseFor($managerN2Employee);
    createWellBeingResponseFor($teamMember);
    createWellBeingResponseFor($externalTeamMember);

    $visibleEmployeeIds = WellBeingResponse::query()
        ->viewableBy($managerN1)
        ->pluck('employee_id')
        ->toArray();

    expect($visibleEmployeeIds)
        ->toContain($managerN2Employee->id)
        ->toContain($teamMember->id)
        ->not->toContain($externalTeamMember->id);
});

it('exposes the department relationship through employees', function () {
    $user = createUserWithRole('Developer');
    $employee = createEmployeeFor($user);

    $response = createWellBeingResponseFor($employee);

    expect($response->department)
        ->not->toBeNull()
        ->id->toBe($employee->department_id);
});

function createUserWithRole(string $roleName): User
{
    $permission = Permission::firstOrCreate(['name' => 'access_wellbeing_reports', 'guard_name' => 'web']);

    $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function ensureOrgStructure(): array
{
    $location = Location::firstOrCreate(
        ['code' => 'LOC-TEST'],
        ['name' => 'Test Location', 'address' => '1 Way', 'city' => 'Nairobi', 'country' => 'Kenya']
    );

    $branch = Branch::firstOrCreate(
        ['code' => 'BR-TEST'],
        ['name' => 'HQ', 'location_id' => $location->id]
    );

    $department = Department::firstOrCreate(
        ['code' => 'DEP-TEST'],
        ['name' => 'People Operations', 'branch_id' => $branch->id]
    );

    $designation = Designation::firstOrCreate(
        ['code' => 'DES-TEST'],
        ['name' => 'Manager']
    );

    $contractType = ContractType::firstOrCreate(
        ['code' => 'CON-TEST'],
        ['name' => 'Full Time']
    );

    return compact('location', 'branch', 'department', 'designation', 'contractType');
}

function createEmployeeFor(User $user, ?Employee $supervisor = null): Employee
{
    static $staffCounter = 1000;
    $structure = ensureOrgStructure();

    return Employee::create([
        'user_id' => $user->id,
        'staff_number' => 'SN'.$staffCounter++,
        'location_id' => $structure['location']->id,
        'branch_id' => $structure['branch']->id,
        'department_id' => $structure['department']->id,
        'designation_id' => $structure['designation']->id,
        'contract_type_id' => $structure['contractType']->id,
        'date_of_join' => now(),
        'supervisor_id' => $supervisor?->id,
        'basic_salary' => 100000,
    ]);
}

function createWellBeingResponseFor(Employee $employee, array $overrides = []): WellBeingResponse
{
    $defaults = [
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
    ];

    return WellBeingResponse::create(array_merge($defaults, $overrides));
}
