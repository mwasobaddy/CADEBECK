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

<div class="max-w-5xl mx-auto p-6">
    <div class="flex items-center gap-3 mb-8">
        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"></path>
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
        </svg>
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">{{ __('Create Job Advert') }}</h1>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-lg p-8 mb-10 transition-all duration-300 hover:shadow-xl">
        <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Title') }}</label>
                <input type="text" wire:model="form.title" class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:outline-none bg-gray-50 dark:bg-zinc-800 transition" required />
            </div>
            <div>
                <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Deadline') }}</label>
                <input type="date" wire:model="form.deadline" class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:outline-none bg-gray-50 dark:bg-zinc-800 transition" required />
            </div>
            <div class="md:col-span-2">
                <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Description') }}</label>
                <textarea wire:model="form.description" class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-3 min-h-[120px] focus:ring-2 focus:ring-blue-500 focus:outline-none bg-gray-50 dark:bg-zinc-800 transition" required></textarea>
            </div>
            <div>
                <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Status') }}</label>
                <select wire:model="form.status" class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:outline-none bg-gray-50 dark:bg-zinc-800 transition">
                    <option value="Draft">{{ __('Draft') }}</option>
                    <option value="Published">{{ __('Published') }}</option>
                    <option value="Expired">{{ __('Expired') }}</option>
                </select>
            </div>
            <div class="flex items-end gap-3">
                <button type="submit"
                    class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                    </svg>
                    {{ $editing ? __('Update') : __('Create') }}
                </button>
                <button type="button" wire:click="resetForm"
                    class="bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-200 px-6 py-2 rounded-lg font-semibold shadow transition-all duration-200">
                    {{ __('Reset') }}
                </button>
            </div>
        </form>
    </div>
</div>
