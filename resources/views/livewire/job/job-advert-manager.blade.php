<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\JobAdvert;

new #[Layout('components.layouts.app')] class extends Component {
    public ?int $editingId = null;
    public $jobAdverts;

    public function mount(): void
    {
        $this->loadJobAdverts();
    }

    public function loadJobAdverts(): void
    {
        $this->jobAdverts = JobAdvert::orderByDesc('created_at')->get();
    }

    public function edit($id): void
    {
        $advert = JobAdvert::findOrFail($id);
        $this->form = $advert->only(['title', 'description', 'deadline', 'status']);
        $this->editingId = $id;
    }

    public function delete($id): void
    {
        JobAdvert::findOrFail($id)->delete();
        $this->loadJobAdverts();
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->form = [
            'title' => '',
            'description' => '',
            'deadline' => '',
            'status' => 'Draft',
        ];
        $this->editingId = null;
    }
}; ?>

<div class="max-w-5xl mx-auto p-6">
    <!-- Page Title with Icon and Create Button -->
    <div class="flex items-center gap-3 mb-8 justify-between">
        <div class="flex items-center gap-3">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"></path>
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
            </svg>
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">{{ __('Manage Job Adverts') }}</h1>
        </div>
        <a href="{{ route('admin.job-adverts.create') }}" class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
            </svg>
            {{ __('Create New') }}
        </a>
    </div>


    <!-- Card for Table -->
    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-lg p-8 transition-all duration-300 hover:shadow-xl">
        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
            </svg>
            {{ __('Job Advert List') }}
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-zinc-800">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('Title') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('Slug') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('Deadline') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jobAdverts as $advert)
                        <tr class="hover:bg-blue-50 dark:hover:bg-zinc-800 transition">
                            <td class="border-b px-4 py-3 text-gray-900 dark:text-gray-100 font-medium">{{ $advert->title }}</td>
                            <td class="border-b px-4 py-3 text-gray-700 dark:text-gray-300">{{ $advert->slug }}</td>
                            <td class="border-b px-4 py-3 text-gray-700 dark:text-gray-300">{{ $advert->deadline }}</td>
                            <td class="border-b px-4 py-3">
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold
                                    @if($advert->status === 'Published') bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200
                                    @elseif($advert->status === 'Draft') bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-200
                                    @else bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200 @endif">
                                    {{ __($advert->status) }}
                                </span>
                            </td>
                            <td class="border-b px-4 py-3 flex gap-2">
                                <a href="{{ route('admin.job-adverts.edit', ['slug' => $advert->slug]) }}"
                                    class="flex items-center gap-1 text-blue-600 hover:text-blue-800 font-semibold px-3 py-1 rounded transition focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    title="{{ __('Edit') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.293-6.293a1 1 0 011.414 0l1.586 1.586a1 1 0 010 1.414L11 15H9v-2z"></path>
                                    </svg>
                                    {{ __('Edit') }}
                                </a>
                                <button wire:click="delete({{ $advert->id }})"
                                    class="flex items-center gap-1 text-red-600 hover:text-red-800 font-semibold px-3 py-1 rounded transition focus:outline-none focus:ring-2 focus:ring-red-500"
                                    title="{{ __('Delete') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    {{ __('Delete') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">{{ __('No job adverts found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
