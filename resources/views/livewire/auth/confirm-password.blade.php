<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <x-auth-header
        :title="__('Confirm your password')"
        :description="__('For your security, please confirm your password to continue')"
    />

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="confirmPassword" class="space-y-6">
        <!-- Password -->
        <div class="space-y-2">
            <flux:input
                wire:model="password"
                :label="__('Current password')"
                type="password"
                required
                autocomplete="current-password"
                placeholder="Enter your current password"
                viewable
            />
            <p class="text-sm text-gray-500 dark:text-gray-400">Please enter your current password to verify your identity.</p>
        </div>

        <!-- Submit Button -->
        <flux:button variant="primary" type="submit" class="w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 dark:from-green-500 dark:to-green-600 dark:hover:from-green-600 dark:hover:to-green-700 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
            {{ __('Confirm password') }}
        </flux:button>
    </form>

    <!-- Security Notice -->
    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-green-400 dark:text-green-300 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
            <div>
                <h4 class="text-sm font-medium text-green-800 dark:text-green-200">Secure Area</h4>
                <p class="text-sm text-green-700 dark:text-green-300 mt-1">This is a secure area that requires password confirmation for your protection.</p>
            </div>
        </div>
    </div>
</div>
