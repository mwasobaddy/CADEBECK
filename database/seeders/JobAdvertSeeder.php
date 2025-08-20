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
                'description' => 'This is a sample description for Job Position ' . $i . '. Responsibilities include managing daily operations, collaborating with cross-functional teams, and ensuring compliance with company policies. Candidates should possess excellent communication skills, relevant academic qualifications, and a minimum of 3 years experience in a similar role. Additional requirements include proficiency in computer applications, ability to work under pressure, and strong problem-solving skills. Successful applicants will participate in onboarding, receive training, and contribute to organizational growth. Apply before the deadline.',
                'deadline' => now()->addDays(rand(5, 60)),
                'status' => 'Published',
                'posted_by' => $users[array_rand($users)],
            ]);
        }
    }
}
