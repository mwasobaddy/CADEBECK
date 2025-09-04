<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new #[Layout('components.layouts.app')] class extends Component {
    public $currentAttendance = null;
    public $todayAttendance = null;
    public $isLoading = false;
    public $locationData = null;

    public function mount()
    {
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
                'message' => __('Employee record not found. Please contact HR.')
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
                'type' => 'warning',
                'message' => __('You are already clocked in for today.')
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
            'message' => __('Successfully clocked in at ') . now()->format('H:i')
        ]);

        $this->isLoading = false;
    }

    public function clockOut()
    {
        $this->isLoading = true;

        if (!$this->currentAttendance) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('No active clock-in session found.')
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
            'message' => __('Successfully clocked out. Total hours: ') . round($totalHours, 2)
        ]);

        $this->isLoading = false;
    }

    public function getStatusText()
    {
        if ($this->currentAttendance) {
            return __('Currently clocked in since ') . $this->currentAttendance->clock_in_time->format('H:i');
        }

        if ($this->todayAttendance && $this->todayAttendance->clock_out_time) {
            return __('Clocked out at ') . $this->todayAttendance->clock_out_time->format('H:i') .
                   __(' (') . $this->todayAttendance->total_hours . __(' hours)');
        }

        return __('Not clocked in today');
    }

    public function getStatusColor()
    {
        if ($this->currentAttendance) {
            return 'green';
        }

        if ($this->todayAttendance && $this->todayAttendance->clock_out_time) {
            return 'blue';
        }

        return 'gray';
    }
};
?>

<div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
    <div class="flex items-center gap-3 mb-6">
        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
            <polyline points="12,6 12,12 16,14"></polyline>
        </svg>
        <h2 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500">
            {{ __('Clock In/Out') }}
        </h2>
    </div>

    <div class="space-y-6">
        <!-- Status Card -->
        <div class="bg-gradient-to-r from-{{ $this->getStatusColor() }}-50 to-{{ $this->getStatusColor() }}-100 dark:from-{{ $this->getStatusColor() }}-900/20 dark:to-{{ $this->getStatusColor() }}-800/20 rounded-xl p-6 border border-{{ $this->getStatusColor() }}-200 dark:border-{{ $this->getStatusColor() }}-700">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-{{ $this->getStatusColor() }}-800 dark:text-{{ $this->getStatusColor() }}-200">
                        {{ __('Current Status') }}
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
                    <span wire:loading.remove>{{ __('Clock In') }}</span>
                    <span wire:loading>{{ __('Clocking In...') }}</span>
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
                    <span wire:loading.remove>{{ __('Clock Out') }}</span>
                    <span wire:loading>{{ __('Clocking Out...') }}</span>
                </button>
            @endif
        </div>

        <!-- Today's Summary -->
        @if($this->todayAttendance)
            <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">{{ __('Today\'s Summary') }}</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    @if($this->todayAttendance->clock_in_time)
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Clock In:') }}</span>
                            <span class="font-medium ml-2">{{ $this->todayAttendance->clock_in_time->format('H:i') }}</span>
                        </div>
                    @endif
                    @if($this->todayAttendance->clock_out_time)
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Clock Out:') }}</span>
                            <span class="font-medium ml-2">{{ $this->todayAttendance->clock_out_time->format('H:i') }}</span>
                        </div>
                    @endif
                    @if($this->todayAttendance->total_hours)
                        <div class="col-span-2">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Total Hours:') }}</span>
                            <span class="font-medium ml-2">{{ $this->todayAttendance->total_hours }} hours</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
