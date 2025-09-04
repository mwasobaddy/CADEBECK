<?php

use function Livewire\Volt\{layout, state, mount};
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;

layout('components.layouts.app');

new class extends \Livewire\Volt\Component {
    public $dashboardData = [];
    public $currentAttendance = null;
    public $todayAttendance = null;
    public $isLoading = false;
    public $locationData = null;

    public function mount()
    {
        $user = Auth::user();
        $data = [];

        if ($user->can('full_system_access')) {
            $data['system_stats'] = 'All system stats';
            $data['onboarding'] = 'All onboarding templates';
            $data['users'] = 'All users';
            $data['reports'] = 'All reports';
            $data['payroll'] = 'Payroll integrations';
        }

        if ($user->can('create_onboarding_workflows')) {
            $data['onboarding'] = 'Manage onboarding workflows';
            $data['employees'] = 'Manage employees';
            $data['leave'] = 'Leave requests & attendance';
            $data['performance'] = 'Performance reviews';
            $data['wellbeing'] = 'Well-being reports';
        }

        if ($user->can('login')) {
            $data['check_in_out'] = 'Check in/out';
            $data['documents'] = 'Upload documents';
            $data['orientation'] = 'Orientation materials';
            $data['leave'] = 'Request leave & view balance';
            $data['payslips'] = 'View/download payslips';
            $data['wellbeing'] = 'Stress monitoring & surveys';
        }

        $this->dashboardData = $data;

        // Load clock attendance data
        $this->loadTodayAttendance();
        $this->getCurrentLocation();
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

    public function testNotification()
    {
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Test notification! This should appear in the bottom right corner.'
        ]);
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
            {{ __('Welcome back, ') . Auth::user()->name }}!
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

                <!-- Action Buttons -->
                <div class="flex gap-4">
                    @if(!$this->currentAttendance)
                        <button
                            wire:click="clockIn"
                            wire:loading.attr="disabled"
                            class="flex-1 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white px-6 py-3 rounded-xl font-semibold shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            <span wire:loading.remove>{{ 'Clock In' }}</span>
                            <span wire:loading>{{ 'Clocking In...' }}</span>
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

                <!-- Test Notification Button -->
                <div class="mt-4">
                    <button
                        wire:click="testNotification"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium shadow-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        Test Notification
                    </button>
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

    <!-- Role-based Dashboard Content -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($dashboardData as $key => $value)
            <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
                <h2 class="text-lg font-semibold mb-2 text-gray-800 dark:text-gray-200">{{ __(ucwords(str_replace('_', ' ', $key))) }}</h2>
                <p class="text-gray-600 dark:text-gray-400">{{ __($value) }}</p>
            </div>
        @endforeach
    </div>
</div>