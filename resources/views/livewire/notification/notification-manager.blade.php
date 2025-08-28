<?php
use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public $notifications = [];

    #[On('notify')]
    public function showNotification($data)
    {
        if (is_array($data)) {
            $type = $data['type'];
            $message = $data['message'];
        } else {
            // Fallback for old format (type, message)
            $type = $data;
            $message = func_get_arg(1);
        }
        
        $this->notifications[] = [
            'type' => $type,
            'message' => __($message),
        ];
        // Remove notification after 5 seconds
        $this->dispatch('remove-notification', ['index' => count($this->notifications) - 1], 5000);
    }

    public function removeNotification($index)
    {
        unset($this->notifications[$index]);
        $this->notifications = array_values($this->notifications);
    }
};
?>

<div class="fixed bottom-4 right-4 space-y-4 z-50">
    @foreach ($notifications as $index => $notification)
        <div class="p-4 rounded shadow-lg text-white"
             :class="{
                 'bg-green-500': $notification['type'] === 'success',
                 'bg-red-500': $notification['type'] === 'error',
                 'bg-blue-500': $notification['type'] === 'info',
             }">
            <div class="flex justify-between items-center">
                <span>{{ $notification['message'] }}</span>
                <button wire:click="removeNotification({{ $index }})" class="ml-4" aria-label="Close notification">&times;</button>
            </div>
        </div>
    @endforeach
</div>