<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Services\ClientContext;
use Illuminate\Database\Seeder;

class DemoClientSeeder extends Seeder
{
    /**
     * The demo client that all seeded HR data belongs to.
     */
    public const SLUG = 'demo-client';

    /**
     * Create the demo client and make it the current context.
     *
     * Runs first in DatabaseSeeder so every later seeder writes inside a client
     * context, exactly as a request from that client would. client_id is NOT
     * NULL on the client-owned tables, so seeding outside a context would fail.
     */
    public function run(): void
    {
        $client = Client::firstOrCreate(
            ['slug' => self::SLUG],
            [
                'name' => 'Demo Client',
                'status' => 'active',
                'contact_email' => 'info@cadebeckhr.com',
                'plan' => 'professional',
                'timezone' => config('app.timezone', 'UTC'),
            ]
        );

        ClientContext::override($client->id);
    }
}
