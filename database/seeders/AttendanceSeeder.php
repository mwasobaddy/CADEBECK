<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::all();

        if ($employees->isEmpty()) {
            $this->command->info('No employees found. Please run EmployeeSeeder first.');
            return;
        }

        $this->command->info('Creating attendance records for the past 30 days...');

        $created = 0;
        $startDate = Carbon::now()->subDays(30);

        for ($day = 0; $day < 30; $day++) {
            $currentDate = $startDate->copy()->addDays($day);

            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($currentDate->dayOfWeek === 0 || $currentDate->dayOfWeek === 6) {
                continue;
            }

            foreach ($employees as $employee) {
                // 90% chance of attendance (simulating real-world attendance)
                if (rand(1, 100) <= 90) {
                    $clockInHour = rand(8, 10); // 8-10 AM
                    $clockInMinute = rand(0, 59);
                    $clockInTime = $currentDate->copy()->setTime($clockInHour, $clockInMinute);

                    $workHours = rand(6, 10); // 6-10 hours
                    $clockOutTime = $clockInTime->copy()->addHours($workHours)->addMinutes(rand(0, 59));

                    // Sometimes people forget to clock out (5% chance)
                    $actuallyClockedOut = rand(1, 100) <= 95;

                    Attendance::create([
                        'employee_id' => $employee->id,
                        'user_id' => $employee->user_id,
                        'date' => $currentDate,
                        'clock_in_time' => $clockInTime,
                        'clock_out_time' => $actuallyClockedOut ? $clockOutTime : null,
                        'total_hours' => $actuallyClockedOut ? round($clockInTime->diffInMinutes($clockOutTime) / 60, 2) : null,
                        'status' => $actuallyClockedOut ? 'present' : 'present', // Still present if clocked in
                        'notes' => $this->getRandomNote(),
                        'location_data' => [
                            'latitude' => -1.2864 + (rand(-100, 100) / 10000), // Nairobi area
                            'longitude' => 36.8172 + (rand(-100, 100) / 10000),
                            'accuracy' => rand(5, 50),
                            'timestamp' => $clockInTime->timestamp
                        ]
                    ]);

                    $created++;
                } else {
                    // Absent record
                    Attendance::create([
                        'employee_id' => $employee->id,
                        'user_id' => $employee->user_id,
                        'date' => $currentDate,
                        'status' => 'absent',
                        'notes' => 'Absent',
                    ]);

                    $created++;
                }
            }
        }

        $this->command->info("Created {$created} attendance records.");
    }

    private function getRandomNote(): ?string
    {
        $notes = [
            null,
            'Working from home',
            'Client meeting',
            'Training session',
            'Overtime work',
            'Late start due to traffic',
            'Early departure for personal reasons',
            'Team meeting',
            'Project deadline',
        ];

        return $notes[array_rand($notes)];
    }
}
