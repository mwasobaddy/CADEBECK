@php
    // Set defaults for when variables are not passed by Laravel's error handler
    $code = $code ?? 403;
    $message = $message ?? 'You do not have permission to access this resource.';
    $isAuthenticated = $isAuthenticated ?? auth()->check();
@endphp

@if($isAuthenticated)
    <x-layouts.app>

        <div class="relative max-w-6xl mx-auto md:px-4 md:py-8">
            <!-- SVG Blobs Background -->
            <svg class="fixed -top-24 right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0" viewBox="0 0 400 400"
                fill="none">
                <ellipse cx="200" cy="200" rx="180" ry="120" fill="url(#blob1)" />
                <defs>
                    <radialGradient id="blob1" cx="0" cy="0" r="1"
                        gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#38bdf8" />
                        <stop offset="1" stop-color="#6366f1" />
                    </radialGradient>
                </defs>
            </svg>
            <svg class="fixed -bottom-24 -right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0"
                viewBox="0 0 400 400" fill="none">
                <ellipse cx="200" cy="200" rx="160" ry="100" fill="url(#blob2)" />
                <defs>
                    <radialGradient id="blob2" cx="0" cy="0" r="1"
                        gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#34d399" />
                        <stop offset="1" stop-color="#f472b6" />
                    </radialGradient>
                </defs>
            </svg>

            <!-- Breadcrumbs -->
            <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 z-10 relative border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
                <nav class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('dashboard') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('dashboard') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}" wire:navigate>
                            {{ __('Dashboard') }}
                        </a>
                        <button onclick="history.back()" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            {{ __('Go Back') }}
                        </button>
                    </div>
                </nav>
            </div>

            <!-- Error Content -->
            <div class="relative z-10 flex items-center justify-center min-h-[60vh]">
                <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-12 border border-red-100 dark:border-zinc-800 ring-1 ring-red-200/30 dark:ring-zinc-700/40 w-full">
                    <div class="text-center">
                        <!-- Error Icon -->
                        <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-red-100 dark:bg-red-900/20 mb-8">
                            <svg class="h-12 w-12 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
                            </svg>
                        </div>

                        <!-- Error Code -->
                        <h1 class="text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-red-600 via-red-500 to-pink-500 mb-4">
                            {{ $code }}
                        </h1>

                        <!-- Error Title -->
                        <h2 class="text-2xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-red-800 via-red-500 to-indigo-500 gap-2 drop-shadow-lg">
                            {{ __('Access Denied') }}
                        </h2>

                        <!-- Error Message -->
                        <p class="text-lg text-gray-700 dark:text-gray-300 font-medium text-center mb-8">
                            {{ $message }}
                        </p>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="{{ route('home') }}"
                            class="flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-semibold rounded-full shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500" wire:navigate>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                {{ __('Go Home') }}
                            </a>

                            <button onclick="window.location.reload()"
                                    class="flex items-center justify-center gap-2 px-6 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-200 font-semibold rounded-full shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ __('Try Again') }}
                            </button>
                        </div>

                        <!-- Additional Help -->
                        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('If you believe this is an error, please contact your system administrator.') }}
                            </p>
                            @if(config('app.debug') && isset($exception))
                                <details class="mt-4 text-left">
                                    <summary class="cursor-pointer text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                                        {{ __('Technical Details (Debug Mode)') }}
                                    </summary>
                                    <pre class="mt-2 text-xs bg-gray-100 dark:bg-gray-800 p-2 rounded overflow-auto">{{ $exception->getMessage() }}</pre>
                                </details>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </x-layouts.app>
@else
    <x-layouts.app.guest>
        <div class="relative max-w-6xl mx-auto md:px-4 md:py-8">
            <!-- SVG Blobs Background -->
            <svg class="fixed -top-24 right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0" viewBox="0 0 400 400"
                fill="none">
                <ellipse cx="200" cy="200" rx="180" ry="120" fill="url(#blob1)" />
                <defs>
                    <radialGradient id="blob1" cx="0" cy="0" r="1"
                        gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#38bdf8" />
                        <stop offset="1" stop-color="#6366f1" />
                    </radialGradient>
                </defs>
            </svg>
            <svg class="fixed -bottom-24 -right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0"
                viewBox="0 0 400 400" fill="none">
                <ellipse cx="200" cy="200" rx="160" ry="100" fill="url(#blob2)" />
                <defs>
                    <radialGradient id="blob2" cx="0" cy="0" r="1"
                        gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#34d399" />
                        <stop offset="1" stop-color="#f472b6" />
                    </radialGradient>
                </defs>
            </svg>

            <!-- Error Content -->
            <div class="relative z-10 flex items-center justify-center min-h-[60vh]">
                <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-12 border border-red-100 dark:border-zinc-800 ring-1 ring-red-200/30 dark:ring-zinc-700/40 w-full">
                    <div class="text-center">
                        <!-- Error Icon -->
                        <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-red-100 dark:bg-red-900/20 mb-8">
                            <svg class="h-12 w-12 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
                            </svg>
                        </div>

                        <!-- Error Code -->
                        <h1 class="text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-red-600 via-red-500 to-pink-500 mb-4">
                            {{ $code }}
                        </h1>

                        <!-- Error Title -->
                        <h2 class="text-2xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-red-800 via-red-500 to-indigo-500 gap-2 drop-shadow-lg">
                            {{ __('Access Denied') }}
                        </h2>

                        <!-- Error Message -->
                        <p class="text-lg text-gray-700 dark:text-gray-300 font-medium text-center mb-8">
                            {{ $message }}
                        </p>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="{{ route('home') }}"
                            class="flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-semibold rounded-full shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500" wire:navigate>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                {{ __('Go Home') }}
                            </a>

                            <button onclick="window.location.reload()"
                                    class="flex items-center justify-center gap-2 px-6 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-200 font-semibold rounded-full shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ __('Try Again') }}
                            </button>
                        </div>

                        <!-- Additional Help -->
                        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('If you believe this is an error, please contact your system administrator.') }}
                            </p>
                            @if(config('app.debug') && isset($exception))
                                <details class="mt-4 text-left">
                                    <summary class="cursor-pointer text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                                        {{ __('Technical Details (Debug Mode)') }}
                                    </summary>
                                    <pre class="mt-2 text-xs bg-gray-100 dark:bg-gray-800 p-2 rounded overflow-auto">{{ $exception->getMessage() }}</pre>
                                </details>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </x-layouts.app.guest>
@endif