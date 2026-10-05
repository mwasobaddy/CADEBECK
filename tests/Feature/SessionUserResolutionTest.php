<?php

use App\Models\User;
use App\Services\ClientContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

/*
 * Regression tests for the session-guard recursion.
 *
 * ClientContext is consulted from inside the client global scope, so it must
 * never resolve the signed-in user through that same scope. Doing so re-entered
 * the scope while the session guard was still fetching the user, producing
 * unbounded recursion: every authenticated page load died after 30 seconds in
 * whatever query happened to be running.
 *
 * These tests authenticate the way a real request does - by putting the user id
 * in the session so the guard must load the record from the database - rather
 * than with actingAs(), which hands the guard an already resolved instance and
 * therefore hides the bug.
 */

uses(RefreshDatabase::class);

afterEach(function () {
    ClientContext::flush();
});

/** The session key the web guard reads the signed in user id from. */
function sessionLoginKey(): string
{
    return 'login_web_'.sha1(\Illuminate\Auth\SessionGuard::class);
}

/**
 * Authenticate the way a real request does: the user id lives in the session, so
 * the guard has to load the row from the database.
 */
function withSessionUser(TestCase $test, User $user)
{
    // Drop any resolved guard so auth()->user() goes to the database.
    Auth::forgetGuards();

    return $test->withSession([sessionLoginKey() => $user->id]);
}

it('loads the signed in user from the session without recursing', function () {
    $this->seed();
    ClientContext::flush();

    $developer = User::withoutGlobalScopes()
        ->where('email', 'kelvinramsiel@gmail.com')
        ->firstOrFail();

    $this->get('/login')->assertOk();

    withSessionUser($this, $developer);

    expect(auth()->check())->toBeTrue()
        ->and(auth()->id())->toBe($developer->id);
});

it('serves the dashboard to a session authenticated developer', function () {
    $this->seed();
    ClientContext::flush();

    $developer = User::withoutGlobalScopes()
        ->where('email', 'kelvinramsiel@gmail.com')
        ->firstOrFail();

    withSessionUser($this, $developer)
        ->get('/dashboard')
        ->assertRedirect(route('platform.clients.index'));
});

it('serves the platform clients screen to a session authenticated developer', function () {
    $this->seed();
    ClientContext::flush();

    $developer = User::withoutGlobalScopes()
        ->where('email', 'kelvinramsiel@gmail.com')
        ->firstOrFail();

    withSessionUser($this, $developer)
        ->get(route('platform.clients.index'))
        ->assertOk()
        ->assertSee('Demo Client');
});

it('serves the dashboard to a session authenticated client user', function () {
    $this->seed();
    ClientContext::flush();

    $employee = User::withoutGlobalScopes()
        ->where('email', 'employee1@cadebeck.test')
        ->firstOrFail();

    withSessionUser($this, $employee)->get('/dashboard')->assertOk();
});

it('resolves the client id without invoking the user scope', function () {
    $this->seed();
    ClientContext::flush();

    $employee = User::withoutGlobalScopes()
        ->where('email', 'employee1@cadebeck.test')
        ->firstOrFail();

    $this->get('/login')->assertOk();
    withSessionUser($this, $employee);

    expect(ClientContext::currentClientId())->toBe($employee->client_id)
        ->and(ClientContext::canAccessAllClients())->toBeFalse();
});

it('keeps working when the guard resolves the user mid scope', function () {
    $this->seed();
    ClientContext::flush();

    $developer = User::withoutGlobalScopes()
        ->where('email', 'kelvinramsiel@gmail.com')
        ->firstOrFail();

    $this->get('/login')->assertOk();
    withSessionUser($this, $developer);

    // Forcing a fresh resolution from inside a query is exactly what used to
    // recurse; it must now resolve without runaway queries.
    DB::enableQueryLog();
    $clients = Client::count();
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($clients)->toBe(1)
        ->and($queries)->toBeLessThan(20);
});