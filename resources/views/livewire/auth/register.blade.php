<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));

        Auth::login($user);

        $this->redirectIntended(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <x-auth-header :title="__('Create your account')" :description="__('Get started with your CADEBECK account')" />

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="register" class="space-y-6">
        <!-- Name -->
        <div class="space-y-2">
            <flux:input
                wire:model="name"
                :label="__('Full name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                placeholder="Enter your full name"
            />
        </div>

        <!-- Email Address -->
        <div class="space-y-2">
            <flux:input
                wire:model="email"
                :label="__('Email address')"
                type="email"
                required
                autocomplete="email"
                placeholder="Enter your email address"
            />
        </div>

        <!-- Password -->
        <div class="space-y-2">
            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Create a password"
                viewable
            />
            <p class="text-sm text-gray-500 dark:text-gray-400">Must be at least 8 characters long</p>
        </div>

        <!-- Confirm Password -->
        <div class="space-y-2">
            <flux:input
                wire:model="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Confirm your password"
                viewable
            />
        </div>

        <!-- Submit Button -->
        <flux:button type="submit" variant="primary" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 dark:from-blue-500 dark:to-blue-600 dark:hover:from-blue-600 dark:hover:to-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
            {{ __('Create account') }}
        </flux:button>
    </form>

    <!-- Terms and Privacy -->
    <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
        By creating an account, you agree to our 
        <a href="#" class="text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300">Terms of Service</a> and 
        <a href="#" class="text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300">Privacy Policy</a>
    </p>

    <!-- Login Link -->
    <div class="text-center">
        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Already have an account?') }}</span>
        <flux:link :href="route('login')" wire:navigate class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 font-medium ml-1">
            {{ __('Sign in') }}
        </flux:link>
    </div>
</div>
