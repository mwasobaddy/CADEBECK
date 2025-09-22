<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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
    public $filterRole = '';
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

    public function getUsersProperty()
    {
        $query = User::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterRole) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', $this->filterRole);
            });
        }

        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        if ($this->sortField === 'roles') {
            $query->leftJoin('model_has_roles', function($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', User::class);
            })
            ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('users.*')
            ->orderBy('roles.name', $direction);
        } elseif ($this->sortField === 'name') {
            $query->orderByRaw("CONCAT(first_name, ' ', other_names) $direction");
        } else {
            $field = in_array($this->sortField, ['email', 'created_at']) ? $this->sortField : 'created_at';
            $query->orderBy($field, $direction);
        }
        return $query->with('roles')->paginate($this->perPage);
    }

    public function updatedSearch(): void
    {
        $this->isSearching = true;
        $this->resetPage();
        $this->updateSelectAllState();
        $this->isSearching = false;
    }

    public function updatedFilterRole(): void
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
        $users = $this->users;
        if ($users && $users->count() > 0) {
            $currentPageIds = $users->pluck('id')->toArray();
            $this->selectAll = count($currentPageIds) > 0 && 
                              count(array_intersect($this->selected, $currentPageIds)) === count($currentPageIds);
        } else {
            $this->selectAll = false;
        }
    }

    public function toggleSelectAll(): void
    {
        $users = $this->users;
        if ($users && $users->count() > 0) {
            $currentPageIds = $users->pluck('id')->toArray();
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
        $query = User::query();
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }
        if ($this->filterRole) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', $this->filterRole);
            });
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
        $users = User::whereIn('id', $this->selected)->get();
        User::whereIn('id', $this->selected)->delete();

        // Log the bulk delete action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'bulk_delete',
            'target_type' => User::class,
            'details' => json_encode(['user_ids' => $this->selected]),
        ]);

        $this->showBulkDeleteModal = false;
        $this->isLoadingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;
        $this->updateSelectAllState();
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Selected users deleted successfully.')]);
    }

    public function exportSelected(): void
    {
        $this->isLoadingExport = true;
        $users = User::whereIn('id', $this->selected)->get();

        // Log the export selected action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'export_selected',
            'target_type' => User::class,
            'details' => json_encode(['user_ids' => $this->selected]),
        ]);

        $csvData = "ID,Name,Email,Created At\n";
        foreach ($users as $user) {
            $csvData .= '"' . $user->id . '","' .
                       str_replace('"', '""', $user->name) . '","' .
                       str_replace('"', '""', $user->email) . '","' .
                       $user->created_at . '"' . "\n";
        }
        $this->isLoadingExport = false;
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'users_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', ['type' => 'success', 'message' => __('User exported successfully.')]);
    }

    public function exportAll(): void
    {
        $this->isLoadingExport = true;
        $query = User::query();
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }
        if ($this->filterRole) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', $this->filterRole);
            });
        }
        $users = $query->orderByDesc('created_at')->get();

        // Log the export all action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'export_all',
            'target_type' => User::class,
            'details' => json_encode(['total_users' => $users->count()]),
        ]);

        $csvData = "ID,Name,Email,Created At\n";
        foreach ($users as $user) {
            $csvData .= '"' . $user->id . '","' .
                       str_replace('"', '""', $user->name) . '","' .
                       str_replace('"', '""', $user->email) . '","' .
                       $user->created_at . '"' . "\n";
        }
        $this->isLoadingExport = false;
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'all_users_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', ['type' => 'success', 'message' => __('User exported successfully.')]);
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
        $user = User::findOrFail($this->pendingEditId);
        $this->showEditModal = false;
        $this->isLoadingEdit = false;
        $this->redirectRoute('user.edit', ['id' => $user->id]);
    }

    public function deleteConfirmed(): void
    {
        $this->isLoadingDelete = true;
        $user = User::findOrFail($this->pendingDeleteId);
        $user->delete();

        // Log the delete action
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'delete',
            'target_type' => User::class,
            'target_id' => $user->id,
            'details' => json_encode(['name' => $user->name, 'email' => $user->email]),
        ]);

        $this->resetForm();
        $this->showDeleteModal = false;
        $this->isLoadingDelete = false;
        $this->dispatch('notify', ['type' => 'success', 'message' => __('User deleted successfully.')]);
    }

    public function resetForm(): void
    {
        $this->form = [
            'name' => '',
            'email' => '',
            'password' => '',
        ];
        $this->editingId = null;
    }

    public function createNewUser(): void
    {
        $this->redirectRoute('user.show');
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
                <a href="{{ route('user.index') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('user.index') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none' : '' }}" wire:navigate>
                    {{ __('User List') }}
                </a>
                <a href="{{ route('user.show') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('user.show') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none' : '' }}" wire:navigate>
                    {{ __('Create User') }}
                </a>
            </div>
        </nav>
    </div>

    @can('manage_user')
        <!-- Card Container for Table -->
        <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
            <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
                <div class="flex items-center gap-3 mb-8">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"></path>
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                    </svg>
                    <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                        {{ __('User List') }}
                        <span class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                    </h1>
                </div>
                <div class="flex items-center gap-3">
                    @can('export_user')
                        <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" wire:click="exportAll" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            {{ $isLoadingExport ? __('Exporting...') : __('Export All') }}
                        </flux:button>
                    @else
                        <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" :disabled="true" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            {{ __('Exporting Denied') }}
                        </flux:button>
                    @endcan
                    @can('create_user')
                        <flux:button icon:trailing="plus" variant="primary" type="button" wire:click="createNewUser" class="flex flex-row items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            {{ __('Add User') }}
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
                        <input type="text" wire:model.live.debounce.300ms="search"
                            class="w-full pl-10 pr-4 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white transition shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md"
                            placeholder="{{ __('Search user...') }}">
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
                        <flux:select wire:model.live="filterRole" placeholder="{{ __('All Roles') }}" class="!ps-3 pe-4 !py-2 !rounded-full border !border-blue-200 dark:!border-indigo-700 !focus:ring-2 !focus:ring-blue-400 dark:!bg-zinc-800/80 dark:!text-white !shadow-sm !bg-white/80 dark:!bg-zinc-900/80 !backdrop-blur-md !w-fit !outline-none">
                            <flux:select.option value="">{{ __('All Roles') }}</flux:select.option>
                            @foreach (Spatie\Permission\Models\Role::all() as $role)
                                <flux:select.option value="{{ $role->name }}">{{ $role->name }}</flux:select.option>
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
                        @if(count($selected) < ($this->users ? $this->users->total() : 0))
                            <button type="button" wire:click="selectAllData"
                                class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                {{ __('Select all') }} {{ $this->users ? $this->users->total() : 0 }} {{ __('items') }}
                            </button>
                        @endif
                    </div>
                    <div class="flex items-end justify-end gap-3 md:col-span-2 lg:col-span-3">
                        @can('export_user')
                            <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" wire:click="exportSelected" :disabled="$isLoadingExport" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                {{ $isLoadingExport ? __('Exporting...') : __('Export Selected') }}
                            </flux:button>
                        @else
                            <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" wire:click="exportSelected" :disabled="true" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                {{ __('Exporting Denied') }}
                            </flux:button>
                        @endcan
                        @can('delete_user')
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
                                        <svg class="w-3 h-3 text-gray-800 font-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    @endif
                                </button>
                            </th>
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('name')">
                                {{ __('Name') }}
                                @if($this->sortField === 'name')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3 text-gray-400 inline ml-1" />
                                @else
                                    <flux:icon name="arrows-up-down" class="w-3 h-3 text-gray-400 inline ml-1" />
                                @endif
                            </th>
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('roles')">
                                {{ __('Roles') }}
                                @if($this->sortField === 'roles')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3 text-gray-400 inline ml-1" />
                                @else
                                    <flux:icon name="arrows-up-down" class="w-3 h-3 text-gray-400 inline ml-1" />
                                @endif
                            </th>
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('created_at')">
                                {{ __('Created At') }}
                                @if($this->sortField === 'created_at')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3 text-gray-400 inline ml-1" />
                                @else
                                    <flux:icon name="arrows-up-down" class="w-3 h-3 text-gray-400 inline ml-1" />
                                @endif
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
                                        <div class="flex gap-2">
                                            <div class="h-8 w-8 bg-gray-100 dark:bg-zinc-800 rounded"></div>
                                            <div class="h-8 w-8 bg-gray-100 dark:bg-zinc-800 rounded"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endfor
                        @else
                            @forelse (($this->users ?? []) as $user)
                                <tr class="hover:bg-gray-100 dark:hover:bg-white/20 group border-b border-gray-200 dark:border-gray-700 transition-all duration-500 ease-in-out" wire:loading.class.delay="opacity-50 dark:opacity-40">
                                    <td class="px-5 py-4">
                                        <button type="button"
                                            wire:click="toggleSelection({{ $user->id }})"
                                            class="rounded focus:ring-2 focus:ring-pink-400 transition-colors duration-200
                                                @if(in_array($user->id, $selected))
                                                    bg-pink-500 text-white p-[2px]
                                                @else
                                                    bg-transparent text-pink-500 border border-gray-500 p-[6px]
                                                @endif
                                                flex items-center gap-2"
                                        >
                                            @if(in_array($user->id, $selected))
                                                <svg class="w-3 h-3 text-gray-800 font-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            @endif
                                        </button>
                                    </td>
                                    <td class="px-5 py-4 text-gray-900 dark:text-white font-bold max-w-xs truncate flex items-center gap-3">
                                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 dark:bg-zinc-800 text-blue-600 dark:text-blue-300 font-bold text-lg">
                                            {{ strtoupper(Str::substr($user->first_name, 0, 1) . Str::substr($user->other_names, 0, 1)) }}
                                        </span>
                                        <span>
                                            <span class="block font-semibold text-base">{{ $user->first_name }}{{ $user->other_names ? ' ' . $user->other_names : '' }}</span>
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-gray-700 dark:text-gray-300 min-w-md col-span-2">
                                        @foreach($user->roles as $role)
                                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold shadow bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 mr-1 mb-1">
                                                {{ __($role->name) }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="px-5 py-4 font-semibold">
                                        <span class="truncate text-blue-600 dark:text-blue-400">
                                            {{ $user->created_at->translatedFormat('j M Y, H:i') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="flex gap-2">
                                            @can('edit_user')
                                                <flux:button
                                                    wire:click="confirmEdit({{ $user->id }})"
                                                    variant="primary"
                                                    color="blue"
                                                    size="sm"
                                                    icon="pencil-square"
                                                />
                                            @endcan
                                            @can('delete_user')
                                                <flux:button
                                                    wire:click="confirmDelete({{ $user->id }})"
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
                                            {{ __('No users found.') }}
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        @endif
                    </tbody>
                </table>
                <div class="mt-6">
                    @if($this->users && !$this->shouldShowSkeleton())
                        {{ $this->users->links() }}
                    @endif
                </div>
            </div>
        </div>
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
                        {{ __('Are you sure you want to delete this user? This action cannot be undone.') }}
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
                        {{ __('Are you sure you want to edit this user?') }}</p>
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
        @if ($showBulkDeleteModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
                <div class="bg-gradient-to-br from-pink-50/80 via-white/80 to-red-100/80 dark:from-zinc-900/80 dark:via-zinc-800/80 dark:to-zinc-900/80 backdrop-blur-xl rounded-xl shadow-2xl p-10 max-w-md w-full border border-pink-200 dark:border-zinc-800">
                    <h3 class="text-2xl font-extrabold text-pink-600 dark:text-pink-400 flex items-center gap-2 mb-4">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        {{ __('Confirm Bulk Delete') }}
                    </h3>
                    <p class="mb-6 text-zinc-700 dark:text-zinc-300 font-semibold">
                        {{ __('Are you sure you want to delete the selected users? This action cannot be undone.') }}
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
