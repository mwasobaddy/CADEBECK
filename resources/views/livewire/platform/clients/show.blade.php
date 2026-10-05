<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Payroll;
use App\Models\User;
use App\Services\ClientOverview;
use App\Services\ClientContext;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.app')] class extends Component {
    /**
     * The client being viewed.
     *
     * Left untyped because Livewire assigns the raw route parameter first; the
     * model is resolved in mount().
     *
     * @var Client
     */
    public $client;

    public array $form = [
        'name' => '',
        'contact_email' => '',
        'contact_phone' => '',
        'plan' => '',
        'max_users' => '',
        'timezone' => 'UTC',
    ];

    /**
     * Headline numbers for this client only.
     */
    public array $stats = [];

    public function mount($client): void
    {
        $this->client = Client::findOrFail($client);

        $this->form = [
            'name' => $this->client->name,
            'contact_email' => $this->client->contact_email ?? '',
            'contact_phone' => $this->client->contact_phone ?? '',
            'plan' => $this->client->plan ?? '',
            'max_users' => $this->client->max_users ?? '',
            'timezone' => $this->client->timezone ?? 'UTC',
        ];

        $this->stats = [
            'users' => User::forClient($this->client->id)->count(),
            'employees' => Employee::forClient($this->client->id)->count(),
            'locations' => Location::forClient($this->client->id)->count(),
            'payroll_runs' => Payroll::forClient($this->client->id)->count(),
        ];
    }

    public function save(): void
    {
        abort_unless(Auth::user()?->can('clients.edit'), 403);

        $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.contact_email' => ['nullable', 'email', 'max:255'],
            'form.contact_phone' => ['nullable', 'string', 'max:50'],
            'form.plan' => ['nullable', 'string', 'max:50'],
            'form.max_users' => ['nullable', 'integer', 'min:1'],
            'form.timezone' => ['nullable', 'string', 'max:64'],
        ]);

        $this->client->update([
            'name' => $this->form['name'],
            'contact_email' => $this->form['contact_email'] ?: null,
            'contact_phone' => $this->form['contact_phone'] ?: null,
            'plan' => $this->form['plan'] ?: null,
            'max_users' => $this->form['max_users'] === '' ? null : $this->form['max_users'],
            'timezone' => $this->form['timezone'] ?: 'UTC',
        ]);

        $this->dispatch('notify', ['type' => 'success', 'message' => __('Client updated successfully.')]);
    }

    /**
     * Suspend or reactivate a client.
     */
    public function toggleStatus(): void
    {
        abort_unless(Auth::user()?->can('clients.suspend'), 403);

        $this->client->update([
            'status' => $this->client->status === 'active' ? 'suspended' : 'active',
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $this->client->status === 'active'
                ? __('Client reactivated.')
                : __('Client suspended.'),
        ]);
    }

    public function backToList(): void
    {
        $this->redirectRoute('platform.clients.index', navigate: true);
    }
};
?>

<div class="relative max-w-4xl mx-auto md:px-4 md:py-8">
    <!-- SVG Blobs Background -->
    <svg class="fixed -top-24 right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0" viewBox="0 0 400 400"
        fill="none">
        <ellipse cx="200" cy="200" rx="180" ry="120" fill="url(#clientShowBlob1)" />
        <defs>
            <radialGradient id="clientShowBlob1" cx="0" cy="0" r="1"
                gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                <stop stop-color="#38bdf8" />
                <stop offset="1" stop-color="#6366f1" />
            </radialGradient>
        </defs>
    </svg>

    <!-- Header -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 z-10 relative border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <nav class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button type="button" wire:click="backToList"
                    class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-200"
                    wire:navigate>
                    {{ __('All Clients') }}
                </button>
                <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                    {{ $this->client->name }}
                </span>
            </div>
            <div class="flex items-center gap-3">
                @can('clients.suspend')
                    <flux:button variant="filled" type="button" wire:click="toggleStatus"
                        wire:confirm="{{ $this->client->status === 'active' ? __('Suspend this client? They will no longer be able to sign in to work.') : __('Reactivate this client?') }}"
                        class="flex flex-row items-center gap-2 px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200
                               {{ $this->client->status === 'active' ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-green-600 hover:bg-green-700 text-white' }}">
                        {{ $this->client->status === 'active' ? __('Suspend') : __('Reactivate') }}
                    </flux:button>
                @endcan
            </div>
        </nav>
    </div>

    <!-- Stats -->
    <div class="relative z-10 grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @foreach ([
            ['label' => __('Users'), 'value' => $this->stats['users'], 'icon' => 'users'],
            ['label' => __('Employees'), 'value' => $this->stats['employees'], 'icon' => 'users'],
            ['label' => __('Locations'), 'value' => $this->stats['locations'], 'icon' => 'home'],
            ['label' => __('Payroll Runs'), 'value' => $this->stats['payroll_runs'], 'icon' => 'currency-dollar'],
        ] as $tile)
            <div
                class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-lg p-4 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
                <div class="flex items-center gap-2 mb-1">
                    <flux:icon :name="$tile['icon']" variant="solid"
                        class="w-4 h-4 text-green-600 dark:text-green-500" />
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ $tile['label'] }}
                    </span>
                </div>
                <p class="text-lg font-extrabold text-zinc-900 dark:text-zinc-100">{{ $tile['value'] }}</p>
            </div>
        @endforeach
    </div>

    <!-- Details -->
    <div
        class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-3">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21">
                    </path>
                </svg>
                <div>
                    <h1
                        class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                        {{ $this->client->name }}
                        <span
                            class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                    </h1>
                    <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                        <span class="font-mono">{{ $this->client->slug }}</span>
                        &middot;
                        @if ($this->client->status === 'active')
                            <span class="text-green-600 dark:text-green-400 font-semibold">{{ __('Active') }}</span>
                        @else
                            <span class="text-amber-600 dark:text-amber-400 font-semibold">
                                {{ ucfirst($this->client->status) }}
                            </span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        @can('clients.edit')
            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <flux:input wire:model="form.name" :label="__('Company name')" required />
                    </div>
                    <div>
                        <flux:input wire:model="form.contact_email" :label="__('Contact email')" type="email" />
                    </div>
                    <div>
                        <flux:input wire:model="form.contact_phone" :label="__('Contact phone')" />
                    </div>
                    <div>
                        <flux:input wire:model="form.plan" :label="__('Plan')" />
                    </div>
                    <div>
                        <flux:input wire:model="form.max_users" :label="__('Seat cap')" type="number" min="1" />
                    </div>
                    <div class="sm:col-span-2">
                        <flux:input wire:model="form.timezone" :label="__('Timezone')" />
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <flux:button variant="primary" type="submit"
                        class="flex flex-row items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        {{ __('Save Changes') }}
                    </flux:button>
                </div>
            </form>
        @else
            <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Contact email') }}
                    </dt>
                    <dd class="mt-1 text-zinc-900 dark:text-zinc-100">
                        {{ $this->client->contact_email ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Contact phone') }}
                    </dt>
                    <dd class="mt-1 text-zinc-900 dark:text-zinc-100">
                        {{ $this->client->contact_phone ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Plan') }}
                    </dt>
                    <dd class="mt-1 text-zinc-900 dark:text-zinc-100">{{ $this->client->plan ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Seat cap') }}
                    </dt>
                    <dd class="mt-1 text-zinc-900 dark:text-zinc-100">{{ $this->client->max_users ?? '—' }}</dd>
                </div>
            </dl>
        @endcan
    </div>
</div>