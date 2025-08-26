<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public bool $showFilters = false;
    public string $search = '';
    public array $selected = [];
    public bool $selectAll = false;
    public int $perPage = 10;
    public bool $showDeleteModal = false;
    public $pendingDeleteId = null;
    public bool $showEditModal = false;
    public $pendingEditId = null;
    public bool $isLoadingExport = false;
    public bool $isLoadingDelete = false;
    public bool $isLoadingEdit = false;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

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

    public function updatedSearch(): void
    {
        $this->resetPage();
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

    public function confirmDelete($id): void
    {
        $this->pendingDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function editConfirmed(): void
    {
        $this->showEditModal = false;
        $this->pendingEditId = null;
    }

    public function deleteConfirmed(): void
    {
        $this->selected = array_values(array_diff($this->selected, [$this->pendingDeleteId]));
        $this->showDeleteModal = false;
        $this->pendingDeleteId = null;
    }
};
?>

<div class="relative max-w-6xl mx-auto md:px-4 md:py-8">
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 z-10 relative border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <nav class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="#" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 bg-green-600 text-white">
                    Designation List
                </a>
                <a href="#" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    Create Designation
                </a>
            </div>
        </nav>
    </div>

    <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
            <div class="flex items-center gap-3 mb-8">
                <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight">
                    Designation List
                </h1>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" class="flex items-center gap-2 px-4 py-2 rounded-full border border-blue-200 text-blue-600 bg-blue-50/80">
                    Export
                </button>
                <button type="button" class="flex items-center gap-2 px-4 py-2 rounded-full border border-blue-200 text-blue-600 bg-blue-50/80">
                    Create
                </button>
            </div>
        </div>

        <div class="flex flex-wrap gap-8 items-center">
            <div class="relative w-80">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search designations..." class="w-full px-4 py-3 rounded-2xl border" />
            </div>

            <button type="button" wire:click="toggleFilters" class="px-3 py-2 rounded-3xl border bg-white/80 text-blue-600">
                Filters
            </button>
        </div>

        <div class="overflow-x-auto bg-transparent mt-6">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="h-16 bg-zinc-800/5 text-zinc-600">
                        <th class="pl-6">#</th>
                        <th class="text-left">Title</th>
                        <th class="text-left">Level</th>
                        <th class="text-left">Created At</th>
                        <th class="text-right pr-6">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="p-6 text-center text-zinc-500">No designations found.</td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-6">
                <!-- Pagination placeholder -->
            </div>
        </div>
    </div>

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl p-8 max-w-md w-full">
                <h3 class="text-xl font-bold mb-4 text-red-600">Confirm Delete</h3>
                <p class="mb-6 text-zinc-700">Are you sure you want to delete this designation? This action cannot be undone.</p>
                <div class="flex justify-end gap-3">
                    <button wire:click="deleteConfirmed" class="px-4 py-2 bg-red-600 text-white rounded-xl">Delete</button>
                    <button wire:click="$set('showDeleteModal', false)" class="px-4 py-2 bg-gray-200 rounded-xl">Cancel</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl p-8 max-w-md w-full">
                <h3 class="text-xl font-bold mb-4 text-blue-600">Confirm Edit</h3>
                <p class="mb-6 text-zinc-700">Are you sure you want to edit this designation?</p>
                <div class="flex justify-end gap-3">
                    <button wire:click="editConfirmed" class="px-4 py-2 bg-blue-600 text-white rounded-xl">Edit</button>
                    <button wire:click="$set('showEditModal', false)" class="px-4 py-2 bg-gray-200 rounded-xl">Cancel</button>
                </div>
            </div>
        </div>
    @endif
</div>
