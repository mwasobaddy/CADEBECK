<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Services\ClientProvisioner;
use App\Services\ClientContext;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.app')] class extends Component {
    public array $form = [
        'name' => '',
        'contact_email' => '',
        'contact_phone' => '',
        'plan' => '',
        'max_users' => '',
        'timezone' => 'UTC',
        // first administrator for the new client
        'first_name' => '',
        'other_names' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => '',
    ];

    public ?string $createdSlug = null;

    public function save(): void
    {
        $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.contact_email' => ['nullable', 'email', 'max:255'],
            'form.contact_phone' => ['nullable', 'string', 'max:50'],
            'form.plan' => ['nullable', 'string', 'max:50'],
            'form.max_users' => ['nullable', 'integer', 'min:1'],
            'form.timezone' => ['nullable', 'string', 'max:64'],
            'form.first_name' => ['required', 'string', 'max:255'],
            'form.other_names' => ['nullable', 'string', 'max:255'],
            'form.email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'form.password' => ['required', 'string', 'min:8', 'confirmed'],
            'form.password_confirmation' => ['required', 'same:form.password'],
        ], [
            'form.email.unique' => __('That email address is already registered.'),
        ]);

        $client = app(ClientProvisioner::class)->provision(
            [
                'name' => $this->form['name'],
                'contact_email' => $this->form['contact_email'] ?: null,
                'contact_phone' => $this->form['contact_phone'] ?: null,
                'plan' => $this->form['plan'] ?: null,
                'max_users' => $this->form['max_users'] === '' ? null : $this->form['max_users'],
                'timezone' => $this->form['timezone'] ?: config('app.timezone', 'UTC'),
            ],
            [
                'first_name' => $this->form['first_name'],
                'other_names' => $this->form['other_names'] ?: null,
                'email' => $this->form['email'],
                'password' => $this->form['password'],
            ],
            Auth::id(),
        );

        $this->createdSlug = $client->slug;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Client created. Their administrator can now sign in.'),
        ]);

        $this->redirectRoute('platform.clients.show', ['client' => $client->id], navigate: true);
    }
};
?>

<div class="relative max-w-4xl mx-auto md:px-4 md:py-8">
    <!-- SVG Blobs Background -->
    <svg class="fixed -top-24 right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0" viewBox="0 0 400 400"
        fill="none">
        <ellipse cx="200" cy="200" rx="180" ry="120" fill="url(#clientCreateBlob1)" />
        <defs>
            <radialGradient id="clientCreateBlob1" cx="0" cy="0" r="1"
                gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                <stop stop-color="#38bdf8" />
                <stop offset="1" stop-color="#6366f1" />
            </radialGradient>
        </defs>
    </svg>

    <div
        class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex items-center gap-3 mb-8">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21">
                </path>
            </svg>
            <div>
                <h1
                    class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                    {{ __('Add Client') }}
                    <span
                        class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                </h1>
                <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('The client becomes usable immediately: their administrator can sign in straight away.') }}
                </p>
            </div>
        </div>

        <form wire:submit="save" class="space-y-6">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-4">
                    {{ __('Client details') }}
                </h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <flux:input wire:model="form.name" :label="__('Company name')" required
                            placeholder="Acme Ltd" />
                    </div>
                    <div>
                        <flux:input wire:model="form.contact_email" :label="__('Contact email')" type="email"
                            placeholder="hr@acme.com" />
                    </div>
                    <div>
                        <flux:input wire:model="form.contact_phone" :label="__('Contact phone')"
                            placeholder="+44 20 1234 5678" />
                    </div>
                    <div>
                        <flux:input wire:model="form.plan" :label="__('Plan')" placeholder="professional" />
                    </div>
                    <div>
                        <flux:input wire:model="form.max_users" :label="__('Seat cap')" type="number" min="1"
                            placeholder="Leave blank for unlimited" />
                    </div>
                    <div class="sm:col-span-2">
                        <flux:input wire:model="form.timezone" :label="__('Timezone')" placeholder="UTC" />
                    </div>
                </div>
            </div>

            <div class="border-t border-zinc-200 dark:border-zinc-800 pt-6">
                <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1">
                    {{ __('Client administrator') }}
                </h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">
                    {{ __('This person will be able to sign in and add everyone else at their company.') }}
                </p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <flux:input wire:model="form.first_name" :label="__('First name')" required />
                    </div>
                    <div>
                        <flux:input wire:model="form.other_names" :label="__('Other names')" />
                    </div>
                    <div class="sm:col-span-2">
                        <flux:input wire:model="form.email" :label="__('Login email')" type="email" required
                            placeholder="admin@acme.com" />
                    </div>
                    <div>
                        <flux:input wire:model="form.password" :label="__('Initial password')" type="password"
                            required />
                    </div>
                    <div>
                        <flux:input wire:model="form.password_confirmation" :label="__('Confirm password')"
                            type="password" required />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="filled" type="button" wire:click="createNewClient"
                    class="!rounded-full font-semibold">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="submit"
                    class="flex flex-row items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    {{ __('Create Client') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>