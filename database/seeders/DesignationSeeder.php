<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Designation;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        Designation::firstOrCreate([
            'code' => 'HRM',
        ], [
            'name' => 'HR Manager',
            'description' => 'Responsible for HR operations',
        ]);
        Designation::firstOrCreate([
            'code' => 'FINA',
        ], [
            'name' => 'Finance Analyst',
            'description' => 'Responsible for financial analysis',
        ]);
    }
}
