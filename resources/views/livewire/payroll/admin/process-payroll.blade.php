<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Payroll;
use App\Models\Employee;
use App\Models\Payslip;
use App\Services\PayrollProcessingService;
use App\Services\PayslipService;
use App\Notifications\PayrollProcessedNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $selectedPeriod = '';
    public ?Carbon $payDate = null;
    public bool $confirmProcess = false;
    public bool $processing = false;
    public ?array $processResult = null;
    public string $search = '';
    public string $statusFilter = 'all';
    public int $perPage = 10;

    // UI State
    public bool $showFilters = false;
    public bool $isLoadingData = false;
    public bool $isSearching = false;
    public bool $isFiltering = false;
    public bool $isPaginating = false;
    public bool $isLoadingExport = false;
    public bool $showBulkActionsModal = false;
    public string $bulkAction = '';

    // Bulk actions
    public array $selected = [];
    public bool $selectAll = false;

    public array $availablePeriods = [];
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    public function mount(): void
    {
        // Set default period to current month
        $this->selectedPeriod = Carbon::now()->format('m/Y');
        $this->payDate = Carbon::now()->endOfMonth();

        // Initialize available periods
        $this->availablePeriods = [
            Carbon::now()->format('m/Y'),
            Carbon::now()->subMonth()->format('m/Y'),
            Carbon::now()->subMonths(2)->format('m/Y'),
            Carbon::now()->subMonths(3)->format('m/Y'),
        ];
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

    public function updatedSearch(): void
    {
        $this->isSearching = true;
        $this->resetPage();
        $this->updateSelectAllState();
        $this->isSearching = false;
    }

    public function updatedStatusFilter(): void
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
        $payrolls = $this->payrolls;
        if ($payrolls && $payrolls->count() > 0) {
            $currentPageIds = $payrolls->pluck('id')->toArray();
            $this->selectAll = count($currentPageIds) > 0 && 
                              count(array_intersect($this->selected, $currentPageIds)) === count($currentPageIds);
        } else {
            $this->selectAll = false;
        }
    }

    public function toggleSelectAll(): void
    {
        $payrolls = $this->payrolls;
        if ($payrolls && $payrolls->count() > 0) {
            $currentPageIds = $payrolls->pluck('id')->toArray();
            if ($this->selectAll) {
                $this->selected = array_values(array_diff($this->selected, $currentPageIds));
                $this->selectAll = false;
            } else {
                $this->selected = array_values(array_unique(array_merge($this->selected, $currentPageIds)));
                $this->selectAll = true;
            }
        }
    }

    public function updatedSelected(): void
    {
        $this->updateSelectAllState();
    }

    public function selectAllData(): void
    {
        $query = Payroll::query();
        if ($this->search) {
            $query->where(function($q) {
                $q->where('payroll_period', 'like', "%{$this->search}%")
                  ->orWhereHas('employee', function($eq) {
                      $eq->where('first_name', 'like', "%{$this->search}%")
                         ->orWhere('other_names', 'like', "%{$this->search}%")
                         ->orWhere('staff_number', 'like', "%{$this->search}%");
                  });
            });
        }
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }
        $this->selected = $query->pluck('id')->toArray();
        $this->updateSelectAllState();
    }

    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
    }

    public function shouldShowSkeleton(): bool
    {
        return $this->processing || 
               $this->isSearching || 
               $this->isFiltering || 
               $this->isPaginating ||
               $this->isLoadingExport ||
               $this->isLoadingData;
    }

    public function processPayroll(): void
    {
        if (!$this->selectedPeriod || !$this->payDate) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Please select a payroll period and pay date.'
            ]);
            return;
        }

        $this->processing = true;
        $this->confirmProcess = false;

        try {
            $payrollService = app(PayrollProcessingService::class);
            $result = $payrollService->processPayroll($this->selectedPeriod, $this->payDate);

            $this->processResult = $result;
            $this->processing = false;

            if ($result['success']) {
                // Send notifications to all processed employees synchronously
                $processedPayrolls = Payroll::with(['employee.user'])
                    ->where('payroll_period', $this->selectedPeriod)
                    ->where('status', 'processed')
                    ->get();

                $notificationCount = 0;
                $notificationErrors = 0;

                foreach ($processedPayrolls as $payroll) {
                    try {
                        if ($payroll->employee && $payroll->employee->user && $payroll->employee->user->email) {
                            // Create database notification first (always succeeds)
                            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                                'id' => \Illuminate\Support\Str::uuid(),
                                'type' => 'App\\Notifications\\PayrollProcessedNotification',
                                'notifiable_type' => 'App\\Models\\User',
                                'notifiable_id' => $payroll->employee->user->id,
                                'data' => json_encode([
                                    'payroll_id' => $payroll->id,
                                    'payroll_period' => $payroll->payroll_period,
                                    'subject' => 'Payroll Processing Completed',
                                    'message' => 'Your payroll for ' . $payroll->payroll_period . ' has been processed and is ready for review.',
                                    'type' => 'payroll_processed',
                                    'action_url' => route('employee.payroll-history'),
                                ]),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            // Send email notification synchronously (notify the related User)
                            $payroll->employee->user->notify(new PayrollProcessedNotification($payroll));

                            $notificationCount++;

                            // Add delay to avoid Mailtrap rate limiting (2 seconds between emails)
                            if ($notificationCount < $processedPayrolls->count()) {
                                sleep(2);
                            }
                        } else {
                            \Log::warning('Employee missing user or email', [
                                'employee_id' => $payroll->employee->id ?? null,
                                'payroll_id' => $payroll->id
                            ]);
                            $notificationErrors++;
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Failed to send payroll notification', [
                            'error' => $e->getMessage(),
                            'employee_id' => $payroll->employee->id ?? null,
                            'user_email' => $payroll->employee->user->email ?? 'N/A',
                            'payroll_id' => $payroll->id
                        ]);
                        $notificationErrors++;
                    }
                }

                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => "Payroll processed successfully! {$result['processed_count']} employees processed, {$notificationCount} notifications sent, {$notificationErrors} errors."
                ]);
            } else {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Payroll processing failed. Please check the errors.'
                ]);
            }
        } catch (\Exception $e) {
            $this->processing = false;
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error processing payroll: ' . $e->getMessage()
            ]);
        }
    }

    public function confirmBulkAction(string $action): void
    {
        if (empty($this->selected)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Please select payrolls first.'
            ]);
            return;
        }

        $this->bulkAction = $action;
        $this->showBulkActionsModal = true;
    }

    public function executeBulkAction(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $payrolls = Payroll::with(['employee.user', 'employee.department', 'employee.designation'])
            ->whereIn('id', $this->selected)
            ->get();
        $payrollService = app(PayrollProcessingService::class);

        switch ($this->bulkAction) {
            case 'approve':
                // Only process payrolls with 'draft' status
                $filteredPayrolls = $payrolls->where('status', 'draft');
                if ($filteredPayrolls->isEmpty()) {
                    $this->dispatch('notify', [
                        'type' => 'error',
                        'message' => 'No draft payrolls found in selection. Only draft payrolls can be approved.'
                    ]);
                    $this->showBulkActionsModal = false;
                    $this->selected = [];
                    $this->selectAll = false;
                    $this->updateSelectAllState();
                    return;
                }

                $result = $payrollService->bulkApprovePayrolls($filteredPayrolls, Auth::user());
                if ($result['approved_count'] > 0) {
                    $this->dispatch('notify', [
                        'type' => 'success',
                        'message' => "{$result['approved_count']} payrolls approved successfully. {$result['notifications_sent']} notifications sent."
                    ]);
                }
                break;

            case 'mark_paid':
                // Only process payrolls with 'processed' status
                $filteredPayrolls = $payrolls->filter(function($payroll) {
                    return $payroll->status === 'processed';
                });

                // Debug: Log the statuses of selected payrolls
                \Log::info('Bulk mark as paid - Selected payrolls', [
                    'selected_ids' => $this->selected,
                    'payroll_statuses' => $payrolls->map(function($p) {
                        return ['id' => $p->id, 'status' => $p->status];
                    })->toArray(),
                    'filtered_count' => $filteredPayrolls->count()
                ]);

                if ($filteredPayrolls->isEmpty()) {
                    $this->dispatch('notify', [
                        'type' => 'error',
                        'message' => 'No processed payrolls found in selection. Only processed payrolls can be marked as paid.'
                    ]);
                    $this->showBulkActionsModal = false;
                    $this->selected = [];
                    $this->selectAll = false;
                    $this->updateSelectAllState();
                    return;
                }

                try {
                    $result = $payrollService->bulkMarkAsPaid($filteredPayrolls);

                    if ($result['paid_count'] > 0) {
                        $message = "{$result['paid_count']} payrolls marked as paid, payslips generated.";
                        if ($result['notifications_sent'] > 0) {
                            $message .= " {$result['notifications_sent']} rich email notifications sent.";
                        }
                        if ($result['notification_errors'] > 0) {
                            $message .= " {$result['notification_errors']} notification errors.";
                        }

                        $this->dispatch('notify', [
                            'type' => 'success',
                            'message' => $message
                        ]);
                    }

                    if ($result['error_count'] > 0) {
                        $errorMessage = "{$result['error_count']} payrolls failed to be marked as paid.";
                        if (!empty($result['errors'])) {
                            $errorDetails = collect($result['errors'])->pluck('error')->join('; ');
                            $errorMessage .= " Errors: " . $errorDetails;
                        }

                        $this->dispatch('notify', [
                            'type' => 'error',
                            'message' => $errorMessage
                        ]);
                    }
                } catch (\Exception $e) {
                    $this->dispatch('notify', [
                        'type' => 'error',
                        'message' => 'Error marking payrolls as paid: ' . $e->getMessage()
                    ]);
                }
                break;

            case 'send_payslips':
                $sent = 0;
                $failed = 0;

                foreach ($payrolls as $payroll) {
                    if ($payroll->payslip && $payroll->employee && $payroll->employee->user && $payroll->employee->user->email) {
                        try {
                            // Create database notification first (always succeeds)
                            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                                'id' => \Illuminate\Support\Str::uuid(),
                                'type' => 'App\\Notifications\\PayslipNotification',
                                'notifiable_type' => 'App\\Models\\User',
                                'notifiable_id' => $payroll->employee->user->id,
                                'data' => json_encode([
                                    'payslip_id' => $payroll->payslip->id,
                                    'payroll_period' => $payroll->payroll_period,
                                    'subject' => 'Your Payslip is Ready',
                                    'message' => 'Your payslip for ' . $payroll->payroll_period . ' is now available for download.',
                                    'type' => 'payslip_generated',
                                    'action_url' => route('employee.payroll-history'),
                                ]),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                        // Send email notification synchronously
                        $payroll->employee->user->notify(new \App\Notifications\PayslipNotification(
                            $payroll->payslip,
                            'Your Payslip is Ready',
                            'Your payslip for ' . $payroll->payroll_period . ' is now available for download.'
                        ));

                        $sent++;
                        } catch (\Exception $e) {
                            \Log::warning('Failed to queue payslip notification', [
                                'payroll_id' => $payroll->id,
                                'employee_id' => $payroll->employee->id ?? null,
                                'error' => $e->getMessage()
                            ]);
                            $failed++;
                        }
                    } else {
                        $failed++;
                    }
                }

                if ($sent > 0) {
                    $this->dispatch('notify', [
                        'type' => 'success',
                        'message' => "{$sent} payslip notifications sent successfully."
                    ]);
                }
                if ($failed > 0) {
                    $this->dispatch('notify', [
                        'type' => 'warning',
                        'message' => "{$failed} payslip notifications failed to queue."
                    ]);
                }
                break;
        }

        $this->showBulkActionsModal = false;
        $this->selected = [];
        $this->selectAll = false;
        $this->updateSelectAllState();
    }

    public function getPayrollsProperty()
    {
        $query = Payroll::with(['employee', 'payslip']);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('payroll_period', 'like', "%{$this->search}%")
                  ->orWhereHas('employee', function($eq) {
                      $eq->where('first_name', 'like', "%{$this->search}%")
                         ->orWhere('other_names', 'like', "%{$this->search}%")
                         ->orWhere('staff_number', 'like', "%{$this->search}%");
                  });
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        if ($this->sortField === 'employee') {
            $query->leftJoin('employees', 'payrolls.employee_id', '=', 'employees.id')
                  ->select('payrolls.*')
                  ->orderByRaw("CONCAT(employees.first_name, ' ', employees.other_names) $direction");
        } else {
            $field = in_array($this->sortField, ['payroll_period', 'gross_pay', 'net_pay', 'status', 'created_at']) 
                   ? $this->sortField : 'created_at';
            $query->orderBy($field, $direction);
        }

        return $query->paginate($this->perPage);
    }

    public function downloadPayslip(Payslip $payslip)
    {
        if (!Auth::user()->can('process_payroll')) {
            abort(403, 'Access denied. Only payroll administrators can download payslips.');
        }

        $payslipService = app(PayslipService::class);

        // Ensure payslip file exists (will regenerate if deleted)
        $filePath = $payslipService->ensurePayslipFileExists($payslip);

        if (Storage::disk('public')->exists($filePath)) {
            return response()->download(storage_path('app/public/' . $filePath), $payslip->file_name);
        }

        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Payslip file could not be generated. Please contact the system administrator.'
        ]);
    }

    public function exportSelected(): void
    {
        $this->isLoadingExport = true;
        $payrolls = Payroll::with('employee')->whereIn('id', $this->selected)->get();

        $csvData = "Employee Number,Employee Name,Period,Gross Pay,Net Pay,Status\n";
        foreach ($payrolls as $payroll) {
            $csvData .= '"' . ($payroll->employee ? $payroll->employee->staff_number : 'N/A') . '","' .
                       str_replace('"', '""', ($payroll->employee ? $payroll->employee->first_name . ' ' . $payroll->employee->other_names : 'N/A')) . '","' .
                       $payroll->payroll_period . '","' .
                       $payroll->gross_pay . '","' .
                       $payroll->net_pay . '","' .
                       $payroll->status . '"' . "\n";
        }
        $this->isLoadingExport = false;
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'payrolls_selected_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Payrolls exported successfully.')]);
    }

    public function exportAll(): void
    {
        $this->isLoadingExport = true;
        $query = Payroll::with('employee');
        if ($this->search) {
            $query->where(function($q) {
                $q->where('payroll_period', 'like', "%{$this->search}%")
                  ->orWhereHas('employee', function($eq) {
                      $eq->where('first_name', 'like', "%{$this->search}%")
                         ->orWhere('other_names', 'like', "%{$this->search}%")
                         ->orWhere('staff_number', 'like', "%{$this->search}%");
                  });
            });
        }
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }
        $payrolls = $query->orderByDesc('created_at')->get();

        $csvData = "Employee Number,Employee Name,Period,Gross Pay,Net Pay,Status\n";
        foreach ($payrolls as $payroll) {
            $csvData .= '"' . ($payroll->employee ? $payroll->employee->staff_number : 'N/A') . '","' .
                       str_replace('"', '""', ($payroll->employee ? $payroll->employee->first_name . ' ' . $payroll->employee->other_names : 'N/A')) . '","' .
                       $payroll->payroll_period . '","' .
                       $payroll->gross_pay . '","' .
                       $payroll->net_pay . '","' .
                       $payroll->status . '"' . "\n";
        }
        $this->isLoadingExport = false;
        $this->dispatch('download-csv', [
            'data' => $csvData,
            'filename' => 'all_payrolls_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
        $this->dispatch('notify', ['type' => 'success', 'message' => __('Payrolls exported successfully.')]);
    }

    public function getPayrollSummaryProperty()
    {
        if (!$this->selectedPeriod) {
            return null;
        }

        $payrollService = app(PayrollProcessingService::class);
        return $payrollService->getPayrollSummary($this->selectedPeriod);
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
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 z-10 relative border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <nav class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h1 class="text-xl font-bold text-gray-800 dark:text-gray-200">
                    {{ __('Payroll Processing') }}
                </h1>
            </div>
        </nav>
    </div>

    <!-- Process Payroll Section -->
    <div class="relative bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40 mb-8">
        <div class="flex items-center gap-3 mb-6">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-800 via-blue-500 to-purple-500 tracking-tight drop-shadow-lg relative inline-block">
                {{ __('Process Payroll') }}
                <span class="absolute -bottom-2 left-0 w-[150px] h-1 rounded-full bg-gradient-to-r from-blue-800 via-blue-500 to-purple-500"></span>
            </h1>
        </div>

        <!-- Payroll Configuration -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="relative">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Payroll Period') }}</label>
                <select wire:model="selectedPeriod"
                    class="w-full px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                    @foreach($this->availablePeriods as $period)
                        <option value="{{ $period }}">{{ $period }}</option>
                    @endforeach
                </select>
            </div>
            <div class="relative">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Pay Date') }}</label>
                <input type="date" wire:model="payDate"
                    class="w-full px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
            </div>
            <div class="flex items-end">
                <button wire:click="$set('confirmProcess', true)"
                    wire:loading.attr="disabled"
                    class="flex items-center gap-2 px-6 py-3 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 hover:from-blue-600 hover:to-purple-600 text-white font-semibold shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all duration-200 disabled:opacity-50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    {{ __('Process Payroll') }}
                </button>
            </div>
        </div>

        <!-- Payroll Summary -->
        @if($this->payrollSummary)
            <div class="bg-gradient-to-r from-blue-50/80 to-indigo-50/80 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800 backdrop-blur-sm">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4">{{ __('Payroll Summary for') }} {{ $this->selectedPeriod }}</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ $this->payrollSummary['total_employees'] }}</div>
                        <div class="text-sm text-blue-600">{{ __('Employees') }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">USD {{ number_format($this->payrollSummary['total_gross_pay'], 2) }}</div>
                        <div class="text-sm text-green-600">{{ __('Total Gross Pay') }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">USD {{ number_format($this->payrollSummary['total_deductions'], 2) }}</div>
                        <div class="text-sm text-red-600">{{ __('Total Deductions') }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">USD {{ number_format($this->payrollSummary['total_net_pay'], 2) }}</div>
                        <div class="text-sm text-purple-600">{{ __('Total Net Pay') }}</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @can('process_payroll')
        <!-- Payroll Management Section -->
        <div class="relative bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
            <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
                <div class="flex items-center gap-3 mb-8">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                        {{ __('Payroll Management') }}
                        <span class="absolute -bottom-2 left-0 w-[180px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                    </h1>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="exportAll"
                        class="flex items-center gap-2 px-2 lg:px-4 py-2 rounded-full border border-purple-200 dark:border-purple-700 text-purple-600 dark:text-purple-400 bg-purple-50/80 dark:bg-purple-900/20 hover:bg-purple-100/80 dark:hover:bg-purple-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-purple-400 transition"
                        @if ($isLoadingExport) disabled @endif>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <span class="hidden lg:inline">
                            {{ $isLoadingExport ? __('Exporting...') : __('Export All') }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Search and Filters -->
            <div>
                <div class="flex flex-wrap gap-8 items-center">
                    <div class="relative w-80">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-5 h-5 text-blue-200 dark:text-indigo-400 z-[1]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" fill="none"></circle>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"></path>
                            </svg>
                        </span>
                        <input type="text" wire:model.live.debounce.500ms="search"
                            class="w-full pl-10 pr-4 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white transition shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md"
                            placeholder="{{ __('Search payrolls...') }}">
                    </div>
                    <button type="button" wire:click="toggleFilters"
                        class="flex items-center gap-1 px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 bg-white/80 dark:bg-zinc-900/80 text-blue-600 dark:text-indigo-300 hover:bg-blue-50/80 dark:hover:bg-zinc-800/80 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h8m-8 6h16"></path>
                        </svg>
                        <span class="hidden lg:inline">{{ __('Filters') }}</span>
                    </button>
                </div>
            </div>

            <!-- Filters Section -->
            <div>
                @if ($showFilters ?? false)
                    <div class="flex flex-wrap gap-6 mt-6 items-center animate-fade-in">
                        <select wire:model.live="statusFilter"
                            class="px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                            <option value="all">{{ __('All Statuses') }}</option>
                            <option value="draft">{{ __('Draft') }}</option>
                            <option value="processed">{{ __('Processed') }}</option>
                            <option value="paid">{{ __('Paid') }}</option>
                        </select>
                        <select wire:model.live="perPage"
                            class="px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                @endif
            </div>

            <!-- Bulk Actions Bar -->
            @if (count($selected) > 0)
                <div class="flex items-center justify-between flex-wrap mt-6 p-4 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 dark:from-zinc-800/50 dark:to-zinc-700/50 rounded-xl border border-blue-200 dark:border-zinc-700 backdrop-blur-sm">
                    <div class="flex items-center gap-2 py-2">
                        <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                            {{ count($selected) }} {{ __('item(s) selected') }}
                        </span>
                        @if(count($selected) < ($this->payrolls ? $this->payrolls->total() : 0))
                            <button type="button" wire:click="selectAllData"
                                class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                {{ __('Select all') }} {{ $this->payrolls ? $this->payrolls->total() : 0 }} {{ __('items') }}
                            </button>
                        @endif
                        <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" />
                            </svg>
                            Actions apply only to eligible statuses
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap mt-2 md:mt-0">
                        <button type="button" wire:click="exportSelected"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl border border-purple-200 dark:border-purple-700 text-purple-600 dark:text-purple-400 bg-purple-50/80 dark:bg-purple-900/20 hover:bg-purple-100/80 dark:hover:bg-purple-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-purple-400 transition"
                            @if ($isLoadingExport) disabled @endif>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            {{ $isLoadingExport ? __('Exporting...') : __('Export Selected') }}
                        </button>
                        <button type="button" wire:click="confirmBulkAction('approve')"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl border border-yellow-200 dark:border-yellow-700 text-yellow-600 dark:text-yellow-400 bg-yellow-50/80 dark:bg-yellow-900/20 hover:bg-yellow-100/80 dark:hover:bg-yellow-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-yellow-400 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ __('Approve Selected') }}
                            <span class="text-xs bg-yellow-200 dark:bg-yellow-800 px-1.5 py-0.5 rounded text-yellow-800 dark:text-yellow-200 ml-1">
                                Draft only
                            </span>
                        </button>
                        <button type="button" wire:click="confirmBulkAction('mark_paid')"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white font-semibold shadow-lg focus:outline-none focus:ring-2 focus:ring-green-400 backdrop-blur-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            {{ __('Mark as Paid') }}
                            <span class="text-xs bg-green-600 px-1.5 py-0.5 rounded text-white ml-1">
                                Processed only
                            </span>
                        </button>
                        <button type="button" wire:click="confirmBulkAction('send_payslips')"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600 text-white font-semibold shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-400 backdrop-blur-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            {{ __('Send Payslips') }}
                        </button>
                    </div>
                </div>
            @endif

            <!-- Payrolls Table -->
            <div class="overflow-x-auto bg-transparent mt-6">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead>
                        <tr class="h-16 bg-zinc-800/5 dark:bg-white/10 text-zinc-600 dark:text-white/70">
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">
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
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('employee')">
                                {{ __('Employee') }}
                                @if($this->sortField === 'employee')
                                    <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        @endif
                                    </svg>
                                @endif
                            </th>
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('payroll_period')">
                                {{ __('Period') }}
                                @if($this->sortField === 'payroll_period')
                                    <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        @endif
                                    </svg>
                                @endif
                            </th>
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('gross_pay')">
                                {{ __('Gross Pay') }}
                                @if($this->sortField === 'gross_pay')
                                    <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        @endif
                                    </svg>
                                @endif
                            </th>
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('net_pay')">
                                {{ __('Net Pay') }}
                                @if($this->sortField === 'net_pay')
                                    <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        @endif
                                    </svg>
                                @endif
                            </th>
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('status')">
                                {{ __('Status') }}
                                @if($this->sortField === 'status')
                                    <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        @if($sortDirection === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        @endif
                                    </svg>
                                @endif
                            </th>
                            <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($this->shouldShowSkeleton())
                            @for($i = 0; $i < $perPage; $i++)
                                <tr class="animate-pulse border-b border-gray-200 dark:border-gray-700">
                                    <td class="px-5 py-4">
                                        <div class="h-4 w-4 bg-blue-100 dark:bg-zinc-800 rounded"></div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="h-4 w-32 bg-blue-100 dark:bg-zinc-800 rounded mb-2"></div>
                                        <div class="h-3 w-24 bg-blue-50 dark:bg-zinc-800/70 rounded"></div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="h-4 w-20 bg-blue-100 dark:bg-zinc-800 rounded"></div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="h-4 w-24 bg-blue-100 dark:bg-zinc-800 rounded"></div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="h-4 w-24 bg-blue-100 dark:bg-zinc-800 rounded"></div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="h-6 w-16 bg-blue-100 dark:bg-zinc-800 rounded-full"></div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="h-8 w-8 bg-gray-100 dark:bg-zinc-800 rounded"></div>
                                    </td>
                                </tr>
                            @endfor
                        @else
                            @forelse (($this->payrolls ?? []) as $payroll)
                                <tr class="hover:bg-gray-100 dark:hover:bg-white/20 group border-b border-gray-200 dark:border-gray-700 transition-all duration-500 ease-in-out" wire:loading.class.delay="opacity-50 dark:opacity-40">
                                    <td class="px-5 py-4">
                                        <input type="checkbox" wire:model.live="selected" value="{{ $payroll->id }}" class="accent-pink-500 rounded focus:ring-2 focus:ring-pink-400" />
                                    </td>
                                    <td class="px-5 py-4 text-gray-900 dark:text-white font-bold max-w-xs truncate flex items-center gap-3">
                                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 dark:bg-zinc-800 text-blue-600 dark:text-blue-300 font-bold text-lg">
                                            {{ $payroll->employee && $payroll->employee->user ? strtoupper(substr($payroll->employee->user->first_name, 0, 1) . substr($payroll->employee->user->other_names, 0, 1)) : 'N/A' }}
                                        </span>
                                        <span>
                                            <span class="block font-semibold text-base">{{ $payroll->employee && $payroll->employee->user ? $payroll->employee->user->first_name . ' ' . $payroll->employee->user->other_names : 'N/A' }}</span>
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $payroll->employee ? $payroll->employee->staff_number : 'N/A' }}</span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 font-semibold">
                                        <span class="text-blue-600 dark:text-blue-400">
                                            {{ $payroll->payroll_period }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 font-semibold">
                                        <span class="text-green-600 dark:text-green-400">
                                            USD {{ number_format($payroll->gross_pay, 2) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 font-semibold">
                                        <span class="text-purple-600 dark:text-purple-400">
                                            USD {{ number_format($payroll->net_pay, 2) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($payroll->status === 'draft')
                                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold shadow bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                                {{ __('Draft') }}
                                            </span>
                                        @elseif($payroll->status === 'processed')
                                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold shadow bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                {{ __('Processed') }}
                                            </span>
                                        @elseif($payroll->status === 'paid')
                                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold shadow bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                {{ __('Paid') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($payroll->payslip)
                                            <button wire:click="downloadPayslip({{ $payroll->payslip->id }})"
                                                class="inline-flex items-center justify-center w-8 h-8 text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                            </button>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500 text-sm">
                                                {{ __('No payslip') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="w-8 h-8 text-gray-300 dark:text-zinc-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            {{ __('No payrolls found.') }}
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        @endif
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="mt-6">
                    @if($this->payrolls && !$this->shouldShowSkeleton())
                        {{ $this->payrolls->links() }}
                    @endif
                </div>
            </div>
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

    <!-- Process Confirmation Modal -->
    @if($confirmProcess)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
            <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-md w-full border border-gray-100 dark:border-zinc-800">
                <h3 class="text-xl font-bold mb-4 text-yellow-600 dark:text-yellow-400 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" />
                    </svg>
                    {{ __('Confirm Payroll Processing') }}
                </h3>
                <p class="mb-6 text-zinc-700 dark:text-zinc-300">
                    {{ __('Are you sure you want to process payroll for period') }} <strong>{{ $selectedPeriod }}</strong>?
                    {{ __('This will calculate taxes and generate payroll records for all active employees.') }}
                </p>
                <div class="flex justify-end gap-3">
                    <button wire:click="processPayroll"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                        @if ($processing) disabled @endif>
                        {{ $processing ? __('Processing...') : __('Process Payroll') }}
                    </button>
                    <button wire:click="$set('confirmProcess', false)"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Bulk Action Confirmation Modal -->
    @if($showBulkActionsModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
            <div class="bg-gradient-to-br from-blue-50/80 via-white/80 to-indigo-100/80 dark:from-zinc-900/80 dark:via-zinc-800/80 dark:to-zinc-900/80 backdrop-blur-xl rounded-xl shadow-2xl p-10 max-w-md w-full border border-blue-200 dark:border-zinc-800">
                <h3 class="text-2xl font-extrabold text-blue-600 dark:text-blue-400 flex items-center gap-2 mb-4">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                    </svg>
                    {{ __('Confirm Bulk Action') }}
                </h3>
                <p class="mb-6 text-zinc-700 dark:text-zinc-300 font-semibold">
                    @if($bulkAction === 'approve')
                        {{ __('Are you sure you want to approve the selected payrolls? Only draft payrolls will be processed.') }}
                    @elseif($bulkAction === 'mark_paid')
                        {{ __('Are you sure you want to mark the selected payrolls as paid? Only processed payrolls will be processed.') }}
                    @elseif($bulkAction === 'send_payslips')
                        {{ __('Are you sure you want to send payslips for the selected payrolls?') }}
                    @endif
                </p>
                <div class="flex justify-end gap-3">
                    <button wire:click="executeBulkAction"
                        class="bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600 text-white px-5 py-3 rounded-xl font-semibold shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition backdrop-blur-sm">
                        {{ __('Confirm') }}
                    </button>
                    <button wire:click="$set('showBulkActionsModal', false)"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-xl font-semibold shadow focus:outline-none focus:ring-2 focus:ring-gray-400 transition">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Processing Modal -->
    @if($processing)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
            <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-md w-full border border-gray-100 dark:border-zinc-800">
                <div class="flex items-center gap-3 mb-4">
                    <svg class="w-8 h-8 text-blue-600 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        {{ __('Processing Payroll') }}
                    </h2>
                </div>

                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    {{ __('Please wait while we process payroll for all employees. This may take a few moments...') }}
                </p>

                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                    <div class="bg-blue-600 h-2 rounded-full animate-pulse" style="width: 100%"></div>
                </div>
            </div>
        </div>
    @endif

    <!-- Process Result Modal -->
    @if($processResult)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition">
            <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl p-8 max-w-lg w-full border border-gray-100 dark:border-zinc-800">
                <div class="flex items-center gap-3 mb-4">
                    @if($processResult['success'])
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            {{ __('Payroll Processed Successfully') }}
                        </h2>
                    @else
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" />
                        </svg>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            {{ __('Payroll Processing Failed') }}
                        </h2>
                    @endif
                </div>

                @if($processResult['success'])
                    <div class="mb-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <div class="text-2xl font-bold text-green-600">{{ $processResult['processed_count'] }}</div>
                                <div class="text-sm text-green-600">{{ __('Employees Processed') }}</div>
                            </div>
                            <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600">{{ $processResult['error_count'] }}</div>
                                <div class="text-sm text-blue-600">{{ __('Errors') }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(isset($processResult['errors']) && count($processResult['errors']) > 0)
                    <div class="mb-4">
                        <h3 class="font-semibold text-red-600 mb-2">{{ __('Errors:') }}</h3>
                        <div class="max-h-32 overflow-y-auto bg-red-50 dark:bg-red-900/20 rounded p-3">
                            @foreach($processResult['errors'] as $error)
                                <div class="text-sm text-red-600 mb-1">
                                    {{ $error['employee_name'] }}: {{ $error['error'] }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex items-center justify-end">
                    <button wire:click="$set('processResult', null)"
                        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold shadow transition-all duration-200">
                        {{ __('Close') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', function () {
        Livewire.on('download-csv', function (data) {
            const blob = new Blob([data[0].data], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', data[0].filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        });
    });
</script>