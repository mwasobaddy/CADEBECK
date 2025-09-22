@props([
    'title',
    'description',
])

<div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2 leading-tight">{{ $title }}</h2>
    @if($description)
        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">{{ $description }}</p>
    @endif
</div>
