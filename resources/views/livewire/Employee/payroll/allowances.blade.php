<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\PayrollAllowance;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use App\Models\Audit;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public ?Employee $employee = null;
    public bool $showCreateModal = false;
    public bool $showDeleteModal = false;
    public bool $showEditModal = false;
    public bool $showViewModal = false;
    public bool $showBulkDeleteModal = false;
    public bool $showExportModal = false;
    public $pendingDeleteId = null;
    public $pendingEditId = null;
    public $pendingViewId = null;
    public $isLoadingDelete = false;
    public $isLoadingEdit = false;
    public $isLoadingView = false;
    public $isLoadingBulkDelete = false;
    public $isLoadingExport = false;
    public bool $showReactivateModal = false;
    public $pendingReactivateId = null;
    public $isLoadingReactivate = false;
    
    public $search = '';
    public $filterType = '';
    public $filterStatus = '';
    public $perPage = 10;
    public $sortField = 'effective_date';
    public $sortDirection = 'desc';
    public bool $editing = true;
    public bool $showFilters = false;
    public $selected = [];
    public $selectAll = false;
    public $isSearching = false;
    public $isFiltering = false;
    public $isPaginating = false;
    public $isLoadingData = false;

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

    public function openCreateModal(): void
    {
        $this->redirectRoute('employee.payroll.allowances.create', ['employeeId' => $this->employee->id], navigate: true);
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetAllowanceForm();
    }

    public function resetAllowanceForm(): void
    {
        $this->allowanceForm = [
            'allowance_type' => '',
            'description' => '',
            'amount' => '',
            'is_recurring' => true,
            'effective_date' => '',
            'end_date' => '',
            'notes' => '',
        ];
    }

    public function saveAllowance(): void
    {
        $rules = [
            'allowanceForm.allowance_type' => 'required|string|max:50',
            'allowanceForm.description' => 'required|string|max:255',
            'allowanceForm.amount' => 'required|numeric|min:0',
            'allowanceForm.effective_date' => 'required|date',
            'allowanceForm.end_date' => 'nullable|date|after:allowanceForm.effective_date',
            'allowanceForm.notes' => 'nullable|string|max:500',
        ];

        $this->validate($rules);

        $allowanceData = array_merge($this->allowanceForm, [
            'employee_id' => $this->employee->id,
            'status' => 'active',
        ]);

        $allowance = PayrollAllowance::create($allowanceData);

        // Audit log
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'create_allowance',
            'target_type' => PayrollAllowance::class,
            'target_id' => $allowance->id,
            'details' => json_encode($allowanceData),
        ]);

        $this->closeCreateModal();
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Allowance created successfully.')
        ]);
    }

    public function confirmDelete($allowanceId): void
    {
        $this->pendingDeleteId = $allowanceId;
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed(): void
    {
        $this->isLoadingDelete = true;
        $allowance = PayrollAllowance::findOrFail($this->pendingDeleteId);
        $allowance->update(['status' => 'inactive']);

        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'deactivate_allowance',
            'target_type' => PayrollAllowance::class,
            'target_id' => $this->pendingDeleteId,
            'details' => json_encode(['allowance_id' => $this->pendingDeleteId]),
        ]);

        $this->showDeleteModal = false;
        $this->isLoadingDelete = false;
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Allowance deactivated successfully.')
        ]);
    }

    public function getAllowancesProperty()
    {
        $query = $this->employee->payrollAllowances();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhere('allowance_type', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterType) {
            $query->where('allowance_type', $this->filterType);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        $query->orderBy($this->sortField, $direction);

        return $query->paginate($this->perPage);
    }

    public function getAllowanceTypesProperty()
    {
        return [
            'house' => __('House Allowance'),
            'transport' => __('Transport Allowance'),
            'medical' => __('Medical Allowance'),
            'overtime' => __('Overtime'),
            'bonus' => __('Bonus'),
            'other' => __('Other'),
        ];
    }

    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
    }

    public function confirmEdit($id): void
    {
        $this->pendingEditId = $id;
        $this->showEditModal = true;
    }

    public function confirmView($id): void
    {
        $this->pendingViewId = $id;
        $this->showViewModal = true;
    }

    public function editConfirmed(): void
    {
        $this->isLoadingEdit = true;
        $allowance = PayrollAllowance::findOrFail($this->pendingEditId);
        $this->showEditModal = false;
        $this->isLoadingEdit = false;
        $this->redirectRoute('employee.payroll.allowances.edit', ['employeeId' => $this->employee->id, 'allowanceId' => $allowance->id], navigate: true);
    }

    public function viewConfirmed(): void
    {
        $this->isLoadingView = true;
        $allowance = PayrollAllowance::findOrFail($this->pendingViewId);
        $this->showViewModal = false;
        $this->isLoadingView = false;
        $this->redirectRoute('employee.allowance.view', ['employeeId' => $this->employee->id, 'allowanceId' => $allowance->id], navigate: true);
    }

    public function confirmReactivate($allowanceId): void
    {
        $this->pendingReactivateId = $allowanceId;
        $this->showReactivateModal = true;
    }

    public function reactivateConfirmed(): void
    {
        $this->isLoadingReactivate = true;
        $allowance = PayrollAllowance::findOrFail($this->pendingReactivateId);
        $allowance->update(['status' => 'active']);

        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'reactivate_allowance',
            'target_type' => PayrollAllowance::class,
            'target_id' => $this->pendingReactivateId,
            'details' => json_encode(['allowance_id' => $this->pendingReactivateId]),
        ]);

        $this->showReactivateModal = false;
        $this->isLoadingReactivate = false;
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Allowance reactivated successfully.')
        ]);
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
        $allowances = $this->allowances;
        if ($allowances && $allowances->count() > 0) {
            $currentPageIds = $allowances->pluck('id')->toArray();
            $this->selectAll = count($currentPageIds) > 0 && 
                              count(array_intersect($this->selected, $currentPageIds)) === count($currentPageIds);
        } else {
            $this->selectAll = false;
        }
    }

    public function toggleSelectAll(): void
    {
        $allowances = $this->allowances;
        if ($allowances && $allowances->count() > 0) {
            $currentPageIds = $allowances->pluck('id')->toArray();
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
        $query = $this->employee->payrollAllowances();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhere('allowance_type', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterType) {
            $query->where('allowance_type', $this->filterType);
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
        PayrollAllowance::whereIn('id', $this->selected)->update(['status' => 'inactive']);

        // Audit log
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'bulk_deactivate_allowances',
            'target_type' => PayrollAllowance::class,
            'target_id' => null,
            'details' => json_encode(['allowance_ids' => $this->selected]),
        ]);

        $this->showBulkDeleteModal = false;
        $this->isLoadingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;
        $this->updateSelectAllState();
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Selected allowances deactivated successfully.')]);
    }

    public function exportSelected(): void
    {
        $this->isLoadingExport = true;
        $allowances = PayrollAllowance::whereIn('id', $this->selected)->with('employee.user')->get();
        $csvData = "ID,Employee Name,Allowance Type,Description,Amount,Effective Date,End Date,Status,Recurring\n";
        foreach ($allowances as $allowance) {
            $csvData .= '"' . $allowance->id . '","' .
                str_replace('"', '""', $allowance->employee->user->first_name . ' ' . $allowance->employee->user->other_names) . '","' .
                str_replace('"', '""', $this->allowanceTypes[$allowance->allowance_type] ?? $allowance->allowance_type) . '","' .
                str_replace('"', '""', $allowance->description) . '","' .
                $allowance->amount . '","' .
                $allowance->effective_date . '","' .
                ($allowance->end_date ?? 'N/A') . '","' .
                $allowance->status . '","' .
                ($allowance->is_recurring ? 'Yes' : 'No') . '"\n';
        }
        
        // Audit log
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'export_selected_allowances',
            'target_type' => PayrollAllowance::class,
            'target_id' => null,
            'details' => json_encode(['allowance_ids' => $this->selected]),
        ]);

        $this->isLoadingExport = false;
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'selected_allowances_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Selected allowances exported successfully.')]);
    }

    public function exportAll(): void
    {
        $this->isLoadingExport = true;
        $query = $this->employee->payrollAllowances()->with('employee.user');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhere('allowance_type', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterType) {
            $query->where('allowance_type', $this->filterType);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $allowances = $query->orderByDesc('created_at')->get();
        $csvData = "ID,Employee Name,Allowance Type,Description,Amount,Effective Date,End Date,Status,Recurring\n";
        foreach ($allowances as $allowance) {
            $csvData .= '"' . $allowance->id . '","' .
                str_replace('"', '""', $allowance->employee->user->first_name . ' ' . $allowance->employee->user->other_names) . '","' .
                str_replace('"', '""', $this->allowanceTypes[$allowance->allowance_type] ?? $allowance->allowance_type) . '","' .
                str_replace('"', '""', $allowance->description) . '","' .
                $allowance->amount . '","' .
                $allowance->effective_date . '","' .
                ($allowance->end_date ?? 'N/A') . '","' .
                $allowance->status . '","' .
                ($allowance->is_recurring ? 'Yes' : 'No') . '"\n';
        }
        
        // Audit log
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'export_all_allowances',
            'target_type' => PayrollAllowance::class,
            'target_id' => null,
            'details' => json_encode(['total_allowances' => $allowances->count(), 'employee_id' => $this->employee->id]),
        ]);

        $this->isLoadingExport = false;
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'all_allowances_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', ['type' => 'success', 'message' => __('All allowances exported successfully.')]);
    }

    public function shouldShowSkeleton(): bool
    {
        return $this->isLoadingBulkDelete || 
               $this->isLoadingDelete || 
               $this->isLoadingEdit || 
               $this->isLoadingView ||
               $this->isLoadingReactivate ||
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
                <a href="{{ route('employee.index') }}" class="border rounded-full py-2 px-2 md:px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.index') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}" wire:navigate>
                    <span class="hidden md:flex">
                        {{ __('Employee List') }}
                    </span>
                    <span class="flex: md:hidden">
                        <flux:icon name="numbered-list" variant="solid" class="w-5 h-5" />
                    </span>
                </a>

                {{-- add employee link--}}
                @if($editing)
                <a href="{{ route('employee.show') }}" class="border rounded-full py-2 px-2 md:px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.show') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}" wire:navigate>
                    <span class="hidden md:flex">
                        {{ __('Add Employee') }}
                    </span>
                    <span class="flex: md:hidden">
                        <flux:icon name="users" variant="solid" class="w-5 h-5" />
                    </span>
                </a>
                @endif

                {{-- edit employee link --}}
                <a href="{{ $editing && $employee ? route('employee.edit', $employee->id) : route('employee.show') }}" class="border rounded-full py-2 px-2 md:px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.show') || request()->routeIs('employee.edit') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}" wire:navigate>
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
                           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.allowances') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}" wire:navigate>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            {{ __('Allowances') }}
                        </a>
                        
                        <a href="{{ route('employee.payroll.deductions', $employee->id) }}" 
                           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.deductions') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}" wire:navigate>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            {{ __('Deductions') }}
                        </a>
                        
                        <a href="{{ route('employee.payroll.payslips', $employee->id) }}" 
                           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.payslips') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}" wire:navigate>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{ __('Payslips') }}
                        </a>
                        
                        <a href="{{ route('employee.payroll.history', $employee->id) }}" 
                           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.history', $employee->id) ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}" wire:navigate>
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
        <div class="flex justify-between mb-8 items-center">
            <div class="flex items-center">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
                <h2 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                    {{ __('Allowances for') }} {{ $employee->user->first_name }}
                    <span class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                </h2>
            </div>

            <div class="flex items-center gap-3">
                @can('export_allowance')
                    <button type="button" wire:click="exportAll"
                        class="flex items-center gap-2 px-2 lg:px-4 py-2 rounded-full border border-purple-200 dark:border-purple-700 text-purple-600 dark:text-purple-400 bg-purple-50/80 dark:bg-purple-900/20 hover:bg-purple-100/80 dark:hover:bg-purple-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-purple-400 transition"
                        @if ($isLoadingExport) disabled @endif>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="hidden lg:inline">
                            {{ __('Export All') }}
                        </span>
                    </button>
                @endcan
                @can('create_allowance')
                    <a href="{{ route('employee.payroll.allowances.create', $employee->id) }}" class="flex items-center gap-2 px-2 h-fit py-2 rounded-full border border-blue-200 dark:border-blue-700 text-blue-600 dark:text-blue-400 bg-blue-50/80 dark:bg-blue-900/20 hover:bg-blue-100/80 dark:hover:bg-blue-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-blue-400 transition" wire:navigate>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span class="hidden lg:inline">
                            {{ __('Add Allowance') }}
                        </span>
                    </a>
                @endcan
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="flex flex-wrap gap-6 items-center mb-6">
            <div class="relative w-80">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-5 h-5 text-blue-200 dark:text-indigo-400 z-[1]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" fill="none"></circle>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"></path>
                    </svg>
                </span>
                <input type="text" wire:model.live.debounce.300ms="search"
                    class="w-full pl-10 pr-4 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white transition shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md"
                    placeholder="{{ __('Search allowances...') }}">
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
            <select wire:model.live="filterType"
                class="px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                <option value="">{{ __('All Types') }}</option>
                @foreach($this->allowanceTypes as $key => $value)
                <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterStatus"
                class="px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                <option value="">{{ __('All Status') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
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
                        {{ count($selected) }} {{ __('allowance(s) selected') }}
                    </span>
                    @if(count($selected) < ($this->allowances ? $this->allowances->total() : 0))
                        <button wire:click="selectAllData" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline">
                            {{ __('Select all') }} {{ $this->allowances ? $this->allowances->total() : 0 }} {{ __('allowances') }}
                        </button>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    @can('export_allowance')
                        <button wire:click="exportSelected"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl border border-purple-200 dark:border-purple-700 text-purple-600 dark:text-purple-400 bg-purple-50/80 dark:bg-purple-900/20 hover:bg-purple-100/80 dark:hover:bg-purple-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-purple-400 transition"
                            @if ($isLoadingExport) disabled @endif>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{ $isLoadingExport ? __('Exporting...') : __('Export Selected') }}
                        </button>
                    @endcan
                    @can('delete_allowance')
                        <button wire:click="bulkDeleteConfirm"
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
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[150px]" wire:click="sortBy('allowance_type')">
                            {{ __('Type') }}
                            @if($this->sortField === 'allowance_type')
                                <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    @endif
                                </svg>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider min-w-[200px]">{{ __('Description') }}</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[120px]" wire:click="sortBy('amount')">
                            {{ __('Amount') }}
                            @if($this->sortField === 'amount')
                                <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    @endif
                                </svg>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[140px]" wire:click="sortBy('effective_date')">
                            {{ __('Effective Date') }}
                            @if($this->sortField === 'effective_date')
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
                        @for($i = 0; $i < $this->perPage; $i++)
                        <tr class="animate-pulse">
                            <td class="px-4 py-4">
                                <div class="w-4 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="w-20 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="w-32 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="w-16 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="w-20 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="w-12 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex gap-2">
                                    <div class="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                    <div class="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                    <div class="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                </div>
                            </td>
                        </tr>
                        @endfor
                    @else
                        @forelse($this->allowances as $allowance)
                    <tr class="hover:bg-gray-100 dark:hover:bg-white/20 transition group border-b border-gray-200 dark:border-gray-700">
                        <td class="px-3 py-4">
                            <input type="checkbox" wire:model.live="selected" value="{{ $allowance->id }}" class="accent-pink-500 rounded focus:ring-2 focus:ring-pink-400" />
                        </td>
                        <td class="px-4 py-4 text-gray-900 dark:text-white font-medium">
                            {{ $this->allowanceTypes[$allowance->allowance_type] ?? $allowance->allowance_type }}
                        </td>
                        <td class="px-4 py-4 text-gray-900 dark:text-white">
                            {{ $allowance->description }}
                        </td>
                        <td class="px-4 py-4 text-gray-900 dark:text-white font-semibold">
                            <span class="text-green-600 dark:text-green-400">
                                USD {{ number_format($allowance->amount, 2) }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-gray-900 dark:text-white">
                            {{ \Carbon\Carbon::parse($allowance->effective_date)->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $allowance->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                                {{ ucfirst($allowance->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-2 py-2">
                                <flux:button
                                    wire:click="confirmView({{ $allowance->id }})"
                                    variant="primary"
                                    color="green"
                                    size="sm"
                                    icon="eye"
                                    title="{{ __('View Allowance') }}"
                                />

                                @can('edit_allowance')
                                @if($allowance->status === 'active')
                                <flux:button
                                    wire:click="confirmEdit({{ $allowance->id }})"
                                    variant="primary"
                                    color="blue"
                                    size="sm"
                                    icon="pencil-square"
                                    title="{{ __('Edit') }}"
                                />
                                @else
                                <flux:button
                                    disabled
                                    variant="primary"
                                    color="gray"
                                    size="sm"
                                    icon="pencil-square"
                                    title="{{ __('Cannot edit inactive allowance') }}"
                                />
                                @endif
                                @endcan

                                @can('delete_allowance')
                                @if($allowance->status === 'active')
                                <flux:button
                                    wire:click="confirmDelete({{ $allowance->id }})"
                                    variant="danger"
                                    color="red"
                                    size="sm"
                                    icon="trash"
                                    title="{{ __('Deactivate Allowance') }}"
                                />
                                @else
                                <flux:button
                                    wire:click="confirmReactivate({{ $allowance->id }})"
                                    variant="primary"
                                    color="green"
                                    size="sm"
                                    icon="arrow-path"
                                    title="{{ __('Reactivate Allowance') }}"
                                />
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-8 h-8 text-gray-300 dark:text-zinc-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                                {{ __('No allowances found for this employee.') }}
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
            {{ $this->allowances->links() }}
        </div>
    </div>

    <!-- Create Allowance Modal -->
    @if($showCreateModal)
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="relative p-8 border shadow-2xl rounded-2xl bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl w-full max-w-md border-blue-100 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                    </svg>
                    {{ __('Add New Allowance') }}
                </h3>
                <flux:button icon:trailing="x-mark" variant="primary" type="button" wire:click="closeCreateModal" class="flex flex-row items-center gap-2 bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-200 px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                    {{ __('Close') }}
                </flux:button>
            </div>

            <p class="text-center text-gray-600 dark:text-gray-400 mb-6">
                {{ __('You will be redirected to the allowance creation page.') }}
            </p>

            <div class="flex items-end justify-end gap-3 md:col-span-2 lg:col-span-3">
                <flux:button icon:trailing="check" variant="primary" type="button" wire:click="openCreateModal" class="flex flex-row items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    {{ __('Create Allowance') }}
                </flux:button>
                <flux:button icon:trailing="x-mark" variant="primary" type="button" wire:click="closeCreateModal" class="flex flex-row items-center gap-2 bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-200 px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                    {{ __('Cancel') }}
                </flux:button>
            </div>
        </div>
    </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
        <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-md w-full border border-gray-100 dark:border-zinc-800">
            <h3 class="text-xl font-bold mb-4 text-red-600 dark:text-red-400 flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-1L4.308 4c-.897-.897-.39-2.197.732-2.197h15.856c1.54 0 2.502 1.667 1.732 1z"></path>
                </svg>
                {{ __('Confirm Deactivation') }}
            </h3>
            <p class="mb-6 text-zinc-700 dark:text-zinc-300">
                {{ __('Are you sure you want to deactivate this allowance? This action will make it inactive.') }}
            </p>
            <div class="flex justify-end gap-3">
                <button wire:click="deleteConfirmed"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-red-500 transition"
                    @if ($isLoadingDelete) disabled @endif>
                    {{ $isLoadingDelete ? __('Deactivating...') : __('Deactivate') }}
                </button>
                <button wire:click="$set('showDeleteModal', false)"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                    {{ __('Cancel') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- View Allowance Modal -->
    @if($showViewModal)
    <div class="fixed inset-0 z-50 flex items-start overflow-y-auto lg:py-8 justify-center bg-black/40 backdrop-blur-sm transition">
        <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-4xl w-full border border-gray-100 dark:border-zinc-800">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    {{ __('View Allowance') }}
                </h3>
                <button wire:click="$set('showViewModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            @if($pendingViewId)
            @php
                $viewAllowance = PayrollAllowance::with('employee.user')->find($pendingViewId);
            @endphp
            @if($viewAllowance)
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
                                {{ $viewAllowance->employee->user->first_name }} {{ $viewAllowance->employee->user->other_names }}
                            </p>
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                {{ __('Staff Number') }}: {{ $viewAllowance->employee->staff_number }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Allowance Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Allowance Type') }}</h4>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $this->allowanceTypes[$viewAllowance->allowance_type] ?? $viewAllowance->allowance_type }}
                        </p>
                    </div>

                    <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Amount') }}</h4>
                        <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                            USD {{ number_format($viewAllowance->amount, 2) }}
                        </p>
                    </div>

                    <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Effective Date') }}</h4>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ \Carbon\Carbon::parse($viewAllowance->effective_date)->format('M d, Y') }}
                        </p>
                    </div>

                    <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('End Date') }}</h4>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $viewAllowance->end_date ? \Carbon\Carbon::parse($viewAllowance->end_date)->format('M d, Y') : __('No end date') }}
                        </p>
                    </div>

                    <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Status') }}</h4>
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                            {{ $viewAllowance->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                            {{ ucfirst($viewAllowance->status) }}
                        </span>
                    </div>

                    <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Recurring') }}</h4>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $viewAllowance->is_recurring ? __('Yes') : __('No') }}
                        </p>
                    </div>
                </div>

                <!-- Description -->
                @if($viewAllowance->description)
                <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Description') }}</h4>
                    <p class="text-gray-900 dark:text-white">{{ $viewAllowance->description }}</p>
                </div>
                @endif

                <!-- Notes -->
                @if($viewAllowance->notes)
                <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Notes') }}</h4>
                    <p class="text-gray-900 dark:text-white">{{ $viewAllowance->notes }}</p>
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
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                {{ __('Confirm Edit') }}
            </h3>
            <p class="mb-6 text-zinc-700 dark:text-zinc-300">
                {{ __('Are you sure you want to edit this allowance?') }}
            </p>
            <div class="flex justify-end gap-3">
                <button wire:click="editConfirmed"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                    @if ($isLoadingEdit) disabled @endif>
                    {{ $isLoadingEdit ? __('Loading...') : __('Edit') }}
                </button>
                <button wire:click="$set('showEditModal', false)"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                    {{ __('Cancel') }}
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-1L4.308 4c-.897-.897-.39-2.197.732-2.197h15.856c1.54 0 2.502 1.667 1.732 1z"></path>
                    </svg>
                    {{ __('Confirm Bulk Deactivation') }}
                </h3>
                <p class="mb-6 text-zinc-700 dark:text-zinc-300 font-semibold">
                    {{ __('Are you sure you want to deactivate the selected allowances? This action will make them inactive.') }}
                </p>
                <div class="flex justify-end gap-3">
                    <button wire:click="bulkDelete"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-red-500 transition"
                        @if ($isLoadingBulkDelete) disabled @endif>
                        {{ $isLoadingBulkDelete ? __('Deactivating...') : __('Deactivate Selected') }}
                    </button>
                    <button wire:click="$set('showBulkDeleteModal', false)"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Reactivate Allowance Modal -->
    @if($showReactivateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
        <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-md w-full border border-gray-100 dark:border-zinc-800">
            <h3 class="text-xl font-bold mb-4 text-green-600 dark:text-green-400 flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                {{ __('Confirm Reactivation') }}
            </h3>
            <p class="mb-6 text-zinc-700 dark:text-zinc-300">
                {{ __('Are you sure you want to reactivate this allowance? This action will make it active again.') }}
            </p>
            <div class="flex justify-end gap-3">
                <button wire:click="reactivateConfirmed"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-green-500 transition"
                    @if ($isLoadingReactivate) disabled @endif>
                    {{ $isLoadingReactivate ? __('Reactivating...') : __('Reactivate') }}
                </button>
                <button wire:click="$set('showReactivateModal', false)"
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