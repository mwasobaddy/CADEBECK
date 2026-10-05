<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Services\ClientOverview;
use App\Services\ClientContext;

new #[Layout('components.layouts.app')] class extends Component {
    public string $search = '';

    /**
     * Cross-client totals. Read-only.
     */
    public function getTotalsProperty(): array
    {
        return app(ClientOverview::class)->totals();
    }

    /**
     * Client summaries, optionally filtered by name, plan or status.
     */
    public function getSummariesProperty()
    {
        $search = trim($this->search);

        return collect(app(ClientOverview::class)->clientSummaries())
            ->filter(function (array $row) use ($search) {
                if ($search === '') {
                    return true;
                }

                $client = $row['client'];

                return str_contains(strtolower($client->name), strtolower($search))
                    || ($client->plan && str_contains(strtolower($client->plan), strtolower($search)))
                    || $client->status === strtolower($search);
            })
            ->values();
    }

    /**
     * Send the operator to the create form.
     */
    public function createNewClient(): void
    {
        $this->redirectRoute('platform.clients.create', navigate: true);
    }

    /**
     * Open a client.
     */
    public function viewClient(int $id): void
    {
        $this->redirectRoute('platform.clients.show', ['client' => $id], navigate: true);
    }
};
?>

<div class="relative max-w-6xl mx-auto md:px-4 md:py-8">
    <!-- SVG Blobs Background -->
    <svg class="fixed -top-24 right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0" viewBox="0 0 400 400"
        fill="none">
        <ellipse cx="200" cy="200" rx="180" ry="120" fill="url(#clientsBlob1)" />
        <defs>
            <radialGradient id="clientsBlob1" cx="0" cy="0" r="1"
                gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                <stop stop-color="#38bdf8" />
                <stop offset="1" stop-color="#6366f1" />
            </radialGradient>
        </defs>
    </svg>
    <svg class="fixed -bottom-24 -right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0"
        viewBox="0 0 400 400" fill="none">
        <ellipse cx="200" cy="200" rx="160" ry="100" fill="url(#clientsBlob2)" />
        <defs>
            <radialGradient id="clientsBlob2" cx="0" cy="0" r="1"
                gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                <stop stop-color="#34d399" />
                <stop offset="1" stop-color="#f472b6" />
            </radialGradient>
        </defs>
    </svg>

    <!-- Summary tiles -->
    <div class="relative z-10 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        @foreach ([
            ['label' => __('Clients'), 'value' => $this->totals['clients'], 'icon' => 'building-office'],
            ['label' => __('Active'), 'value' => $this->totals['active_clients'], 'icon' => 'check'],
            ['label' => __('Users'), 'value' => $this->totals['users'], 'icon' => 'users'],
            ['label' => __('Employees'), 'value' => $this->totals['employees'], 'icon' => 'users'],
            ['label' => __('Payroll Runs'), 'value' => $this->totals['payroll_runs'], 'icon' => 'currency-dollar'],
            ['label' => __('Gross Pay'), 'value' => 'GBP ' . number_format($this->totals['gross_pay_total'], 2), 'icon' => 'currency-dollar'],
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
                <p class="text-lg font-extrabold text-zinc-900 dark:text-zinc-100 truncate">{{ $tile['value'] }}</p>
            </div>
        @endforeach
    </div>

    <!-- Clients table -->
    <div
        class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-3">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21">
                    </path>
                </svg>
                <div>
                    <h1
                        class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                        {{ __('Clients') }}
                        <span
                            class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                    </h1>
                    <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Platform overview. Read-only with respect to client HR data.') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @can('clients.create')
                    <flux:button variant="primary" type="button" wire:click="createNewClient"
                        class="flex flex-row items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        {{ __('Add Client') }}
                    </flux:button>
                @endcan
                <flux:input wire:model.live.debounce.300ms="search" size="sm"
                    :placeholder="__('Search by client, plan or status')"
                    class="!ps-4 pe-4 !py-2 !rounded-full border !border-blue-200 dark:!border-indigo-700 !bg-white/80 dark:!bg-zinc-900/80 !backdrop-blur-md dark:!text-white !shadow-sm focus:!ring-green-500" />
            </div>
        </div>

        <div class="overflow-x-auto bg-transparent mt-6">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700 text-sm">
                <thead>
                    <tr class="h-16 bg-zinc-800/5 dark:bg-white/10 text-zinc-600 dark:text-white/70">
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">
                            {{ __('Client') }}
                        </th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">
                            {{ __('Status') }}
                        </th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">
                            {{ __('Plan') }}
                        </th>
                        <th class="px-5 py-3 text-right font-semibold uppercase tracking-wider">
                            {{ __('Users') }}
                        </th>
                        <th class="px-5 py-3 text-right font-semibold uppercase tracking-wider">
                            {{ __('Employees') }}
                        </th>
                        <th class="px-5 py-3 text-right font-semibold uppercase tracking-wider">
                            {{ __('Locations') }}
                        </th>
                        <th class="px-5 py-3 text-right font-semibold uppercase tracking-wider">
                            {{ __('Payroll Runs') }}
                        </th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">
                            {{ __('Since') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($this->summaries as $row)
                        @php($client = $row['client'])
                        <tr class="hover:bg-zinc-500/5 dark:hover:bg-white/5 transition-colors">
                            <td class="px-5 py-3 font-semibold text-zinc-900 dark:text-zinc-100">
                                <button type="button" wire:click="viewClient({{ $client->id }})"
                                    class="text-left hover:underline text-green-700 dark:text-green-400">
                                    {{ $client->name }}
                                </button>
                                @if ($client->trashed())
                                    <span class="ml-2 text-xs text-red-600 dark:text-red-400">
                                        {{ __('deleted') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($client->status === 'active')
                                    <span
                                        class="bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 text-xs px-2 py-1 rounded-full font-semibold">
                                        {{ __('Active') }}
                                    </span>
                                @else
                                    <span
                                        class="bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 text-xs px-2 py-1 rounded-full font-semibold">
                                        {{ ucfirst($client->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-zinc-600 dark:text-zinc-300">
                                {{ $client->plan ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-right text-zinc-600 dark:text-zinc-300">
                                {{ $row['users'] }}
                            </td>
                            <td class="px-5 py-3 text-right text-zinc-600 dark:text-zinc-300">
                                {{ $row['employees'] }}
                            </td>
                            <td class="px-5 py-3 text-right text-zinc-600 dark:text-zinc-300">
                                {{ $row['locations'] }}
                            </td>
                            <td class="px-5 py-3 text-right text-zinc-600 dark:text-zinc-300">
                                {{ $row['payroll_runs'] }}
                            </td>
                            <td class="px-5 py-3 text-zinc-600 dark:text-zinc-300">
                                {{ $client->created_at?->format('j M Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                                {{ __('No clients found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>