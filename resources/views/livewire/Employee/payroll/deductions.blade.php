<?php
use Livewiretes\Layout;
use Livewire\Volt\Component;
use App\Models\PayrollDeduction;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use App\Models\Audit;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public ?Employee $employee = null;
    public bool $showDeleteModal = false;
    public bool $showBulkDeleteModal = false;
    public bool $showViewModal = false;
    public bool $showEditModal = false;
    public bool $showReactivateModal = false;
    public $pendingDeleteId = null;
    public $pendingViewId = null;
    public $pendingEditId = null;
    public $pendingReactivateId = null;
    public $isLoadingDelete = false;
    public $isLoadingBulkDelete = false;
    public $isLoadingExport = false;
    public $isLoadingView = false;
    public $isLoadingEdit = false;
    public $isLoadingReactivate = false;
    public bool $isSearching = false;
    public bool $isFiltering = false;
    public bool $isPaginating = false;
    public bool $isLoadingData = false;
    public bool $showFilters = false;
    public array $selected = [];
    public bool $selectAll = false;

    public $search = '';
    public $filterType = '';
    public $filterStatus = '';
    public $perPage = 10;
    public $sortField = 'effective_date';
    public $sortDirection = 'desc';
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

    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
    }

    public function createDeduction(): void
    {
        $this->redirectRoute('employee.payroll.deductions.create', ['employeeId' => $this->employee->id], navigate: true);
    }

    public function editDeduction($deductionId): void
    {
        $this->redirectRoute('employee.payroll.deductions.edit', ['employeeId' => $this->employee->id, 'deductionId' => $deductionId], navigate: true);
    }

    public function confirmDelete($deductionId): void
    {
        $this->pendingDeleteId = $deductionId;
        $this->showDeleteModal = true;
    }

    public function confirmView($deductionId): void
    {
        $this->pendingViewId = $deductionId;
        $this->showViewModal = true;
    }

    public function confirmEdit($deductionId): void
    {
        $this->pendingEditId = $deductionId;
        $this->showEditModal = true;
    }

    public function confirmReactivate($deductionId): void
    {
        $this->pendingReactivateId = $deductionId;
        $this->showReactivateModal = true;
    }

    public function deleteConfirmed(): void
    {
        $this->isLoadingDelete = true;
        $deduction = PayrollDeduction::findOrFail($this->pendingDeleteId);
        $deduction->update(['status' => 'inactive']);

        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'deactivate_deduction',
            'target_type' => PayrollDeduction::class,
            'target_id' => $this->pendingDeleteId,
            'details' => json_encode(['deduction_id' => $this->pendingDeleteId]),
        ]);

        $this->showDeleteModal = false;
        $this->isLoadingDelete = false;
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Deduction deactivated successfully.'),
        ]);
    }

    public function viewConfirmed(): void
    {
        $this->isLoadingView = true;
        $deduction = PayrollDeduction::findOrFail($this->pendingViewId);
        $this->showViewModal = false;
        $this->isLoadingView = false;
        // For now, just redirect to edit form since we don't have a separate view
        $this->redirectRoute('employee.payroll.deductions.edit', ['employeeId' => $this->employee->id, 'deductionId' => $deduction->id], navigate: true);
    }

    public function editConfirmed(): void
    {
        $this->isLoadingEdit = true;
        $deduction = PayrollDeduction::findOrFail($this->pendingEditId);
        $this->showEditModal = false;
        $this->isLoadingEdit = false;
        $this->redirectRoute('employee.payroll.deductions.edit', ['employeeId' => $this->employee->id, 'deductionId' => $deduction->id], navigate: true);
    }

    public function reactivateConfirmed(): void
    {
        $this->isLoadingReactivate = true;
        $deduction = PayrollDeduction::findOrFail($this->pendingReactivateId);
        $deduction->update(['status' => 'active']);

        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'reactivate_deduction',
            'target_type' => PayrollDeduction::class,
            'target_id' => $this->pendingReactivateId,
            'details' => json_encode(['deduction_id' => $this->pendingReactivateId]),
        ]);

        $this->showReactivateModal = false;
        $this->isLoadingReactivate = false;
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Deduction reactivated successfully.'),
        ]);
    }

    public function getDeductionsProperty()
    {
        $query = $this->employee->payrollDeductions();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')->orWhere('deduction_type', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterType) {
            $query->where('deduction_type', $this->filterType);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        $query->orderBy($this->sortField, $direction);

        return $query->paginate($this->perPage);
    }

    public function getDeductionTypesProperty()
    {
        return [
            'insurance' => __('Insurance'),
            'loan' => __('Loan Repayment'),
            'tax' => __('Tax'),
            'pension' => __('Pension'),
            'other' => __('Other'),
        ];
    }

    public function updatedSearch(): void
    {
        $this->isSearching = true;
        $this->resetPage();
        $this->updateSelectAllState();
        $this->isSearching = false;
    }

    public function updatedFilterType(): void
    {
        $this->isFiltering = true;
        $this->resetPage();
        $this->updateSelectAllState();
        $this->isFiltering = false;
    }

    public function updatedFilterStatus(): void
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
        $deductions = $this->deductions;
        if ($deductions && $deductions->count() > 0) {
            $currentPageIds = $deductions->pluck('id')->toArray();
            $this->selectAll = count($currentPageIds) > 0 && count(array_intersect($this->selected, $currentPageIds)) === count($currentPageIds);
        } else {
            $this->selectAll = false;
        }
    }

    public function toggleSelectAll(): void
    {
        $deductions = $this->deductions;
        if ($deductions && $deductions->count() > 0) {
            $currentPageIds = $deductions->pluck('id')->toArray();
            if ($this->selectAll) {
                $this->selected = array_values(array_diff($this->selected, $currentPageIds));
                $this->selectAll = false;
            } else {
                $this->selected = array_values(array_unique(array_merge($this->selected, $currentPageIds)));
                $this->selectAll = true;
            }
        }
    }

    public function toggleSelection($deductionId): void
    {
        if (in_array($deductionId, $this->selected)) {
            $this->selected = array_values(array_diff($this->selected, [$deductionId]));
        } else {
            $this->selected[] = $deductionId;
        }
        $this->updateSelectAllState();
    }

    public function updatedSelected(): void
    {
        $this->updateSelectAllState();
    }

    public function selectAllData(): void
    {
        $query = $this->employee->payrollDeductions();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')->orWhere('deduction_type', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterType) {
            $query->where('deduction_type', $this->filterType);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
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
        $deductions = PayrollDeduction::whereIn('id', $this->selected)->get();

        foreach ($deductions as $deduction) {
            $deduction->update(['status' => 'inactive']);

            // Audit log for each deduction
            Audit::create([
                'actor_id' => Auth::id(),
                'action' => 'bulk_deactivate_deduction',
                'target_type' => PayrollDeduction::class,
                'target_id' => $deduction->id,
                'details' => json_encode(['deduction_id' => $deduction->id]),
            ]);
        }

        $this->showBulkDeleteModal = false;
        $this->isLoadingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;
        $this->updateSelectAllState();
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Selected deductions deactivated successfully.'),
        ]);
    }

    public function exportSelected(): void
    {
        $this->isLoadingExport = true;
        $deductions = PayrollDeduction::whereIn('id', $this->selected)->with('employee.user')->get();
        $csvData = "ID,Employee,Type,Description,Amount,Effective Date,Status\n";

        foreach ($deductions as $deduction) {
            $csvData .= '"' . $deduction->id . '","' . str_replace('"', '""', $deduction->employee->user->first_name . ' ' . $deduction->employee->user->other_names) . '","' . str_replace('"', '""', $this->deductionTypes[$deduction->deduction_type] ?? $deduction->deduction_type) . '","' . str_replace('"', '""', $deduction->description) . '","' . $deduction->amount . '","' . $deduction->effective_date . '","' . $deduction->status . '"\n';
        }

        // Audit log
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'export_selected_deductions',
            'target_type' => PayrollDeduction::class,
            'details' => json_encode(['deduction_ids' => $this->selected]),
        ]);

        $this->isLoadingExport = false;
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'deductions_' . now()->format('Y-m-d_H-i-s') . '.csv',
        ]);
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Selected deductions exported successfully.'),
        ]);
    }

    public function exportAll(): void
    {
        $this->isLoadingExport = true;
        $query = $this->employee->payrollDeductions()->with('employee.user');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')->orWhere('deduction_type', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterType) {
            $query->where('deduction_type', $this->filterType);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $deductions = $query->orderByDesc('effective_date')->get();
        $csvData = "ID,Employee,Type,Description,Amount,Effective Date,Status\n";

        foreach ($deductions as $deduction) {
            $csvData .= '"' . $deduction->id . '","' . str_replace('"', '""', $deduction->employee->user->first_name . ' ' . $deduction->employee->user->other_names) . '","' . str_replace('"', '""', $this->deductionTypes[$deduction->deduction_type] ?? $deduction->deduction_type) . '","' . str_replace('"', '""', $deduction->description) . '","' . $deduction->amount . '","' . $deduction->effective_date . '","' . $deduction->status . '"\n';
        }

        // Audit log
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'export_all_deductions',
            'target_type' => PayrollDeduction::class,
            'details' => json_encode(['employee_id' => $this->employee->id, 'total_deductions' => $deductions->count()]),
        ]);

        $this->isLoadingExport = false;
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'all_deductions_' . now()->format('Y-m-d_H-i-s') . '.csv',
        ]);
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('All deductions exported successfully.'),
        ]);
    }

    public function shouldShowSkeleton(): bool
    {
        return $this->isLoadingBulkDelete || $this->isLoadingDelete || $this->isLoadingView || $this->isLoadingEdit || $this->isLoadingReactivate || $this->isSearching || $this->isFiltering || $this->isPaginating || $this->isLoadingExport || $this->isLoadingData;
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
    <svg class="fixed -bottom-24 -right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0" viewBox="0 0 400 400"
        fill="none">
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
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 z-10 relative border border-green-100 dark:border-zinc-800 ring-1 ring-green-200/30 dark:ring-zinc-700/40">
        <nav class="flex items-center justify-between md:justify-start gap-4 w-full">
            <div class="flex items-center gap-4">
                <a href="{{ route('employee.index') }}"
                    class="border rounded-full py-2 px-2 md:px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.index') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}" wire:navigate>
                    <span class="hidden md:flex">
                        {{ __('Employee List') }}
                    </span>
                    <span class="flex: md:hidden">
                        <flux:icon name="numbered-list" variant="solid" class="w-5 h-5" />
                    </span>
                </a>

                {{-- add employee link --}}
                @if ($editing)
                    <a href="{{ route('employee.show') }}"
                        class="border rounded-full py-2 px-2 md:px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.show') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}" wire:navigate>
                        <span class="hidden md:flex">
                            {{ __('Add Employee') }}
                        </span>
                        <span class="flex: md:hidden">
                            <flux:icon name="users" variant="solid" class="w-5 h-5" />
                        </span>
                    </a>
                @endif

                {{-- edit employee link --}}
                <a href="{{ $editing && $employee ? route('employee.edit', $employee->id) : route('employee.show') }}"
                    class="border rounded-full py-2 px-2 md:px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.show') || request()->routeIs('employee.edit') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}" wire:navigate>
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
                        {{ request()->routeIs('employee.payroll.allowances') || request()->routeIs('employee.payroll.deductions') || request()->routeIs('employee.payroll.payslips') || request()->routeIs('employee.payroll.history', $employee->id) ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400' }}">
                    <flux:icon name="ellipsis-vertical" variant="solid" class="w-5 h-5" />
                </button>

                <!-- Dropdown menu -->
                <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95"
                    class="absolute right-0 mt-2 w-56 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-xl rounded-xl shadow-xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40 z-50"
                    style="display: none;">
                    <div class="py-2">
                        <a href="{{ route('employee.payroll.allowances', $employee->id) }}"
                            class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.allowances') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}" wire:navigate>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1">
                                </path>
                            </svg>
                            {{ __('Allowances') }}
                        </a>

                        <a href="{{ route('employee.payroll.deductions', $employee->id) }}"
                            class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.deductions') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}" wire:navigate>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            {{ __('Deductions') }}
                        </a>

                        <a href="{{ route('employee.payroll.payslips', $employee->id) }}"
                            class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.payslips') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}" wire:navigate>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            {{ __('Payslips') }}
                        </a>

                        <a href="{{ route('employee.payroll.history', $employee->id) }}"
                            class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.history', $employee->id) ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}" wire:navigate>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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
        <div class="flex justify-between mb-8 items-center">
            <div class="flex items-center">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path fill-rule="evenodd"
                    d="M2.25 2.25a.75.75 0 0 0 0 1.5H3v10.5a3 3 0 0 0 3 3h1.21l-1.172 3.513a.75.75 0 0 0 1.424.474l.329-.987h8.418l.33.987a.75.75 0 0 0 1.422-.474l-1.17-3.513H18a3 3 0 0 0 3-3V3.75h.75a.75.75 0 0 0 0-1.5H2.25Zm6.54 15h6.42l.5 1.5H8.29l.5-1.5Zm8.085-8.995a.75.75 0 1 0-.75-1.299 12.81 12.81 0 0 0-3.558 3.05L11.03 8.47a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 1 0 1.06 1.06l2.47-2.47 1.617 1.618a.75.75 0 0 0 1.146-.102 11.312 11.312 0 0 1 3.612-3.321Z"
                    clip-rule="evenodd" />
                </svg>
                <h2 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                    {{ __('Deductions for') }} {{ $employee->user->first_name }}
                    <span class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                </h2>
            </div>

            <div class="flex items-center gap-3">
                @can('export_deduction')
                    <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" wire:click="exportAll" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        {{ $isLoadingExport ? __('Exporting...') : __('Export All') }}
                    </flux:button>
                @else
                    <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" :disabled="true" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        {{ __('Export Denied') }}
                    </flux:button>
                @endcan
                @can('create_deduction')
                    <flux:button icon:trailing="plus" variant="primary" type="button" wire:click="createDeduction" class="flex flex-row items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        {{ __('Add Deduction') }}
                    </flux:button>
                @else
                    <flux:button icon:trailing="plus" variant="primary" type="button" :disabled="true" class="flex flex-row items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        {{ __('Adding Denied') }}
                    </flux:button>
                @endcan
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="flex flex-wrap gap-6 items-center mb-6">
            <div class="relative w-80">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-5 h-5 text-blue-200 dark:text-indigo-400 z-[1]" fill="none"
                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"
                            fill="none"></circle>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"></path>
                    </svg>
                </span>
                <input type="text" wire:model.live.debounce.500ms="search"
                    class="w-full pl-10 pr-4 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white transition shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md"
                    placeholder="{{ __('Search deductions...') }}">
            </div>

            <button type="button" wire:click="toggleFilters"
                class="flex items-center gap-1 px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 bg-white/80 dark:bg-zinc-900/80 text-blue-600 dark:text-indigo-300 hover:bg-blue-50/80 dark:hover:bg-zinc-800/80 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h8m-8 6h16"></path>
                </svg>
                <span class="hidden lg:inline">{{ __('Filters') }}</span>
            </button>
        </div>

        <!-- Advanced Filters -->
        @if ($showFilters)
            <div class="flex flex-wrap gap-6 mt-6 items-center animate-fade-in">
                <flux:select wire:model.live="filterType" placeholder="{{ __('All Types') }}" class="!ps-3 pe-4 !py-2 !rounded-full border !border-blue-200 dark:!border-indigo-700 !focus:ring-2 !focus:ring-blue-400 dark:!bg-zinc-800/80 dark:!text-white !shadow-sm !bg-white/80 dark:!bg-zinc-900/80 !backdrop-blur-md !w-fit !outline-none">
                    <flux:select.option value="">{{ __('All Types') }}</flux:select.option>
                    @foreach ($this->deductionTypes as $key => $value)
                    <flux:select.option value="{{ $key }}">{{ $value }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="filterStatus" placeholder="{{ __('All Status') }}" class="!ps-3 pe-4 !py-2 !rounded-full border !border-blue-200 dark:!border-indigo-700 !focus:ring-2 !focus:ring-blue-400 dark:!bg-zinc-800/80 dark:!text-white !shadow-sm !bg-white/80 dark:!bg-zinc-900/80 !backdrop-blur-md !w-fit !outline-none">
                    <flux:select.option value="">{{ __('All Status') }}</flux:select.option>
                    <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                    <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="perPage" class="!ps-3 pe-4 !py-2 !rounded-full border !border-blue-200 dark:!border-indigo-700 !focus:ring-2 !focus:ring-blue-400 dark:!bg-zinc-800/80 dark:!text-white !shadow-sm !bg-white/80 dark:!bg-zinc-900/80 !backdrop-blur-md !w-fit !outline-none">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                </flux:select>
            </div>
        @endif

        @if (count($selected) > 0)
            <div
                class="flex items-center justify-between flex-wrap mt-6 p-4 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 dark:from-zinc-800/50 dark:to-zinc-700/50 rounded-xl border border-blue-200 dark:border-zinc-700 backdrop-blur-sm">
                <div class="flex items-center gap-2 py-2">
                    <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                        {{ count($selected) }} {{ __('item(s) selected') }}
                    </span>
                    @if (count($selected) < ($this->deductions ? $this->deductions->total() : 0))
                        <button type="button" wire:click="selectAllData"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            {{ __('Select all') }} {{ $this->deductions ? $this->deductions->total() : 0 }}
                            {{ __('items') }}
                        </button>
                    @endif
                </div>
                <div class="flex items-end justify-end gap-3 md:col-span-2 lg:col-span-3">
                    @can('export_deduction')
                        <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" wire:click="exportSelected" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            {{ $isLoadingExport ? __('Exporting...') : __('Export Selected') }}
                        </flux:button>
                    @else
                        <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" :disabled="true" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            {{ __('Exporting Denied') }}
                        </flux:button>
                    @endcan
                    @can('delete_deduction')
                        <flux:button icon:trailing="trash" variant="primary" type="button" wire:click="bulkDeleteConfirm" class="flex flex-row items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                            {{ __('Delete Selected') }}
                        </flux:button>
                    @else
                        <flux:button icon:trailing="trash" variant="primary" type="button" :disabled="true" class="flex flex-row items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                            {{ __('Deleting Denied') }}
                        </flux:button>
                    @endcan
                </div>
            </div>
        @endif

        <!-- Table -->
        <div class="overflow-x-auto bg-transparent mt-6">
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
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[150px]"
                            wire:click="sortBy('deduction_type')">
                            {{ __('Type') }}
                            @if ($this->sortField === 'deduction_type')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @else
                                <flux:icon name="arrows-up-down" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider min-w-[200px]">
                            {{ __('Description') }}</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[120px]"
                            wire:click="sortBy('amount')">
                            {{ __('Amount') }}
                            @if ($this->sortField === 'amount')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @else
                                <flux:icon name="arrows-up-down" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[140px]"
                            wire:click="sortBy('effective_date')">
                            {{ __('Effective Date') }}
                            @if ($this->sortField === 'effective_date')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @else
                                <flux:icon name="arrows-up-down" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider min-w-[100px]">
                            {{ __('Status') }}
                        </th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider min-w-[100px]">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @if ($this->shouldShowSkeleton())
                        @for ($i = 0; $i < $perPage; $i++)
                            <tr class="animate-pulse border-b border-gray-200 dark:border-gray-700">
                                <td class="px-3 py-4">
                                    <div class="h-4 w-4 bg-orange-100 dark:bg-zinc-800 rounded"></div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="h-4 w-24 bg-orange-100 dark:bg-zinc-800 rounded"></div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="h-4 w-32 bg-orange-100 dark:bg-zinc-800 rounded"></div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="h-4 w-20 bg-orange-100 dark:bg-zinc-800 rounded"></div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="h-4 w-20 bg-orange-100 dark:bg-zinc-800 rounded"></div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="h-4 w-16 bg-orange-100 dark:bg-zinc-800 rounded"></div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex gap-2">
                                        <div class="h-8 w-8 bg-gray-100 dark:bg-zinc-800 rounded"></div>
                                    </div>
                                </td>
                            </tr>
                        @endfor
                    @else
                        @forelse($this->deductions as $deduction)
                            <tr class="hover:bg-gray-100 dark:hover:bg-white/20 group border-b border-gray-200 dark:border-gray-700 transition-all duration-500 ease-in-out" wire:loading.class.delay="opacity-50 dark:opacity-40">
                                <td class="px-3 py-4">
                                    <button type="button"
                                        wire:click="toggleSelection({{ $deduction->id }})"
                                        class="rounded focus:ring-2 focus:ring-pink-400 transition-colors duration-200
                                            @if(in_array($deduction->id, $selected))
                                                bg-pink-500 text-white p-[2px]
                                            @else
                                                bg-transparent text-pink-500 border border-gray-500 p-[6px]
                                            @endif
                                            flex items-center gap-2"
                                    >
                                        @if(in_array($deduction->id, $selected))
                                            <svg class="w-3 h-3 text-gray-800 font-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        @endif
                                    </button>
                                </td>
                                <td class="px-4 py-4 text-gray-900 dark:text-white font-medium">
                                    {{ $this->deductionTypes[$deduction->deduction_type] ?? $deduction->deduction_type }}
                                </td>
                                <td class="px-4 py-4 text-gray-900 dark:text-white">
                                    {{ $deduction->description }}
                                </td>
                                <td class="px-4 py-4 text-gray-900 dark:text-white font-semibold">
                                    <span class="text-red-600 dark:text-red-400">
                                        -USD {{ number_format($deduction->amount, 2) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($deduction->effective_date)->format('M d, Y') }}
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $deduction->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                                        {{ ucfirst($deduction->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2 py-2">
                                        <flux:button wire:click="confirmView({{ $deduction->id }})" variant="primary"
                                            color="green" size="sm" icon="eye"
                                            title="{{ __('View Deduction') }}" />

                                        @can('edit_deduction')
                                            @if ($deduction->status === 'active')
                                                <flux:button wire:click="confirmEdit({{ $deduction->id }})"
                                                    variant="primary" color="blue" size="sm" icon="pencil-square"
                                                    title="{{ __('Edit') }}" />
                                            @else
                                                <flux:button disabled variant="primary" color="gray" size="sm"
                                                    icon="pencil-square"
                                                    title="{{ __('Cannot edit inactive deduction') }}" />
                                            @endif
                                        @else
                                            <flux:button disabled variant="primary" color="gray" size="sm"
                                                icon="pencil-square" title="{{ __('No permission to edit') }}" />
                                        @endcan

                                        @can('delete_deduction')
                                            @if ($deduction->status === 'active')
                                                <flux:button wire:click="confirmDelete({{ $deduction->id }})"
                                                    variant="danger" color="red" size="sm" icon="trash"
                                                    title="{{ __('Deactivate Deduction') }}" />
                                            @else
                                                <flux:button wire:click="confirmReactivate({{ $deduction->id }})"
                                                    variant="primary" color="green" size="sm" icon="arrow-path"
                                                    title="{{ __('Reactivate Deduction') }}" />
                                            @endif
                                        @else
                                            <flux:button disabled variant="primary" color="gray" size="sm"
                                                icon="trash" title="{{ __('No permission to deactivate') }}" />
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-8 h-8 text-gray-300 dark:text-zinc-700" fill="none"
                                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7">
                                            </path>
                                            <circle cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="2" fill="none"></circle>
                                        </svg>
                                        {{ __('No deductions found for this employee.') }}
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
            {{ $this->deductions->links() }}
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
            <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-md w-full border border-gray-100 dark:border-zinc-800">
                <h3 class="text-xl font-bold mb-4 text-red-600 dark:text-red-400 flex items-center gap-2">
                    <flux:icon name="trash" class="w-6 h-6" />
                    {{ __('Confirm Deactivation') }}
                </h3>
                <p class="mb-6 text-zinc-700 dark:text-zinc-300">
                    {{ __('Are you sure you want to deactivate this deduction? This action will make it inactive.') }}
                </p>
                <div class="flex justify-end gap-3">
                    <flux:button icon:trailing="trash" variant="primary" type="button" wire:click="deleteConfirmed" class="flex flex-row items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                        {{ $isLoadingDelete ? __('Deactivating...') : __('Deactivate') }}
                    </flux:button>
                    <flux:button icon:trailing="x-mark" variant="primary" type="button" wire:click="$set('showDeleteModal', false)" class="flex flex-row items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-800 px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    <!-- Bulk Delete Confirmation Modal -->
    @if ($showBulkDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
            <div class="bg-gradient-to-br from-pink-50/80 via-white/80 to-red-100/80 dark:from-zinc-900/80 dark:via-zinc-800/80 dark:to-zinc-900/80 backdrop-blur-xl rounded-xl shadow-2xl p-10 max-w-md w-full border border-pink-200 dark:border-zinc-800">
                <h3 class="text-xl font-bold mb-4 text-red-600 dark:text-red-400 flex items-center gap-2">
                    <flux:icon name="trash" class="w-6 h-6" />
                    {{ __('Confirm Deactivation') }}
                </h3>
                <p class="mb-6 text-zinc-700 dark:text-zinc-300 font-semibold">
                    {{ __('Are you sure you want to deactivate the selected deductions? This action will make them inactive.') }}
                </p>
                <div class="flex items-end justify-end gap-3 md:col-span-2 lg:col-span-3">
                    <flux:button icon:trailing="trash" variant="primary" type="button" wire:click="bulkDelete" class="flex flex-row items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                        {{ $isLoadingBulkDelete ? __('Deactivating...') : __('Deactivate Selected') }}
                    </flux:button>
                    <flux:button icon:trailing="x-mark" variant="primary" type="button" wire:click="$set('showBulkDeleteModal', false)" class="flex flex-row items-center gap-2 bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-200 px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    <!-- View Deduction Modal -->
    @if ($showViewModal)
        <div class="fixed inset-0 z-50 flex items-start overflow-y-auto lg:py-8 justify-center bg-black/40 backdrop-blur-sm transition">
            <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-4xl w-full border border-gray-100 dark:border-zinc-800">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold mb-4 text-blue-600 dark:text-blue-400 flex items-center gap-2">
                        <flux:icon name="eye" class="w-6 h-6" />
                        {{ __('View Deduction') }}
                    </h3>
                    <button wire:click="$set('showViewModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                @if ($pendingViewId)
                    @php
                        $viewDeduction = PayrollDeduction::with('employee.user')->find($pendingViewId);
                    @endphp
                    @if ($viewDeduction)
                        <div class="space-y-6">
                            <!-- Employee Info -->
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                                <h4 class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-2">{{ __('Employee Information') }}</h4>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-blue-900 dark:text-blue-100">
                                            {{ $viewDeduction->employee->user->first_name }} {{ $viewDeduction->employee->user->other_names }}
                                        </p>
                                        <p class="text-sm text-blue-700 dark:text-blue-300">
                                            {{ __('Staff Number') }}: {{ $viewDeduction->employee->staff_number }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Deduction Details -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Deduction Type') }}</h4>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $this->deductionTypes[$viewDeduction->deduction_type] ?? $viewDeduction->deduction_type }}
                                    </p>
                                </div>

                                <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Amount') }}</h4>
                                    <p class="text-lg font-semibold text-red-600 dark:text-red-400">
                                        -USD {{ number_format($viewDeduction->amount, 2) }}
                                    </p>
                                </div>

                                <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Effective Date') }}</h4>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($viewDeduction->effective_date)->format('M d, Y') }}
                                    </p>
                                </div>

                                <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('End Date') }}</h4>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $viewDeduction->end_date ? \Carbon\Carbon::parse($viewDeduction->end_date)->format('M d, Y') : __('No end date') }}
                                    </p>
                                </div>

                                <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Status') }}</h4>
                                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                                        {{ $viewDeduction->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                                        {{ ucfirst($viewDeduction->status) }}
                                    </span>
                                </div>

                                <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Created At') }}</h4>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($viewDeduction->created_at)->format('M d, Y H:i') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Description -->
                            @if ($viewDeduction->description)
                                <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Description') }}</h4>
                                    <p class="text-gray-900 dark:text-white">{{ $viewDeduction->description }}</p>
                                </div>
                            @endif

                            <!-- Notes -->
                            @if ($viewDeduction->notes)
                                <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Notes') }}</h4>
                                    <p class="text-gray-900 dark:text-white">{{ $viewDeduction->notes }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                @endif

                <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="$set('showViewModal', false)"
                        class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                        {{ __('Close') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Confirmation Modal -->
    @if($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
            <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-md w-full border border-gray-100 dark:border-zinc-800">
                <h3 class="text-xl font-bold mb-4 text-blue-600 dark:text-blue-400 flex items-center gap-2">
                    <flux:icon name="pencil-square" class="w-6 h-6" />
                    {{ __('Confirm Edit') }}
                </h3>
                <p class="mb-6 text-zinc-700 dark:text-zinc-300">
                    {{ __('Are you sure you want to edit this deduction?') }}
                </p>
                <div class="flex items-end justify-end gap-3 md:col-span-2 lg:col-span-3">
                    <flux:button icon:trailing="pencil-square" variant="primary" type="button" wire:click="editConfirmed" class="flex flex-row items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        {{ __('Edit') }}
                    </flux:button>
                    <flux:button icon:trailing="x-mark" variant="primary" type="button" wire:click="$set('showEditModal', false)" class="flex flex-row items-center gap-2 bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-200 px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    <!-- Reactivate Deduction Modal -->
    @if ($showReactivateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
            <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-md w-full border border-gray-100 dark:border-zinc-800">
                <h3 class="text-xl font-bold mb-4 text-green-600 dark:text-green-400 flex items-center gap-2">
                    <flux:icon name="arrow-path" class="w-6 h-6" />
                    {{ __('Confirm Reactivation') }}
                </h3>
                <p class="mb-6 text-zinc-700 dark:text-zinc-300">
                    {{ __('Are you sure you want to reactivate this deduction? This action will make it active again.') }}
                </p>
                <div class="flex items-end justify-end gap-3 md:col-span-2 lg:col-span-3">
                    <flux:button icon:trailing="arrow-path" variant="primary" type="button" wire:click="reactivateConfirmed" class="flex flex-row items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500">
                        {{ $isLoadingReactivate ? __('Reactivating...') : __('Reactivate') }}
                    </flux:button>
                    <flux:button icon:trailing="x-mark" variant="primary" type="button" wire:click="$set('showReactivateModal', false)" class="flex flex-row items-center gap-2 bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-200 px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', function() {
        Livewire.on('download-csv', function(data) {
            const blob = new Blob([data[0].data], {
                type: 'text/csv;charset=utf-8;'
            });
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
