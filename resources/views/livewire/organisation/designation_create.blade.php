<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Designation;

new #[Layout('components.layouts.app')] class extends Component {
    public array $form = [
        'name' => '',
        'code' => '',
        'description' => '',
    ];
    public bool $editing = false;
    public $entity_id = null;

    public function mount($id = null): void
    {
        if ($id) {
            $this->entity_id = $id;
            $this->editing = true;
            $designation = Designation::find($id);
            if ($designation) {
                $this->form = [
                    'name' => $designation->name,
                    'code' => $designation->code,
                    'description' => $designation->description,
                ];
            }
        }
    }

    public function save(): void
    {
        $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.code' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string'],
        ]);

        if ($this->editing && $this->entity_id) {
            $designation = Designation::find($this->entity_id);
            if ($designation) {
                $designation->update($this->form);
                $this->dispatch('notify', ['type' => 'success', 'message' => __('Designation updated successfully.')]);
            }
        } else {
            Designation::create($this->form);
            $this->dispatch('notify', ['type' => 'success', 'message' => __('Designation created successfully.')]);
        }
        $this->redirectRoute('designation.manage');
    }

    public function resetForm(): void
    {
        if ($this->editing && $this->entity_id) {
            $designation = Designation::find($this->entity_id);
            if ($designation) {
                $this->form = [
                    'name' => $designation->name,
                    'code' => $designation->code,
                    'description' => $designation->description,
                ];
            }
        } else {
            $this->form = [
                'name' => '',
                'code' => '',
                'description' => '',
            ];
        }

        $this->dispatch('notify', ['type' => 'info', 'message' => __('Form reset successfully.')]);
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
                <a href="{{ route('designation.manage') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('designation.manage') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none' : '' }}">
                    {{ __('Designation List') }}
                </a>
                <a href="{{ route('designation.create') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('designation.create') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none' : '' }}">
                    {{ $editing ? __('Edit Designation') : __('Create Designation') }}
                </a>
            </div>
        </nav>
    </div>

    <!-- Card Container for Form -->
    <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex items-center gap-3 mb-8">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"></path>
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
            </svg>
            <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                {{ $editing ? __('Edit Designation') : __('Create Designation') }}
                <span class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
            </h1>
        </div>
        <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <flux:input
                    wire:model="form.name"
                    :label="__('Designation Name')"
                    type="text"
                    required
                    autofocus
                    autocomplete="off"
                    :placeholder="__('Enter designation name')"
                />
            </div>
            <div>
                <flux:input
                    wire:model="form.code"
                    :label="__('Designation Code')"
                    type="text"
                    required
                    autocomplete="off"
                    :placeholder="__('Enter designation code')"
                />
            </div>
            <div class="md:col-span-2">
                <flux:textarea
                    wire:model="form.description"
                    :label="__('Description')"
                    :placeholder="__('Enter description')"
                    rows="5"
                />
            </div>
            <div class="flex items-end justify-end gap-3 md:col-span-2">
                <button type="submit"
                    class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500">
                    {{ $editing ? __('Update') : __('Create') }}
                    <flux:icon name="check" class="w-5 h-5" />
                </button>
                <button type="button" wire:click="resetForm"
                    class="flex items-center gap-2 bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-200 px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                    {{ __('Reset') }}
                    <flux:icon name="arrow-path-rounded-square" class="w-5 h-5" />
                </button>
            </div>
        </form>
    </div>
</div>
