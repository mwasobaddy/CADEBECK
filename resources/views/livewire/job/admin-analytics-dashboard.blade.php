<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\JobAdvert;
use App\Models\Application;
use App\Models\Audit;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.app')] class extends Component {
    public $applicationsPerJob = [];
    public $applicationTrends = [];
    public $avgTimeToHire = null;
    public $recruiterActivity = [];

    public function mount(): void
    {
        $this->applicationsPerJob = JobAdvert::withCount('applications')->get();
        $this->applicationTrends = Application::select(DB::raw('DATE(submitted_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        $this->avgTimeToHire = JobAdvert::whereHas('applications', function($q) {
            $q->where('status', 'Invited');
        })->select(DB::raw('AVG(julianday((SELECT submitted_at FROM applications WHERE job_advert_id = job_adverts.id AND status = "Invited" LIMIT 1)) - julianday(created_at)) as avg_days'))->value('avg_days');
        $this->recruiterActivity = Audit::select('actor_id', DB::raw('count(*) as actions'))
            ->groupBy('actor_id')
            ->orderByDesc('actions')
            ->get();
    }
}; ?>

<div class="p-6">
    <h1 class="text-2xl font-bold mb-4">{{ __('Admin Analytics Dashboard') }}</h1>
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-2">{{ __('Applications Per Job') }}</h2>
        <table class="min-w-full bg-white dark:bg-zinc-900 rounded shadow mb-4">
            <thead>
                <tr>
                    <th class="px-4 py-2">{{ __('Job Title') }}</th>
                    <th class="px-4 py-2">{{ __('Applications') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($applicationsPerJob as $job)
                    <tr>
                        <td class="border px-4 py-2">{{ $job->title }}</td>
                        <td class="border px-4 py-2">{{ $job->applications_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-2">{{ __('Application Trends') }}</h2>
        <table class="min-w-full bg-white dark:bg-zinc-900 rounded shadow mb-4">
            <thead>
                <tr>
                    <th class="px-4 py-2">{{ __('Date') }}</th>
                    <th class="px-4 py-2">{{ __('Applications') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($applicationTrends as $trend)
                    <tr>
                        <td class="border px-4 py-2">{{ $trend->date }}</td>
                        <td class="border px-4 py-2">{{ $trend->count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-2">{{ __('Average Time to Hire (days)') }}</h2>
        <div class="text-xl">{{ $avgTimeToHire ? number_format($avgTimeToHire, 2) : __('N/A') }}</div>
    </div>
    <div class="mb-8">
        <h2 class="text-lg font-semibold mb-2">{{ __('Recruiter Activity') }}</h2>
        <table class="min-w-full bg-white dark:bg-zinc-900 rounded shadow mb-4">
            <thead>
                <tr>
                    <th class="px-4 py-2">{{ __('Recruiter ID') }}</th>
                    <th class="px-4 py-2">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recruiterActivity as $activity)
                    <tr>
                        <td class="border px-4 py-2">{{ $activity->actor_id }}</td>
                        <td class="border px-4 py-2">{{ $activity->actions }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
