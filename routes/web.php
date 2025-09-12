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

    // Test route for payroll notifications
    Route::get('/test-payroll-notification', function () {
        $employee = auth()->user()->employee ?? \App\Models\Employee::first();

        if (!$employee) {
            return response()->json(['error' => 'No employee found'], 404);
        }

        // Create a test payroll record
        $payroll = \App\Models\Payroll::create([
            'employee_id' => $employee->id,
            'payroll_period' => now()->format('m/Y'),
            'pay_date' => now()->endOfMonth(),
            'basic_salary' => 50000,
            'gross_pay' => 55000,
            'net_pay' => 48000,
            'status' => 'processed',
        ]);

        try {
            $employee->user->notify(new \App\Notifications\PayrollProcessedNotification($payroll));
            $payroll->delete(); // Clean up

            return response()->json([
                'success' => true,
                'message' => 'Payroll notification sent successfully!',
                'employee' => $employee->first_name . ' ' . $employee->other_names,
                'email' => $employee->user->email ?? 'N/A'
            ]);
        } catch (\Exception $e) {
            $payroll->delete(); // Clean up
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    })->name('test.payroll.notification');
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

        // Payroll routes
        Volt::route('payroll/process', 'payroll.admin.process-payroll')
            ->middleware(['auth', 'permission:process_payroll'])
            ->name('payroll.process');

        Volt::route('payroll/employee', 'payroll.employee.payslips')
            ->middleware(['auth', 'permission:view_my_payslips'])
            ->name('payroll.employee');

        
        // Employee-specific payroll management routes
        Volt::route('employees/payroll/{employeeId}/allowances', 'employee.payroll.allowances')
            ->middleware(['auth', 'permission:manage_allowance'])
            ->name('employee.payroll.allowances');

        Volt::route('employees/payroll/{employeeId}/allowances/create', 'employee.payroll.allowance-form')
            ->middleware(['auth', 'permission:create_allowance'])
            ->name('employee.payroll.allowances.create');

        Volt::route('employees/payroll/{employeeId}/allowances/{allowanceId}/edit', 'employee.payroll.allowance-form')
            ->middleware(['auth', 'permission:edit_allowance'])
            ->name('employee.payroll.allowances.edit');

        Volt::route('employees/payroll/{employeeId}/deductions', 'employee.payroll.deductions')
            ->middleware(['auth', 'permission:manage_deduction'])
            ->name('employee.payroll.deductions');

        Volt::route('employees/payroll/{employeeId}/deductions/create', 'employee.payroll.deduction-form')
            ->middleware(['auth', 'permission:create_deduction'])
            ->name('employee.payroll.deductions.create');

        Volt::route('employees/payroll/{employeeId}/deductions/{deductionId}/edit', 'employee.payroll.deduction-form')
            ->middleware(['auth', 'permission:edit_deduction'])
            ->name('employee.payroll.deductions.edit');

        Volt::route('employees/payroll/{employeeId}/payslips', 'employee.payroll.payslips')
            ->middleware(['auth', 'permission:view_employee_payslips'])
            ->name('employee.payroll.payslips');

        Volt::route('my-payroll-history', 'employee.payroll.history')
            ->middleware(['auth', 'permission:view_employee_payroll_history'])
            ->name('employee.payroll.history');

        // Settings routes
        Volt::route('settings/mail', 'settings.mail-configuration')
            ->middleware(['auth', 'permission:manage_settings'])
            ->name('settings.mail');

    });
});

// Public routes for payslip downloads (with authentication)
Route::middleware(['auth'])->group(function () {
    Route::get('payslips/{payslip}/download', [App\Http\Controllers\PayslipController::class, 'download'])
        ->name('payslip.download');
});

require __DIR__.'/auth.php';
