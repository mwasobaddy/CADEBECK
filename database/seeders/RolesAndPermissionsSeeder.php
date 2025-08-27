<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions
        $permissions = [
            'create_job_advert',
            'edit_job_advert',
            'delete_job_advert',
            'manage_job_advert',
            'export_job_advert',
            'import_job_advert',
            'vet_candidate',
            'view_applications',
            'apply_job_advert',

            'create_user',
            'edit_user',
            'delete_user',
            'manage_user',
            'export_user',
            'import_user',

            'manage_employee',
            'create_employee',
            'edit_employee',
            'delete_employee',
            'export_employee',
            'import_employee',

            'manage_location',
            'create_location',
            'edit_location',
            'delete_location',
            'export_location',
            'import_location',

            'manage_branch',
            'create_branch',
            'edit_branch',
            'delete_branch',
            'export_branch',
            'import_branch',

            'manage_department',
            'create_department',
            'edit_department',
            'delete_department',
            'export_department',
            'import_department',

            'manage_designation',
            'create_designation',
            'edit_designation',
            'delete_designation',
            'export_designation',
            'import_designation',

            'manage_role',
            'create_role',
            'edit_role',
            'delete_role',
            'export_role',
            'import_role',

            'manage_permission',
            'create_permission',
            'edit_permission',
            'delete_permission',
            'export_permission',
            'import_permission',

            'manage_other_leave_requests',
            'edit_other_leave_requests',
            'delete_other_leave_requests',
            'view_other_leave_requests',

            'manage_own_leave_requests',
            'create_own_leave_requests',
            'edit_own_leave_requests',
            'delete_own_leave_requests',
            'view_own_leave_requests',


            'manage_other_attendance',
            'edit_other_attendance',
            'delete_other_attendance',
            'view_other_attendance',

            'manage_own_attendance',
            'mark_attendance',
            'edit_own_attendance',
            'delete_own_attendance',
            'view_own_attendance',

            // Super Administrator
            'full_system_access',
            'create_onboarding_templates',
            'modify_onboarding_templates',
            'manage_all_users',
            'view_all_onboarding_processes',
            'system_configuration',
            'language_settings',
            'access_all_modules',
            'access_all_reports',
            'manage_payroll_integrations',
            'manage_external_sources',
            // HR Administrator
            'create_onboarding_workflows',
            'manage_onboarding_workflows',
            'monitor_onboarding_progress',
            'manage_document_templates',
            'create_branch',
            'modify_branch',
            'create_department',
            'modify_department',
            'create_new_employee',
            'modify_new_employee',
            'assign_role',
            'manage_leave_requests',
            'manage_attendance',
            'process_payroll',
            'generate_payslips',
            'manage_performance_reviews',
            'initiate_hiring_processes',
            'create_policies',
            'create_orientation_materials',
            'access_stress_monitoring_reports',
            'access_wellbeing_reports',
            'configure_external_payslip_sources',
            // New Employee
            'login',
            'check_in',
            'check_out',
            'upload_documents',
            'access_orientation_materials',
            'submit_feedback_forms',
            'request_leave',
            'view_leave_balance',
            'participate_performance_reviews',
            'view_payslips',
            'download_payslips',
            'access_stress_monitoring_tools',
            'complete_wellbeing_surveys',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Roles
        $roles = [
            'Super Administrator' => [
                // All permissions
                $permissions,
            ],
            'HR Administrator' => [
                // HR Admin permissions
                [
                    'create_job_advert',
                    'manage_job_advert',
                    'vet_candidate',
                    'view_applications',
                    'apply_job_advert',

                    'manage_employee',
                    'create_employee',
                    'edit_employee',
                    'delete_employee',

                    'manage_branch',
                    'create_branch',
                    'edit_branch',
                    'delete_branch',

                    'manage_department',
                    'create_department',
                    'edit_department',
                    'delete_department',

                    'manage_role',
                    'create_role',
                    'edit_role',
                    'delete_role',

                    'manage_permission',
                    'create_permission',
                    'edit_permission',
                    'delete_permission',

                    'manage_other_leave_requests',
                    'edit_other_leave_requests',
                    'delete_other_leave_requests',
                    'view_other_leave_requests',

                    'manage_own_leave_requests',
                    'create_own_leave_requests',
                    'edit_own_leave_requests',
                    'delete_own_leave_requests',
                    'view_own_leave_requests',


                    'manage_other_attendance',
                    'edit_other_attendance',
                    'delete_other_attendance',
                    'view_other_attendance',

                    'manage_own_attendance',
                    'mark_attendance',
                    'edit_own_attendance',
                    'delete_own_attendance',
                    'view_own_attendance',

                    'process_payroll',
                    'generate_payslips',
                    'manage_performance_reviews',
                    'initiate_hiring_processes',
                    'create_policies',
                    'create_orientation_materials',
                    'access_stress_monitoring_reports',
                    'access_wellbeing_reports',
                    'configure_external_payslip_sources',
                ],
            ],
            'New Employee' => [
                [
                    'login',
                    'check_in',
                    'check_out',
                    'upload_documents',
                    'access_orientation_materials',
                    'submit_feedback_forms',
                    'request_leave',
                    'view_leave_balance',
                    'participate_performance_reviews',
                    'view_payslips',
                    'download_payslips',
                    'access_stress_monitoring_tools',
                    'complete_wellbeing_surveys',
                ],
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            foreach ($rolePermissions as $perms) {
                $role->syncPermissions($perms);
            }
        }

        // Create demo users for each role
        $users = [
            [
                'first_name' => 'Super',
                'other_names' => 'Admin',
                'email' => 'kelvinramsiel@gmail.com',
                'password' => Hash::make('kelvin1234'),
                'role' => 'Super Administrator',
            ],
            [
                'first_name' => 'HR',
                'other_names' => 'Admin',
                'email' => 'hradmin@cadebeck.com',
                'password' => Hash::make('HRAdmin123!'),
                'role' => 'HR Administrator',
            ],
            [
                'first_name' => 'New',
                'other_names' => 'Employee',
                'email' => 'employee@cadebeck.com',
                'password' => Hash::make('Employee123!'),
                'role' => 'New Employee',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate([
                'email' => $userData['email'],
            ], [
                'first_name' => $userData['first_name'],
                'other_names' => $userData['other_names'],
                'password' => $userData['password'],
            ]);
            $user->assignRole($userData['role']);
        }
    }
}
