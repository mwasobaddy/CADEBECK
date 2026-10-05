<?php

use App\Models\Client;
use App\Models\User;
use App\Services\ClientContext;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/*
 * Guards the seeded tenancy split: the platform Developer must stay unassigned
 * on the platform team while every client user sits on the demo client team.
 */

it('seeds the platform developer without a client', function () {
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    $developer = DB::table('users')->where('email', 'kelvinramsiel@gmail.com')->first();

    expect($developer)->not->toBeNull()
        ->and($developer->client_id)->toBeNull();
});

it('seeds the developer role assignment on the platform team', function () {
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    $developer = DB::table('users')->where('email', 'kelvinramsiel@gmail.com')->first();

    $team = DB::table('model_has_roles')
        ->where('model_id', $developer->id)
        ->where('model_type', User::class)
        ->value('client_id');

    expect($team)->toBe(ClientContext::PLATFORM_TEAM_ID);
});

it('seeds every client user on the demo client team', function () {
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    $demo = Client::where('slug', 'demo-client')->first();

    $clientUserIds = User::where('client_id', $demo->id)->pluck('id');

    expect($clientUserIds)->not->toBeEmpty()
        ->and(DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->whereIn('model_id', $clientUserIds)
            ->where('client_id', '!=', $demo->id)
            ->count())->toBe(0);
});

it('leaves every client-owned table fully assigned', function () {
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    $tables = [
        'employees', 'attendances', 'leave_requests', 'well_being_responses',
        'payrolls', 'payslips', 'locations', 'branches', 'departments',
        'designations', 'contract_types', 'job_adverts', 'applications',
    ];

    foreach ($tables as $table) {
        expect(DB::table($table)->whereNull('client_id')->count())
            ->toBe(0, "{$table} still has unassigned rows");
    }
});

it('does not treat pre-launch signups as client data', function () {
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    // launch_subscribers is platform level: a signup from an anonymous visitor
    // has no client and must not be swept into the demo client.
    expect(DB::table('launch_subscribers')->whereNotNull('client_id')->count())->toBe(0);
});

afterEach(function () {
    ClientContext::flush();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});