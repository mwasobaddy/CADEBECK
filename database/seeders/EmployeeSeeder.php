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
        $users = User::all();
        $location = Location::first();
        $branch = Branch::first();
        $department = Department::first();
        $designation = Designation::first();
        $contractType = ContractType::first();

        foreach ($users as $user) {
            Employee::firstOrCreate([
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
            ]);
        }
    }
}
