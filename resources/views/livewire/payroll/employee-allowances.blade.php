<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\PayrollAllowance;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use App\Models\Audit;

new #[Layout('components.layouts.app')] class extends Component {
    public ?Employee $employee = null;
    public bool $showCreateModal = false;
    public array $allowanceForm = [
        'allowance_type' => '',
        'description' => '',
        'amount' => '',
        'is_recurring' => true,
        'effective_date' => '',
        'end_date' => '',
        'notes' => '',
    ];

    public function mount($employeeId): void
    {
        $this->employee = Employee::findOrFail($employeeId);
    }

    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
        $this->resetAllowanceForm();
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetAllowanceForm();
    }

    public function resetAllowanceForm(): void
    {
        $this->allowanceForm = [
            'allowance_type' => '',
            'description' => '',
            'amount' => '',
            'is_recurring' => true,
            'effective_date' => '',
            'end_date' => '',
            'notes' => '',
        ];
    }

    public function saveAllowance(): void
    {
        $rules = [
            'allowanceForm.allowance_type' => 'required|string|max:50',
            'allowanceForm.description' => 'required|string|max:255',
            'allowanceForm.amount' => 'required|numeric|min:0',
            'allowanceForm.effective_date' => 'required|date',
            'allowanceForm.end_date' => 'nullable|date|after:allowanceForm.effective_date',
            'allowanceForm.notes' => 'nullable|string|max:500',
        ];

        $this->validate($rules);

        $allowanceData = array_merge($this->allowanceForm, [
            'employee_id' => $this->employee->id,
            'status' => 'active',
        ]);

        PayrollAllowance::create($allowanceData);

        // Audit log
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'create_allowance',
            'details' => json_encode($allowanceData),
        ]);

        $this->closeCreateModal();
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Allowance created successfully.')
        ]);
    }

    public function deactivateAllowance($allowanceId): void
    {
        $allowance = PayrollAllowance::findOrFail($allowanceId);
        $allowance->update(['status' => 'inactive']);

        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'deactivate_allowance',
            'details' => json_encode(['allowance_id' => $allowanceId]),
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Allowance deactivated successfully.')
        ]);
    }

    public function getAllowancesProperty()
    {
        return $this->employee->payrollAllowances()
            ->orderBy('effective_date', 'desc')
            ->get();
    }

    public function getAllowanceTypesProperty()
    {
        return [
            'house' => __('House Allowance'),
            'transport' => __('Transport Allowance'),
            'medical' => __('Medical Allowance'),
            'overtime' => __('Overtime'),
            'bonus' => __('Bonus'),
            'other' => __('Other'),
        ];
    }
};
?>

<div class="relative max-w-6xl mx-auto md:px-4 md:py-8">
    <!-- Header -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 z-10 relative border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <nav class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('employee.show', $employee->id) }}" class="text-blue-600 hover:text-blue-800">
                    ← {{ __('Back to Employee') }}
                </a>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ __('Payroll Allowances for') }} {{ $employee->user->first_name }} {{ $employee->user->last_name }}
                </h1>
            </div>
            <button wire:click="openCreateModal" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                {{ __('Add Allowance') }}
            </button>
        </nav>
    </div>

    <!-- Allowances Table -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Type') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Description') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Amount') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Effective Date') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Status') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($this->allowances as $allowance)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $this->allowanceTypes[$allowance->allowance_type] ?? $allowance->allowance_type }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $allowance->description }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            KES {{ number_format($allowance->amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ \Carbon\Carbon::parse($allowance->effective_date)->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $allowance->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($allowance->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            @if($allowance->status === 'active')
                            <button wire:click="deactivateAllowance({{ $allowance->id }})"
                                    class="text-red-600 hover:text-red-900">
                                {{ __('Deactivate') }}
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No allowances found for this employee.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Allowance Modal -->
    @if($showCreateModal)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ __('Add New Allowance') }}
                </h3>
                <form wire:submit.prevent="saveAllowance">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Allowance Type') }}
                        </label>
                        <select wire:model="allowanceForm.allowance_type"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                            <option value="">{{ __('Select Type') }}</option>
                            @foreach($this->allowanceTypes as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                        @error('allowanceForm.allowance_type')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Description') }}
                        </label>
                        <input type="text" wire:model="allowanceForm.description"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                        @error('allowanceForm.description')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Amount (KES)') }}
                        </label>
                        <input type="number" step="0.01" wire:model="allowanceForm.amount"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                        @error('allowanceForm.amount')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Effective Date') }}
                        </label>
                        <input type="date" wire:model="allowanceForm.effective_date"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                        @error('allowanceForm.effective_date')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('End Date (Optional)') }}
                        </label>
                        <input type="date" wire:model="allowanceForm.end_date"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                        @error('allowanceForm.end_date')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Notes') }}
                        </label>
                        <textarea wire:model="allowanceForm.notes" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm"></textarea>
                        @error('allowanceForm.notes')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="closeCreateModal"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            {{ __('Save Allowance') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
{{-- </content>
<parameter name="filePath">/Users/app/Desktop/Laravel/CADEBECK/resources/views/livewire/payroll/employee-allowances.blade.php --}}
