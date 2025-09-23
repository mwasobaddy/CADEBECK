<?php

use function Livewire\Volt\{layout, state, mount};
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;

layout('components.layouts.app');

new class extends \Livewire\Volt\Component {
    // Listen for wellbeing submission events from the wellbeing manager
    protected $listeners = [
        'wellbeingSubmitted' => 'handleWellbeingSubmitted'
    ];
    public $dashboardData = [];
    public $currentAttendance = null;
    public $todayAttendance = null;
    public $isLoading = false;
    public $locationData = null;
    public $absentEmployees = [];
    public $attendanceStats = [];
    public $surveyCompletedToday = false;
    public $surveyCompletedThisWeek = false;
    public $surveyCompletedThisMonth = false;

    public function mount()
    {
        $user = Auth::user();

        // Load clock attendance data
        $this->loadTodayAttendance();
        $this->getCurrentLocation();
        $this->loadAbsentEmployees();
        $this->loadAttendanceStats();
        $this->checkSurveyStatus();
    }

    public function loadTodayAttendance()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if ($employee) {
            $this->todayAttendance = Attendance::where('employee_id', $employee->id)
                ->where('date', today())
                ->first();

            // Check if currently clocked in
            if ($this->todayAttendance && $this->todayAttendance->clock_in_time && !$this->todayAttendance->clock_out_time) {
                $this->currentAttendance = $this->todayAttendance;
            }
        }
    }

    public function getCurrentLocation()
    {
        // In a real app, you'd get GPS coordinates from the browser
        // For now, we'll just store a placeholder
        $this->locationData = [
            'latitude' => null,
            'longitude' => null,
            'accuracy' => null,
            'timestamp' => now()->timestamp
        ];
    }

    public function loadAbsentEmployees()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            $this->absentEmployees = [];
            return;
        }

        // Get employees based on hierarchical permissions
        $employeeIds = $this->getViewableEmployeeIds();

        if (empty($employeeIds)) {
            $this->absentEmployees = [];
            return;
        }

        // Get employees who haven't clocked in today
        $todayAttendeeIds = Attendance::whereDate('date', today())
            ->whereIn('employee_id', $employeeIds)
            ->whereNotNull('clock_in_time')
            ->pluck('employee_id')
            ->toArray();

        $this->absentEmployees = Employee::active()
            ->whereIn('employees.id', $employeeIds)
            ->whereNotIn('employees.id', $todayAttendeeIds)
            ->with(['user', 'department', 'designation'])
            ->join('users', 'employees.user_id', '=', 'users.id')
            ->orderBy('users.first_name')
            ->select('employees.*')
            ->limit(10) // Limit to 10 for dashboard display
            ->get();
    }

    public function loadAttendanceStats()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            $this->attendanceStats = [];
            return;
        }

        $employeeIds = $this->getViewableEmployeeIds();

        if (empty($employeeIds)) {
            $this->attendanceStats = [];
            return;
        }

        $totalEmployees = count($employeeIds);
        $presentToday = Attendance::whereDate('date', today())
            ->whereIn('employee_id', $employeeIds)
            ->whereNotNull('clock_in_time')
            ->count();

        $absentToday = $totalEmployees - $presentToday;

        // Weekly stats
        $weekStart = now()->startOfWeek();
        $weeklyAttendance = Attendance::whereBetween('date', [$weekStart, now()])
            ->whereIn('employee_id', $employeeIds)
            ->where('status', 'present')
            ->count();

        $this->attendanceStats = [
            'total_employees' => $totalEmployees,
            'present_today' => $presentToday,
            'absent_today' => $absentToday,
            'attendance_rate' => $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100, 1) : 0,
            'weekly_attendance' => $weeklyAttendance,
        ];
    }

    public function checkSurveyStatus()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if ($employee) {
            // Daily: check for a daily assessment submitted for today
            $todaySurvey = \App\Models\WellBeingResponse::where('employee_id', $employee->id)
                ->where('assessment_type', 'daily')
                ->whereDate('period_start_date', today())
                ->exists();

            // Weekly: check for at least one weekly assessment whose period includes today
            $weekStart = Carbon::now()->startOfWeek()->toDateString();
            $weekEnd = Carbon::now()->endOfWeek()->toDateString();
            $weeklySurvey = \App\Models\WellBeingResponse::where('employee_id', $employee->id)
                ->where('assessment_type', 'weekly')
                ->whereBetween('period_start_date', [$weekStart, $weekEnd])
                ->exists();

            // Monthly: check for at least one monthly assessment for current month
            $monthStart = Carbon::now()->startOfMonth()->toDateString();
            $monthEnd = Carbon::now()->endOfMonth()->toDateString();
            $monthlySurvey = \App\Models\WellBeingResponse::where('employee_id', $employee->id)
                ->where('assessment_type', 'monthly')
                ->whereBetween('period_start_date', [$monthStart, $monthEnd])
                ->exists();

            $this->surveyCompletedToday = $todaySurvey;
            $this->surveyCompletedThisWeek = $weeklySurvey;
            $this->surveyCompletedThisMonth = $monthlySurvey;
        }
    }

    public function getViewableEmployeeIds()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return [];
        }

        // Executive and Developer can see all employees
        if ($user->can('view_other_attendance') && ($user->hasRole(['Executive', 'Developer']))) {
            return Employee::active()->pluck('id')->toArray();
        }

        // Manager N-1 can see employees and Manager N-2 they supervise
        if ($user->hasRole('Manager N-1')) {
            $subordinateIds = $this->getAllSubordinateIds($employee->id);
            return array_merge([$employee->id], $subordinateIds);
        }

        // Manager N-2 can see employees they directly supervise
        if ($user->hasRole('Manager N-2')) {
            $subordinateIds = $this->getDirectSubordinateIds($employee->id);
            return array_merge([$employee->id], $subordinateIds);
        }

        // Regular employees can only see themselves
        return [$employee->id];
    }

    private function getDirectSubordinateIds($employeeId)
    {
        return Employee::where('supervisor_id', $employeeId)
            ->active()
            ->pluck('id')
            ->toArray();
    }

    private function getAllSubordinateIds($employeeId)
    {
        $subordinates = [];
        $directSubordinates = $this->getDirectSubordinateIds($employeeId);

        foreach ($directSubordinates as $subId) {
            $subordinates[] = $subId;
            // Recursively get subordinates of subordinates
            $subordinates = array_merge($subordinates, $this->getAllSubordinateIds($subId));
        }

        return array_unique($subordinates);
    }

    public function clockIn()
    {
        $this->isLoading = true;

        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Employee record not found. Please contact HR.'
            ]);
            $this->isLoading = false;
            return;
        }

        // Check if employee has completed today's wellbeing survey
        // Re-check all required assessments (daily, weekly, monthly) before clock-in
        $this->checkSurveyStatus();

        if (!($this->surveyCompletedToday && $this->surveyCompletedThisWeek && $this->surveyCompletedThisMonth)) {
            $missing = [];
            if (!$this->surveyCompletedToday) $missing[] = 'daily';
            if (!$this->surveyCompletedThisWeek) $missing[] = 'weekly';
            if (!$this->surveyCompletedThisMonth) $missing[] = 'monthly';

            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Please complete the following well-being assessments before clocking in: ' . implode(', ', $missing) . '.'
            ]);

            $this->isLoading = false;
            return;
        }

        // Check if already clocked in today
        $existingAttendance = Attendance::where('employee_id', $employee->id)
            ->where('date', today())
            ->first();

        if ($existingAttendance && $existingAttendance->clock_in_time) {
            $this->dispatch('notify', [
                'type' => 'info',
                'message' => 'You are already clocked in for today.'
            ]);
            $this->isLoading = false;
            return;
        }

        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'date' => today(),
            'clock_in_time' => now(),
            'status' => 'present',
            'location_data' => $this->locationData,
        ]);

        $this->currentAttendance = $attendance;
        $this->todayAttendance = $attendance;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Successfully clocked in at ' . now()->format('H:i')
        ]);

        $this->isLoading = false;
    }

    public function clockOut()
    {
        $this->isLoading = true;

        if (!$this->currentAttendance) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No active clock-in session found.'
            ]);
            $this->isLoading = false;
            return;
        }

        $clockOutTime = now();
        $totalHours = $this->currentAttendance->clock_in_time->diffInMinutes($clockOutTime) / 60;

        $this->currentAttendance->update([
            'clock_out_time' => $clockOutTime,
            'total_hours' => round($totalHours, 2),
            'location_data' => array_merge($this->currentAttendance->location_data ?? [], [
                'clock_out_location' => $this->locationData
            ])
        ]);

        $this->currentAttendance = null;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Successfully clocked out. Total hours: ' . round($totalHours, 2)
        ]);

        $this->isLoading = false;
    }

    public function getStatusText()
    {
        if ($this->currentAttendance) {
            return 'Currently clocked in since ' . $this->currentAttendance->clock_in_time->format('H:i');
        }

        if ($this->todayAttendance && $this->todayAttendance->clock_out_time) {
            return 'Clocked out at ' . $this->todayAttendance->clock_out_time->format('H:i') .
                   ' (' . $this->todayAttendance->total_hours . ' hours)';
        }

        return 'Not clocked in today';
    }

    public function getStatusColor()
    {
        if ($this->currentAttendance) {
            return 'green';
        }

        if ($this->todayAttendance && $this->todayAttendance->clock_out_time) {
            return 'yellow';
        }

        return 'gray';
    }

    // Called when another component emits a wellbeingSubmitted event
    public function handleWellbeingSubmitted()
    {
        // Refresh survey status and related attendance data so the UI updates immediately
        $this->checkSurveyStatus();
        $this->loadTodayAttendance();
        $this->loadAttendanceStats();
        $this->loadAbsentEmployees();
    }
};
?>

<div class="relative max-w-6xl mx-auto md:px-4 md:py-8">
    <!-- SVG Blobs Background -->
    <svg class="fixed -top-24 right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0" viewBox="0 0 400 400"
        fill="none">
        <ellipse cx="200" cy="200" rx="180" ry="120" fill="url(#blob1)" />
        <defs>
            <radialGradient id="blob1" cx="0" cy="0" r="1"
                gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                <stop stop-color="#38bdf8" />
                <stop offset="1" stop-color="#6366f1" />
            </radialGradient>
        </defs>
    </svg>
    <svg class="fixed -bottom-24 -right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0"
        viewBox="0 0 400 400" fill="none">
        <ellipse cx="200" cy="200" rx="160" ry="100" fill="url(#blob2)" />
        <defs>
            <radialGradient id="blob2" cx="0" cy="0" r="1"
                gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                <stop stop-color="#34d399" />
                <stop offset="1" stop-color="#f472b6" />
            </radialGradient>
        </defs>
    </svg>

    <!-- Welcome Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            {{ __('Welcome back, ') . Auth::user()->first_name }}!
        </h1>
        <p class="text-gray-600 dark:text-gray-300 mt-2">
            {{ 'Here\'s your HR dashboard overview for today.' }}
        </p>
    </div>

    <!-- Clock In/Out Section -->
    <div class="mb-8">
        <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
            <div class="flex items-center gap-3 mb-6">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                    <polyline points="12,6 12,12 16,14"></polyline>
                </svg>
                <h2 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500">
                    {{ 'Clock In/Out' }}
                </h2>
            </div>

            <div class="space-y-6">
                <!-- Status Card -->
                <div class="bg-gradient-to-r from-{{ $this->getStatusColor() }}-50 to-{{ $this->getStatusColor() }}-100 dark:from-{{ $this->getStatusColor() }}-900/20 dark:to-{{ $this->getStatusColor() }}-800/20 rounded-xl p-6 border border-{{ $this->getStatusColor() }}-200 dark:border-{{ $this->getStatusColor() }}-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-{{ $this->getStatusColor() }}-800 dark:text-{{ $this->getStatusColor() }}-200">
                                {{ 'Current Status' }}
                            </h3>
                            <p class="text-{{ $this->getStatusColor() }}-600 dark:text-{{ $this->getStatusColor() }}-300 mt-1">
                                {{ $this->getStatusText() }}
                            </p>
                        </div>
                        <div class="text-{{ $this->getStatusColor() }}-500">
                            @if($this->currentAttendance)
                                <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                                    <circle cx="12" cy="12" r="6" fill="currentColor"></circle>
                                </svg>
                            @else
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                                </svg>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Wellbeing Survey Status -->
                @php
                    $all_assessments_done = $this->surveyCompletedToday && $this->surveyCompletedThisWeek && $this->surveyCompletedThisMonth;
                    $missing = [];
                    if (!$this->surveyCompletedToday) $missing[] = 'Daily';
                    if (!$this->surveyCompletedThisWeek) $missing[] = 'Weekly';
                    if (!$this->surveyCompletedThisMonth) $missing[] = 'Monthly';
                @endphp

                @if(!$all_assessments_done)
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-amber-100 dark:bg-amber-800 rounded-lg">
                                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-amber-800 dark:text-amber-200">{{ 'Well-being Assessments Required' }}</h4>
                                    <p class="text-sm text-amber-600 dark:text-amber-300">{{ 'Please complete the following assessments before clocking in:' }}</p>
                                    <ul class="mt-2 text-sm text-amber-700 dark:text-amber-300 list-disc ml-5">
                                        @foreach($missing as $m)
                                            <li>{{ $m }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-xl p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-green-100 dark:bg-green-800 rounded-lg">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-green-800 dark:text-green-200">{{ 'All Well-being Assessments Completed' }}</h4>
                                <p class="text-sm text-green-600 dark:text-green-300">{{ 'Thank you — you may now clock in.' }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Action Buttons -->
                <div class="flex gap-4">
                    @php $can_clock_in = $this->surveyCompletedToday && $this->surveyCompletedThisWeek && $this->surveyCompletedThisMonth; @endphp
                    @if(!$this->currentAttendance)
                        <button
                            wire:click="clockIn"
                            wire:loading.attr="disabled"
                            wire:target="clockIn"
                            @if(!$can_clock_in) disabled @endif
                            class="flex-1 @if($can_clock_in) bg-green-600 hover:bg-green-700 disabled:bg-green-400 @else bg-gray-400 cursor-not-allowed @endif text-white px-6 py-3 rounded-xl font-semibold shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            <span wire:loading.remove wire:target="clockIn">{{ 'Clock In' }}</span>
                            <span wire:loading wire:target="clockIn">{{ 'Clocking In...' }}</span>
                        </button>
                    @else
                        <button
                            wire:click="clockOut"
                            wire:loading.attr="disabled"
                            class="flex-1 bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white px-6 py-3 rounded-xl font-semibold shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            <span wire:loading.remove>{{ 'Clock Out' }}</span>
                            <span wire:loading>{{ 'Clocking Out...' }}</span>
                        </button>
                    @endif
                </div>

                <!-- Today's Summary -->
                @if($this->todayAttendance)
                    <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">{{ 'Today\'s Summary' }}</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            @if($this->todayAttendance->clock_in_time)
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">{{ 'Clock In:' }}</span>
                                    <span class="font-medium ml-2">{{ $this->todayAttendance->clock_in_time->format('H:i') }}</span>
                                </div>
                            @endif
                            @if($this->todayAttendance->clock_out_time)
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">{{ 'Clock Out:' }}</span>
                                    <span class="font-medium ml-2">{{ $this->todayAttendance->clock_out_time->format('H:i') }}</span>
                                </div>
                            @endif
                            @if($this->todayAttendance->total_hours)
                                <div class="col-span-2">
                                    <span class="text-gray-600 dark:text-gray-400">{{ 'Total Hours:' }}</span>
                                    <span class="font-medium ml-2">{{ $this->todayAttendance->total_hours }} hours</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Attendance Overview Section -->
    @if(count($this->attendanceStats) > 0)
    <div class="mb-8">
        <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
            <div class="flex items-center gap-3 mb-6">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <h2 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500">
                    {{ 'Team Attendance Overview' }}
                </h2>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-4 border border-green-200 dark:border-green-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-green-600 dark:text-green-400">Present Today</p>
                            <p class="text-2xl font-bold text-green-800 dark:text-green-200">{{ $this->attendanceStats['present_today'] }}</p>
                        </div>
                        <div class="p-2 bg-green-200 dark:bg-green-800 rounded-lg">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-xl p-4 border border-red-200 dark:border-red-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-red-600 dark:text-red-400">Absent Today</p>
                            <p class="text-2xl font-bold text-red-800 dark:text-red-200">{{ $this->attendanceStats['absent_today'] }}</p>
                        </div>
                        <div class="p-2 bg-red-200 dark:bg-red-800 rounded-lg">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-4 border border-blue-200 dark:border-blue-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Team</p>
                            <p class="text-2xl font-bold text-blue-800 dark:text-blue-200">{{ $this->attendanceStats['total_employees'] }}</p>
                        </div>
                        <div class="p-2 bg-blue-200 dark:bg-blue-800 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl p-4 border border-purple-200 dark:border-purple-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Attendance Rate</p>
                            <p class="text-2xl font-bold text-purple-800 dark:text-purple-200">{{ $this->attendanceStats['attendance_rate'] }}%</p>
                        </div>
                        <div class="p-2 bg-purple-200 dark:bg-purple-800 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Absent Employees List -->
            @if(count($this->absentEmployees) > 0)
            <div class="bg-red-50/50 dark:bg-red-900/10 rounded-xl p-4">
                <h3 class="text-lg font-semibold text-red-800 dark:text-red-300 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    {{ 'Employees Not Clocked In Today' }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($this->absentEmployees as $employee)
                    <div class="bg-white/80 dark:bg-zinc-800/80 rounded-lg p-3 border border-red-200 dark:border-red-700/50">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 h-8 w-8">
                                <div class="h-8 w-8 rounded-full bg-gradient-to-br from-red-400 to-red-500 flex items-center justify-center text-white text-sm font-semibold">
                                    {{ substr($employee->user->first_name, 0, 1) }}{{ substr($employee->user->other_names, 0, 1) }}
                                </div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $employee->user->first_name }} {{ $employee->user->other_names }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ $employee->department->name ?? 'No Department' }} • {{ $employee->designation->name ?? 'No Designation' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @if(count($this->absentEmployees) >= 10)
                <div class="mt-4 text-center">
                    <a href="{{ route('attendance.manage') }}" 
                       class="inline-flex items-center gap-2 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 font-medium text-sm transition-colors"
                       wire:navigate>
                        {{ 'View All Attendance Records' }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
                @endif
            </div>
            @else
            <div class="bg-green-50/50 dark:bg-green-900/10 rounded-xl p-4 text-center">
                <svg class="w-12 h-12 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-green-800 dark:text-green-300 font-medium">{{ 'All team members have clocked in today!' }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif
    
    <!-- Right Column - Well-being -->
    <div class="lg:col-span-1">
        <livewire:wellbeing.wellbeing-manager />
    </div>
</div>