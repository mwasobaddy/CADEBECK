<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Payslip;
use App\Models\Payroll;
use App\Services\PayslipService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.app')] class extends Component {
    public string $search = '';
    public string $periodFilter = '';
    public int $perPage = 10;
    public ?Payslip $selectedPayslip = null;
    public bool $showPayslipModal = false;

    public function mount(): void
    {
        // Ensure only employees can access their own payslips
        if (!Auth::user()->hasRole('Employee')) {
            abort(403, 'Access denied. Only employees can view payslips.');
        }
    }

    public function viewPayslip(Payslip $payslip): void
    {
        // Ensure employee can only view their own payslips
        if ($payslip->employee_id !== Auth::user()->employee->id) {
            abort(403, 'Access denied. You can only view your own payslips.');
        }

        $this->selectedPayslip = $payslip;
        $this->showPayslipModal = true;
    }

    public function downloadPayslip(Payslip $payslip)
    {
        // Ensure employee can only download their own payslips
        if ($payslip->employee_id !== Auth::user()->employee->id) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('Access denied. You can only download your own payslips.')
            ]);
            return;
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
            'message' => __('Payslip file could not be generated. Please contact HR.')
        ]);
    }

    public function closeModal(): void
    {
        $this->showPayslipModal = false;
        $this->selectedPayslip = null;
    }

    public function getPayslipsProperty()
    {
        $query = Payslip::with(['payroll.employee.user', 'payroll.employee.department', 'payroll.employee.designation', 'payroll.employee.branch'])
            ->whereHas('payroll', function($q) {
                $q->where('employee_id', Auth::user()->employee->id);
            });

        if ($this->search) {
            $query->where(function($q) {
                $q->where('payslip_number', 'like', "%{$this->search}%")
                  ->orWhereHas('payroll', function($pq) {
                      $pq->where('payroll_period', 'like', "%{$this->search}%");
                  });
            });
        }

        if ($this->periodFilter) {
            $query->whereHas('payroll', function($q) {
                $q->where('payroll_period', $this->periodFilter);
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($this->perPage);
    }

    public function getPeriodsProperty()
    {
        return Payroll::where('employee_id', Auth::user()->employee->id)
            ->distinct()
            ->pluck('payroll_period')
            ->sort()
            ->reverse();
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
                    {{ __('My Payslips') }}
                </h1>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex items-center gap-3 mb-8">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-800 via-blue-500 to-purple-500 tracking-tight drop-shadow-lg relative inline-block">
                {{ __('My Payslips') }}
                <span class="absolute -bottom-2 left-0 w-[120px] h-1 rounded-full bg-gradient-to-r from-blue-800 via-blue-500 to-purple-500"></span>
            </h1>
        </div>

        <!-- Filters -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <flux:input
                    wire:model.live="search"
                    :label="__('Search Payslips')"
                    type="text"
                    placeholder="{{ __('Search by payslip number or period...') }}"
                />
            </div>
            <div>
                <flux:select
                    wire:model.live="periodFilter"
                    :placeholder="__('All Periods')"
                    :label="__('Filter by Period')"
                >
                    <flux:select.option value="">{{ __('All Periods') }}</flux:select.option>
                    @foreach($this->periods as $period)
                        <flux:select.option value="{{ $period }}">{{ $period }}</flux:select.option>
                    @endforeach
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

        <!-- Payslips Table -->
        <div class="overflow-x-auto">
            <table class="w-full table-auto border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Payslip Number') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Period') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Pay Date') }}
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
                    @forelse($this->payslips as $payslip)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $payslip->payslip_number }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                {{ $payslip->payroll->payroll_period }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                {{ $payslip->payroll->pay_date?->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                KES {{ number_format($payslip->payroll->net_pay, 2) }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($payslip->email_sent_at)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        {{ __('Emailed') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                        {{ __('Generated') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-2">
                                    <button wire:click="viewPayslip({{ $payslip->id }})"
                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                    <button wire:click="downloadPayslip({{ $payslip->id }})"
                                        class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                {{ __('No payslips found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($this->payslips->hasPages())
            <div class="mt-6">
                {{ $this->payslips->links() }}
            </div>
        @endif
    </div>

    <!-- Payslip Modal -->
    @if($showPayslipModal && $selectedPayslip)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ __('Payslip Details') }}
                        </h2>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Payslip Preview -->
                    <div class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-700">
                        <div class="text-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ $selectedPayslip->payslip_number }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $selectedPayslip->payroll->payroll_period }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('Earnings') }}</h4>
                                <div class="space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <span>{{ __('Basic Salary') }}:</span>
                                        <span>KES {{ number_format($selectedPayslip->payroll->basic_salary, 2) }}</span>
                                    </div>
                                    @if($selectedPayslip->payroll->total_allowances > 0)
                                        <div class="flex justify-between">
                                            <span>{{ __('Total Allowances') }}:</span>
                                            <span>KES {{ number_format($selectedPayslip->payroll->total_allowances, 2) }}</span>
                                        </div>
                                    @endif
                                    <div class="flex justify-between font-semibold border-t pt-1">
                                        <span>{{ __('Gross Pay') }}:</span>
                                        <span>KES {{ number_format($selectedPayslip->payroll->gross_pay, 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('Deductions') }}</h4>
                                <div class="space-y-1 text-sm">
                                    @if($selectedPayslip->payroll->paye_tax > 0)
                                        <div class="flex justify-between">
                                            <span>{{ __('PAYE Tax') }}:</span>
                                            <span>KES {{ number_format($selectedPayslip->payroll->paye_tax, 2) }}</span>
                                        </div>
                                    @endif
                                    @if($selectedPayslip->payroll->nhif_deduction > 0)
                                        <div class="flex justify-between">
                                            <span>{{ __('NHIF') }}:</span>
                                            <span>KES {{ number_format($selectedPayslip->payroll->nhif_deduction, 2) }}</span>
                                        </div>
                                    @endif
                                    @if($selectedPayslip->payroll->nssf_deduction > 0)
                                        <div class="flex justify-between">
                                            <span>{{ __('NSSF') }}:</span>
                                            <span>KES {{ number_format($selectedPayslip->payroll->nssf_deduction, 2) }}</span>
                                        </div>
                                    @endif
                                    <div class="flex justify-between font-semibold border-t pt-1">
                                        <span>{{ __('Total Deductions') }}:</span>
                                        <span>KES {{ number_format($selectedPayslip->payroll->total_deductions, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                KES {{ number_format($selectedPayslip->payroll->net_pay, 2) }}
                            </div>
                            <div class="text-sm text-green-600 dark:text-green-400 font-semibold">
                                {{ __('NET PAY') }}
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-3 mt-6">
                        <button wire:click="downloadPayslip({{ $selectedPayslip->id }})"
                            class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold shadow transition-all duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{ __('Download PDF') }}
                        </button>
                        <button wire:click="closeModal"
                            class="flex items-center gap-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-lg font-semibold shadow transition-all duration-200">
                            {{ __('Close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
