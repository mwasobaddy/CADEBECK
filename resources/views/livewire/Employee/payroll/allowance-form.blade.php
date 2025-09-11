<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\PayrollAllowance;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use App\Models\Audit;

new #[Layout('components.layouts.app')] class extends Component {
    public ?Employee $employee = null;
    public ?PayrollAllowance $allowance = null;
    public bool $isEditing = false;
    public array $allowanceForm = [
        'allowance_type' => '',
        'description' => '',
        'amount' => '',
        'is_recurring' => true,
        'effective_date' => '',
        'end_date' => '',
        'notes' => '',
    ];

    public function mount($employeeId, $allowanceId = null): void
    {
        $this->employee = Employee::findOrFail($employeeId);

        if ($allowanceId) {
            $this->allowance = PayrollAllowance::findOrFail($allowanceId);
            $this->isEditing = true;
            $this->loadAllowanceData();
        }
    }

    public function loadAllowanceData(): void
    {
        if ($this->allowance) {
            $this->allowanceForm = [
                'allowance_type' => $this->allowance->allowance_type,
                'description' => $this->allowance->description,
                'amount' => $this->allowance->amount,
                'is_recurring' => $this->allowance->is_recurring,
                'effective_date' => $this->allowance->effective_date,
                'end_date' => $this->allowance->end_date,
                'notes' => $this->allowance->notes,
            ];
        }
    }

    public function saveAllowance()
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

        if ($this->isEditing && $this->allowance) {
            $this->allowance->update($allowanceData);

            // Audit log
            Audit::create([
                'actor_id' => Auth::id(),
                'action' => 'update_allowance',
                'target_type' => PayrollAllowance::class,
                'target_id' => $this->allowance->id,
                'details' => json_encode($allowanceData),
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __('Allowance updated successfully.')
            ]);
        } else {
            $newAllowance = PayrollAllowance::create($allowanceData);

            // Audit log
            Audit::create([
                'actor_id' => Auth::id(),
                'action' => 'create_allowance',
                'target_type' => PayrollAllowance::class,
                'target_id' => $newAllowance->id,
                'details' => json_encode($allowanceData),
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __('Allowance created successfully.')
            ]);
        }

        return $this->redirectRoute('employee.payroll.allowances', ['employeeId' => $this->employee->id]);
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

<div class="relative max-w-4xl mx-auto md:px-4 md:py-8">
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
    <svg class="fixed -bottom-24 -right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0" viewBox="0 0 400 400" fill="none">
        <ellipse cx="200" cy="200" rx="160" ry="100" fill="url(#blob2)" />
        <defs>
            <radialGradient id="blob2" cx="0" cy="0" r="1" gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                <stop stop-color="#34d399" />
                <stop offset="1" stop-color="#f472b6" />
            </radialGradient>
        </defs>
    </svg>

    <!-- Breadcrumbs -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 z-10 relative border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <nav class="flex items-center gap-4">
            <a href="{{ route('employee.index') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.index') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}">
                {{ __('Employee List') }}
            </a>
            <a href="{{ route('employee.show') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.show') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}">
                {{ __('Add Employee') }}
            </a>
            <a href="{{ route('employee.edit', $employee->id) }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.edit') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}">
                {{ __('Edit Employee') }}
            </a>
            <a href="{{ route('employee.payroll.allowances', $employee->id) }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.payroll.allowances') || request()->routeIs('employee.payroll.allowances.create') || request()->routeIs('employee.payroll.allowances.edit') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}">
                {{ __('Allowances') }}
            </a>
        </nav>
    </div>

    <!-- Main Content Card -->
    <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <!-- Header with Icon -->
        <div class="flex justify-between mb-8 items-center">
            <div class="flex items-center">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                </svg>
                <h2 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-800 via-blue-500 to-indigo-500 tracking-tight drop-shadow-lg relative inline-block">
                    {{ $isEditing ? __('Edit Allowance') : __('Create Allowance') }}
                    <span class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-blue-800 via-blue-500 to-indigo-500"></span>
                </h2>
            </div>
            <a href="{{ route('employee.payroll.allowances', $employee->id) }}" class="flex items-center gap-2 px-2 lg:px-4 py-2 rounded-full border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 bg-gray-50/80 dark:bg-gray-900/20 hover:bg-gray-100/80 dark:hover:bg-gray-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span class="hidden lg:inline">{{ __('Back to Allowances') }}</span>
            </a>
        </div>

        <!-- Employee Info -->
        <div class="bg-gradient-to-r from-blue-50/80 to-indigo-50/80 dark:from-zinc-800/50 dark:to-zinc-700/50 rounded-xl p-6 mb-8 border border-blue-200 dark:border-zinc-700 backdrop-blur-sm">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                        {{ $employee->user->first_name }} {{ $employee->user->other_names }}
                    </h3>
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        {{ __('Staff Number') }}: {{ $employee->staff_number }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form wire:submit.prevent="saveAllowance" class="space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Allowance Type -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('Allowance Type') }}
                    </label>
                    <select wire:model="allowanceForm.allowance_type"
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 px-4 py-3">
                        <option value="">{{ __('Select Type') }}</option>
                        @foreach($this->allowanceTypes as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                    @error('allowanceForm.allowance_type')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('Description') }}
                    </label>
                    <input type="text" wire:model="allowanceForm.description"
                           class="w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 px-4 py-3"
                           placeholder="{{ __('Enter allowance description...') }}">
                    @error('allowanceForm.description')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('Amount (KES)') }}
                    </label>
                    <input type="number" step="0.01" wire:model="allowanceForm.amount"
                           class="w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 px-4 py-3"
                           placeholder="0.00">
                    @error('allowanceForm.amount')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Effective Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('Effective Date') }}
                    </label>
                    <input type="date" wire:model="allowanceForm.effective_date"
                           class="w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 px-4 py-3">
                    @error('allowanceForm.effective_date')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- End Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('End Date (Optional)') }}
                    </label>
                    <input type="date" wire:model="allowanceForm.end_date"
                           class="w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 px-4 py-3">
                    @error('allowanceForm.end_date')
                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Is Recurring -->
                <div class="flex items-center">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="allowanceForm.is_recurring"
                               class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400">
                        <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Recurring Allowance') }}
                        </span>
                    </label>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    {{ __('Notes') }}
                </label>
                <textarea wire:model="allowanceForm.notes" rows="4"
                          class="w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 px-4 py-3"
                          placeholder="{{ __('Additional notes...') }}"></textarea>
                @error('allowanceForm.notes')
                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-4 pt-8 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('employee.payroll.allowances', $employee->id) }}"
                   class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                    {{ __('Cancel') }}
                </a>
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl font-semibold shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                    {{ $isEditing ? __('Update Allowance') : __('Create Allowance') }}
                </button>
            </div>
        </form>
    </div>
</div>
