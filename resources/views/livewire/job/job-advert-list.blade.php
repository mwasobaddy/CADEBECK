
<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\JobAdvert;
use Livewire\WithPagination;

new #[Layout('components.layouts.app.guest')] class extends Component {
    use WithPagination;

    public $search = '';
    public $filterStatus = '';
    public $showFilters = false;
    public $perPage = 12;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    public function getJobAdvertsProperty()
    {
        $query = JobAdvert::where('status', 'Published')
            ->whereDate('deadline', '>=', now()->toDateString());

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhere('requirements', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterStatus === 'urgent') {
            $query->whereDate('deadline', '<=', now()->addDays(7));
        } elseif ($this->filterStatus === 'new') {
            $query->where('created_at', '>=', now()->subDays(7));
        }

        return $query->orderBy('deadline')->paginate($this->perPage);
    }
};
?>

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

    <div class="relative max-w-7xl mx-auto px-4 py-8 lg:px-8 z-10">
        <!-- Header Section -->
        <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-2xl shadow-2xl p-8 mb-8 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
            <div class="text-center">
                <div class="flex items-center justify-center gap-3 mb-4">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M7.5 5.25a3 3 0 0 1 3-3h3a3 3 0 0 1 3 3v.205c.933.085 1.857.197 2.774.334 1.454.218 2.476 1.483 2.476 2.917v3.033c0 1.211-.734 2.352-1.936 2.752A24.726 24.726 0 0 1 12 15.75c-2.73 0-5.357-.442-7.814-1.259-1.202-.4-1.936-1.541-1.936-2.752V8.706c0-1.434 1.022-2.7 2.476-2.917A48.814 48.814 0 0 1 7.5 5.455V5.25Zm7.5 0v.09a49.488 49.488 0 0 0-6 0v-.09a1.5 1.5 0 0 1 1.5-1.5h3a1.5 1.5 0 0 1 1.5 1.5Zm-3 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
                        <path d="M3 18.4v-2.796a4.3 4.3 0 0 0 .713.31A26.226 26.226 0 0 0 12 17.25c2.892 0 5.68-.468 8.287-1.335.252-.084.49-.189.713-.311V18.4c0 1.452-1.047 2.728-2.523 2.923-2.12.282-4.282.427-6.477.427a49.19 49.19 0 0 1-6.477-.427C4.047 21.128 3 19.852 3 18.4Z" />
                    </svg>
                    <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg">
                        {{ __('Current Job Openings') }}
                    </h1>
                </div>
                <p class="text-lg text-zinc-600 dark:text-zinc-300 font-medium max-w-3xl mx-auto">
                    {{ __('Discover exciting career opportunities at CADEBECK. Join our team and make a difference in the world of HR technology.') }}
                </p>
                <div class="w-24 h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500 mx-auto mt-4"></div>
            </div>
        </div>

        <!-- Job Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-lg p-6 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m-8 0V6a2 2 0 00-2 2v6"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-zinc-800 dark:text-white">{{ $this->jobAdverts->total() }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Open Positions') }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-lg p-6 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-zinc-800 dark:text-white">{{ $this->jobAdverts->where('deadline', '<=', now()->addDays(7))->count() }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Closing Soon') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-lg p-6 mb-8 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
            <div class="flex flex-wrap gap-4 items-center justify-between">
                <!-- Search Bar -->
                <div class="relative flex-1 min-w-[300px]">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-5 h-5 text-blue-400 dark:text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                        </svg>
                    </span>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        class="w-full pl-10 pr-4 py-3 rounded-full border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white transition shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md"
                        placeholder="{{ __('Search jobs by title, description, or requirements...') }}">
                </div>

                <!-- Filter Toggle Button -->
                <button type="button" wire:click="toggleFilters"
                    class="flex items-center gap-2 px-4 py-3 rounded-full border border-blue-200 dark:border-indigo-700 bg-white/80 dark:bg-zinc-900/80 text-blue-600 dark:text-indigo-300 hover:bg-blue-50/80 dark:hover:bg-zinc-800/80 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/>
                    </svg>
                    <span>{{ __('Filters') }}</span>
                    <svg class="w-4 h-4 transition-transform {{ $showFilters ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>
            </div>

            <!-- Collapsible Filters -->
            @if($showFilters)
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 animate-fade-in">
                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Job Status') }}</label>
                        <select wire:model.live="filterStatus"
                            class="w-full px-3 py-2 rounded-full border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                            <option value="">{{ __('All Jobs') }}</option>
                            <option value="urgent">{{ __('Closing Soon (7 days)') }}</option>
                            <option value="new">{{ __('Recently Posted') }}</option>
                        </select>
                    </div>

                    <!-- Results Per Page -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Results Per Page') }}</label>
                        <select wire:model.live="perPage"
                            class="w-full px-3 py-2 rounded-full border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                            <option value="6">6</option>
                            <option value="12">12</option>
                            <option value="18">18</option>
                            <option value="24">24</option>
                        </select>
                    </div>
                </div>

                <!-- Active Filters Summary -->
                @if($search || $filterStatus)
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Active filters:') }}</span>
                        
                        @if($search)
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs">
                                {{ __('Search') }}: "{{ $search }}"
                                <button wire:click="$set('search', '')" class="ml-1 hover:text-blue-600">×</button>
                            </span>
                        @endif
                        
                        @if($filterStatus)
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded-full text-xs">
                                {{ __('Status') }}: {{ $filterStatus === 'urgent' ? __('Closing Soon') : __('Recently Posted') }}
                                <button wire:click="$set('filterStatus', '')" class="ml-1 hover:text-purple-600">×</button>
                            </span>
                        @endif
                        
                        <button wire:click="$set('search', ''); $set('filterStatus', '')" 
                            class="text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                            {{ __('Clear all') }}
                        </button>
                    </div>
                @endif
            @endif
        </div>

        <!-- Job Listings Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse ($this->jobAdverts as $advert)
                <div class="group bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40 overflow-hidden transform hover:-translate-y-1">
                    <!-- Card Header -->
                    <div class="bg-gradient-to-r from-blue-500/10 via-green-500/10 to-purple-500/10 p-4 border-b border-blue-100 dark:border-zinc-700">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h2 class="text-xl font-bold text-zinc-800 dark:text-white mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-200">
                                    <a href="{{ url('/careers/' . $advert->slug) }}" class="hover:underline" wire:navigate>
                                        {{ $advert->title }}
                                    </a>
                                </h2>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-green-100 to-green-200 text-green-800 dark:from-green-900/30 dark:to-green-800/30 dark:text-green-300">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $advert->status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-6">
                        <p class="text-zinc-600 dark:text-zinc-300 text-sm leading-relaxed mb-4 line-clamp-3">
                            {{ Str::limit($advert->description, 150) }}
                        </p>

                        <!-- Job Details -->
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>{{ __('Deadline') }}: {{ \Carbon\Carbon::parse($advert->deadline)->format('M d, Y') }}</span>
                            </div>
                            
                            <div class="flex items-center gap-2 text-sm">
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
                                <svg class="w-4 h-4 {{ $isExpired ? 'text-red-500' : ($isUrgent ? 'text-orange-500' : 'text-green-500') }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="{{ $isExpired ? 'text-red-600 dark:text-red-400 font-semibold' : ($isUrgent ? 'text-orange-600 dark:text-orange-400 font-semibold' : 'text-green-600 dark:text-green-400') }}">
                                    {{ $deadlineLabel }}
                                </span>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="flex items-center justify-between">
                            <a href="{{ url('/careers/' . $advert->slug) }}" 
                               wire:navigate
                               class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-full transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <span>{{ __('View Details') }}</span>
                                <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </a>
                            
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Posted') }} {{ \Carbon\Carbon::parse($advert->created_at)->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-1 md:col-span-2 lg:col-span-3">
                    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-lg p-12 text-center border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
                        <svg class="w-16 h-16 text-zinc-400 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m-8 0V6a2 2 0 00-2 2v6"/>
                        </svg>
                        <h3 class="text-xl font-semibold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('No Current Openings') }}</h3>
                        <p class="text-zinc-500 dark:text-zinc-400 max-w-md mx-auto">
                            {{ __('We don\'t have any job openings at this time, but we\'re always looking for talented individuals. Check back soon or contact us directly.') }}
                        </p>
                        <div class="mt-6">
                            <a href="mailto:careers@cadebeck.com" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-semibold rounded-full transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                {{ __('Contact Us') }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($this->jobAdverts->hasPages())
            <div class="mt-12 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-lg p-6 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
                {{ $this->jobAdverts->links() }}
            </div>
        @endif

        <!-- Call to Action Footer -->
        <div class="mt-12 bg-gradient-to-r from-blue-50/80 via-indigo-50/80 to-purple-50/80 dark:from-zinc-800/50 dark:via-zinc-700/50 dark:to-zinc-800/50 backdrop-blur-xl rounded-2xl shadow-lg p-8 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40 text-center">
            <h3 class="text-2xl font-bold text-zinc-800 dark:text-white mb-4">{{ __('Don\'t See What You\'re Looking For?') }}</h3>
            <p class="text-zinc-600 dark:text-zinc-300 mb-6 max-w-2xl mx-auto">
                {{ __('We\'re always interested in connecting with talented professionals. Send us your resume and let us know how you can contribute to our team.') }}
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="mailto:careers@cadebeck.com" class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-full transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    {{ __('Send Resume') }}
                </a>
                <a href="#" class="inline-flex items-center gap-2 px-8 py-3 border-2 border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400 font-semibold rounded-full hover:bg-blue-600 hover:text-white dark:hover:bg-blue-400 dark:hover:text-zinc-900 transition-all duration-200 transform hover:scale-105">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ __('Learn More') }}
                </a>
            </div>
        </div>
    </div>
</div>
