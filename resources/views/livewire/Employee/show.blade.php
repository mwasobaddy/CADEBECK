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
        'first_name' => '',
        'other_names' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => '',
        'role' => '',
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
                'first_name' => $this->employee->user->first_name,
                'other_names' => $this->employee->user->other_names,
                'email' => $this->employee->user->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => $this->employee->user->roles->first()?->name ?? '',
                'date_of_birth' => $this->employee->date_of_birth ? \Illuminate\Support\Carbon::parse($this->employee->date_of_birth)->format('Y-m-d') : '',
                'gender' => $this->employee->gender,
                'mobile_number' => $this->employee->mobile_number,
                'home_address' => $this->employee->home_address,
                'staff_number' => $this->employee->staff_number,
                'location_id' => $this->employee->location_id,
                'branch_id' => $this->employee->branch_id,
                'department_id' => $this->employee->department_id,
                'designation_id' => $this->employee->designation_id,
                'date_of_join' => $this->employee->date_of_join ? \Illuminate\Support\Carbon::parse($this->employee->date_of_join)->format('Y-m-d') : '',
                'contract_type_id' => $this->employee->contract_type_id,
            ];
            $user = $this->employee->user;
            if ($user) {
                $this->user = [
                    'first_name' => $user->first_name,
                    'other_names' => $user->other_names,
                    'email' => $user->email,
                    'password' => '',
                    'password_confirmation' => '',
                    'role' => $user->roles->first()?->name ?? '',
                ];
            }
            $this->editing = true;
        }
    }

    public function updatedFormUserId($value): void
    {
        $this->showNewUserFields = ($value === 'create_new');
    }

    // Add method to handle location change and reset dependent fields
    public function updatedFormLocationId(): void
    {
        $this->form['branch_id'] = '';
        $this->form['department_id'] = '';
    }

    // Add method to handle branch change and reset dependent fields
    public function updatedFormBranchId(): void
    {
        $this->form['department_id'] = '';
    }

    public function save(): void
    {
        $rules = [
            'form.first_name' => ['required', 'string', 'max:50'],
            'form.other_names' => ['nullable', 'string', 'max:50'],
            'form.email' => ['required', 'email', $this->editing ? 'unique:users,email,' . ($this->employee?->user_id ?? 'NULL') : 'unique:users,email'],
            'form.password' => [$this->editing ? 'nullable' : 'required', 'min:8', 'confirmed'],
            'form.role' => ['required', 'string'],
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
        ];
        $this->validate($rules);

        if ($this->editing && $this->employee) {
            $user = $this->employee->user;
            if ($user) {
                $user->first_name = $this->user['first_name'];
                $user->other_names = $this->user['other_names'];
                $user->email = $this->user['email'];
                if (!empty($this->user['password'])) {
                    $user->password = Hash::make($this->user['password']);
                }
                $user->save();
                $user->syncRoles([$this->user['role']]);
            }
            $this->employee->update($this->form);
            $this->dispatch('notify', ['type' => 'success', 'message' => __('User updated successfully.')]);
        } else {
            $user = User::create([
                'first_name' => $this->user['first_name'],
                'other_names' => $this->user['other_names'],
                'email' => $this->user['email'],
                'password' => Hash::make($this->user['password']),
            ]);
            $user->assignRole($this->user['role']);
            $employeeData = $this->form;
            $employeeData['user_id'] = $user->id;
            Employee::create($employeeData);
            $this->dispatch('notify', ['type' => 'success', 'message' => __('User created successfully.')]);
        }
        $this->redirectRoute('employee.index');
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
        $this->dispatch('notify', ['type' => 'info', 'message' => __('Form reset successfully.')]);
    }

    public function getLocationsProperty() { return Location::all(); }
    public function getBranchesProperty() { return Branch::all(); }
    public function getDepartmentsProperty() { return Department::all(); }
    public function getDesignationsProperty() { return Designation::all(); }
    public function getContractTypesProperty() { return ContractType::all(); }
    public function getUsersProperty() { return User::all(); }
};
?>

<div class="relative max-w-6xl mx-auto md:px-4 md:py-8">
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 z-10 relative border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <nav class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('employee.index') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.index') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none' : '' }}">
                    {{ __('Employee List') }}
                </a>
                <a href="{{ route('employee.show') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.show') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-none' : '' }}">
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
                <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                    {{ $editing ? __('Edit Employee') : __('Create Employee') }}
                    <span class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                </h1>
            </div>
            <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Personal Information -->
                <div class="md:col-span-2 lg:col-span-3">
                    <h2 class="text-lg font-bold text-green-700 mb-2">{{ __('Personal Information') }}</h2>
                </div>
                <div>
                    <flux:input
                        wire:model="form.first_name"
                        :label="__('First Name')"
                        type="text"
                        required
                        placeholder="{{ __('First Name') }}" />
                </div>
                <div>
                    <flux:input
                        wire:model="form.other_names"
                        :label="__('Other Names')"
                        type="text"
                        required
                        placeholder="{{ __('Other Names') }}" />
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
                <div class="md:col-span-2 lg:col-span-3">
                    <flux:input
                        wire:model="form.home_address"
                        :label="__('Home Address')"
                        type="text"
                        required
                        autocomplete="street-address"
                        placeholder="{{ __('Home Address') }}"
                    />
                </div>

                <!-- Employment Details -->
                <div class="md:col-span-2 lg:col-span-3 mt-6">
                    <h2 class="text-lg font-bold text-green-700 mb-2">{{ __('Employment Details') }}</h2>
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
                        wire:model="form.date_of_join"
                        :label="__('Date of Join')"
                        type="date"
                        required
                        autocomplete="date-of-join"
                        placeholder="{{ __('Date of Join') }}"
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
                        :disabled="!$form['location_id']"
                    >
                        @if($form['location_id'])
                            @foreach($this->branches->where('location_id', $form['location_id']) as $branch)
                                <flux:select.option value="{{ $branch->id }}">{{ $branch->name }}</flux:select.option>
                            @endforeach
                        @endif
                    </flux:select>
                </div>
                <div>
                    <flux:select
                        wire:model="form.department_id"
                        :label="__('Department')"
                        required
                        :placeholder="__('Department')"
                        :disabled="!$form['branch_id']"
                    >
                        @if($form['branch_id'])
                            @foreach($this->departments->where('branch_id', $form['branch_id']) as $department)
                                <flux:select.option value="{{ $department->id }}">{{ $department->name }}</flux:select.option>
                            @endforeach
                        @endif
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

                <!-- Account Details -->
                <div class="md:col-span-2 lg:col-span-3 mt-6">
                    <h2 class="text-lg font-bold text-green-700 mb-2">{{ __('Account Details') }}</h2>
                </div>
                <div>
                    <flux:input
                        wire:model="form.email"
                        :label="__('Email')"
                        type="email"
                        required
                        placeholder="{{ __('Email') }}" />
                </div>
                <div>
                    <flux:select wire:model="form.role" :label="__('Role')" required :placeholder="__('Role')">
                        <flux:select.option value="">{{ __('Select Role') }}</flux:select.option>
                        <flux:select.option value="Super Administrator">Super Administrator</flux:select.option>
                        <flux:select.option value="HR Administrator">HR Administrator</flux:select.option>
                        <flux:select.option value="New Employee">New Employee</flux:select.option>
                    </flux:select>
                </div>
                <div>
                    <flux:input
                        wire:model="form.password"
                        :label="__('Password')"
                        type="password"
                        autocomplete="new-password"
                        :placeholder="__('Password')"
                        viewable
                    />
                </div>
                <div>
                    <flux:input
                        wire:model="form.password_confirmation"
                        :label="__('Confirm Password')"
                        type="password"
                        autocomplete="new-password"
                        :placeholder="__('Confirm Password')"
                        viewable
                    />
                </div>

                <!-- Actions -->
                <div class="flex items-end justify-end gap-3 md:col-span-2 lg:col-span-3">
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