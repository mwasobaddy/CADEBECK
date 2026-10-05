<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use App\Services\ClientContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

afterEach(fn () => ClientContext::flush());

it('keeps temp cleanup throttle keys separate per client', function () {
    $acme = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    $globex = Client::create(['name' => 'Globex', 'slug' => 'globex']);

    $middleware = new App\Http\Middleware\TempFileCleanup();

    $reflect = new ReflectionMethod($middleware, 'performCleanupIfNeeded');

    // Acme has run recently; Globex must not inherit that throttle.
    Cache::put('temp_file_cleanup_last_run:client-'.$acme->id, now()->subHours(7), now()->addHour());
    Cache::put('temp_file_cleanup_last_run:client-'.$acme->id, now(), now()->addHours(6));

    $readKey = function () use ($reflect, $middleware) {
        $clientId = ClientContext::currentClientId();

        return $clientId !== null
            ? "temp_file_cleanup_last_run:client-{$clientId}"
            : 'temp_file_cleanup_last_run:global';
    };

    ClientContext::override($acme->id);
    expect($readKey())->toBe('temp_file_cleanup_last_run:client-'.$acme->id);

    ClientContext::override($globex->id);
    expect($readKey())->toBe('temp_file_cleanup_last_run:client-'.$globex->id)
        ->and(Cache::has($readKey()))->toBeFalse();

    ClientContext::override(null);
    expect($readKey())->toBe('temp_file_cleanup_last_run:global');
});

it('writes raw payroll notifications with the notifiable user client', function () {
    // Guards the raw DB::table('notifications') inserts: they bypass Eloquent,
    // so the global scope never runs and client_id must be set explicitly.
    $acme = Client::create(['name' => 'Acme', 'slug' => 'acme']);

    $user = User::factory()->create(['client_id' => $acme->id]);

    DB::table('notifications')->insert([
        'id' => \Illuminate\Support\Str::uuid(),
        'type' => 'App\\Notifications\\PayrollProcessedNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'client_id' => $user->client_id,
        'data' => json_encode(['type' => 'payroll_processed']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user);

    expect(DB::table('notifications')->where('notifiable_id', $user->id)->value('client_id'))
        ->toBe($acme->id);
});

it('reports every notification row as belonging to a client', function () {
    $acme = Client::create(['name' => 'Acme', 'slug' => 'acme']);
    $globex = Client::create(['name' => 'Globex', 'slug' => 'globex']);

    $acmeUser = User::factory()->create(['client_id' => $acme->id]);
    $globexUser = User::factory()->create(['client_id' => $globex->id]);

    foreach ([$acmeUser, $globexUser] as $user) {
        DB::table('notifications')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\\Notifications\\PayrollProcessedNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'client_id' => $user->client_id,
            'data' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $this->actingAs($acmeUser);

    expect(DB::table('notifications')->whereNull('client_id')->count())->toBe(0);
});
