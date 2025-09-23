<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\Attributes\Url;
use App\Models\JobAdvert;
use App\Models\Application;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app.guest')] class extends Component {
    use WithFileUploads;

    #[Url]
    public $slug;

    public ?JobAdvert $advert = null;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public $cv = null;
    public string $cover_letter = '';
    public bool $submitted = false;
    public bool $isSubmitting = false;

    public function mount(): void
    {
        $this->advert = JobAdvert::where('slug', $this->slug)->where('status', 'Published')->firstOrFail();
    }

    public function submit(): void
    {
        $this->isSubmitting = true;
        
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'cv' => ['required', 'file', 'mimes:pdf', 'max:2048'],
            'cover_letter' => ['required', 'string', 'max:600'],
        ]);

        // Check if user has already applied for this job
        $existingApplication = Application::where('job_advert_id', $this->advert->id)
            ->where('email', $this->email)
            ->first();

        if ($existingApplication) {
            $this->dispatch('notify', ['type' => 'error', 'message' => __('You have already submitted an application for this position.')]);
            $this->isSubmitting = false;
            return;
        }

        $cv_blob = file_get_contents($this->cv->getRealPath());

        Application::create([
            'job_advert_id' => $this->advert->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'cv_blob' => $cv_blob,
            'cover_letter' => $this->cover_letter,
            'status' => 'Pending',
            'submitted_at' => now(),
        ]);

        $this->submitted = true;
        $this->isSubmitting = false;
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Your application has been submitted.')]);
        $this->resetExcept('advert', 'submitted');
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        $this->cv = null;
        $this->cover_letter = '';
    }

    public function removeCv(): void
    {
        $this->cv = null;
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
                <a href="{{ route('careers.details', $advert->slug) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors" wire:navigate>
                    {{ $advert->title }}
                </a>
                <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-zinc-600 dark:text-zinc-300 font-medium">{{ __('Apply') }}</span>
            </nav>
        </div>

        @if ($submitted)
            <!-- Success State -->
            <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-2xl shadow-2xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40 overflow-hidden">
                <div class="bg-gradient-to-r from-green-500/10 via-emerald-500/10 to-teal-500/10 p-8 text-center">
                    <div class="mx-auto w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-emerald-500 tracking-tight drop-shadow-lg mb-4">
                        {{ __('Application Submitted Successfully!') }}
                    </h1>
                    <p class="text-lg text-zinc-700 dark:text-zinc-300 mb-8 max-w-2xl mx-auto leading-relaxed">
                        {{ __('Thank you for your interest in joining our team. We have received your application and will review it carefully. We will contact you within the next few business days regarding the next steps.') }}
                    </p>
                    
                    <div class="bg-blue-50/60 dark:bg-blue-900/20 rounded-xl p-6 mb-8 border border-blue-200 dark:border-blue-800">
                        <h3 class="text-lg font-bold text-blue-800 dark:text-blue-300 mb-3">{{ __('What happens next?') }}</h3>
                        <div class="grid md:grid-cols-3 gap-4 text-sm">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 dark:text-blue-400 font-bold">1</span>
                                </div>
                                <span class="text-blue-700 dark:text-blue-300">{{ __('Application Review') }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 dark:text-blue-400 font-bold">2</span>
                                </div>
                                <span class="text-blue-700 dark:text-blue-300">{{ __('Initial Screening') }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 dark:text-blue-400 font-bold">3</span>
                                </div>
                                <span class="text-blue-700 dark:text-blue-300">{{ __('Interview Process') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ url('/') }}" 
                           wire:navigate
                           class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-full transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m-8 0V6a2 2 0 00-2 2v6"/>
                            </svg>
                            {{ __('View More Opportunities') }}
                        </a>
                        <a href="{{ route('careers.details', $advert->slug) }}" 
                           wire:navigate
                           class="inline-flex items-center gap-2 px-8 py-3 border-2 border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400 font-semibold rounded-full hover:bg-blue-600 hover:text-white dark:hover:bg-blue-400 dark:hover:text-zinc-900 transition-all duration-200 transform hover:scale-105">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            {{ __('Back to Job Details') }}
                        </a>
                    </div>
                </div>
            </div>
        @else
            <!-- Application Form -->
            <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-2xl shadow-2xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40 overflow-hidden">
                <!-- Header Section -->
                <div class="bg-gradient-to-r from-blue-500/10 via-green-500/10 to-purple-500/10 p-8 border-b border-blue-100 dark:border-zinc-700">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg">
                                {{ __('Apply for') }} {{ $advert->title }}
                            </h1>
                            <p class="text-zinc-600 dark:text-zinc-400 mt-2">{{ __('Complete the form below to submit your application') }}</p>
                        </div>
                    </div>
                    
                    <!-- Job Summary Card -->
                    <div class="bg-white/40 dark:bg-zinc-800/40 backdrop-blur-sm rounded-xl p-4 border border-blue-100 dark:border-zinc-600">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex-1">
                                <h3 class="font-semibold text-zinc-800 dark:text-white mb-1">{{ $advert->title }}</h3>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400 line-clamp-2">{{ Str::limit($advert->description, 120) }}</p>
                            </div>
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
                                <span class="{{ $isExpired ? 'text-red-600 dark:text-red-400 font-semibold' : ($isUrgent ? 'text-orange-600 dark:text-orange-400 font-semibold' : 'text-blue-600 dark:text-blue-400') }}">
                                    {{ $deadlineLabel }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application Form -->
                <div class="p-8">
                    <form wire:submit.prevent="submit" class="space-y-8">
                        <!-- Personal Information Section -->
                        <div>
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-8 h-8 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <h2 class="text-xl font-bold text-zinc-800 dark:text-white">{{ __('Personal Information') }}</h2>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <flux:input
                                        wire:model="name"
                                        :label="__('Full Name')"
                                        type="text"
                                        required
                                        autocomplete="name"
                                        :placeholder="__('Enter your full name')"
                                    />
                                </div>
                                <div>
                                    <flux:input
                                        wire:model="email"
                                        :label="__('Email Address')"
                                        type="email"
                                        required
                                        autocomplete="email"
                                        :placeholder="__('your.email@example.com')"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <flux:input
                                        wire:model="phone"
                                        :label="__('Phone Number')"
                                        type="tel"
                                        required
                                        autocomplete="tel"
                                        :placeholder="__('Enter your phone number')"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Documents Section -->
                        <div>
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <h2 class="text-xl font-bold text-zinc-800 dark:text-white">{{ __('Documents') }}</h2>
                            </div>
                            
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                        {{ __('Resume/CV') }} <span class="text-red-500">*</span>
                                    </label>
                                    
                                    @if($cv)
                                        <!-- Uploaded File Display -->
                                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-medium text-green-800 dark:text-green-300">{{ $cv->getClientOriginalName() }}</p>
                                                        <p class="text-xs text-green-600 dark:text-green-400">
                                                            {{ number_format($cv->getSize() / 1024, 1) }} KB • PDF
                                                        </p>
                                                    </div>
                                                </div>
                                                <button 
                                                    type="button" 
                                                    wire:click="removeCv"
                                                    class="p-2 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-full transition-colors"
                                                    title="{{ __('Remove file') }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <!-- Upload Area -->
                                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-zinc-300 dark:border-zinc-600 border-dashed rounded-xl hover:border-blue-400 dark:hover:border-blue-500 transition-colors">
                                            <div class="space-y-1 text-center">
                                                <svg class="mx-auto h-12 w-12 text-zinc-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <div class="flex text-sm text-zinc-600 dark:text-zinc-400">
                                                    <label for="cv-upload" class="relative cursor-pointer bg-white dark:bg-zinc-800 rounded-md font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                                        <span>{{ __('Upload a file') }}</span>
                                                        <input id="cv-upload" wire:model="cv" type="file" accept="application/pdf" class="sr-only" required>
                                                    </label>
                                                    <p class="pl-1">{{ __('or drag and drop') }}</p>
                                                </div>
                                                <p class="text-xs text-zinc-500">{{ __('PDF files only, up to 2MB') }}</p>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @error('cv') 
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Cover Letter Section -->
                        <div>
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </div>
                                <h2 class="text-xl font-bold text-zinc-800 dark:text-white">{{ __('Cover Letter') }}</h2>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    {{ __('Tell us about yourself') }} <span class="text-red-500">*</span>
                                </label>
                                <textarea 
                                    wire:model="cover_letter" 
                                    maxlength="600" 
                                    rows="6"
                                    required
                                    placeholder="{{ __('Write a brief cover letter explaining why you are interested in this position and what makes you a great fit for our team...') }}"
                                    class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-500 dark:placeholder-zinc-400 transition-colors">
                                </textarea>
                                <div class="flex justify-between items-center mt-2">
                                    @error('cover_letter') 
                                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @else
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Share your passion and relevant experience') }}</p>
                                    @enderror
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ strlen($cover_letter) }}/600
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Section -->
                        <div class="bg-gradient-to-r from-blue-50/80 via-indigo-50/80 to-purple-50/80 dark:from-zinc-800/50 dark:via-zinc-700/50 dark:to-zinc-800/50 rounded-xl p-6 border border-blue-100 dark:border-zinc-700">
                            <div class="flex flex-col gap-4 justify-between">
                                <div class="text-center sm:text-left">
                                    <h3 class="text-lg font-semibold text-zinc-800 dark:text-white mb-1">{{ __('Ready to Submit?') }}</h3>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Review your information before submitting your application') }}</p>
                                </div>
                                <div class="flex gap-3">
                                    <button 
                                        type="button" 
                                        wire:click="resetForm"
                                        class="px-6 py-3 border-2 border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 font-semibold rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                                        {{ __('Reset Form') }}
                                    </button>
                                    <button 
                                        type="submit"
                                        wire:loading.attr="disabled"
                                        wire:target="submit"
                                        class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 disabled:from-blue-400 disabled:to-blue-500 text-white font-semibold rounded-full transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl disabled:transform-none disabled:shadow-md">
                                        <span wire:loading.remove wire:target="submit">{{ __('Submit Application') }}</span>
                                        <span wire:loading wire:target="submit">{{ __('Submitting...') }}</span>
                                        <svg wire:loading.remove wire:target="submit" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                        </svg>
                                        <svg wire:loading wire:target="submit" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
