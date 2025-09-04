
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Welcome Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ __('Welcome back, ') . Auth::user()->name }}!
                </h1>
                <p class="text-gray-600 dark:text-gray-300 mt-2">
                    {{ __('Here\'s your HR dashboard overview for today.') }}
                </p>
            </div>

            <!-- Quick Stats Row -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Today's Status -->
                <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-xl">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Today\'s Status') }}</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Not Clocked In') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Pending Leave Requests -->
                <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-orange-100 dark:bg-orange-900/50 rounded-xl">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 4v10a2 2 0 002 2h4a2 2 0 002-2V11M9 11h6"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Pending Leave') }}</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('0 Requests') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Well-being Score -->
                <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-purple-100 dark:bg-purple-900/50 rounded-xl">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Well-being') }}</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Not Assessed') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-xl">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Quick Actions') }}</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Available') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column - Clock In/Out -->
                <div class="lg:col-span-1">
                    <livewire:clock.clock-manager />
                </div>

                <!-- Middle Column - Leave Management -->
                <div class="lg:col-span-1">
                    <livewire:leave.leave-manager />
                </div>

                <!-- Right Column - Well-being -->
                <div class="lg:col-span-1">
                    <livewire:wellbeing.wellbeing-manager />
                </div>
            </div>

            <!-- Additional Information Section -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Recent Activity -->
                <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Recent Activity') }}</h3>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-900 dark:text-white">{{ __('Welcome to CADEBECK HR System') }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">{{ __('Just now') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Announcements -->
                <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Announcements') }}</h3>
                    <div class="space-y-3">
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-700">
                            <h4 class="font-semibold text-blue-900 dark:text-blue-100">{{ __('New Features Available') }}</h4>
                            <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                                {{ __('Clock in/out, leave management, and well-being surveys are now available on your dashboard.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
