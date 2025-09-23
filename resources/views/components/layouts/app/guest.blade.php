<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header
            class="sticky z-20 top-0 border-b transition-colors duration-300"
            x-data="{ 'scrolled': false }"
            @scroll.window="scrolled = (window.pageYOffset > 10)"
            x-bind:class="scrolled
                ? 'py-4 bg-white/60 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 shadow-md backdrop-blur-sm transition-shadow duration-300 ease-in-out'
                : 'py-4 bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 transition-shadow duration-300 ease-in-out'"
        >
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <a href="{{ route('home') }}" class="ms-2 me-5 flex items-center space-x-2 rtl:space-x-reverse lg:ms-0" wire:navigate>
                <x-app-logo />
            </a>
            
            <flux:spacer />

            <div class="-mb-px flex flex-row max-lg:hidden space-x-3">
                <ul>
                    <li>
                        <a href="https://www.cadebeckhrms.com" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold pr-4 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800" target="_blank" rel="noopener noreferrer">
                            <span class="flex items-center rounded-full font-black bg-zinc-200 dark:bg-zinc-700 p-2">
                                <flux:icon name="home" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                            </span>
                            <span>{{ __('Home') }}</span>
                        </a>
                    </li>
                </ul>


                <ul>
                    <li>
                        <a href="{{ route('home') }}" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold pr-4 {{ request()->routeIs('home') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}" wire:navigate>
                            <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('home') ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                <flux:icon name="briefcase" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                            </span>
                            <span>{{ __('Careers') }}</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <flux:spacer />

            <ul class="max-lg:hidden">
                <li>
                    <a href="/login" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold bg-blue-600 dark:bg-blue-700 text-white dark:text-zinc-200 pr-4" wire:navigate>
                        <span class="flex items-center rounded-full font-black bg-white dark:bg-zinc-900 p-2">
                            <flux:icon name="arrow-left-end-on-rectangle" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                        </span>
                        <span>{{ __('Login') }}</span>
                    </a>
                </li>
            </ul>
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar stashable sticky class="lg:hidden bg-zinc-50/20 dark:bg-zinc-900/20 w-full backdrop-blur-sm !p-0">
            <div class="border-e border-zinc-200 dark:border-zinc-700 w-fit h-screen p-4 bg-zinc-50 dark:bg-zinc-900 flex flex-col">
                <flux:sidebar.toggle class="lg:hidden left-[86%]" icon="x-mark" />

                <a href="{{ route('home') }}" class="ms-1 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                    <x-app-logo />
                </a>


                <div class="mt-4 space-y-4">
                    <ul>
                        <li>
                            <a href="https://www.cadebeckhrms.com" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold pr-4 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800" target="_blank" rel="noopener noreferrer">
                                <span class="flex items-center rounded-full font-black bg-zinc-200 dark:bg-zinc-700 p-2">
                                    <flux:icon name="home" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                </span>
                                <span>{{ __('Home') }}</span>
                            </a>
                        </li>
                    </ul>


                    <ul>
                        <li>
                            <a href="{{ route('home') }}" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold pr-4 {{ request()->routeIs('home') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}" wire:navigate>
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('home') ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                    <flux:icon name="briefcase" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                </span>
                                <span>{{ __('Careers') }}</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <flux:spacer />

            <ul class="">
                <li>
                    <a href="/login" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold bg-blue-600 dark:bg-blue-700 text-white dark:text-zinc-200 pr-4" wire:navigate>
                        <span class="flex items-center rounded-full font-black bg-white dark:bg-zinc-900 p-2">
                            <flux:icon name="arrow-left-end-on-rectangle" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                        </span>
                        <span>{{ __('Login') }}</span>
                    </a>
                </li>
            </ul>
            </div>
        </flux:sidebar>

        {{ $slot }}

    @livewire('notification.notification-manager')
    @fluxScripts
    </body>
</html>
