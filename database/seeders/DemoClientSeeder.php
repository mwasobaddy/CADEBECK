<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class DemoClientSeeder extends Seeder
{
    /**
     * The demo client that all seeded HR data belongs to.
     */
    public const SLUG = 'demo-client';

    /**
     * Create the demo client.
     *
     * Runs first in DatabaseSeeder so later seeders can reference the client.
     */
    public function run(): void
    {
        Client::firstOrCreate(
            ['slug' => self::SLUG],
            [
                'name' => 'Demo Client',
                'status' => 'active',
                'contact_email' => 'info@cadebeckhr.com',
                'plan' => 'professional',
                'timezone' => config('app.timezone', 'UTC'),
            ]
        );
    }
}
