<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\LeaveRequest;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new #[Layout('components.layouts.app')] class extends Component {
    public $showLeaveForm = false;
    public $editingLeave = null;
    public $leaveRequests = [];

    // Form fields
    public $leave_type = '';
    public $start_date = '';
    public $end_date = '';
    public $days_requested = 0;
    public $reason = '';

    public $isLoading = false;

    public function mount()
    {
        $this->loadLeaveRequests();
    }

    public function loadLeaveRequests()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if ($employee) {
            $this->leaveRequests = LeaveRequest::where('employee_id', $employee->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }
    }

    public function showLeaveForm()
    {
        $this->showLeaveForm = true;
        $this->resetForm();
    }

    public function hideLeaveForm()
    {
        $this->showLeaveForm = false;
        $this->editingLeave = null;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->leave_type = '';
        $this->start_date = '';
        $this->end_date = '';
        $this->days_requested = 0;
        $this->reason = '';
        $this->editingLeave = null;
    }

    public function updatedStartDate()
    {
        $this->calculateDays();
    }

    public function updatedEndDate()
    {
        $this->calculateDays();
    }

    public function calculateDays()
    {
        if ($this->start_date && $this->end_date) {
            $start = \Carbon\Carbon::parse($this->start_date);
            $end = \Carbon\Carbon::parse($this->end_date);

            if ($end->greaterThanOrEqualTo($start)) {
                // Calculate business days (excluding weekends)
                $days = 0;
                $current = $start->copy();

                while ($current->lessThanOrEqualTo($end)) {
                    // Skip weekends (Saturday = 6, Sunday = 0)
                    if ($current->dayOfWeek !== 0 && $current->dayOfWeek !== 6) {
                        $days++;
                    }
                    $current->addDay();
                }

                $this->days_requested = $days;
            }
        }
    }

    public function saveLeaveRequest()
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

        $this->validate([
            'leave_type' => 'required|in:annual,sick,maternity,paternity,emergency,unpaid',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'days_requested' => 'required|integer|min:1|max:30',
            'reason' => 'required|string|min:10|max:500',
        ]);

        if ($this->editingLeave) {
            $this->editingLeave->update([
                'leave_type' => $this->leave_type,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'days_requested' => $this->days_requested,
                'reason' => $this->reason,
            ]);

            $message = __('Leave request updated successfully.');
        } else {
            LeaveRequest::create([
                'employee_id' => $employee->id,
                'user_id' => $user->id,
                'leave_type' => $this->leave_type,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'days_requested' => $this->days_requested,
                'reason' => $this->reason,
                'status' => 'pending',
            ]);

            $message = __('Leave request submitted successfully.');
        }

        $this->loadLeaveRequests();
        $this->hideLeaveForm();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message
        ]);

        $this->isLoading = false;
    }

    public function editLeave($leaveId)
    {
        $leave = LeaveRequest::find($leaveId);

        if ($leave && $leave->employee->user_id === Auth::id() && $leave->isPending()) {
            $this->editingLeave = $leave;
            $this->leave_type = $leave->leave_type;
            $this->start_date = $leave->start_date->format('Y-m-d');
            $this->end_date = $leave->end_date->format('Y-m-d');
            $this->days_requested = $leave->days_requested;
            $this->reason = $leave->reason;
            $this->showLeaveForm = true;
        }
    }

    public function cancelLeave($leaveId)
    {
        $leave = LeaveRequest::find($leaveId);

        if ($leave && $leave->employee->user_id === Auth::id() && $leave->isPending()) {
            $leave->update(['status' => 'cancelled']);

            $this->loadLeaveRequests();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __('Leave request cancelled successfully.')
            ]);
        }
    }

    public function getLeaveBalance()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) return ['annual' => 0, 'sick' => 0];

        // This would typically come from employee contract or HR settings
        // For now, return default values
        return [
            'annual' => 25, // Default annual leave days
            'sick' => 10,   // Default sick leave days
        ];
    }

    public function getUsedLeaveDays($type)
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) return 0;

        return LeaveRequest::where('employee_id', $employee->id)
            ->where('leave_type', $type)
            ->where('status', 'approved')
            ->whereYear('start_date', now()->year)
            ->sum('days_requested');
    }

    public function getStatusColor($status)
    {
        return match($status) {
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            'cancelled' => 'gray',
            default => 'gray'
        };
    }

    public function getStatusText($status)
    {
        return match($status) {
            'pending' => __('Pending'),
            'approved' => __('Approved'),
            'rejected' => __('Rejected'),
            'cancelled' => __('Cancelled'),
            default => __('Unknown')
        };
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 4v10a2 2 0 002 2h4a2 2 0 002-2V11M9 11h6"></path>
                </svg>
                <h2 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-800 via-blue-500 to-cyan-500">
                    {{ __('Leave Management') }}
                </h2>
            </div>
            <button
                wire:click="showLeaveForm"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                </svg>
                {{ __('Request Leave') }}
            </button>
        </div>
    </div>

    <!-- Leave Balance -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">{{ __('Leave Balance') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @php
                $balance = $this->getLeaveBalance();
                $annualUsed = $this->getUsedLeaveDays('annual');
                $sickUsed = $this->getUsedLeaveDays('sick');
            @endphp

            <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-4 border border-green-200 dark:border-green-700">
                <div class="flex justify-between items-center">
                    <div>
                        <h4 class="font-semibold text-green-800 dark:text-green-200">{{ __('Annual Leave') }}</h4>
                        <p class="text-sm text-green-600 dark:text-green-300">{{ $annualUsed }}/{{ $balance['annual'] }} days used</p>
                    </div>
                    <div class="text-2xl font-bold text-green-600">{{ $balance['annual'] - $annualUsed }}</div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl p-4 border border-orange-200 dark:border-orange-700">
                <div class="flex justify-between items-center">
                    <div>
                        <h4 class="font-semibold text-orange-800 dark:text-orange-200">{{ __('Sick Leave') }}</h4>
                        <p class="text-sm text-orange-600 dark:text-orange-300">{{ $sickUsed }}/{{ $balance['sick'] }} days used</p>
                    </div>
                    <div class="text-2xl font-bold text-orange-600">{{ $balance['sick'] - $sickUsed }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Request Form -->
    @if($this->showLeaveForm)
        <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                    {{ $this->editingLeave ? __('Edit Leave Request') : __('New Leave Request') }}
                </h3>
                <button wire:click="hideLeaveForm" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form wire:submit="saveLeaveRequest" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <flux:select
                            wire:model="leave_type"
                            :label="__('Leave Type')"
                            required
                        >
                            <flux:select.option value="annual">{{ __('Annual Leave') }}</flux:select.option>
                            <flux:select.option value="sick">{{ __('Sick Leave') }}</flux:select.option>
                            <flux:select.option value="maternity">{{ __('Maternity Leave') }}</flux:select.option>
                            <flux:select.option value="paternity">{{ __('Paternity Leave') }}</flux:select.option>
                            <flux:select.option value="emergency">{{ __('Emergency Leave') }}</flux:select.option>
                            <flux:select.option value="unpaid">{{ __('Unpaid Leave') }}</flux:select.option>
                        </flux:select>
                    </div>

                    <div>
                        <flux:input
                            wire:model="days_requested"
                            :label="__('Days Requested')"
                            type="number"
                            readonly
                            :value="$this->days_requested"
                        />
                    </div>

                    <div>
                        <flux:input
                            wire:model="start_date"
                            :label="__('Start Date')"
                            type="date"
                            required
                        />
                    </div>

                    <div>
                        <flux:input
                            wire:model="end_date"
                            :label="__('End Date')"
                            type="date"
                            required
                        />
                    </div>
                </div>

                <div>
                    <flux:textarea
                        wire:model="reason"
                        :label="__('Reason')"
                        :placeholder="__('Please provide details about your leave request...')"
                        required
                        rows="4"
                    />
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        wire:click="hideLeaveForm"
                        class="px-4 py-2 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <span wire:loading.remove>{{ $this->editingLeave ? __('Update Request') : __('Submit Request') }}</span>
                        <span wire:loading>{{ __('Submitting...') }}</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Leave Requests List -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">{{ __('My Leave Requests') }}</h3>

        @if($this->leaveRequests->count() > 0)
            <div class="space-y-4">
                @foreach($this->leaveRequests as $leave)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h4 class="font-semibold text-gray-800 dark:text-gray-200">
                                        {{ __(ucwords(str_replace('_', ' ', $leave->leave_type))) }} Leave
                                    </h4>
                                    <span class="px-2 py-1 text-xs rounded-full bg-{{ $this->getStatusColor($leave->status) }}-100 text-{{ $this->getStatusColor($leave->status) }}-800">
                                        {{ $this->getStatusText($leave->status) }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <p>{{ __('From:') }} {{ $leave->start_date->format('M j, Y') }} {{ __('to') }} {{ $leave->end_date->format('M j, Y') }}</p>
                                    <p>{{ __('Days:') }} {{ $leave->days_requested }}</p>
                                    <p>{{ __('Reason:') }} {{ Str::limit($leave->reason, 100) }}</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                @if($leave->isPending())
                                    <button
                                        wire:click="editLeave({{ $leave->id }})"
                                        class="text-blue-600 hover:text-blue-800 p-1"
                                        title="{{ __('Edit') }}"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button
                                        wire:click="cancelLeave({{ $leave->id }})"
                                        class="text-red-600 hover:text-red-800 p-1"
                                        title="{{ __('Cancel') }}"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 4v10a2 2 0 002 2h4a2 2 0 002-2V11M9 11h6"></path>
                </svg>
                <p>{{ __('No leave requests found.') }}</p>
                <button wire:click="showLeaveForm" class="mt-2 text-blue-600 hover:text-blue-800">
                    {{ __('Submit your first leave request') }}
                </button>
            </div>
        @endif
    </div>
</div>
