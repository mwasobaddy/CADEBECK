<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\LeaveRequest;
use App\Models\Employee;
use App\Models\Audit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new #[Layout('components.layouts.app')] class extends Component {
    public $leaveRequest = null;
    public $isEditing = false;

    // Form fields
    public $leave_type = '';
    public $start_date = '';
    public $end_date = '';
    public $days_requested = 0;
    public $reason = '';

    public $isLoading = false;

    public function mount($leaveRequestId = null)
    {
        if ($leaveRequestId) {
            $this->leaveRequest = LeaveRequest::find($leaveRequestId);
            if ($this->leaveRequest && $this->leaveRequest->employee->user_id === Auth::id() && $this->leaveRequest->isPending()) {
                $this->isEditing = true;
                $this->populateForm();
            } else {
                // Invalid request, redirect or show error
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => __('Leave request not found or cannot be edited.')
                ]);
                return redirect()->route('leave.leave-manager');
            }
        }

        // Calculate days if we have both dates (for editing or pre-filled forms)
        if ($this->start_date && $this->end_date) {
            $this->calculateDays();
        }
    }

    public function populateForm()
    {
        if ($this->leaveRequest) {
            $this->leave_type = $this->leaveRequest->leave_type;
            $this->start_date = $this->leaveRequest->start_date->format('Y-m-d');
            $this->end_date = $this->leaveRequest->end_date->format('Y-m-d');
            $this->days_requested = $this->leaveRequest->days_requested;
            $this->reason = $this->leaveRequest->reason;
        }
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
        // Reset days_requested if dates are not set
        if (!$this->start_date || !$this->end_date) {
            $this->days_requested = 0;
            return;
        }

        try {
            $start = \Carbon\Carbon::parse($this->start_date);
            $end = \Carbon\Carbon::parse($this->end_date);

            // If end date is before start date, reset to 0
            if ($end->lessThan($start)) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => __('End date must be after start date.')
                ]);
                $this->days_requested = 0;
                return;
            }

            // Calculate business days using Carbon's built-in method
            // This method excludes weekends and we add 1 to include both start and end dates
            $this->days_requested = $start->diffInWeekdays($end) + 1;

            // Optional: Debug info (you can remove this after testing)
            logger('Leave calculation', [
                'start' => $start->format('Y-m-d (l)'), // l shows day name
                'end' => $end->format('Y-m-d (l)'),
                'business_days' => $this->days_requested
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('Error calculating leave days: ' . $e->getMessage())
            ]);
            $this->days_requested = 0;
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

        $formData = [
            'leave_type' => $this->leave_type,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'days_requested' => $this->days_requested,
            'reason' => $this->reason,
        ];

        if ($this->isEditing && $this->leaveRequest) {
            $this->leaveRequest->update($formData);

            // Log the update action
            Audit::create([
                'actor_id' => Auth::id(),
                'action' => 'update',
                'target_type' => LeaveRequest::class,
                'target_id' => $this->leaveRequest->id,
                'details' => json_encode($formData),
            ]);

            $message = __('Leave request updated successfully.');
        } else {
            $leaveRequest = LeaveRequest::create([
                'employee_id' => $employee->id,
                'user_id' => $user->id,
                'leave_type' => $this->leave_type,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'days_requested' => $this->days_requested,
                'reason' => $this->reason,
                'status' => 'pending',
            ]);

            // Log the create action
            Audit::create([
                'actor_id' => Auth::id(),
                'action' => 'create',
                'target_type' => LeaveRequest::class,
                'target_id' => $leaveRequest->id,
                'details' => json_encode($formData),
            ]);

            $message = __('Leave request submitted successfully.');
        }

        // Store notification directly in session for notification manager
        $notification = [
            'type' => 'success',
            'message' => $message,
            'timestamp' => now()->timestamp,
        ];

        $existingNotifications = session('notifications', []);
        if (!is_array($existingNotifications)) {
            $existingNotifications = [];
        }
        $existingNotifications[] = $notification;
        session(['notifications' => $existingNotifications]);

        $this->isLoading = false;

        // Redirect back to leave manager
        return redirect()->route('leave.leave-manager');
    }

    public function cancel()
    {
        return redirect()->route('leave.leave-manager');
    }
};
?>

<div class="relative max-w-6xl mx-auto md:px-4 md:py-8">
    <!-- SVG Blobs Background -->
    <svg class="fixed -top-24 right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0" viewBox="0 0 400 400" fill="none">
        <ellipse cx="200" cy="200" rx="180" ry="120" fill="url(#blob1)" />
        <defs>
            <radialGradient id="blob1" cx="0" cy="0" r="1" gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                <stop stop-color="#38bdf8" />
                <stop offset="1" stop-color="#6366f1" />
            </radialGradient>
        </defs>
    </svg>
    <svg class="fixed -bottom-24 -right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0" viewBox="0 0 400 400" fill="none">
        <ellipse cx="200" cy="200" rx="160" ry="100" fill="url(#blob2)" />
        <defs>
            <radialGradient id="blob2" cx="0" cy="0" r="1" gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                <stop stop-color="#34d399" />
                <stop offset="1" stop-color="#f472b6" />
            </radialGradient>
        </defs>
    </svg>

    <!-- Breadcrumbs -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 z-10 relative border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <nav class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('leave.leave-manager') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('leave.leave-manager') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none' : '' }}">
                    {{ __('Leave Manager') }}
                </a>
                <a href="{{ request()->routeIs('leave.request.edit') ? route('leave.request.edit', $leaveRequest->id ?? '') : route('leave.apply') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('leave.apply*') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none' : '' }}">
                    {{ $this->isEditing ? __('Edit Leave Request') : __('New Leave Request') }}
                </a>
            </div>
        </nav>
    </div>

    <!-- Card Container for Form -->
    <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex items-center gap-3 mb-8">
            <flux:icon name="document-check" class="w-10 h-10 text-green-600" />
            <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                {{ $this->isEditing ? __('Edit Leave Request') : __('New Leave Request') }}
                <span class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
            </h1>
        </div>

        <form wire:submit.prevent="saveLeaveRequest" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Leave Information -->
            <div class="md:col-span-2">
                <h2 class="text-lg font-bold text-green-700 mb-2">{{ __('Leave Information') }}</h2>
            </div>

            <div>
                <flux:select
                    wire:model="leave_type"
                    :label="__('Leave Type')"
                    required
                    :placeholder="__('Select Leave Type')"
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
                    wire:model.lazy="days_requested"
                    :label="__('Days Requested')"
                    readonly
                    :value="$this->days_requested"
                    :placeholder="__('Auto-calculated')"
                />
            </div>

            <!-- Date Information -->
            <div class="md:col-span-2">
                <h2 class="text-lg font-bold text-green-700 mb-2">{{ __('Date Information') }}</h2>
            </div>

            <div>
                <flux:input
                    wire:model.lazy="start_date"
                    :label="__('Start Date')"
                    type="date"
                    required
                    :min="date('Y-m-d')"
                />
            </div>

            <div>
                <flux:input
                    wire:model.lazy="end_date"
                    :label="__('End Date')"
                    type="date"
                    required
                    :min="date('Y-m-d')"
                />
            </div>

            <!-- Additional Information -->
            <div class="md:col-span-2">
                <h2 class="text-lg font-bold text-green-700 mb-2">{{ __('Additional Information') }}</h2>
            </div>

            <div class="md:col-span-2">
                <flux:textarea
                    wire:model="reason"
                    :label="__('Reason')"
                    :placeholder="__('Please provide details about your leave request...')"
                    required
                    rows="4"
                />
            </div>

            <div class="flex items-end justify-end gap-3 md:col-span-2">
                <button type="submit"
                    wire:loading.attr="disabled"
                    class="flex items-center gap-2 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <span wire:loading.remove>{{ $this->isEditing ? __('Update Request') : __('Submit Request') }}</span>
                    <span wire:loading>{{ __('Submitting...') }}</span>
                    <flux:icon name="check" class="w-5 h-5" />
                </button>
                <a href="{{ route('leave.leave-manager') }}"
                    class="flex items-center gap-2 bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-200 px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                    {{ __('Cancel') }}
                    <flux:icon name="arrow-path-rounded-square" class="w-5 h-5" />
                </a>
            </div>
        </form>
    </div>
</div>
