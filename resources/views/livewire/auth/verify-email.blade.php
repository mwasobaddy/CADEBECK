<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="space-y-6">
    <x-auth-header
        :title="__('Verify your email')"
        :description="__('We\'ve sent a verification link to your email address')"
    />

    <!-- Email Icon -->
    <div class="flex justify-center mb-6">
        <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
    </div>

    <div class="text-center space-y-4">
        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
            {{ __('Please check your email and click the verification link to activate your account. If you don\'t see the email, check your spam folder.') }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <p class="text-green-800 dark:text-green-200 font-medium">
                    {{ __('A new verification link has been sent to your email address.') }}
                </p>
            </div>
        @endif
    </div>

    <div class="space-y-4">
        <!-- Resend Verification Button -->
        <flux:button wire:click="sendVerification" variant="primary" class="w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 dark:from-green-500 dark:to-green-600 dark:hover:from-green-600 dark:hover:to-green-700 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
            {{ __('Resend verification email') }}
        </flux:button>

        <!-- Logout Button -->
        <flux:button wire:click="logout" variant="ghost" class="w-full text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
            {{ __('Sign out') }}
        </flux:button>
    </div>

    <!-- Help Text -->
    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
            <div>
                <h4 class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ __('Need help?') }}</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('If you continue to have issues, please contact your system administrator.') }}</p>
            </div>
        </div>
    </div>
</div>
