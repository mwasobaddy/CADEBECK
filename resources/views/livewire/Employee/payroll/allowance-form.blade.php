<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\PayrollAllowance;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use App\Models\Audit;
use Carbon\Carbon;
use Livewire\Attributes\Computed;

new #[Layout('components.layouts.app')] class extends Component {
    public ?Employee $employee = null;
    public ?PayrollAllowance $allowance = null;
    public bool $editing = false;
    public array $form = [
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
            $this->editing = true;
            $this->form = [
                'allowance_type' => $this->allowance->allowance_type ?? '',
                'description' => $this->allowance->description ?? '',
                'amount' => $this->allowance->amount ?? '',
                'is_recurring' => $this->allowance->is_recurring ?? true,
                'effective_date' => $this->allowance->effective_date ? Carbon::parse($this->allowance->effective_date)->format('Y-m-d') : '',
                'end_date' => $this->allowance->end_date ? Carbon::parse($this->allowance->end_date)->format('Y-m-d') : '',
                'notes' => $this->allowance->notes ?? '',
            ];
        } else {
            // Set default effective date to today for new allowances
            $this->form['effective_date'] = Carbon::today()->format('Y-m-d');
        }
    }

    #[Computed]
    public function allowanceTypes(): array
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

    public function save(): void
    {
        $rules = [
            'form.allowance_type' => 'required|string|max:50',
            'form.description' => 'required|string|max:255',
            'form.amount' => 'required|numeric|min:0',
            'form.effective_date' => 'required|date',
            'form.end_date' => 'nullable|date|after:form.effective_date',
            'form.notes' => 'nullable|string|max:500',
        ];

        $this->validate($rules);

        // Fetch latest payroll for the employee (for reference only)
        $latestPayroll = $this->employee->payrolls()->latest('pay_date')->first();
        
        $allowanceData = array_merge($this->form, [
            'employee_id' => $this->employee->id,
            'payroll_id' => null, // Will be set during payroll processing
            'status' => 'active',
        ]);

        // Ensure description is not null or empty
        if (empty($allowanceData['description'])) {
            $allowanceData['description'] = __('No description provided');
        }

        if ($this->editing && $this->allowance) {
            $this->allowance->update($allowanceData);

            // Audit log
            Audit::create([
                'actor_id' => Auth::id(),
                'action' => 'update_allowance',
                'target_type' => PayrollAllowance::class,
                'target_id' => $this->allowance->id,
                'details' => json_encode($allowanceData),
            ]);

            $notification = [
                'type' => 'success',
                'message' => __('Allowance updated successfully.'),
                'timestamp' => now()->timestamp,
            ];

            $existingNotifications = session('notifications', []);
            if (!is_array($existingNotifications)) {
                $existingNotifications = [];
            }
            $existingNotifications[] = $notification;
            session(['notifications' => $existingNotifications]);
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

            $notification = [
                'type' => 'success',
                'message' => __('Allowance created successfully.'),
                'timestamp' => now()->timestamp,
            ];

            $existingNotifications = session('notifications', []);
            if (!is_array($existingNotifications)) {
                $existingNotifications = [];
            }
            $existingNotifications[] = $notification;
            session(['notifications' => $existingNotifications]);
        }

        $this->redirectRoute('employee.payroll.allowances', ['employeeId' => $this->employee->id], navigate: true);
    }

    public function resetForm(): void
    {
        if ($this->editing && $this->allowance) {
            $this->form = [
                'allowance_type' => $this->allowance->allowance_type ?? '',
                'description' => $this->allowance->description ?? '',
                'amount' => $this->allowance->amount ?? '',
                'is_recurring' => $this->allowance->is_recurring ?? true,
                'effective_date' => $this->allowance->effective_date ? Carbon::parse($this->allowance->effective_date)->format('Y-m-d') : '',
                'end_date' => $this->allowance->end_date ? Carbon::parse($this->allowance->end_date)->format('Y-m-d') : '',
                'notes' => $this->allowance->notes ?? '',
            ];
        } else {
            $this->form = [
                'allowance_type' => '',
                'description' => '',
                'amount' => '',
                'is_recurring' => true,
                'effective_date' => Carbon::today()->format('Y-m-d'),
                'end_date' => '',
                'notes' => '',
            ];
        }
        $this->dispatch('notify', ['type' => 'info', 'message' => __('Form reset successfully.')]);
    }

    public function cancel(): void
    {
        $this->redirectRoute('employee.payroll.allowances', ['employeeId' => $this->employee->id], navigate: true);
    }
};
?>

<div class="relative max-w-6xl mx-auto md:px-4 md:py-8">
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
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 z-10 relative border border-green-100 dark:border-zinc-800 ring-1 ring-green-200/30 dark:ring-zinc-700/40">
        <nav class="flex items-center justify-between md:justify-start gap-4 w-full">
            <div class="flex items-center gap-4">
                <a href="{{ route('employee.index') }}" class="border rounded-full py-2 px-2 md:px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.index') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}" wire:navigate>
                    <span class="hidden md:flex">
                        {{ __('Employee List') }}
                    </span>
                    <span class="flex: md:hidden">
                        <flux:icon name="numbered-list" variant="solid" class="w-5 h-5" />
                    </span>
                </a>

                <a href="{{ route('employee.show') }}" class="border rounded-full py-2 px-2 md:px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.show') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}" wire:navigate>
                    <span class="hidden md:flex">
                        {{ __('Add Employee') }}
                    </span>
                    <span class="flex: md:hidden">
                        <flux:icon name="users" variant="solid" class="w-5 h-5" />
                    </span>
                </a>

                <a href="{{ route('employee.edit', $employee->id) }}" class="border rounded-full py-2 px-2 md:px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.edit') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}" wire:navigate>
                    <span class="hidden md:flex">
                        {{ __('Edit Employee') }}
                    </span>
                    <span class="flex: md:hidden">
                        <flux:icon name="pencil-square" variant="solid" class="w-5 h-5" />
                    </span>
                </a>

                <a href="{{ route('employee.payroll.allowances', $employee->id) }}" class="border rounded-full py-2 px-2 md:px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.payroll.allowances') || request()->routeIs('employee.payroll.allowances.create') || request()->routeIs('employee.payroll.allowances.edit') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}" wire:navigate>
                    <span class="hidden md:flex">
                        {{ __('Allowances') }}
                    </span>
                    <span class="flex: md:hidden">
                        <flux:icon name="currency-dollar" variant="solid" class="w-5 h-5" />
                    </span>
                </a>
            </div>
            
            <!-- Three dots dropdown menu -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-400 border
                        {{ request()->routeIs('employee.payroll.deductions') || request()->routeIs('employee.payroll.payslips') || request()->routeIs('employee.payroll.history', ['employeeId' => $employee->id]) ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400' }}">
                    <flux:icon name="ellipsis-vertical" variant="solid" class="w-5 h-5" />
                </button>
                
                <!-- Dropdown menu -->
                <div x-show="open" 
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="absolute right-0 mt-2 w-56 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-xl rounded-xl shadow-xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40 z-50"
                     style="display: none;">
                    <div class="py-2">
                        <a href="{{ route('employee.payroll.deductions', $employee->id) }}" 
                           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.deductions') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}" wire:navigate>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            {{ __('Deductions') }}
                        </a>
                        
                        <a href="{{ route('employee.payroll.payslips', ['employeeId' => $employee->id]) }}" 
                           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.payslips') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}" wire:navigate>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{ __('Payslips') }}
                        </a>
                        
                        <a href="{{ route('employee.payroll.history', ['employeeId' => $employee->id]) }}" 
                           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.history', $employee->id) ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}" wire:navigate>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ __('History') }}
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </div>
    
    <div class="relative bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex items-center gap-3 mb-8">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
            <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                {{ $editing ? __('Edit Allowance') : __('Create Allowance') }}
                <span class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
            </h1>
        </div>
        
        <form wire:submit="save" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Allowance Information -->
            <div class="md:col-span-2 lg:col-span-3">
                <h2 class="text-lg font-bold text-green-700 mb-2">{{ __('Allowance Information') }}</h2>
            </div>
            
            <div>
                <flux:select 
                    wire:model="form.allowance_type" 
                    label="{{ __('Allowance Type') }}" 
                    placeholder="{{ __('Select Type') }}"
                    required
                >
                    <option value="">{{ __('Select Type') }}</option>
                    @foreach($this->allowanceTypes as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </flux:select>
            </div>
            
            <div class="md:col-span-2">
                <flux:input 
                    wire:model="form.description" 
                    label="{{ __('Description') }}"
                    placeholder="{{ __('Enter allowance description...') }}"
                    required
                />
            </div>

            <!-- Financial Details -->
            <div class="md:col-span-2 lg:col-span-3 mt-6">
                <h2 class="text-lg font-bold text-green-700 mb-2">{{ __('Financial Details') }}</h2>
            </div>
            
            <div>
                <flux:input 
                    wire:model="form.amount" 
                    type="number"
                    step="0.01"
                    label="{{ __('Amount (USD)') }}"
                    placeholder="0.00"
                    required
                />
            </div>
            
            <div>
                <flux:input 
                    wire:model="form.effective_date" 
                    type="date"
                    label="{{ __('Effective Date') }}"
                    required
                />
            </div>
            
            <div>
                <flux:input 
                    wire:model="form.end_date" 
                    type="date"
                    label="{{ __('End Date (Optional)') }}"
                />
            </div>
            
            <div class="flex items-center">
                <flux:checkbox 
                    wire:model="form.is_recurring" 
                    label="{{ __('Recurring Allowance') }}"
                />
            </div>

            <!-- Notes -->
            <div class="md:col-span-2 lg:col-span-3 mt-6">
                <h2 class="text-lg font-bold text-green-700 mb-2">{{ __('Additional Notes') }}</h2>
            </div>
            
            <div class="md:col-span-2 lg:col-span-3">
                <flux:textarea 
                    wire:model="form.notes" 
                    label="{{ __('Notes') }}"
                    rows="4"
                    placeholder="{{ __('Additional notes...') }}"
                />
            </div>

            <!-- Actions -->
            <div class="flex items-end justify-end gap-3 md:col-span-2 lg:col-span-3">
                <flux:button 
                    icon-trailing="check" 
                    variant="primary" 
                    type="submit" 
                    class="flex flex-row items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500"
                >
                    {{ $editing ? __('Update') : __('Create') }}
                </flux:button>
                
                @if($editing)
                    <flux:button 
                        icon-trailing="x-mark" 
                        variant="primary" 
                        type="button" 
                        wire:click="cancel" 
                        class="flex flex-row items-center gap-2 bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-200 px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400"
                    >
                        {{ __('Cancel') }}
                    </flux:button>
                @else
                    <flux:button 
                        icon-trailing="arrow-path-rounded-square" 
                        variant="primary" 
                        type="button" 
                        wire:click="resetForm" 
                        class="flex flex-row items-center gap-2 bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-200 px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400"
                    >
                        {{ __('Reset Form') }}
                    </flux:button>
                @endif
            </div>
        </form>
    </div>
</div>