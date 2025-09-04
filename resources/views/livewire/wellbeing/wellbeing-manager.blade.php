<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\WellBeingResponse;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new #[Layout('components.layouts.app')] class extends Component {
    public $showSurveyForm = false;
    public $currentSurvey = null;
    public $wellBeingResponses = [];
    public $surveys = [];

    // Survey form fields
    public $stress_level = 5; // 1-10 scale
    public $mood_rating = 5; // 1-10 scale
    public $sleep_quality = 5; // 1-10 scale
    public $work_satisfaction = 5; // 1-10 scale
    public $workload_rating = 5; // 1-10 scale
    public $comments = '';
    public $anonymity_preference = false;

    public $isLoading = false;

    public function mount()
    {
        $this->loadWellBeingData();
        $this->initializeSurveys();
    }

    public function loadWellBeingData()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if ($employee) {
            $this->wellBeingResponses = WellBeingResponse::where('employee_id', $employee->id)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        }
    }

    public function initializeSurveys()
    {
        $this->surveys = [
            [
                'id' => 'daily_checkin',
                'title' => __('Daily Well-being Check-in'),
                'description' => __('Quick daily assessment of your current well-being'),
                'frequency' => 'daily',
                'questions' => ['stress_level', 'mood_rating', 'sleep_quality']
            ],
            [
                'id' => 'weekly_assessment',
                'title' => __('Weekly Work Assessment'),
                'description' => __('Comprehensive weekly evaluation of work satisfaction and workload'),
                'frequency' => 'weekly',
                'questions' => ['work_satisfaction', 'workload_rating', 'stress_level', 'comments']
            ],
            [
                'id' => 'monthly_review',
                'title' => __('Monthly Well-being Review'),
                'description' => __('Monthly comprehensive well-being and stress monitoring review'),
                'frequency' => 'monthly',
                'questions' => ['stress_level', 'mood_rating', 'sleep_quality', 'work_satisfaction', 'workload_rating', 'comments']
            ]
        ];
    }

    public function startSurvey($surveyId)
    {
        $this->currentSurvey = collect($this->surveys)->firstWhere('id', $surveyId);
        $this->showSurveyForm = true;
        $this->resetSurveyForm();
    }

    public function hideSurveyForm()
    {
        $this->showSurveyForm = false;
        $this->currentSurvey = null;
        $this->resetSurveyForm();
    }

    public function resetSurveyForm()
    {
        $this->stress_level = 5;
        $this->mood_rating = 5;
        $this->sleep_quality = 5;
        $this->work_satisfaction = 5;
        $this->workload_rating = 5;
        $this->comments = '';
        $this->anonymity_preference = false;
    }

    public function submitSurvey()
    {
        $this->isLoading = true;

        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('Employee record not found. Please contact HR.')
            ]);
            $this->isLoading = false;
            return;
        }

        $this->validate([
            'stress_level' => 'required|integer|min:1|max:10',
            'mood_rating' => 'required|integer|min:1|max:10',
            'sleep_quality' => 'required|integer|min:1|max:10',
            'work_satisfaction' => 'required|integer|min:1|max:10',
            'workload_rating' => 'required|integer|min:1|max:10',
            'comments' => 'nullable|string|max:1000',
        ]);

        WellBeingResponse::create([
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'survey_type' => $this->currentSurvey['id'],
            'responses' => [
                'stress_level' => $this->stress_level,
                'mood_rating' => $this->mood_rating,
                'sleep_quality' => $this->sleep_quality,
                'work_satisfaction' => $this->work_satisfaction,
                'workload_rating' => $this->workload_rating,
                'comments' => $this->comments,
            ],
            'is_anonymous' => $this->anonymity_preference,
            'submitted_at' => now(),
        ]);

        $this->loadWellBeingData();
        $this->hideSurveyForm();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Well-being survey submitted successfully. Thank you for your feedback!')
        ]);

        $this->isLoading = false;
    }

    public function getAverageRating($responses, $key)
    {
        if ($responses->isEmpty()) return 0;

        return round($responses->avg(function($response) use ($key) {
            return $response->responses[$key] ?? 0;
        }), 1);
    }

    public function getStressLevelText($level)
    {
        return match(true) {
            $level <= 3 => __('Low'),
            $level <= 7 => __('Moderate'),
            default => __('High')
        };
    }

    public function getStressLevelColor($level)
    {
        return match(true) {
            $level <= 3 => 'green',
            $level <= 7 => 'yellow',
            default => 'red'
        };
    }

    public function getMoodText($rating)
    {
        return match(true) {
            $rating <= 3 => __('Poor'),
            $rating <= 7 => __('Fair'),
            default => __('Good')
        };
    }

    public function getMoodColor($rating)
    {
        return match(true) {
            $rating <= 3 => 'red',
            $rating <= 7 => 'yellow',
            default => 'green'
        };
    }

    public function getRecentTrend($responses, $key, $days = 7)
    {
        if ($responses->count() < 2) return 'stable';

        $recent = $responses->take(7);
        $first = $recent->first()->responses[$key] ?? 0;
        $last = $recent->last()->responses[$key] ?? 0;

        $difference = $last - $first;

        return match(true) {
            $difference > 1 => 'improving',
            $difference < -1 => 'declining',
            default => 'stable'
        };
    }

    public function getTrendIcon($trend)
    {
        return match($trend) {
            'improving' => '↗️',
            'declining' => '↘️',
            default => '→'
        };
    }

    public function getTrendColor($trend)
    {
        return match($trend) {
            'improving' => 'text-green-600',
            'declining' => 'text-red-600',
            default => 'text-gray-600'
        };
    }

    public function shouldShowSurveyReminder($surveyId)
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) return false;

        $lastResponse = WellBeingResponse::where('employee_id', $employee->id)
            ->where('survey_type', $surveyId)
            ->latest()
            ->first();

        if (!$lastResponse) return true;

        $survey = collect($this->surveys)->firstWhere('id', $surveyId);
        $frequency = $survey['frequency'] ?? 'daily';

        $daysSinceLast = now()->diffInDays($lastResponse->submitted_at);

        return match($frequency) {
            'daily' => $daysSinceLast >= 1,
            'weekly' => $daysSinceLast >= 7,
            'monthly' => $daysSinceLast >= 30,
            default => false
        };
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
                <h2 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-800 via-purple-500 to-pink-500">
                    {{ __('Well-being & Stress Monitoring') }}
                </h2>
            </div>
        </div>
    </div>

    <!-- Well-being Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @php
            $recentResponses = collect($this->wellBeingResponses)->take(10);
            $avgStress = $this->getAverageRating($recentResponses, 'stress_level');
            $avgMood = $this->getAverageRating($recentResponses, 'mood_rating');
            $avgSleep = $this->getAverageRating($recentResponses, 'sleep_quality');
            $stressTrend = $this->getRecentTrend($recentResponses, 'stress_level');
            $moodTrend = $this->getRecentTrend($recentResponses, 'mood_rating');
        @endphp

        <!-- Stress Level Card -->
        <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __('Stress Level') }}</h3>
                <span class="text-2xl {{ $this->getTrendColor($stressTrend) }}">{{ $this->getTrendIcon($stressTrend) }}</span>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-3xl font-bold text-{{ $this->getStressLevelColor($avgStress) }}-600">{{ $avgStress }}/10</div>
                <div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ $this->getStressLevelText($avgStress) }}</div>
                    <div class="text-xs text-gray-500">{{ __('Last 10 responses') }}</div>
                </div>
            </div>
        </div>

        <!-- Mood Rating Card -->
        <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __('Mood Rating') }}</h3>
                <span class="text-2xl {{ $this->getTrendColor($moodTrend) }}">{{ $this->getTrendIcon($moodTrend) }}</span>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-3xl font-bold text-{{ $this->getMoodColor($avgMood) }}-600">{{ $avgMood }}/10</div>
                <div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ $this->getMoodText($avgMood) }}</div>
                    <div class="text-xs text-gray-500">{{ __('Last 10 responses') }}</div>
                </div>
            </div>
        </div>

        <!-- Sleep Quality Card -->
        <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __('Sleep Quality') }}</h3>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-3xl font-bold text-blue-600">{{ $avgSleep }}/10</div>
                <div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        @if($avgSleep <= 3)
                            {{ __('Poor') }}
                        @elseif($avgSleep <= 7)
                            {{ __('Fair') }}
                        @else
                            {{ __('Good') }}
                        @endif
                    </div>
                    <div class="text-xs text-gray-500">{{ __('Last 10 responses') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Surveys -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">{{ __('Available Surveys') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($this->surveys as $survey)
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-800 dark:text-gray-200">{{ $survey['title'] }}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $survey['description'] }}</p>
                        </div>
                        @if($this->shouldShowSurveyReminder($survey['id']))
                            <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">{{ __('Due') }}</span>
                        @endif
                    </div>
                    <button
                        wire:click="startSurvey('{{ $survey['id'] }}')"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500"
                    >
                        {{ __('Take Survey') }}
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Survey Form Modal -->
    @if($this->showSurveyForm && $this->currentSurvey)
        <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ $this->currentSurvey['title'] }}</h3>
                <button wire:click="hideSurveyForm" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form wire:submit="submitSurvey" class="space-y-6">
                @if(in_array('stress_level', $this->currentSurvey['questions']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Stress Level (1-10)') }}
                        </label>
                        <input
                            type="range"
                            wire:model="stress_level"
                            min="1"
                            max="10"
                            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                        />
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>{{ __('Low') }}</span>
                            <span class="font-semibold">{{ $this->stress_level }}</span>
                            <span>{{ __('High') }}</span>
                        </div>
                    </div>
                @endif

                @if(in_array('mood_rating', $this->currentSurvey['questions']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Mood Rating (1-10)') }}
                        </label>
                        <input
                            type="range"
                            wire:model="mood_rating"
                            min="1"
                            max="10"
                            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                        />
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>{{ __('Poor') }}</span>
                            <span class="font-semibold">{{ $this->mood_rating }}</span>
                            <span>{{ __('Excellent') }}</span>
                        </div>
                    </div>
                @endif

                @if(in_array('sleep_quality', $this->currentSurvey['questions']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Sleep Quality (1-10)') }}
                        </label>
                        <input
                            type="range"
                            wire:model="sleep_quality"
                            min="1"
                            max="10"
                            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                        />
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>{{ __('Poor') }}</span>
                            <span class="font-semibold">{{ $this->sleep_quality }}</span>
                            <span>{{ __('Excellent') }}</span>
                        </div>
                    </div>
                @endif

                @if(in_array('work_satisfaction', $this->currentSurvey['questions']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Work Satisfaction (1-10)') }}
                        </label>
                        <input
                            type="range"
                            wire:model="work_satisfaction"
                            min="1"
                            max="10"
                            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                        />
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>{{ __('Low') }}</span>
                            <span class="font-semibold">{{ $this->work_satisfaction }}</span>
                            <span>{{ __('High') }}</span>
                        </div>
                    </div>
                @endif

                @if(in_array('workload_rating', $this->currentSurvey['questions']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Workload Rating (1-10)') }}
                        </label>
                        <input
                            type="range"
                            wire:model="workload_rating"
                            min="1"
                            max="10"
                            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                        />
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>{{ __('Light') }}</span>
                            <span class="font-semibold">{{ $this->workload_rating }}</span>
                            <span>{{ __('Heavy') }}</span>
                        </div>
                    </div>
                @endif

                @if(in_array('comments', $this->currentSurvey['questions']))
                    <div>
                        <flux:textarea
                            wire:model="comments"
                            :label="__('Additional Comments (Optional)')"
                            :placeholder="__('Share any thoughts or concerns about your well-being...')"
                            rows="4"
                        />
                    </div>
                @endif

                <div class="flex items-center gap-3">
                    <input
                        type="checkbox"
                        wire:model="anonymity_preference"
                        id="anonymity"
                        class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                    />
                    <label for="anonymity" class="text-sm text-gray-700 dark:text-gray-300">
                        {{ __('Submit anonymously (your identity will be hidden from HR reports)') }}
                    </label>
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        wire:click="hideSurveyForm"
                        class="px-4 py-2 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="bg-purple-600 hover:bg-purple-700 disabled:bg-purple-400 text-white px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500"
                    >
                        <span wire:loading.remove>{{ __('Submit Survey') }}</span>
                        <span wire:loading>{{ __('Submitting...') }}</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Recent Responses -->
    @if($this->wellBeingResponses->count() > 0)
        <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">{{ __('Recent Responses') }}</h3>
            <div class="space-y-4">
                @foreach($this->wellBeingResponses as $response)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h4 class="font-semibold text-gray-800 dark:text-gray-200">
                                    {{ collect($this->surveys)->firstWhere('id', $response->survey_type)['title'] ?? $response->survey_type }}
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $response->submitted_at->format('M j, Y \a\t g:i A') }}
                                </p>
                            </div>
                            @if($response->is_anonymous)
                                <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full">{{ __('Anonymous') }}</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                            @if(isset($response->responses['stress_level']))
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Stress:') }}</span>
                                    <span class="font-semibold">{{ $response->responses['stress_level'] }}/10</span>
                                </div>
                            @endif
                            @if(isset($response->responses['mood_rating']))
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Mood:') }}</span>
                                    <span class="font-semibold">{{ $response->responses['mood_rating'] }}/10</span>
                                </div>
                            @endif
                            @if(isset($response->responses['sleep_quality']))
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Sleep:') }}</span>
                                    <span class="font-semibold">{{ $response->responses['sleep_quality'] }}/10</span>
                                </div>
                            @endif
                            @if(isset($response->responses['work_satisfaction']))
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Satisfaction:') }}</span>
                                    <span class="font-semibold">{{ $response->responses['work_satisfaction'] }}/10</span>
                                </div>
                            @endif
                            @if(isset($response->responses['workload_rating']))
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Workload:') }}</span>
                                    <span class="font-semibold">{{ $response->responses['workload_rating'] }}/10</span>
                                </div>
                            @endif
                        </div>

                        @if(!empty($response->responses['comments']))
                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <strong>{{ __('Comments:') }}</strong> {{ $response->responses['comments'] }}
                                </p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
