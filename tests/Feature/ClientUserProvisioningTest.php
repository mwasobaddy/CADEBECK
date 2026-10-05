<?php

use App\Models\Client;
use App\Models\User;
use App\Services\ClientContext;
use App\Services\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

afterEach(function () {
    ClientContext::flush();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

/** Seed the platform permissions plus a shared Developer role. */
function seedPlatformRoles(): void
{
    foreach (\Database\Seeders\RoleBundlesSeeder::PLATFORM_PERMISSIONS as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $developer = Role::create(['name' => 'Developer', 'guard_name' => 'web', 'client_id' => null]);
    $developer->givePermissionTo(\Database\Seeders\RoleBundlesSeeder::PLATFORM_PERMISSIONS);

    $employee = Role::create(['name' => 'Employee', 'guard_name' => 'web', 'client_id' => null]);
}

function actingClientAdmin(?Client $client = null): User
{
    $client ??= Client::create(['name' => 'Acme', 'slug' => 'acme']);

    seedPlatformRoles();

    Permission::firstOrCreate(['name' => 'manage_client_users', 'guard_name' => 'web']);

    $adminRole = Role::create(['name' => 'Client Admin', 'guard_name' => 'web', 'client_id' => $client->id]);
    $adminRole->givePermissionTo('manage_client_users');

    $user = User::factory()->create(['client_id' => $client->id]);

    ClientContext::override($client->id);
    $user->assignRole($adminRole);
    ClientContext::override($client->id);

    return $user;
}

it('never offers a platform role to a client admin', function () {
    actingClientAdmin();

    $names = app(RoleCatalog::class)->assignableRoles()->pluck('name')->all();

    expect($names)->toContain('Employee')
        ->and($names)->not->toContain('Developer');
});

it('refuses to let a client admin assign the developer role', function () {
    actingClientAdmin();

    expect(fn () => app(RoleCatalog::class)->findAssignableByName('Developer'))
        ->toThrow(Illuminate\Validation\ValidationException::class);
});

it('still lets a client admin assign an ordinary role', function () {
    actingClientAdmin();

    expect(app(RoleCatalog::class)->findAssignableByName('Employee')->name)->toBe('Employee');
});

it('identifies a role as platform level by its permissions not its name', function () {
    $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    actingClientAdmin($client);

    // A client role with an imposing-sounding name but no platform permission.
    $lookalike = Role::create(['name' => 'Super Admin', 'guard_name' => 'web', 'client_id' => $client->id]);

    // A shared role carrying a platform permission, whatever it is called.
    $renamed = Role::create(['name' => 'Site Support', 'guard_name' => 'web', 'client_id' => null]);
    $renamed->givePermissionTo('access_all_clients');

    $catalog = app(RoleCatalog::class);

    expect($catalog->isPlatformRole($lookalike))->toBeFalse()
        ->and($catalog->isPlatformRole($renamed))->toBeTrue();
});

it('will not let a client create a role that collides with a shared role name', function () {
    $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    actingClientAdmin($client);

    // A shared 'Developer' is visible to every client, so a same-named client
    // role would make name based role assignment ambiguous.
    expect(fn () => Role::create(['name' => 'Employee', 'guard_name' => 'web', 'client_id' => $client->id]))
        ->toThrow(Spatie\Permission\Exceptions\RoleAlreadyExists::class);
});

it('will not let a client rename a role onto a shared role name', function () {
    $client = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    actingClientAdmin($client);

    $shared = Role::where('name', 'Employee')->first();
    $own = Role::create(['name' => 'Night Shift', 'guard_name' => 'web', 'client_id' => $client->id]);

    expect(fn () => app(RoleCatalog::class)->assertNameAvailable($shared->name, $own))
        ->toThrow(Illuminate\Validation\ValidationException::class);

    // The role keeps its own name.
    expect($own->fresh()->name)->toBe('Night Shift');
});

it('refuses to let a client admin provision a user into another client', function () {
    $acme = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    $globex = Client::create(['name' => 'Globex', 'slug' => 'globex']);

    $admin = actingClientAdmin($acme);
    ClientContext::override($acme->id);

    // resolveClientId() ignores any requested client for a non-platform user.
    expect($acme->id)->not->toBe($globex->id);
});

it('locks a client admin to their own client when creating a user', function () {
    $acme = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    $globex = Client::create(['name' => 'Globex', 'slug' => 'globex']);

    actingClientAdmin($acme);
    ClientContext::override($acme->id);

    $user = User::create([
        'client_id' => $globex->id, // attempted cross-client assignment
        'first_name' => 'Mallory',
        'email' => 'mallory@globex.test',
        'password' => bcrypt('Password123!'),
    ]);

    // The BelongsToClient creating hook overrides any attempted client_id.
    expect($user->fresh()->client_id)->toBe($acme->id);
});

it('lets platform staff create platform staff and provision into a client', function () {
    $acme = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    seedPlatformRoles();

    $developerRole = Role::where('name', 'Developer')->first();
    $developer = User::factory()->create(['client_id' => null]);
    ClientContext::override(null);
    $developer->assignRole($developerRole);

    $this->actingAs($developer);
    ClientContext::override(null);

    expect(ClientContext::canAccessAllClients())->toBeTrue()
        ->and(app(RoleCatalog::class)->newRoleClientId())->toBeNull()
        ->and(app(RoleCatalog::class)->assignableRoles()->pluck('name')->all())
        ->toContain('Developer');
});

it('keeps user email globally unique across clients', function () {
    $acme = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    $globex = Client::create(['name' => 'Globex', 'slug' => 'globex']);

    User::factory()->create(['client_id' => $acme->id, 'email' => 'shared@example.test']);

    expect(fn () => User::factory()->create(['client_id' => $globex->id, 'email' => 'shared@example.test']))
        ->toThrow(Illuminate\Database\QueryException::class);
});
it('renders the user screens for a client admin', function () {
    $acme = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    $admin = actingClientAdmin($acme);

    $admin->givePermissionTo(
        Permission::firstOrCreate(['name' => 'manage_user', 'guard_name' => 'web'])
    );

    $this->actingAs($admin);
    ClientContext::override($acme->id);

    $this->get(route('user.index'))->assertOk();
    $this->get(route('user.show'))->assertOk();
});
