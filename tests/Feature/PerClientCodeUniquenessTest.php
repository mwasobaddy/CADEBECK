<?php

use App\Models\Branch;
use App\Models\Client;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use App\Services\ClientContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(fn () => ClientContext::flush());

function orgClient(string $slug): Client
{
    return Client::create(['name' => ucfirst($slug), 'slug' => $slug]);
}

/** Create the same codes for a client, exactly as the seeders do. */
function seedOrgCodes(Client $client): array
{
    $location = Location::create([
        'client_id' => $client->id, 'name' => 'Head Office',
        'code' => 'HQ', 'city' => 'London', 'country' => 'UK',
    ]);
    $branch = Branch::create([
        'client_id' => $client->id, 'name' => 'Main Branch',
        'code' => 'MAIN', 'location_id' => $location->id,
    ]);
    $department = Department::create([
        'client_id' => $client->id, 'name' => 'People',
        'code' => 'PEOPLE', 'branch_id' => $branch->id,
    ]);
    $designation = Designation::create([
        'client_id' => $client->id, 'name' => 'Analyst', 'code' => 'ANALYST',
    ]);
    $contractType = ContractType::create([
        'client_id' => $client->id, 'name' => 'Full Time', 'code' => 'FT',
    ]);

    return compact('location', 'branch', 'department', 'designation', 'contractType');
}

function employeeWith(Client $client, array $org, string $staffNumber): Employee
{
    $user = User::factory()->create(['client_id' => $client->id]);

    return Employee::create([
        'user_id' => $user->id, 'client_id' => $client->id, 'staff_number' => $staffNumber,
        'location_id' => $org['location']->id, 'branch_id' => $org['branch']->id,
        'department_id' => $org['department']->id, 'designation_id' => $org['designation']->id,
        'contract_type_id' => $org['contractType']->id,
        'date_of_join' => now(), 'basic_salary' => 50000, 'salary_frequency' => 'monthly',
    ]);
}

it('lets two clients use the same organisation codes', function () {
    $acme = orgClient('acme');
    $globex = orgClient('globex');

    seedOrgCodes($acme);
    $globexOrg = seedOrgCodes($globex);

    expect($globexOrg['location']->code)->toBe('HQ')
        ->and($globexOrg['branch']->code)->toBe('MAIN')
        ->and($globexOrg['department']->code)->toBe('PEOPLE');

    expect(Location::acrossClients()->where('code', 'HQ')->count())->toBe(2);
});

it('lets two clients reuse the same staff number', function () {
    $acme = orgClient('acme');
    $globex = orgClient('globex');

    employeeWith($acme, seedOrgCodes($acme), 'EMP-001');
    employeeWith($globex, seedOrgCodes($globex), 'EMP-001');

    expect(Employee::acrossClients()->where('staff_number', 'EMP-001')->count())->toBe(2);
});

it('still rejects a duplicate staff number inside one client', function () {
    $acme = orgClient('acme');
    $org = seedOrgCodes($acme);

    employeeWith($acme, $org, 'EMP-001');

    expect(fn () => employeeWith($acme, $org, 'EMP-001'))
        ->toThrow(Illuminate\Database\QueryException::class);
});

it('still rejects a duplicate code inside one client', function () {
    $acme = orgClient('acme');
    seedOrgCodes($acme);

    expect(fn () => Location::create([
        'client_id' => $acme->id, 'name' => 'Second HQ',
        'code' => 'HQ', 'city' => 'Leeds', 'country' => 'UK',
    ]))->toThrow(Illuminate\Database\QueryException::class);
});