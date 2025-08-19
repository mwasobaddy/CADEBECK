<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\JobAdvert;
use App\Models\Application;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app.guest')] class extends Component {
    use WithFileUploads;

    public ?JobAdvert $advert = null;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public $cv = null;
    public string $cover_letter = '';
    public bool $submitted = false;

    public function mount($slug): void
    {
        $this->advert = JobAdvert::where('slug', $slug)->where('status', 'Published')->firstOrFail();
    }

    public function submit(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'cv' => ['required', 'file', 'mimes:pdf', 'max:2048'],
            'cover_letter' => ['required', 'string', 'max:600'],
        ]);

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
        Session::flash('status', __('Your application has been submitted.'));
        $this->resetExcept('advert', 'submitted');
    }
}; ?>

<div class="p-6 max-w-xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">{{ __('Apply for') }} {{ $advert->title }}</h1>
    @if ($submitted)
        <div class="bg-green-100 text-green-800 p-4 rounded mb-4">{{ __('Your application has been submitted.') }}</div>
    @else
        <form wire:submit.prevent="submit" class="grid grid-cols-1 gap-4">
            <div>
                <label class="block mb-1 font-medium">{{ __('Name') }}</label>
                <input type="text" wire:model="name" class="w-full border rounded p-2" required />
            </div>
            <div>
                <label class="block mb-1 font-medium">{{ __('Email') }}</label>
                <input type="email" wire:model="email" class="w-full border rounded p-2" required />
            </div>
            <div>
                <label class="block mb-1 font-medium">{{ __('Phone') }}</label>
                <input type="text" wire:model="phone" class="w-full border rounded p-2" required />
            </div>
            <div>
                <label class="block mb-1 font-medium">{{ __('CV (PDF only)') }}</label>
                <input type="file" wire:model="cv" accept="application/pdf" class="w-full border rounded p-2" required />
            </div>
            <div>
                <label class="block mb-1 font-medium">{{ __('Cover Letter') }}</label>
                <textarea wire:model="cover_letter" maxlength="600" class="w-full border rounded p-2" required></textarea>
                <div class="text-xs text-zinc-500">{{ __('Max 600 characters') }}</div>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">{{ __('Submit Application') }}</button>
        </form>
    @endif
</div>
