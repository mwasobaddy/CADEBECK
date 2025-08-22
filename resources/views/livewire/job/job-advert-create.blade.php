<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\JobAdvert;

new #[Layout('components.layouts.app')] class extends Component {
    public array $form = [
        'title' => '',
        'description' => '',
        'deadline' => '',
        'status' => 'Draft',
    ];
    public ?JobAdvert $advert = null;
    public bool $editing = false;

    public function mount($slug = null): void
    {
        if ($slug) {
            $this->advert = JobAdvert::where('slug', $slug)->firstOrFail();
            $this->form = $this->advert->only(['title', 'description', 'deadline', 'status']);
            $this->editing = true;
        }
    }

    public function save(): void
    {
        $this->validate([
            'form.title' => ['required', 'string', 'max:255'],
            'form.description' => ['required', 'string'],
            'form.deadline' => ['required', 'date'],
            'form.status' => ['required', 'in:Published,Expired,Draft'],
        ]);

        if ($this->editing && $this->advert) {
            $this->advert->update($this->form);
            session()->flash('status', __('Job advert updated successfully.'));
        } else {
            $slugBase = Str::slug($this->form['title']);
            $slug = $slugBase;
            $i = 2;
            while (JobAdvert::where('slug', $slug)->exists()) {
                $slug = $slugBase . '-' . $i;
                $i++;
            }
            $data = [
                'title' => $this->form['title'],
                'slug' => $slug,
                'description' => $this->form['description'],
                'deadline' => $this->form['deadline'],
                'status' => $this->form['status'],
                'posted_by' => Auth::id(),
            ];
            JobAdvert::create($data);
            session()->flash('status', __('Job advert created successfully.'));
        }
        $this->redirectRoute('admin.job-adverts');
    }

    public function resetForm(): void
    {
        if ($this->editing && $this->advert) {
            $this->form = $this->advert->only(['title', 'description', 'deadline', 'status']);
        } else {
            $this->form = [
                'title' => '',
                'description' => '',
                'deadline' => '',
                'status' => 'Draft',
            ];
        }
    }
}; ?>


<div class="relative max-w-6xl mx-auto px-4 py-8">
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
                <a href="{{ route('job.job-adverts') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('job.job-adverts') ? 'bg-green-600 dark:bg-green-800 text-white dark:text-zinc-200 border-none' : '' }}">
                    {{ __('Job Advert List') }}
                </a>
                <a href="{{ route('job.job-adverts.create') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('job.job-adverts.create') ? 'bg-green-600 dark:bg-green-800 text-white dark:text-zinc-200 border-none' : '' }}">
                    {{ $editing ? __('Edit Advert') : __('Create Advert') }}
                </a>
            </div>
        </nav>
    </div>

    <!-- Card Container for Form -->
    <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex items-center gap-3 mb-8">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"></path>
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
            </svg>
            <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg">
                {{ $editing ? __('Edit Job Advert') : __('Create Job Advert') }}
            </h1>
        </div>
        <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Title') }}</label>
                <input type="text" wire:model="form.title" class="w-full px-4 py-3 rounded-2xl border border-gray-300 dark:border-zinc-700 focus:ring-2 focus:ring-gray-400 dark:bg-zinc-800/80 dark:text-white transition shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md" required />
            </div>
            <div>
                <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Deadline') }}</label>
                <input type="date" wire:model="form.deadline" class="w-full px-4 py-3 rounded-2xl border border-gray-300 dark:border-zinc-700 focus:ring-2 focus:ring-gray-400 dark:bg-zinc-800/80 dark:text-white transition shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md" required />
            </div>
            <div class="md:col-span-2">
                <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Description') }}</label>
                <textarea wire:model="form.description" class="w-full px-4 py-3 rounded-2xl border border-gray-300 dark:border-zinc-700 focus:ring-2 focus:ring-gray-400 dark:bg-zinc-800/80 dark:text-white min-h-[120px] transition shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md" required></textarea>
            </div>
            <div>
                <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Status') }}</label>
                <select wire:model="form.status" class="w-full px-4 py-3 rounded-2xl border border-gray-300 dark:border-zinc-700 focus:ring-2 focus:ring-gray-400 dark:bg-zinc-800/80 dark:text-white transition shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                    <option value="Draft">{{ __('Draft') }}</option>
                    <option value="Published">{{ __('Published') }}</option>
                    <option value="Expired">{{ __('Expired') }}</option>
                </select>
            </div>
            <div class="flex items-end justify-end gap-3">
                <button type="submit"
                    class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500">
                    {{ $editing ? __('Update') : __('Create') }}
                    <flux:icon name="check" class="w-5 h-5" />
                </button>
                <button type="button" wire:click="resetForm"
                    class="flex items-center gap-2 bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-200 px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                    {{ __('Reset') }}
                    <flux:icon name="arrow-path-rounded-square" class="w-5 h-5" />
                </button>
            </div>
        </form>
    </div>
</div>
