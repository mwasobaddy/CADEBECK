<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Designation;

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
    public bool $isLoadingBulkDelete = false;
    public bool $isLoadingData = false;
    public bool $showBulkDeleteModal = false;

    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    public function getDesignationsProperty()
    {
        $query = Designation::query();
        if ($this->search) {
            $query->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('code', 'like', '%'.$this->search.'%');
        }
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        $field = in_array($this->sortField, ['name', 'code', 'created_at']) ? $this->sortField : 'created_at';
        $query->orderBy($field, $direction);
        return $query->paginate($this->perPage);
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

    public function updatedSearch(): void
    {
        $this->isLoadingData = true;
        $this->resetPage();
        $this->isLoadingData = false;
    }

    public function updatedPage(): void
    {
        $this->isLoadingData = true;
        $this->isLoadingData = false;
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
    }

    public function updateSelectAllState(): void
    {
        $designations = $this->designations;
        if ($designations && $designations->count() > 0) {
            $currentPageIds = $designations->pluck('id')->toArray();
            $this->selectAll = count($currentPageIds) > 0 && 
                              count(array_intersect($this->selected, $currentPageIds)) === count($currentPageIds);
        } else {
            $this->selectAll = false;
        }
    }

    public function toggleSelectAll(): void
    {
        $designations = $this->designations;
        if ($designations && $designations->count() > 0) {
            $currentPageIds = $designations->pluck('id')->toArray();
            if ($this->selectAll) {
                $this->selectAll = false;
                $this->selected = array_values(array_diff($this->selected, $currentPageIds));
            } else {
                $this->selected = array_values(array_unique(array_merge($this->selected, $currentPageIds)));
                $this->selectAll = true;
            }
        }
        $this->updateSelectAllState();
    }

    public function updatedSelected(): void
    {
        $this->updateSelectAllState();
    }

    public function selectAllData(): void
    {
        $query = Designation::query();
        if ($this->search) {
            $query->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('code', 'like', '%'.$this->search.'%');
        }
        $this->selected = $query->pluck('id')->toArray();
        $this->updateSelectAllState();
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
        // Redirect to edit route if needed
        // $this->redirectRoute('designation.edit', ['id' => $id]);
    }

    public function deleteConfirmed(): void
    {
        $this->isLoadingDelete = true;
        if ($this->pendingDeleteId) {
            Designation::where('id', $this->pendingDeleteId)->delete();
            $this->selected = array_values(array_diff($this->selected, [$this->pendingDeleteId]));
        }
        $this->showDeleteModal = false;
        $this->pendingDeleteId = null;
        $this->isLoadingDelete = false;
        $this->updateSelectAllState();
    }

    public function bulkDeleteConfirm(): void
    {
        $this->showBulkDeleteModal = true;
    }

    public function bulkDelete(): void
    {
        $this->isLoadingBulkDelete = true;
        Designation::whereIn('id', $this->selected)->delete();
        $this->showBulkDeleteModal = false;
        $this->isLoadingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;
        $this->updateSelectAllState();
    }

    public function exportSelected(): void
    {
        $this->isLoadingExport = true;
        $designations = Designation::whereIn('id', $this->selected)->get();
        $csvData = "ID,Name,Code,Description,Created At\n";
        foreach ($designations as $d) {
            $csvData .= '"' . $d->id . '","' . str_replace('"', '""', $d->name) . '","' . str_replace('"', '""', $d->code) . '","' . str_replace('"', '""', $d->description) . '","' . $d->created_at . '"\n';
        }
        $this->isLoadingExport = false;
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'designations_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
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
                <button type="button" wire:click="exportSelected" class="flex items-center gap-2 px-4 py-2 rounded-full border border-blue-200 text-blue-600 bg-blue-50/80">
                    Export
                </button>
                <a href="{{ route('designation.create') }}" class="flex items-center gap-2 px-4 py-2 rounded-full border border-blue-200 text-blue-600 bg-blue-50/80">
                    Create
                </a>
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
                        <th class="pl-6"><input type="checkbox" wire:model="selectAll" wire:click="toggleSelectAll" /></th>
                        <th class="text-left">Name</th>
                        <th class="text-left">Code</th>
                        <th class="text-left">Description</th>
                        <th class="text-left">Created At</th>
                        <th class="text-right pr-6">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->designations as $designation)
                        <tr>
                            <td class="pl-6">
                                <input type="checkbox" value="{{ $designation->id }}" wire:model="selected" />
                            </td>
                            <td>{{ $designation->name }}</td>
                            <td>{{ $designation->code }}</td>
                            <td>{{ $designation->description }}</td>
                            <td>{{ $designation->created_at->format('Y-m-d') }}</td>
                            <td class="text-right pr-6">
                                <button wire:click="confirmEdit({{ $designation->id }})" class="px-2 py-1 text-blue-600">Edit</button>
                                <button wire:click="confirmDelete({{ $designation->id }})" class="px-2 py-1 text-red-600">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-zinc-500">No designations found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-6">
                {{ $this->designations->links() }}
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

    @if ($showBulkDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-gradient-to-br from-pink-50/80 via-white/80 to-red-100/80 dark:from-zinc-900/80 dark:via-zinc-800/80 dark:to-zinc-900/80 backdrop-blur-xl rounded-xl shadow-2xl p-10 max-w-md w-full">
                <h3 class="text-2xl font-extrabold text-pink-600 dark:text-pink-400 flex items-center gap-2 mb-4">Confirm Bulk Delete</h3>
                <p class="mb-6 text-zinc-700 dark:text-zinc-300 font-semibold">Are you sure you want to delete the selected designations? This action cannot be undone.</p>
                <div class="flex justify-end gap-3">
                    <button wire:click="bulkDelete" class="px-4 py-2 bg-red-600 text-white rounded-xl">Delete</button>
                    <button wire:click="$set('showBulkDeleteModal', false)" class="px-4 py-2 bg-gray-200 rounded-xl">Cancel</button>
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

