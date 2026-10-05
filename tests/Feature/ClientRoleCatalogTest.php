<?php

use App\Models\Client;
use App\Models\User;
use App\Services\ClientContext;
use App\Services\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

afterEach(function () {
    ClientContext::flush();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function clientAdmin(?Client $client = null): User
{
    $client ??= Client::create(['name' => 'Acme', 'slug' => 'acme']);

    $user = User::factory()->create(['client_id' => $client->id]);
    Permission::firstOrCreate(['name' => 'manage_client_roles', 'guard_name' => 'web']);

    $role = Role::create(['name' => 'Client Admin', 'guard_name' => 'web', 'client_id' => $client->id]);
    $role->givePermissionTo('manage_client_roles');

    // The client context must be active before assigning the role: Spatie stores
    // the team id on the pivot, so assigning outside the client context would
    // file the assignment under the platform team.
    ClientContext::override($client->id);
    $user->assignRole($role);

    return $user;
}

function platformDeveloper(): User
{
    Permission::firstOrCreate(['name' => 'access_all_clients', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'Developer', 'guard_name' => 'web', 'client_id' => null]);
    $role->givePermissionTo('access_all_clients');

    $user = User::factory()->create(['client_id' => null]);
    $user->assignRole($role);

    ClientContext::override(null);

    return $user;
}

it('shows a client admin the shared roles and their own client roles only', function () {
    $acme = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    $globex = Client::create(['name' => 'Globex', 'slug' => 'globex']);

    Role::create(['name' => 'Employee', 'guard_name' => 'web', 'client_id' => null]);
    Role::create(['name' => 'Acme Payroll', 'guard_name' => 'web', 'client_id' => $acme->id]);
    Role::create(['name' => 'Globex Payroll', 'guard_name' => 'web', 'client_id' => $globex->id]);

    clientAdmin($acme);

    $names = app(RoleCatalog::class)->visibleRoles()->pluck('name')->sort()->values()->all();

    // 'Client Admin' is the helper's own Acme role; 'Globex Payroll' must not appear.
    expect($names)->toBe(['Acme Payroll', 'Client Admin', 'Employee']);
});

it('refuses to let a client admin edit or delete another client role', function () {
    $acme = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    $globex = Client::create(['name' => 'Globex', 'slug' => 'globex']);

    $globexRole = Role::create(['name' => 'Globex Payroll', 'guard_name' => 'web', 'client_id' => $globex->id]);

    clientAdmin($acme);

    expect(fn () => app(RoleCatalog::class)->findManageableOrFail($globexRole->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(DB::table('roles')->where('id', $globexRole->id)->exists())->toBeTrue();
});

it('refuses to let a client admin modify a shared role', function () {
    $acme = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    $shared = Role::create(['name' => 'Employee', 'guard_name' => 'web', 'client_id' => null]);

    clientAdmin($acme);

    expect(fn () => app(RoleCatalog::class)->findManageableOrFail($shared->id))
        ->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);

    expect(DB::table('roles')->where('id', $shared->id)->value('name'))->toBe('Employee');
});

it('creates new roles inside the creating user client', function () {
    $acme = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    clientAdmin($acme);

    $role = Role::create([
        'name' => 'Night Shift Lead',
        'guard_name' => 'web',
        'client_id' => app(RoleCatalog::class)->newRoleClientId(),
    ]);

    expect($role->client_id)->toBe($acme->id);
});

it('strips platform permissions a client admin tries to grant itself', function () {
    $acme = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    clientAdmin($acme);

    foreach (['access_all_clients', 'impersonate_client', 'clients.edit', 'process_payroll'] as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $catalog = app(RoleCatalog::class);

    $filtered = $catalog->filterAssignable([
        'access_all_clients', 'impersonate_client', 'clients.edit', 'process_payroll',
    ]);

    expect($filtered)->toBe(['process_payroll']);
});

it('hides platform permissions from the client admin catalogue', function () {
    $acme = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    clientAdmin($acme);

    foreach (['access_all_clients', 'clients.view', 'process_payroll'] as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $names = app(RoleCatalog::class)->assignablePermissions()->pluck('name')->all();

    expect($names)->toContain('process_payroll')
        ->and($names)->not->toContain('access_all_clients')
        ->and($names)->not->toContain('clients.view');
});

it('lets platform staff create shared roles and assign platform permissions', function () {
    $this->actingAs(platformDeveloper());

    Permission::firstOrCreate(['name' => 'access_all_clients', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'clients.view', 'guard_name' => 'web']);

    $catalog = app(RoleCatalog::class);

    expect($catalog->newRoleClientId())->toBeNull()
        ->and($catalog->filterAssignable(['access_all_clients', 'clients.view']))
        ->toBe(['access_all_clients', 'clients.view']);
});

it('lets platform staff manage a shared role that a client admin cannot', function () {
    $shared = Role::create(['name' => 'Employee', 'guard_name' => 'web', 'client_id' => null]);

    $this->actingAs(platformDeveloper());

    expect(app(RoleCatalog::class)->findManageableOrFail($shared->id)->id)->toBe($shared->id);
});

it('allows the same role name in two different clients', function () {
    $acme = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    $globex = Client::create(['name' => 'Globex', 'slug' => 'globex']);

    $one = Role::create(['name' => 'Reviewer', 'guard_name' => 'web', 'client_id' => $acme->id]);
    $two = Role::create(['name' => 'Reviewer', 'guard_name' => 'web', 'client_id' => $globex->id]);

    expect($one->id)->not->toBe($two->id);
});
it('renders the role screens for a client admin', function () {
    $acme = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    Role::create(['name' => 'Employee', 'guard_name' => 'web', 'client_id' => null]);

    $admin = clientAdmin($acme);
    $this->actingAs($admin);

    // The role screens also require the legacy manage_role gate.
    $admin->givePermissionTo(
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'manage_role', 'guard_name' => 'web'])
    );

    $this->get(route('role.index'))->assertOk();
    $this->get(route('role.show'))->assertOk();
});
