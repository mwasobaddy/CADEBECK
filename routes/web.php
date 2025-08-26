<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;


Route::get('/', function () {
    return redirect()->route('careers');
})->name('home');

Volt::route('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');



Volt::route('careers', 'job.job-advert-list')
    ->name('careers');
Volt::route('careers/{slug}', 'job.job-advert-details')
    ->name('careers.details');
Volt::route('careers/{slug}/apply', 'job.job-application-form')
    ->name('careers.apply');

// Public applications route for guest layout navigation
Volt::route('applications', 'job.job-application-form')
    ->name('applications');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Route::middleware(['verified'])->group(function () {
        Volt::route('employees/manage', 'Employee.employee-manager')
            ->middleware(['auth', 'permission:manage_employee'])
            ->name('employee.manage');

        Volt::route('employees/create', 'Employee.employee-create')
            ->middleware(['auth', 'permission:create_employee'])
            ->name('employee.create');

        Volt::route('employees/{id}/edit', 'Employee.employee-create')
            ->middleware(['auth', 'permission:edit_employee'])
            ->name('employee.edit');
        Volt::route('users/manage', 'User.user-manager')
            ->middleware(['auth', 'permission:manage_user'])
            ->name('user.manage');

        Volt::route('users/create', 'User.user-create')
            ->middleware(['auth', 'permission:create_user'])
            ->name('user.create');

        Volt::route('users/{id}/edit', 'User.user-create')
            ->middleware(['auth', 'permission:edit_user'])
            ->name('user.edit');

        Volt::route('job/job-adverts', 'job.job-advert-manager')
            ->middleware(['auth', 'permission:manage_job_advert'])
            ->name('job.job-adverts');

        Volt::route('job/job-adverts/create', 'job.job-advert-create')
            ->middleware(['auth', 'permission:manage_job_advert'])
            ->name('job.job-adverts.create');

        Volt::route('job/job-adverts/{slug}/edit', 'job.job-advert-create')
            ->middleware(['auth', 'permission:manage_job_advert'])
            ->name('job.job-adverts.edit');

        Volt::route('role/manage', 'Role.role-manager')
            ->middleware(['permission:manage_role'])
            ->name('role.manage');

        Volt::route('role/create', 'Role.role-create')
            ->middleware(['permission:create_role'])
            ->name('role.create');

        Volt::route('role/{id}/edit', 'Role.role-create')
            ->middleware(['permission:edit_role'])
            ->name('role.edit');

        Volt::route('job/job-adverts/{slug}/vetting', 'job.candidate-vetting')
            ->middleware(['auth', 'permission:vet_candidates'])
            ->name('job.job-adverts.vetting');
            
        Volt::route('admin/analytics', 'job.admin-analytics-dashboard')
            ->middleware(['auth', 'permission:view_analytics'])
            ->name('job.analytics');
    });
});

require __DIR__.'/auth.php';
