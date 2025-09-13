
<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\JobAdvert;

new #[Layout('components.layouts.app.guest')] class extends Component {};
?>

@php
    $jobAdverts = App\Models\JobAdvert::where('status', 'Published')
        ->whereDate('deadline', '>=', now()->toDateString())
        ->orderBy('deadline')
        ->paginate(12);
@endphp

<div class="p-6 mx-auto w-full h-full [:where(&)]:max-w-7xl px-6 lg:px-8 flex flex-col">
    <h1 class="text-2xl font-bold mb-4">{{ __('Current Job Openings') }}</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($jobAdverts as $advert)
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-4">
                <h2 class="text-lg font-semibold mb-2">
                    <a href="{{ url('/careers/' . $advert->slug) }}" class="text-blue-600 hover:underline" wire:navigate>
                        {{ $advert->title }}
                    </a>
                </h2>
                <p class="mb-2 text-zinc-600 dark:text-zinc-300">{{ Str::limit($advert->description, 120) }}</p>
                <div class="text-sm text-zinc-500 mb-1">{{ __('Deadline') }}: {{ $advert->deadline }}</div>
                <span class="inline-block px-2 py-1 rounded bg-green-100 text-green-800 text-xs">{{ $advert->status }}</span>
            </div>
        @empty
            <div class="col-span-3 text-center text-zinc-500">{{ __('No job openings at this time.') }}</div>
        @endforelse
    </div>
    <div class="mt-6">
        {{ $jobAdverts->links() }}
    </div>
</div>
