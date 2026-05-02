<div class="border-e border-zinc-200 dark:border-zinc-700 h-screen p-4 bg-zinc-50 dark:bg-zinc-900 flex flex-col">
    <flux:sidebar.toggle class="lg:hidden left-[86%]" icon="x-mark" />
    <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
        <x-app-logo />
    </a>
    <nav class="flex-1 px-4 py-6 overflow-y-auto text-sm">
        <ul class="space-y-2">
            <li>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold {{ request()->routeIs('dashboard') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}" wire:navigate>
                    <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('dashboard') ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                        <flux:icon name="home" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                    </span>
                    <span>{{ __('Dashboard') }}</span>
                </a>
            </li>
            @if (Auth::user()->can('manage_employee') || Auth::user()->can('create_employee'))
            <li x-data="{ open: {{ (request()->routeIs('employee.*')) ? 'true' : 'false' }} }">
                <div class="flex flex-col">
                    <button type="button" @click="open = !open" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold {{ request()->routeIs('employee.*') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                        <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('employee.*') ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                            <flux:icon name="users" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                        </span>
                        <span>{{ __('Employees') }}</span>
                        <svg class="w-4 h-4 transition-transform ml-auto" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" class="mt-2 ms-4 space-y-1" style="display: none;">
                        @can('manage_employee')
                        <a href="{{ route('employee.index') }}" class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('employee.index') ? 'bg-blue-600 text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}" wire:navigate>{{ __('Manage Employees') }}</a>
                        @endcan
                        @can('create_employee')
                        <a href="{{ route('employee.show') }}" class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('employee.show') ? 'bg-blue-600 text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}" wire:navigate>{{ __('Add Employee') }}</a>
                        @endcan
                    </div>
                </div>
            </li>
            @endif

            @if (Auth::user()->can('manage_user') || Auth::user()->can('create_user'))
            <li x-data="{ open: {{ (request()->routeIs('user.*')) ? 'true' : 'false' }} }">
                <div class="flex flex-col">
                    <button type="button" @click="open = !open" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold {{ request()->routeIs('user.*') ? 'bg-purple-600 dark:bg-purple-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                        <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('user.*') ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                            <flux:icon name="user-circle" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                        </span>
                        <span>{{ __('Users') }}</span>
                        <svg class="w-4 h-4 transition-transform ml-auto" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" class="mt-2 ms-4 space-y-1" style="display: none;">
                        @can('manage_user')
                        <a href="{{ route('user.index') }}" class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('user.index') ? 'bg-purple-600 text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}" wire:navigate>{{ __('Manage Users') }}</a>
                        @endcan
                        @can('create_user')
                        <a href="{{ route('user.show') }}" class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('user.show') ? 'bg-purple-600 text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}" wire:navigate>{{ __('Add User') }}</a>
                        @endcan
                    </div>
                </div>
            </li>
            @endif

            @if (Auth::user()->can('manage_job_advert') || Auth::user()->can('create_job_advert'))
            <li x-data="{ open: {{ (request()->routeIs('job.*') && !request()->routeIs('careers.*')) ? 'true' : 'false' }} }">
                <div class="flex flex-col">
                    <button type="button" @click="open = !open" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold {{ request()->routeIs('job.*') && !request()->routeIs('careers.*') ? 'bg-indigo-600 dark:bg-indigo-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                        <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('job.*') && !request()->routeIs('careers.*') ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                            <flux:icon name="briefcase" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                        </span>
                        <span>{{ __('Recruitment') }}</span>
                        <svg class="w-4 h-4 transition-transform ml-auto" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" class="mt-2 ms-4 space-y-1" style="display: none;">
                        <a href="{{ route('job.index') }}" class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('job.index') ? 'bg-indigo-600 text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}" wire:navigate>{{ __('Job Adverts') }}</a>
                        @can('manage_job_advert')
                        <a href="{{ route('job.show') }}" class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('job.show') ? 'bg-indigo-600 text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}" wire:navigate>{{ __('Create Job Ad') }}</a>
                        @endcan
                        @can('view_analytics')
                        <a href="{{ route('job.analytics') }}" class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('job.analytics') ? 'bg-indigo-600 text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}" wire:navigate>{{ __('Analytics') }}</a>
                        @endcan
                    </div>
                </div>
            </li>
            @endif

            @if (Auth::user()->can('manage_role') || Auth::user()->can('create_role'))
            <li>
                <a href="{{ route('role.index') }}" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold {{ request()->routeIs('role.*') ? 'bg-yellow-600 dark:bg-yellow-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}" wire:navigate>
                    <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('role.index') ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                        <flux:icon name="shield-check" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                    </span>
                    <span>{{ __('Roles & Permissions') }}</span>
                </a>
            </li>
            @endif

            @if (Auth::user()->can('manage_location') || Auth::user()->can('manage_branch') || Auth::user()->can('manage_department') || Auth::user()->can('manage_designation'))
            <li x-data="{ open: {{ (request()->routeIs('organisation.*')) ? 'true' : 'false' }} }">
                <div class="flex flex-col">
                    <button type="button" @click="open = !open" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold {{ request()->routeIs('organisation.*') ? 'bg-cyan-600 dark:bg-cyan-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                        <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('organisation.*') ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                            <flux:icon name="building-office" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                        </span>
                        <span>{{ __('Organisation') }}</span>
                        <svg class="w-4 h-4 transition-transform ml-auto" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" class="mt-2 ms-4 space-y-1" style="display: none;">
                        @can('manage_location')
                        <a href="{{ route('location.manage') }}" class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('location.*') ? 'bg-cyan-600 text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}" wire:navigate>{{ __('Locations') }}</a>
                        @endcan
                        @can('manage_branch')
                        <a href="{{ route('branch.manage') }}" class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('branch.*') ? 'bg-cyan-600 text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}" wire:navigate>{{ __('Branches') }}</a>
                        @endcan
                        @can('manage_department')
                        <a href="{{ route('department.manage') }}" class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('department.*') ? 'bg-cyan-600 text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}" wire:navigate>{{ __('Departments') }}</a>
                        @endcan
                        @can('manage_designation')
                        <a href="{{ route('designation.manage') }}" class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('designation.*') ? 'bg-cyan-600 text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}" wire:navigate>{{ __('Designations') }}</a>
                        @endcan
                    </div>
                </div>
            </li>
            @endif

            @if (Auth::user()->can('mark_attendance'))
            <li>
                <a href="{{ route('clock.manage') }}" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold {{ request()->routeIs('clock.*') ? 'bg-pink-600 dark:bg-pink-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}" wire:navigate>
                    <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('clock.*') ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                        <flux:icon name="clock" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                    </span>
                    <span>{{ __('Clock In/Out') }}</span>
                </a>
            </li>
            @endif

            @if (Auth::user()->can('view_other_attendance'))
            <li>
                <a href="{{ route('attendance.manage') }}" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold {{ request()->routeIs('attendance.manage') ? 'bg-rose-600 dark:bg-rose-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}" wire:navigate>
                    <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('attendance.manage') ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                        <flux:icon name="calendar-days" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                    </span>
                    <span>{{ __('Attendance') }}</span>
                </a>
            </li>
            @endif

            @if (Auth::user()->can('manage_my_leave'))
            <li x-data="{ open: {{ (request()->routeIs('own-leave.*') || request()->routeIs('leave.apply')) ? 'true' : 'false' }} }">
                <div class="flex flex-col">
                    <button type="button" @click="open = !open" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold {{ request()->routeIs('own-leave.*') || request()->routeIs('leave.apply') ? 'bg-orange-600 dark:bg-orange-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                        <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('own-leave.*') || request()->routeIs('leave.apply') ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                            <flux:icon name="calendar" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                        </span>
                        <span>{{ __('Leave') }}</span>
                        <svg class="w-4 h-4 transition-transform ml-auto" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" class="mt-2 ms-4 space-y-1" style="display: none;">
                        <a href="{{ route('own-leave.manage') }}" class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('own-leave.manage') ? 'bg-orange-600 text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}" wire:navigate>{{ __('My Leave') }}</a>
                        <a href="{{ route('leave.apply') }}" class="block px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('leave.apply') ? 'bg-orange-600 text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}" wire:navigate>{{ __('Apply Leave') }}</a>
                    </div>
                </div>
            </li>
            @endif

            @if (Auth::user()->can('manage_all_leaves'))
            <li>
                <a href="{{ route('all-leave.manage') }}" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold {{ request()->routeIs('all-leave.*') ? 'bg-red-600 dark:bg-red-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}" wire:navigate>
                    <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('all-leave.manage') ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                        <flux:icon name="calendar" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                    </span>
                    <span>{{ __('All Leaves') }}</span>
                </a>
            </li>
            @endif

            @if (Auth::user()->can('process_payroll'))
            <li>
                <a href="{{ route('payroll.process') }}" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold {{ request()->routeIs('payroll.process') ? 'bg-teal-600 dark:bg-teal-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}" wire:navigate>
                    <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('payroll.process') ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                        <flux:icon name="banknotes" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                    </span>
                    <span>{{ __('Payroll') }}</span>
                </a>
            </li>
            @endif

            @if (Auth::user()->can('edit_user'))
            <li>
                <a href="{{ route('wellbeing.dashboard') }}" class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold {{ request()->routeIs('wellbeing.*') ? 'bg-violet-600 dark:bg-violet-700 text-white dark:text-zinc-200' : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}" wire:navigate>
                    <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2 {{ request()->routeIs('wellbeing.dashboard') ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                        <flux:icon name="heart" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                    </span>
                    <span>{{ __('Well-being') }}</span>
                </a>
            </li>
            @endif
        </ul>
    </nav>

    <div class="border-t border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex items-center gap-3 mb-3">
            @if(Auth::user()->employee && Auth::user()->employee->profile_picture)
            <img src="{{ asset('storage/' . Auth::user()->employee->profile_picture) }}" class="w-10 h-10 rounded-full object-cover" alt="{{ Auth::user()->first_name }}">
            @else
            <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold">
                {{ Auth::user()->initials() }}
            </div>
            @endif
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-zinc-900 dark:text-white truncate">{{ Auth::user()->first_name }} {{ Auth::user()->other_names }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ Auth::user()->email }}</p>
            </div>
        </div>
        <div class="flex flex-col gap-2">
            <a href="{{ route('settings.profile') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors" wire:navigate>
                <flux:icon name="cog-6-tooth" variant="solid" class="w-4 h-4" />
                {{ __('Settings') }}
            </a>
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="flex items-center gap-2 px-3 py-2 w-full text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                    <flux:icon name="arrow-right-start-on-rectangle" variant="solid" class="w-4 h-4" />
                    {{ __('Log out') }}
                </button>
            </form>
        </div>
    </div>
</div>