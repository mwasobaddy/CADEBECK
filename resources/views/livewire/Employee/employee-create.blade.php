<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\ContractType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

new #[Layout('components.layouts.app')] class extends Component {
    public array $form = [
        'user_id' => '',
        'date_of_birth' => '',
        'gender' => '',
        'mobile_number' => '',
        'home_address' => '',
        'staff_number' => '',
        'location_id' => '',
        'branch_id' => '',
        'department_id' => '',
        'designation_id' => '',
        'date_of_join' => '',
        'contract_type_id' => '',
    ];
    public ?Employee $employee = null;
    public bool $editing = false;
    public int $perPage = 10;

    public function mount($id = null): void
    {
        if ($id) {
            $this->employee = Employee::findOrFail($id);
            $this->form = [
                'user_id' => $this->employee->user_id,
                'date_of_birth' => $this->employee->date_of_birth,
                'gender' => $this->employee->gender,
                'mobile_number' => $this->employee->mobile_number,
                'home_address' => $this->employee->home_address,
                'staff_number' => $this->employee->staff_number,
                'location_id' => $this->employee->location_id,
                'branch_id' => $this->employee->branch_id,
                'department_id' => $this->employee->department_id,
                'designation_id' => $this->employee->designation_id,
                'date_of_join' => $this->employee->date_of_join,
                'contract_type_id' => $this->employee->contract_type_id,
            ];
            $this->editing = true;
        }
    }

    public function save(): void
    {
        $this->validate([
            'form.user_id' => ['required', 'exists:users,id'],
            'form.date_of_birth' => ['required', 'date'],
            'form.gender' => ['required', 'in:male,female,other'],
            'form.mobile_number' => ['required', 'string', 'max:20'],
            'form.home_address' => ['required', 'string', 'max:255'],
            'form.staff_number' => ['required', 'string', 'max:20', 'unique:employees,staff_number' . ($this->editing && $this->employee ? ',' . $this->employee->id : '')],
            'form.location_id' => ['required', 'exists:locations,id'],
            'form.branch_id' => ['required', 'exists:branches,id'],
            'form.department_id' => ['required', 'exists:departments,id'],
            'form.designation_id' => ['required', 'exists:designations,id'],
            'form.date_of_join' => ['required', 'date'],
            'form.contract_type_id' => ['required', 'exists:contract_types,id'],
        ]);
        if ($this->editing && $this->employee) {
            $this->employee->update($this->form);
            session()->flash('status', __('Employee updated successfully.'));
        } else {
            Employee::create($this->form);
            session()->flash('status', __('Employee created successfully.'));
        }
        $this->redirectRoute('employee.manage');
    }

    public function resetForm(): void
    {
        if ($this->editing && $this->employee) {
            $this->form = [
                'user_id' => $this->employee->user_id,
                'date_of_birth' => $this->employee->date_of_birth,
                'gender' => $this->employee->gender,
                'mobile_number' => $this->employee->mobile_number,
                'home_address' => $this->employee->home_address,
                'staff_number' => $this->employee->staff_number,
                'location_id' => $this->employee->location_id,
                'branch_id' => $this->employee->branch_id,
                'department_id' => $this->employee->department_id,
                'designation_id' => $this->employee->designation_id,
                'date_of_join' => $this->employee->date_of_join,
                'contract_type_id' => $this->employee->contract_type_id,
            ];
        } else {
            $this->form = [
                'user_id' => '',
                'date_of_birth' => '',
                'gender' => '',
                'mobile_number' => '',
                'home_address' => '',
                'staff_number' => '',
                'location_id' => '',
                'branch_id' => '',
                'department_id' => '',
                'designation_id' => '',
                'date_of_join' => '',
                'contract_type_id' => '',
            ];
        }
    }

    public function getLocationsProperty() { return Location::all(); }
    public function getBranchesProperty() { return Branch::all(); }
    public function getDepartmentsProperty() { return Department::all(); }
    public function getDesignationsProperty() { return Designation::all(); }
    public function getContractTypesProperty() { return ContractType::all(); }
    public function getUsersProperty() { return User::all(); }
};
?>

<div class="relative max-w-4xl mx-auto md:px-4 md:py-8">
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 z-10 relative border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <nav class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('employee.manage') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.manage') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none' : '' }}">
                    {{ __('Employee List') }}
                </a>
                <a href="{{ route('employee.create') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.create') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none' : '' }}">
                    {{ $editing ? __('Edit Employee') : __('Add Employee') }}
                </a>
            </div>
        </nav>
    </div>

    @can('create_employee')
        <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
            <div class="flex items-center gap-3 mb-8">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"></path>
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                </svg>
                <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg">
                    {{ $editing ? __('Edit Employee') : __('Add Employee') }}
                </h1>
            </div>
            <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <flux:select
                        wire:model="form.user_id"
                        :label="__('Select User')"
                        required
                        :placeholder="__('Select User')"
                    >
                        @foreach($this->users as $user)
                            <flux:select.option value="{{ $user->id }}">{{ $user->first_name }}{{ $user->other_names ? ' ' . $user->other_names : '' }} ({{ $user->email }})</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div>
                    <flux:input
                        wire:model="form.staff_number"
                        :label="__('Staff Number')"
                        type="text"
                        required
                        autocomplete="off"
                        placeholder="{{ __('Staff Number') }}"
                    />
                </div>
                <div>
                    <flux:input
                        wire:model="form.date_of_birth"
                        :label="__('Date of Birth')"
                        type="date"
                        required
                        autocomplete="bday"
                        placeholder="{{ __('Date of Birth') }}"
                    />
                </div>
                <div>
                    <flux:select
                        wire:model="form.gender"
                        :label="__('Gender')"
                        required
                        :placeholder="__('Gender')"
                    >
                        <flux:select.option value="male">{{ __('Male') }}</flux:select.option>
                        <flux:select.option value="female">{{ __('Female') }}</flux:select.option>
                        <flux:select.option value="other">{{ __('Other') }}</flux:select.option>
                    </flux:select>
                </div>
                <div>
                    <flux:input
                        wire:model="form.mobile_number"
                        :label="__('Mobile Number')"
                        type="text"
                        required
                        autocomplete="tel"
                        placeholder="{{ __('Mobile Number') }}"
                    />
                </div>
                <div>
                    <flux:input
                        wire:model="form.home_address"
                        :label="__('Home Address')"
                        type="text"
                        required
                        autocomplete="street-address"
                        placeholder="{{ __('Home Address') }}"
                    />
                </div>
                <div>
                    <flux:select
                        wire:model="form.location_id"
                        :label="__('Branch Location')"
                        required
                        :placeholder="__('Branch Location')"
                    >
                        @foreach($this->locations as $location)
                            <flux:select.option value="{{ $location->id }}">{{ $location->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div>
                    <flux:select
                        wire:model="form.branch_id"
                        :label="__('Branch')"
                        required
                        :placeholder="__('Branch')"
                        @if(!$form['location_id']) disabled @endif
                    >
                        @foreach($form['location_id'] ? $this->branches->where('location_id', $form['location_id']) : [] as $branch)
                            <flux:select.option value="{{ $branch->id }}">{{ $branch->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div>
                    <flux:select
                        wire:model="form.department_id"
                        :label="__('Department')"
                        required
                        :placeholder="__('Department')"
                        @if(!$form['branch_id']) disabled @endif
                    >
                        @foreach($form['branch_id'] ? $this->departments->where('branch_id', $form['branch_id']) : [] as $department)
                            <flux:select.option value="{{ $department->id }}">{{ $department->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div>
                    <flux:select
                        wire:model="form.designation_id"
                        :label="__('Designation')"
                        required
                        :placeholder="__('Designation')"
                    >
                        @foreach($this->designations as $designation)
                            <flux:select.option value="{{ $designation->id }}">{{ $designation->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div>
                    <flux:select
                        wire:model="form.contract_type_id"
                        :label="__('Contract Type')"
                        required
                        :placeholder="__('Contract Type')"
                    >
                        @foreach($this->contractTypes as $type)
                            <flux:select.option value="{{ $type->id }}">{{ $type->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <div>
                    <flux:input
                        wire:model="form.date_of_join"
                        :label="__('Date of Join')"
                        type="date"
                        required
                        autocomplete="date-of-join"
                        placeholder="{{ __('Date of Join') }}"
                    />
                </div>
                <div class="flex items-end justify-end gap-3">
                    <button type="submit"
                        class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500">
                        {{ $editing ? __('Update') : __('Create') }}
                        <flux:icon name="check" class="w-5 h-5" />
                    </button>
                    <button type="button" wire:click="resetForm"
                        class="flex items-center gap-2 bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-200 px-6 py-2 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                        {{ __('Reset') }}
                        <flux:icon name="arrow-path-rounded-square" class="w-5 h-5" />
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="flex items-center justify-center w-full">
            <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-10 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40 flex flex-col items-center gap-6 max-w-lg w-full">
                <svg class="w-16 h-16 text-red-400 mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" />
                </svg>
                <h2 class="text-2xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 flex items-center gap-2 drop-shadow-lg">
                    {{ __('Access Denied') }}
                </h2>
                <p class="text-lg text-gray-700 dark:text-gray-300 font-medium text-center">
                    {{ __('You do not have permission to access this page or perform this action.') }}
                </p>
            </div>
        </div>
    @endcan
</div>
