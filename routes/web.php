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
            
        // Organisation: Locations, Branches, Departments, Designations
        Volt::route('organisation/locations', 'organisation.location_manager')
            ->middleware(['auth', 'permission:manage_location'])
            ->name('location.manage');

        Volt::route('organisation/locations/create', 'organisation.location_create')
            ->middleware(['auth', 'permission:create_location'])
            ->name('location.create');

        Volt::route('organisation/locations/{id}/edit', 'organisation.location_create')
            ->middleware(['auth', 'permission:edit_location'])
            ->name('location.edit');

        Volt::route('organisation/branches', 'organisation.branch_manager')
            ->middleware(['auth', 'permission:manage_branch'])
            ->name('branch.manage');

        Volt::route('organisation/branches/create', 'organisation.branch_create')
            ->middleware(['auth', 'permission:create_branch'])
            ->name('branch.create');

        Volt::route('organisation/branches/{id}/edit', 'organisation.branch_create')
            ->middleware(['auth', 'permission:edit_branch'])
            ->name('branch.edit');

        Volt::route('organisation/departments', 'organisation.department_manager')
            ->middleware(['auth', 'permission:manage_department'])
            ->name('department.manage');

        Volt::route('organisation/departments/create', 'organisation.department_create')
            ->middleware(['auth', 'permission:create_department'])
            ->name('department.create');

        Volt::route('organisation/departments/{id}/edit', 'organisation.department_create')
            ->middleware(['auth', 'permission:edit_department'])
            ->name('department.edit');

        Volt::route('organisation/designations', 'organisation.designation_manager')
            ->middleware(['auth', 'permission:manage_designation'])
            ->name('designation.manage');

        Volt::route('organisation/designations/create', 'organisation.designation_create')
            ->middleware(['auth', 'permission:create_designation'])
            ->name('designation.create');

        Volt::route('organisation/designations/{id}/edit', 'organisation.designation_create')
            ->middleware(['auth', 'permission:edit_designation'])
            ->name('designation.edit');

        Volt::route('admin/analytics', 'job.admin-analytics-dashboard')
            ->middleware(['auth', 'permission:view_analytics'])
            ->name('job.analytics');
    });
});

require __DIR__.'/auth.php';
