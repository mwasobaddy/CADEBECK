<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

/*
 * Guards the permission catalogue against names used in code that were never
 * seeded.
 *
 * A missing permission fails quietly: a @can guard is simply false, so the UI
 * hides a button nobody misses, and a route guard 403s a page that looks
 * reachable. That is how view_analytics, edit_applications, edit_payroll and
 * friends went unnoticed, and how permission:vet_candidates 403'd the vetting
 * route for every user.
 */

uses(RefreshDatabase::class);

afterEach(fn () => app(PermissionRegistrar::class)->forgetCachedPermissions());

/**
 * Every permission name referenced by a view, route or code-level check.
 *
 * @return array<int, string>
 */
function referencedPermissionNames(): array
{
    $names = [];

    $add = function (?string $name) use (&$names) {
        if ($name && $name !== '') {
            $names[] = $name;
        }
    };

    // Only live views. resources/views/layout/app.blade.php is a leftover
    // layout that Volt never renders - the active one is
    // components/layouts/app.blade.php, which includes <x-app.sidebar /> - and
    // it still carries nav references from an earlier version of the app.
    $deadViews = ['layout/app.blade.php'];

    $scan = function (string $pattern, int $group) use ($add, $deadViews) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            foreach ($deadViews as $dead) {
                if (str_ends_with(str_replace(DIRECTORY_SEPARATOR, '/', $file->getPathname()), $dead)) {
                    continue 2;
                }
            }

            if (preg_match_all($pattern, file_get_contents($file->getPathname()), $matches)) {
                foreach ($matches[$group] ?? [] as $name) {
                    $add($name);
                }
            }
        }
    };

    // @can('x') / @cannot('x'). The trailing comma guard skips Gate abilities
    // such as @can('approve', $leaveRequest), which resolve to a policy method
    // rather than a permission row.
    $scan("/@(?:can|cannot)\(\s*'([^']+)'\s*(?![,)])/", 1);

    // auth()->user()->can('x')
    $scan("/(?:Auth::user\(\)|auth\(\)->user\(\))->can\(\s*'([^']+)'\s*(?![,)])/", 1);

    // permission middleware. Names may be dotted (clients.view) or a
    // pipe-separated any-of list.
    foreach (explode("\n", (string) file_get_contents(base_path('routes/web.php'))) as $line) {
        if (preg_match('/permission:([A-Za-z0-9_|.]+)/', $line, $m)) {
            foreach (explode('|', $m[1]) as $permission) {
                $add(trim($permission));
            }
        }
    }

    return array_values(array_unique($names));
}

it('defines every permission referenced in views and routes', function () {
    $this->seed();

    $defined = \Spatie\Permission\Models\Permission::pluck('name')->all();
    $missing = array_values(array_diff(referencedPermissionNames(), $defined));

    expect($missing)->toBe([], 'Undefined permissions referenced in code: '.implode(', ', $missing));
});

it('defines the permissions the recruitment and payroll screens rely on', function () {
    $this->seed();

    // Each of these was referenced in code but never seeded, so the guard was
    // always false and the feature was unreachable.
    $expected = [
        'view_analytics',
        'edit_applications',
        'delete_applications',
        'edit_payroll',
        'delete_payroll',
        'export_payroll',
        'vet_candidate',
        'edit_job_advert',
    ];

    $defined = \Spatie\Permission\Models\Permission::pluck('name')->all();

    expect(array_values(array_diff($expected, $defined)))->toBe([]);
});

it('lets a developer reach the analytics page and vetting route', function () {
    $this->seed();

    $developer = User::withoutGlobalScopes()
        ->where('email', 'kelvinramsiel@gmail.com')
        ->firstOrFail();

    $this->actingAs($developer);

    expect($developer->can('view_analytics'))->toBeTrue()
        ->and($developer->can('vet_candidate'))->toBeTrue();

    $this->get(route('job.analytics'))->assertOk();
});