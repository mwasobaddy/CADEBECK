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

<div class="p-6 max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold mb-2">{{ $advert->title }}</h1>
    <div class="mb-4 text-zinc-600 dark:text-zinc-300">{{ $advert->description }}</div>
    <div class="mb-2 text-sm text-zinc-500">{{ __('Deadline') }}: {{ $advert->deadline }}</div>
    <span class="inline-block px-2 py-1 rounded bg-green-100 text-green-800 text-xs mb-4">{{ $advert->status }}</span>
    <a href="{{ route('careers.apply', $advert->slug) }}" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" wire:navigate>
        {{ __('Apply Now') }}
    </a>
</div>
