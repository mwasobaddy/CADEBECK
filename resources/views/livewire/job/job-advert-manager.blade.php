<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\JobAdvert;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public $showFilters = false;
    public ?int $editingId = null;
    // Removed public $jobAdverts property
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
    public $selected = [];
    public $selectAll = false;
    public $showBulkDeleteModal = false;
    public $perPage = 10;

    // Add loading states for various operations
    public $isSearching = false;
    public $isFiltering = false;
    public $isPaginating = false;
    public $isLoadingData = false; // Added new loading state

    // No need to load job adverts in mount

    public function getJobAdvertsProperty()
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

        return $query->orderByDesc('created_at')->paginate($this->perPage);
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
        $this->showBulkDeleteModal = false;
        $this->isLoadingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;
        $this->updateSelectAllState();
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
        
        $this->isLoadingExport = false;
        
        // Trigger download
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'job_adverts_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
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
        
        $this->isLoadingExport = false;
        
        // Trigger download
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'all_job_adverts_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
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
        $advert = JobAdvert::findOrFail($this->pendingEditId);
        $this->showEditModal = false;
        $this->isLoadingEdit = false;
        $this->redirectRoute('job.job-adverts.edit', ['slug' => $advert->slug]);
    }

    public function deleteConfirmed(): void
    {
        $this->isLoadingDelete = true;
        JobAdvert::findOrFail($this->pendingDeleteId)->delete();
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
        $this->redirectRoute('job.job-adverts.create');
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

<div class="relative max-w-6xl mx-auto px-4 py-8">
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
                <a href="{{ route('job.job-adverts.create') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('job.job-adverts') ? 'bg-green-600 dark:bg-green-800 text-white dark:text-zinc-200 border-none' : '' }}">
                    {{ __('Job Advert List') }}
                </a>
                <a href="{{ route('job.job-adverts.create') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('job.job-adverts.create') ? 'bg-green-600 dark:bg-green-800 text-white dark:text-zinc-200 border-none' : '' }}">
                    {{ __('Create Advert') }}
                </a>
            </div>
        </nav>
    </div>

    <!-- Card Container for Table -->
    <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex flex-row items-center justify-between mb-6 gap-4">
            <div class="flex items-center gap-2">
                <h2 class="text-2xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 flex items-center gap-2 drop-shadow-lg">
                    <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <rect x="4" y="6" width="16" height="12" rx="2" stroke="currentColor"
                            stroke-width="2" fill="none"></rect>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h8"></path>
                    </svg>
                    {{ __('Job Advert List') }}
                </h2>
            </div>
            
            <div class="flex items-center gap-3">
                <!-- Import Button -->
                <button type="button" onclick="document.getElementById('import-file').click()"
                    class="flex items-center gap-2 px-4 py-2 rounded-full border border-green-200 dark:border-green-700 text-green-600 dark:text-green-400 bg-green-50/80 dark:bg-green-900/20 hover:bg-green-100/80 dark:hover:bg-green-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-green-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                    </svg>
                    <span class="hidden lg:inline">{{ __('Import') }}</span>
                </button>
                <input type="file" id="import-file" accept=".csv,.xlsx,.xls" class="hidden" wire:change="importFile">

                <!-- Export All Button -->
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

                @can('create_job_advert')
                    <!-- Create New Advert Button -->
                    <button type="button" wire:click="createNewAdvert"
                        class="flex items-center gap-2 px-4 py-2 rounded-full border border-blue-200 dark:border-blue-700 text-blue-600 dark:text-blue-400 bg-blue-50/80 dark:bg-blue-900/20 hover:bg-blue-100/80 dark:hover:bg-blue-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span class="hidden lg:inline">{{ __('Add Advert') }}</span>
                    </button>
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
                    <input type="text" wire:model.live.debounce.300ms="search"
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
                    <select wire:model.live="filterStatus"
                        class="px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="Published">{{ __('Published') }}</option>
                        <option value="Draft">{{ __('Draft') }}</option>
                        <option value="Expired">{{ __('Expired') }}</option>
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

        <!-- Action Buttons Row -->
        @if (count($selected) > 0)
            <div class="flex items-center justify-between mt-6 p-4 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 dark:from-zinc-800/50 dark:to-zinc-700/50 rounded-xl border border-blue-200 dark:border-zinc-700 backdrop-blur-sm">
                <div class="flex items-center gap-2">
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
                <div class="flex items-center gap-3">
                    <!-- Export Selected Button -->
                    <button type="button" wire:click="exportSelected"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl border border-purple-200 dark:border-purple-700 text-purple-600 dark:text-purple-400 bg-purple-50/80 dark:bg-purple-900/20 hover:bg-purple-100/80 dark:hover:bg-purple-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-purple-400 transition"
                        @if ($isLoadingExport) disabled @endif>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        {{ $isLoadingExport ? __('Exporting...') : __('Export Selected') }}
                    </button>
                    
                    <!-- Delete Selected Button -->
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
                                    <!-- Checkmark Icon -->
                                    <svg class="w-3 h-3 text-gray-800 font-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">{{ __('Title') }}</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">{{ __('Description') }}</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">{{ __('Deadline') }}</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">{{ __('Actions') }}</th>
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
                                    <div class="flex gap-2">
                                        <div class="h-8 w-8 bg-gray-100 dark:bg-zinc-800 rounded"></div>
                                        <div class="h-8 w-8 bg-gray-100 dark:bg-zinc-800 rounded"></div>
                                    </div>
                                </td>
                            </tr>
                        @endfor
                    @else
                        @forelse (($this->jobAdverts ?? []) as $advert)
                            <tr class="hover:bg-gray-100 dark:hover:bg-white/20 transition group border-b border-gray-200 dark:border-gray-700">
                                <td class="px-5 py-4">
                                    <input type="checkbox" 
                                           wire:model.live="selected" 
                                           value="{{ $advert->id }}" 
                                           class="accent-pink-500 rounded focus:ring-2 focus:ring-pink-400" />
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
                                    <span class="flex gap-2">
                                        @can('edit_job_advert')
                                            <flux:button
                                                wire:click="confirmEdit({{ $advert->id }})"
                                                variant="primary"
                                                color="blue"
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
                                        @endcan
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
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