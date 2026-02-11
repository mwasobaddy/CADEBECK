<?php

namespace Database\Seeders;

use App\Models\WellBeingResponse;
use Illuminate\Database\Seeder;

class WellBeingResponseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        WellBeingResponse::factory(50)->create();
    }
}
