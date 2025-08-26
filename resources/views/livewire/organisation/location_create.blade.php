<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public array $form = [
        'name' => '',
        'description' => '',
    ];
    public bool $editing = false;
    public $entity_id = null;

    public function mount($id = null): void
    {
        if ($id) {
            // placeholder: load model by id when available
            $this->entity_id = $id;
            $this->editing = true;
        }
    }

    public function save(): void
    {
        $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string'],
        ]);

        // Implement actual save/update logic with models later
        session()->flash('status', $this->editing ? 'Location updated.' : 'Location created.');
        $this->redirectRoute('organisation.location_manager');
    }
};
?>

<div class="relative max-w-6xl mx-auto md:px-4 md:py-8">
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8">
        <nav class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="#" class="border rounded-full py-2 px-4">Location List</a>
                <a href="#" class="border rounded-full py-2 px-4 bg-green-600 text-white">Create Location</a>
            </div>
        </nav>
    </div>

    <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8">
        <div class="flex items-center gap-3 mb-8">
            <h1 class="text-3xl font-extrabold">
                {{ $editing ? 'Edit Location' : 'Create Location' }}
            </h1>
        </div>

        <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <label class="block mb-2 text-sm font-semibold">Name</label>
                <input type="text" wire:model="form.name" class="w-full px-4 py-3 rounded-2xl border" required />
            </div>
            <div class="md:col-span-2">
                <label class="block mb-2 text-sm font-semibold">Description</label>
                <textarea wire:model="form.description" class="w-full px-4 py-3 rounded-2xl border min-h-[120px]"></textarea>
            </div>
            <div class="flex items-end justify-end gap-3 md:col-span-2">
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-xl">{{ $editing ? 'Update' : 'Create' }}</button>
                <button type="button" wire:click="resetForm" class="px-6 py-2 bg-gray-200 rounded-xl">Reset</button>
            </div>
        </form>
    </div>
</div>
