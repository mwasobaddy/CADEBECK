@props([
    'label' => __('Export'),
    'hasSelected' => false,
    'isLoading' => false,
    'showSelected' => true,
])

<div class="relative inline-block" x-data="{ open: false }" @click.outside="open = false">
    <button type="button"
        @click="open = !open"
        x-bind:disabled="{{ $isLoading ? 'true' : 'false' }}"
        class="flex items-center gap-2 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-400 text-white px-4 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm">
        @if ($isLoading)
            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
        @else
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        @endif
        {{ $isLoading ? __('Exporting...') : $label }}
        <svg class="w-4 h-4" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="absolute right-0 mt-2 w-44 bg-white dark:bg-zinc-900 rounded-xl shadow-xl border border-purple-100 dark:border-zinc-700 ring-1 ring-purple-200/30 dark:ring-zinc-600/40 z-50 overflow-hidden"
        style="display: none;">

        <div class="py-1">
            @if ($showSelected && $hasSelected)
                <button type="button" wire:click="exportCsv('selected')" @click="open = false"
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-left hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ __('CSV (Selected)') }}
                </button>
            @endif
            <button type="button" wire:click="exportCsv('all')" @click="open = false"
                class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-left hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                {{ __('CSV') }}
            </button>
            <div class="border-t border-purple-100 dark:border-zinc-700 my-1"></div>
            @if ($showSelected && $hasSelected)
                <button type="button" wire:click="exportPdf('selected')" @click="open = false"
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-left hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    {{ __('PDF (Selected)') }}
                </button>
            @endif
            <button type="button" wire:click="exportPdf('all')" @click="open = false"
                class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-left hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors">
                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                {{ __('PDF') }}
            </button>
        </div>
    </div>
</div>
