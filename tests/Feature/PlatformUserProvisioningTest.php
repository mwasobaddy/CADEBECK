<?php

use App\Models\Audit;
use App\Models\User;
use App\Services\ClientContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\PermissionRegistrar;

/*
 * Regression tests for platform-level user provisioning.
 *
 * Creating platform staff from the users form failed with
 * "NOT NULL constraint failed: audits.client_id", because the form pushed a null
 * client into ClientContext::override(), which cleared the ambient context the
 * audit trail relies on.
 */

uses(RefreshDatabase::class);

afterEach(function () {
    ClientContext::flush();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function developerUser(): User
{
    return User::withoutGlobalScopes()
        ->where('email', 'kelvinramsiel@gmail.com')
        ->firstOrFail();
}

it('lets a developer create platform staff and records the audit', function () {
    $this->seed();
    ClientContext::flush();

    $developer = developerUser();
    $this->actingAs($developer);

    Volt::test('user.show')
        ->set('form.first_name', 'TaShya')
        ->set('form.other_names', 'Brent Morse')
        ->set('form.email', 'bewy@mailinator.com')
        ->set('form.password', 'Pa$$w0rd!')
        ->set('form.password_confirmation', 'Pa$$w0rd!')
        ->set('form.role', 'System Admin')
        ->set('form.client_id', '')
        ->call('save')
        ->assertHasNoErrors();

    $created = User::withoutGlobalScopes()->where('email', 'bewy@mailinator.com')->firstOrFail();

    expect($created->client_id)->toBeNull();

    // The audit row must be written without tripping the constraint.
    expect(Audit::acrossClients()->where('target_id', $created->id)->exists())->toBeTrue();
});

it('lets a developer provision a user into a chosen client', function () {
    $this->seed();
    ClientContext::flush();

    $developer = developerUser();
    $this->actingAs($developer);

    $demo = \App\Models\Client::where('slug', 'demo-client')->firstOrFail();

    Volt::test('user.show')
        ->set('form.first_name', 'Client Staff')
        ->set('form.other_names', 'Person')
        ->set('form.email', 'staff@demo.test')
        ->set('form.password', 'Pa$$w0rd!')
        ->set('form.password_confirmation', 'Pa$$w0rd!')
        ->set('form.role', 'Employee')
        ->set('form.client_id', (string) $demo->id)
        ->call('save')
        ->assertHasNoErrors();

    $created = User::withoutGlobalScopes()->where('email', 'staff@demo.test')->firstOrFail();

    expect($created->client_id)->toBe($demo->id)
        ->and(Audit::acrossClients()->where('target_id', $created->id)->exists())->toBeTrue();
});

it('keeps a client admin inside their own client', function () {
    $this->seed();
    ClientContext::flush();

    $admin = User::withoutGlobalScopes()
        ->where('email', 'executive@cadebeck.test')
        ->firstOrFail();

    $this->actingAs($admin);
    ClientContext::override($admin->client_id);

    Volt::test('user.show')
        ->set('form.first_name', 'Scoped')
        ->set('form.other_names', 'User')
        ->set('form.email', 'scoped@demo.test')
        ->set('form.password', 'Pa$$w0rd!')
        ->set('form.password_confirmation', 'Pa$$w0rd!')
        ->set('form.role', 'Employee')
        ->call('save')
        ->assertHasNoErrors();

    expect(User::withoutGlobalScopes()->where('email', 'scoped@demo.test')->value('client_id'))
        ->toBe($admin->client_id);
});