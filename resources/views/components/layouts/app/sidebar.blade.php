<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <!-- Alpine.js for dropdown functionality -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">

        <div class="min-h-screen bg-white dark:bg-zinc-800 flex">
            <aside class="w-64 flex-shrink-0 border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 flex flex-col">
                <div class="h-full fixed overflow-y-auto">
                    <div class="flex items-center h-20 px-6 border-b border-zinc-200 dark:border-zinc-700">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                            <x-app-logo class="h-8 w-8" />
                            <span class="font-bold text-lg text-green-600">HRM</span>
                        </a>
                    </div>
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
                                                {{-- circle svg --}}
                                                <svg
                                                    class="w-2 h-2 mr-2
                                                    {{ request()->routeIs('job.job-adverts') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <circle cx="12" cy="12" r="10"/>
                                                </svg>
                                                <a href="{{ route('job.job-adverts') }}">
                                                    {{ __('Job Adverts') }}
                                                </a>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('job.job-adverts.create') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                                {{-- circle svg --}}
                                                <svg
                                                    class="w-2 h-2 mr-2
                                                    {{ request()->routeIs('job.job-adverts.create') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}"
                                                    viewBox="0 0 24 24"
                                                >
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
                                            class="block px-2 py-1 rounded
                                            {{ request()->routeIs('job.job-adverts.edit') ? 'text-green-700 bg-green-100 font-semibold' : 'text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                                {{ __('Edit Advert') }}
                                            </a>
                                            @endif
                                            @if(!empty($slug))
                                            <a href="{{ route('job.job-adverts.vetting', ['slug' => $slug]) }}"
                                            class="block px-2 py-1 rounded
                                            {{ request()->routeIs('job.job-adverts.vetting') ? 'text-green-700 bg-green-100 font-semibold' : 'text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                                {{ __('Vetting') }}
                                            </a>
                                            @endif
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li>
                                <a href="#" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full {{ request()->routeIs('attendance.*') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('attendance.*') ? 'bg-white' : '' }}">
                                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 17l4 4 4-4m-4-5v9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span>{{ __('Attendance') }}</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full {{ request()->routeIs('award.*') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('award.*') ? 'bg-white' : '' }}">
                                    <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span>{{ __('Award') }}</span>
                                </a>
                            </li>
                            <li>
                                <div class="flex flex-col">
                                <button class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 font-semibold">
                                    <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h8m-8 6h16" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                    <span>{{ __('Employee') }}</span>
                                </button>
                                <ul class="pl-8 mt-2 space-y-1">
                                    <li><a href="#" class="block px-2 py-1 rounded text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Position') }}</a></li>
                                    <li><a href="#" class="block px-2 py-1 rounded text-green-700 bg-green-100 font-semibold">{{ __('Employee List') }}</a></li>
                                    <li><a href="#" class="block px-2 py-1 rounded text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Performance') }}</a></li>
                                </ul>
                                </div>
                            </li>
                            <li>
                                <a href="#" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full {{ request()->routeIs('department.*') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('department.*') ? 'bg-white' : '' }}">
                                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 12v4m0 0v4m0-4h4m-4 0h-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span>{{ __('Department') }}</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full {{ request()->routeIs('leave.*') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('leave.*') ? 'bg-white' : '' }}">
                                    <svg class="w-4 h-4 text-pink-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span>{{ __('Leave') }}</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full {{ request()->routeIs('loan.*') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('loan.*') ? 'bg-white' : '' }}">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span>{{ __('Loan') }}</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full {{ request()->routeIs('project.*') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('project.*') ? 'bg-white' : '' }}">
                                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span>{{ __('Project Management') }}</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full {{ request()->routeIs('recruitment.*') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('recruitment.*') ? 'bg-white' : '' }}">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span>{{ __('Recruitment') }}</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full {{ request()->routeIs('reports.*') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('reports.*') ? 'bg-white' : '' }}">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7v4a1 1 0 001 1h16a1 1 0 001-1V7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span>{{ __('Reports') }}</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full {{ request()->routeIs('reward.*') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('reward.*') ? 'bg-white' : '' }}">
                                    <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span>{{ __('Reward Points') }}</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </aside>
            <main class="flex-1">
                {{ $slot }}
            </main>

            @fluxScripts
        </div>
    </body>
</html>