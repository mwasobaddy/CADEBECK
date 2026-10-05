<?php

use App\Models\Client;
use App\Models\User;
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

function clientTeam(string $slug): Client
{
    return Client::create(['name' => ucfirst($slug), 'slug' => $slug]);
}

function userIn(?Client $client): User
{
    return User::factory()->create(['client_id' => $client?->id]);
}

it('resolves the permission team from the current client', function () {
    $acme = clientTeam('acme');

    ClientContext::override($acme->id);
    expect(getPermissionsTeamId())->toBe($acme->id);

    ClientContext::override(null);
    expect(getPermissionsTeamId())->toBe(ClientContext::PLATFORM_TEAM_ID);
});

it('stores role assignments under the owning client team', function () {
    $acme = clientTeam('acme');

    $role = Role::create(['name' => 'Employee', 'guard_name' => 'web', 'client_id' => null]);

    $user = userIn($acme);
    ClientContext::override($acme->id);
    $user->assignRole($role);

    expect(DB::table('model_has_roles')
        ->where('model_id', $user->id)
        ->value('client_id'))->toBe($acme->id);
});

it('stores platform staff assignments under the reserved platform team', function () {
    $role = Role::create(['name' => 'Developer', 'guard_name' => 'web', 'client_id' => null]);
    $developer = userIn(null);

    ClientContext::override(null);
    $developer->assignRole($role);

    expect(DB::table('model_has_roles')
        ->where('model_id', $developer->id)
        ->value('client_id'))->toBe(ClientContext::PLATFORM_TEAM_ID);
});

it('shows a user the global roles plus their own client roles only', function () {
    $acme = clientTeam('acme');
    $globex = clientTeam('globex');

    $globalRole = Role::create(['name' => 'Employee', 'guard_name' => 'web', 'client_id' => null]);
    $acmeRole = Role::create(['name' => 'Payroll Wizard', 'guard_name' => 'web', 'client_id' => $acme->id]);
    $globexRole = Role::create(['name' => 'Globex Only', 'guard_name' => 'web', 'client_id' => $globex->id]);

    $acmeUser = userIn($acme);

    ClientContext::override($acme->id);
    $acmeUser->assignRole($globalRole);
    $acmeUser->assignRole($acmeRole);

    $roleNames = $acmeUser->roles()->pluck('name')->sort()->values()->all();

    expect($roleNames)->toBe(['Employee', 'Payroll Wizard'])
        ->and($roleNames)->not->toContain('Globex Only');
});

it('lets two clients hold roles with the same name independently', function () {
    $acme = clientTeam('acme');
    $globex = clientTeam('globex');

    $acmeRole = Role::create(['name' => 'Reviewer', 'guard_name' => 'web', 'client_id' => $acme->id]);
    $globexRole = Role::create(['name' => 'Reviewer', 'guard_name' => 'web', 'client_id' => $globex->id]);

    expect($acmeRole->id)->not->toBe($globexRole->id);

    $acmeUser = userIn($acme);
    ClientContext::override($acme->id);
    $acmeUser->assignRole($acmeRole);

    expect($acmeUser->roles()->pluck('name')->all())->toBe(['Reviewer']);
});

it('keeps permissions resolved per team when checking abilities', function () {
    $acme = clientTeam('acme');

    $permission = Permission::create(['name' => 'process_payroll', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'Payroll', 'guard_name' => 'web', 'client_id' => $acme->id]);
    $role->givePermissionTo($permission);

    $user = userIn($acme);

    ClientContext::override($acme->id);
    $user->assignRole($role);

    expect($user->fresh()->can('process_payroll'))->toBeTrue();

    // The same user, viewed from another client's context, must not keep it.
    ClientContext::override(clientTeam('globex')->id);

    expect($user->fresh()->can('process_payroll'))->toBeFalse();
});
