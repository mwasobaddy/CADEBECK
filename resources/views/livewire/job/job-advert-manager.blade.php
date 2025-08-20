<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\JobAdvert;

new #[Layout('components.layouts.app')] class extends Component {
    public $showFilters = false;
    public ?int $editingId = null;
    public $jobAdverts;
    public $showDeleteModal = false;
    public $showEditModal = false;
    public $pendingDeleteId = null;
    public $pendingEditId = null;
    public $isLoadingDelete = false;
    public $isLoadingEdit = false;
    public $search = '';
    public $filterStatus = '';
    public $selected = [];
    public $selectAll = false;
    public $showBulkDeleteModal = false;
    public $isLoadingBulkDelete = false;
    public $perPage = 10;
    public $page = 1;
    public $total = 0;

    public function mount(): void
    {
        $this->loadJobAdverts();
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
        $this->loadJobAdverts();
    }
    public function updatedFilterStatus(): void
    {
        $this->page = 1;
        $this->loadJobAdverts();
    }
    public function updatedPage(): void
    {
        $this->loadJobAdverts();
    }
    public function updatedPerPage(): void
    {
        $this->page = 1;
        $this->loadJobAdverts();
    }

    public function loadJobAdverts(): void
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
        $this->total = $query->count();
        $this->jobAdverts = $query
            ->orderByDesc('created_at')
            ->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage)
            ->get();
        $this->updateSelectAll();
    }

    public function updateSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selected = $this->jobAdverts->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function toggleSelectAll(): void
    {
        $this->selectAll = !$this->selectAll;
        $this->updateSelectAll();
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
        $this->loadJobAdverts();
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
        $this->redirectRoute('admin.job-adverts.edit', ['slug' => $advert->slug]);
    }

    public function deleteConfirmed(): void
    {
        $this->isLoadingDelete = true;
        JobAdvert::findOrFail($this->pendingDeleteId)->delete();
        $this->loadJobAdverts();
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
}; ?>

<div class="relative max-w-6xl mx-auto px-4 py-8">
    <!-- SVG Blobs Background -->
    <svg class="fixed -top-24 -left-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0" viewBox="0 0 400 400"
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

    {{-- create a mini navbar and separate breadcrumbs with glassmorphism --}}
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-lg p-6 mb-8 z-10 relative">
        <nav class="flex items-center justify-between">
            <div class="flex items-center">
                <a href="{{ route('admin.job-adverts.create') }}" class="text-gray-500 hover:text-gray-700">
                    {{ __('Job Advert List') }}
                </a>
                <span class="text-gray-400">/</span>
                <a href="{{ route('admin.job-adverts.create') }}" class="text-gray-500 hover:text-gray-700">
                    {{ __('Create Advert') }}
                </a>
            </div>
        </nav>
    </div>

    <!-- Card Container for Table -->
    <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex flex-row items-center justify-between mb-6 gap-4">
            <div class="flex items-center gap-2">
                <h2 class="text-2xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-500 via-indigo-500 to-pink-500 flex items-center gap-2 drop-shadow-lg">
                    <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <rect x="4" y="6" width="16" height="12" rx="2" stroke="currentColor"
                            stroke-width="2" fill="none"></rect>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h8"></path>
                    </svg>
                    {{ __('Job Advert List') }}
                </h2>
            </div>
            @can('create_job_advert')
                <!-- Create New Advert Button -->
                <button type="button" wire:click="createNewAdvert"
                    class="flex items-center gap-1 px-3 py-2 rounded-full sm:rounded-3xl h-12 sm:h-fit h border border-blue-200 dark:border-indigo-700 text-white/80 dark:text-zinc-900/80 bg-blue-600 hover:bg-blue-600/80 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span class="hidden sm:inline">{{ __('Add Advert') }}</span>
                </button>
            @endcan
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
                <button type="button" wire:click="$toggle('showFilters')"
                    class="flex items-center gap-1 px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 bg-white/80 dark:bg-zinc-900/80 text-blue-600 dark:text-indigo-300 hover:bg-blue-50/80 dark:hover:bg-zinc-800/80 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h8m-8 6h16"></path>
                    </svg>
                    <span class="hidden sm:inline">{{ __('Filters') }}</span>
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
        <div class="overflow-x-auto bg-transparent">
            <form wire:submit.prevent="bulkDelete">
                <table class="min-w-full divide-y divide-blue-200 dark:divide-indigo-700 text-sm border-separate border-spacing-y-4 border-spacing-x-0 table-auto">
                    <thead>
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold text-blue-500 dark:text-indigo-400 uppercase tracking-wider rounded-tl-2xl rounded-bl-2xl bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md">
                                <input type="checkbox" wire:model="selectAll" wire:click="toggleSelectAll" class="accent-pink-500 rounded focus:ring-2 focus:ring-pink-400" />
                            </th>
                            <th class="px-5 py-3 text-left font-semibold text-blue-500 dark:text-indigo-400 uppercase tracking-wider bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md">{{ __('Title') }}</th>
                            <th class="px-5 py-3 text-left font-semibold text-blue-500 dark:text-indigo-400 uppercase tracking-wider bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md">{{ __('Slug') }}</th>
                            <th class="px-5 py-3 text-left font-semibold text-blue-500 dark:text-indigo-400 uppercase tracking-wider bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md">{{ __('Deadline') }}</th>
                            <th class="px-5 py-3 text-left font-semibold text-blue-500 dark:text-indigo-400 uppercase tracking-wider bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md">{{ __('Status') }}</th>
                            <th class="px-5 py-3 text-left font-semibold text-blue-500 dark:text-indigo-400 uppercase tracking-wider rounded-tr-2xl rounded-br-2xl bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($isLoadingBulkDelete || $isLoadingDelete || $isLoadingEdit)
                            @for($i = 0; $i < $perPage; $i++)
                                <tr class="animate-pulse">
                                    <td class="px-5 py-4">
                                        <div class="h-4 w-4 bg-blue-100 dark:bg-zinc-800 rounded"></div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="h-4 w-32 bg-blue-100 dark:bg-zinc-800 rounded"></div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="h-4 w-24 bg-blue-100 dark:bg-zinc-800 rounded"></div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="h-4 w-20 bg-pink-100 dark:bg-zinc-800 rounded"></div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="h-4 w-16 bg-green-100 dark:bg-zinc-800 rounded-full"></div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="h-8 w-20 bg-gray-100 dark:bg-zinc-800 rounded-xl"></div>
                                    </td>
                                </tr>
                            @endfor
                        @else
                            @forelse ($jobAdverts as $advert)
                                <tr class="hover:bg-gradient-to-r hover:from-blue-100/60 hover:via-indigo-100/60 hover:to-pink-100/60 dark:hover:from-zinc-800/60 dark:hover:via-zinc-900/60 dark:hover:to-zinc-800/60 transition group backdrop-blur-md rounded-tl-2xl rounded-bl-2xl rounded-tr-2xl rounded-br-2xl">
                                    <td class="px-5 py-4">
                                        <input type="checkbox" wire:model="selected" value="{{ $advert->id }}" class="accent-pink-500 rounded focus:ring-2 focus:ring-pink-400" />
                                    </td>
                                    <td class="px-5 py-4 text-gray-900 dark:text-white font-bold">
                                        <span class="group-hover:underline">{{ $advert->title }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-indigo-600 dark:text-indigo-300 font-mono">
                                        {{ $advert->slug }}</td>
                                    <td class="px-5 py-4 text-pink-600 dark:text-pink-300 font-semibold">
                                        {{ $advert->deadline }}</td>
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
                                    <td>
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
                <div class="flex items-center justify-between mt-6">
                    <div>
                        @if (count($selected) > 0)
                            <button type="button" wire:click="bulkDeleteConfirm"
                                class="bg-gradient-to-r from-pink-500 to-red-500 hover:from-pink-600 hover:to-red-600 text-white px-5 py-3 rounded-xl font-semibold shadow-lg focus:outline-none focus:ring-2 focus:ring-red-400 backdrop-blur-sm">
                                {{ __('Delete Selected') }} ({{ count($selected) }})
                            </button>
                        @endif
                    </div>
                    <div class="flex gap-2 items-center">
                        <span class="text-sm text-indigo-500 dark:text-indigo-400 font-semibold">{{ __('Page') }}
                            {{ $page }} / {{ ceil($total / $perPage) }}</span>
                        <button type="button" wire:click="$set('page', max(1, $page-1))"
                            class="px-3 py-1 rounded-xl border border-blue-200 dark:border-indigo-700 text-blue-600 dark:text-indigo-300 bg-white/80 dark:bg-zinc-900/80 hover:bg-blue-50/80 dark:hover:bg-zinc-800/80 font-semibold shadow backdrop-blur-sm">&laquo;
                            {{ __('Prev') }}</button>
                        <button type="button" wire:click="$set('page', min(ceil($total / $perPage), $page+1))"
                            class="px-3 py-1 rounded-xl border border-blue-200 dark:border-indigo-700 text-blue-600 dark:text-indigo-300 bg-white/80 dark:bg-zinc-900/80 hover:bg-blue-50/80 dark:hover:bg-zinc-800/80 font-semibold shadow backdrop-blur-sm">{{ __('Next') }}
                            &raquo;</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

            <!-- Delete Confirmation Modal -->
            @if ($showDeleteModal)
                <div
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
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
                <div
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
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
                <div
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 backdrop-blur-2xl transition">
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
