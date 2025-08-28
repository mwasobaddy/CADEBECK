<?php
use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public $notifications = [];

    protected $listeners = ['notify' => 'showNotification'];

    public function mount()
    {
        // Load persistent notifications from session
        $sessionNotifications = session('notifications', []);
        if (!empty($sessionNotifications) && is_array($sessionNotifications)) {
            // Filter out any invalid notifications
            $validNotifications = array_filter($sessionNotifications, function($notification) {
                return is_array($notification) && 
                       isset($notification['type']) && 
                       isset($notification['message']) && 
                       isset($notification['timestamp']);
            });
            $this->notifications = array_values($validNotifications);
            // Clear session notifications after loading
            session()->forget('notifications');
        }
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
        // Ensure index is valid
        if (!is_numeric($index) || $index < 0) {
            return;
        }

        // Remove from component notifications
        if (isset($this->notifications[$index])) {
            unset($this->notifications[$index]);
            $this->notifications = array_values($this->notifications);
        }
        
        // Also remove from session - find by timestamp to ensure consistency
        $sessionNotifications = session('notifications', []);
        if (isset($sessionNotifications[$index])) {
            unset($sessionNotifications[$index]);
            $sessionNotifications = array_values($sessionNotifications);
            session(['notifications' => $sessionNotifications]);
        }
    }

    public function removeNotification($index)
    {
        if (isset($this->notifications[$index])) {
            unset($this->notifications[$index]);
            $this->notifications = array_values($this->notifications);
            
            // Also remove from session
            $sessionNotifications = session('notifications', []);
            if (isset($sessionNotifications[$index])) {
                unset($sessionNotifications[$index]);
                $sessionNotifications = array_values($sessionNotifications);
                session(['notifications' => $sessionNotifications]);
            }
        }
    }
};
?>

<div class="fixed bottom-4 right-4 space-y-4 z-50">
    @foreach ($notifications as $index => $notification)
        <div class="p-4 rounded shadow-lg text-white @if($notification['type'] === 'success') bg-green-500 @elseif($notification['type'] === 'error') bg-red-500 @elseif($notification['type'] === 'info') bg-blue-500 @else bg-gray-700 @endif"
             x-data="{ show: true }"
             x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform translate-y-2"
             x-init="setTimeout(() => show = false, 5000); setTimeout(() => $wire.removeNotificationByIndex({{ $index }}), 5300)">
            <div class="flex justify-between items-center">
                <span>{{ $notification['message'] }}</span>
                <button wire:click="removeNotification({{ $index }})" class="ml-4" aria-label="Close notification">&times;</button>
            </div>
        </div>
    @endforeach
</div>