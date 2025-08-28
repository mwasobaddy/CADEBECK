<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

new #[Layout('components.layouts.app')] class extends Component {
    public array $form = [
        'name' => '',
        'permissions' => [],
    ];
    public ?Role $role = null;
    public bool $editing = false;

    public function mount($id = null): void
    {
        if ($id) {
            $this->role = Role::with('permissions')->findOrFail($id);
            $this->form['name'] = $this->role->name;
            $this->form['permissions'] = $this->role->permissions->pluck('name')->toArray();
            $this->editing = true;
        }
    }

    public function save(): void
    {
        $rules = [
            'form.name' => ['required', 'string', 'max:50', $this->editing ? 'unique:roles,name,' . ($this->role?->id ?? 'NULL') : 'unique:roles,name'],
            'form.permissions' => ['array'],
        ];
        $this->validate($rules);

        if ($this->editing && $this->role) {
            $this->role->name = $this->form['name'];
            $this->role->save();
            $this->role->syncPermissions($this->form['permissions']);
            session()->flash('status', __('Role updated successfully.'));
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Role updated successfully.']);
        } else {
            $role = Role::create(['name' => $this->form['name']]);
            $role->syncPermissions($this->form['permissions']);
            session()->flash('status', __('Role created successfully.'));
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Role created successfully.']);
        }
        $this->redirectRoute('role.manage');
    }

    public function getPermissionsProperty() { return Permission::all(); }
};
?>

<div class="relative max-w-6xl mx-auto md:px-4 md:py-8">
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 z-10 relative border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <nav class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('role.manage') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('role.manage') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none' : '' }}">
                    {{ __('Role List') }}
                </a>
                <a href="{{ route('role.create') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('role.create') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none' : '' }}">
                    {{ __('Add Role') }}
                </a>
            </div>
        </nav>
    </div>

    @can('create_role')
        <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
            <div class="flex items-center gap-3 mb-8">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"></path>
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                </svg>
                <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                    {{ __('Add Role') }}
                    <span class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                </h1>
            </div>
            <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="md:col-span-2">
                    <flux:input
                        wire:model="form.name"
                        :label="__('Role Name')"
                        type="text"
                        required
                        placeholder="{{ __('Role Name') }}" />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Permissions') }}</label>
                    <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($this->permissions as $permission)
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="form.permissions" value="{{ $permission->name }}" class="rounded text-green-600 focus:ring-green-500" />
                                <span class="text-gray-700 dark:text-gray-300">{{ $permission->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-end justify-end gap-3 md:col-span-2">
                    <button type="submit"
                        class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500">
                        {{ __('Create') }}
                        <flux:icon name="check" class="w-5 h-5" />
                    </button>
                </div>
            </form>
        </div>
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
