<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;





Volt::route('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


Volt::route('/', 'job.job-advert-list')
    ->name('home');
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
        Volt::route('employees/manage', 'employee.index')
            ->middleware(['auth', 'permission:manage_employee'])
            ->name('employee.index');

        Volt::route('employees/create', 'employee.show')
            ->middleware(['auth', 'permission:create_employee'])
            ->name('employee.show');

        Volt::route('employees/{id}/edit', 'employee.show')
            ->middleware(['auth', 'permission:edit_employee'])
            ->name('employee.edit');

        Volt::route('users/manage', 'user.index')
            ->middleware(['auth', 'permission:manage_user'])
            ->name('user.index');

        Volt::route('users/create', 'user.show')
            ->middleware(['auth', 'permission:create_user'])
            ->name('user.show');

        Volt::route('users/{id}/edit', 'user.show')
            ->middleware(['auth', 'permission:edit_user'])
            ->name('user.edit');

        Volt::route('job/job-adverts', 'job.index')
            ->middleware(['auth', 'permission:manage_job_advert'])
            ->name('job.index');

        Volt::route('job/job-adverts/create', 'job.show')
            ->middleware(['auth', 'permission:manage_job_advert'])
            ->name('job.show');

        Volt::route('job/job-adverts/{slug}/edit', 'job.show')
            ->middleware(['auth', 'permission:manage_job_advert'])
            ->name('job.edit');

        Volt::route('role/manage', 'role.index')
            ->middleware(['permission:manage_role'])
            ->name('role.index');

        Volt::route('role/create', 'role.show')
            ->middleware(['permission:create_role'])
            ->name('role.show');

        Volt::route('role/{id}/edit', 'role.show')
            ->middleware(['permission:edit_role'])
            ->name('role.edit');

        Volt::route('job/job-adverts/{slug}/vetting', 'job.candidate-vetting')
            ->middleware(['auth', 'permission:vet_candidates'])
            ->name('job.index.vetting');
            
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

        Volt::route('clock', 'clock.clock-manager')
            ->middleware(['auth', 'permission:mark_attendance'])
            ->name('clock.manage');

        Volt::route('wellbeing', 'wellbeing.wellbeing-manager')
            ->middleware(['auth', 'permission:edit_user'])
            ->name('wellbeing.dashboard');

        Volt::route('own-leave/manage', 'leave.own-manage')
            ->middleware(['auth', 'permission:manage_my_leave'])
            ->name('own-leave.manage');

        Volt::route('leave/apply', 'leave.apply')
            ->middleware(['auth', 'permission:manage_my_leave'])
            ->name('leave.apply');

        Volt::route('own-leave/{id}/edit', 'leave.apply')
            ->middleware(['auth', 'permission:edit_my_leave'])
            ->name('own-leave.edit');

        Volt::route('all-leave/manage', 'leave.all-manage')
            ->middleware(['auth', 'permission:manage_all_leaves'])
            ->name('all-leave.manage');

        Volt::route('all-leave/{id}/edit', 'leave.all-show')
            ->middleware(['auth', 'permission:edit_all_leaves'])
            ->name('all-leave.edit');

    });
});

require __DIR__.'/auth.php';
