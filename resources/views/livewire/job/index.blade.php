<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\JobAdvert;
use App\Models\Audit;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public $showFilters = false;
    public ?int $editingId = null;
    // Removed public $jobAdverts property
    public $showDeleteModal = false;
    public $showEditModal = false;
    public $showViewModal = false;
    public $showExportModal = false;
    public $pendingDeleteId = null;
    public $pendingEditId = null;
    public $pendingViewId = null;
    public $isLoadingDelete = false;
    public $isLoadingEdit = false;
    public $isLoadingBulkDelete = false;
    public $isLoadingExport = false;
    public $isLoadingImport = false;
    public $search = '';
    public $filterStatus = '';
    public $selected = [];
    public $selectAll = false;
    public $showBulkDeleteModal = false;
    public $perPage = 10;

    // Add loading states for various operations
    public $isSearching = false;
    public $isFiltering = false;
    public $isPaginating = false;
    public $isLoadingData = false; // Added new loading state

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

    // No need to load job adverts in mount

    public function getJobAdvertsProperty()
    {
        $query = JobAdvert::query()->withCount('applications');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhere('slug', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        $field = in_array($this->sortField, ['title', 'description', 'deadline', 'status', 'created_at']) ? $this->sortField : 'created_at';
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

    // Removed loadJobAdverts method; pagination is now handled by computed property

    public function updateSelectAllState(): void
    {
        $jobAdverts = $this->jobAdverts;
        if ($jobAdverts && $jobAdverts->count() > 0) {
            $currentPageIds = $jobAdverts->pluck('id')->toArray();
            $this->selectAll = count($currentPageIds) > 0 && 
                              count(array_intersect($this->selected, $currentPageIds)) === count($currentPageIds);
        } else {
            $this->selectAll = false;
        }
    }

    public function toggleSelectAll(): void
    {
        $jobAdverts = $this->jobAdverts;
        if ($jobAdverts && $jobAdverts->count() > 0) {
            $currentPageIds = $jobAdverts->pluck('id')->toArray();
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
        $query = JobAdvert::query();
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhere('slug', 'like', '%' . $this->search . '%');
            });
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
        JobAdvert::whereIn('id', $this->selected)->delete();

        // Log the bulk delete action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'bulk_delete',
            'target_type' => JobAdvert::class,
            'details' => json_encode(['job_advert_ids' => $this->selected]),
        ]);

        $this->showBulkDeleteModal = false;
        $this->isLoadingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;
        $this->updateSelectAllState();
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Selected job adverts deleted successfully.')]);
    }

    public function exportSelected(): void
    {
        $this->isLoadingExport = true;
        
        // Get selected job adverts
        $jobAdverts = JobAdvert::whereIn('id', $this->selected)->get();
        
        // Create CSV content
        $csvData = "ID,Title,Description,Deadline,Status,Created At\n";
        foreach ($jobAdverts as $advert) {
            $csvData .= '"' . $advert->id . '","' . 
                       str_replace('"', '""', $advert->title) . '","' . 
                       str_replace('"', '""', $advert->description) . '","' . 
                       $advert->deadline . '","' . 
                       $advert->status . '","' . 
                       $advert->created_at . '"' . "\n";
        } 
        
        // Log the export selected action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'export_selected',
            'target_type' => JobAdvert::class,
            'details' => json_encode(['job_advert_ids' => $this->selected]),
        ]);
        
        $this->isLoadingExport = false;
        
        // Trigger download
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'job_adverts_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Selected job adverts exported successfully.')]);
    }

    public function exportAll(): void
    {
        $this->isLoadingExport = true;
        
        // Get all job adverts based on current filters
        $query = JobAdvert::query();
        
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhere('slug', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }
        
        $jobAdverts = $query->orderByDesc('created_at')->get();
        
        // Create CSV content
        $csvData = "ID,Title,Description,Deadline,Status,Created At\n";
        foreach ($jobAdverts as $advert) {
            $csvData .= '"' . $advert->id . '","' . 
                       str_replace('"', '""', $advert->title) . '","' . 
                       str_replace('"', '""', $advert->description) . '","' . 
                       $advert->deadline . '","' . 
                       $advert->status . '","' . 
                       $advert->created_at . '"' . "\n";
        }        
        
        // Log the export all action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'export_all',
            'target_type' => JobAdvert::class,
            'details' => json_encode(['total_job_adverts' => $jobAdverts->count()]),
        ]);
        
        $this->isLoadingExport = false;
        
        // Trigger download
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'all_job_adverts_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Selected job adverts exported successfully.')]);
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

    public function manageApplications($id): void
    {
        $this->redirectRoute('job.applications', ['jobAdvertId' => $id], navigate: true);
    }

    public function confirmDelete($id): void
    {
        $this->pendingDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function editConfirmed(): void
    {
        $this->isLoadingEdit = true;
        $advert = JobAdvert::findOrFail($this->pendingEditId);
        $this->showEditModal = false;
        $this->isLoadingEdit = false;
        $this->redirectRoute('job.edit', ['slug' => $advert->slug]);
    }

    public function deleteConfirmed(): void
    {
        $this->isLoadingDelete = true;
        $jobAdvert = JobAdvert::findOrFail($this->pendingDeleteId);
        $jobAdvert->delete();

        // Log the delete action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'delete',
            'target_type' => JobAdvert::class,
            'target_id' => $jobAdvert->id,
            'details' => json_encode(['title' => $jobAdvert->title]),
        ]);

        $this->dispatch('notify', ['type' => 'success', 'message' => __('Job advert deleted successfully.')]);

        $this->resetForm();
        $this->showDeleteModal = false;
        $this->isLoadingDelete = false;
    }

    public function resetForm(): void
    {
        $this->form = [
            'title' => '',
            'description' => '',
            'deadline' => '',
            'status' => 'Draft',
        ];
        $this->editingId = null;
    }

    public function createNewAdvert(): void
    {
        $this->redirectRoute('job.show');
    }

    // Helper method to check if we should show skeleton
    public function shouldShowSkeleton(): bool
    {
        return $this->isLoadingBulkDelete || 
               $this->isLoadingDelete || 
               $this->isLoadingEdit || 
               $this->isSearching || 
               $this->isFiltering || 
               $this->isPaginating ||
               $this->isLoadingExport ||
               $this->isLoadingData; // Added the new loading state
    }

    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
        // Do not reload or reset job adverts here
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
                <a href="{{ route('job.show') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('job.index') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}" wire:navigate>
                    {{ __('Job Advert List') }}
                </a>
                <a href="{{ route('job.show') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('job.show') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}" wire:navigate>
                    {{ __('Create Advert') }}
                </a>
            </div>
        </nav>
    </div>

    <!-- Card Container for Table -->
    <div class="relative bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
            <div class="flex items-center gap-3 mb-8">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"></path>
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                </svg>
                <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                    {{ __('Job Advert List') }}
                    <span class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                </h1>
            </div>

            <div class="flex items-center gap-3">
                @can('export_job_advert')
                    <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" wire:click="exportAll"
                    class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    {{ $isLoadingExport ? __('Exporting...') : __('Export All') }}
                    </flux:button>
                @else
                    <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" :disabled="true"
                    class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    {{ __('Exporting Denied') }}
                    </flux:button>
                @endcan

                @can('create_job_advert')
                    <flux:button icon:trailing="plus" variant="primary" type="button" wire:click="createNewAdvert"
                    class="flex flex-row items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    {{ __('Add Advert') }}
                    </flux:button>
                @else
                    <flux:button icon:trailing="plus" variant="primary" type="button" :disabled="true"
                    class="flex flex-row items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    {{ __('Adding Denied') }}
                    </flux:button>
                @endcan
            </div>
        </div>
        
        <div>
            <div class="flex flex-wrap gap-8 items-center">
                <!-- Search Bar with Icon -->
                <div class="relative w-80">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-5 h-5 text-blue-200 dark:text-indigo-400 z-[1]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" fill="none"></circle>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"></path>
                        </svg>
                    </span>
                    <input type="text" wire:model.live.debounce.500ms="search"
                        class="w-full pl-10 pr-4 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white transition shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md"
                        placeholder="{{ __('Search adverts...') }}">
                </div>
                
                <!-- Filter Toggle Icon -->
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
            <!-- Filters (hidden by default, shown when toggled) -->
            @if ($showFilters ?? false)
                <div class="flex flex-wrap gap-6 mt-6 items-center animate-fade-in">
                    <flux:select wire:model.live="filterStatus" class="!ps-3 pe-4 !py-2 !rounded-full border !border-blue-200 dark:!border-indigo-700 !focus:ring-2 !focus:ring-blue-400 dark:!bg-zinc-800/80 dark:!text-white !shadow-sm !bg-white/80 dark:!bg-zinc-900/80 !backdrop-blur-md !w-fit !outline-none">
                        <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                        <flux:select.option value="Published">{{ __('Published') }}</flux:select.option>
                        <flux:select.option value="Draft">{{ __('Draft') }}</flux:select.option>
                        <flux:select.option value="Expired">{{ __('Expired') }}</flux:select.option>
                    </flux:select>
                    <flux:select wire:model.live="perPage" class="!ps-3 pe-4 !py-2 !rounded-full border !border-blue-200 dark:!border-indigo-700 !focus:ring-2 !focus:ring-blue-400 dark:!bg-zinc-800/80 dark:!text-white !shadow-sm !bg-white/80 dark:!bg-zinc-900/80 !backdrop-blur-md !w-fit !outline-none">
                        <flux:select.option value="10">10</flux:select.option>
                        <flux:select.option value="25">25</flux:select.option>
                        <flux:select.option value="50">50</flux:select.option>
                    </flux:select>
                </div>
            @endif
        </div>

        <!-- Action Buttons Row -->
        @if (count($selected) > 0)
            <div class="flex items-center justify-between flex-wrap mt-6 p-4 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 dark:from-zinc-800/50 dark:to-zinc-700/50 rounded-xl border border-blue-200 dark:border-zinc-700 backdrop-blur-sm">
                <div class="flex items-center gap-2 py-2">
                    <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                        {{ count($selected) }} {{ __('item(s) selected') }}
                    </span>
                    @if(count($selected) < ($this->jobAdverts ? $this->jobAdverts->total() : 0))
                        <button type="button" wire:click="selectAllData"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            {{ __('Select all') }} {{ $this->jobAdverts ? $this->jobAdverts->total() : 0 }} {{ __('items') }}
                        </button>
                    @endif
                </div>
                <div class="flex items-end justify-end gap-3 md:col-span-2 lg:col-span-3">
                    @can('export_job_advert')
                        <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" wire:click="exportSelected" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            {{ __('Export Selected') }}
                        </flux:button>
                    @else
                        <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" :disabled="true" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            {{ __('Exporting Denied') }}
                        </flux:button>
                    @endcan

                    @can('delete_job_advert')
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
                                    <!-- Checkmark Icon -->
                                    <svg class="w-3 h-3 text-gray-800 font-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('title')">
                            {{ __('Title') }}
                            @if($this->sortField === 'title')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @else
                                <flux:icon name="arrows-up-down" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @endif
                        </th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('description')">
                            {{ __('Description') }}
                            @if($this->sortField === 'description')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @else
                                <flux:icon name="arrows-up-down" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @endif
                        </th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('deadline')">
                            {{ __('Deadline') }}
                            @if($this->sortField === 'deadline')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @else
                                <flux:icon name="arrows-up-down" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @endif
                        </th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('status')">
                            {{ __('Status') }}
                            @if($this->sortField === 'status')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @else
                                <flux:icon name="arrows-up-down" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @endif
                        </th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">
                            {{ __('Applications') }}
                        </th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @if($this->shouldShowSkeleton())
                        @for($i = 0; $i < $perPage; $i++)
                            <tr class="animate-pulse border-b border-gray-200 dark:border-gray-700">
                                <td class="px-5 py-4">
                                    <div class="h-4 w-4 bg-blue-100 dark:bg-zinc-800 rounded"></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="h-4 w-32 bg-blue-100 dark:bg-zinc-800 rounded"></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="h-4 w-48 bg-blue-100 dark:bg-zinc-800 rounded mb-2"></div>
                                    <div class="h-3 w-36 bg-blue-50 dark:bg-zinc-800/70 rounded"></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="h-4 w-24 bg-pink-100 dark:bg-zinc-800 rounded"></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="h-6 w-16 bg-green-100 dark:bg-zinc-800 rounded-full"></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="h-6 w-12 bg-blue-100 dark:bg-zinc-800 rounded-full"></div>
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
                        @forelse (($this->jobAdverts ?? []) as $advert)
                            <tr class="hover:bg-gray-100 dark:hover:bg-white/20 group border-b border-gray-200 dark:border-gray-700 transition-all duration-500 ease-in-out" wire:loading.class.delay="opacity-50 dark:opacity-40">
                                <td class="px-5 py-4">
                                    <button type="button"
                                        wire:click="toggleSelection({{ $advert->id }})"
                                        class="rounded focus:ring-2 focus:ring-pink-400 transition-colors duration-200
                                            @if(in_array($advert->id, $selected))
                                                bg-pink-500 text-white p-[2px]
                                            @else
                                                bg-transparent text-pink-500 border border-gray-500 p-[6px]
                                            @endif
                                            flex items-center gap-2"
                                    >
                                        @if(in_array($advert->id, $selected))
                                            <svg class="w-3 h-3 text-gray-800 font-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        @endif
                                    </button>
                                </td>
                                <td class="px-5 py-4 text-gray-900 dark:text-white font-bold max-w-xs truncate">
                                    <span class="group-hover:underline">{{ $advert->title }}</span>
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300 min-w-md col-span-2">
                                    <span class="line-clamp-2">{{ $advert->description }}</span>
                                </td>
                                <td class="px-5 py-4 font-semibold">
                                    @php
                                        $deadlineDate = \Carbon\Carbon::parse($advert->deadline);
                                        $isPast = $deadlineDate->isPast();
                                    @endphp
                                    <span class="truncate
                                        @if($isPast)
                                            text-red-600 dark:text-red-400
                                        @else
                                            text-blue-600 dark:text-blue-400
                                        @endif
                                    ">
                                        {{ $deadlineDate->translatedFormat('j M Y, H:i') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold shadow-md
                                        @if ($advert->status === 'Published') bg-gradient-to-r from-green-400/60 via-green-300/60 to-green-500/60 text-green-900 dark:bg-green-900/60 dark:text-green-200
                                        @elseif($advert->status === 'Draft') bg-gradient-to-r from-yellow-300/60 via-yellow-200/60 to-yellow-400/60 text-yellow-900 dark:bg-yellow-900/60 dark:text-yellow-200
                                        @else bg-gradient-to-r from-red-300/60 via-red-200/60 to-red-400/60 text-red-900 dark:bg-red-900/60 dark:text-red-200 @endif
                                        transition-colors duration-200 backdrop-blur-sm"
                                    >
                                        {{ __($advert->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($advert->applications_count > 0)
                                                bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                            @else
                                                bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300
                                            @endif">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            {{ $advert->applications_count ?? 0 }}
                                        </span>
                                        @can('manage_applications')
                                            @if($advert->applications_count > 0)
                                                <button wire:click="manageApplications({{ $advert->id }})"
                                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-xs underline">
                                                    {{ __('View') }}
                                                </button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="flex gap-2">
                                        {{-- view button --}}
                                        <flux:button
                                            wire:click="confirmView({{ $advert->id }})"
                                            variant="primary"
                                            color="green"
                                            size="sm"
                                            icon="eye"
                                        />
                                        @can('edit_job_advert')
                                            <flux:button
                                                wire:click="confirmEdit({{ $advert->id }})"
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
                                        @can('delete_job_advert')
                                            <flux:button
                                                wire:click="confirmDelete({{ $advert->id }})"
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
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-8 h-8 text-gray-300 dark:text-zinc-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 10h6v4H9z"></path>
                                        </svg>
                                        {{ __('No job adverts found.') }}
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
            
            <!-- Pagination -->
            <div class="mt-6">
                @if($this->jobAdverts && !$this->shouldShowSkeleton())
                    {{ $this->jobAdverts->links() }}
                @endif
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
            <div
                class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-md w-full border border-gray-100 dark:border-zinc-800">
                <h3
                    class="text-xl font-bold mb-4 text-red-600 dark:text-red-400 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor"
                        stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    {{ __('Confirm Delete') }}
                </h3>
                <p class="mb-6 text-zinc-700 dark:text-zinc-300">
                    {{ __('Are you sure you want to delete this job advert? This action cannot be undone.') }}
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

    <!-- Edit Confirmation Modal -->
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
            <div
                class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-md w-full border border-gray-100 dark:border-zinc-800">
                <h3
                    class="text-xl font-bold mb-4 text-blue-600 dark:text-blue-400 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor"
                        stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.232 5.232l3.536 3.536M9 13l6.293-6.293a1 1 0 011.414 0l1.586 1.586a1 1 0 010 1.414L11 15H9v-2z">
                        </path>
                    </svg>
                    {{ __('Confirm Edit') }}
                </h3>
                <p class="mb-6 text-zinc-700 dark:text-zinc-300">
                    {{ __('Are you sure you want to edit this job advert?') }}</p>
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
    
    <!-- Bulk Delete Confirmation Modal -->
    @if ($showBulkDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
            <div
                class="bg-gradient-to-br from-pink-50/80 via-white/80 to-red-100/80 dark:from-zinc-900/80 dark:via-zinc-800/80 dark:to-zinc-900/80 backdrop-blur-xl rounded-xl shadow-2xl p-10 max-w-md w-full border border-pink-200 dark:border-zinc-800">
                <h3
                    class="text-2xl font-extrabold text-pink-600 dark:text-pink-400 flex items-center gap-2 mb-4">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    {{ __('Confirm Bulk Delete') }}
                </h3>
                <p class="mb-6 text-zinc-700 dark:text-zinc-300 font-semibold">
                    {{ __('Are you sure you want to delete the selected job adverts? This action cannot be undone.') }}
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
    
    <!-- View Job Advert Modal -->
    @if($showViewModal)
        <div class="fixed inset-0 z-50 flex items-start overflow-y-auto lg:py-8 justify-center bg-black/40 backdrop-blur-sm transition">
            <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-4xl w-full border border-gray-100 dark:border-zinc-800">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold mb-4 text-blue-600 dark:text-blue-400 flex items-center gap-2">
                        <flux:icon name="eye" class="w-6 h-6" />
                        {{ __('View Job Advert') }}
                    </h3>
                    <button wire:click="$set('showViewModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                @if($pendingViewId)
                @php
                    $viewAdvert = JobAdvert::find($pendingViewId);
                @endphp
                @if($viewAdvert)
                <div class="space-y-6">
                    <!-- Job Advert Info -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                        <h4 class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-2">{{ __('Job Advert Information') }}</h4>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m8 0V8a2 2 0 01-2 2H8a2 2 0 01-2-2V6m8 0H8"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-blue-900 dark:text-blue-100">
                                    {{ $viewAdvert->title }}
                                </p>
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    {{ __('ID') }}: {{ $viewAdvert->id }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Job Advert Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Title') }}</h4>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $viewAdvert->title }}
                            </p>
                        </div>

                        <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Status') }}</h4>
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                                {{ $viewAdvert->status === 'Published' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 
                                    ($viewAdvert->status === 'Draft' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300') }}">
                                {{ ucfirst($viewAdvert->status) }}
                            </span>
                        </div>

                        <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Deadline') }}</h4>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($viewAdvert->deadline)->format('M d, Y H:i') }}
                            </p>
                        </div>

                        <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Created At') }}</h4>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($viewAdvert->created_at)->format('M d, Y H:i') }}
                            </p>
                        </div>
                    </div>

                    <!-- Applications Status Breakdown -->
                    @php
                        $applicationStats = \App\Models\Application::where('job_advert_id', $viewAdvert->id)
                            ->selectRaw('status, COUNT(*) as count')
                            ->groupBy('status')
                            ->pluck('count', 'status');
                        $totalApplications = $applicationStats->sum();
                    @endphp
                    @if($totalApplications > 0)
                    <div class="bg-gradient-to-r from-blue-50/80 to-indigo-50/80 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                        <h4 class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{ __('Applications Overview') }} ({{ $totalApplications }})
                        </h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="bg-white/60 dark:bg-zinc-800/60 rounded-lg p-3 text-center border border-gray-200 dark:border-zinc-700">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $applicationStats->get('pending', 0) }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wide">{{ __('Pending') }}</div>
                            </div>
                            <div class="bg-white/60 dark:bg-zinc-800/60 rounded-lg p-3 text-center border border-gray-200 dark:border-zinc-700">
                                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $applicationStats->get('shortlisted', 0) }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wide">{{ __('Shortlisted') }}</div>
                            </div>
                            <div class="bg-white/60 dark:bg-zinc-800/60 rounded-lg p-3 text-center border border-gray-200 dark:border-zinc-700">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $applicationStats->get('invited', 0) }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wide">{{ __('Invited') }}</div>
                            </div>
                            <div class="bg-white/60 dark:bg-zinc-800/60 rounded-lg p-3 text-center border border-gray-200 dark:border-zinc-700">
                                <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $applicationStats->get('rejected', 0) }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wide">{{ __('Rejected') }}</div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Description -->
                    @if($viewAdvert->description)
                    <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Description') }}</h4>
                        <p class="text-gray-900 dark:text-white">{{ $viewAdvert->description }}</p>
                    </div>
                    @endif

                    <!-- Slug -->
                    <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Slug') }}</h4>
                        <p class="text-gray-900 dark:text-white font-mono">{{ $viewAdvert->slug }}</p>
                    </div>
                </div>
                @endif
                @endif

                <div class="flex justify-between gap-3 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                    @can('manage_applications')
                        <flux:button
                            wire:click="manageApplications({{ $pendingViewId }})"
                            variant="primary"
                            color="blue"
                            size="sm"
                            icon="document-text"
                        >
                            {{ ('Manage Applications') }}
                        </flux:button>
                    @else
                        <flux:button
                            variant="primary"
                            color="gray"
                            color="blue"
                            size="sm"
                            icon="document-text"
                            class="cursor-not-allowed"
                        >
                            {{ ('Manage Applications') }}
                        </flux:button>
                    @endcan
                    <flux:button
                        wire:click="$set('showViewModal', false)"
                    >
                        {{ __('Close') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- JavaScript for CSV Download -->
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