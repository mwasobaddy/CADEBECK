<?php

use App\Models\Branch;
use App\Models\Client;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Payroll;
use App\Models\User;
use App\Services\ClientContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(fn () => ClientContext::flush());

function makeClient(string $slug): Client
{
    return Client::create(['name' => ucfirst($slug), 'slug' => $slug]);
}

/**
 * Build the mandatory org structure for a client. Note each client gets its
 * own location/branch/department/designation/contract type rows.
 */
function makeOrgStructureFor(Client $client): array
{
    $location = Location::create([
        'client_id' => $client->id,
        'name' => $client->name.' Location',
        'code' => 'LOC-'.$client->slug,
        'city' => 'London',
        'country' => 'UK',
    ]);

    $branch = Branch::create([
        'client_id' => $client->id,
        'name' => $client->name.' Branch',
        'code' => 'BR-'.$client->slug,
        'location_id' => $location->id,
    ]);

    $department = Department::create([
        'client_id' => $client->id,
        'name' => $client->name.' Department',
        'code' => 'DEP-'.$client->slug,
        'branch_id' => $branch->id,
    ]);

    $designation = Designation::create([
        'client_id' => $client->id,
        'name' => $client->name.' Designation',
        'code' => 'DES-'.$client->slug,
    ]);

    $contractType = ContractType::create([
        'client_id' => $client->id,
        'name' => $client->name.' Contract',
        'code' => 'CON-'.$client->slug,
    ]);

    return compact('location', 'branch', 'department', 'designation', 'contractType');
}

function makeEmployeeFor(Client $client, string $staffNumber): Employee
{
    $structure = makeOrgStructureFor($client);

    $user = User::factory()->create(['client_id' => $client->id]);

    return Employee::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'staff_number' => $staffNumber,
        'location_id' => $structure['location']->id,
        'branch_id' => $structure['branch']->id,
        'department_id' => $structure['department']->id,
        'designation_id' => $structure['designation']->id,
        'contract_type_id' => $structure['contractType']->id,
        'date_of_join' => now(),
        'basic_salary' => 50000,
        'salary_frequency' => 'monthly',
    ]);
}

function makeDeveloper(): User
{
    Permission::firstOrCreate(['name' => 'access_all_clients', 'guard_name' => 'web']);

    $role = Role::firstOrCreate(['name' => 'Developer', 'guard_name' => 'web']);
    $role->givePermissionTo('access_all_clients');

    $developer = User::factory()->create(['client_id' => null]);
    $developer->assignRole($role);

    return $developer;
}

it('hides one client data from another client user', function () {
    $acme = makeClient('acme');
    $globex = makeClient('globex');

    $acmeEmployee = makeEmployeeFor($acme, 'SN-ACME-1');
    $globexEmployee = makeEmployeeFor($globex, 'SN-GLOBEX-1');

    $this->actingAs($acmeEmployee->user);

    $visibleIds = Employee::query()->pluck('id')->all();

    expect($visibleIds)
        ->toContain($acmeEmployee->id)
        ->not->toContain($globexEmployee->id);
});

it('hides another client payrolls and payslips', function () {
    $acme = makeClient('acme');
    $globex = makeClient('globex');

    $acmeEmployee = makeEmployeeFor($acme, 'SN-ACME-1');
    $globexEmployee = makeEmployeeFor($globex, 'SN-GLOBEX-1');

    Payroll::create([
        'client_id' => $acme->id,
        'employee_id' => $acmeEmployee->id,
        'payroll_period' => '2026-01',
        'pay_date' => now(),
        'basic_salary' => 50000,
        'gross_pay' => 50000,
        'net_pay' => 40000,
        'status' => 'processed',
    ]);

    Payroll::create([
        'client_id' => $globex->id,
        'employee_id' => $globexEmployee->id,
        'payroll_period' => '2026-01',
        'pay_date' => now(),
        'basic_salary' => 60000,
        'gross_pay' => 60000,
        'net_pay' => 48000,
        'status' => 'processed',
    ]);

    $this->actingAs($acmeEmployee->user);

    expect(Payroll::query()->pluck('employee_id')->all())
        ->toBe([$acmeEmployee->id]);
});

it('returns nothing for an authenticated user with no client and no cross-client permission', function () {
    $acme = makeClient('acme');
    makeEmployeeFor($acme, 'SN-ACME-1');

    // Platform user with no client and no access_all_clients (System Admin shape).
    $this->actingAs(User::factory()->create(['client_id' => null]));

    expect(Employee::query()->count())->toBe(0);
});

it('lets a user with access_all_clients see every client', function () {
    $acme = makeClient('acme');
    $globex = makeClient('globex');

    $acmeEmployee = makeEmployeeFor($acme, 'SN-ACME-1');
    $globexEmployee = makeEmployeeFor($globex, 'SN-GLOBEX-1');

    $this->actingAs(makeDeveloper());

    $visibleIds = Employee::query()->pluck('id')->all();

    expect($visibleIds)
        ->toContain($acmeEmployee->id)
        ->toContain($globexEmployee->id);
});

it('auto assigns client_id from the authenticated user on create', function () {
    $acme = makeClient('acme');
    $structure = makeOrgStructureFor($acme);

    $user = User::factory()->create(['client_id' => $acme->id]);
    $this->actingAs($user);

    $employee = Employee::create([
        'user_id' => $user->id,
        'staff_number' => 'SN-AUTO-1',
        'location_id' => $structure['location']->id,
        'branch_id' => $structure['branch']->id,
        'department_id' => $structure['department']->id,
        'designation_id' => $structure['designation']->id,
        'contract_type_id' => $structure['contractType']->id,
        'date_of_join' => now(),
        'basic_salary' => 50000,
        'salary_frequency' => 'monthly',
    ]);

    expect($employee->client_id)->toBe($acme->id);
});

it('exposes the client relationship on scoped models', function () {
    $acme = makeClient('acme');

    $employee = makeEmployeeFor($acme, 'SN-ACME-1');

    expect($employee->client)->not->toBeNull()
        ->and($employee->client->slug)->toBe('acme');
});

it('still supports system wide queries for cross-client reporting', function () {
    $acme = makeClient('acme');
    $globex = makeClient('globex');

    $acmeEmployee = makeEmployeeFor($acme, 'SN-ACME-1');
    $globexEmployee = makeEmployeeFor($globex, 'SN-GLOBEX-1');

    $this->actingAs($acmeEmployee->user);

    expect(Employee::acrossClients()->count())->toBe(2)
        ->and(Employee::forClient($globex->id)->pluck('id')->all())
        ->toBe([$globexEmployee->id]);
});
