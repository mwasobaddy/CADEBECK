<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public $filterPeriod = 'year'; // week, month, year, custom
    public $customStartDate = null;
    public $customEndDate = null;
    public $selectedEmployee = null;
    public $search = '';
    public $showDeleted = false;
    public $selectedRecords = [];
    public $selectAll = false;

    protected $queryString = [
        'filterPeriod' => ['except' => 'year'],
        'customStartDate' => ['except' => null],
        'customEndDate' => ['except' => null],
        'selectedEmployee' => ['except' => null],
        'search' => ['except' => ''],
        'showDeleted' => ['except' => false],
    ];

    public function mount()
    {
        $this->customStartDate = now()->startOfMonth()->format('Y-m-d');
        $this->customEndDate = now()->endOfMonth()->format('Y-m-d');
    }

    public function getEmployeesProperty()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return collect();
        }

        // Get employee IDs based on hierarchical permissions
        $employeeIds = $this->getViewableEmployeeIds();

        if (empty($employeeIds)) {
            return collect();
        }

        return Employee::active()
            ->whereIn('employees.id', $employeeIds)
            ->with('user', 'department', 'designation')
            ->join('users', 'employees.user_id', '=', 'users.id')
            ->orderBy('users.first_name')
            ->select('employees.*')
            ->get();
    }

    private function getViewableEmployeeIds()
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

    public function getAttendancesProperty()
    {
        $query = Attendance::with(['employee.user', 'employee.department', 'employee.designation']);

        // Apply hierarchical filtering
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            // Return empty paginated result instead of collection
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
        }

        $employeeIds = $this->getViewableEmployeeIds();
        
        if (empty($employeeIds)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
        }

        $query->whereIn('employee_id', $employeeIds);

        // Apply employee filter if selected
        if ($this->selectedEmployee) {
            $query->where('employee_id', $this->selectedEmployee);
        }

        // Apply date filtering
        $query = $this->applyDateFilter($query);

        // Apply search
        if ($this->search) {
            $query->whereHas('employee.user', function ($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('other_names', 'like', '%' . $this->search . '%');
            })->orWhereHas('employee', function ($q) {
                $q->where('staff_number', 'like', '%' . $this->search . '%');
            });
        }

        // Apply soft delete filter
        if (!$this->showDeleted) {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy('date', 'desc')
                    ->orderBy('clock_in_time', 'desc')
                    ->paginate(20);
    }

    private function applyDateFilter($query)
    {
        switch ($this->filterPeriod) {
            case 'week':
                return $query->whereBetween('date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
            case 'month':
                return $query->whereMonth('date', now()->month)
                            ->whereYear('date', now()->year);
            case 'year':
                return $query->whereYear('date', now()->year);
            case 'custom':
                if ($this->customStartDate && $this->customEndDate) {
                    return $query->whereBetween('date', [
                        Carbon::parse($this->customStartDate)->startOfDay(),
                        Carbon::parse($this->customEndDate)->endOfDay()
                    ]);
                }
                break;
        }

        return $query;
    }

    public function updatedFilterPeriod()
    {
        if ($this->filterPeriod === 'custom') {
            $this->customStartDate = now()->startOfMonth()->format('Y-m-d');
            $this->customEndDate = now()->endOfMonth()->format('Y-m-d');
        }
        $this->resetPage();
    }

    public function updatedSelectedEmployee()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedShowDeleted()
    {
        $this->resetPage();
    }

    public function updatedSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedRecords = $this->attendances->pluck('id')->toArray();
        } else {
            $this->selectedRecords = [];
        }
    }

    public function exportCsv()
    {
        $filename = 'attendance_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $attendances = $this->getAttendancesForExport();

        $callback = function () use ($attendances) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                __('Employee ID'),
                __('Employee Name'),
                __('Department'),
                __('Date'),
                __('Clock In'),
                __('Clock Out'),
                __('Total Hours'),
                __('Status'),
                __('Notes')
            ]);

            // CSV data
            foreach ($attendances as $attendance) {
                fputcsv($file, [
                    $attendance->employee->employee_id ?? '',
                    $attendance->employee->user->first_name . ' ' . ($attendance->employee->user->other_names ?? ''),
                    $attendance->employee->department->name ?? '',
                    $attendance->date->format('Y-m-d'),
                    $attendance->clock_in_time ? $attendance->clock_in_time->format('H:i:s') : '',
                    $attendance->clock_out_time ? $attendance->clock_out_time->format('H:i:s') : '',
                    $attendance->total_hours ?? '',
                    $attendance->status ?? '',
                    $attendance->notes ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getAttendancesForExport()
    {
        $query = Attendance::with(['employee.user', 'employee.department']);

        // Apply same filters as the main query
        $user = Auth::user();
        $employee = $user->employee;

        $employeeIds = $this->getViewableEmployeeIds();
        
        if (!empty($employeeIds)) {
            $query->whereIn('employee_id', $employeeIds);
        }

        if ($this->selectedEmployee) {
            $query->where('employee_id', $this->selectedEmployee);
        }

        $query = $this->applyDateFilter($query);

        if ($this->search) {
            $query->whereHas('employee.user', function ($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('other_names', 'like', '%' . $this->search . '%');
            })->orWhereHas('employee', function ($q) {
                $q->where('staff_number', 'like', '%' . $this->search . '%');
            });
        }

        if (!$this->showDeleted) {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy('date', 'desc')->get();
    }

    public function softDeleteSelected()
    {
        if (!Auth::user()->can('delete_other_attendance')) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('You do not have permission to delete attendance records.')
            ]);
            return;
        }

        if (empty($this->selectedRecords)) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => __('Please select records to delete.')
            ]);
            return;
        }

        Attendance::whereIn('id', $this->selectedRecords)->delete();

        $this->selectedRecords = [];
        $this->selectAll = false;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Selected attendance records have been deleted.')
        ]);
    }

    public function restoreSelected()
    {
        if (!Auth::user()->can('edit_other_attendance')) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('You do not have permission to restore attendance records.')
            ]);
            return;
        }

        if (empty($this->selectedRecords)) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => __('Please select records to restore.')
            ]);
            return;
        }

        Attendance::withTrashed()->whereIn('id', $this->selectedRecords)->restore();

        $this->selectedRecords = [];
        $this->selectAll = false;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Selected attendance records have been restored.')
        ]);
    }

    public function forceDeleteSelected()
    {
        if (!Auth::user()->can('delete_other_attendance')) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('You do not have permission to permanently delete attendance records.')
            ]);
            return;
        }

        if (empty($this->selectedRecords)) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => __('Please select records to permanently delete.')
            ]);
            return;
        }

        Attendance::withTrashed()->whereIn('id', $this->selectedRecords)->forceDelete();

        $this->selectedRecords = [];
        $this->selectAll = false;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Selected attendance records have been permanently deleted.')
        ]);
    }

    public function getTotalHoursProperty()
    {
        $query = Attendance::query();

        // Apply same filters as attendances
        $user = Auth::user();
        $employee = $user->employee;

        $employeeIds = $this->getViewableEmployeeIds();
        
        if (!empty($employeeIds)) {
            $query->whereIn('employee_id', $employeeIds);
        }

        if ($this->selectedEmployee) {
            $query->where('employee_id', $this->selectedEmployee);
        }

        $query = $this->applyDateFilter($query);

        if ($this->search) {
            $query->whereHas('employee.user', function ($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('other_names', 'like', '%' . $this->search . '%');
            })->orWhereHas('employee', function ($q) {
                $q->where('staff_number', 'like', '%' . $this->search . '%');
            });
        }

        if (!$this->showDeleted) {
            $query->whereNull('deleted_at');
        }

        return $query->sum('total_hours');
    }

    public function getAttendanceStatsProperty()
    {
        $query = Attendance::query();

        // Apply same filters
        $user = Auth::user();
        $employee = $user->employee;

        $employeeIds = $this->getViewableEmployeeIds();
        
        if (!empty($employeeIds)) {
            $query->whereIn('employee_id', $employeeIds);
        }

        if ($this->selectedEmployee) {
            $query->where('employee_id', $this->selectedEmployee);
        }

        $query = $this->applyDateFilter($query);

        if ($this->search) {
            $query->whereHas('employee.user', function ($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('other_names', 'like', '%' . $this->search . '%');
            })->orWhereHas('employee', function ($q) {
                $q->where('staff_number', 'like', '%' . $this->search . '%');
            });
        }

        if (!$this->showDeleted) {
            $query->whereNull('deleted_at');
        }

        $totalRecords = $query->count();
        $presentRecords = (clone $query)->where('status', 'present')->count();
        $absentRecords = (clone $query)->where('status', 'absent')->count();

        return [
            'total' => $totalRecords,
            'present' => $presentRecords,
            'absent' => $absentRecords,
            'present_percentage' => $totalRecords > 0 ? round(($presentRecords / $totalRecords) * 100, 1) : 0
        ];
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

    <!-- Breadcrumbs -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 z-10 relative border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <nav class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('attendance.manage') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none" wire:navigate>
                    {{ __('Attendance Management') }}
                </a>
            </div>
        </nav>
    </div>

    @can('view_own_attendance')
        <!-- Card Container for Content -->
        <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
            <!-- Header -->
            <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
                <div class="flex items-center gap-3">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                        {{ __('Attendance Management') }}
                        <span class="absolute -bottom-2 left-0 w-[200px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                    </h1>
                </div>
                <div class="flex items-center gap-3">
                    @can('view_other_attendance')
                        <button
                            wire:click="exportCsv"
                            class="flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{ __('Export CSV') }}
                        </button>
                    @else
                        <button
                            disabled
                            class="flex items-center gap-2 bg-gray-400 text-white px-6 py-2 rounded-full font-semibold shadow cursor-not-allowed"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{ __('Export Denied') }}
                        </button>
                    @endcan
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white/80 dark:bg-zinc-800/80 backdrop-blur-md rounded-xl p-6 shadow-sm border border-blue-200/30 dark:border-zinc-700/40 transition-all duration-300 hover:shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Total Records') }}</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->attendanceStats['total'] }}</p>
                        </div>
                        <div class="p-3 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white/80 dark:bg-zinc-800/80 backdrop-blur-md rounded-xl p-6 shadow-sm border border-green-200/30 dark:border-zinc-700/40 transition-all duration-300 hover:shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Present') }}</p>
                            <p class="text-2xl font-bold text-green-600">{{ $this->attendanceStats['present'] }}</p>
                            <p class="text-xs text-green-500 font-medium">{{ $this->attendanceStats['present_percentage'] }}%</p>
                        </div>
                        <div class="p-3 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white/80 dark:bg-zinc-800/80 backdrop-blur-md rounded-xl p-6 shadow-sm border border-red-200/30 dark:border-zinc-700/40 transition-all duration-300 hover:shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Absent') }}</p>
                            <p class="text-2xl font-bold text-red-600">{{ $this->attendanceStats['absent'] }}</p>
                        </div>
                        <div class="p-3 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-xl">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white/80 dark:bg-zinc-800/80 backdrop-blur-md rounded-xl p-6 shadow-sm border border-purple-200/30 dark:border-zinc-700/40 transition-all duration-300 hover:shadow-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Total Hours') }}</p>
                            <p class="text-2xl font-bold text-purple-600">{{ number_format($this->totalHours, 1) }}</p>
                        </div>
                        <div class="p-3 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white/80 dark:bg-zinc-800/80 backdrop-blur-md rounded-xl p-6 shadow-sm border border-blue-200/30 dark:border-zinc-700/40 mb-6">
                <div class="flex flex-wrap gap-8 items-center mb-4">
                    <div class="relative w-80">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-5 h-5 text-blue-400 dark:text-indigo-400 z-[1]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </span>
                        <input type="text" wire:model.live.debounce.300ms="search"
                            class="w-full pl-10 pr-4 py-2 rounded-full border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white transition shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md"
                            placeholder="{{ __('Search by name or ID...') }}">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Period Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Time Period') }}
                        </label>
                        <select
                            wire:model.live="filterPeriod"
                            class="w-full px-3 py-2 rounded-full border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md text-gray-900 dark:text-white transition shadow-sm"
                        >
                            <option value="week">{{ __('This Week') }}</option>
                            <option value="month">{{ __('This Month') }}</option>
                            <option value="year">{{ __('This Year') }}</option>
                            <option value="custom">{{ __('Custom Range') }}</option>
                        </select>
                    </div>

                    <!-- Employee Filter -->
                    @if($this->employees->count() > 1)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Employee') }}
                        </label>
                        <select
                            wire:model.live="selectedEmployee"
                            class="w-full px-3 py-2 rounded-full border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md text-gray-900 dark:text-white transition shadow-sm"
                        >
                            <option value="">{{ __('All Employees') }}</option>
                            @foreach($this->employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->user->first_name }} {{ $employee->user->other_names ?? '' }} ({{ $employee->staff_number }})</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <!-- Show Deleted -->
                    @can('delete_other_attendance')
                    <div class="flex items-end">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                wire:model.live="showDeleted"
                                class="rounded border-blue-200 dark:border-indigo-700 text-blue-600 focus:ring-blue-500 bg-white/80 dark:bg-zinc-900/80"
                            >
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Show Deleted') }}</span>
                        </label>
                    </div>
                    @endcan
                </div>

                <!-- Custom Date Range -->
                @if($this->filterPeriod === 'custom')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 pt-4 border-t border-blue-200/30 dark:border-zinc-700/40">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Start Date') }}
                        </label>
                        <input
                            type="date"
                            wire:model.live="customStartDate"
                            class="w-full px-3 py-2 rounded-full border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md text-gray-900 dark:text-white transition shadow-sm"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('End Date') }}
                        </label>
                        <input
                            type="date"
                            wire:model.live="customEndDate"
                            class="w-full px-3 py-2 rounded-full border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md text-gray-900 dark:text-white transition shadow-sm"
                        >
                    </div>
                </div>
                @endif
            </div>

            <!-- Bulk Actions -->
            @can('delete_other_attendance')
            @if(!empty($this->selectedRecords))
            <div class="bg-gradient-to-r from-yellow-50/80 to-orange-50/80 dark:from-yellow-900/20 dark:to-orange-900/20 backdrop-blur-md border border-yellow-200/60 dark:border-yellow-800/60 rounded-xl p-4 mb-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <span class="text-yellow-800 dark:text-yellow-200 font-medium">
                            {{ count($this->selectedRecords) }} {{ __('record(s) selected') }}
                        </span>
                    </div>
                    <div class="flex gap-2">
                        @if($this->showDeleted)
                            @can('edit_other_attendance')
                            <button
                                wire:click="restoreSelected"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-full font-medium transition-all duration-200 shadow-sm hover:shadow-md"
                            >
                                {{ __('Restore Selected') }}
                            </button>
                            @endcan
                            <button
                                wire:click="forceDeleteSelected"
                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-full font-medium transition-all duration-200 shadow-sm hover:shadow-md"
                            >
                                {{ __('Permanently Delete') }}
                            </button>
                        @else
                            <button
                                wire:click="softDeleteSelected"
                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-full font-medium transition-all duration-200 shadow-sm hover:shadow-md"
                            >
                                {{ __('Delete Selected') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            @endcan

            <!-- Attendance Table -->
            <div class="bg-white/80 dark:bg-zinc-800/80 backdrop-blur-md rounded-xl shadow-sm border border-blue-200/30 dark:border-zinc-700/40 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead>
                            <tr class="h-16 bg-zinc-800/5 dark:bg-white/10 text-zinc-600 dark:text-white/70">
                                @can('delete_other_attendance')
                                <th class="px-6 py-3 text-left">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectAll"
                                        class="rounded border-blue-200 dark:border-indigo-700 text-blue-600 focus:ring-blue-500 bg-white/80 dark:bg-zinc-900/80"
                                    >
                                </th>
                                @endcan
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Employee') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Date') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Clock In') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Clock Out') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Hours') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Status') }}
                                </th>
                                @if($this->showDeleted)
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ __('Deleted At') }}
                                </th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                            @forelse($this->attendances as $attendance)
                            <tr class="hover:bg-blue-50/30 dark:hover:bg-zinc-700/50 transition-colors duration-200 {{ $attendance->trashed() ? 'opacity-60 bg-red-50/30 dark:bg-red-900/10' : '' }}">
                                @can('delete_other_attendance')
                                <td class="px-6 py-4">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedRecords"
                                        value="{{ $attendance->id }}"
                                        class="rounded border-blue-200 dark:border-indigo-700 text-blue-600 focus:ring-blue-500 bg-white/80 dark:bg-zinc-900/80"
                                    >
                                </td>
                                @endcan
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white font-semibold">
                                                {{ substr($attendance->employee->user->first_name, 0, 1) }}{{ substr($attendance->employee->user->other_names ?? '', 0, 1) }}
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $attendance->employee->user->first_name }} {{ $attendance->employee->user->other_names ?? '' }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $attendance->employee->staff_number }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ $attendance->date->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ $attendance->clock_in_time ? $attendance->clock_in_time->format('H:i') : '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ $attendance->clock_out_time ? $attendance->clock_out_time->format('H:i') : '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ $attendance->total_hours ? number_format($attendance->total_hours, 2) : '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        @if($attendance->status === 'present')
                                            bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400
                                        @elseif($attendance->status === 'absent')
                                            bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400
                                        @elseif($attendance->status === 'late')
                                            bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400
                                        @else
                                            bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400
                                        @endif">
                                        {{ ucfirst($attendance->status ?? 'unknown') }}
                                    </span>
                                </td>
                                @if($this->showDeleted)
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ $attendance->deleted_at ? $attendance->deleted_at->format('M d, Y H:i') : '-' }}
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ Auth::user()->can('delete_other_attendance') ? 8 : 7 }}" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">{{ __('No attendance records found') }}</p>
                                        <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">{{ __('Try adjusting your filters or check back later.') }}</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($this->attendances->hasPages())
                <div class="bg-white/80 dark:bg-zinc-800/80 px-4 py-3 border-t border-blue-200/30 dark:border-zinc-700/40 sm:px-6">
                    {{ $this->attendances->links() }}
                </div>
                @endif
            </div>
        </div>
    @else
        <div class="flex items-center justify-center w-full">
            <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-10 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40 flex flex-col items-center gap-6 max-w-lg w-full">
                <svg class="w-16 h-16 text-red-400 mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" />
                </svg>
                <h2 class="text-2xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-red-600 via-red-500 to-red-400 flex items-center gap-2 drop-shadow-lg">
                    {{ __('Access Denied') }}
                </h2>
                <p class="text-lg text-gray-700 dark:text-gray-300 font-medium text-center">
                    {{ __('You do not have permission to view attendance records.') }}
                </p>
            </div>
        </div>
    @endcan
</div>