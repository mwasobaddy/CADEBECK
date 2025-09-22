<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Application;
use App\Models\JobAdvert;
use App\Models\Audit;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public $showFilters = false;
    public ?int $jobAdvertId = null;
    public $showDeleteModal = false;
    public $showViewModal = false;
    public $showEditModal = false;
    public $showExportModal = false;
    public $pendingDeleteId = null;
    public $pendingViewId = null;
    public $pendingEditId = null;
    public $isLoadingDelete = false;
    public $isLoadingView = false;
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

    public $sortField = 'submitted_at';
    public $sortDirection = 'desc';

    public function mount($jobAdvertId)
    {
        $this->jobAdvertId = $jobAdvertId;
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

    public function getApplicationsProperty()
    {
        $query = Application::query()->with(['jobAdvert'])
            ->where('job_advert_id', $this->jobAdvertId);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        $field = in_array($this->sortField, ['name', 'email', 'phone', 'status', 'submitted_at']) ? $this->sortField : 'submitted_at';
        $query->orderBy($field, $direction);

        return $query->paginate($this->perPage);
    }

    public function getJobAdvertProperty()
    {
        return JobAdvert::find($this->jobAdvertId);
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

    public function updateSelectAllState(): void
    {
        $applications = $this->applications;
        if ($applications && $applications->count() > 0) {
            $currentPageIds = $applications->pluck('id')->toArray();
            $this->selectAll = count($currentPageIds) > 0 &&
                              count(array_intersect($this->selected, $currentPageIds)) === count($currentPageIds);
        } else {
            $this->selectAll = false;
        }
    }

    public function toggleSelectAll(): void
    {
        $applications = $this->applications;
        if ($applications && $applications->count() > 0) {
            $currentPageIds = $applications->pluck('id')->toArray();
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
        $query = Application::query()->where('job_advert_id', $this->jobAdvertId);
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('phone', 'like', '%' . $this->search . '%');
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
        Application::whereIn('id', $this->selected)->delete();

        // Log the bulk delete action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'bulk_delete',
            'target_type' => Application::class,
            'details' => json_encode(['application_ids' => $this->selected]),
        ]);

        $this->showBulkDeleteModal = false;
        $this->isLoadingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;
        $this->updateSelectAllState();
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Selected applications deleted successfully.')]);
    }

    public function exportSelected(): void
    {
        $this->isLoadingExport = true;

        // Get selected applications
        $applications = Application::whereIn('id', $this->selected)->get();

        // Create CSV content
        $csvData = "ID,Name,Email,Phone,Status,Submitted At,Cover Letter\n";
        foreach ($applications as $application) {
            $csvData .= '"' . $application->id . '","' .
                       str_replace('"', '""', $application->name) . '","' .
                       $application->email . '","' .
                       $application->phone . '","' .
                       $application->status . '","' .
                       $application->submitted_at . '","' .
                       str_replace('"', '""', $application->cover_letter) . '"' . "\n";
        }

        // Log the export selected action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'export_selected',
            'target_type' => Application::class,
            'details' => json_encode(['application_ids' => $this->selected]),
        ]);

        $this->isLoadingExport = false;

        // Trigger download
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'applications_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Selected applications exported successfully.')]);
    }

    public function exportAll(): void
    {
        $this->isLoadingExport = true;

        // Get all applications based on current filters
        $query = Application::query()->where('job_advert_id', $this->jobAdvertId);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $applications = $query->orderByDesc('submitted_at')->get();

        // Create CSV content
        $csvData = "ID,Name,Email,Phone,Status,Submitted At,Cover Letter\n";
        foreach ($applications as $application) {
            $csvData .= '"' . $application->id . '","' .
                       str_replace('"', '""', $application->name) . '","' .
                       $application->email . '","' .
                       $application->phone . '","' .
                       $application->status . '","' .
                       $application->submitted_at . '","' .
                       str_replace('"', '""', $application->cover_letter) . '"' . "\n";
        }

        // Log the export all action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'export_all',
            'target_type' => Application::class,
            'details' => json_encode(['total_applications' => $applications->count()]),
        ]);

        $this->isLoadingExport = false;

        // Trigger download
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'all_applications_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', ['type' => 'success', 'message' => __('All applications exported successfully.')]);
    }

    public function confirmView($id): void
    {
        $this->pendingViewId = $id;
        $this->showViewModal = true;
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
        $application = Application::findOrFail($this->pendingEditId);
        $this->showEditModal = false;
        $this->isLoadingEdit = false;
        // TODO: Implement edit functionality
        $this->dispatch('notify', ['type' => 'info', 'message' => __('Edit functionality coming soon.')]);
    }

    public function deleteConfirmed(): void
    {
        $this->isLoadingDelete = true;
        $application = Application::findOrFail($this->pendingDeleteId);
        $application->delete();

        // Log the delete action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'delete',
            'target_type' => Application::class,
            'target_id' => $application->id,
            'details' => json_encode(['name' => $application->name, 'email' => $application->email]),
        ]);

        $this->dispatch('notify', ['type' => 'success', 'message' => __('Application deleted successfully.')]);

        $this->resetForm();
        $this->showDeleteModal = false;
        $this->isLoadingDelete = false;
    }

    public function resetForm(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function backToJobAdverts(): void
    {
        $this->redirectRoute('job.index', navigate: true);
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
                <button wire:click="backToJobAdverts" class="flex items-center gap-2 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    {{ __('Back to Job Adverts') }}
                </button>
                <span class="text-gray-400 dark:text-gray-500">/</span>
                <span class="text-gray-900 dark:text-white font-medium">{{ $this->jobAdvert->title ?? 'Applications' }}</span>
            </div>
        </nav>
    </div>

    @can('manage_job_advert')
        <!-- Card Container for Table -->
        <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
            <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
                <div class="flex items-center gap-3 mb-8">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                        {{ __('Job Applications') }}
                        <span class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                    </h1>
                </div>
                <div class="flex items-center gap-3">
                    @can('manage_job_advert')
                        <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" wire:click="exportAll" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            {{ $isLoadingExport ? __('Exporting...') : __('Export All') }}
                        </flux:button>
                    @else
                        <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" :disabled="true" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            {{ __('Exporting Denied') }}
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
                            placeholder="{{ __('Search applications...') }}">
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
                            <option value="Pending">{{ __('Pending') }}</option>
                            <option value="Shortlisted">{{ __('Shortlisted') }}</option>
                            <option value="Rejected">{{ __('Rejected') }}</option>
                            <option value="Invited">{{ __('Invited') }}</option>
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
                <div class="flex items-center justify-between flex-wrap mt-6 p-4 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 dark:from-zinc-800/50 dark:to-zinc-700/50 rounded-xl border border-blue-200 dark:border-zinc-700 backdrop-blur-sm">
                    <div class="flex items-center gap-2 py-2">
                        <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                            {{ count($selected) }} {{ __('item(s) selected') }}
                        </span>
                        @if(count($selected) < ($this->applications ? $this->applications->total() : 0))
                            <button type="button" wire:click="selectAllData"
                                class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                {{ __('Select all') }} {{ $this->applications ? $this->applications->total() : 0 }} {{ __('items') }}
                            </button>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        @can('manage_job_advert')
                            <!-- Export Selected Button -->
                            <button type="button" wire:click="exportSelected"
                                class="flex items-center gap-2 px-4 py-2 rounded-xl border border-purple-200 dark:border-purple-700 text-purple-600 dark:text-purple-400 bg-purple-50/80 dark:bg-purple-900/20 hover:bg-purple-100/80 dark:hover:bg-purple-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-purple-400 transition"
                                @if ($isLoadingExport) disabled @endif>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                {{ $isLoadingExport ? __('Exporting...') : __('Export Selected') }}
                            </button>
                        @endcan
                        
                        @can('manage_job_advert')
                            <!-- Delete Selected Button -->
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
                                        <!-- Checkmark Icon -->
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
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('email')">
                                {{ __('Email') }}
                                @if($this->sortField === 'email')
                                    <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        @endif
                                    </svg>
                                @endif
                            </th>
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('phone')">
                                {{ __('Phone') }}
                                @if($this->sortField === 'phone')
                                    <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        @endif
                                    </svg>
                                @endif
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
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('submitted_at')">
                                {{ __('Submitted At') }}
                                @if($this->sortField === 'submitted_at')
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
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($this->applications ?? [] as $application)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-5 py-4">
                                    <flux:checkbox
                                        wire:model.live="selected"
                                        value="{{ $application->id }}"
                                    />
                                </td>
                                <td class="px-5 py-4 text-gray-900 dark:text-white font-bold max-w-xs truncate">
                                    <span class="group-hover:underline">{{ $application->name }}</span>
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                                    {{ $application->email }}
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                                    {{ $application->phone }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold shadow-md
                                        @if ($application->status === 'Pending') bg-gradient-to-r from-yellow-400/60 via-yellow-300/60 to-yellow-500/60 text-yellow-900 dark:bg-yellow-900/60 dark:text-yellow-200
                                        @elseif($application->status === 'Shortlisted') bg-gradient-to-r from-blue-400/60 via-blue-300/60 to-blue-500/60 text-blue-900 dark:bg-blue-900/60 dark:text-blue-200
                                        @elseif($application->status === 'Rejected') bg-gradient-to-r from-red-400/60 via-red-300/60 to-red-500/60 text-red-900 dark:bg-red-900/60 dark:text-red-200
                                        @else bg-gradient-to-r from-green-400/60 via-green-300/60 to-green-500/60 text-green-900 dark:bg-green-900/60 dark:text-green-200 @endif
                                        transition-colors duration-200 backdrop-blur-sm"
                                    >
                                        {{ __($application->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                                    @if($application->submitted_at)
                                        {{ \Carbon\Carbon::parse($application->submitted_at)->format('M d, Y H:i') }}
                                    @else
                                        {{ __('Not submitted') }}
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span class="flex gap-2">
                                        <flux:button
                                            wire:click="confirmView({{ $application->id }})"
                                            variant="primary"
                                            color="green"
                                            size="sm"
                                            icon="eye"
                                        />
                                        <flux:button
                                            wire:click="confirmEdit({{ $application->id }})"
                                            variant="primary"
                                            color="blue"
                                            size="sm"
                                            icon="pencil-square"
                                        />
                                        <flux:button
                                            wire:click="confirmDelete({{ $application->id }})"
                                            variant="danger"
                                            color="red"
                                            size="sm"
                                            icon="trash"
                                        />
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
                                        {{ __('No applications found.') }}
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="mt-6">
                    @if($this->applications && !$this->shouldShowSkeleton())
                        {{ $this->applications->links() }}
                    @endif
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        @if ($showDeleteModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
                <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-md w-full border border-gray-100 dark:border-zinc-800">
                    <h3 class="text-xl font-bold mb-4 text-red-600 dark:text-red-400 flex items-center gap-2">
                        <flux:icon name="exclamation-triangle" class="w-6 h-6" />
                        {{ __('Confirm Delete') }}
                    </h3>
                    <p class="mb-6 text-zinc-700 dark:text-zinc-300">
                        {{ __('Are you sure you want to delete this application? This action cannot be undone.') }}
                    </p>
                    <div class="flex justify-end gap-3">
                        <flux:button
                            wire:click="$set('showDeleteModal', false)"
                            variant="outline"
                            color="gray"
                        >
                            {{ __('Cancel') }}
                        </flux:button>
                        <flux:button
                            wire:click="deleteConfirmed"
                            :loading="$isLoadingDelete"
                            variant="danger"
                            color="red"
                        >
                            {{ $isLoadingDelete ? __('Deleting...') : __('Delete') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif

        <!-- View Application Modal -->
        @if($showViewModal)
            <div class="fixed inset-0 z-50 flex items-start overflow-y-auto lg:py-8 justify-center bg-black/40 backdrop-blur-sm transition">
                <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-4xl w-full border border-gray-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold mb-4 text-blue-600 dark:text-blue-400 flex items-center gap-2">
                            <flux:icon name="eye" class="w-6 h-6" />
                            {{ __('View Application') }}
                        </h3>
                        <button wire:click="$set('showViewModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    @if($pendingViewId)
                    @php
                        $viewApplication = Application::find($pendingViewId);
                    @endphp
                    @if($viewApplication)
                    <div class="space-y-6">
                        <!-- Applicant Info -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                            <h4 class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-2">{{ __('Applicant Information') }}</h4>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-blue-900 dark:text-blue-100">
                                        {{ $viewApplication->name }}
                                    </p>
                                    <p class="text-sm text-blue-700 dark:text-blue-300">
                                        {{ __('ID') }}: {{ $viewApplication->id }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Application Details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Email') }}</h4>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $viewApplication->email }}
                                </p>
                            </div>

                            <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Phone') }}</h4>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $viewApplication->phone }}
                                </p>
                            </div>

                            <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Status') }}</h4>
                                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                                    {{ $viewApplication->status === 'Pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' :
                                       ($viewApplication->status === 'Shortlisted' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' :
                                       ($viewApplication->status === 'Rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300')) }}">
                                    {{ ucfirst($viewApplication->status) }}
                                </span>
                            </div>

                            <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Submitted At') }}</h4>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $viewApplication->submitted_at ? \Carbon\Carbon::parse($viewApplication->submitted_at)->format('M d, Y H:i') : __('Not submitted') }}
                                </p>
                            </div>
                        </div>

                        <!-- Cover Letter -->
                        @if($viewApplication->cover_letter)
                        <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Cover Letter') }}</h4>
                            <p class="text-gray-900 dark:text-white whitespace-pre-line">{{ $viewApplication->cover_letter }}</p>
                        </div>
                        @endif

                        <!-- CV Download -->
                        @if($viewApplication->cv_blob)
                        <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-4">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('CV/Resume') }}</h4>
                            <a href="{{ route('application.download-cv', $viewApplication->id) }}" 
                               target="_blank"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                {{ __('Download CV') }}
                            </a>
                        </div>
                        @endif
                    </div>
                    @endif
                    @endif

                    <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button wire:click="$set('showViewModal', false)"
                                class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 rounded-xl font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
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
                        {{ __('Edit functionality coming soon.') }}
                    </p>
                    <div class="flex items-end justify-end gap-3 md:col-span-2 lg:col-span-3">
                        <flux:button
                            wire:click="$set('showEditModal', false)"
                            variant="outline"
                            color="gray"
                        >
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
                        <flux:icon name="exclamation-triangle" class="w-6 h-6" />
                        {{ __('Confirm Bulk Delete') }}
                    </h3>
                    <p class="mb-6 text-zinc-700 dark:text-zinc-300">
                        {{ __('Are you sure you want to delete the selected applications? This action cannot be undone.') }}
                    </p>
                    <div class="flex justify-end gap-3">
                        <flux:button
                            wire:click="$set('showBulkDeleteModal', false)"
                            variant="outline"
                            color="gray"
                        >
                            {{ __('Cancel') }}
                        </flux:button>
                        <flux:button
                            wire:click="bulkDelete"
                            :loading="$isLoadingBulkDelete"
                            variant="danger"
                            color="red"
                        >
                            {{ $isLoadingBulkDelete ? __('Deleting...') : __('Delete Selected') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif
    @else
        {{-- using the same layout as the a you do not have the permission to access this --}}
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