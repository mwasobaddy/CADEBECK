<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar stashable sticky class="bg-zinc-50/20 dark:bg-zinc-900/20 w-full backdrop-blur-sm !p-0">
            <x-app.sidebar />
        </flux:sidebar>

        <flux:main>
            {{ $slot }}
        </flux:main>

        @fluxScripts
    </body>
</html>