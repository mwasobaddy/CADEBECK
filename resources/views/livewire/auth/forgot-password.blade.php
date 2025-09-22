<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink($this->only('email'));

        session()->flash('status', __('A reset link will be sent if the account exists.'));
    }
}; ?>

<div class="space-y-6">
    <x-auth-header :title="__('Forgot your password?')" :description="__('No worries, we\'ll send you reset instructions')" />

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="space-y-6">
        <!-- Email Address -->
        <div class="space-y-2">
            <flux:input
                wire:model="email"
                :label="__('Email address')"
                type="email"
                required
                autofocus
                placeholder="Enter your email address"
            />
            <p class="text-sm text-gray-500 dark:text-gray-400">We'll send reset instructions to this email if it exists in our system.</p>
        </div>

        <!-- Submit Button -->
        <flux:button variant="primary" type="submit" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 dark:from-blue-500 dark:to-blue-600 dark:hover:from-blue-600 dark:hover:to-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
            {{ __('Send reset instructions') }}
        </flux:button>
    </form>

    <!-- Back to Login -->
    <div class="text-center">
        <flux:link :href="route('login')" wire:navigate class="inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            {{ __('Back to login') }}
        </flux:link>
    </div>
</div>
