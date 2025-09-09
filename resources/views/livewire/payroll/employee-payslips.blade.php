<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Payslip;
use App\Models\Employee;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.app')] class extends Component {
    public ?Employee $employee = null;
    public int $perPage = 10;

    public function mount($employeeId): void
    {
        $this->employee = Employee::findOrFail($employeeId);
    }

    public function downloadPayslip($payslipId): void
    {
        $payslip = Payslip::findOrFail($payslipId);

        if ($payslip->file_path && Storage::exists($payslip->file_path)) {
            $payslip->update(['is_downloaded' => true, 'downloaded_at' => now()]);

            return Storage::download($payslip->file_path, $payslip->file_name);
        }

        $this->dispatch('notify', [
            'type' => 'error',
            'message' => __('Payslip file not found.')
        ]);
    }

    public function getPayslipsProperty()
    {
        return $this->employee->payslips()
            ->with('payroll')
            ->orderBy('pay_date', 'desc')
            ->paginate($this->perPage);
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
                    {{ __('Payslips for') }} {{ $employee->user->first_name }} {{ $employee->user->last_name }}
                </h1>
            </div>
        </nav>
    </div>

    <!-- Payslips Table -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-8 border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Payslip Number') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Period') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Pay Date') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('Gross Pay') }}
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
                    @forelse($this->payslips as $payslip)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            {{ $payslip->payslip_number }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $payslip->payroll_period }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ \Carbon\Carbon::parse($payslip->pay_date)->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            @if($payslip->payroll)
                            KES {{ number_format($payslip->payroll->gross_pay, 2) }}
                            @else
                            -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            @if($payslip->payroll)
                            KES {{ number_format($payslip->payroll->net_pay, 2) }}
                            @else
                            -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col gap-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $payslip->is_emailed ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $payslip->is_emailed ? __('Emailed') : __('Not Emailed') }}
                                </span>
                                @if($payslip->is_downloaded)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ __('Downloaded') }}
                                </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            @if($payslip->file_path && Storage::exists($payslip->file_path))
                            <button wire:click="downloadPayslip({{ $payslip->id }})"
                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                {{ __('Download') }}
                            </button>
                            @else
                            <span class="text-gray-400">{{ __('File not available') }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('No payslips found for this employee.') }}
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
</div>
