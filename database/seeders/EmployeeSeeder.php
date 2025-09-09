<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\User;
use App\Models\Location;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\ContractType;
use Illuminate\Support\Carbon;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereNotIn('id', [1])->get();
        $location = Location::first();
        $branch = Branch::first();
        $department = Department::first();
        $designation = Designation::first();
        $contractType = ContractType::first();

        // First, create all employees without supervisors
        $employees = [];
        foreach ($users as $user) {
            $employee = Employee::firstOrCreate([
                'user_id' => $user->id,
            ], [
                'user_id' => $user->id,
                'date_of_birth' => Carbon::parse('1990-01-01'),
                'gender' => 'male',
                'mobile_number' => '0700000000',
                'home_address' => 'Nairobi',
                'staff_number' => 'EMP' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                'location_id' => $location?->id,
                'branch_id' => $branch?->id,
                'department_id' => $department?->id,
                'designation_id' => $designation?->id,
                'date_of_join' => Carbon::now()->subYears(2),
                'contract_type_id' => $contractType?->id,
                'supervisor_id' => null, // Will be updated later
            ]);
            $employees[$user->id] = $employee;
        }

        // Now update supervisors based on hierarchy
        foreach ($users as $user) {
            $supervisorId = $this->getSupervisorId($user, $users, $employees);
            if ($supervisorId) {
                $employees[$user->id]->update(['supervisor_id' => $supervisorId]);
            }
        }
    }

    /**
     * Get the supervisor ID based on user role hierarchy
     */
    private function getSupervisorId($user, $allUsers, $employees)
    {
        if ($user->hasRole('Employee')) {
            // Employees report to Manager N-2
            $managerN2Users = $allUsers->filter(fn($u) => $u->hasRole('Manager N-2'));
            if ($managerN2Users->isNotEmpty()) {
                $managerN2 = $managerN2Users->random();
                return $employees[$managerN2->id]?->id;
            }
        }

        if ($user->hasRole('Manager N-2')) {
            // Manager N-2 reports to Manager N-1
            $managerN1Users = $allUsers->filter(fn($u) => $u->hasRole('Manager N-1'));
            if ($managerN1Users->isNotEmpty()) {
                $managerN1 = $managerN1Users->random();
                return $employees[$managerN1->id]?->id;
            }
        }

        if ($user->hasRole('Manager N-1')) {
            // Manager N-1 reports to Executive
            $executiveUsers = $allUsers->filter(fn($u) => $u->hasRole('Executive'));
            if ($executiveUsers->isNotEmpty()) {
                $executive = $executiveUsers->first();
                return $employees[$executive->id]?->id;
            }
        }

        // Executives don't have supervisors
        return null;
    }
}
