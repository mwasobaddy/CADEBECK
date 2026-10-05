<?php

use App\Models\Audit;
use App\Models\Client;
use App\Models\User;
use App\Services\ClientContext;
use App\Services\ClientProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

afterEach(function () {
    ClientContext::flush();
    Client::forgetCurrent();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function provisioningDeveloper(): User
{
    return User::withoutGlobalScopes()
        ->where('email', 'kelvinramsiel@gmail.com')
        ->firstOrFail();
}

function provisionAcme(): Client
{
    return app(ClientProvisioner::class)->provision(
        ['name' => 'Acme Ltd', 'plan' => 'professional', 'max_users' => 25],
        [
            'first_name' => 'Ada',
            'other_names' => 'Lovelace',
            'email' => 'ada@acme.test',
            'password' => 'Password123!',
        ],
        null,
    );
}

it('creates a client that is immediately usable', function () {
    $this->seed();
    ClientContext::flush();

    $client = provisionAcme();

    expect($client->exists)->toBeTrue()
        ->and($client->slug)->toBe('acme-ltd')
        ->and($client->status)->toBe('active')
        ->and($client->max_users)->toBe(25);
});

it('provisions a client admin who can sign in and holds the role', function () {
    $this->seed();
    ClientContext::flush();

    $client = provisionAcme();

    $owner = User::withoutGlobalScopes()->where('email', 'ada@acme.test')->firstOrFail();

    expect($owner->client_id)->toBe($client->id)
        ->and($owner->email_verified_at)->not->toBeNull();

    // The role must resolve inside the new client's team, otherwise the owner
    // appears to have no roles at all.
    ClientContext::override($client->id);

    expect($owner->fresh()->getRoleNames()->all())->toBe(['Client Admin'])
        ->and($owner->fresh()->can('manage_client_users'))->toBeTrue()
        ->and($owner->fresh()->can('manage_user'))->toBeTrue();
});

it('leaves the permission team as the client once provisioning finishes', function () {
    $this->seed();
    ClientContext::flush();

    $client = provisionAcme();

    // Provisioning must not leak its context into the surrounding request.
    expect(ClientContext::currentClientId())->toBeNull()
        ->and(ClientContext::permissionTeamId())->toBe(ClientContext::PLATFORM_TEAM_ID);
});

it('records an audit entry for the new client', function () {
    $this->seed();
    ClientContext::flush();

    $client = provisionAcme();

    $audit = Audit::acrossClients()
        ->where('target_type', Client::class)
        ->where('target_id', $client->id)
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->action)->toBe('create');
});

it('derives a unique slug when the name repeats', function () {
    $this->seed();
    ClientContext::flush();

    $first = provisionAcme();
    $second = app(ClientProvisioner::class)->provision(
        ['name' => 'Acme Ltd'],
        ['first_name' => 'Grace', 'email' => 'grace@acme2.test', 'password' => 'Password123!'],
        null,
    );

    expect($first->slug)->toBe('acme-ltd')
        ->and($second->slug)->toBe('acme-ltd-2');
});

it('creates a client through the screen and lands on it', function () {
    $this->seed();
    ClientContext::flush();

    $this->actingAs(provisioningDeveloper());

    Volt::test('admin.clients.create')
        ->set('form.name', 'Globex Ltd')
        ->set('form.plan', 'enterprise')
        ->set('form.first_name', 'Hank')
        ->set('form.other_names', 'Scorpio')
        ->set('form.email', 'hank@globex.test')
        ->set('form.password', 'Password123!')
        ->set('form.password_confirmation', 'Password123!')
        ->call('save')
        ->assertHasNoErrors();

    $client = Client::where('name', 'Globex Ltd')->firstOrFail();

    expect($client->slug)->toBe('globex-ltd')
        ->and(User::withoutGlobalScopes()->where('email', 'hank@globex.test')->value('client_id'))
        ->toBe($client->id);
});

it('rejects a duplicate login email on the create form', function () {
    $this->seed();
    ClientContext::flush();

    $this->actingAs(provisioningDeveloper());

    Volt::test('admin.clients.create')
        ->set('form.name', 'Another Ltd')
        ->set('form.first_name', 'Someone')
        ->set('form.email', 'employee1@cadebeck.test')
        ->set('form.password', 'Password123!')
        ->set('form.password_confirmation', 'Password123!')
        ->call('save')
        ->assertHasErrors(['form.email']);
});

it('lets a developer suspend and reactivate a client', function () {
    $this->seed();
    ClientContext::flush();

    $client = provisionAcme();
    $this->actingAs(provisioningDeveloper());

    Volt::test('admin.clients.show', ['client' => $client->id])
        ->call('toggleStatus');

    expect($client->fresh()->status)->toBe('suspended');

    Volt::test('admin.clients.show', ['client' => $client->id])
        ->call('toggleStatus');

    expect($client->fresh()->status)->toBe('active');
});

it('keeps the client screens away from client users', function () {
    $this->seed();
    ClientContext::flush();

    $clientUser = User::withoutGlobalScopes()
        ->where('email', 'employee1@cadebeck.test')
        ->firstOrFail();

    $this->actingAs($clientUser);
    ClientContext::override($clientUser->client_id);

    $this->get(route('admin.clients.create'))->assertForbidden();
    $this->get(route('admin.clients.show', ['client' => $clientUser->client_id]))->assertForbidden();
});