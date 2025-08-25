<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Branch;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $nairobiBranch = Branch::where('code', 'NBO-01')->first();
        $mombasaBranch = Branch::where('code', 'MSA-01')->first();
        Department::firstOrCreate([
            'code' => 'HR-NBO',
        ], [
            'name' => 'Human Resources',
            'branch_id' => $nairobiBranch?->id,
        ]);
        Department::firstOrCreate([
            'code' => 'FIN-MSA',
        ], [
            'name' => 'Finance',
            'branch_id' => $mombasaBranch?->id,
        ]);
    }
}
