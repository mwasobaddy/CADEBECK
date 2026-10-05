<?php

use App\Models\User;

/*
 * Public self-registration is deliberately closed.
 *
 * Users are provisioned by a platform Developer or by a client's own Client
 * Admin, so that every account belongs to a client (or is platform staff).
 * Leaving /register reachable would let anyone create an account outside a
 * client context, which the tenancy model cannot represent.
 */

test('the registration route does not exist', function () {
    expect(Route::has('register'))->toBeFalse();
});

test('the registration screen is not reachable', function () {
    $this->get('/register')->assertNotFound();
});

test('no route exposes the registration component', function () {
    // The component file is kept for reference, but nothing routes to it, so it
    // cannot be reached over HTTP. If a route is ever added back for it, this
    // test fails and self-registration has been reopened by accident.
    $exposed = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_contains($route->getActionName() ?? '', 'auth.register')
            || str_contains($route->uri() ?? '', 'register'))
        ->count();

    expect($exposed)->toBe(0);
});

test('no user can be created through registration', function () {
    $before = User::count();

    $this->post('/register', [
        'first_name' => 'Intruder',
        'other_names' => 'Person',
        'email' => 'intruder@example.test',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertNotFound();

    expect(User::count())->toBe($before);
});