<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        Location::firstOrCreate([
            'code' => 'NBO',
        ], [
            'name' => 'Nairobi',
            'address' => 'Nairobi CBD',
            'city' => 'Nairobi',
            'country' => 'Kenya',
        ]);
        Location::firstOrCreate([
            'code' => 'MSA',
        ], [
            'name' => 'Mombasa',
            'address' => 'Mombasa CBD',
            'city' => 'Mombasa',
            'country' => 'Kenya',
        ]);
    }
}
