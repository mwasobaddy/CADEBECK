<?php
use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public $notifications = [];

    public function mount()
    {
        // Load persistent notifications from session
        $sessionNotifications = session('notifications', []);
        if (!empty($sessionNotifications) && is_array($sessionNotifications)) {
            // Filter out any invalid notifications and expired ones (older than 5 minutes)
            $validNotifications = array_filter($sessionNotifications, function($notification) {
                return is_array($notification) &&
                       isset($notification['type']) &&
                       isset($notification['message']) &&
                       isset($notification['timestamp']) &&
                       (now()->timestamp - $notification['timestamp']) < 300; // 5 minutes
            });
            $this->notifications = array_values($validNotifications);

            // Keep notifications in session for persistence across page loads
            // Don't clear them here - they'll be cleared when dismissed or expired
            session(['notifications' => $this->notifications]);
        }

        // Set up periodic cleanup of expired notifications
        // This will be handled by JavaScript setInterval
    }

    public function showNotification($data)
    {
        if (is_array($data)) {
            $type = $data['type'] ?? 'info';
            $message = $data['message'] ?? '';
        } else {
            // Fallback for old format (type, message)
            $type = $data;
            $message = func_get_arg(1) ?? '';
        }
        
        if (empty($message)) {
            return; // Don't show empty notifications
        }
        
        $notification = [
            'type' => $type,
            'message' => __($message),
            'timestamp' => now()->timestamp,
        ];
        
        $this->notifications[] = $notification;
        
        // Store in session for persistence across page loads
        $sessionNotifications = session('notifications', []);
        if (!is_array($sessionNotifications)) {
            $sessionNotifications = [];
        }
        $sessionNotifications[] = $notification;
        session(['notifications' => $sessionNotifications]);
        
        // No need for Livewire dispatch timeout - Alpine.js handles the UI timeout
    }

    public function removeNotificationByIndex($index)
    {
        if (isset($this->notifications[$index])) {
            unset($this->notifications[$index]);
            $this->notifications = array_values($this->notifications);
            session(['notifications' => $this->notifications]);
        }
    }

    public function removeNotification($notificationId)
    {
        $this->notifications = array_filter($this->notifications, function($notification) use ($notificationId) {
            return $notification['id'] !== $notificationId;
        });
        $this->notifications = array_values($this->notifications);
        session(['notifications' => $this->notifications]);
    }

    public function cleanupExpiredNotifications()
    {
        $this->notifications = array_filter($this->notifications, function($notification) {
            return (now()->timestamp - $notification['timestamp']) < 300; // 5 minutes
        });
        $this->notifications = array_values($this->notifications);
        session(['notifications' => $this->notifications]);
    }

    public function getListeners()
    {
        return [
            'cleanup-notifications' => 'cleanupExpiredNotifications',
            'notify' => 'showNotification',
        ];
    }
};
?>

<div class="fixed bottom-4 right-4 space-y-4 z-50">
    @foreach ($notifications as $index => $notification)
        <div class="p-4 rounded shadow-lg text-white @if($notification['type'] === 'success') bg-green-500 @elseif($notification['type'] === 'error') bg-red-500 @elseif($notification['type'] === 'info') bg-yellow-500 @else bg-gray-700 @endif"
             x-data="{ show: true }"
             x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform translate-y-2"
             x-init="
                 // Auto-hide after 5 seconds, but keep in session for persistence
                 setTimeout(() => show = false, 5000);
                 // Remove from component and session after animation completes
                 setTimeout(() => $wire.removeNotificationByIndex({{ $index }}), 5300);
             ">
            <div class="flex justify-between items-center">
                <span>{{ $notification['message'] }}</span>
                <button wire:click="removeNotification({{ $index }})" class="ml-4 text-white hover:text-gray-200" aria-label="Close notification">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    @endforeach
</div>

<script>
    document.addEventListener('livewire:loaded', () => {
        // Set up periodic cleanup every minute
        setInterval(() => {
            $wire.call('cleanupExpiredNotifications');
        }, 60000); // 1 minute
    });
</script>