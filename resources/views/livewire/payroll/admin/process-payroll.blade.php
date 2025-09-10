<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Payroll;
use App\Models\Employee;
use App\Services\PayrollProcessingService;
use App\Services\PayslipService;
use App\Notifications\PayrollProcessedNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

new #[Layout('components.layouts.app')] class extends Component {
    public string $selectedPeriod = '';
    public ?Carbon $payDate = null;
    public bool $confirmProcess = false;
    public bool $processing = false;
    public ?array $processResult = null;
    public string $search = '';
    public string $statusFilter = 'draft';
    public int $perPage = 10;

    // Bulk actions
    public array $selectedPayrolls = [];
    public bool $selectAll = false;

    public array $availablePeriods = [];

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

    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedPayrolls = $this->payrolls->pluck('id')->toArray();
        } else {
            $this->selectedPayrolls = [];
        }
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
                // Send notifications to all processed employees
                $processedPayrolls = Payroll::with(['employee.user'])
                    ->where('payroll_period', $this->selectedPeriod)
                    ->where('status', 'processed')
                    ->get();

                $notificationCount = 0;
                $notificationErrors = 0;

                foreach ($processedPayrolls as $payroll) {
                    try {
                        // Check if employee has a user and email
                        if ($payroll->employee && $payroll->employee->user && $payroll->employee->user->email) {
                            $payroll->employee->notify(new PayrollProcessedNotification($payroll));
                            $notificationCount++;
                        } else {
                            \Log::warning('Employee missing user or email', [
                                'employee_id' => $payroll->employee->id ?? null,
                                'payroll_id' => $payroll->id
                            ]);
                            $notificationErrors++;
                        }
                    } catch (\Exception $e) {
                        // Log notification error but don't fail the entire process
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
                    'message' => "Payroll processed successfully! {$result['processed_count']} employees processed, {$notificationCount} notified, {$notificationErrors} errors."
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

    public function approveSelected(): void
    {
        if (empty($this->selectedPayrolls)) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Please select payrolls to approve.'
            ]);
            return;
        }

        $payrolls = Payroll::whereIn('id', $this->selectedPayrolls)->get();
        $payrollService = app(PayrollProcessingService::class);

        $result = $payrollService->bulkApprovePayrolls($payrolls, Auth::user());

        if ($result['approved_count'] > 0) {
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "{$result['approved_count']} payrolls approved successfully."
            ]);
        }

        if ($result['error_count'] > 0) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => "{$result['error_count']} payrolls failed to approve."
            ]);
        }

        $this->selectedPayrolls = [];
        $this->selectAll = false;
    }

    public function markPaidSelected(): void
    {
        if (empty($this->selectedPayrolls)) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Please select payrolls to mark as paid.'
            ]);
            return;
        }

        $payrolls = Payroll::whereIn('id', $this->selectedPayrolls)->get();
        $payrollService = app(PayrollProcessingService::class);

        $result = $payrollService->bulkMarkAsPaid($payrolls);

        if ($result['paid_count'] > 0) {
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "{$result['paid_count']} payrolls marked as paid and payslips generated."
            ]);
        }

        if ($result['error_count'] > 0) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => "{$result['error_count']} payrolls failed to process."
            ]);
        }

        $this->selectedPayrolls = [];
        $this->selectAll = false;
    }

    public function sendPayslips(): void
    {
        if (empty($this->selectedPayrolls)) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Please select payrolls to send payslips for.'
            ]);
            return;
        }

        $payrolls = Payroll::with('payslip')->whereIn('id', $this->selectedPayrolls)->get();
        $payslipService = app(PayslipService::class);
        $sent = 0;
        $failed = 0;

        foreach ($payrolls as $payroll) {
            if ($payroll->payslip) {
                $result = $payslipService->sendPayslipEmail($payroll->payslip);
                if ($result) {
                    $sent++;
                } else {
                    $failed++;
                }
            }
        }

        if ($sent > 0) {
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "{$sent} payslips sent successfully."
            ]);
        }

        if ($failed > 0) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => "{$failed} payslips failed to send."
            ]);
        }

        $this->selectedPayrolls = [];
        $this->selectAll = false;
    }

    public function getPayrollsProperty()
    {
        $query = Payroll::with(['employee', 'payslip']);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('payroll_period', 'like', "%{$this->search}%")
                  ->orWhereHas('employee', function($eq) {
                      $eq->where('first_name', 'like', "%{$this->search}%")
                         ->orWhere('last_name', 'like', "%{$this->search}%")
                         ->orWhere('employee_number', 'like', "%{$this->search}%");
                  });
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderBy('created_at', 'desc')->paginate($this->perPage);
    }

    public function downloadPayslip(Payslip $payslip)
    {
        // Ensure admin can download any payslip
        if (!Auth::user()->can('process_payroll')) {
            abort(403, 'Access denied. Only payroll administrators can download payslips.');
        }

        if (Storage::disk('public')->exists($payslip->file_path)) {
            return response()->download(storage_path('app/public/' . $payslip->file_path), $payslip->file_name);
        }

        // If file doesn't exist, regenerate it
        $payslipService = app(PayslipService::class);
        $newPayslip = $payslipService->regeneratePayslip($payslip);

        if ($newPayslip && Storage::disk('public')->exists($newPayslip->file_path)) {
            return response()->download(storage_path('app/public/' . $newPayslip->file_path), $newPayslip->file_name);
        }

        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Payslip file could not be generated. Please contact the system administrator.'
        ]);
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

<div class="relative max-w-7xl mx-auto md:px-4 md:py-8">
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
    <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40 mb-8">
        <div class="flex items-center gap-3 mb-8">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-800 via-blue-500 to-purple-500 tracking-tight drop-shadow-lg relative inline-block">
                {{ __('Process Payroll') }}
                <span class="absolute -bottom-2 left-0 w-[150px] h-1 rounded-full bg-gradient-to-r from-blue-800 via-blue-500 to-purple-500"></span>
            </h1>
        </div>

        <!-- Payroll Configuration -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <flux:select
                    wire:model="selectedPeriod"
                    :label="__('Payroll Period')"
                    required
                >
                    @foreach($this->availablePeriods as $period)
                        <flux:select.option value="{{ $period }}">{{ $period }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div>
                <flux:input
                    wire:model="payDate"
                    type="date"
                    :label="__('Pay Date')"
                    required
                />
            </div>
            <div class="flex items-end">
                <button wire:click="$set('confirmProcess', true)"
                    wire:loading.attr="disabled"
                    class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold shadow transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    {{ __('Process Payroll') }}
                </button>
            </div>
        </div>

        <!-- Payroll Summary -->
        @if($this->payrollSummary)
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4">{{ __('Payroll Summary for') }} {{ $this->selectedPeriod }}</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ $this->payrollSummary['total_employees'] }}</div>
                        <div class="text-sm text-blue-600">{{ __('Employees') }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">KES {{ number_format($this->payrollSummary['total_gross_pay'], 2) }}</div>
                        <div class="text-sm text-green-600">{{ __('Total Gross Pay') }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">KES {{ number_format($this->payrollSummary['total_deductions'], 2) }}</div>
                        <div class="text-sm text-red-600">{{ __('Total Deductions') }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">KES {{ number_format($this->payrollSummary['total_net_pay'], 2) }}</div>
                        <div class="text-sm text-purple-600">{{ __('Total Net Pay') }}</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Payroll Management Section -->
    <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-3">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                    {{ __('Payroll Management') }}
                    <span class="absolute -bottom-2 left-0 w-[180px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                </h1>
            </div>

            <!-- Bulk Actions -->
            @if(count($this->selectedPayrolls) > 0)
                <div class="flex items-center gap-3">
                    <button wire:click="approveSelected"
                        class="flex items-center gap-2 bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-semibold shadow transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ __('Approve Selected') }}
                    </button>
                    <button wire:click="markPaidSelected"
                        class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold shadow transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        {{ __('Mark as Paid') }}
                    </button>
                    <button wire:click="sendPayslips"
                        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold shadow transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        {{ __('Send Payslips') }}
                    </button>
                </div>
            @endif
        </div>

        <!-- Filters -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <flux:input
                    wire:model.live="search"
                    :label="__('Search Payrolls')"
                    type="text"
                    placeholder="{{ __('Search by employee name, number, or period...') }}"
                />
            </div>
            <div>
                <flux:select
                    wire:model.live="statusFilter"
                    :label="__('Status Filter')"
                >
                    <flux:select.option value="all">{{ __('All Statuses') }}</flux:select.option>
                    <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                    <flux:select.option value="processed">{{ __('Processed') }}</flux:select.option>
                    <flux:select.option value="paid">{{ __('Paid') }}</flux:select.option>
                </flux:select>
            </div>
            <div>
                <flux:select
                    wire:model.live="perPage"
                    :label="__('Items per page')"
                >
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                </flux:select>
            </div>
        </div>

        <!-- Payrolls Table -->
        <div class="overflow-x-auto">
            <table class="w-full table-auto border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800">
                        <th class="px-4 py-3">
                            <flux:checkbox wire:model="selectAll" />
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Employee') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Period') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Gross Pay') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Net Pay') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Status') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($this->payrolls as $payroll)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-4">
                                <flux:checkbox wire:model="selectedPayrolls" value="{{ $payroll->id }}" />
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-300">
                                    {{ $payroll->employee->employee_number }}
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                {{ $payroll->payroll_period }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                KES {{ number_format($payroll->gross_pay, 2) }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                KES {{ number_format($payroll->net_pay, 2) }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($payroll->status === 'draft')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                        {{ __('Draft') }}
                                    </span>
                                @elseif($payroll->status === 'processed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        {{ __('Processed') }}
                                    </span>
                                @elseif($payroll->status === 'paid')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        {{ __('Paid') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                @if($payroll->payslip)
                                    <button wire:click="downloadPayslip({{ $payroll->payslip->id }})"
                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
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
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                {{ __('No payrolls found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($this->payrolls->hasPages())
            <div class="mt-6">
                {{ $this->payrolls->links() }}
            </div>
        @endif
    </div>

    <!-- Process Confirmation Modal -->
    @if($confirmProcess)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"></circle>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" />
                        </svg>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            {{ __('Confirm Payroll Processing') }}
                        </h2>
                    </div>

                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        {{ __('Are you sure you want to process payroll for period') }} <strong>{{ $selectedPeriod }}</strong>?
                        {{ __('This will calculate taxes and generate payroll records for all active employees.') }}
                    </p>

                    <div class="flex items-center justify-end gap-3">
                        <button wire:click="$set('confirmProcess', false)"
                            class="flex items-center gap-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-lg font-semibold shadow transition-all duration-200">
                            {{ __('Cancel') }}
                        </button>
                        <button wire:click="processPayroll"
                            wire:loading.attr="disabled"
                            class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold shadow transition-all duration-200 disabled:opacity-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            {{ __('Process Payroll') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Processing Modal -->
    @if($processing)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full">
                <div class="p-6">
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
        </div>
    @endif

    <!-- Process Result Modal -->
    @if($processResult)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full">
                <div class="p-6">
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
        </div>
    @endif
</div>
