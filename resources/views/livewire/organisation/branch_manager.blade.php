<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

use App\Models\Branch;
use App\Models\Location;
use App\Models\Audit;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public $showFilters = false;
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

    public function getBranchesProperty()
    {
        $query = Branch::query()->with('location');
        
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->filterLocation) {
            $query->where('location_id', $this->filterLocation);
        }
        
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        $field = in_array($this->sortField, ['name', 'code', 'created_at']) ? $this->sortField : 'created_at';
        $query->orderBy($field, $direction);
        
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
        $branches = $this->branches;
        if ($branches && $branches->count() > 0) {
            $currentPageIds = $branches->pluck('id')->toArray();
            $this->selectAll = count($currentPageIds) > 0 && count(array_intersect($this->selected, $currentPageIds)) === count($currentPageIds);
        } else {
            $this->selectAll = false;
        }
    }

    public function toggleSelectAll(): void
    {
        $branches = $this->branches;
        if ($branches && $branches->count() > 0) {
            $currentPageIds = $branches->pluck('id')->toArray();
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
        $query = Branch::query();
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
            });
        }
        if ($this->filterLocation) {
            $query->where('location_id', $this->filterLocation);
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
        $branches = Branch::whereIn('id', $this->selected)->get();
        Branch::whereIn('id', $this->selected)->delete();

        // Log the bulk delete action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'bulk_delete',
            'target_type' => Branch::class,
            'details' => json_encode(['branch_ids' => $this->selected]),
        ]);

        $this->showBulkDeleteModal = false;
        $this->isLoadingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;
        $this->updateSelectAllState();
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Selected branches deleted successfully.')]);
    }

    public function exportSelected(): void
    {
        $this->isLoadingExport = true;
        $branches = Branch::whereIn('id', $this->selected)->with('location')->get();

        // Log the export selected action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'export_selected',
            'target_type' => Branch::class,
            'details' => json_encode(['branch_ids' => $this->selected]),
        ]);

        $csvData = "ID,Name,Code,Location,Address,Created At\n";
        foreach ($branches as $branch) {
            $csvData .= '"' . $branch->id . '","' .
                str_replace('"', '""', $branch->name) . '","' .
                str_replace('"', '""', $branch->code) . '","' .
                str_replace('"', '""', $branch->location?->name) . '","' .
                $branch->created_at . '"\n';
        }
        $this->isLoadingExport = false;
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'branches_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Selected branches exported successfully.')]);
    }

    public function exportAll(): void
    {
        $this->isLoadingExport = true;
        $query = Branch::query()->with('location');
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
            });
        }
        if ($this->filterLocation) {
            $query->where('location_id', $this->filterLocation);
        }
        $branches = $query->orderByDesc('created_at')->get();

        // Log the export all action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'export_all',
            'target_type' => Branch::class,
            'details' => json_encode(['total_branches' => $branches->count()]),
        ]);

        $csvData = "ID,Name,Code,Location,Address,Created At\n";
        foreach ($branches as $branch) {
            $csvData .= '"' . $branch->id . '","' .
                str_replace('"', '""', $branch->name) . '","' .
                str_replace('"', '""', $branch->code) . '","' .
                str_replace('"', '""', $branch->location?->name) . '","' .
                $branch->created_at . '"\n';
        }
        $this->isLoadingExport = false;
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'all_branches_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Selected branches exported successfully.')]);
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
        $branch = Branch::findOrFail($this->pendingEditId);
        $this->showEditModal = false;
        $this->isLoadingEdit = false;
        $this->redirectRoute('branch.edit', ['id' => $branch->id]);
    }

    public function deleteConfirmed(): void
    {
        $this->isLoadingDelete = true;
        $branch = Branch::findOrFail($this->pendingDeleteId);
        $branch->delete();

        // Log the delete action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'delete',
            'target_type' => Branch::class,
            'target_id' => $branch->id,
            'details' => json_encode(['name' => $branch->name, 'code' => $branch->code]),
        ]);

        $this->selected = array_values(array_diff($this->selected, [$this->pendingDeleteId]));
        $this->showDeleteModal = false;
        $this->isLoadingDelete = false;
        
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Branch deleted successfully.')]);
    }

    public function resetForm(): void
    {
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('notify', ['type' => 'success', 'message' => __('Selected roles deleted successfully.')]);
    }

    public function createNewBranch(): void
    {
        $this->redirectRoute('branch.create');
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
                <a href="{{ route('branch.manage') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('branch.manage') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}" wire:navigate>
                    {{ __('Branch List') }}
                </a>
                <a href="{{ route('branch.create') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('branch.create') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}" wire:navigate>
                    {{ __('Create Branch') }}
                </a>
            </div>
        </nav>
    </div>

    @can('manage_branch')
        <!-- Card Container for Table -->
        <div class="relative bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
            <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
                <div class="flex items-center gap-3 mb-8">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                        {{ __('Branch List') }}
                        <span class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                    </h1>
                </div>
                <div class="flex items-center gap-3">
                    @can('export_branch')
                        <button type="button" wire:click="exportAll"
                            class="flex items-center gap-2 px-2 lg:px-4 py-2 rounded-full border border-purple-200 dark:border-purple-700 text-purple-600 dark:text-purple-400 bg-purple-50/80 dark:bg-purple-900/20 hover:bg-purple-100/80 dark:hover:bg-purple-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-purple-400 transition"
                            @if ($isLoadingExport) disabled @endif>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <span class="hidden lg:inline">
                                {{ $isLoadingExport ? __('Exporting...') : __('Export All') }}
                            </span>
                        </button>
                    @endcan
                    @can('create_branch')
                        <button type="button" wire:click="createNewBranch"
                            class="flex items-center gap-2 px-2 lg:px-4 py-2 rounded-full border border-blue-200 dark:border-blue-700 text-blue-600 dark:text-blue-400 bg-blue-50/80 dark:bg-blue-900/20 hover:bg-blue-100/80 dark:hover:bg-blue-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span class="hidden lg:inline">{{ __('Add Branch') }}</span>
                        </button>
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
                            placeholder="{{ __('Search branches...') }}">
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
                        <select wire:model.live="filterLocation"
                            class="px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                            <option value="">{{ __('All Locations') }}</option>
                            @foreach (App\Models\Location::all() as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="perPage"
                            class="px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                @endif
            </div>
            @if (count($selected) > 0)
                <div class="flex items-center justify-between flex-wrap mt-6 p-4 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 dark:from-zinc-800/50 dark:to-zinc-700/50 rounded-xl border border-blue-200 dark:border-zinc-700 backdrop-blur-sm">
                    <div class="flex items-center gap-2 py-2">
                        <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                            {{ count($selected) }} {{ __('item(s) selected') }}
                        </span>
                        @if(count($selected) < ($this->branches ? $this->branches->total() : 0))
                            <button type="button" wire:click="selectAllData"
                                class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                {{ __('Select all') }} {{ $this->branches ? $this->branches->total() : 0 }} {{ __('items') }}
                            </button>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        @can('export_branch')
                            <button type="button" wire:click="exportSelected"
                                class="flex items-center gap-2 px-4 py-2 rounded-xl border border-purple-200 dark:border-purple-700 text-purple-600 dark:text-purple-400 bg-purple-50/80 dark:bg-purple-900/20 hover:bg-purple-100/80 dark:hover:bg-purple-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-purple-400 transition"
                                @if ($isLoadingExport) disabled @endif>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                {{ $isLoadingExport ? __('Exporting...') : __('Export Selected') }}
                            </button>
                        @endcan
                        @can('delete_branch')
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
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('name')">
                                {{ __('Name') }}
                                @if($this->sortField === 'name')
                                    <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        @endif
                                    </svg>
                                @endif
                            </th>
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('code')">
                                {{ __('Code') }}
                                @if($this->sortField === 'code')
                                    <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        @endif
                                    </svg>
                                @endif
                            </th>
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">{{ __('Location') }}</th>
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('created_at')">
                                {{ __('Created At') }}
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
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($this->shouldShowSkeleton())
                            @for($i = 0; $i < $perPage; $i++)
                                <tr class="animate-pulse border-b border-gray-200 dark:border-gray-700">
                                    <td class="px-5 py-4"><div class="h-4 w-4 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                    <td class="px-5 py-4"><div class="h-4 w-32 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                    <td class="px-5 py-4"><div class="h-4 w-24 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                    <td class="px-5 py-4"><div class="h-4 w-24 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                    <td class="px-5 py-4"><div class="h-4 w-36 bg-blue-100 dark:bg-zinc-800 rounded"></div></td>
                                    <td class="px-5 py-4"><div class="h-4 w-24 bg-pink-100 dark:bg-zinc-800 rounded"></div></td>
                                    <td class="px-5 py-4"><div class="flex gap-2"><div class="h-8 w-8 bg-gray-100 dark:bg-zinc-800 rounded"></div><div class="h-8 w-8 bg-gray-100 dark:bg-zinc-800 rounded"></div></div></td>
                                </tr>
                            @endfor
                        @else
                            @forelse (($this->branches ?? []) as $branch)
                                <tr class="hover:bg-gray-100 dark:hover:bg-white/20 group border-b border-gray-200 dark:border-gray-700 transition-all duration-500 ease-in-out" wire:loading.class.delay="opacity-50 dark:opacity-40">
                                    <td class="px-5 py-4">
                                        <input type="checkbox" wire:model.live="selected" value="{{ $branch->id }}" class="accent-pink-500 rounded focus:ring-2 focus:ring-pink-400" />
                                    </td>
                                    <td class="px-5 py-4 text-gray-900 dark:text-white font-bold max-w-xs truncate">
                                        <span class="block font-semibold text-base">{{ $branch->name }}</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $branch->code }}</span>
                                    </td>
                                    <td class="px-5 py-4 font-semibold">{{ $branch->code }}</td>
                                    <td class="px-5 py-4">{{ $branch->location?->name }}</td>
                                    <td class="px-5 py-4 font-semibold">
                                        <span class="truncate text-blue-600 dark:text-blue-400">
                                            {{ $branch->created_at ? \Carbon\Carbon::parse($branch->created_at)->translatedFormat('j M Y') : '' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="flex gap-2">
                                            @can('edit_branch')
                                                <flux:button
                                                    wire:click="confirmEdit({{ $branch->id }})"
                                                    variant="primary"
                                                    color="blue"
                                                    size="sm"
                                                    icon="pencil-square"
                                                />
                                            @endcan
                                            @can('delete_branch')
                                                <flux:button
                                                    wire:click="confirmDelete({{ $branch->id }})"
                                                    variant="danger"
                                                    color="red"
                                                    size="sm"
                                                    icon="trash"
                                                />
                                            @endcan
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="w-8 h-8 text-gray-300 dark:text-zinc-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                            {{ __('No branches found.') }}
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        @endif
                    </tbody>
                </table>
                <div class="mt-6">
                    @if($this->branches && !$this->shouldShowSkeleton())
                        {{ $this->branches->links() }}
                    @endif
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        @if ($showDeleteModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
                <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-md w-full border border-gray-100 dark:border-zinc-800">
                    <h3 class="text-xl font-bold mb-4 text-red-600 dark:text-red-400 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        {{ __('Confirm Delete') }}
                    </h3>
                    <p class="mb-6 text-zinc-700 dark:text-zinc-300">
                        {{ __('Are you sure you want to delete this branch? This action cannot be undone.') }}
                    </p>
                    <div class="flex justify-end gap-3">
                        <button wire:click="deleteConfirmed"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-red-500 transition"
                            @if ($isLoadingDelete) disabled @endif>
                            {{ $isLoadingDelete ? __('Deleting...') : __('Delete') }}
                        </button>
                        <button wire:click="$set('showDeleteModal', false)"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Edit Modal -->
        @if ($showEditModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
                <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-md w-full border border-gray-100 dark:border-zinc-800">
                    <h3 class="text-xl font-bold mb-4 text-blue-600 dark:text-blue-400 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.293-6.293a1 1 0 011.414 0l1.586 1.586a1 1 0 010 1.414L11 15H9v-2z">
                            </path>
                        </svg>
                        {{ __('Confirm Edit') }}
                    </h3>
                    <p class="mb-6 text-zinc-700 dark:text-zinc-300">
                        {{ __('Are you sure you want to edit this branch?') }}</p>
                    <div class="flex justify-end gap-3">
                        <button wire:click="editConfirmed"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                            @if ($isLoadingEdit) disabled @endif>
                            {{ $isLoadingEdit ? __('Editing...') : __('Edit') }}
                        </button>
                        <button wire:click="$set('showEditModal', false)"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Bulk Delete Modal -->
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
                        {{ __('Are you sure you want to delete the selected branches? This action cannot be undone.') }}
                    </p>
                    <div class="flex justify-end gap-3">
                        <button wire:click="bulkDelete"
                            class="bg-gradient-to-r from-pink-500 to-red-500 hover:from-pink-600 hover:to-red-600 text-white px-5 py-3 rounded-xl font-semibold shadow-lg focus:outline-none focus:ring-2 focus:ring-red-400 transition backdrop-blur-sm"
                            @if ($isLoadingBulkDelete) disabled @endif>
                            {{ $isLoadingBulkDelete ? __('Deleting...') : __('Delete Selected') }}
                        </button>
                        <button wire:click="$set('showBulkDeleteModal', false)"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-xl font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif

    @else
        <!-- Access Denied Section -->
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