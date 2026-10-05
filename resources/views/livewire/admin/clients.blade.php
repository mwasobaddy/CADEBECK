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
     * Whether the viewer may change client state.
     *
     * A System Admin can list and report on clients but must never edit client
     * HR data, so the destructive controls stay hidden behind their own
     * permissions.
     */
    public function getCanEditClientsProperty(): bool
    {
        return auth()->user()?->can('clients.edit') ?? false;
    }
};
?>

<div class="relative max-w-7xl mx-auto md:px-4 md:py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Clients') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                {{ __('Platform overview. This view is read-only with respect to client HR data.') }}
            </p>
        </div>
    </div>

    {{-- Aggregate totals across every client --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        @foreach ([
            ['label' => __('Clients'), 'value' => $this->totals['clients']],
            ['label' => __('Active'), 'value' => $this->totals['active_clients']],
            ['label' => __('Users'), 'value' => $this->totals['users']],
            ['label' => __('Employees'), 'value' => $this->totals['employees']],
            ['label' => __('Payroll Runs'), 'value' => $this->totals['payroll_runs']],
            ['label' => __('Gross Pay'), 'value' => 'GBP '.number_format($this->totals['gross_pay_total'], 2)],
        ] as $card)
            <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
                <p class="text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ $card['label'] }}</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Search --}}
    <div class="mb-4">
        <input type="search" wire:model.live="search"
               placeholder="{{ __('Search by client, plan or status') }}"
               class="w-full max-w-sm px-4 py-2 rounded-xl border border-zinc-200 dark:border-zinc-700
                      bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white" />
    </div>

    {{-- Client table --}}
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700 text-sm">
            <thead>
                <tr class="h-14 bg-zinc-800/5 dark:bg-white/10 text-zinc-600 dark:text-white/70">
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">{{ __('Client') }}</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">{{ __('Status') }}</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">{{ __('Plan') }}</th>
                    <th class="px-5 py-3 text-right font-semibold uppercase tracking-wider">{{ __('Users') }}</th>
                    <th class="px-5 py-3 text-right font-semibold uppercase tracking-wider">{{ __('Employees') }}</th>
                    <th class="px-5 py-3 text-right font-semibold uppercase tracking-wider">{{ __('Locations') }}</th>
                    <th class="px-5 py-3 text-right font-semibold uppercase tracking-wider">{{ __('Payroll Runs') }}</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">{{ __('Since') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($this->summaries as $row)
                    @php($client = $row['client'])
                    <tr class="hover:bg-zinc-500/5 dark:hover:bg-white/5">
                        <td class="px-5 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $client->name }}
                            @if ($client->trashed())
                                <span class="ml-2 text-xs text-red-600">({{ __('deleted') }})</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <span @class([
                                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold',
                                'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' => $client->status === 'active',
                                'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' => $client->status !== 'active',
                            ])>{{ __($client->status) }}</span>
                        </td>
                        <td class="px-5 py-3 text-zinc-600 dark:text-zinc-300">{{ $client->plan ?? '—' }}</td>
                        <td class="px-5 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ $row['users'] }}</td>
                        <td class="px-5 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ $row['employees'] }}</td>
                        <td class="px-5 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ $row['locations'] }}</td>
                        <td class="px-5 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ $row['payroll_runs'] }}</td>
                        <td class="px-5 py-3 text-zinc-600 dark:text-zinc-300">{{ $client->created_at?->format('j M Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('No clients found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>