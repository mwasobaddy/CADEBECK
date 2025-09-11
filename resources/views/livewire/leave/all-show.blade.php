<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\LeaveRequest;
use App\Models\Employee;
use App\Models\User;
use App\Models\Audit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

new #[Layout('components.layouts.app')] class extends Component {
    public array $form = [
        'leave_type' => '',
        'start_date' => '',
        'end_date' => '',
        'days_requested' => '',
        'reason' => '',
        'status' => '',
        'approval_notes' => '',
    ];
    public ?LeaveRequest $leaveRequest = null;
    public bool $editing = false;
    public string $search = '';
    public string $roleFilter = '';
    public int $perPage = 10;

    public function mount($id = null): void
    {
        if ($id) {
            $this->leaveRequest = LeaveRequest::with(['employee.user', 'approver'])->findOrFail($id);
            $this->form = [
                'leave_type' => $this->leaveRequest->leave_type,
                'start_date' => $this->leaveRequest->start_date ? $this->leaveRequest->start_date->format('Y-m-d') : '',
                'end_date' => $this->leaveRequest->end_date ? $this->leaveRequest->end_date->format('Y-m-d') : '',
                'days_requested' => $this->leaveRequest->days_requested,
                'reason' => $this->leaveRequest->reason,
                'status' => $this->leaveRequest->status,
                'approval_notes' => $this->leaveRequest->approval_notes ?? '',
            ];
            $this->editing = true;
        }
    }

    public function save(): void
    {
        $this->validate([
            'form.leave_type' => ['required', 'string', 'in:annual,sick,maternity,paternity'],
            'form.start_date' => ['required', 'date', 'before_or_equal:form.end_date'],
            'form.end_date' => ['required', 'date', 'after_or_equal:form.start_date'],
            'form.days_requested' => ['required', 'integer', 'min:1'],
            'form.reason' => ['required', 'string', 'max:1000'],
            'form.status' => ['required', 'string', 'in:pending,approved,rejected,cancelled'],
            'form.approval_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($this->editing && $this->leaveRequest) {
            $oldStatus = $this->leaveRequest->status;
            $this->leaveRequest->update([
                'leave_type' => $this->form['leave_type'],
                'start_date' => $this->form['start_date'],
                'end_date' => $this->form['end_date'],
                'days_requested' => $this->form['days_requested'],
                'reason' => $this->form['reason'],
                'status' => $this->form['status'],
                'approval_notes' => $this->form['approval_notes'],
                'approved_by' => $this->form['status'] !== 'pending' ? Auth::id() : null,
                'approved_at' => $this->form['status'] !== 'pending' ? now() : null,
            ]);

            // Log the update action
            Audit::create([
                'actor_id' => Auth::id(),
                'action' => 'update',
                'target_type' => LeaveRequest::class,
                'target_id' => $this->leaveRequest->id,
                'details' => json_encode([
                    'old_status' => $oldStatus,
                    'new_status' => $this->form['status'],
                    'leave_type' => $this->form['leave_type'],
                    'start_date' => $this->form['start_date'],
                    'end_date' => $this->form['end_date'],
                    'days_requested' => $this->form['days_requested'],
                    'reason' => $this->form['reason'],
                    'approval_notes' => $this->form['approval_notes'],
                ]),
            ]);

            // Store notification directly in session for notification manager
            $notification = [
                'type' => 'success',
                'message' => __('Leave request updated successfully.'),
                'timestamp' => now()->timestamp,
            ];

            $existingNotifications = session('notifications', []);
            if (!is_array($existingNotifications)) {
                $existingNotifications = [];
            }
            $existingNotifications[] = $notification;
            session(['notifications' => $existingNotifications]);
        } else {
            $user = Auth::user();
            $employee = Employee::where('user_id', $user->id)->first();

            if (!$employee) {
                $this->dispatch('notify', ['type' => 'error', 'message' => __('Employee record not found.')]);
                return;
            }

            $leaveRequest = LeaveRequest::create([
                'employee_id' => $employee->id,
                'user_id' => $user->id,
                'leave_type' => $this->form['leave_type'],
                'start_date' => $this->form['start_date'],
                'end_date' => $this->form['end_date'],
                'days_requested' => $this->form['days_requested'],
                'reason' => $this->form['reason'],
                'status' => $this->form['status'],
                'approval_notes' => $this->form['approval_notes'],
                'approved_by' => $this->form['status'] !== 'pending' ? Auth::id() : null,
                'approved_at' => $this->form['status'] !== 'pending' ? now() : null,
            ]);

            // Log the create action
            Audit::create([
                'actor_id' => Auth::id(),
                'action' => 'create',
                'target_type' => LeaveRequest::class,
                'target_id' => $leaveRequest->id,
                'details' => json_encode($this->form),
            ]);

            // Store notification directly in session for notification manager
            $notification = [
                'type' => 'success',
                'message' => __('Leave request created successfully.'),
                'timestamp' => now()->timestamp,
            ];

            $existingNotifications = session('notifications', []);
            if (!is_array($existingNotifications)) {
                $existingNotifications = [];
            }
            $existingNotifications[] = $notification;
            session(['notifications' => $existingNotifications]);
        }
        $this->redirectRoute('all-leave.manage');
    }

    public function resetForm(): void
    {
        if ($this->editing && $this->leaveRequest) {
            $this->form = [
                'leave_type' => $this->leaveRequest->leave_type,
                'start_date' => $this->leaveRequest->start_date ? $this->leaveRequest->start_date->format('Y-m-d') : '',
                'end_date' => $this->leaveRequest->end_date ? $this->leaveRequest->end_date->format('Y-m-d') : '',
                'days_requested' => $this->leaveRequest->days_requested,
                'reason' => $this->leaveRequest->reason,
                'status' => $this->leaveRequest->status,
                'approval_notes' => $this->leaveRequest->approval_notes ?? '',
            ];
        } else {
            $this->form = [
                'leave_type' => '',
                'start_date' => '',
                'end_date' => '',
                'days_requested' => '',
                'reason' => '',
                'status' => 'pending',
                'approval_notes' => '',
            ];
        }
        $this->dispatch('notify', ['type' => 'info', 'message' => __('Form reset successfully.')]);
    }

    public function approve(): void
    {
        if (!$this->editing || !$this->leaveRequest) return;

        // Check if user can approve this leave request
        if (!Auth::user()->can('approve', $this->leaveRequest)) {
            $notification = [
                'type' => 'error',
                'message' => __('You do not have permission to approve this leave request.'),
                'timestamp' => now()->timestamp,
            ];
            $existingNotifications = session('notifications', []);
            if (!is_array($existingNotifications)) {
                $existingNotifications = [];
            }
            $existingNotifications[] = $notification;
            session(['notifications' => $existingNotifications]);
            return;
        }

        $this->leaveRequest->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approval_notes' => $this->form['approval_notes'],
        ]);

        // Log the approval action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'approve',
            'target_type' => LeaveRequest::class,
            'target_id' => $this->leaveRequest->id,
            'details' => json_encode([
                'leave_type' => $this->leaveRequest->leave_type,
                'employee_id' => $this->leaveRequest->employee_id,
                'start_date' => $this->leaveRequest->start_date->format('Y-m-d'),
                'end_date' => $this->leaveRequest->end_date->format('Y-m-d'),
                'days_requested' => $this->leaveRequest->days_requested,
                'approval_notes' => $this->form['approval_notes'],
            ]),
        ]);

        $notification = [
            'type' => 'success',
            'message' => __('Leave request approved successfully.'),
            'timestamp' => now()->timestamp,
        ];

        $existingNotifications = session('notifications', []);
        if (!is_array($existingNotifications)) {
            $existingNotifications = [];
        }
        $existingNotifications[] = $notification;
        session(['notifications' => $existingNotifications]);

        $this->redirectRoute('all-leave.manage');
    }

    public function reject(): void
    {
        if (!$this->editing || !$this->leaveRequest) return;

        // Check if user can approve this leave request (same logic as approve)
        if (!Auth::user()->can('approve', $this->leaveRequest)) {
            $notification = [
                'type' => 'error',
                'message' => __('You do not have permission to reject this leave request.'),
                'timestamp' => now()->timestamp,
            ];
            $existingNotifications = session('notifications', []);
            if (!is_array($existingNotifications)) {
                $existingNotifications = [];
            }
            $existingNotifications[] = $notification;
            session(['notifications' => $existingNotifications]);
            return;
        }

        $this->leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approval_notes' => $this->form['approval_notes'],
        ]);

        // Log the rejection action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'reject',
            'target_type' => LeaveRequest::class,
            'target_id' => $this->leaveRequest->id,
            'details' => json_encode([
                'leave_type' => $this->leaveRequest->leave_type,
                'employee_id' => $this->leaveRequest->employee_id,
                'start_date' => $this->leaveRequest->start_date->format('Y-m-d'),
                'end_date' => $this->leaveRequest->end_date->format('Y-m-d'),
                'days_requested' => $this->leaveRequest->days_requested,
                'approval_notes' => $this->form['approval_notes'],
            ]),
        ]);

        $notification = [
            'type' => 'success',
            'message' => __('Leave request rejected successfully.'),
            'timestamp' => now()->timestamp,
        ];

        $existingNotifications = session('notifications', []);
        if (!is_array($existingNotifications)) {
            $existingNotifications = [];
        }
        $existingNotifications[] = $notification;
        session(['notifications' => $existingNotifications]);

        $this->redirectRoute('all-leave.manage');
    }

    public function getLeaveRequestsProperty()
    {
        $query = LeaveRequest::query();

        // Apply hierarchical access control using policy scope
        $user = Auth::user();
        $query = \App\Policies\LeaveRequestPolicy::scopeViewableBy($query, $user);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('reason', 'like', "%{$this->search}%")
                  ->orWhere('leave_type', 'like', "%{$this->search}%")
                  ->orWhereHas('employee.user', function ($userQuery) {
                      $userQuery->where('first_name', 'like', "%{$this->search}%")
                                ->orWhere('other_names', 'like', "%{$this->search}%")
                                ->orWhere('email', 'like', "%{$this->search}%");
                  });
            });
        }
        if ($this->roleFilter) {
            $query->where('status', $this->roleFilter);
        }
        return $query->with(['employee.user', 'approver'])->paginate($this->perPage);
    }

    public function getStatusColor($status)
    {
        return match($status) {
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            'cancelled' => 'blue',
            default => 'blue'
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
                <a href="{{ route('all-leave.manage') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('all-leave.manage') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}">
                    {{ __('All Leave Requests') }}
                </a>
                <a href="{{ $editing && $leaveRequest ? route('all-leave.edit', ['id' => $leaveRequest->id]) : route('all-leave.edit') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('all-leave.edit') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}">
                    {{ $editing ? __('Review Leave Request') : __('Create Leave Request') }}
                </a>
            </div>
        </nav>
    </div>

    @can('manage_all_leaves')
        <!-- Card Container for Form -->
        <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
            <div class="flex items-center gap-3 mb-8">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"></path>
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                </svg>
                <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                    {{ $editing ? __('Review Leave Request') : __('Create Leave Request') }}
                    <span class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                </h1>
            </div>

            @if($editing && $this->leaveRequest)
                <!-- Employee Information Display -->
                <div class="bg-blue-50/80 dark:bg-blue-900/20 rounded-xl p-6 mb-6 border border-blue-200 dark:border-blue-700">
                    <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-4">{{ __('Employee Information') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-blue-700 dark:text-blue-300">{{ __('Employee Name') }}</label>
                            <p class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                                {{ $this->leaveRequest->employee->user->first_name }} {{ $this->leaveRequest->employee->user->other_names }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-blue-700 dark:text-blue-300">{{ __('Email') }}</label>
                            <p class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                                {{ $this->leaveRequest->employee->user->email }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-blue-700 dark:text-blue-300">{{ __('Staff Number') }}</label>
                            <p class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                                {{ $this->leaveRequest->employee->staff_number }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-blue-700 dark:text-blue-300">{{ __('Current Status') }}</label>
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-bold shadow bg-{{ $this->getStatusColor($this->leaveRequest->status) }}-100 text-{{ $this->getStatusColor($this->leaveRequest->status) }}-800 dark:bg-{{ $this->getStatusColor($this->leaveRequest->status) }}-900/20 dark:text-{{ $this->getStatusColor($this->leaveRequest->status) }}-200">
                                {{ $this->getStatusText($this->leaveRequest->status) }}
                            </span>
                        </div>
                    </div>
                </div>
            @endif

            <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Leave Details -->
                <div class="md:col-span-2">
                    <h2 class="text-lg font-bold text-green-700 mb-2">{{ __('Leave Details') }}</h2>
                </div>
                <div>
                    <flux:select
                        wire:model="form.leave_type"
                        :label="__('Leave Type')"
                        required
                        :placeholder="__('Select Leave Type')"
                        readonly
                        disabled
                    >
                        <flux:select.option value="annual">{{ __('Annual Leave') }}</flux:select.option>
                        <flux:select.option value="sick">{{ __('Sick Leave') }}</flux:select.option>
                        <flux:select.option value="maternity">{{ __('Maternity Leave') }}</flux:select.option>
                        <flux:select.option value="paternity">{{ __('Paternity Leave') }}</flux:select.option>
                    </flux:select>
                </div>
                <div>
                    <flux:input
                        wire:model="form.days_requested"
                        :label="__('Days Requested')"
                        type="number"
                        required
                        min="1"
                        placeholder="{{ __('Number of days') }}"
                        readonly
                    />
                </div>
                <div>
                    <flux:input
                        wire:model="form.start_date"
                        :label="__('Start Date')"
                        type="date"
                        required
                        placeholder="{{ __('Start Date') }}"
                        readonly
                    />
                </div>
                <div>
                    <flux:input
                        wire:model="form.end_date"
                        :label="__('End Date')"
                        type="date"
                        required
                        placeholder="{{ __('End Date') }}"
                        readonly
                    />
                </div>

                <!-- Reason -->
                <div class="md:col-span-2">
                    <flux:textarea
                        wire:model="form.reason"
                        :label="__('Reason for Leave')"
                        required
                        rows="4"
                        placeholder="{{ __('Please provide detailed reason for your leave request...') }}"
                        readonly
                    />
                </div>

                <!-- Status and Approval -->
                @if($editing)
                <div class="md:col-span-2">
                    <h2 class="text-lg font-bold text-green-700 mb-2">{{ __('Approval Status') }}</h2>
                </div>
                <div>
                    <flux:select
                        wire:model="form.status"
                        :label="__('Status')"
                        required
                        :placeholder="__('Select Status')"
                        readonly
                        disabled
                    >
                        <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
                        <flux:select.option value="approved">{{ __('Approved') }}</flux:select.option>
                        <flux:select.option value="rejected">{{ __('Rejected') }}</flux:select.option>
                        <flux:select.option value="cancelled">{{ __('Cancelled') }}</flux:select.option>
                    </flux:select>
                </div>
                <div>
                    <flux:textarea
                        wire:model="form.approval_notes"
                        :label="__('Approval Notes')"
                        rows="3"
                        placeholder="{{ __('Add notes for approval/rejection...') }}"
                    />
                </div>
                @endif

                <!-- Actions -->
                <div class="flex items-end justify-end gap-3 md:col-span-2">
                    @if($editing && $this->leaveRequest && $this->leaveRequest->status === 'pending')
                        @can('approve', $this->leaveRequest)
                            <button type="button" wire:click="approve"
                                class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                </svg>
                                {{ __('Approve') }}
                            </button>
                            <button type="button" wire:click="reject"
                                class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                {{ __('Reject') }}
                            </button>
                        @endcan
                    @endif
                </div>
            </form>
        </div>
    @else
        <div class="flex items-center justify-center w-full">
            <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-10 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40 flex flex-col items-center gap-6 max-w-lg w-full">
                <svg class="w-16 h-16 text-red-400 mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" />
                </svg>
                <h2 class="text-2xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 flex items-center gap-2 drop-shadow-lg">
                    {{ __('Access Denied') }}
                </h2>
                <p class="text-lg text-gray-700 dark:text-gray-300 font-medium text-center">
                    {{ __('You do not have permission to access this page or perform this action.') }}
                </p>
            </div>
        </div>
    @endcan
</div>
