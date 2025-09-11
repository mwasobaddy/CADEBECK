<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\LeaveRequest;
use App\Models\Employee;
use App\Models\User;
use App\Models\Audit;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public $showFilters = false;
    public ?int $editingId = null;
    public $showDeleteModal = false;
    public $showEditModal = false;
    public $showExportModal = false;
    public $pendingDeleteId = null;
    public $pendingEditId = null;
    public $isLoadingDelete = false;
    public $isLoadingEdit = false;
    public $isLoadingBulkDelete = false;
    public $isLoadingExport = false;
    public $isLoadingImport = false;
    public $search = '';
    public $filterStatus = '';
    public $filterType = '';
    public $selected = [];
    public $selectAll = false;
    public $showBulkDeleteModal = false;
    public $perPage = 10;
    public $isSearching = false;
    public $isFiltering = false;
    public $isPaginating = false;
    public $isLoadingData = false;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

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
        $query = LeaveRequest::with(['employee.user', 'employee.supervisor', 'approver']);

        // Apply hierarchical access control using policy scope
        $query = \App\Policies\LeaveRequestPolicy::scopeViewableBy($query, $user);

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('reason', 'like', '%' . $this->search . '%')
                    ->orWhere('leave_type', 'like', '%' . $this->search . '%')
                    ->orWhereHas('employee.user', function ($userQuery) {
                        $userQuery->where('first_name', 'like', '%' . $this->search . '%')
                                ->orWhere('other_names', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%');
                    });
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
        if ($this->sortField === 'employee_name') {
            $query->leftJoin('employees', 'leave_requests.employee_id', '=', 'employees.id')
                ->leftJoin('users', 'employees.user_id', '=', 'users.id')
                ->select('leave_requests.*')
                ->orderByRaw("CONCAT(users.first_name, ' ', users.other_names) $direction");
        } elseif ($this->sortField === 'leave_type') {
            $query->orderBy('leave_type', $direction);
        } elseif ($this->sortField === 'status') {
            $query->orderBy('status', $direction);
        } elseif ($this->sortField === 'start_date') {
            $query->orderBy('start_date', $direction);
        } elseif ($this->sortField === 'end_date') {
            $query->orderBy('end_date', $direction);
        } else {
            $query->orderBy('created_at', $direction);
        }

        return $query->paginate($this->perPage);
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

    public function updateSelectAllState(): void
    {
        $leaveRequests = $this->leaveRequests;
        if ($leaveRequests && $leaveRequests->count() > 0) {
            $currentPageIds = $leaveRequests->pluck('id')->toArray();
            $this->selectAll = count($currentPageIds) > 0 &&
                count(array_intersect($this->selected, $currentPageIds)) === count($currentPageIds);
        } else {
            $this->selectAll = false;
        }
    }

    public function toggleSelectAll(): void
    {
        $leaveRequests = $this->leaveRequests;
        if ($leaveRequests && $leaveRequests->count() > 0) {
            $currentPageIds = $leaveRequests->pluck('id')->toArray();
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
        $query = LeaveRequest::query();

        // Apply the same hierarchical filtering using policy scope
        $user = Auth::user();
        $query = \App\Policies\LeaveRequestPolicy::scopeViewableBy($query, $user);

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('reason', 'like', '%' . $this->search . '%')
                    ->orWhere('leave_type', 'like', '%' . $this->search . '%')
                    ->orWhereHas('employee.user', function ($userQuery) {
                        $userQuery->where('first_name', 'like', '%' . $this->search . '%')
                                ->orWhere('other_names', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%');
                    });
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
        $leaveRequests = LeaveRequest::whereIn('id', $this->selected)->get();
        LeaveRequest::whereIn('id', $this->selected)->delete();

        // Log the bulk delete action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'bulk_delete',
            'target_type' => LeaveRequest::class,
            'details' => json_encode(['leave_request_ids' => $this->selected]),
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
        $leaveRequests = LeaveRequest::whereIn('id', $this->selected)->with(['employee.user', 'approver'])->get();

        // Log the export selected action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'export_selected',
            'target_type' => LeaveRequest::class,
            'details' => json_encode(['leave_request_ids' => $this->selected]),
        ]);

        $csvData = "ID,Employee Name,Leave Type,Start Date,End Date,Days,Status,Reason,Created At\n";
        foreach ($leaveRequests as $leave) {
            $employeeName = $leave->employee ? ($leave->employee->user->first_name . ' ' . $leave->employee->user->other_names) : 'N/A';
            $csvData .= '"' . $leave->id . '","' .
                       str_replace('"', '""', $employeeName) . '","' .
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

    public function exportAll(): void
    {
        $this->isLoadingExport = true;
        $query = LeaveRequest::with(['employee.user', 'approver']);

        // Apply the same hierarchical filtering using policy scope
        $user = Auth::user();
        $query = \App\Policies\LeaveRequestPolicy::scopeViewableBy($query, $user);

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('reason', 'like', '%' . $this->search . '%')
                    ->orWhere('leave_type', 'like', '%' . $this->search . '%')
                    ->orWhereHas('employee.user', function ($userQuery) {
                        $userQuery->where('first_name', 'like', '%' . $this->search . '%')
                                ->orWhere('other_names', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%');
                    });
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

        $leaveRequests = $query->orderByDesc('created_at')->get();

        // Log the export all action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'export_all',
            'target_type' => LeaveRequest::class,
            'details' => json_encode(['total_requests' => $leaveRequests->count()]),
        ]);

        $csvData = "ID,Employee Name,Leave Type,Start Date,End Date,Days,Status,Reason,Created At\n";
        foreach ($leaveRequests as $leave) {
            $employeeName = $leave->employee ? ($leave->employee->user->first_name . ' ' . $leave->employee->user->other_names) : 'N/A';
            $csvData .= '"' . $leave->id . '","' .
                       str_replace('"', '""', $employeeName) . '","' .
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
            'filename' => 'all_leave_requests_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Leave requests exported successfully.')]);
    }

    public function confirmEdit($id): void
    {
        $this->pendingEditId = $id;
        $this->showEditModal = true;
    }

    public function confirmDelete($id): void
    {
        $this->pendingDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function editConfirmed(): void
    {
        $this->isLoadingEdit = true;
        $leaveRequest = LeaveRequest::findOrFail($this->pendingEditId);
        $this->showEditModal = false;
        $this->isLoadingEdit = false;
        $this->redirectRoute('all-leave.edit', ['id' => $leaveRequest->id]);
    }

    public function deleteConfirmed(): void
    {
        $this->isLoadingDelete = true;
        $leaveRequest = LeaveRequest::findOrFail($this->pendingDeleteId);
        $leaveRequest->delete();

        // Log the delete action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'delete',
            'target_type' => LeaveRequest::class,
            'target_id' => $leaveRequest->id,
            'details' => json_encode([
                'leave_type' => $leaveRequest->leave_type,
                'employee_id' => $leaveRequest->employee_id,
                'start_date' => $leaveRequest->start_date->format('Y-m-d'),
                'end_date' => $leaveRequest->end_date->format('Y-m-d'),
                'days_requested' => $leaveRequest->days_requested,
                'reason' => $leaveRequest->reason
            ]),
        ]);

        $this->resetForm();
        $this->showDeleteModal = false;
        $this->isLoadingDelete = false;
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Leave request deleted successfully.')]);
    }

    public function resetForm(): void
    {
        $this->form = [
            'leave_type' => '',
            'start_date' => '',
            'end_date' => '',
            'days_requested' => '',
            'reason' => '',
        ];
        $this->editingId = null;
    }

    public function createNewRequest(): void
    {
        $this->redirectRoute('leave.apply');
    }

    public function shouldShowSkeleton(): bool
    {
        return $this->isLoadingBulkDelete ||
               $this->isLoadingDelete ||
               $this->isLoadingEdit ||
               $this->isSearching ||
               $this->isFiltering ||
               $this->isPaginating ||
               $this->isLoadingExport ||
               $this->isLoadingData;
    }

    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
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
                <a href="{{ route('leave.apply') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('leave.apply*') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}">
                    {{ __('New Leave Request') }}
                </a>
            </div>
        </nav>
    </div>

    @can('manage_all_leaves')
        <!-- Card Container for Table -->
        <div class="relative bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
            <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
                <div class="flex items-center gap-3">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                        {{ __('All Leave Requests') }}
                        <span class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                    </h1>
                </div>
                <div class="flex items-center gap-3">
                    @can('export_all_leaves')
                        <button type="button" wire:click="exportAll"
                            class="flex items-center gap-2 px-2 lg:px-4 py-2 rounded-full border border-purple-200 dark:border-purple-700 text-purple-600 dark:text-purple-400 bg-purple-50/80 dark:bg-purple-900/20 hover:bg-purple-100/80 dark:hover:bg-purple-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-purple-400 transition"
                            @if ($isLoadingExport) disabled @endif>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <span class="hidden lg:inline">{{ $isLoadingExport ? __('Exporting...') : __('Export All') }}</span>
                        </button>
                    @endcan
                    @can('apply_for_leave')
                        <a href="{{ route('leave.apply') }}"
                            class="flex items-center gap-2 px-2 lg:px-4 py-2 rounded-full border border-blue-200 dark:border-blue-700 text-blue-600 dark:text-blue-400 bg-blue-50/80 dark:bg-blue-900/20 hover:bg-blue-100/80 dark:hover:bg-blue-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span class="hidden lg:inline">{{ __('Request Leave') }}</span>
                        </a>
                    @endcan
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

            @if (count($selected) > 0)
                <div class="flex items-center justify-between flex-wrap mt-6 p-4 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 dark:from-zinc-800/50 dark:to-zinc-700/50 rounded-xl border border-blue-200 dark:border-zinc-700 backdrop-blur-sm">
                    <div class="flex items-center gap-2 py-2">
                        <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                            {{ count($selected) }} {{ __('item(s) selected') }}
                        </span>
                        @if(count($selected) < (is_countable($this->leaveRequests) ? count($this->leaveRequests) : 0))
                            <button type="button" wire:click="selectAllData"
                                class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                {{ __('Select all') }} {{ $this->leaveRequests ? $this->leaveRequests->total() : 0 }}{{ __('items') }}
                            </button>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        @can('export_all_leaves')
                            <button type="button" wire:click="exportSelected"
                                class="flex items-center gap-2 px-4 py-2 rounded-xl border border-purple-200 dark:border-purple-700 text-purple-600 dark:text-purple-400 bg-purple-50/80 dark:bg-purple-900/20 hover:bg-purple-100/80 dark:hover:bg-purple-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-purple-400 transition"
                                @if ($isLoadingExport) disabled @endif>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                {{ $isLoadingExport ? __('Exporting...') : __('Export Selected') }}
                            </button>
                        @endcan
                        @can('delete_all_leaves')
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
                                            bg-transparent text-pink-500 border border-gray-500 p-[6px]
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
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('employee_name')">
                                {{ __('Employee') }}
                                @if($this->sortField === 'employee_name')
                                    <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        @endif
                                    </svg>
                                @endif
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
                                        <div class="flex items-center gap-2 py-2">
                                            <div>
                                                <div class="font-semibold">{{ $leave->employee->user->first_name }} {{ $leave->employee->user->other_names }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $leave->employee->user->email }}</div>
                                            </div>
                                        </div>
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
                                            @can('view_all_leaves')
                                                <flux:button
                                                    wire:click="confirmEdit({{ $leave->id }})"
                                                    variant="primary"
                                                    color="green"
                                                    size="sm"
                                                    icon="eye"
                                                    :title="__('View')"
                                                />
                                            @endcan
                                            @can('edit_all_leaves')
                                                @if($leave->status === 'pending')
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
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="w-8 h-8 text-gray-300 dark:text-zinc-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 4v10a2 2 0 002 2h4a2 2 0 002-2V11M9 11h6"></path>
                                            </svg>
                                            <h3 class="text-lg font-medium mb-2">{{ __('No leave requests found.') }}</h3>
                                            <p class="mb-4">{{ __('Leave requests will appear here once submitted.') }}</p>
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
                        {{ __('Delete Leave Request') }}
                    </h3>
                    <p class="mb-6 text-zinc-700 dark:text-zinc-300">
                        {{ __('Are you sure you want to delete this leave request? This action cannot be undone.') }}
                    </p>
                    <div class="flex justify-end gap-3">
                        <button wire:click="deleteConfirmed"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-red-500 transition"
                            @if ($isLoadingDelete) disabled @endif>
                            {{ $isLoadingDelete ? __('Deleting...') : __('Yes, Delete Leave') }}
                        </button>
                        <button wire:click="$set('showDeleteModal', false)"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                            {{ __('Keep Request') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Bulk Delete Confirmation Modal -->
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

<script>
    document.addEventListener('livewire:initialized', function () {
        Livewire.on('download-csv', function (data) {
            const blob = new Blob([data[0].data], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            if (link.download !== undefined) {
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
            }
        });
    });
</script>
