<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobAdvert;
use App\Models\User;
use Illuminate\Support\Str;

class JobAdvertSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::pluck('id')->all();
        if (empty($users)) {
            // If no users exist, create one Super Admin for context
            $user = User::factory()->create(['name' => 'Super Admin', 'email' => 'admin@example.com']);
            $users = [$user->id];
        }

        foreach (range(1, 50) as $i) {
            JobAdvert::create([
                'title' => 'Job Position ' . $i,
                'slug' => Str::slug('Job Position ' . $i . '-' . Str::random(5)),
                'description' => 'This is a sample description for Job Position ' . $i . '. Responsibilities include... Requirements include... Apply before the deadline.',
                'deadline' => now()->addDays(rand(5, 60)),
                'status' => 'Published',
                'posted_by' => $users[array_rand($users)],
            ]);
        }
    }
}
