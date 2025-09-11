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
use App\Models\Audit;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

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
        'supervisor_id' => '',
        'basic_salary' => '',
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
                'supervisor_id' => $this->employee->supervisor_id,
                'basic_salary' => $this->employee->basic_salary ?? '',
            ];
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

    // Add method to handle role change and reset supervisor
    public function updatedFormRole(): void
    {
        $this->form['supervisor_id'] = '';
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
            'form.supervisor_id' => ['nullable', 'exists:employees,id'],
            'form.basic_salary' => ['required', 'numeric', 'min:0'],
        ];
        $this->validate($rules);

        if ($this->editing && $this->employee) {
            $user = $this->employee->user;
            if ($user) {
                $user->first_name = $this->form['first_name'];
                $user->other_names = $this->form['other_names'];
                $user->email = $this->form['email'];
                if (!empty($this->form['password'])) {
                    $user->password = Hash::make($this->form['password']);
                }
                $user->save();
                $user->syncRoles([$this->form['role']]);
            }
            $this->employee->update($this->form);
            
            // Log the create action
            Audit::create([
                'actor_id' => Auth::id(),
                'action' => 'update',
                'target_type' => Employee::class,
                'target_id' => $employee->id,
                'details' => json_encode($this->form),
            ]);
            
            $notification = [
                'type' => 'success',
                'message' => __('Employee updated successfully.'),
                'timestamp' => now()->timestamp,
            ];
            
            $existingNotifications = session('notifications', []);
            if (!is_array($existingNotifications)) {
                $existingNotifications = [];
            }
            $existingNotifications[] = $notification;
            session(['notifications' => $existingNotifications]);
        } else {
            $user = User::create([
                'first_name' => $this->form['first_name'],
                'other_names' => $this->form['other_names'],
                'email' => $this->form['email'],
                'password' => Hash::make($this->form['password']),
            ]);
            $user->assignRole($this->form['role']);
            $employeeData = $this->form;
            $employeeData['user_id'] = $user->id;
            Employee::create($employeeData);
            
            // Log the create action
            Audit::create([
                'actor_id' => Auth::id(),
                'action' => 'create',
                'target_type' => Employee::class,
                'target_id' => $employee->id,
                'details' => json_encode($this->form),
            ]);
            
            $notification = [
                'type' => 'success',
                'message' => __('Employee created successfully.'),
                'timestamp' => now()->timestamp,
            ];
            
            $existingNotifications = session('notifications', []);
            if (!is_array($existingNotifications)) {
                $existingNotifications = [];
            }
            $existingNotifications[] = $notification;
            session(['notifications' => $existingNotifications]);
        }
        $this->redirectRoute('employee.index');
    }

    public function resetForm(): void
    {
        if ($this->editing && $this->employee) {
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
                'supervisor_id' => $this->employee->supervisor_id,
                'basic_salary' => $this->employee->basic_salary ?? '',
            ];
        } else {
            $this->form = [
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
                'supervisor_id' => '',
                'basic_salary' => '',
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

    public function getAvailableSupervisorsProperty()
    {
        if (empty($this->form['role'])) {
            return collect();
        }

        $supervisorRole = '';
        switch ($this->form['role']) {
            case 'Employee':
                $supervisorRole = 'Manager N-2';
                break;
            case 'Manager N-2':
                $supervisorRole = 'Manager N-1';
                break;
            case 'Manager N-1':
                $supervisorRole = 'Executive';
                break;
            case 'Executive':
                // Executives don't have supervisors
                return collect();
            default:
                return collect();
        }

        return User::whereHas('roles', function ($query) use ($supervisorRole) {
            $query->where('name', $supervisorRole);
        })->with('employee')->get()->filter(function ($user) {
            return $user->employee !== null;
        });
    }

    public function getAvailableRolesProperty()
    {
        // Get all roles except the SuperAdmin role and order hierarchy (Executive, Manager N-1, Manager N-2, Employee)
        $roles = Role::where('name', '!=', 'Developer')->get();
        $order = ['Executive', 'Manager N-1', 'Manager N-2', 'Employee'];
        return $roles->sortBy(function ($role) use ($order) {
            $index = array_search($role->name, $order);
            return $index !== false ? $index : count($order);
        })->values();
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
                <a href="{{ route('employee.index') }}" class="border rounded-full py-2 px-2 md:px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.index') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}">
                    <span class="hidden md:flex">
                        {{ __('Employee List') }}
                    </span>
                    <span class="flex: md:hidden">
                        <flux:icon name="numbered-list" variant="solid" class="w-5 h-5" />
                    </span>
                </a>

                {{-- add employee link--}}
                @if($editing)
                <a href="{{ route('employee.show') }}" class="border rounded-full py-2 px-2 md:px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.show') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}">
                    <span class="hidden md:flex">
                        {{ __('Add Employee') }}
                    </span>
                    <span class="flex: md:hidden">
                        <flux:icon name="users" variant="solid" class="w-5 h-5" />
                    </span>
                </a>
                @endif

                {{-- edit employee link --}}
                <a href="{{ $editing && $employee ? route('employee.edit', $employee->id) : route('employee.show') }}" class="border rounded-full py-2 px-2 md:px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.show') || request()->routeIs('employee.edit') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}">
                    <span class="hidden md:flex">
                        {{ $editing ? __('Edit Employee') : __('Add Employee') }}
                    </span>
                    <span class="flex: md:hidden">
                        <flux:icon name="pencil-square" variant="solid" class="w-5 h-5" />
                    </span>
                </a>
            </div>
            
            @if ($editing && $employee)
            <!-- Three dots dropdown menu -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-400 border
                        {{ request()->routeIs('employee.payroll.allowances') || request()->routeIs('employee.payroll.deductions') || request()->routeIs('employee.payroll.payslips') || request()->routeIs('employee.payroll.history') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400' }}">
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
                        <a href="{{ route('employee.payroll.allowances', $employee->id) }}" 
                           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.allowances') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            {{ __('Allowances') }}
                        </a>
                        
                        <a href="{{ route('employee.payroll.deductions', $employee->id) }}" 
                           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.deductions') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            {{ __('Deductions') }}
                        </a>
                        
                        <a href="{{ route('employee.payroll.payslips', $employee->id) }}" 
                           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.payslips') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{ __('Payslips') }}
                        </a>
                        
                        <a href="{{ route('employee.payroll.history', $employee->id) }}" 
                           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.history') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ __('History') }}
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </nav>
    </div>
    
    <div class="relative bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
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
            <div>
                <flux:input
                    wire:model="form.basic_salary"
                    :label="__('Basic Salary (KES)')"
                    type="number"
                    step="0.01"
                    required
                    placeholder="{{ __('Basic Salary') }}" />
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
                <flux:select wire:model.lazy="form.role" :label="__('Role')" required :placeholder="__('Role')">
                    <flux:select.option value="">{{ __('Select Role') }}</flux:select.option>
                    @foreach($this->availableRoles as $role)
                        <flux:select.option value="{{ $role->name }}">{{ $role->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div>
                <flux:select
                    wire:model.lazy="form.supervisor_id"
                    :label="__('Supervisor')"
                    :placeholder="__('Select Supervisor')"
                    :disabled="!$form['role'] || in_array($form['role'], ['Developer', 'HR Administrator', 'Executive', 'New Employee'])"
                >
                    <flux:select.option value="">{{ __('Select Supervisor') }}</flux:select.option>
                    @if($this->availableSupervisors)
                        @foreach($this->availableSupervisors as $supervisor)
                            <flux:select.option value="{{ $supervisor->employee->id }}">{{ $supervisor->first_name }} {{ $supervisor->other_names }} ({{ $supervisor->roles->first()?->name }})</flux:select.option>
                        @endforeach
                    @endif
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
</div>