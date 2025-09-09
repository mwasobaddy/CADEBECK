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

            'mark_attendance',

            'manage_my_leave',
            'apply_for_leave',
            'edit_my_leave',
            'delete_my_leave',
            'export_my_leave',

            'manage_all_leaves',
            'edit_all_leaves',
            'view_all_leaves',
            'delete_all_leaves',
            'export_all_leaves',

            'manage_settings',
            'process_payroll',
            "view_my_payslips",


            'manage_other_attendance',
            'edit_other_attendance',
            'delete_other_attendance',
            'view_other_attendance',

            'manage_own_attendance',
            'mark_attendance',
            'edit_own_attendance',
            'delete_own_attendance',
            'view_own_attendance',

            // Developer
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
            'Developer' => [
                // All permissions
                $permissions,
            ],
            'Executive' => [
                // Executive permissions
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

                    'manage_my_leave',
                    'apply_for_leave',
                    'edit_my_leave',
                    'delete_my_leave',
                    'export_my_leave',

                    'manage_all_leaves',
                    'edit_all_leaves',
                    'delete_all_leaves',
                    'export_all_leaves',

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
            'Manager N-1' => [
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

                    'manage_my_leave',
                    'apply_for_leave',
                    'edit_my_leave',
                    'delete_my_leave',
                    'export_my_leave',

                    'manage_all_leaves',
                    'edit_all_leaves',
                    'delete_all_leaves',
                    'export_all_leaves',

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
            'Manager N-2' => [
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

                    'manage_my_leave',
                    'apply_for_leave',
                    'edit_my_leave',
                    'delete_my_leave',
                    'export_my_leave',

                    'manage_all_leaves',
                    'edit_all_leaves',
                    'delete_all_leaves',
                    'export_all_leaves',

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
            'Employee' => [
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
                'first_name' => 'Technical',
                'other_names' => 'Developer',
                'email' => 'kelvinramsiel@gmail.com',
                'password' => Hash::make('kelvin1234'),
                'role' => 'Developer',
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
