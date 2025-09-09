<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds users and assigns roles: 2 "Manager N-1", 4 "Manager N-2", rest "Employee".
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 1 Executive user
        $executive = User::create([
            'first_name' => 'Executive',
            'other_names' => 'Test',
            'email' => 'executive@cadebeck.test',
            'email_verified_at' => now(),
            'password' => Hash::make('Password123!'),
        ]);
        $executive->assignRole('Executive');

        // Create 2 users with "Manager N-1" role
        for ($i = 1; $i <= 2; $i++) {
            $user = User::create([
                'first_name' => 'ManagerN1_' . $i,
                'other_names' => 'Test',
                'email' => "managern1_{$i}@cadebeck.test",
                'email_verified_at' => now(),
                'password' => Hash::make('Password123!'),
            ]);
            $user->assignRole('Manager N-1');
        }

        // Create 4 users with "Manager N-2" role
        for ($i = 1; $i <= 4; $i++) {
            $user = User::create([
                'first_name' => 'ManagerN2_' . $i,
                'other_names' => 'Test',
                'email' => "managern2_{$i}@cadebeck.test",
                'email_verified_at' => now(),
                'password' => Hash::make('Password123!'),
            ]);
            $user->assignRole('Manager N-2');
        }

        // Create remaining 44 users with "Employee" role
        for ($i = 1; $i <= 44; $i++) {
            $user = User::create([
                'first_name' => 'Employee' . $i,
                'other_names' => 'Test',
                'email' => "employee{$i}@cadebeck.test",
                'email_verified_at' => now(),
                'password' => Hash::make('Password123!'),
            ]);
            $user->assignRole('Employee');
        }
    }
}
