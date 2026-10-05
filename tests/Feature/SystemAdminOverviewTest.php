<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use App\Services\ClientContext;
use App\Services\ClientOverview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

afterEach(function () {
    ClientContext::flush();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function seedPlatformPermissions(): void
{
    foreach (\Database\Seeders\RoleBundlesSeeder::PLATFORM_PERMISSIONS as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
}

/** A System Admin: platform permissions only, no client HR permissions. */
function systemAdmin(): User
{
    seedPlatformPermissions();

    $role = Role::create(['name' => 'System Admin', 'guard_name' => 'web', 'client_id' => null]);
    $role->givePermissionTo([
        'clients.view', 'clients.create', 'clients.edit', 'clients.suspend', 'reports.cross_client',
    ]);

    $user = User::factory()->create(['client_id' => null]);
    ClientContext::override(null);
    $user->assignRole($role);
    ClientContext::override(null);

    return $user;
}

function seedTwoClients(): void
{
    foreach ([['Acme', 'acme'], ['Globex', 'globex']] as [$name, $slug]) {
        $client = Client::create(['name' => $name, 'slug' => $slug, 'plan' => 'professional']);

        User::factory()->count(2)->create(['client_id' => $client->id]);
    }
}

it('lists every client with its counts for a system admin', function () {
    seedTwoClients();

    $admin = systemAdmin();
    $this->actingAs($admin);
    ClientContext::override(null);

    $rows = app(ClientOverview::class)->clientSummaries();

    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('client.name')->all())->toBe(['Acme', 'Globex'])
        ->and($rows->pluck('users')->all())->toBe([2, 2]);
});

it('reports cross client totals to a system admin', function () {
    seedTwoClients();

    $this->actingAs(systemAdmin());
    ClientContext::override(null);

    $totals = app(ClientOverview::class)->totals();

    // 4 client users plus the platform admin created by the helper.
    expect($totals['clients'])->toBe(2)
        ->and($totals['users'])->toBe(5);
});

it('renders the clients screen for a system admin', function () {
    seedTwoClients();

    $this->actingAs(systemAdmin());
    ClientContext::override(null);

    $this->get(route('admin.clients.index'))->assertOk()->assertSee('Acme');
});

it('keeps the clients screen away from client users', function () {
    $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);

    Permission::firstOrCreate(['name' => 'clients.view', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'Client Admin', 'guard_name' => 'web', 'client_id' => $client->id]);
    $role->givePermissionTo('clients.view');

    $user = User::factory()->create(['client_id' => $client->id]);
    ClientContext::override($client->id);
    $user->assignRole($role);

    $this->actingAs($user);
    ClientContext::override($client->id);

    // The catalogue hides it, and the route refuses it even if granted directly.
    $this->get(route('admin.clients.index'))->assertForbidden();
});

it('blocks a system admin from client HR screens', function () {
    $admin = systemAdmin();
    $this->actingAs($admin);
    ClientContext::override(null);

    // None of the client HR permissions are granted to System Admin, so the
    // route guards refuse before any data is read.
    $this->get(route('user.index'))->assertForbidden();
    $this->get(route('role.index'))->assertForbidden();
    $this->get(route('employee.index'))->assertForbidden();
});

it('shows a system admin no client employee rows at all', function () {
    $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    $structure = sysAdminOrgStructure($client);
    sysAdminEmployeeFor($client, $structure, 'SN-ACME-1');

    $this->actingAs(systemAdmin());
    ClientContext::override(null);

    // Fail closed: a platform user with no client cannot read client rows.
    expect(Employee::query()->count())->toBe(0);
});

it('sends platform staff from the dashboard to the clients screen', function () {
    seedTwoClients();

    $admin = systemAdmin();
    $admin->forceFill(['email_verified_at' => now()])->save();

    $this->actingAs($admin);
    ClientContext::override(null);

    $this->get(route('dashboard'))->assertRedirect(route('admin.clients.index'));
});

it('leaves a client user on the normal dashboard', function () {
    $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);

    $user = User::factory()->create([
        'client_id' => $client->id,
        'email_verified_at' => now(),
        'password' => Hash::make('Password123!'),
    ]);

    $this->actingAs($user);
    ClientContext::override($client->id);

    $this->get(route('dashboard'))->assertOk();
});

/** Build the mandatory org structure for a client. */
function sysAdminOrgStructure(Client $client): array
{
    $location = \App\Models\Location::create([
        'client_id' => $client->id, 'name' => $client->name.' HQ',
        'code' => 'LOC-'.$client->slug, 'city' => 'London', 'country' => 'UK',
    ]);
    $branch = \App\Models\Branch::create([
        'client_id' => $client->id, 'name' => $client->name.' Branch',
        'code' => 'BR-'.$client->slug, 'location_id' => $location->id,
    ]);
    $department = \App\Models\Department::create([
        'client_id' => $client->id, 'name' => $client->name.' Dept',
        'code' => 'DEP-'.$client->slug, 'branch_id' => $branch->id,
    ]);
    $designation = \App\Models\Designation::create([
        'client_id' => $client->id, 'name' => $client->name.' Des', 'code' => 'DES-'.$client->slug,
    ]);
    $contractType = \App\Models\ContractType::create([
        'client_id' => $client->id, 'name' => $client->name.' CT', 'code' => 'CON-'.$client->slug,
    ]);

    return compact('location', 'branch', 'department', 'designation', 'contractType');
}

function sysAdminEmployeeFor(Client $client, array $structure, string $staffNumber): Employee
{
    $user = User::factory()->create(['client_id' => $client->id]);

    return Employee::create([
        'user_id' => $user->id, 'client_id' => $client->id, 'staff_number' => $staffNumber,
        'location_id' => $structure['location']->id, 'branch_id' => $structure['branch']->id,
        'department_id' => $structure['department']->id, 'designation_id' => $structure['designation']->id,
        'contract_type_id' => $structure['contractType']->id,
        'date_of_join' => now(), 'basic_salary' => 50000, 'salary_frequency' => 'monthly',
    ]);
}