<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\WellBeingResponse;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new #[Layout('components.layouts.app')] class extends Component {
    use AuthorizesRequests;

    public $selectedDepartment = null;
    public $selectedEmployee = null;
    public $dateRange = 'month';
    public $teamWellbeing = [];
    public $departmentStats = [];
    public $employeeWellbeing = [];

    public function mount()
    {
        $this->authorize('viewAny', WellBeingResponse::class);
        $this->loadTeamWellbeing();
        $this->loadDepartmentStats();
    }

    public function loadTeamWellbeing()
    {
        $user = Auth::user();
        $query = WellBeingResponse::with(['employee.user', 'employee.department'])
            ->viewableBy($user);

        // Apply date filter
        $this->applyDateFilter($query);

        $this->teamWellbeing = $query->get();
    }

    public function loadDepartmentStats()
    {
        $user = Auth::user();

        // Get departments user can access
        $departments = Department::query();

        if (!$user->hasRole(['Developer', 'Executive'])) {
            $departments->whereHas('employees', function ($q) use ($user) {
                if ($user->hasRole('Manager N-1')) {
                    $q->where(function ($subQ) use ($user) {
                        $subQ->whereHas('supervisor.supervisor', function ($superQ) use ($user) {
                            $superQ->where('user_id', $user->id);
                        })->orWhereHas('supervisor', function ($superQ) use ($user) {
                            $superQ->where('user_id', $user->id)
                                   ->whereHas('user.roles', function ($roleQ) {
                                       $roleQ->where('name', 'Manager N-2');
                                   });
                        });
                    });
                } elseif ($user->hasRole('Manager N-2')) {
                    $q->whereHas('supervisor', function ($superQ) use ($user) {
                        $superQ->where('user_id', $user->id);
                    });
                }
            });
        }

        $departments = $departments->get();

        $this->departmentStats = $departments->map(function ($department) use ($user) {
            $query = WellBeingResponse::whereHas('employee', function ($q) use ($department) {
                $q->where('department_id', $department->id);
            })->viewableBy($user);

            $this->applyDateFilter($query);
            $responses = $query->get();

            return [
                'department' => $department,
                'total_responses' => $responses->count(),
                'avg_stress' => $responses->avg('stress_level') ?? 0,
                'high_stress_count' => $responses->where('stress_level', '>=', 7)->count(),
                'low_satisfaction_count' => $responses->where('job_satisfaction', '<=', 3)->count(),
            ];
        });
    }

    private function applyDateFilter($query)
    {
        switch ($this->dateRange) {
            case 'week':
                $query->where('created_at', '>=', now()->startOfWeek());
                break;
            case 'month':
                $query->where('created_at', '>=', now()->startOfMonth());
                break;
            case 'quarter':
                $query->where('created_at', '>=', now()->startOfQuarter());
                break;
            case 'year':
                $query->where('created_at', '>=', now()->startOfYear());
                break;
        }
    }

    public function updatedDateRange()
    {
        $this->loadTeamWellbeing();
        $this->loadDepartmentStats();
    }
};
?>

<!-- Wellbeing Reports Dashboard -->
<div class="relative max-w-6xl mx-auto md:px-4 md:py-8">
    <!-- SVG Blobs Background -->
    <svg class="fixed -top-24 right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0" viewBox="0 0 400 400" fill="none">
        <ellipse cx="200" cy="200" rx="180" ry="120" fill="url(#blob1)" />
        <defs>
            <radialGradient id="blob1" cx="0" cy="0" r="1" gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                <stop stop-color="#38bdf8" />
                <stop offset="1" stop-color="#6366f1" />
            </radialGradient>
        </defs>
    </svg>
    <svg class="fixed -bottom-24 -right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0" viewBox="0 0 400 400" fill="none">
        <ellipse cx="200" cy="200" rx="160" ry="100" fill="url(#blob2)" />
        <defs>
            <radialGradient id="blob2" cx="0" cy="0" r="1" gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                <stop stop-color="#34d399" />
                <stop offset="1" stop-color="#f472b6" />
            </radialGradient>
        </defs>
    </svg>

    <!-- Breadcrumbs -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 z-10 relative border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <nav class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('dashboard') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800" wire:navigate>
                    {{ __('Dashboard') }}
                </a>
                <span class="text-gray-500 dark:text-gray-400">{{ __('Wellbeing Reports') }}</span>
            </div>
        </nav>
    </div>
    
    <!-- Card Container for Content -->
    <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
            <div class="flex items-center gap-3 mb-8">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-800 via-blue-500 to-purple-500 tracking-tight drop-shadow-lg relative inline-block">
                    {{ __('Wellbeing Reports') }}
                </h1>
            </div>
            <div class="flex items-center gap-4">
                <select wire:model.live="dateRange" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100">
                    <option value="week">{{ __('This Week') }}</option>
                    <option value="month">{{ __('This Month') }}</option>
                    <option value="quarter">{{ __('This Quarter') }}</option>
                    <option value="year">{{ __('This Year') }}</option>
                </select>
            </div>
        </div>

        <!-- Department Overview -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">{{ __('Department Overview') }}</h2>
            @if($this->departmentStats->isEmpty())
                <div class="bg-gray-50 dark:bg-zinc-800/50 rounded-xl p-8 text-center">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ __('No Department Data Available') }}</h3>
                    <p class="text-gray-600 dark:text-gray-400">{{ __('There are no departments with wellbeing data for the selected period.') }}</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($this->departmentStats as $stat)
                        <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">
                                {{ $stat['department']->name }}
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Total Responses') }}</span>
                                    <span class="font-semibold">{{ $stat['total_responses'] }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Avg Stress Level') }}</span>
                                    <span class="font-semibold {{ $stat['avg_stress'] >= 7 ? 'text-red-600' : ($stat['avg_stress'] >= 4 ? 'text-yellow-600' : 'text-green-600') }}">
                                        {{ number_format($stat['avg_stress'], 1) }}/10
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('High Stress Cases') }}</span>
                                    <span class="font-semibold text-red-600">{{ $stat['high_stress_count'] }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Low Satisfaction') }}</span>
                                    <span class="font-semibold text-orange-600">{{ $stat['low_satisfaction_count'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Team Wellbeing Table -->
        <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">
                {{ __('Team Wellbeing Submissions') }}
            </h3>
            <div class="overflow-x-auto bg-transparent">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-3 px-4">{{ __('Employee') }}</th>
                            <th class="text-left py-3 px-4">{{ __('Department') }}</th>
                            <th class="text-left py-3 px-4">{{ __('Date') }}</th>
                            <th class="text-left py-3 px-4">{{ __('Stress Level') }}</th>
                            <th class="text-left py-3 px-4">{{ __('Work-Life Balance') }}</th>
                            <th class="text-left py-3 px-4">{{ __('Job Satisfaction') }}</th>
                            <th class="text-left py-3 px-4">{{ __('Comments') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($this->teamWellbeing->isEmpty())
                            <tr>
                                <td colspan="7" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.29-.98-5.5-2.5"></path>
                                    </svg>
                                    <p class="text-lg font-medium">{{ __('No Wellbeing Submissions') }}</p>
                                    <p class="text-sm">{{ __('No wellbeing submissions found for the selected period.') }}</p>
                                </td>
                            </tr>
                        @else
                            @foreach($this->teamWellbeing as $response)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-3 px-4">
                                        {{ $response->employee->user->first_name }} {{ $response->employee->user->other_names }}
                                    </td>
                                    <td class="py-3 px-4">
                                        {{ $response->employee->department->name ?? __('N/A') }}
                                    </td>
                                    <td class="py-3 px-4">
                                        {{ $response->created_at->format('M j, Y') }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                                            @if($response->stress_level >= 7) bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                            @elseif($response->stress_level >= 4) bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                            @else bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 @endif">
                                            {{ $response->stress_level }}/10
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                                            @if($response->work_life_balance >= 7) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                            @elseif($response->work_life_balance >= 4) bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                            @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @endif">
                                            {{ $response->work_life_balance }}/10
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                                            @if($response->job_satisfaction >= 7) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                            @elseif($response->job_satisfaction >= 4) bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                            @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @endif">
                                            {{ $response->job_satisfaction }}/10
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 max-w-xs truncate">
                                        {{ $response->comments ?: '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>