<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\LeaveRequest;
use App\Models\Employee;
use App\Models\Audit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public $showFilters = false;
    public $search = '';
    public $filterStatus = '';
    public $filterType = '';
    public $isSearching = false;
    public $isFiltering = false;
    public $isPaginating = false;
    public $isLoadingData = false;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $showDeleteModal = false;
    public $pendingDeleteId = null;
    public $isLoadingDelete = false;
    public $showEditModal = false;
    public $pendingEditId = null;
    public $isLoadingEdit = false;
    public $perPage = 10;
    public $selected = [];
    public $selectAll = false;
    public $showBulkDeleteModal = false;
    public $isLoadingBulkDelete = false;
    public $isLoadingExport = false;

    public function mount()
    {
        $this->updateSelectAllState();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function getLeaveRequestsProperty()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if ($employee) {
            $query = LeaveRequest::where('employee_id', $employee->id);

            // Apply search filter
            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('reason', 'like', '%' . $this->search . '%')
                        ->orWhere('leave_type', 'like', '%' . $this->search . '%');
                });
            }

            // Apply status filter
            if ($this->filterStatus) {
                $query->where('status', $this->filterStatus);
            }

            // Apply type filter
            if ($this->filterType) {
                $query->where('leave_type', $this->filterType);
            }

            // Apply sorting
            $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
            $field = in_array($this->sortField, ['start_date', 'end_date', 'status', 'leave_type', 'created_at']) 
                     ? $this->sortField : 'created_at';
            $query->orderBy($field, $direction);

            return $query->paginate($this->perPage);
        } else {
            // Return empty paginated collection
            return LeaveRequest::where('id', 0)->paginate($this->perPage);
        }
    }

    public function updatedSearch(): void
    {
        $this->isSearching = true;
        $this->resetPage();
        $this->updateSelectAllState();
        $this->isSearching = false;
    }

    public function updatedFilterStatus(): void
    {
        $this->isFiltering = true;
        $this->resetPage();
        $this->updateSelectAllState();
        $this->isFiltering = false;
    }

    public function updatedFilterType(): void
    {
        $this->isFiltering = true;
        $this->resetPage();
        $this->updateSelectAllState();
        $this->isFiltering = false;
    }
    
    public function updateSelectAllState(): void
    {
        $leaves = $this->leaveRequests;
        if ($leaves && count($leaves) > 0) {
            $currentPageIds = $leaves->pluck('id')->toArray();
            $this->selectAll = count($currentPageIds) > 0 &&
                count(array_intersect($this->selected, $currentPageIds)) === count($currentPageIds);
        } else {
            $this->selectAll = false;
        }
    }

    public function toggleSelectAll(): void
    {
        $leaves = $this->leaveRequests;
        if ($leaves && count($leaves) > 0) {
            $currentPageIds = $leaves->pluck('id')->toArray();
            if ($this->selectAll) {
                $this->selected = array_values(array_diff($this->selected, $currentPageIds));
                $this->selectAll = false;
            } else {
                $this->selected = array_values(array_unique(array_merge($this->selected, $currentPageIds)));
                $this->selectAll = true;
            }
        }
    }

    public function updatedSelected(): void
    {
        $this->updateSelectAllState();
    }

    public function selectAllData(): void
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) return;
        $query = LeaveRequest::where('employee_id', $employee->id);
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('reason', 'like', '%' . $this->search . '%')
                    ->orWhere('leave_type', 'like', '%' . $this->search . '%');
            });
        }
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }
        if ($this->filterType) {
            $query->where('leave_type', $this->filterType);
        }
        $this->selected = $query->pluck('id')->toArray();
        $this->updateSelectAllState();
    }

    public function bulkDeleteConfirm(): void
    {
        $this->showBulkDeleteModal = true;
    }

    public function bulkDelete(): void
    {
        $this->isLoadingBulkDelete = true;
        $leaves = LeaveRequest::whereIn('id', $this->selected)->get();
        LeaveRequest::whereIn('id', $this->selected)->delete();

        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'bulk_delete',
            'target_type' => LeaveRequest::class,
            'details' => json_encode(['leave_ids' => $this->selected]),
        ]);

        $this->showBulkDeleteModal = false;
        $this->isLoadingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;
        $this->updateSelectAllState();
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Selected leave requests deleted successfully.')]);
    }

    public function exportSelected(): void
    {
        $this->isLoadingExport = true;
        $leaves = LeaveRequest::whereIn('id', $this->selected)->get();

        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'export_selected',
            'target_type' => LeaveRequest::class,
            'details' => json_encode(['leave_ids' => $this->selected]),
        ]);

        $csvData = "ID,Leave Type,Start Date,End Date,Days,Status,Reason,Created At\n";
        foreach ($leaves as $leave) {
            $csvData .= '"' . $leave->id . '","' .
                str_replace('"', '""', $leave->leave_type) . '","' .
                $leave->start_date->format('Y-m-d') . '","' .
                $leave->end_date->format('Y-m-d') . '","' .
                $leave->days_requested . '","' .
                str_replace('"', '""', $leave->status) . '","' .
                str_replace('"', '""', $leave->reason) . '","' .
                $leave->created_at . '"' . "\n";
        }
        $this->isLoadingExport = false;
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'selected_leave_requests_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Leave requests exported successfully.')]);
    }

    public function updatedPage(): void
    {
        $this->isPaginating = true;
        $this->updateSelectAllState();
        $this->isPaginating = false;
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
        $this->updateSelectAllState();
    }

    public function confirmDelete($leaveId)
    {
        $this->pendingDeleteId = $leaveId;
        $this->showDeleteModal = true;
    }

    public function cancelLeave()
    {
        if (!$this->pendingDeleteId) return;

        $this->isLoadingDelete = true;
        $leave = LeaveRequest::find($this->pendingDeleteId);

        if ($leave && $leave->employee->user_id === Auth::id() && $leave->isPending()) {
            $leave->update(['status' => 'cancelled']);

            // Log the cancel action
            Audit::create([
                'actor_id' => Auth::id(),
                'action' => 'cancel_leave',
                'target_type' => LeaveRequest::class,
                'target_id' => $leave->id,
                'details' => json_encode([
                    'leave_type' => $leave->leave_type,
                    'start_date' => $leave->start_date->format('Y-m-d'),
                    'end_date' => $leave->end_date->format('Y-m-d'),
                    'days_requested' => $leave->days_requested,
                    'reason' => $leave->reason
                ]),
            ]);

            $this->dispatch('notify', ['type' => 'success', 'message' => __('Leave request cancelled successfully.')]);
        }

        $this->showDeleteModal = false;
        $this->isLoadingDelete = false;
        $this->pendingDeleteId = null;
    }

    public function confirmEdit($leaveId)
    {
        $this->pendingEditId = $leaveId;
        $this->showEditModal = true;
    }

    public function editConfirmed()
    {
        if (!$this->pendingEditId) return;

        $leaveId = $this->pendingEditId; // Store the ID before clearing it
        
        $this->isLoadingEdit = true;
        
        // Log the edit view action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'view_edit_leave',
            'target_type' => LeaveRequest::class,
            'target_id' => $leaveId,
            'details' => json_encode([
                'action' => 'redirected_to_edit'
            ]),
        ]);

        $this->showEditModal = false;
        $this->isLoadingEdit = false;
        $this->pendingEditId = null;

        return redirect()->route('own-leave.edit', ['id' => $leaveId]);
    }

    public function getLeaveBalance()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) return ['annual' => 0, 'sick' => 0];

        // This would typically come from employee contract or HR settings
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
            ->whereIn('status', ['approved', 'pending']) // Include both approved and pending
            ->whereYear('start_date', now()->year)
            ->sum('days_requested');
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

    public function shouldShowSkeleton(): bool
    {
        return $this->isLoadingDelete || 
               $this->isLoadingEdit ||
               $this->isSearching || 
               $this->isFiltering || 
               $this->isPaginating ||
               $this->isLoadingData;
    }

    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
    }

    public function exportLeaveRequests(): void
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) return;

        $query = LeaveRequest::where('employee_id', $employee->id);

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('reason', 'like', '%' . $this->search . '%')
                    ->orWhere('leave_type', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        // Apply type filter
        if ($this->filterType) {
            $query->where('leave_type', $this->filterType);
        }

        // Apply sorting
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        $field = in_array($this->sortField, ['start_date', 'end_date', 'status', 'leave_type', 'created_at']) 
                 ? $this->sortField : 'created_at';
        $query->orderBy($field, $direction);

        $leaves = $query->get();

        // Log the export action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'export_leave_requests',
            'target_type' => LeaveRequest::class,
            'details' => json_encode(['total_requests' => $leaves->count()]),
        ]);

        $csvData = "ID,Leave Type,Start Date,End Date,Days,Status,Reason,Created At\n";
        foreach ($leaves as $leave) {
            $csvData .= '"' . $leave->id . '","' .
                       str_replace('"', '""', $leave->leave_type) . '","' .
                       $leave->start_date->format('Y-m-d') . '","' .
                       $leave->end_date->format('Y-m-d') . '","' .
                       $leave->days_requested . '","' .
                       str_replace('"', '""', $leave->status) . '","' .
                       str_replace('"', '""', $leave->reason) . '","' .
                       $leave->created_at . '"' . "\n";
        }

        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'my_leave_requests_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Leave requests exported successfully.')]);
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
                <a wire:navigate href="{{ route('own-leave.manage') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('own-leave.manage') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none' : '' }}">
                    {{ __('Leave Manager') }}
                </a>
                <a wire:navigate href="{{ route('leave.apply') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('leave.apply*') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none' : '' }}">
                    {{ __('New Leave Request') }}
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Card Container -->
    <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40 mb-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
            <div class="flex items-center gap-3">
                <flux:icon name="calendar-days" class="w-10 h-10 text-green-600" />
                <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                    {{ __('Leave Management') }}
                    <span class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                </h1>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" wire:click="exportLeaveRequests"
                    class="flex items-center gap-2 px-4 py-2 rounded-full border border-purple-200 dark:border-purple-700 text-purple-600 dark:text-purple-400 bg-purple-50/80 dark:bg-purple-900/20 hover:bg-purple-100/80 dark:hover:bg-purple-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-purple-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    <span class="hidden lg:inline">{{ __('Export') }}</span>
                </button>
                <a href="{{ route('leave.apply') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-full border border-blue-200 dark:border-blue-700 text-blue-600 dark:text-blue-400 bg-blue-50/80 dark:bg-blue-900/20 hover:bg-blue-100/80 dark:hover:bg-blue-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span class="hidden lg:inline">{{ __('Request Leave') }}</span>
                </a>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="flex flex-wrap gap-8 items-center mb-6">
            <div class="relative w-80">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-5 h-5 text-blue-200 dark:text-indigo-400 z-[1]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" fill="none"></circle>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"></path>
                    </svg>
                </span>
                <input type="text" wire:model.live.debounce.300ms="search"
                    class="w-full pl-10 pr-4 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white transition shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md"
                    placeholder="{{ __('Search leave requests...') }}">
            </div>
            <button type="button" wire:click="toggleFilters"
                class="flex items-center gap-1 px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 bg-white/80 dark:bg-zinc-900/80 text-blue-600 dark:text-indigo-300 hover:bg-blue-50/80 dark:hover:bg-zinc-800/80 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h8m-8 6h16"></path>
                </svg>
                <span class="hidden lg:inline">{{ __('Filters') }}</span>
            </button>
        </div>

        @if ($showFilters ?? false)
            <div class="flex flex-wrap gap-6 mb-6 items-center animate-fade-in">
                <select wire:model.live="filterStatus"
                    class="px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="approved">{{ __('Approved') }}</option>
                    <option value="rejected">{{ __('Rejected') }}</option>
                    <option value="cancelled">{{ __('Cancelled') }}</option>
                </select>
                <select wire:model.live="filterType"
                    class="px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="annual">{{ __('Annual Leave') }}</option>
                    <option value="sick">{{ __('Sick Leave') }}</option>
                    <option value="maternity">{{ __('Maternity Leave') }}</option>
                    <option value="paternity">{{ __('Paternity Leave') }}</option>
                </select>
                <select wire:model.live="perPage"
                    class="px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        @endif
    </div>

    <!-- Leave Balance -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 mb-8 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
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

    <!-- Leave Requests Table -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">{{ __('My Leave Requests') }}</h3>

        @if (count($selected) > 0)
            <div class="flex items-center justify-between mt-6 p-4 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 dark:from-zinc-800/50 dark:to-zinc-700/50 rounded-xl border border-blue-200 dark:border-zinc-700 backdrop-blur-sm">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                        {{ count($selected) }} {{ __('item(s) selected') }}
                    </span>
                    @if(count($selected) < (is_countable($this->leaveRequests) ? count($this->leaveRequests) : 0))
                        <button type="button" wire:click="selectAllData"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            {{ __('Select all') }} {{ $this->leaveRequests ? $this->leaveRequests->total() : 0 }} {{ __('items') }}
                        </button>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    @can('export_user')
                        <button type="button" wire:click="exportSelected"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl border border-purple-200 dark:border-purple-700 text-purple-600 dark:text-purple-400 bg-purple-50/80 dark:bg-purple-900/20 hover:bg-purple-100/80 dark:hover:bg-purple-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-purple-400 transition"
                            @if ($isLoadingExport) disabled @endif>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            {{ $isLoadingExport ? __('Exporting...') : __('Export Selected') }}
                        </button>
                    @endcan
                    @can('delete_user')
                        <button type="button" wire:click="bulkDeleteConfirm"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-pink-500 to-red-500 hover:from-pink-600 hover:to-red-600 text-white font-semibold shadow-lg focus:outline-none focus:ring-2 focus:ring-red-400 backdrop-blur-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            {{ __('Delete Selected') }}
                        </button>
                    @endcan
                </div>
            </div>
        @endif

        <div class="overflow-x-auto bg-transparent mt-6">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead>
                    <tr class="h-16 bg-zinc-800/5 dark:bg-white/10 text-zinc-600 dark:text-white/70">
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">
                            <button type="button"
                                wire:click="toggleSelectAll"
                                class="rounded focus:ring-2 focus:ring-pink-400 transition-colors duration-200
                                    @if($selectAll)
                                        bg-pink-500 text-white p-[2px]
                                    @else
                                        bg-transparent text-pink-500 border border-gray-200/50 p-[6px]
                                    @endif
                                    flex items-center gap-2"
                            >
                                @if($selectAll)
                                    <svg class="w-3 h-3 text-gray-800 font-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('leave_type')">
                            {{ __('Type') }}
                            @if($this->sortField === 'leave_type')
                                <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    @endif
                                </svg>
                            @endif
                        </th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('start_date')">
                            {{ __('Duration') }}
                            @if($this->sortField === 'start_date')
                                <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    @endif
                                </svg>
                            @endif
                        </th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">
                            {{ __('Reason') }}
                        </th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('status')">
                            {{ __('Status') }}
                            @if($this->sortField === 'status')
                                <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    @endif
                                </svg>
                            @endif
                        </th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('created_at')">
                            {{ __('Submitted') }}
                            @if($this->sortField === 'created_at')
                                <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    @endif
                                </svg>
                            @endif
                        </th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @if($this->shouldShowSkeleton())
                        @for($i = 0; $i < $perPage; $i++)
                            <tr class="animate-pulse border-b border-gray-200 dark:border-gray-700">
                                <td class="px-5 py-4">
                                    <div class="h-4 w-20 bg-blue-100 dark:bg-zinc-800 rounded"></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="h-4 w-32 bg-blue-100 dark:bg-zinc-800 rounded mb-2"></div>
                                    <div class="h-3 w-16 bg-blue-50 dark:bg-zinc-800/70 rounded"></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="h-4 w-48 bg-blue-100 dark:bg-zinc-800 rounded"></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="h-6 w-16 bg-pink-100 dark:bg-zinc-800 rounded-full"></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="h-4 w-24 bg-gray-100 dark:bg-zinc-800 rounded"></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex gap-2">
                                        <div class="h-8 w-8 bg-gray-100 dark:bg-zinc-800 rounded"></div>
                                        <div class="h-8 w-8 bg-gray-100 dark:bg-zinc-800 rounded"></div>
                                    </div>
                                </td>
                            </tr>
                        @endfor
                    @else
                        @forelse(($this->leaveRequests ?? []) as $leave)
                            <tr class="hover:bg-gray-100 dark:hover:bg-white/20 transition group border-b border-gray-200 dark:border-gray-700">
                                <td class="px-5 py-4">
                                    <input type="checkbox" 
                                        wire:model.live="selected" 
                                        value="{{ $leave->id }}" 
                                        class="accent-pink-500 rounded focus:ring-2 focus:ring-pink-400" />
                                </td>
                                <td class="px-5 py-4 text-gray-900 dark:text-white font-bold">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold shadow bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        {{ __(ucwords(str_replace('_', ' ', $leave->leave_type))) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                                    <div class="font-semibold text-base">{{ $leave->start_date->format('M j, Y') }} - {{ $leave->end_date->format('M j, Y') }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $leave->days_requested }} {{ __('days') }}</div>
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300 max-w-xs truncate">
                                    {{ Str::limit($leave->reason, 50) }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold shadow bg-{{ $this->getStatusColor($leave->status) }}-100 text-{{ $this->getStatusColor($leave->status) }}-800 dark:bg-{{ $this->getStatusColor($leave->status) }}-900/20 dark:text-{{ $this->getStatusColor($leave->status) }}-200">
                                        {{ $this->getStatusText($leave->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-semibold">
                                    <span class="truncate text-blue-600 dark:text-blue-400">
                                        {{ $leave->created_at->translatedFormat('j M Y, H:i') }}
                                    </span>
                                </td>
                                <td class="px-3 py-4">
                                    <span class="flex gap-2">
                                        @can('edit_my_leave')
                                            @if($leave->isPending())
                                                <flux:button
                                                    wire:click="confirmEdit({{ $leave->id }})"
                                                    variant="primary"
                                                    color="blue"
                                                    size="sm"
                                                    icon="pencil-square"
                                                    :title="__('Edit')"
                                                />
                                            @endif
                                        @endcan
                                        @can('delete_my_leave')
                                            @if($leave->isPending())
                                                <flux:button
                                                    wire:click="confirmDelete({{ $leave->id }})"
                                                    variant="danger"
                                                    color="red"
                                                    size="sm"
                                                    icon="trash"
                                                    :title="__('Cancel')"
                                                />
                                            @endif
                                        @endcan
                                        @if(!$leave->isPending())
                                            <span class="text-gray-400 dark:text-gray-600 text-sm">{{ __('No actions available') }}</span>
                                        @endif
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-8 h-8 text-gray-300 dark:text-zinc-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 4v10a2 2 0 002 2h4a2 2 0 002-2V11M9 11h6"></path>
                                        </svg>
                                        <h3 class="text-lg font-medium mb-2">{{ __('No leave requests found.') }}</h3>
                                        <p class="mb-4">{{ __('Get started by creating your first leave request.') }}</p>
                                        <a href="{{ route('leave.apply') }}" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            {{ __('Submit your first leave request') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
            
            <div class="mt-6">
                @if($this->leaveRequests && !$this->shouldShowSkeleton())
                    {{ $this->leaveRequests->links() }}
                @endif
            </div>
        </div>
    </div>

    <!-- Edit Confirmation Modal -->
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
            <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-md w-full border border-gray-100 dark:border-zinc-800">
                <h3 class="text-xl font-bold mb-4 text-blue-600 dark:text-blue-400 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    {{ __('Confirm Edit') }}
                </h3>
                <p class="mb-6 text-zinc-700 dark:text-zinc-300">
                    {{ __('Are you sure you want to edit this leave request?') }}
                </p>
                <div class="flex justify-end gap-3">
                    <button wire:click="editConfirmed"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                        @if ($isLoadingEdit) disabled @endif>
                        {{ $isLoadingEdit ? __('Redirecting...') : __('Yes, Edit Leave') }}
                    </button>
                    <button wire:click="$set('showEditModal', false)"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
            <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-md w-full border border-gray-100 dark:border-zinc-800">
                <h3 class="text-xl font-bold mb-4 text-red-600 dark:text-red-400 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    {{ __('Cancel Leave Request') }}
                </h3>
                <p class="mb-6 text-zinc-700 dark:text-zinc-300">
                    {{ __('Are you sure you want to cancel this leave request? This action cannot be undone.') }}
                </p>
                <div class="flex justify-end gap-3">
                    <button wire:click="cancelLeave"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-red-500 transition"
                        @if ($isLoadingDelete) disabled @endif>
                        {{ $isLoadingDelete ? __('Cancelling...') : __('Yes, Cancel Leave') }}
                    </button>
                    <button wire:click="$set('showDeleteModal', false)"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                        {{ __('Keep Request') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showBulkDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
            <div class="bg-gradient-to-br from-pink-50/80 via-white/80 to-red-100/80 dark:from-zinc-900/80 dark:via-zinc-800/80 dark:to-zinc-900/80 backdrop-blur-xl rounded-xl shadow-2xl p-10 max-w-md w-full border border-pink-200 dark:border-zinc-800">
                <h3 class="text-2xl font-extrabold text-pink-600 dark:text-pink-400 flex items-center gap-2 mb-4">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    {{ __('Confirm Bulk Delete') }}
                </h3>
                <p class="mb-6 text-zinc-700 dark:text-zinc-300 font-semibold">
                    {{ __('Are you sure you want to delete the selected leave requests? This action cannot be undone.') }}
                </p>
                <div class="flex justify-end gap-3">
                    <button wire:click="bulkDelete"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-red-500 transition"
                        @if ($isLoadingBulkDelete) disabled @endif>
                        {{ $isLoadingBulkDelete ? __('Deleting...') : __('Delete') }}
                    </button>
                    <button wire:click="$set('showBulkDeleteModal', false)"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', function () {
        Livewire.on('download-csv', function (data) {
            const blob = new Blob([data[0].data], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', data[0].filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        });
    });
</script>