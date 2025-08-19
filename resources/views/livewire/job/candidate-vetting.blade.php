<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\JobAdvert;
use App\Models\Application;
use Illuminate\Support\Facades\Session;

new #[Layout('components.layouts.app')] class extends Component {
    public ?JobAdvert $advert = null;
    public $applications;
    public array $notes = [];
    public array $statuses = [];

    public function mount($slug): void
    {
        $this->advert = JobAdvert::where('slug', $slug)->firstOrFail();
        $this->loadApplications();
    }

    public function loadApplications(): void
    {
        $this->applications = Application::where('job_advert_id', $this->advert->id)->get();
        foreach ($this->applications as $app) {
            $this->notes[$app->id] = $app->private_note ?? '';
            $this->statuses[$app->id] = $app->status;
        }
    }

    public function updateStatus($id, $status): void
    {
        $app = Application::findOrFail($id);
        $oldStatus = $app->status;
        $app->status = $status;
        $app->save();
        $this->statuses[$id] = $status;
        $this->audit('status_update', $app, ['old' => $oldStatus, 'new' => $status]);
        $this->notifyCandidate($app, $status);
        Session::flash('status', __('Candidate status updated.'));
    }

    public function saveNote($id): void
    {
        $app = Application::findOrFail($id);
        $app->private_note = $this->notes[$id];
        $app->save();
        $this->audit('note_added', $app, ['note' => $this->notes[$id]]);
        Session::flash('status', __('Note saved.'));
    }

    protected function audit($action, $target, $details = []): void
    {
        \App\Models\Audit::create([
            'actor_id' => Auth::id(),
            'action' => $action,
            'target_type' => get_class($target),
            'target_id' => $target->id,
            'details' => json_encode($details),
        ]);
    }

    protected function notifyCandidate($app, $status): void
    {
        // TODO: Implement notification logic (Mailables/Notifications)
    }
}; ?>

<div class="p-6">
    <h1 class="text-2xl font-bold mb-4">{{ __('Candidate Vetting for') }} {{ $advert->title }}</h1>
    @if (session('status'))
        <div class="bg-blue-100 text-blue-800 p-4 rounded mb-4">{{ session('status') }}</div>
    @endif
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-zinc-900 rounded shadow">
            <thead>
                <tr>
                    <th class="px-4 py-2">{{ __('Name') }}</th>
                    <th class="px-4 py-2">{{ __('Email') }}</th>
                    <th class="px-4 py-2">{{ __('Phone') }}</th>
                    <th class="px-4 py-2">{{ __('Status') }}</th>
                    <th class="px-4 py-2">{{ __('Private Note') }}</th>
                    <th class="px-4 py-2">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($applications as $app)
                    <tr>
                        <td class="border px-4 py-2">{{ $app->name }}</td>
                        <td class="border px-4 py-2">{{ $app->email }}</td>
                        <td class="border px-4 py-2">{{ $app->phone }}</td>
                        <td class="border px-4 py-2">
                            <select wire:model="statuses.{{ $app->id }}" wire:change="updateStatus({{ $app->id }}, $event.target.value)" class="border rounded p-1">
                                <option value="Pending">{{ __('Pending') }}</option>
                                <option value="Shortlisted">{{ __('Shortlisted') }}</option>
                                <option value="Rejected">{{ __('Rejected') }}</option>
                                <option value="Invited">{{ __('Invited for Interview') }}</option>
                            </select>
                        </td>
                        <td class="border px-4 py-2">
                            <textarea wire:model="notes.{{ $app->id }}" class="w-full border rounded p-1"></textarea>
                            <button wire:click="saveNote({{ $app->id }})" class="mt-1 bg-gray-300 px-2 py-1 rounded">{{ __('Save') }}</button>
                        </td>
                        <td class="border px-4 py-2">
                            <a href="#" class="text-blue-600" onclick="window.open('data:application/pdf;base64,{{ base64_encode($app->cv_blob) }}', '_blank')">{{ __('View CV') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
