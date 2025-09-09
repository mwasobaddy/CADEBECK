<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Payslip;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.app')] class extends Component {
    public string $search = '';
    public string $periodFilter = '';
    public int $perPage = 10;

    public function mount(): void
    {
        // Ensure user has an employee record
        if (!Auth::user()->employee) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('Employee record not found. Please contact HR.')
            ]);
            return;
        }
    }

    public function getPayslipsProperty()
    {
        // Check if user has employee record
        if (!Auth::user()->employee) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage);
        }

        $query = Payslip::with(['payroll'])
            ->whereHas('payroll', function($q) {
                $q->where('employee_id', Auth::user()->employee->id);
            });

        if ($this->search) {
            $query->where(function($q) {
                $q->where('payroll_period', 'like', "%{$this->search}%")
                  ->orWhereHas('payroll', function($pq) {
                      $pq->where('payroll_period', 'like', "%{$this->search}%");
                  });
            });
        }

        if ($this->periodFilter) {
            $query->where('payroll_period', $this->periodFilter);
        }

        return $query->orderBy('created_at', 'desc')->paginate($this->perPage);
    }

    public function getAvailablePeriodsProperty()
    {
        // Check if user has employee record
        if (!Auth::user()->employee) {
            return collect();
        }

        return Payslip::whereHas('payroll', function($q) {
            $q->where('employee_id', Auth::user()->employee->id);
        })
        ->distinct()
        ->orderBy('payroll_period', 'desc')
        ->pluck('payroll_period')
        ->take(12); // Last 12 months
    }

    public function downloadPayslip($payslipId): void
    {
        $payslip = Payslip::find($payslipId);

        if (!$payslip || $payslip->payroll->employee_id !== Auth::user()->employee->id) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('Payslip not found or access denied.')
            ]);
            return;
        }

        // The actual download will be handled by the browser redirect
        $this->dispatch('download-payslip', ['url' => route('payslip.download', $payslip)]);
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

    <!-- Payslips Section -->
    <div class="relative z-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex items-center gap-3 mb-8">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-800 via-blue-500 to-purple-500 tracking-tight drop-shadow-lg relative inline-block">
                {{ __('My Payslips') }}
                <span class="absolute -bottom-2 left-0 w-[150px] h-1 rounded-full bg-gradient-to-r from-blue-800 via-blue-500 to-purple-500"></span>
            </h1>
        </div>

        <!-- Filters -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <flux:input
                    wire:model.live="search"
                    :label="__('Search Payslips')"
                    type="text"
                    placeholder="{{ __('Search by period...') }}"
                />
            </div>
            <div>
                <flux:select
                    wire:model.live="periodFilter"
                    :label="__('Filter by Period')"
                >
                    <flux:select.option value="">{{ __('All Periods') }}</flux:select.option>
                    @foreach($this->availablePeriods as $period)
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
                            {{ __('Period') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Gross Pay') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Net Pay') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Generated') }}
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
                                {{ $payslip->payroll_period }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                KES {{ number_format($payslip->payroll->gross_pay, 2) }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                KES {{ number_format($payslip->payroll->net_pay, 2) }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                {{ $payslip->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-2">
                                    <button wire:click="downloadPayslip({{ $payslip->id }})"
                                        class="flex items-center gap-2 text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        {{ __('Download') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
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

    <!-- Download Script -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('download-payslip', (data) => {
                window.open(data.url, '_blank');
            });
        });
    </script>
</div>
