<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\PayrollDeduction;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use App\Models\Audit;

new #[Layout('components.layouts.app')] class extends Component {
    public ?Employee $employee = null;
    public bool $showCreateModal = false;
    public array $deductionForm = [
        'deduction_type' => '',
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
        $this->resetDeductionForm();
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetDeductionForm();
    }

    public function resetDeductionForm(): void
    {
        $this->deductionForm = [
            'deduction_type' => '',
            'description' => '',
            'amount' => '',
            'is_recurring' => true,
            'effective_date' => '',
            'end_date' => '',
            'notes' => '',
        ];
    }

    public function saveDeduction(): void
    {
        $rules = [
            'deductionForm.deduction_type' => 'required|string|max:50',
            'deductionForm.description' => 'required|string|max:255',
            'deductionForm.amount' => 'required|numeric|min:0',
            'deductionForm.effective_date' => 'required|date',
            'deductionForm.end_date' => 'nullable|date|after:deductionForm.effective_date',
            'deductionForm.notes' => 'nullable|string|max:500',
        ];

        $this->validate($rules);

        $deductionData = array_merge($this->deductionForm, [
            'employee_id' => $this->employee->id,
            'status' => 'active',
        ]);

        PayrollDeduction::create($deductionData);

        // Audit log
        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'create_deduction',
            'details' => json_encode($deductionData),
        ]);

        $this->closeCreateModal();
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Deduction created successfully.')
        ]);
    }

    public function deactivateDeduction($deductionId): void
    {
        $deduction = PayrollDeduction::findOrFail($deductionId);
        $deduction->update(['status' => 'inactive']);

        Audit::create([
            'actor_id' => Auth::id(),
            'action' => 'deactivate_deduction',
            'details' => json_encode(['deduction_id' => $deductionId]),
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Deduction deactivated successfully.')
        ]);
    }

    public function getDeductionsProperty()
    {
        return $this->employee->payrollDeductions()
            ->orderBy('effective_date', 'desc')
            ->get();
    }

    public function getDeductionTypesProperty()
    {
        return [
            'insurance' => __('Insurance'),
            'loan' => __('Loan Repayment'),
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
                    {{ __('Payroll Deductions for') }} {{ $employee->user->first_name }} {{ $employee->user->last_name }}
                </h1>
            </div>
            <button wire:click="openCreateModal" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                {{ __('Add Deduction') }}
            </button>
        </nav>
    </div>

    <!-- Deductions Table -->
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
                    @forelse($this->deductions as $deduction)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $this->deductionTypes[$deduction->deduction_type] ?? $deduction->deduction_type }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $deduction->description }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            KES {{ number_format($deduction->amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ \Carbon\Carbon::parse($deduction->effective_date)->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $deduction->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($deduction->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            @if($deduction->status === 'active')
                            <button wire:click="deactivateDeduction({{ $deduction->id }})"
                                    class="text-red-600 hover:text-red-900">
                                {{ __('Deactivate') }}
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No deductions found for this employee.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Deduction Modal -->
    @if($showCreateModal)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ __('Add New Deduction') }}
                </h3>
                <form wire:submit.prevent="saveDeduction">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Deduction Type') }}
                        </label>
                        <select wire:model="deductionForm.deduction_type"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                            <option value="">{{ __('Select Type') }}</option>
                            @foreach($this->deductionTypes as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                        @error('deductionForm.deduction_type')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Description') }}
                        </label>
                        <input type="text" wire:model="deductionForm.description"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                        @error('deductionForm.description')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Amount (KES)') }}
                        </label>
                        <input type="number" step="0.01" wire:model="deductionForm.amount"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                        @error('deductionForm.amount')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Effective Date') }}
                        </label>
                        <input type="date" wire:model="deductionForm.effective_date"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                        @error('deductionForm.effective_date')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('End Date (Optional)') }}
                        </label>
                        <input type="date" wire:model="deductionForm.end_date"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                        @error('deductionForm.end_date')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Notes') }}
                        </label>
                        <textarea wire:model="deductionForm.notes" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm"></textarea>
                        @error('deductionForm.notes')
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
                            {{ __('Save Deduction') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
