<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds 50 users and assigns the "New Employee" role.
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 50; $i++) {
            $user = User::create([
                'first_name' => 'Employee' . $i,
                'other_names' => 'Test',
                'email' => "employee{$i}@cadebeck.test",
                'email_verified_at' => now(),
                'password' => Hash::make('Password123!'),
            ]);

            // Assign "New Employee" role using Spatie
            $user->assignRole('New Employee');
        }
    }
}
