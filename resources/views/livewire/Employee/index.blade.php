<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Str;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\ContractType;
use Livewire\WithPagination;
use App\Models\Audit;
use Illuminate\Support\Facades\Auth;

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
    public $filterLocation = '';
    public $filterBranch = '';
    public $filterDepartment = '';
    public $filterDesignation = '';
    public $filterContractType = '';
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

    public function getEmployeesProperty()
    {
        $query = Employee::query()->with(['user', 'location', 'branch', 'department', 'designation', 'contractType']);

        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('other_names', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->orWhere('staff_number', 'like', '%' . $this->search . '%')
            ->orWhere('mobile_number', 'like', '%' . $this->search . '%');
        }
        if ($this->filterLocation) {
            $query->where('location_id', $this->filterLocation);
        }
        if ($this->filterBranch) {
            $query->where('branch_id', $this->filterBranch);
        }
        if ($this->filterDepartment) {
            $query->where('department_id', $this->filterDepartment);
        }
        if ($this->filterDesignation) {
            $query->where('designation_id', $this->filterDesignation);
        }
        if ($this->filterContractType) {
            $query->where('contract_type_id', $this->filterContractType);
        }
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        if ($this->sortField === 'name') {
            $query->join('users', 'employees.user_id', '=', 'users.id')
                ->orderByRaw("CONCAT(users.first_name, ' ', users.other_names) $direction")
                ->select('employees.*');
        } elseif ($this->sortField === 'staff_number') {
            $query->orderBy('staff_number', $direction);
        } elseif ($this->sortField === 'gender') {
            $query->orderBy('gender', $direction);
        } elseif ($this->sortField === 'mobile_number') {
            $query->orderBy('mobile_number', $direction);
        } elseif ($this->sortField === 'created_at') {
            $query->orderBy('created_at', $direction);
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

    public function updatedFilterLocation(): void
    {
        $this->isFiltering = true;
        $this->resetPage();
        $this->updateSelectAllState();
        $this->isFiltering = false;
    }

    public function updatedFilterBranch(): void
    {
        $this->isFiltering = true;
        $this->resetPage();
        $this->updateSelectAllState();
        $this->isFiltering = false;
    }

    public function updatedFilterDepartment(): void
    {
        $this->isFiltering = true;
        $this->resetPage();
        $this->updateSelectAllState();
        $this->isFiltering = false;
    }

    public function updatedFilterDesignation(): void
    {
        $this->isFiltering = true;
        $this->resetPage();
        $this->updateSelectAllState();
        $this->isFiltering = false;
    }

    public function updatedFilterContractType(): void
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
        $employees = $this->employees;
        if ($employees && $employees->count() > 0) {
            $currentPageIds = $employees->pluck('id')->toArray();
            $this->selectAll = count($currentPageIds) > 0 && 
                              count(array_intersect($this->selected, $currentPageIds)) === count($currentPageIds);
        } else {
            $this->selectAll = false;
        }
    }

    public function toggleSelectAll(): void
    {
        $employees = $this->employees;
        if ($employees && $employees->count() > 0) {
            $currentPageIds = $employees->pluck('id')->toArray();
            if ($this->selectAll) {
                $this->selected = array_values(array_diff($this->selected, $currentPageIds));
                $this->selectAll = false;
            } else {
                $this->selected = array_values(array_unique(array_merge($this->selected, $currentPageIds)));
                $this->selectAll = true;
            }
        }
    }

    public function toggleSelection($id): void
    {
        if (in_array($id, $this->selected)) {
            // Remove the ID from selected array
            $this->selected = array_values(array_diff($this->selected, [$id]));
        } else {
            // Add the ID to selected array
            $this->selected[] = $id;
            $this->selected = array_values(array_unique($this->selected));
        }
        
        $this->updateSelectAllState();
    }

    public function updatedSelected(): void
    {
        $this->updateSelectAllState();
    }

    public function selectAllData(): void
    {
        $query = Employee::query();
        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('other_names', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->orWhere('staff_number', 'like', '%' . $this->search . '%')
            ->orWhere('mobile_number', 'like', '%' . $this->search . '%');
        }
        if ($this->filterLocation) {
            $query->where('location_id', $this->filterLocation);
        }
        if ($this->filterBranch) {
            $query->where('branch_id', $this->filterBranch);
        }
        if ($this->filterDepartment) {
            $query->where('department_id', $this->filterDepartment);
        }
        if ($this->filterDesignation) {
            $query->where('designation_id', $this->filterDesignation);
        }
        if ($this->filterContractType) {
            $query->where('contract_type_id', $this->filterContractType);
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
        $employees = Employee::whereIn('id', $this->selected)->get();
        Employee::whereIn('id', $this->selected)->delete();

        // Log the bulk delete action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'bulk_delete',
            'target_type' => Employee::class,
            'details' => json_encode(['employee_ids' => $this->selected]),
        ]);

        $this->showBulkDeleteModal = false;
        $this->isLoadingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;
        $this->updateSelectAllState();
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Selected employees deleted successfully.')]);
    }

    public function exportSelected(): void
    {
        $this->isLoadingExport = true;
        $employees = Employee::whereIn('id', $this->selected)->with(['user', 'location', 'branch', 'department', 'designation', 'contractType'])->get();
        $csvData = "ID,Name,Staff Number,Email,Mobile,Location,Branch,Department,Designation,Contract Type,Date of Join\n";
        foreach ($employees as $employee) {
            $csvData .= '"' . $employee->id . '","' .
                str_replace('"', '""', $employee->user->first_name . ' ' . $employee->user->other_names) . '","' .
                str_replace('"', '""', $employee->staff_number) . '","' .
                str_replace('"', '""', $employee->user->gender) . '","' .
                str_replace('"', '""', $employee->mobile_number) . '","' .
                str_replace('"', '""', $employee->location?->name) . '","' .
                str_replace('"', '""', $employee->branch?->name) . '","' .
                str_replace('"', '""', $employee->department?->name) . '","' .
                str_replace('"', '""', $employee->designation?->name) . '","' .
                str_replace('"', '""', $employee->contractType?->name) . '","' .
                $employee->date_of_join . '"\n';
        }        
        
        // Log the export selected action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'export_selected',
            'target_type' => Employee::class,
            'details' => json_encode(['employee_ids' => $this->selected]),
        ]);

        $this->isLoadingExport = false;
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'employees_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Selected employees exported successfully.')]);
    }

    public function exportAll(): void
    {
        $this->isLoadingExport = true;
        $query = Employee::query()->with(['user', 'location', 'branch', 'department', 'designation', 'contractType']);
        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('other_names', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->orWhere('staff_number', 'like', '%' . $this->search . '%')
            ->orWhere('mobile_number', 'like', '%' . $this->search . '%');
        }
        if ($this->filterLocation) {
            $query->where('location_id', $this->filterLocation);
        }
        if ($this->filterBranch) {
            $query->where('branch_id', $this->filterBranch);
        }
        if ($this->filterDepartment) {
            $query->where('department_id', $this->filterDepartment);
        }
        if ($this->filterDesignation) {
            $query->where('designation_id', $this->filterDesignation);
        }
        if ($this->filterContractType) {
            $query->where('contract_type_id', $this->filterContractType);
        }
        $employees = $query->orderByDesc('created_at')->get();
        $csvData = "ID,Name,Staff Number,Email,Mobile,Location,Branch,Department,Designation,Contract Type,Date of Join\n";
        foreach ($employees as $employee) {
            $csvData .= '"' . $employee->id . '","' .
                str_replace('"', '""', $employee->user->first_name . ' ' . $employee->user->other_names) . '","' .
                str_replace('"', '""', $employee->staff_number) . '","' .
                str_replace('"', '""', $employee->user->gender) . '","' .
                str_replace('"', '""', $employee->mobile_number) . '","' .
                str_replace('"', '""', $employee->location?->name) . '","' .
                str_replace('"', '""', $employee->branch?->name) . '","' .
                str_replace('"', '""', $employee->department?->name) . '","' .
                str_replace('"', '""', $employee->designation?->name) . '","' .
                str_replace('"', '""', $employee->contractType?->name) . '","' .
                $employee->date_of_join . '"\n';
        }        
        
        // Log the export all action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'export_all',
            'target_type' => Employee::class,
            'details' => json_encode(['total_employees' => $employees->count()]),
        ]);

        $this->isLoadingExport = false;
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'all_employees_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Selected employees exported successfully.')]);
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
        $employee = Employee::findOrFail($this->pendingEditId);
        $this->showEditModal = false;
        $this->isLoadingEdit = false;
        $this->redirectRoute('employee.edit', ['id' => $employee->id], navigate: true);
    }

    public function deleteConfirmed(): void
    {
        $this->isLoadingDelete = true;
        $employee = Employee::findOrFail($this->pendingDeleteId);
        $employee->delete();

        // Log the delete action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'delete',
            'target_type' => Employee::class,
            'target_id' => $employee->id,
            'details' => json_encode(['name' => $employee->user->first_name, 'email' => $employee->user->email]),
        ]);

        $this->resetForm();
        $this->showDeleteModal = false;
        $this->isLoadingDelete = false;
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Employee deleted successfully.')]);
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->selected = [];
        $this->selectAll = false;
    }

    public function createNewEmployee(): void
    {
        $this->redirectRoute('employee.show', navigate: true);
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
                <a href="{{ route('employee.index') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.index') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none' : '' }}" wire:navigate>
                    {{ __('Employee List') }}
                </a>
                <a href="{{ route('employee.show') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.show') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none' : '' }}" wire:navigate>
                    {{ __('Add Employee') }}
                </a>
            </div>
        </nav>
    </div>
    
    <!-- Card Container for Table -->
    <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
            <div class="flex items-center gap-3 mb-8">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"></path>
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                </svg>
                <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                    {{ __('Employee List') }}
                    <span class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                </h1>
            </div>
            <div class="flex items-center gap-3">
                @can('export_employee')
                    <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" wire:click="exportAll" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        {{ __('Export All') }}
                    </flux:button>
                @else
                    <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" :disabled="true" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        {{ __('Exporting Denied') }}
                    </flux:button>
                @endcan
                @can('create_employee')
                    <flux:button icon:trailing="plus" variant="primary" type="button" wire:click="createNewEmployee" class="flex flex-row items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        {{ __('Add Employee') }}
                    </flux:button>
                @else
                    <flux:button icon:trailing="plus" variant="primary" type="button" :disabled="true" class="flex flex-row items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        {{ __('Adding Denied') }}
                    </flux:button>
                @endcan
            </div>
        </div>
        <div>
            <div class="flex flex-wrap gap-8 items-center">
                <div class="relative w-80">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-5 h-5 text-blue-200 dark:text-indigo-400 z-[1]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" fill="none"></circle>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"></path>
                        </svg>
                    </span>
                    <input type="text" wire:model.live.debounce.500ms="search"
                        class="w-full pl-10 pr-4 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white transition shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md"
                        placeholder="{{ __('Search employee...') }}">
                </div>
                <button type="button" wire:click="toggleFilters"
                    class="flex items-center gap-1 px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 bg-white/80 dark:bg-zinc-900/80 text-blue-600 dark:text-indigo-300 hover:bg-blue-50/80 dark:hover:bg-zinc-800/80 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h8m-8 6h16"></path>
                    </svg>
                    <span class="hidden lg:inline">{{ __('Filters') }}</span>
                </button>
            </div>
        </div>
        <div>
            @if ($showFilters ?? false)
                <div class="flex flex-wrap gap-6 mt-6 items-center animate-fade-in">
                    <flux:select wire:model.live="filterLocation" placeholder="{{ __('All Locations') }}" class="!ps-3 pe-4 !py-2 !rounded-full border !border-blue-200 dark:!border-indigo-700 !focus:ring-2 !focus:ring-blue-400 dark:!bg-zinc-800/80 dark:!text-white !shadow-sm !bg-white/80 dark:!bg-zinc-900/80 !backdrop-blur-md !w-fit !outline-none">
                        <flux:select.option value="">{{ __('All Locations') }}</flux:select.option>
                        @foreach (App\Models\Location::all() as $location)
                            <flux:select.option value="{{ $location->id }}">{{ $location->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="filterBranch" placeholder="{{ __('All Branches') }}" :disabled="!$filterLocation" class="!ps-3 pe-4 !py-2 !rounded-full border !border-blue-200 dark:!border-indigo-700 !focus:ring-2 !focus:ring-blue-400 dark:!bg-zinc-800/80 dark:!text-white !shadow-sm !bg-white/80 dark:!bg-zinc-900/80 !backdrop-blur-md !w-fit !outline-none">
                        <flux:select.option value="">{{ __('All Branches') }}</flux:select.option>
                        @foreach ($filterLocation ? App\Models\Branch::where('location_id', $filterLocation)->get() : [] as $branch)
                            <flux:select.option value="{{ $branch->id }}">{{ $branch->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="filterDepartment" placeholder="{{ __('All Departments') }}" :disabled="!$filterBranch" class="!ps-3 pe-4 !py-2 !rounded-full border !border-blue-200 dark:!border-indigo-700 !focus:ring-2 !focus:ring-blue-400 dark:!bg-zinc-800/80 dark:!text-white !shadow-sm !bg-white/80 dark:!bg-zinc-900/80 !backdrop-blur-md !w-fit !outline-none">
                        <flux:select.option value="">{{ __('All Departments') }}</flux:select.option>
                        @foreach ($filterBranch ? App\Models\Department::where('branch_id', $filterBranch)->get() : [] as $department)
                            <flux:select.option value="{{ $department->id }}">{{ $department->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="filterDesignation" placeholder="{{ __('All Designations') }}" class="!ps-3 pe-4 !py-2 !rounded-full border !border-blue-200 dark:!border-indigo-700 !focus:ring-2 !focus:ring-blue-400 dark:!bg-zinc-800/80 dark:!text-white !shadow-sm !bg-white/80 dark:!bg-zinc-900/80 !backdrop-blur-md !w-fit !outline-none">
                        <flux:select.option value="">{{ __('All Designations') }}</flux:select.option>
                        @foreach (App\Models\Designation::all() as $designation)
                            <flux:select.option value="{{ $designation->id }}">{{ $designation->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="filterContractType" placeholder="{{ __('All Contract Types') }}" class="!ps-3 pe-4 !py-2 !rounded-full border !border-blue-200 dark:!border-indigo-700 !focus:ring-2 !focus:ring-blue-400 dark:!bg-zinc-800/80 dark:!text-white !shadow-sm !bg-white/80 dark:!bg-zinc-900/80 !backdrop-blur-md !w-fit !outline-none">
                        <flux:select.option value="">{{ __('All Contract Types') }}</flux:select.option>
                        @foreach (App\Models\ContractType::all() as $type)
                            <flux:select.option value="{{ $type->id }}">{{ $type->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="perPage" placeholder="10" class="!ps-3 pe-4 !py-2 !rounded-full border !border-blue-200 dark:!border-indigo-700 !focus:ring-2 !focus:ring-blue-400 dark:!bg-zinc-800/80 dark:!text-white !shadow-sm !bg-white/80 dark:!bg-zinc-900/80 !backdrop-blur-md !w-fit !outline-none">
                        <flux:select.option value="10">10</flux:select.option>
                        <flux:select.option value="25">25</flux:select.option>
                        <flux:select.option value="50">50</flux:select.option>
                    </flux:select>
                </div>
            @endif
        </div>
        @if (count($selected) > 0)
            <div class="flex items-center justify-between flex-wrap mt-6 p-4 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 dark:from-zinc-800/50 dark:to-zinc-700/50 rounded-xl border border-blue-200 dark:border-zinc-700 backdrop-blur-sm">
                <div class="flex items-center gap-2 py-2">
                    <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                        {{ count($selected) }} {{ __('item(s) selected') }}
                    </span>
                    @if(count($selected) < ($this->employees ? $this->employees->total() : 0))
                        <button type="button" wire:click="selectAllData"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            {{ __('Select all') }} {{ $this->employees ? $this->employees->total() : 0 }} {{ __('items') }}
                        </button>
                    @endif
                </div>
                <div class="flex items-end justify-end gap-3 md:col-span-2 lg:col-span-3">
                    @can('export_employee')
                        <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" wire:click="exportSelected" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            {{ __('Export Selected') }}
                        </flux:button>
                    @else
                        <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" wire:click="exportSelected" :disabled="true" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            {{ __('Exporting Denied') }}
                        </flux:button>
                    @endcan
                    @can('delete_employee')
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
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[200px]" wire:click="sortBy('name')">
                            {{ __('Name') }}
                            @if($this->sortField === 'name')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @else
                                <flux:icon name="arrows-up-down" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @endif
                        </th>
                        <th class="px-3 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[120px] gap-1 whitespace-nowrap" wire:click="sortBy('staff_number')">
                            {{ __('Staff') }}&nbsp;{{ __('NO.') }}
                            @if($this->sortField === 'staff_number')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @else
                                <flux:icon name="arrows-up-down" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @endif
                        </th>
                        <th class="px-3 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[120px] gap-1 whitespace-nowrap" wire:click="sortBy('gender')">
                            {{ __('Gender') }}
                            @if($this->sortField === 'gender')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @else
                                <flux:icon name="arrows-up-down" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @endif
                        </th>
                        <th class="px-3 py-3 text-left font-semibold uppercase tracking-wider min-w-[120px]" wire:click="sortBy('mobile_number')">
                            {{ __('Mobile') }}
                            @if($this->sortField === 'mobile_number')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @else
                                <flux:icon name="arrows-up-down" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @endif
                        </th>
                        <th class="px-3 py-3 text-left font-semibold uppercase tracking-wider min-w-[100px]">{{ __('Location') }}</th>
                        <th class="px-3 py-3 text-left font-semibold uppercase tracking-wider min-w-[100px]">{{ __('Branch') }}</th>
                        <th class="px-3 py-3 text-left font-semibold uppercase tracking-wider min-w-[120px]">{{ __('Department') }}</th>
                        <th class="px-3 py-3 text-left font-semibold uppercase tracking-wider min-w-[120px]">{{ __('Designation') }}</th>
                        <th class="px-3 py-3 text-left font-semibold uppercase tracking-wider min-w-[130px]">{{ __('Contract') }}&nbsp;{{ __('Type') }}</th>
                        <th class="px-3 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[120px]" wire:click="sortBy('created_at')">
                            {{ __('D.O.J') }}
                            @if($this->sortField === 'created_at')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @else
                                <flux:icon name="arrows-up-down" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @endif
                        </th>
                        <th class="px-3 py-3 text-left font-semibold uppercase tracking-wider min-w-[100px]">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if($this->shouldShowSkeleton())
                        @for($i = 0; $i < $perPage; $i++)
                            <tr class="animate-pulse border-b border-gray-200 dark:border-gray-700">
                                <td class="px-5 py-4"><div class="h-4 w-4 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-5 py-4"><div class="h-4 w-32 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-5 py-4"><div class="h-4 w-24 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-5 py-4"><div class="h-4 w-36 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-5 py-4"><div class="h-4 w-24 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-5 py-4"><div class="h-4 w-24 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-5 py-4"><div class="h-4 w-24 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-5 py-4"><div class="h-4 w-24 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-5 py-4"><div class="h-4 w-24 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-5 py-4"><div class="h-4 w-24 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-5 py-4"><div class="h-4 w-24 bg-pink-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-5 py-4"><div class="flex gap-2"><div class="h-8 w-8 bg-gray-100 dark:bg-zinc-800 rounded"></div><div class="h-8 w-8 bg-gray-100 dark:bg-zinc-800 rounded"></div></div></td>
                            </tr>
                        @endfor
                    @else
                        @forelse (($this->employees ?? []) as $employee)
                            <tr class="hover:bg-gray-100 dark:hover:bg-white/20 group border-b border-gray-200 dark:border-gray-700 transition-all duration-500 ease-in-out" wire:loading.class.delay="opacity-50 dark:opacity-40">
                                <td class="px-3 py-4">
                                    <button type="button"
                                        wire:click="toggleSelection({{ $employee->id }})"
                                        class="rounded focus:ring-2 focus:ring-pink-400 transition-colors duration-200
                                            @if(in_array($employee->id, $selected))
                                                bg-pink-500 text-white p-[2px]
                                            @else
                                                bg-transparent text-pink-500 border border-gray-500 p-[6px]
                                            @endif
                                            flex items-center gap-2"
                                    >
                                        @if(in_array($employee->id, $selected))
                                            <svg class="w-3 h-3 text-gray-800 font-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        @endif
                                    </button>
                                </td>
                                <td class="px-4 py-4 text-gray-900 dark:text-white font-bold max-w-xs truncate flex items-center gap-3">
                                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 dark:bg-zinc-800 text-blue-600 dark:text-blue-300 font-bold text-lg">
                                        {{ strtoupper(Str::substr($employee->user->first_name, 0, 1) . Str::substr($employee->user->other_names, 0, 1)) }}
                                    </span>
                                    <span>
                                        <span class="block font-semibold text-base">{{ $employee->user->first_name }}{{ $employee->user->other_names ? ' ' . $employee->user->other_names : '' }}</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $employee->user->email }}</span>
                                    </span>
                                </td>
                                <td class="px-3 py-4 font-semibold">{{ $employee->staff_number }}</td>
                                <td class="px-3 py-4">{{ $employee->gender }}</td>
                                <td class="px-3 py-4">{{ $employee->mobile_number }}</td>
                                <td class="px-3 py-4">{{ $employee->location?->name }}</td>
                                <td class="px-3 py-4">{{ $employee->branch?->name }}</td>
                                <td class="px-3 py-4">{{ $employee->department?->name }}</td>
                                <td class="px-3 py-4 font-medium text-green-600 dark:text-green-400">{{ $employee->designation?->name }}</td>
                                <td class="px-3 py-4">{{ $employee->contractType?->name }}</td>
                                <td class="px-3 py-4 font-semibold">
                                    <span class="truncate text-blue-600 dark:text-blue-400">
                                        {{ $employee->date_of_join ? \Carbon\Carbon::parse($employee->date_of_join)->translatedFormat('j M Y') : '' }}
                                    </span>
                                </td>
                                <td class="px-3 py-4">
                                    <span class="flex gap-2">
                                        @can('edit_employee')
                                            <flux:button
                                                wire:click="confirmEdit({{ $employee->id }})"
                                                variant="primary"
                                                color="blue"
                                                size="sm"
                                                icon="pencil-square"
                                            />
                                        @else
                                            <flux:button
                                                :disabled="true"
                                                variant="primary"
                                                color="gray"
                                                size="sm"
                                                icon="pencil-square"
                                            />
                                        @endcan
                                        @can('delete_employee')
                                            <flux:button
                                                wire:click="confirmDelete({{ $employee->id }})"
                                                variant="danger"
                                                color="red"
                                                size="sm"
                                                icon="trash"
                                            />
                                        @else
                                            <flux:button
                                                :disabled="true"
                                                variant="danger"
                                                color="gray"
                                                size="sm"
                                                icon="trash"
                                            />
                                        @endcan
                                        @can('restore_employee')
                                            <flux:button
                                                wire:click="confirmRestore({{ $employee->id }})"
                                                variant="primary"
                                                color="green"
                                                size="sm"
                                                icon="arrow-uturn-left"
                                            />
                                        @else
                                            <flux:button
                                                :disabled="true"
                                                variant="primary"
                                                color="gray"
                                                size="sm"
                                                icon="arrow-uturn-left"
                                            />
                                        @endcan
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-8 h-8 text-gray-300 dark:text-zinc-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 10h6v4H9z"></path>
                                        </svg>
                                        {{ __('No employees found.') }}
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
            <div class="mt-6">
                @if($this->employees && !$this->shouldShowSkeleton())
                    {{ $this->employees->links() }}
                @endif
            </div>
        </div>
    </div>
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
            <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-md w-full border border-gray-100 dark:border-zinc-800">
                <h3 class="text-xl font-bold mb-4 text-red-600 dark:text-red-400 flex items-center gap-2">
                    <flux:icon name="trash" class="w-6 h-6" />
                    {{ __('Confirm Delete') }}
                </h3>
                <p class="mb-6 text-zinc-700 dark:text-zinc-300">
                    {{ __('Are you sure you want to delete this employee? This action cannot be undone.') }}
                </p>
                <div class="flex justify-end gap-3">
                    <flux:button icon:trailing="trash" variant="primary" type="button" wire:click="deleteConfirmed" class="flex flex-row items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                        {{ __('Delete') }}
                    </flux:button>
                    <flux:button icon:trailing="x-mark" variant="primary" type="button" wire:click="$set('showDeleteModal', false)" class="flex flex-row items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-800 px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
            <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-md w-full border border-gray-100 dark:border-zinc-800">
                <h3 class="text-xl font-bold mb-4 text-blue-600 dark:text-blue-400 flex items-center gap-2">
                    <flux:icon name="pencil-square" class="w-6 h-6" />
                    {{ __('Confirm Edit') }}
                </h3>
                <p class="mb-6 text-zinc-700 dark:text-zinc-300">
                    {{ __('Are you sure you want to edit this employee?') }}
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
    @if ($showBulkDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
            <div class="bg-gradient-to-br from-pink-50/80 via-white/80 to-red-100/80 dark:from-zinc-900/80 dark:via-zinc-800/80 dark:to-zinc-900/80 backdrop-blur-xl rounded-xl shadow-2xl p-10 max-w-md w-full border border-pink-200 dark:border-zinc-800">
                <h3 class="text-xl font-bold mb-4 text-red-600 dark:text-red-400 flex items-center gap-2">
                    <flux:icon name="trash" class="w-6 h-6" />
                    {{ __('Confirm Bulk Delete') }}
                </h3>
                <p class="mb-6 text-zinc-700 dark:text-zinc-300 font-semibold">
                    {{ __('Are you sure you want to delete the selected employees? This action cannot be undone.') }}
                </p>
                <div class="flex justify-end gap-3">
                    <flux:button icon:trailing="trash" variant="primary" type="button" wire:click="bulkDelete" class="flex flex-row items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                        {{ __('Delete Selected') }}
                    </flux:button>
                    <flux:button icon:trailing="x-mark" variant="primary" type="button" wire:click="$set('showBulkDeleteModal', false)" class="flex flex-row items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-xl font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                        {{ __('Cancel') }}
                    </flux:button>
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
