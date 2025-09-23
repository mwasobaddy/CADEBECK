<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\JobAdvert;

new #[Layout('components.layouts.app.guest')] class extends Component {
    public $advert;

    public function mount($slug): void
    {
        $this->advert = JobAdvert::where('slug', $slug)->where('status', 'Published')->firstOrFail();
    }
}; ?>

<div class="relative min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-zinc-900 dark:via-zinc-800 dark:to-zinc-900">
    <!-- SVG Blobs Background -->
    <svg class="fixed -top-24 right-32 w-96 h-96 opacity-20 blur-2xl pointer-events-none z-0" viewBox="0 0 400 400" fill="none">
        <ellipse cx="200" cy="200" rx="180" ry="120" fill="url(#blob1)" />
        <defs>
            <radialGradient id="blob1" cx="0" cy="0" r="1" gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                <stop stop-color="#38bdf8" />
                <stop offset="1" stop-color="#6366f1" />
            </radialGradient>
        </defs>
    </svg>
    <svg class="fixed -bottom-24 -left-32 w-96 h-96 opacity-20 blur-2xl pointer-events-none z-0" viewBox="0 0 400 400" fill="none">
        <ellipse cx="200" cy="200" rx="160" ry="100" fill="url(#blob2)" />
        <defs>
            <radialGradient id="blob2" cx="0" cy="0" r="1" gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                <stop stop-color="#34d399" />
                <stop offset="1" stop-color="#f472b6" />
            </radialGradient>
        </defs>
    </svg>

    <div class="relative max-w-4xl mx-auto px-4 py-8 lg:px-8 z-10">
        <!-- Breadcrumb Navigation -->
        <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
            <nav class="flex items-center gap-2 text-sm">
                <a href="{{ url('/') }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors" wire:navigate>
                    {{ __('Careers') }}
                </a>
                <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-zinc-600 dark:text-zinc-300 font-medium">{{ $advert->title }}</span>
            </nav>
        </div>

        <!-- Main Content Card -->
        <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-2xl shadow-2xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40 overflow-hidden">
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-blue-500/10 via-green-500/10 to-purple-500/10 p-8 border-b border-blue-100 dark:border-zinc-700">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="flex-1">
                        <h1 class="text-3xl lg:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg mb-4">
                            {{ $advert->title }}
                        </h1>
                        <div class="flex flex-wrap items-center gap-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gradient-to-r from-green-100 to-green-200 text-green-800 dark:from-green-900/30 dark:to-green-800/30 dark:text-green-300">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                {{ $advert->status }}
                            </span>
                            
                            @php
                                $deadline = \Carbon\Carbon::parse($advert->deadline);
                                $now = now();
                                $isExpired = $deadline->isPast();
                                $isToday = $deadline->isToday();
                                $daysLeft = (!$isExpired && !$isToday) ? (int) $now->diffInDays($deadline, false) : 0;
                                $isUrgent = !$isExpired && !$isToday && $daysLeft <= 7;
                                $deadlineLabel = '';
                                if ($isExpired) {
                                    $deadlineLabel = __('Position Closed');
                                } elseif ($isToday) {
                                    $deadlineLabel = __('Closes Today');
                                } elseif ($daysLeft === 1) {
                                    $deadlineLabel = '1 ' . __('day remaining');
                                } elseif ($daysLeft > 1) {
                                    $deadlineLabel = $daysLeft . ' ' . __('days remaining');
                                }
                            @endphp
                            
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4 {{ $isExpired ? 'text-red-500' : ($isUrgent ? 'text-orange-500' : 'text-blue-500') }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="{{ $isExpired ? 'text-red-600 dark:text-red-400' : ($isUrgent ? 'text-orange-600 dark:text-orange-400' : 'text-blue-600 dark:text-blue-400') }} font-medium">
                                    {{ __('Deadline') }}: {{ $deadline->format('M d, Y') }}
                                </span>
                            </div>
                            
                            <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="@if($isExpired || $isToday) text-red-600 dark:text-red-400 font-semibold @elseif($isUrgent) text-orange-600 dark:text-orange-400 font-semibold @else text-green-600 dark:text-green-400 @endif">
                                    {{ $deadlineLabel }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    @if(!$isExpired)
                        <div class="lg:flex-shrink-0">
                            <a href="{{ route('careers.apply', $advert->slug) }}" 
                               wire:navigate
                               class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-full transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/>
                                    <circle cx="12" cy="12" r="10"/>
                                </svg>
                                <span>{{ __('Apply Now') }}</span>
                                <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Job Description Content -->
            <div class="p-8">
                <div class="prose prose-lg max-w-none">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-zinc-800 dark:text-white mb-4 flex items-center gap-2">
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            {{ __('Job Description') }}
                        </h2>
                        <div class="text-zinc-700 dark:text-zinc-300 leading-relaxed whitespace-pre-line">
                            {{ $advert->description }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Call to Action Footer -->
            @if(!$isExpired)
                <div class="bg-gradient-to-r from-blue-50/80 via-indigo-50/80 to-purple-50/80 dark:from-zinc-800/50 dark:via-zinc-700/50 dark:to-zinc-800/50 p-8 border-t border-blue-100 dark:border-zinc-700">
                    <div class="text-center">
                        <h3 class="text-xl font-bold text-zinc-800 dark:text-white mb-3">{{ __('Ready to Join Our Team?') }}</h3>
                        <p class="text-zinc-600 dark:text-zinc-300 mb-6 max-w-2xl mx-auto">
                            {{ __('Take the next step in your career journey. We look forward to reviewing your application and learning more about what you can bring to our team.') }}
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="{{ route('careers.apply', $advert->slug) }}" 
                               wire:navigate
                               class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-full transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                {{ __('Submit Application') }}
                            </a>
                            <a href="{{ url('/') }}" 
                               wire:navigate
                               class="inline-flex items-center gap-2 px-8 py-3 border-2 border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400 font-semibold rounded-full hover:bg-blue-600 hover:text-white dark:hover:bg-blue-400 dark:hover:text-zinc-900 transition-all duration-200 transform hover:scale-105">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                {{ __('View All Jobs') }}
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-gradient-to-r from-red-50/80 via-orange-50/80 to-red-50/80 dark:from-red-900/20 dark:via-orange-900/20 dark:to-red-900/20 p-8 border-t border-red-200 dark:border-red-800">
                    <div class="text-center">
                        <svg class="w-16 h-16 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                        <h3 class="text-xl font-bold text-red-600 dark:text-red-400 mb-3">{{ __('Application Period Closed') }}</h3>
                        <p class="text-red-700 dark:text-red-300 mb-6 max-w-md mx-auto">
                            {{ __('The deadline for this position has passed. Please check our other available opportunities.') }}
                        </p>
                        <a href="{{ url('/') }}" 
                           wire:navigate
                           class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-semibold rounded-full transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m-8 0V6a2 2 0 00-2 2v6"/>
                            </svg>
                            {{ __('View Open Positions') }}
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
