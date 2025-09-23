<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Payslip;
use App\Models\Employee;
use Illuminate\Support\Facades\Storage;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public ?Employee $employee = null;
    public int $perPage = 10;
    public string $search = '';
    public string $filterStatus = '';
    public string $sortField = 'pay_date';
    public string $sortDirection = 'desc';
    public bool $isDownloading = false;
    public bool $isLoadingExport = false;
    public bool $isLoadingBulkDelete = false;
    public bool $isSearching = false;
    public bool $isFiltering = false;
    public bool $isPaginating = false;
    public bool $isLoadingData = false;
    public bool $showFilters = false;
    public array $selected = [];
    public bool $selectAll = false;
    public bool $showBulkDeleteModal = false;
    public bool $editing = true;

    public function mount($employeeId): void
    {
        $this->employee = Employee::findOrFail($employeeId);
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function downloadPayslip($payslipId): void
    {
        $this->isDownloading = true;
        
        $payslip = Payslip::with('payroll', 'employee.user')->findOrFail($payslipId);
        
        if ($payslip->file_path && Storage::exists($payslip->file_path)) {
            // File exists, proceed to download
            $payslip->update(['is_downloaded' => true, 'downloaded_at' => now()]);
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __('Payslip downloaded successfully.')
            ]);
            
            $this->dispatch('download-file', [
                'url' => Storage::url($payslip->file_path),
                'filename' => $payslip->file_name
            ]);
        } else {
            // File not found, generate new PDF
            try {
                // Prepare data for the PDF template
                $company = [
                    'name' => config('app.name', 'CADEBECK'), // Adjust as needed
                    'address' => 'Your Company Address', // Replace with actual data
                    'phone' => 'Your Phone Number', // Replace with actual data
                    'email' => 'Your Email', // Replace with actual data
                ];
                
                $employee = [
                    'employee_number' => $payslip->employee->employee_number ?? 'N/A',
                    'name' => $payslip->employee->user->first_name . ' ' . ($payslip->employee->user->other_names ?? ''),
                    'department' => $payslip->employee->department->name ?? 'N/A', // Assuming department relationship
                    'designation' => $payslip->employee->designation->name ?? 'N/A', // Assuming designation relationship
                ];
                
                $payroll = [
                    'period' => $payslip->payroll_period,
                    'pay_date' => \Carbon\Carbon::parse($payslip->pay_date)->format('M d, Y'),
                    'basic_salary' => $payslip->payroll->basic_salary ?? 0,
                    'allowances' => [
                        'house' => $payslip->payroll->house_allowance ?? 0,
                        'transport' => $payslip->payroll->transport_allowance ?? 0,
                        'medical' => $payslip->payroll->medical_allowance ?? 0,
                        'overtime' => $payslip->payroll->overtime_allowance ?? 0,
                        'bonus' => $payslip->payroll->bonus ?? 0,
                        'other' => $payslip->payroll->other_allowances ?? 0,
                        'total' => ($payslip->payroll->basic_salary ?? 0) + ($payslip->payroll->house_allowance ?? 0) + ($payslip->payroll->transport_allowance ?? 0) + ($payslip->payroll->medical_allowance ?? 0) + ($payslip->payroll->overtime_allowance ?? 0) + ($payslip->payroll->bonus ?? 0) + ($payslip->payroll->other_allowances ?? 0),
                    ],
                    'deductions' => [
                        'paye' => $payslip->payroll->paye ?? 0,
                        'nhif' => $payslip->payroll->nhif ?? 0,
                        'nssf' => $payslip->payroll->nssf ?? 0,
                        'insurance' => $payslip->payroll->insurance ?? 0,
                        'loan' => $payslip->payroll->loan ?? 0,
                        'other' => $payslip->payroll->other_deductions ?? 0,
                        'total' => ($payslip->payroll->paye ?? 0) + ($payslip->payroll->nhif ?? 0) + ($payslip->payroll->nssf ?? 0) + ($payslip->payroll->insurance ?? 0) + ($payslip->payroll->loan ?? 0) + ($payslip->payroll->other_deductions ?? 0),
                    ],
                    'net_pay' => $payslip->payroll->net_pay ?? 0,
                ];
                
                $payslip_number = $payslip->payslip_number;
                $generated_at = now()->format('M d, Y H:i');
                
                // Generate PDF using DomPDF (assuming barryvdh/laravel-dompdf is installed)
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('PDF-Templates.payslip', compact('payslip_number', 'company', 'employee', 'payroll', 'generated_at'));
                
                // Define file path and name
                $fileName = 'payslip_' . $payslip->payslip_number . '.pdf';
                $filePath = 'temp/' . $fileName;
                
                // Save PDF to storage
                Storage::put($filePath, $pdf->output());
                
                // Update payslip record
                $payslip->update([
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                    'is_downloaded' => true,
                    'downloaded_at' => now(),
                ]);
                
                // Dispatch download
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => __('Payslip generated and downloaded successfully.')
                ]);
                
                $this->dispatch('download-file', [
                    'url' => Storage::url($filePath),
                    'filename' => $fileName
                ]);
            } catch (\Exception $e) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => __('Failed to generate payslip PDF: ') . $e->getMessage()
                ]);
            }
        }
        
        $this->isDownloading = false;
    }

    public function getPayslipsProperty()
    {
        $query = $this->employee->payslips()->with('payroll');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('payslip_number', 'like', '%' . $this->search . '%')
                  ->orWhere('payroll_period', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterStatus === 'emailed') {
            $query->where('is_emailed', true);
        } elseif ($this->filterStatus === 'not_emailed') {
            $query->where('is_emailed', false);
        } elseif ($this->filterStatus === 'downloaded') {
            $query->where('is_downloaded', true);
        } elseif ($this->filterStatus === 'not_downloaded') {
            $query->where('is_downloaded', false);
        }

        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        $query->orderBy($this->sortField, $direction);

        return $query->paginate($this->perPage);
    }

    public function updatedSearch(): void
    {
        $this->isSearching = true;
        $this->resetPage();
        $this->updateSelectAllState();
        $this->isSearching = false;
    }

    public function updatedFilterStatus(): void
    {
        $this->isFiltering = true;
        $this->resetPage();
        $this->updateSelectAllState();
        $this->isFiltering = false;
    }

    public function updatedPage(): void
    {
        $this->isPaginating = true;
        $this->updateSelectAllState();
        $this->isPaginating = false;
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
        $this->updateSelectAllState();
    }

    public function updateSelectAllState(): void
    {
        $payslips = $this->payslips;
        if ($payslips && $payslips->count() > 0) {
            $currentPageIds = $payslips->pluck('id')->toArray();
            $this->selectAll = count($currentPageIds) > 0 && 
                              count(array_intersect($this->selected, $currentPageIds)) === count($currentPageIds);
        } else {
            $this->selectAll = false;
        }
    }

    public function toggleSelectAll(): void
    {
        $payslips = $this->payslips;
        if ($payslips && $payslips->count() > 0) {
            $currentPageIds = $payslips->pluck('id')->toArray();
            if ($this->selectAll) {
                $this->selected = array_values(array_diff($this->selected, $currentPageIds));
                $this->selectAll = false;
            } else {
                $this->selected = array_values(array_unique(array_merge($this->selected, $currentPageIds)));
                $this->selectAll = true;
            }
        }
    }

    public function toggleSelection($payslipId): void
    {
        if (in_array($payslipId, $this->selected)) {
            $this->selected = array_values(array_diff($this->selected, [$payslipId]));
        } else {
            $this->selected[] = $payslipId;
        }
        $this->updateSelectAllState();
    }

    public function updatedSelected(): void
    {
        $this->updateSelectAllState();
    }

    public function selectAllData(): void
    {
        $query = $this->employee->payslips();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('payslip_number', 'like', '%' . $this->search . '%')
                  ->orWhere('payroll_period', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterStatus === 'emailed') {
            $query->where('is_emailed', true);
        } elseif ($this->filterStatus === 'not_emailed') {
            $query->where('is_emailed', false);
        } elseif ($this->filterStatus === 'downloaded') {
            $query->where('is_downloaded', true);
        } elseif ($this->filterStatus === 'not_downloaded') {
            $query->where('is_downloaded', false);
        }

        $this->selected = $query->pluck('id')->toArray();
        $this->updateSelectAllState();
    }
    
    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
    }

    public function bulkDeleteConfirm(): void
    {
        $this->showBulkDeleteModal = true;
    }

    public function bulkDelete(): void
    {
        $this->isLoadingBulkDelete = true;
        $payslips = Payslip::whereIn('id', $this->selected)->get();
        
        foreach ($payslips as $payslip) {
            // Delete the file if it exists
            if ($payslip->file_path && Storage::exists($payslip->file_path)) {
                Storage::delete($payslip->file_path);
            }
            $payslip->delete();
        }

        $this->showBulkDeleteModal = false;
        $this->isLoadingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;
        $this->updateSelectAllState();
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Selected payslips deleted successfully.')
        ]);
    }

    public function exportSelected(): void
    {
        $this->isLoadingExport = true;
        $payslips = Payslip::whereIn('id', $this->selected)->with(['employee.user', 'payroll'])->get();
        $csvData = "ID,Employee,Payslip Number,Payroll Period,Pay Date,Basic Salary,Net Pay,Status\n";
        
        foreach ($payslips as $payslip) {
            $csvData .= '"' . $payslip->id . '","' .
                str_replace('"', '""', $payslip->employee->user->first_name . ' ' . $payslip->employee->user->other_names) . '","' .
                str_replace('"', '""', $payslip->payslip_number) . '","' .
                str_replace('"', '""', $payslip->payroll_period) . '","' .
                $payslip->pay_date . '","' .
                ($payslip->payroll->basic_salary ?? 0) . '","' .
                ($payslip->payroll->net_pay ?? 0) . '","' .
                ($payslip->is_downloaded ? 'Downloaded' : 'Not Downloaded') . '"\n';
        }
        
        $this->isLoadingExport = false;
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'payslips_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('Selected payslips exported successfully.')
        ]);
    }

    public function exportAll(): void
    {
        $this->isLoadingExport = true;
        $query = $this->employee->payslips()->with(['employee.user', 'payroll']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('payslip_number', 'like', '%' . $this->search . '%')
                  ->orWhere('payroll_period', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterStatus === 'emailed') {
            $query->where('is_emailed', true);
        } elseif ($this->filterStatus === 'not_emailed') {
            $query->where('is_emailed', false);
        } elseif ($this->filterStatus === 'downloaded') {
            $query->where('is_downloaded', true);
        } elseif ($this->filterStatus === 'not_downloaded') {
            $query->where('is_downloaded', false);
        }

        $payslips = $query->orderByDesc('pay_date')->get();
        $csvData = "ID,Employee,Payslip Number,Payroll Period,Pay Date,Basic Salary,Net Pay,Status\n";
        
        foreach ($payslips as $payslip) {
            $csvData .= '"' . $payslip->id . '","' .
                str_replace('"', '""', $payslip->employee->user->first_name . ' ' . $payslip->employee->user->other_names) . '","' .
                str_replace('"', '""', $payslip->payslip_number) . '","' .
                str_replace('"', '""', $payslip->payroll_period) . '","' .
                $payslip->pay_date . '","' .
                ($payslip->payroll->basic_salary ?? 0) . '","' .
                ($payslip->payroll->net_pay ?? 0) . '","' .
                ($payslip->is_downloaded ? 'Downloaded' : 'Not Downloaded') . '"\n';
        }
        
        $this->isLoadingExport = false;
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'all_payslips_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('All payslips exported successfully.')
        ]);
    }

    public function shouldShowSkeleton(): bool
    {
        return $this->isLoadingBulkDelete || 
               $this->isSearching || 
               $this->isFiltering || 
               $this->isPaginating ||
               $this->isLoadingExport ||
               $this->isLoadingData;
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

                {{-- add employee link--}}
                @if($editing)
                <a href="{{ route('employee.show') }}" class="border rounded-full py-2 px-2 md:px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.show') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}" wire:navigate>
                    <span class="hidden md:flex">
                        {{ __('Add Employee') }}
                    </span>
                    <span class="flex: md:hidden">
                        <flux:icon name="users" variant="solid" class="w-5 h-5" />
                    </span>
                </a>
                @endif

                {{-- edit employee link --}}
                <a href="{{ $editing && $employee ? route('employee.edit', $employee->id) : route('employee.show') }}" class="border rounded-full py-2 px-2 md:px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('employee.show') || request()->routeIs('employee.edit') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}" wire:navigate>
                    <span class="hidden md:flex">
                        {{ $editing ? __('Edit Employee') : __('Add Employee') }}
                    </span>
                    <span class="flex: md:hidden">
                        <flux:icon name="pencil-square" variant="solid" class="w-5 h-5" />
                    </span>
                </a>
            </div>
            
            <!-- Three dots dropdown menu -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-400 border
                        {{ request()->routeIs('employee.payroll.allowances') || request()->routeIs('employee.payroll.deductions') || request()->routeIs('employee.payroll.payslips') || request()->routeIs('employee.payroll.history', $employee->id) ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400' }}">
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
                           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.allowances') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}" wire:navigate>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            {{ __('Allowances') }}
                        </a>
                        
                        <a href="{{ route('employee.payroll.deductions', $employee->id) }}" 
                           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.deductions') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}" wire:navigate>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            {{ __('Deductions') }}
                        </a>
                        
                        <a href="{{ route('employee.payroll.payslips', $employee->id) }}" 
                           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors duration-200 {{ request()->routeIs('employee.payroll.payslips') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : 'text-zinc-700 dark:text-zinc-300' }}" wire:navigate>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{ __('Payslips') }}
                        </a>
                        
                        <a href="{{ route('employee.payroll.history', $employee->id) }}" 
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

    <!-- Main Content Card -->
    <div class="relative bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <!-- Header with Icon -->
        <div class="flex justify-between mb-8 items-center">
            <div class="flex items-center">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h2 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                    {{ __('Payslips for') }} {{ $employee->user->first_name }}
                    <span class="absolute -bottom-2 left-0 w-[100px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                </h2>
            </div>

            <div class="flex items-center gap-3">
                <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" wire:click="exportAll" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    {{ $isLoadingExport ? __('Exporting...') : __('Export All') }}
                </flux:button>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="flex flex-wrap gap-6 items-center mb-6">
            <div class="relative w-80">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg class="w-5 h-5 text-blue-200 dark:text-indigo-400 z-[1]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" fill="none"></circle>
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"></path>
                </svg>
            </span>
            <input type="text" wire:model.live.debounce.500ms="search"
                class="w-full pl-10 pr-4 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white transition shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md"
                placeholder="{{ __('Search payslips...') }}">
            </div>
            
            <button type="button" wire:click="toggleFilters"
            class="flex items-center gap-1 px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 bg-white/80 dark:bg-zinc-900/80 text-blue-600 dark:text-indigo-300 hover:bg-blue-50/80 dark:hover:bg-zinc-800/80 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h8m-8 6h16"></path>
            </svg>
            <span class="hidden lg:inline">{{ __('Filters') }}</span>
            </button>
        </div>

        <!-- Advanced Filters -->
        @if($showFilters)
            <div class="flex flex-wrap gap-6 mt-6 items-center animate-fade-in">
                <flux:select wire:model.live="filterStatus" placeholder="{{ __('All Status') }}" class="!ps-3 pe-4 !py-2 !rounded-full border !border-blue-200 dark:!border-indigo-700 !focus:ring-2 !focus:ring-blue-400 dark:!bg-zinc-800/80 dark:!text-white !shadow-sm !bg-white/80 dark:!bg-zinc-900/80 !backdrop-blur-md !w-fit !outline-none">
                    <flux:select.option value="">{{ __('All Status') }}</flux:select.option>
                    <flux:select.option value="emailed">{{ __('Emailed') }}</flux:select.option>
                    <flux:select.option value="not_emailed">{{ __('Not Emailed') }}</flux:select.option>
                    <flux:select.option value="downloaded">{{ __('Downloaded') }}</flux:select.option>
                    <flux:select.option value="not_downloaded">{{ __('Not Downloaded') }}</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="perPage" class="!ps-3 pe-4 !py-2 !rounded-full border !border-blue-200 dark:!border-indigo-700 !focus:ring-2 !focus:ring-blue-400 dark:!bg-zinc-800/80 dark:!text-white !shadow-sm !bg-white/80 dark:!bg-zinc-900/80 !backdrop-blur-md !w-fit !outline-none">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                </flux:select>
            </div>
        @endif

        @if (count($selected) > 0)
            <div class="flex items-center justify-between flex-wrap mt-6 p-4 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 dark:from-zinc-800/50 dark:to-zinc-700/50 rounded-xl border border-blue-200 dark:border-zinc-700 backdrop-blur-sm">
                <div class="flex items-center gap-2 py-2">
                    <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                    {{ count($selected) }} {{ __('item(s) selected') }}
                    </span>
                    @if(count($selected) < ($this->payslips ? $this->payslips->total() : 0))
                    <button type="button" wire:click="selectAllData"
                        class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                        {{ __('Select all') }} {{ $this->payslips ? $this->payslips->total() : 0 }} {{ __('items') }}
                    </button>
                    @endif
                </div>
                <div class="flex items-end justify-end gap-3 md:col-span-2 lg:col-span-3">
                    @can('export_payslip')
                        <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" wire:click="exportSelected" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            {{ $isLoadingExport ? __('Exporting...') : __('Export Selected') }}
                        </flux:button>
                    @else
                        <flux:button icon:trailing="arrow-up-tray" variant="primary" type="button" :disabled="true" class="flex flex-row items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            {{ __('Exporting Denied') }}
                        </flux:button>
                    @endcan
                    @can('delete_payslip')
                        <flux:button icon:trailing="trash" variant="primary" type="button" wire:click="bulkDeleteConfirm" class="flex flex-row items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                            {{ __('Delete Selected') }}
                        </flux:button>
                    @else
                        <flux:button icon:trailing="trash" variant="primary" type="button" :disabled="true" class="flex flex-row items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-2 !rounded-full font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                            {{ __('Deleting Denied') }}
                        </flux:button>
                    @endcan
                </div>
            </div>
        @endif

        <!-- Table -->
        <div class="overflow-x-auto bg-transparent mt-6">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead>
                    <tr class="h-16 bg-zinc-800/5 dark:bg-white/10 text-zinc-600 dark:text-white/70">
                        <th class="px-3 py-3 text-left font-semibold uppercase tracking-wider w-12">
                            <button type="button"
                                wire:click="toggleSelectAll"
                                class="rounded focus:ring-2 focus:ring-pink-400 transition-colors duration-200
                                    @if($selectAll)
                                        bg-pink-500 text-white p-[2px]
                                    @else
                                        bg-transparent text-pink-500 border border-gray-500 p-[6px]
                                    @endif
                                    flex items-center gap-2"
                            >
                                @if($selectAll)
                                    <svg class="w-3 h-3 text-gray-800 font-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-3 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[140px] gap-1 whitespace-nowrap" wire:click="sortBy('payslip_number')">
                            {{ __('Payslip Number') }}
                            @if ($this->sortField === 'payslip_number')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @else
                                <flux:icon name="arrows-up-down" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[120px]" wire:click="sortBy('payroll_period')">
                            {{ __('Period') }}
                            @if ($this->sortField === 'payroll_period')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @else
                                <flux:icon name="arrows-up-down" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @endif
                        </th>
                        <th class="px-3 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none min-w-[120px] gap-1 whitespace-nowrap" wire:click="sortBy('pay_date')">
                            {{ __('Pay Date') }}
                            @if ($this->sortField === 'pay_date')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @else
                                <flux:icon name="arrows-up-down" class="w-3 h-3 text-gray-400 inline ml-1" />
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider min-w-[120px]">{{ __('Gross Pay') }}</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider min-w-[120px]">{{ __('Net Pay') }}</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider min-w-[150px]">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-left font-semibold uppercase tracking-wider min-w-[100px]">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if($this->shouldShowSkeleton())
                        @for($i = 0; $i < $perPage; $i++)
                            <tr class="animate-pulse border-b border-gray-200 dark:border-gray-700">
                                <td class="px-3 py-4"><div class="h-4 w-4 bg-purple-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-4 py-4"><div class="h-4 w-24 bg-purple-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-4 py-4"><div class="h-4 w-20 bg-purple-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-4 py-4"><div class="h-4 w-20 bg-purple-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-4 py-4"><div class="h-4 w-16 bg-purple-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-4 py-4"><div class="h-4 w-16 bg-purple-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-4 py-4"><div class="h-4 w-16 bg-purple-100 dark:bg-zinc-800 rounded"></div></td>
                                <td class="px-4 py-4"><div class="flex gap-2"><div class="h-8 w-8 bg-gray-100 dark:bg-zinc-800 rounded"></div></div></td>
                            </tr>
                        @endfor
                    @else
                        @forelse($this->payslips as $payslip)
                        <tr class="hover:bg-gray-100 dark:hover:bg-white/20 group border-b border-gray-200 dark:border-gray-700 transition-all duration-500 ease-in-out" wire:loading.class.delay="opacity-50 dark:opacity-40">
                            <td class="px-3 py-4">
                                <button type="button"
                                        wire:click="toggleSelection({{ $payslip->id }})"
                                        class="rounded focus:ring-2 focus:ring-pink-400 transition-colors duration-200
                                            @if(in_array($payslip->id, $selected))
                                                bg-pink-500 text-white p-[2px]
                                            @else
                                                bg-transparent text-pink-500 border border-gray-500 p-[6px]
                                            @endif
                                            flex items-center gap-2"
                                    >
                                        @if(in_array($payslip->id, $selected))
                                            <svg class="w-3 h-3 text-gray-800 font-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        @endif
                                    </button>
                            </td>
                            <td class="px-4 py-4 text-gray-900 dark:text-white font-semibold">
                                {{ $payslip->payslip_number }}
                            </td>
                        <td class="px-4 py-4 text-gray-900 dark:text-white font-medium">
                            {{ $payslip->payroll_period }}
                        </td>
                        <td class="px-4 py-4 text-gray-900 dark:text-white">
                            {{ \Carbon\Carbon::parse($payslip->pay_date)->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-4 text-gray-900 dark:text-white font-medium">
                            @if($payslip->payroll)
                            <span class="text-blue-600 dark:text-blue-400">
                                USD {{ number_format($payslip->payroll->gross_pay, 2) }}
                            </span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-gray-900 dark:text-white font-bold">
                            @if($payslip->payroll)
                            <span class="text-green-600 dark:text-green-400">
                                USD {{ number_format($payslip->payroll->net_pay, 2) }}
                            </span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap gap-2">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $payslip->is_emailed ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' }}">
                                    {{ $payslip->is_emailed ? __('Emailed') : __('Not Emailed') }}
                                </span>
                                @if($payslip->is_downloaded)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    {{ __('Downloaded') }}
                                </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <flux:button wire:click="downloadPayslip({{ $payslip->id }})"
                                icon="arrow-down-tray"
                                variant="ghost"
                                size="sm"
                                class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 hover:bg-purple-200 dark:hover:bg-purple-900/50 transition">
                            </flux:button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-8 h-8 text-gray-300 dark:text-zinc-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                {{ __('No payslips found for this employee.') }}
                            </div>
                        </td>
                    </tr>
                    @endforelse
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $this->payslips->links() }}
        </div>
    </div>

    <!-- Bulk Delete Confirmation Modal -->
    @if($showBulkDeleteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
        <div class="bg-gradient-to-br from-purple-50/80 via-white/80 to-red-100/80 dark:from-zinc-900/80 dark:via-zinc-800/80 dark:to-zinc-900/80 backdrop-blur-xl rounded-xl shadow-2xl p-10 max-w-md w-full border border-purple-200 dark:border-zinc-800">
            <h3 class="text-2xl font-extrabold text-purple-600 dark:text-purple-400 flex items-center gap-2 mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                {{ __('Confirm Bulk Delete') }}
            </h3>
            <p class="mb-6 text-zinc-700 dark:text-zinc-300 font-semibold">
                {{ __('Are you sure you want to delete the selected payslips? This action will permanently delete the files and cannot be undone.') }}
            </p>
            <div class="flex justify-end gap-3">
                <button wire:click="bulkDelete"
                    class="bg-gradient-to-r from-purple-500 to-red-500 hover:from-purple-600 hover:to-red-600 text-white px-5 py-3 rounded-xl font-semibold shadow-lg focus:outline-none focus:ring-2 focus:ring-red-400 transition backdrop-blur-sm"
                    @if ($isLoadingBulkDelete) disabled @endif>
                    {{ $isLoadingBulkDelete ? __('Deleting...') : __('Delete Selected') }}
                </button>
                <button wire:click="$set('showBulkDeleteModal', false)"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-xl font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                    {{ __('Cancel') }}
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', function () {
        Livewire.on('download-file', function (data) {
            const link = document.createElement('a');
            link.href = data[0].url;
            link.download = data[0].filename;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    });
</script>