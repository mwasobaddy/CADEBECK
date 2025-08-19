<?php

use function Livewire\Volt\{layout, state, mount};
use Illuminate\Support\Facades\Auth;

layout('components.layouts.app');

state(['dashboardData' => []]);

mount(function () {
    $user = Auth::user();
    $data = [];

    if ($user->can('full_system_access')) {
        $data['system_stats'] = 'All system stats';
        $data['onboarding'] = 'All onboarding templates';
        $data['users'] = 'All users';
        $data['reports'] = 'All reports';
        $data['payroll'] = 'Payroll integrations';
    }

    if ($user->can('create_onboarding_workflows')) {
        $data['onboarding'] = 'Manage onboarding workflows';
        $data['employees'] = 'Manage employees';
        $data['leave'] = 'Leave requests & attendance';
        $data['performance'] = 'Performance reviews';
        $data['wellbeing'] = 'Well-being reports';
    }

    if ($user->can('login')) {
        $data['check_in_out'] = 'Check in/out';
        $data['documents'] = 'Upload documents';
        $data['orientation'] = 'Orientation materials';
        $data['leave'] = 'Request leave & view balance';
        $data['payslips'] = 'View/download payslips';
        $data['wellbeing'] = 'Stress monitoring & surveys';
    }

    $this->dashboardData = $data;
});

?>

<div class="p-6">
    <h1 class="text-2xl font-bold mb-4">{{ __('Dashboard') }}</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($dashboardData as $key => $value)
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-4">
                {{-- display role --}}

                <h2 class="text-lg font-semibold mb-2">{{ __(ucwords(str_replace('_', ' ', $key))) }}</h2>
                <p class="text-zinc-600 dark:text-zinc-300">{{ __($value) }}</p>
            </div>
        @endforeach
    </div>
</div>