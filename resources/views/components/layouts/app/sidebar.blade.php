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
                    @if (Auth::user()->can('manage_employee') || Auth::user()->can('create_employee'))
                    <li x-data="{ open: {{ (request()->routeIs('employee.*')) ? 'true' : 'false' }} }">
                        <div class="flex flex-col">
                            <button type="button"
                                @click="open = !open"
                                class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                {{ request()->routeIs('employee.*')
                                    ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200'
                                    : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                    {{ request()->routeIs('employee.*')
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
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('employee.index') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('employee.index') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        <a href="{{ route('employee.index') }}">
                                            {{ __('Employee List') }}
                                        </a>
                                    </div>
                                </li>
                                @endcan
                                @if (request()->routeIs('employee.edit') || request()->routeIs('employee.payroll.*'))
                                @can('edit_employee')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('employee.edit') || request()->routeIs('employee.payroll.*') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('employee.edit') || request()->routeIs('employee.payroll.*') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        <a href="">
                                            {{ __('Edit') }}
                                            @if (request()->routeIs('employee.edit'))
                                                {{ __('Employee') }}
                                            @elseif (request()->routeIs('employee.payroll.allowances.*') || request()->routeIs('employee.payroll.allowances'))
                                                {{ __('Allowances') }}
                                            @elseif (request()->routeIs('employee.payroll.deductions.*') || request()->routeIs('employee.payroll.deductions'))
                                                {{ __('Deductions') }}
                                            @elseif (request()->routeIs('employee.payroll.payslips.*') || request()->routeIs('employee.payroll.payslips'))
                                                {{ __('Payslips') }}
                                            @elseif (request()->routeIs('employee.payroll-history.*') || request()->routeIs('employee.payroll-history'))
                                                {{ __('Payroll History') }}
                                            @endif
                                        </a>
                                    </div>
                                </li>
                                @endcan
                                @else
                                @can('create_employee')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('employee.show') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('employee.show') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        <a href="{{ route('employee.show') }}">
                                            {{ __('Create Employee') }}
                                        </a>
                                    </div>
                                </li>
                                @endcan
                                @endif
                            </ul>
                        </div>
                    </li>
                    @endif

                    @if (Auth::user()->can('manage_user') || Auth::user()->can('create_user'))
                    <li x-data="{ open: {{ (request()->routeIs('user.show') || request()->routeIs('user.index') || request()->routeIs('user.edit')) ? 'true' : 'false' }} }">
                        <div class="flex flex-col">
                            <button type="button"
                                @click="open = !open"
                                class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                {{ request()->routeIs('user.show') || request()->routeIs('user.index') || request()->routeIs('user.edit')
                                    ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200'
                                    : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                    {{ request()->routeIs('user.show') || request()->routeIs('user.index') || request()->routeIs('user.edit')
                                        ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                    <flux:icon name="user" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                </span>
                                <span>{{ __('User') }}</span>
                                <svg :class="{ 'rotate-180': open }" class="w-4 h-4 ml-auto text-zinc-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <ul x-show="open" x-transition class="pl-8 mt-2">
                                @can('manage_user')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('user.index') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('user.index') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        <a href="{{ route('user.index') }}">
                                            {{ __('User List') }}
                                        </a>
                                    </div>
                                </li>
                                @endcan
                                @can('create_user')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('user.show') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('user.show') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        <a href="{{ route('user.show') }}">
                                            {{ __('Create User') }}
                                        </a>
                                    </div>
                                </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                    @endif

                    @if (Auth::user()->can('manage_job_advert') || Auth::user()->can('create_job_advert'))
                    <li x-data="{ open: {{ (request()->routeIs('job.index') || request()->routeIs('job.show') || request()->routeIs('job.edit') || request()->routeIs('job.index.vetting')) ? 'true' : 'false' }} }">
                        <div class="flex flex-col">
                            <button type="button"
                                @click="open = !open"
                                class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                {{ request()->routeIs('job.index') || request()->routeIs('job.show') || request()->routeIs('job.edit') || request()->routeIs('job.index.vetting')
                                    ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200'
                                    : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                    {{ request()->routeIs('job.index') || request()->routeIs('job.show') || request()->routeIs('job.edit') || request()->routeIs('job.index.vetting')
                                        ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                    <flux:icon name="briefcase" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                </span>
                                <span>{{ __('Adverts') }}</span>
                                <svg :class="{ 'rotate-180': open }" class="w-4 h-4 ml-auto text-zinc-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <ul x-show="open" x-transition class="pl-8 mt-2">
                                @can('manage_job_advert')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('job.index') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('job.index') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        <a href="{{ route('job.index') }}">
                                            {{ __('Job Adverts') }}
                                        </a>
                                    </div>
                                </li>
                                @endcan
                                @can('create_job_advert')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('job.show') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('job.show') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        <a href="{{ route('job.show') }}">
                                            {{ __('Create Adverts') }}
                                        </a>
                                    </div>
                                </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                    @endif

                    @if (Auth::user()->can('manage_role') || Auth::user()->can('create_role'))
                    <li x-data="{ open: {{ (request()->routeIs('role.show') || request()->routeIs('role.index') || request()->routeIs('role.edit')) ? 'true' : 'false' }} }">
                        <div class="flex flex-col">
                            <button type="button"
                                @click="open = !open"
                                class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                {{ request()->routeIs('role.show') || request()->routeIs('role.index') || request()->routeIs('role.edit')
                                    ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200'
                                    : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                    {{ request()->routeIs('role.show') || request()->routeIs('role.index') || request()->routeIs('role.edit')
                                        ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                    <flux:icon name="tag" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                </span>
                                <span>{{ __('Role') }}</span>
                                <svg :class="{ 'rotate-180': open }" class="w-4 h-4 ml-auto text-zinc-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <ul x-show="open" x-transition class="pl-8 mt-2">
                                @can('manage_role')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('role.index') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('role.index') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        <a href="{{ route('role.index') }}">
                                            {{ __('Role List') }}
                                        </a>
                                    </div>
                                </li>
                                @endcan
                                @can('create_role')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('role.show') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('role.show') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                        <a href="{{ route('role.show') }}">
                                            {{ __('Create Role') }}
                                        </a>
                                    </div>
                                </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                    @endif
                    
                    @if (Auth::user()->can('manage_location') || Auth::user()->can('create_location') || Auth::user()->can('manage_branch') || Auth::user()->can('create_branch') || Auth::user()->can('manage_department') || Auth::user()->can('create_department') || Auth::user()->can('manage_designation') || Auth::user()->can('create_designation'))
                    <li x-data="{ open: {{ (request()->routeIs('location.create') || request()->routeIs('location.manage') || request()->routeIs('location.edit') || request()->routeIs('branch.create') || request()->routeIs('branch.manage') || request()->routeIs('branch.edit') || request()->routeIs('department.create') || request()->routeIs('department.manage') || request()->routeIs('department.edit') || request()->routeIs('designation.create') || request()->routeIs('designation.manage') || request()->routeIs('designation.edit')) ? 'true' : 'false' }} }">
                        <div class="flex flex-col">
                            <button type="button"
                                @click="open = !open"
                                class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                {{ request()->routeIs('location.create') || request()->routeIs('location.manage') || request()->routeIs('location.edit') || request()->routeIs('branch.create') || request()->routeIs('branch.manage') || request()->routeIs('branch.edit') || request()->routeIs('department.create') || request()->routeIs('department.manage') || request()->routeIs('department.edit') || request()->routeIs('designation.create') || request()->routeIs('designation.manage') || request()->routeIs('designation.edit')
                                    ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200'
                                    : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                    {{ request()->routeIs('location.create') || request()->routeIs('location.manage') || request()->routeIs('location.edit') || request()->routeIs('branch.create') || request()->routeIs('branch.manage') || request()->routeIs('branch.edit') || request()->routeIs('department.create') || request()->routeIs('department.manage') || request()->routeIs('department.edit') || request()->routeIs('designation.create') || request()->routeIs('designation.manage') || request()->routeIs('designation.edit')
                                        ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                    <flux:icon name="building-office" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                </span>
                                <span>{{ __("Organization") }}</span>
                                <svg :class="{ 'rotate-180': open }" class="w-4 h-4 ml-auto text-zinc-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <ul x-show="open" x-transition class="pl-8 mt-2">
                                @can('manage_location')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('location.manage') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('location.manage') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                        </svg>
                                        <a href="{{ route('location.manage') }}">{{ __('Locations') }}</a>
                                    </div>
                                </li>
                                @endcan

                                @can('manage_branch')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('branch.manage') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('branch.manage') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                        </svg>
                                        <a href="{{ route('branch.manage') }}">{{ __('Branches') }}</a>
                                    </div>
                                </li>
                                @endcan

                                @can('manage_department')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('department.manage') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('department.manage') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                        </svg>
                                        <a href="{{ route('department.manage') }}">{{ __('Departments') }}</a>
                                    </div>
                                </li>
                                @endcan

                                @can('manage_designation')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('designation.manage') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('designation.manage') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                        </svg>
                                        <a href="{{ route('designation.manage') }}">{{ __('Designations') }}</a>
                                    </div>
                                </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                    @endif

                    @if (Auth::user()->can('manage_my_leave') || Auth::user()->can('apply_for_leave') || Auth::user()->can('manage_all_leaves') || Auth::user()->can('manage_leave_type') || Auth::user()->can('create_leave_type'))
                    <li x-data="{ open: {{ (request()->routeIs('own-leave.manage') || request()->routeIs('leave.apply') || request()->routeIs('own-leave.edit') || request()->routeIs('all-leave.manage') || request()->routeIs('all-leave.edit')) ? 'true' : 'false' }} }">
                        <div class="flex flex-col">
                            <button type="button"
                                @click="open = !open"
                                class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                {{ request()->routeIs('own-leave.manage') || request()->routeIs('leave.apply') || request()->routeIs('own-leave.edit') || request()->routeIs('all-leave.manage') || request()->routeIs('all-leave.edit')
                                    ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200'
                                    : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                    {{ request()->routeIs('own-leave.manage') || request()->routeIs('leave.apply') || request()->routeIs('own-leave.edit') || request()->routeIs('all-leave.manage') || request()->routeIs('all-leave.edit')
                                        ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                    <flux:icon name="building-office" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                </span>
                                <span>{{ __("Leave") }}</span>
                                <svg :class="{ 'rotate-180': open }" class="w-4 h-4 ml-auto text-zinc-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <ul x-show="open" x-transition class="pl-8 mt-2">
                                <li class="mb-2">
                                    <div class="flex items-center gap-2 px-2 py-1">
                                        <flux:icon name="document" variant="solid" class="w-4 h-4 text-green-600 dark:text-green-400" />
                                        <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ __('My Requests') }}</span>
                                    </div>
                                    <flux:menu.separator class="my-2 border-green-600 dark:border-green-400" />
                                </li>
                                @can('apply_for_leave')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('leave.apply') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('leave.apply') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                        </svg>
                                        <a href="{{ route('leave.apply') }}">{{ __('Apply Leave') }}</a>
                                    </div>
                                </li>
                                @endcan
                                @can('manage_my_leave')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('own-leave.manage') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('own-leave.manage') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                        </svg>
                                        <a href="{{ route('own-leave.manage') }}">{{ __('Leave List') }}</a>
                                    </div>
                                </li>
                                @endcan

                                <li class="mb-2">
                                    <div class="flex items-center gap-2 px-2 py-1">
                                        <flux:icon name="document" variant="solid" class="w-4 h-4 text-green-600 dark:text-green-400" />
                                        <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Other Requests') }}</span>
                                    </div>
                                    <flux:menu.separator class="my-2 border-green-600 dark:border-green-400" />
                                </li>
                                @can('manage_all_leaves')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('all-leave.manage') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('all-leave.manage') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                        </svg>
                                        <a href="{{ route('all-leave.manage') }}">{{ __('All Leave List') }}</a>
                                    </div>
                                </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                    @endif
                    
                    @if (Auth::user()->can('process_payroll') || Auth::user()->can('view_my_payslips'))
                    <li x-data="{ open: {{ (request()->routeIs('payroll.process') || request()->routeIs('payroll.employee')) ? 'true' : 'false' }} }">
                        <div class="flex flex-col">
                            <button type="button"
                                @click="open = !open"
                                class="flex items-center gap-2 px-1 py-1 transition-colors rounded-full font-semibold
                                {{ request()->routeIs('payroll.process') || request()->routeIs('payroll.employee')
                                    ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200'
                                    : 'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }}">
                                <span class="flex items-center rounded-full font-black bg-gray-200 dark:bg-zinc-700 p-2
                                    {{ request()->routeIs('payroll.process') || request()->routeIs('payroll.employee')
                                        ? 'bg-white dark:bg-zinc-900' : 'dark:bg-zinc-500' }}">
                                    <flux:icon name="building-office" variant="solid" class="w-4 h-4 text-zinc-500 dark:text-zinc-200" />
                                </span>
                                <span>{{ __("Payroll") }}</span>
                                <svg :class="{ 'rotate-180': open }" class="w-4 h-4 ml-auto text-zinc-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <ul x-show="open" x-transition class="pl-8 mt-2">
                                @can('process_payroll')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('payroll.process') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('payroll.process') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                        </svg>
                                        <a href="{{ route('payroll.process') }}">{{ __('Process Payroll') }}</a>
                                    </div>
                                </li>
                                @endcan

                                @can('view_my_payslips')
                                <li>
                                    <div class="block px-2 py-1 border-l-2 py-2 flex items-center rounded-e-4xl {{ request()->routeIs('payroll.employee') ? 'border-green-600 dark:border-green-700 text-zinc-500 dark:text-zinc-200 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-300/50 dark:hover:bg-zinc-800' }} duration-300 ease-in-out">
                                        <svg class="w-2 h-2 mr-2 {{ request()->routeIs('payroll.employee') ? 'fill-current text-green-600 dark:text-green-700' : 'fill-zinc-500 dark:fill-zinc-700' }}" viewBox="0 0 24 24">
                                        </svg>
                                        <a href="{{ route('payroll.employee') }}">{{ __('My Payslips') }}</a>
                                    </div>
                                </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                    @endif
                </ul>
            </nav>
            
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

    @livewire('notification.notification-manager')
    @fluxScripts
        
    </body>
</html>