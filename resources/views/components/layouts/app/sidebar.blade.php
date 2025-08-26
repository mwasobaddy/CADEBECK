<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    <body class="min-h-screen bg-white dark:bg-zinc-800">

        <!-- Sidebar -->
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />
            <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>
            <nav class="flex-1 px-4 py-6 overflow-y-auto text-sm">
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full {{ request()->routeIs('dashboard') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                            <span class="flex items-center rounded-full font-black p-2 {{ request()->routeIs('dashboard') ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                <flux:icon name="home" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                            </span>
                            <span>{{ __('Dashboard') }}</span>
                        </a>
                    </li>
                    <li x-data="{ open: {{ (request()->routeIs('employee.create') || request()->routeIs('employee.manage') || request()->routeIs('employee.edit')) ? 'true' : 'false' }} }">
                        <div class="flex flex-col">
                            <button type="button"
                                @click="open = !open"
                                class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                {{ request()->routeIs('employee.create') || request()->routeIs('employee.manage') || request()->routeIs('employee.edit')
                                    ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200'
                                    : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                    {{ request()->routeIs('employee.create') || request()->routeIs('employee.manage') || request()->routeIs('employee.edit')
                                        ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                    <flux:icon name="users" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                </span>
                                <span>{{ __('Employee') }}</span>
                                <svg :class="{ 'rotate-180': open }" class="w-4 h-4 ml-auto text-zinc-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <ul x-show="open" x-transition class="pl-8 mt-2">
                                @can('manage_employee')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('employee.manage') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('employee.manage') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        <a href="{{ route('employee.manage') }}">
                                            {{ __('Employee List') }}
                                        </a>
                                    </div>
                                </li>
                                @endcan
                                @can('create_employee')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('employee.create') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('employee.create') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        <a href="{{ route('employee.create') }}">
                                            {{ __('Create Employee') }}
                                        </a>
                                    </div>
                                </li>
                                @endcan
                                @can('edit_employee')
                                <li>
                                    @if(!empty($slug))
                                    <a href="{{ route('employee.edit', ['id' => $slug]) }}"
                                    class="block px-2 py-1 rounded {{ request()->routeIs('employee.edit') ? 'text-green-700 bg-green-100 font-semibold' : 'text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                        {{ __('Edit Employee') }}
                                    </a>
                                    @endif
                                </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                    <li x-data="{ open: {{ (request()->routeIs('user.create') || request()->routeIs('user.manage') || request()->routeIs('user.edit')) ? 'true' : 'false' }} }">
                        <div class="flex flex-col">
                            <button type="button"
                                @click="open = !open"
                                class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                {{ request()->routeIs('user.create') || request()->routeIs('user.manage') || request()->routeIs('user.edit')
                                    ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200'
                                    : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                    {{ request()->routeIs('user.create') || request()->routeIs('user.manage') || request()->routeIs('user.edit')
                                        ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                    <flux:icon name="user" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                </span>
                                <span>{{ __('User') }}</span>
                                <svg :class="{ 'rotate-180': open }" class="w-4 h-4 ml-auto text-zinc-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <ul x-show="open" x-transition class="pl-8 mt-2">
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('user.manage') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('user.manage') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        <a href="{{ route('user.manage') }}">
                                            {{ __('User List') }}
                                        </a>
                                    </div>
                                </li>
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('user.create') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('user.create') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        <a href="{{ route('user.create') }}">
                                            {{ __('Create User') }}
                                        </a>
                                    </div>
                                </li>
                                <li>
                                    @if(!empty($slug))
                                    <a href="{{ route('user.edit', ['slug' => $slug]) }}"
                                    class="block px-2 py-1 rounded {{ request()->routeIs('user.edit') ? 'text-green-700 bg-green-100 font-semibold' : 'text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                        {{ __('Edit Advert') }}
                                    </a>
                                    @endif
                                </li>
                            </ul>
                        </div>
                    </li>
                    <li x-data="{ open: {{ (request()->routeIs('job.job-adverts') || request()->routeIs('job.job-adverts.create') || request()->routeIs('job.job-adverts.edit') || request()->routeIs('job.job-adverts.vetting')) ? 'true' : 'false' }} }">
                        <div class="flex flex-col">
                            <button type="button"
                                @click="open = !open"
                                class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                {{ request()->routeIs('job.job-adverts') || request()->routeIs('job.job-adverts.create') || request()->routeIs('job.job-adverts.edit') || request()->routeIs('job.job-adverts.vetting')
                                    ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200'
                                    : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                    {{ request()->routeIs('job.job-adverts') || request()->routeIs('job.job-adverts.create') || request()->routeIs('job.job-adverts.edit') || request()->routeIs('job.job-adverts.vetting')
                                        ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                    <flux:icon name="briefcase" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                </span>
                                <span>{{ __('Adverts') }}</span>
                                <svg :class="{ 'rotate-180': open }" class="w-4 h-4 ml-auto text-zinc-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <ul x-show="open" x-transition class="pl-8 mt-2">
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('job.job-adverts') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('job.job-adverts') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        <a href="{{ route('job.job-adverts') }}">
                                            {{ __('Job Adverts') }}
                                        </a>
                                    </div>
                                </li>
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('job.job-adverts.create') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('job.job-adverts.create') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        <a href="{{ route('job.job-adverts.create') }}">
                                            {{ __('Create Adverts') }}
                                        </a>
                                    </div>
                                </li>
                                <li>
                                    @if(!empty($slug))
                                    <a href="{{ route('job.job-adverts.edit', ['slug' => $slug]) }}"
                                    class="block px-2 py-1 rounded {{ request()->routeIs('job.job-adverts.edit') ? 'text-green-700 bg-green-100 font-semibold' : 'text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                        {{ __('Edit Advert') }}
                                    </a>
                                    @endif
                                    @if(!empty($slug))
                                    <a href="{{ route('job.job-adverts.vetting', ['slug' => $slug]) }}"
                                    class="block px-2 py-1 rounded {{ request()->routeIs('job.job-adverts.vetting') ? 'text-green-700 bg-green-100 font-semibold' : 'text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                        {{ __('Vetting') }}
                                    </a>
                                    @endif
                                </li>
                            </ul>
                        </div>
                    </li>
                    <li x-data="{ open: {{ (request()->routeIs('role.create') || request()->routeIs('role.manage') || request()->routeIs('role.edit')) ? 'true' : 'false' }} }">
                        <div class="flex flex-col">
                            <button type="button"
                                @click="open = !open"
                                class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                {{ request()->routeIs('role.create') || request()->routeIs('role.manage') || request()->routeIs('role.edit')
                                    ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200'
                                    : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                    {{ request()->routeIs('role.create') || request()->routeIs('role.manage') || request()->routeIs('role.edit')
                                        ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                    <flux:icon name="tag" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                </span>
                                <span>{{ __('Role') }}</span>
                                <svg :class="{ 'rotate-180': open }" class="w-4 h-4 ml-auto text-zinc-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <ul x-show="open" x-transition class="pl-8 mt-2">
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('role.manage') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('role.manage') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        <a href="{{ route('role.manage') }}">
                                            {{ __('Role List') }}
                                        </a>
                                    </div>
                                </li>
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('role.create') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('role.create') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        <a href="{{ route('role.create') }}">
                                            {{ __('Create Role') }}
                                        </a>
                                    </div>
                                </li>
                                <li>
                                    @if(!empty($slug))
                                    <a href="{{ route('role.edit', ['slug' => $slug]) }}"
                                    class="block px-2 py-1 rounded {{ request()->routeIs('role.edit') ? 'text-green-700 bg-green-100 font-semibold' : 'text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                        {{ __('Edit Advert') }}
                                    </a>
                                    @endif
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </nav>
            {{-- <flux:spacer /> --}}
            
            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                />
                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile Header -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 ml-4">
                <x-app-logo class="h-8 w-8" />
                <span class="font-bold text-lg text-green-600">HRM</span>
            </a>
            <flux:spacer />
            <!-- Mobile User Menu -->
            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />
                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
        
    </body>
</html>