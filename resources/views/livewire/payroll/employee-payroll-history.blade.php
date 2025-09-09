<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Payroll;
use App\Models\Employee;

new #[Layout('components.layouts.app')] class extends Component {
    public ?Employee $employee = null;
    public int $perPage = 10;
    public string $selectedPeriod = '';

    public function mount($employeeId): void
    {
        $this->employee = Employee::findOrFail($employeeId);
    }

    public function viewPayrollDetails($payrollId): void
    {
        // This could open a modal or redirect to a detailed view
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => __('Payroll details feature coming soon.')
        ]);
    }

    public function getPayrollsProperty()
    {
        $query = $this->employee->payrolls()
            ->orderBy('pay_date', 'desc');

        if ($this->selectedPeriod) {
            $query->where('payroll_period', $this->selectedPeriod);
        }

        return $query->paginate($this->perPage);
    }

    public function getPayrollPeriodsProperty()
    {
        return $this->employee->payrolls()
            ->select('payroll_period')
            ->distinct()
            ->orderBy('payroll_period', 'desc')
            ->pluck('payroll_period');
    }
};
?>

<div class="relative max-w-6xl mx-auto md:px-4 md:py-8">
    <!-- Header -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 z-10 relative border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <nav class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('employee.show', $employee->id) }}" class="text-blue-600 hover:text-blue-800">
                    ← {{ __('Back to Employee') }}
                </a>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ __('Payroll History for') }} {{ $employee->user->first_name }} {{ $employee->user->last_name }}
                </h1>
            </div>
        </nav>
    </div>

    <!-- Filters -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-lg p-6 mb-6 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('Filter by Period') }}
                </label>
                <select wire:model.live="selectedPeriod"
                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                    <option value="">{{ __('All Periods') }}</option>
                    @foreach($this->payrollPeriods as $period)
                    <option value="{{ $period }}">{{ $period }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Payroll History Table -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Period') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Pay Date') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Basic Salary') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Allowances') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Deductions') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Net Pay') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Status') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($this->payrolls as $payroll)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            {{ $payroll->payroll_period }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ \Carbon\Carbon::parse($payroll->pay_date)->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            KES {{ number_format($payroll->basic_salary, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            KES {{ number_format($payroll->total_allowances, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            KES {{ number_format($payroll->total_deductions, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400">
                            KES {{ number_format($payroll->net_pay, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($payroll->status === 'paid')
                                    bg-green-100 text-green-800
                                @elseif($payroll->status === 'processed')
                                    bg-blue-100 text-blue-800
                                @else
                                    bg-yellow-100 text-yellow-800
                                @endif">
                                {{ ucfirst($payroll->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button wire:click="viewPayrollDetails({{ $payroll->id }})"
                                    class="text-blue-600 hover:text-blue-900">
                                {{ __('View Details') }}
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No payroll records found for this employee.') }}
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
</div>
