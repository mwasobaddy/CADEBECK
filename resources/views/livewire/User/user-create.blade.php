<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

new #[Layout('components.layouts.app')] class extends Component {
    public array $form = [
        'first_name' => '',
        'other_names' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => '',
        'role' => '',
    ];
    public ?User $user = null;
    public bool $editing = false;
    public string $search = '';
    public string $roleFilter = '';
    public int $perPage = 10;

    public function mount($id = null): void
    {
        if ($id) {
            $this->user = User::findOrFail($id);
            $this->form = [
                'first_name' => $this->user->first_name,
                'other_names' => $this->user->other_names,
                'email' => $this->user->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => $this->user->roles->first()?->name ?? '',
            ];
            $this->editing = true;
        }
    }

    public function save(): void
    {
        $this->validate([
            'form.first_name' => ['required', 'string', 'max:255'],
            'form.other_names' => ['nullable', 'string', 'max:255'],
            'form.email' => ['required', 'email', 'unique:users,email' . ($this->editing && $this->user ? ',' . $this->user->id : '')],
            'form.password' => [$this->editing ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'form.password_confirmation' => [$this->editing ? 'nullable' : 'required', 'same:form.password'],
            'form.role' => ['required', 'exists:roles,name'],
        ]);

        if ($this->editing && $this->user) {
            $this->user->update([
                'first_name' => $this->form['first_name'],
                'other_names' => $this->form['other_names'],
                'email' => $this->form['email'],
                'password' => $this->form['password'] ? Hash::make($this->form['password']) : $this->user->password,
            ]);
            $this->user->syncRoles([$this->form['role']]);
            $this->dispatch('notify', ['type' => 'success', 'message' => __('User updated successfully.')]);
        } else {
            $user = User::create([
                'first_name' => $this->form['first_name'],
                'other_names' => $this->form['other_names'],
                'email' => $this->form['email'],
                'password' => Hash::make($this->form['password']),
            ]);
            $user->assignRole($this->form['role']);
            $this->dispatch('notify', ['type' => 'success', 'message' => __('User created successfully.')]);
        }
        $this->redirectRoute('user.manage');
    }

    public function resetForm(): void
    {
        if ($this->editing && $this->user) {
            $this->form = [
                'first_name' => $this->user->first_name,
                'other_names' => $this->user->other_names,
                'email' => $this->user->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => $this->user->roles->first()?->name ?? '',
            ];
        } else {
            $this->form = [
                'first_name' => '',
                'other_names' => '',
                'email' => '',
                'password' => '',
                'password_confirmation' => '',
                'role' => '',
            ];
        }
        $this->dispatch('notify', ['type' => 'info', 'message' => __('Form reset successfully.')]);
    }

    public function getUsersProperty()
    {
        $query = User::query();
        if ($this->search) {
            $query->where(function($q) {
                $q->where('first_name', 'like', "%{$this->search}%")
                  ->orWhere('other_names', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
        }
        if ($this->roleFilter) {
            $query->whereHas('roles', fn($q) => $q->where('name', $this->roleFilter));
        }
        return $query->with('roles')->paginate($this->perPage);
    }

    public function getRolesProperty()
    {
        return Role::all();
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
                <a href="{{ route('user.manage') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('user.manage') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none' : '' }}">
                    {{ __('User List') }}
                </a>
                <a href="{{ route('user.create') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('user.create') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none' : '' }}">
                    {{ $editing ? __('Edit User') : __('Create User') }}
                </a>
            </div>
        </nav>
    </div>

    @can('create_user')
        <!-- Card Container for Form -->
        <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
            <div class="flex items-center gap-3 mb-8">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"></path>
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                </svg>
                <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                    {{ $editing ? __('Edit User') : __('Create User') }}
                    <span class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                </h1>
            </div>
            <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="md:col-span-2">
                    <h2 class="text-lg font-bold text-green-700 mb-2">{{ __('Personal Information') }}</h2>
                </div>
                <div>
                    <flux:input
                        wire:model="form.first_name"
                        :label="__('First Name')"
                        type="text"
                        required
                        autocomplete="given-name"
                        placeholder="{{ __('First Name') }}"
                    />
                </div>
                <div>
                    <flux:input
                        wire:model="form.other_names"
                        :label="__('Other Names')"
                        type="text"
                        autocomplete="additional-name"
                        placeholder="{{ __('Other Names') }}"
                    />
                </div>

                <!-- Account Information -->
                <div class="md:col-span-2">
                    <h2 class="text-lg font-bold text-green-700 mb-2">{{ __('Account Information') }}</h2>
                </div>
                <div>
                    <flux:input
                        wire:model="form.email"
                        :label="__('Email')"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                </div>
                <div>
                    <flux:select
                        wire:model="form.role"
                        :placeholder="__('Select Role')"
                        required
                        :label="__('Role')"
                    >
                        @foreach($this->roles as $role)
                            <flux:select.option value="{{ $role->name }}">{{ __($role->name) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Security -->
                <div class="md:col-span-2">
                    <h2 class="text-lg font-bold text-green-700 mb-2">{{ __('Security') }}</h2>
                </div>
                <div>
                    <flux:input
                        wire:model="form.password"
                        :label="__('Password')"
                        type="password"
                        autocomplete="new-password"
                        :placeholder="__('Password')"
                        viewable
                    />
                </div>
                <div>
                    <flux:input
                        wire:model="form.password_confirmation"
                        :label="__('Confirm Password')"
                        type="password"
                        autocomplete="new-password"
                        :placeholder="__('Confirm Password')"
                        viewable
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