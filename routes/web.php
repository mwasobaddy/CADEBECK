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


    Volt::route('admin/job-adverts', 'job.job-advert-manager')
        ->middleware(['auth', 'permission:manage_job_advert'])
        ->name('admin.job-adverts');
    Volt::route('admin/job-adverts/create', 'job.job-advert-create')
        ->middleware(['auth', 'permission:manage_job_advert'])
        ->name('admin.job-adverts.create');
    Volt::route('admin/job-adverts/{slug}/edit', 'job.job-advert-create')
        ->middleware(['auth', 'permission:manage_job_advert'])
        ->name('admin.job-adverts.edit');
    Volt::route('admin/job-adverts/{slug}/vetting', 'job.candidate-vetting')
        ->middleware(['auth', 'permission:vet_candidates'])
        ->name('admin.job-adverts.vetting');
    Volt::route('admin/analytics', 'job.admin-analytics-dashboard')
            ->middleware(['auth', 'permission:view_analytics'])
            ->name('admin.analytics');
});

require __DIR__.'/auth.php';
