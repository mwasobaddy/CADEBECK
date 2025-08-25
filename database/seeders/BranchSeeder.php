<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Location;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $nairobi = Location::where('code', 'NBO')->first();
        $mombasa = Location::where('code', 'MSA')->first();
        Branch::firstOrCreate([
            'code' => 'NBO-01',
        ], [
            'name' => 'Nairobi Main Branch',
            'location_id' => $nairobi?->id,
        ]);
        Branch::firstOrCreate([
            'code' => 'MSA-01',
        ], [
            'name' => 'Mombasa Main Branch',
            'location_id' => $mombasa?->id,
        ]);
    }
}
