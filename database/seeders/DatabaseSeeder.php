<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            // Must run first: creates the demo client the rest of the data belongs to.
            DemoClientSeeder::class,
            RolesAndPermissionsSeeder::class,
            PlatformPermissionsSeeder::class,
            JobAdvertSeeder::class,
            LocationSeeder::class,
            BranchSeeder::class,
            DepartmentSeeder::class,
            DesignationSeeder::class,
            ContractTypeSeeder::class,
            UserSeeder::class,
            EmployeeSeeder::class,
            PayrollSeeder::class,
            LeaveRequestSeeder::class,
            ApplicationSeeder::class,
            AttendanceSeeder::class,
            WellBeingResponseSeeder::class,
            // Must run last: stamps client_id across everything seeded above.
            AssignDemoClientSeeder::class,
        ]);
    }
}
