<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Payroll;
use App\Models\Employee;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public ?Employee $employee = null;
    public string $selectedPeriod = '';
    public string $selectedStatus = '';
    public int $perPage = 10;
    public string $sortField = 'pay_date';
    public string $sortDirection = 'desc';
    public bool $showDetailsModal = false;
    public ?Payroll $selectedPayroll = null;
    public bool $isLoadingExport = false;
    public bool $isLoadingBulkDelete = false;
    public bool $isSearching = false;
    public bool $isFiltering = false;
    public bool $isPaginating = false;
    public bool $isLoadingData = false;
    public array $selected = [];
    public bool $selectAll = false;
    public bool $showBulkDeleteModal = false;
    public bool $editing = true;

    public function mount($employeeId): void
    {
        $this->employee = Employee::findOrFail($employeeId);
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

    public function viewPayrollDetails($payrollId): void
    {
        $this->selectedPayroll = Payroll::findOrFail($payrollId);
        $this->showDetailsModal = true;
    }

    public function closeDetailsModal(): void
    {
        $this->showDetailsModal = false;
        $this->selectedPayroll = null;
    }

    public function getPayrollsProperty()
    {
        $query = $this->employee->payrolls();

        if ($this->selectedPeriod) {
            $query->where('payroll_period', $this->selectedPeriod);
        }

        if ($this->selectedStatus) {
            $query->where('status', $this->selectedStatus);
        }

        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        $query->orderBy($this->sortField, $direction);

        return $query->paginate($this->perPage);
    }

    public function getPayrollPeriodsProperty()
    {
        return $this->employee->payrolls()
            ->select('payroll_period')
            ->distinct()
            ->orderBy('payroll_period', 'desc')
            ->pluck('payroll_period');
    }

    public function updatedSelectedPeriod(): void
    {
        $this->isFiltering = true;
        $this->resetPage();
        $this->updateSelectAllState();
        $this->isFiltering = false;
    }

    public function updatedSelectedStatus(): void
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
        $payrolls = $this->payrolls;
        if ($payrolls && $payrolls->count() > 0) {
            $currentPageIds = $payrolls->pluck('id')->toArray();
            $this->selectAll = count($currentPageIds) > 0 && 
                              count(array_intersect($this->selected, $currentPageIds)) === count($currentPageIds);
        } else {
            $this->selectAll = false;
        }
    }

    public function toggleSelectAll(): void
    {
        $payrolls = $this->payrolls;
        if ($payrolls && $payrolls->count() > 0) {
            $currentPageIds = $payrolls->pluck('id')->toArray();
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
        $query = $this->employee->payrolls();

        if ($this->selectedPeriod) {
            $query->where('payroll_period', $this->selectedPeriod);
        }

        if ($this->selectedStatus) {
            $query->where('status', $this->selectedStatus);
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
        $payrolls = Payroll::whereIn('id', $this->selected)->get();
        
        foreach ($payrolls as $payroll) {
            $payroll->delete();
        }

        $this->showBulkDeleteModal = false;
        $this->isLoadingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;
        $this->updateSelectAllState();
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Selected payroll records deleted successfully.')
        ]);
    }

    public function exportSelected(): void
    {
        $this->isLoadingExport = true;
        $payrolls = Payroll::whereIn('id', $this->selected)->with(['employee.user', 'payslips'])->get();
        $csvData = "ID,Employee,Payroll Period,Pay Date,Basic Salary,Gross Pay,Net Pay,Status\n";
        
        foreach ($payrolls as $payroll) {
            $csvData .= '"' . $payroll->id . '","' .
                str_replace('"', '""', $payroll->employee->user->first_name . ' ' . $payroll->employee->user->other_names) . '","' .
                str_replace('"', '""', $payroll->payroll_period) . '","' .
                $payroll->pay_date . '","' .
                $payroll->basic_salary . '","' .
                $payroll->gross_pay . '","' .
                $payroll->net_pay . '","' .
                $payroll->status . '"\n';
        }
        
        $this->isLoadingExport = false;
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'payroll_history_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Selected payroll records exported successfully.')
        ]);
    }

    public function exportAll(): void
    {
        $this->isLoadingExport = true;
        $query = $this->employee->payrolls()->with(['employee.user', 'payslips']);

        if ($this->selectedPeriod) {
            $query->where('payroll_period', $this->selectedPeriod);
        }

        if ($this->selectedStatus) {
            $query->where('status', $this->selectedStatus);
        }

        $payrolls = $query->orderByDesc('pay_date')->get();
        $csvData = "ID,Employee,Payroll Period,Pay Date,Basic Salary,Gross Pay,Net Pay,Status\n";
        
        foreach ($payrolls as $payroll) {
            $csvData .= '"' . $payroll->id . '","' .
                str_replace('"', '""', $payroll->employee->user->first_name . ' ' . $payroll->employee->user->other_names) . '","' .
                str_replace('"', '""', $payroll->payroll_period) . '","' .
                $payroll->pay_date . '","' .
                $payroll->basic_salary . '","' .
                $payroll->gross_pay . '","' .
                $payroll->net_pay . '","' .
                $payroll->status . '"\n';
        }
        
        $this->isLoadingExport = false;
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'all_payroll_history_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('All payroll records exported successfully.')
        ]);
    }

    public function shouldShowSkeleton(): bool
    {
        return $this->isLoadingBulkDelete || 
               $this->isSearching || 
               $this->isFiltering || 
               $this->isPaginating ||
               $this->isLoadingExport ||
               $this->isLoadingData;
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
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 z-10 relative border border-green-100 dark:border-zinc-800 ring-1 ring-green-200/30 dark:ring-zinc-700/40">
        <nav class="flex items-center justify-between md:justify-start gap-4 w-full">
            <div class="flex items-center gap-4">
                <a href="{{ route('employee.index') }}" class="border rounded-full py-2 px-2 md:px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.index') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}">
                    <span class="hidden md:flex">
                        {{ __('Employee List') }}
                    </span>
                    <span class="flex: md:hidden">
                        <flux:icon name="numbered-list" variant="solid" class="w-5 h-5" />
                    </span>
                </a>

                {{-- add employee link--}}
                @if($editing)
                <a href="{{ route('employee.show') }}" class="border rounded-full py-2 px-2 md:px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.show') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}">
                    <span class="hidden md:flex">
                        {{ __('Add Employee') }}
                    </span>
                    <span class="flex: md:hidden">
                        <flux:icon name="users" variant="solid" class="w-5 h-5" />
                    </span>
                </a>
                @endif

                {{-- edit employee link --}}
                <a href="{{ $editing && $employee ? route('employee.edit', $employee->id) : route('employee.show') }}" class="border rounded-full py-2 px-2 md:px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.show') || request()->routeIs('employee.edit') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}">
                    <span class="hidden md:flex">
                        {{ $editing ? __('Edit Employee') : __('Add Employee') }}
                    </span>
                    <span class="flex: md:hidden">
                        <flux:icon name="pencil-square" variant="solid" class="w-5 h-5" />
                    </span>
                </a>
            </div>
            
            <!-- Three dots dropdown menu -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-400 border
                        {{ request()->routeIs('employee.payroll.allowances') || request()->routeIs('employee.payroll.deductions') || request()->routeIs('employee.payroll.payslips') || request()->routeIs('employee.payroll.history') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400' }}">
                    <flux:icon name="ellipsis-vertical" variant="solid" class="w-5 h-5" />
                </button>
                
                <!-- Dropdown menu -->
                <div x-show="open" 
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="absolute right-0 mt-2 w-56 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-xl rounded-xl shadow-xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40 z-50"
                     style="display: none;">
                    <div class="py-2">
                        <a href="{{ route('employee.payroll.allowances', $employee->id) }}" 
                           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.allowances') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            {{ __('Allowances') }}
                        </a>
                        
                        <a href="{{ route('employee.payroll.deductions', $employee->id) }}" 
                           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.deductions') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            {{ __('Deductions') }}
                        </a>
                        
                        <a href="{{ route('employee.payroll.payslips', $employee->id) }}" 
                           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.payslips') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{ __('Payslips') }}
                        </a>
                        
                        <a href="{{ route('employee.payroll.history', $employee->id) }}" 
                           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.history') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ __('History') }}
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </div>

    <!-- Main Content Card -->
    <div class="relative bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <!-- Header with Icon -->
        <div class="flex items-center gap-3 mb-8">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            <h2 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-800 via-blue-500 to-green-500 tracking-tight drop-shadow-lg relative inline-block">
                {{ __('Payroll History') }}
                <span class="absolute -bottom-2 left-0 w-[140px] h-1 rounded-full bg-gradient-to-r from-blue-800 via-blue-500 to-green-500"></span>
            </h2>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between mb-6 gap-4">
            <div class="flex items-center gap-3">
                <!-- No create button for history as it's read-only -->
            </div>
            <div class="flex items-center gap-3">
                <button type="button" wire:click="exportAll"
                    class="flex items-center gap-2 px-4 py-2 rounded-full border border-purple-200 dark:border-purple-700 text-purple-600 dark:text-purple-400 bg-purple-50/80 dark:bg-purple-900/20 hover:bg-purple-100/80 dark:hover:bg-purple-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-purple-400 transition"
                    @if ($isLoadingExport) disabled @endif>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    <span class="hidden lg:inline">
                        {{ $isLoadingExport ? __('Exporting...') : __('Export All') }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-6 items-center mb-6">
            <select wire:model.live="selectedPeriod"
                class="px-3 py-2 rounded-3xl border border-blue-200 dark:border-blue-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                <option value="">{{ __('All Periods') }}</option>
                @foreach($this->payrollPeriods as $period)
                <option value="{{ $period }}">{{ $period }}</option>
                @endforeach
            </select>

            <select wire:model.live="selectedStatus"
                class="px-3 py-2 rounded-3xl border border-blue-200 dark:border-blue-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                <option value="">{{ __('All Status') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="processed">{{ __('Processed') }}</option>
                <option value="paid">{{ __('Paid') }}</option>
            </select>

            <select wire:model.live="perPage"
                class="px-3 py-2 rounded-3xl border border-blue-200 dark:border-blue-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>

        @if (count($selected) > 0)
            <div class="flex items-center justify-between flex-wrap mt-6 p-4 bg-gradient-to-r from-blue-50/80 to-green-50/80 dark:from-zinc-800/50 dark:to-zinc-700/50 rounded-xl border border-blue-200 dark:border-zinc-700 backdrop-blur-sm">
                <div class="flex items-center gap-2 py-2">
                    <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                        {{ count($selected) }} {{ __('item(s) selected') }}
                    </span>
                    @if(count($selected) < ($this->payrolls ? $this->payrolls->total() : 0))
                        <button type="button" wire:click="selectAllData"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            {{ __('Select all') }} {{ $this->payrolls ? $this->payrolls->total() : 0 }} {{ __('items') }}
                        </button>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="exportSelected"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl border border-purple-200 dark:border-purple-700 text-purple-600 dark:text-purple-400 bg-purple-50/80 dark:bg-purple-900/20 hover:bg-purple-100/80 dark:hover:bg-purple-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-purple-400 transition"
                        @if ($isLoadingExport) disabled @endif>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        {{ $isLoadingExport ? __('Exporting...') : __('Export Selected') }}
                    </button>
                    <button type="button" wire:click="bulkDeleteConfirm"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-pink-500 to-red-500 hover:from-pink-600 hover:to-red-600 text-white font-semibold shadow-lg focus:outline-none focus:ring-2 focus:ring-red-400 backdrop-blur-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        {{ __('Delete Selected') }}
                    </button>
                </div>
            </div>
        @endif

        <!-- Table -->
        <div class="overflow-x-auto bg-transparent">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead>
                    <tr class="h-16 bg-zinc-800/5 dark:bg-white/10 text-zinc-600 dark:text-white/70">
                        <th class="px-3 py-3 text-left font-semibold uppercase tracking-wider w-12">
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
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[120px]" wire:click="sortBy('payroll_period')">
                            {{ __('Period') }}
                            @if($this->sortField === 'payroll_period')
                                <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    @endif
                                </svg>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[120px]" wire:click="sortBy('pay_date')">
                            {{ __('Pay Date') }}
                            @if($this->sortField === 'pay_date')
                                <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    @endif
                                </svg>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[130px]" wire:click="sortBy('basic_salary')">
                            {{ __('Basic Salary') }}
                            @if($this->sortField === 'basic_salary')
                                <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    @endif
                                </svg>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[120px]" wire:click="sortBy('total_allowances')">
                            {{ __('Allowances') }}
                            @if($this->sortField === 'total_allowances')
                                <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    @endif
                                </svg>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[120px]" wire:click="sortBy('total_deductions')">
                            {{ __('Deductions') }}
                            @if($this->sortField === 'total_deductions')
                                <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    @endif
                                </svg>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[120px]" wire:click="sortBy('net_pay')">
                            {{ __('Net Pay') }}
                            @if($this->sortField === 'net_pay')
                                <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    @endif
                                </svg>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider min-w-[100px]">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider min-w-[100px]">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if($this->shouldShowSkeleton())
                        @for($i = 0; $i < $perPage; $i++)
                            <tr class="animate-pulse border-b border-gray-200 dark:border-gray-700">
                                <td class="px-3 py-4"><div class="h-4 w-4 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-4 py-4"><div class="h-4 w-20 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-4 py-4"><div class="h-4 w-20 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-4 py-4"><div class="h-4 w-16 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-4 py-4"><div class="h-4 w-16 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-4 py-4"><div class="h-4 w-16 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-4 py-4"><div class="h-4 w-16 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-4 py-4"><div class="h-4 w-16 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-4 py-4"><div class="flex gap-2"><div class="h-8 w-8 bg-gray-100 dark:bg-zinc-800 rounded"></div></div></td>
                            </tr>
                        @endfor
                    @else
                        @forelse($this->payrolls as $payroll)
                        <tr class="hover:bg-gray-100 dark:hover:bg-white/20 transition group border-b border-gray-200 dark:border-gray-700">
                            <td class="px-3 py-4">
                                <input type="checkbox" wire:model.live="selected" value="{{ $payroll->id }}" class="accent-pink-500 rounded focus:ring-2 focus:ring-pink-400" />
                            </td>
                            <td class="px-4 py-4 text-gray-900 dark:text-white font-semibold">
                                {{ $payroll->payroll_period }}
                            </td>
                        <td class="px-4 py-4 text-gray-900 dark:text-white">
                            {{ \Carbon\Carbon::parse($payroll->pay_date)->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-4 text-gray-900 dark:text-white font-medium">
                            <span class="text-blue-600 dark:text-blue-400">
                                KES {{ number_format($payroll->basic_salary, 2) }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-gray-900 dark:text-white font-medium">
                            <span class="text-green-600 dark:text-green-400">
                                +KES {{ number_format($payroll->total_allowances, 2) }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-gray-900 dark:text-white font-medium">
                            <span class="text-red-600 dark:text-red-400">
                                -KES {{ number_format($payroll->total_deductions, 2) }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-gray-900 dark:text-white font-bold">
                            <span class="text-lg text-green-600 dark:text-green-400">
                                KES {{ number_format($payroll->net_pay, 2) }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($payroll->status === 'paid')
                                    bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                @elseif($payroll->status === 'processed')
                                    bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                @else
                                    bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300
                                @endif">
                                {{ ucfirst($payroll->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <button wire:click="viewPayrollDetails({{ $payroll->id }})"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-8 h-8 text-gray-300 dark:text-zinc-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                {{ __('No payroll records found for this employee.') }}
                            </div>
                        </td>
                    </tr>
                    @endforelse
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $this->payrolls->links() }}
        </div>
    </div>

    <!-- Payroll Details Modal -->
    @if($showDetailsModal && $selectedPayroll)
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="relative p-8 border shadow-2xl rounded-2xl bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl w-full max-w-2xl border-blue-100 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    {{ __('Payroll Details') }} - {{ $selectedPayroll->payroll_period }}
                </h3>
                <button wire:click="closeDetailsModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Information -->
                <div class="space-y-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">
                        {{ __('Basic Information') }}
                    </h4>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Period') }}:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $selectedPayroll->payroll_period }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Pay Date') }}:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($selectedPayroll->pay_date)->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Status') }}:</span>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                @if($selectedPayroll->status === 'paid')
                                    bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                @elseif($selectedPayroll->status === 'processed')
                                    bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                @else
                                    bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300
                                @endif">
                                {{ ucfirst($selectedPayroll->status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Salary Breakdown -->
                <div class="space-y-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">
                        {{ __('Salary Breakdown') }}
                    </h4>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Basic Salary') }}:</span>
                            <span class="font-medium text-blue-600 dark:text-blue-400">KES {{ number_format($selectedPayroll->basic_salary, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Total Allowances') }}:</span>
                            <span class="font-medium text-green-600 dark:text-green-400">+KES {{ number_format($selectedPayroll->total_allowances, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('Total Deductions') }}:</span>
                            <span class="font-medium text-red-600 dark:text-red-400">-KES {{ number_format($selectedPayroll->total_deductions, 2) }}</span>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                            <div class="flex justify-between">
                                <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Net Pay') }}:</span>
                                <span class="text-lg font-bold text-green-600 dark:text-green-400">KES {{ number_format($selectedPayroll->net_pay, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-8">
                <button wire:click="closeDetailsModal"
                        class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Bulk Delete Confirmation Modal -->
    @if($showBulkDeleteModal)
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="relative p-8 border shadow-2xl rounded-2xl bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl w-full max-w-md border-red-100 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    {{ __('Confirm Bulk Delete') }}
                </h3>
                <button wire:click="$set('showBulkDeleteModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="mb-6">
                <p class="text-gray-600 dark:text-gray-400">
                    {{ __('Are you sure you want to delete') }} <strong>{{ count($this->selected) }}</strong> {{ __('selected payroll records? This action cannot be undone.') }}
                </p>
            </div>

            <div class="flex justify-end gap-4">
                <button wire:click="$set('showBulkDeleteModal', false)"
                        class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                    {{ __('Cancel') }}
                </button>
                <button wire:click="bulkDelete"
                        wire:loading.attr="disabled"
                        class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold shadow focus:outline-none focus:ring-2 focus:ring-red-400 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove>{{ __('Delete Selected') }}</span>
                    <span wire:loading class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('Deleting...') }}
                    </span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>