<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractType;

class ContractTypeSeeder extends Seeder
{
    public function run(): void
    {
        ContractType::firstOrCreate([
            'code' => 'PERM',
        ], [
            'name' => 'Permanent',
            'description' => 'Permanent employment contract',
        ]);
        ContractType::firstOrCreate([
            'code' => 'TEMP',
        ], [
            'name' => 'Temporary',
            'description' => 'Temporary employment contract',
        ]);
        ContractType::firstOrCreate([
            'code' => 'CONT',
        ], [
            'name' => 'Contract',
            'description' => 'Fixed-term contract',
        ]);
        ContractType::firstOrCreate([
            'code' => 'INTRN',
        ], [
            'name' => 'Internship',
            'description' => 'Internship contract',
        ]);
    }
}
