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
            'vet_candidate',
            'view_applications',
            'apply_job_advert',

            'manage_employees',
            'create_employees',
            'edit_employees',
            'delete_employees',
            'view_employees',

            'manage_branches',
            'create_branches',
            'edit_branches',
            'delete_branches',
            'view_branches',

            'manage_departments',
            'create_departments',
            'edit_departments',
            'delete_departments',
            'view_departments',

            'manage_roles',
            'create_roles',
            'edit_roles',
            'delete_roles',
            'view_roles',

            'manage_permissions',
            'create_permissions',
            'edit_permissions',
            'delete_permissions',
            'view_permissions',

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
            'create_branches',
            'modify_branches',
            'create_departments',
            'modify_departments',
            'create_new_employees',
            'modify_new_employees',
            'assign_roles',
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

                    'manage_employees',
                    'create_employees',
                    'edit_employees',
                    'delete_employees',
                    'view_employees',

                    'manage_branches',
                    'create_branches',
                    'edit_branches',
                    'delete_branches',
                    'view_branches',

                    'manage_departments',
                    'create_departments',
                    'edit_departments',
                    'delete_departments',
                    'view_departments',

                    'manage_roles',
                    'create_roles',
                    'edit_roles',
                    'delete_roles',
                    'view_roles',

                    'manage_permissions',
                    'create_permissions',
                    'edit_permissions',
                    'delete_permissions',
                    'view_permissions',

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
                'name' => 'Super Admin',
                'email' => 'kelvinramsiel@gmail.com',
                'password' => Hash::make('kelvin1234'),
                'role' => 'Super Administrator',
            ],
            [
                'name' => 'HR Admin',
                'email' => 'hradmin@cadebeck.com',
                'password' => Hash::make('HRAdmin123!'),
                'role' => 'HR Administrator',
            ],
            [
                'name' => 'New Employee',
                'email' => 'employee@cadebeck.com',
                'password' => Hash::make('Employee123!'),
                'role' => 'New Employee',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate([
                'email' => $userData['email'],
            ], [
                'name' => $userData['name'],
                'password' => $userData['password'],
            ]);
            $user->assignRole($userData['role']);
        }
    }
}
