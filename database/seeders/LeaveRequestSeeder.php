<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LeaveRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leave_types = ['annual', 'sick', 'maternity', 'paternity', 'emergency', 'unpaid'];
        $statuses = ['pending', 'approved', 'rejected', 'cancelled'];
        $users = User::pluck('id')->toArray();
        $employees = Employee::pluck('id', 'user_id');

        // 11 leave requests for user_id 1
        for ($i = 0; $i < 11; $i++) {
            $user_id = 1;
            $employee_id = $employees[$user_id] ?? Employee::inRandomOrder()->first()->id;
            $type = $leave_types[array_rand($leave_types)];
            $start = Carbon::now()->subDays(rand(0, 60));
            $end = (clone $start)->addDays(rand(1, 10));
            $days = $start->diffInWeekdays($end) + 1;
            LeaveRequest::create([
                'employee_id' => $employee_id,
                'user_id' => $user_id,
                'leave_type' => $type,
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'days_requested' => $days,
                'reason' => 'Seeder reason for user 1 - ' . Str::random(20),
                'status' => $statuses[array_rand($statuses)],
            ]);
        }

        // 19 leave requests for random users
        for ($i = 0; $i < 19; $i++) {
            $user_id = collect($users)->filter(fn($id) => $id !== 1)->random();
            $employee_id = $employees[$user_id] ?? Employee::inRandomOrder()->first()->id;
            $type = $leave_types[array_rand($leave_types)];
            $start = Carbon::now()->subDays(rand(0, 60));
            $end = (clone $start)->addDays(rand(1, 10));
            $days = $start->diffInWeekdays($end) + 1;
            LeaveRequest::create([
                'employee_id' => $employee_id,
                'user_id' => $user_id,
                'leave_type' => $type,
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'days_requested' => $days,
                'reason' => 'Seeder reason for user ' . $user_id . ' - ' . Str::random(20),
                'status' => $statuses[array_rand($statuses)],
            ]);
        }
    }
}
