<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Services\MailConfigurationService;
use Illuminate\Support\Facades\Artisan;

new #[Layout('components.layouts.app')] class extends Component {
    public string $selectedProvider = 'mailtrap';
    public array $credentials = [
        'host' => '',
        'port' => '',
        'username' => '',
        'password' => '',
        'encryption' => '',
        'from_address' => '',
        'from_name' => '',
    ];
    public bool $testing = false;
    public ?array $testResult = null;
    public bool $configuring = false;

    public function mount(): void
    {
        $this->loadCurrentConfiguration();
    }

    public function loadCurrentConfiguration(): void
    {
        $status = MailConfigurationService::getConfigurationStatus();

        // Try to detect current provider based on host
        $providers = MailConfigurationService::getMailProviders();
        foreach ($providers as $key => $provider) {
            if ($status['host'] === $provider['host']) {
                $this->selectedProvider = $key;
                break;
            }
        }

        $this->credentials = [
            'host' => $status['host'] ?? '',
            'port' => $status['port'] ?? '',
            'username' => $status['username'] === 'Configured' ? '' : '',
            'password' => $status['password'] === 'Configured' ? '' : '',
            'encryption' => $status['encryption'] ?? '',
            'from_address' => $status['from_address'] ?? '',
            'from_name' => $status['from_name'] ?? '',
        ];
    }

    public function updatedSelectedProvider(): void
    {
        $providers = MailConfigurationService::getMailProviders();
        $provider = $providers[$this->selectedProvider] ?? null;

        if ($provider) {
            $this->credentials['host'] = $provider['host'];
            $this->credentials['port'] = $provider['port'];
            $this->credentials['encryption'] = $provider['encryption'];
        }
    }

    public function configureMail(): void
    {
        $this->configuring = true;
        $this->testResult = null;

        try {
            $result = MailConfigurationService::configureProvider(
                $this->selectedProvider,
                $this->credentials
            );

            if ($result) {
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => __('Mail configuration updated successfully!')
                ]);

                // Reload configuration
                $this->loadCurrentConfiguration();
            } else {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => __('Failed to configure mail provider.')
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('Error configuring mail: ') . $e->getMessage()
            ]);
        }

        $this->configuring = false;
    }

    public function testMailConfiguration(): void
    {
        $this->testing = true;
        $this->testResult = null;

        try {
            $this->testResult = MailConfigurationService::testConfiguration();

            if ($this->testResult['success']) {
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => __('Test email sent successfully!')
                ]);
            } else {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => __('Test failed: ') . $this->testResult['message']
                ]);
            }
        } catch (\Exception $e) {
            $this->testResult = [
                'success' => false,
                'message' => $e->getMessage(),
            ];

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('Test failed: ') . $e->getMessage()
            ]);
        }

        $this->testing = false;
    }

    public function getProvidersProperty()
    {
        return MailConfigurationService::getMailProviders();
    }

    public function getProviderTipsProperty()
    {
        return MailConfigurationService::getProviderTips($this->selectedProvider);
    }

    public function getCurrentStatusProperty()
    {
        return MailConfigurationService::getConfigurationStatus();
    }
};
?>

<div class="relative max-w-7xl mx-auto md:px-4 md:py-8">
    <!-- SVG Blobs Background -->
    <svg class="fixed -top-24 right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0" viewBox="0 0 400 400" fill="none">
        <ellipse cx="200" cy="200" rx="180" ry="120" fill="url(#blob1)" />
        <defs>
            <radialGradient id="blob1" cx="0" cy="0" r="1" gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                <stop stop-color="#38bdf8" />
                <stop offset="1" stop-color="#6366f1" />
            </radialGradient>
        </defs>
    </svg>

    <!-- Breadcrumbs -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 z-10 relative border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <nav class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h1 class="text-xl font-bold text-gray-800 dark:text-gray-200">
                    {{ __('Email Configuration') }}
                </h1>
            </div>
        </nav>
    </div>

    <!-- Current Configuration Status -->
    <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40 mb-8">
        <div class="flex items-center gap-3 mb-6">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-800 via-blue-500 to-purple-500 tracking-tight drop-shadow-lg relative inline-block">
                {{ __('Current Configuration') }}
                <span class="absolute -bottom-2 left-0 w-[200px] h-1 rounded-full bg-gradient-to-r from-blue-800 via-blue-500 to-purple-500"></span>
            </h1>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Mailer') }}</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $this->currentStatus['mailer'] }}</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Host') }}</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $this->currentStatus['host'] }}</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Port') }}</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $this->currentStatus['port'] }}</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('From Address') }}</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $this->currentStatus['from_address'] }}</div>
            </div>
        </div>

        <div class="mt-6 flex items-center gap-4">
            <button wire:click="testMailConfiguration"
                wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold shadow transition-all duration-200 disabled:opacity-50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ __('Test Configuration') }}
            </button>

            @if($testResult)
                <div class="flex items-center gap-2 px-4 py-2 rounded-lg {{ $testResult['success'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                    @if($testResult['success'])
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" />
                        </svg>
                    @endif
                    {{ $testResult['message'] }}
                </div>
            @endif
        </div>
    </div>

    <!-- Mail Provider Configuration -->
    <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex items-center gap-3 mb-8">
            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-800 via-purple-500 to-pink-500 tracking-tight drop-shadow-lg relative inline-block">
                {{ __('Configure Mail Provider') }}
                <span class="absolute -bottom-2 left-0 w-[220px] h-1 rounded-full bg-gradient-to-r from-purple-800 via-purple-500 to-pink-500"></span>
            </h1>
        </div>

        <!-- Provider Selection -->
        <div class="mb-6">
            <flux:select
                wire:model.live="selectedProvider"
                :label="__('Select Mail Provider')"
            >
                @foreach($this->providers as $key => $provider)
                    <flux:select.option value="{{ $key }}">{{ $provider['name'] }}</flux:select.option>
                @endforeach
            </flux:select>

            @if(isset($this->providers[$this->selectedProvider]))
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ $this->providers[$this->selectedProvider]['description'] }}
                </p>
            @endif
        </div>

        <!-- Configuration Form -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <flux:input
                    wire:model="credentials.host"
                    type="text"
                    :label="__('SMTP Host')"
                    placeholder="smtp.example.com"
                    required
                />
            </div>
            <div>
                <flux:input
                    wire:model="credentials.port"
                    type="number"
                    :label="__('SMTP Port')"
                    placeholder="587"
                    required
                />
            </div>
            <div>
                <flux:input
                    wire:model="credentials.username"
                    type="text"
                    :label="__('SMTP Username')"
                    placeholder="your-email@example.com"
                    required
                />
            </div>
            <div>
                <flux:input
                    wire:model="credentials.password"
                    type="password"
                    :label="__('SMTP Password')"
                    placeholder="your-password"
                    required
                />
            </div>
            <div>
                <flux:select
                    wire:model="credentials.encryption"
                    :label="__('Encryption')"
                >
                    <flux:select.option value="">{{ __('None') }}</flux:select.option>
                    <flux:select.option value="tls">{{ __('TLS') }}</flux:select.option>
                    <flux:select.option value="ssl">{{ __('SSL') }}</flux:select.option>
                </flux:select>
            </div>
            <div></div>
            <div>
                <flux:input
                    wire:model="credentials.from_address"
                    type="email"
                    :label="__('From Email Address')"
                    placeholder="noreply@yourcompany.com"
                    required
                />
            </div>
            <div>
                <flux:input
                    wire:model="credentials.from_name"
                    type="text"
                    :label="__('From Name')"
                    placeholder="Your Company HR"
                    required
                />
            </div>
        </div>

        <!-- Provider Tips -->
        @if(count($this->providerTips) > 0)
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">
                    {{ __('Configuration Tips for') }} {{ $this->providers[$this->selectedProvider]['name'] ?? 'Selected Provider' }}
                </h3>
                <ul class="list-disc list-inside space-y-1 text-blue-800 dark:text-blue-200">
                    @foreach($this->providerTips as $tip)
                        <li>{{ $tip }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Configure Button -->
        <div class="flex items-center justify-end">
            <button wire:click="configureMail"
                wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 rounded-xl font-semibold shadow transition-all duration-200 disabled:opacity-50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                {{ __('Configure Mail Settings') }}
            </button>
        </div>
    </div>

    <!-- Testing Section -->
    @if($testing)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <svg class="w-8 h-8 text-blue-600 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            {{ __('Testing Mail Configuration') }}
                        </h2>
                    </div>

                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        {{ __('Please wait while we test your mail configuration...') }}
                    </p>

                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                        <div class="bg-blue-600 h-2 rounded-full animate-pulse" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
