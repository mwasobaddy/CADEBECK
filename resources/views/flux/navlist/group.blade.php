@props([
    'expandable' => false,
    'expanded' => true,
    'heading' => null,
    'icon' => null,
    'iconVariant' => 'outline',
    'iconTrailing' => null,
])

<?php if ($expandable && $heading): ?>

<ui-disclosure
    {{ $attributes->class('group/disclosure') }}
    @if ($expanded === true) open @endif
    data-flux-navlist-group
>
    <button
        type="button"
        class="group/disclosure-button mb-[2px] flex h-10 w-full items-center rounded-lg text-zinc-500 hover:bg-zinc-800/5 hover:text-zinc-800 lg:h-8 dark:text-white/80 dark:hover:bg-white/[7%] dark:hover:text-white justify-between px-3"
    >
        <div class="flex gap-1">
            @if ($icon)
                <span class="me-2">
                    @if (is_string($icon) && $icon !== '')
                        <flux:icon :icon="$icon" :variant="$iconVariant" class="size-4!" />
                    @else
                        {{ $icon }}
                    @endif
                </span>
            @endif

            <span class="text-sm font-medium leading-none">{{ $heading }}</span>

            @if ($iconTrailing)
                <span class="ms-2">
                    @if (is_string($iconTrailing) && $iconTrailing !== '')
                        <flux:icon :icon="$iconTrailing" :variant="$iconVariant" class="size-4!" />
                    @else
                        {{ $iconTrailing }}
                    @endif
                </span>
            @endif
        </div>

        <div class="ps-3 pe-4">
            <flux:icon.chevron-down class="hidden size-3! group-data-open/disclosure-button:block" />
            <flux:icon.chevron-right class="block size-3! group-data-open/disclosure-button:hidden" />
        </div>
    </button>

    <div class="relative hidden space-y-[2px] ps-7 data-open:block" @if ($expanded === true) data-open @endif>
        <div class="absolute inset-y-[3px] start-0 ms-4 w-px bg-zinc-200 dark:bg-white/30"></div>

        {{ $slot }}
    </div>
</ui-disclosure>

<?php elseif ($heading): ?>

<div {{ $attributes->class('block space-y-[2px]') }}>
    <div class="px-1 py-2 flex items-center">
        @if ($icon)
            <span class="me-2">
                @if (is_string($icon) && $icon !== '')
                    <flux:icon :icon="$icon" :variant="$iconVariant" class="size-4!" />
                @else
                    {{ $icon }}
                @endif
            </span>
        @endif

        <div class="text-xs leading-none text-zinc-400">{{ $heading }}</div>

        @if ($iconTrailing)
            <span class="ms-2">
                @if (is_string($iconTrailing) && $iconTrailing !== '')
                    <flux:icon :icon="$iconTrailing" :variant="$iconVariant" class="size-4!" />
                @else
                    {{ $iconTrailing }}
                @endif
            </span>
        @endif
    </div>

    <div>
        {{ $slot }}
    </div>
</div>

<?php else: ?>

<div {{ $attributes->class('block space-y-[2px]') }}>
    {{ $slot }}
</div>

<?php endif; ?>
