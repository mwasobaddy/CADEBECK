<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Services\PayrollImportService;
use Carbon\Carbon;
use OpenSpout\Reader\CSV\Reader;
use OpenSpout\Reader\CSV\Options as CsvOptions;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    public $file = null;
    public string $mode = 'bulk';
    public string $selectedPeriod = '';
    public string $payDate = '';
    public bool $importing = false;
    public bool $showPreview = false;
    public array $previewData = [];
    public array $previewHeaders = [];
    public ?array $result = null;
    public array $availablePeriods = [];

    public function mount(): void
    {
        $this->selectedPeriod = Carbon::now()->format('m/Y');
        $this->payDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->availablePeriods = [
            Carbon::now()->format('m/Y'),
            Carbon::now()->subMonth()->format('m/Y'),
            Carbon::now()->subMonths(2)->format('m/Y'),
            Carbon::now()->subMonths(3)->format('m/Y'),
        ];
    }

    public function updatedFile(): void
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx|max:10240',
        ]);

        $this->showPreview = false;
        $this->previewData = [];
        $this->result = null;
    }

    public function preview(): void
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx|max:10240',
        ]);

        $path = $this->file->getRealPath();
        $options = new CsvOptions();
        $reader = new Reader($options);
        $reader->open($path);

        $headers = [];
        $rows = [];
        $rowIndex = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowIndex++;
                $cells = [];
                foreach ($row->cells as $cell) {
                    $cells[] = (string) $cell->getValue();
                }

                if ($rowIndex === 1) {
                    $headers = $cells;
                    continue;
                }

                $rows[] = $cells;

                if (count($rows) >= 10) {
                    break;
                }
            }
        }

        $reader->close();

        $this->previewHeaders = $headers;
        $this->previewData = $rows;
        $this->showPreview = true;
    }

    public function downloadTemplate()
    {
        $service = app(PayrollImportService::class);
        $path = $service->generateTemplate($this->mode);

        $filename = $this->mode === 'bulk'
            ? 'payroll_bulk_import_template.csv'
            : 'payroll_precomputed_import_template.csv';

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function import(): void
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx|max:10240',
            'selectedPeriod' => 'required|string',
        ]);

        $this->importing = true;
        $this->result = null;

        try {
            $path = $this->file->getRealPath();
            $service = app(PayrollImportService::class);

            $this->result = $service->import(
                filePath: $path,
                mode: $this->mode,
                period: $this->selectedPeriod,
                payDate: Carbon::parse($this->payDate),
            );
        } catch (\Exception $e) {
            $this->result = [
                'total' => 0,
                'imported' => 0,
                'errors' => 1,
                'error_details' => [['row' => 0, 'employee' => '-', 'errors' => [$e->getMessage()]]],
                'mode' => $this->mode,
                'period' => $this->selectedPeriod,
                'pay_date' => Carbon::parse($this->payDate)->format('Y-m-d'),
            ];
        }

        $this->importing = false;
        $this->showPreview = false;
        $this->file = null;
        $this->previewData = [];
    }
};

?>

<div class="py-8 px-4 max-w-7xl mx-auto space-y-8">

    @can('process_payroll')
        <!-- Header -->
        <div class="flex items-center gap-3">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                {{ __('Import Payroll') }}
                <span class="absolute -bottom-2 left-0 w-[180px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
            </h1>
        </div>

        <!-- Main Card -->
        <div class="relative bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">

            <!-- Import Result Alert -->
            @if ($result)
                <div class="mb-6 rounded-xl p-4 {{ $result['errors'] > 0 ? 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800' : 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' }}">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0">
                            @if ($result['errors'] > 0)
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            @else
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-zinc-900 dark:text-white">
                                {{ __('Import complete') }} — {{ $result['imported'] }} / {{ $result['total'] }} {{ __('rows imported') }}
                                @if ($result['errors'] > 0)
                                    , {{ $result['errors'] }} {{ __('errors') }}
                                @endif
                            </p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                                {{ __('Period') }}: {{ $result['period'] }} &middot; {{ __('Pay date') }}: {{ $result['pay_date'] }}
                            </p>
                        </div>
                        <button wire:click="$set('result', null)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    @if (!empty($result['error_details']))
                        <div class="mt-4">
                            <p class="text-sm font-semibold text-amber-700 dark:text-amber-400 mb-2">{{ __('Error details') }}:</p>
                            <div class="max-h-48 overflow-y-auto space-y-1">
                                @foreach ($result['error_details'] as $error)
                                    <div class="text-sm text-amber-600 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 rounded-lg px-3 py-1.5">
                                        <span class="font-medium">{{ __('Row') }} {{ $error['row'] }}</span>
                                        ({{ $error['employee'] }}):
                                        <span class="text-amber-700 dark:text-amber-200">{{ implode('; ', $error['errors']) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Payroll Settings Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('Import Mode') }}</label>
                    <select wire:model.live="mode"
                        class="w-full px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                        <option value="bulk">{{ __('Bulk Data Import') }}</option>
                        <option value="precomputed">{{ __('Pre-computed Upload') }}</option>
                    </select>
                    <p class="text-xs text-zinc-400 mt-1.5">
                        @if ($mode === 'bulk')
                            {{ __('Upload raw payroll data — the system will calculate taxes, NI, and pensions automatically.') }}
                        @else
                            {{ __('Upload pre-computed payroll results from an external system for record-keeping.') }}
                        @endif
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('Payroll Period') }}</label>
                    <select wire:model="selectedPeriod"
                        class="w-full px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                        @foreach ($availablePeriods as $period)
                            <option value="{{ $period }}">{{ $period }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('Pay Date') }}</label>
                    <input type="date" wire:model.live="payDate"
                        class="w-full px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                </div>
            </div>

            <!-- Divider -->
            <div class="border-t border-blue-100 dark:border-zinc-700 mb-6"></div>

            <!-- Upload Section -->
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Upload CSV File') }}</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Download a template first, fill it in, then upload it here.') }}
                    </p>
                </div>
                <button wire:click="downloadTemplate" type="button"
                    class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600 text-white px-4 py-2 rounded-xl font-semibold text-sm shadow-lg transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ __('Download Template') }}
                </button>
            </div>

            <div class="border-2 border-dashed border-blue-200 dark:border-indigo-700/50 rounded-xl p-10 text-center hover:border-blue-400 dark:hover:border-indigo-500 transition-colors bg-white/40 dark:bg-zinc-800/40 backdrop-blur-sm">
                @if ($file)
                    <div class="flex items-center justify-center gap-3 mb-4">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-zinc-700 dark:text-zinc-300 font-medium">{{ $file->getClientOriginalName() }}</span>
                        <span class="text-sm text-zinc-400">({{ number_format($file->getSize() / 1024, 1) }} KB)</span>
                        <button wire:click="$set('file', null)" class="text-red-500 hover:text-red-700 text-sm font-semibold ml-2">{{ __('Remove') }}</button>
                    </div>
                @endif

                <input type="file" wire:model="file" accept=".csv,.txt,.xlsx" class="hidden" id="file-upload">
                <label for="file-upload" class="cursor-pointer">
                    <svg class="w-12 h-12 mx-auto text-blue-300 dark:text-indigo-400 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        <span class="text-blue-600 dark:text-indigo-400 font-semibold">{{ __('Click to upload') }}</span>
                        {{ __('or drag and drop') }}
                    </p>
                    <p class="text-xs text-zinc-400 mt-1">{{ __('CSV files only, max 10MB') }}</p>
                </label>
                @error('file')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            @if ($file)
                <div class="flex items-center gap-3 mt-6">
                    @if (!$showPreview)
                        <button wire:click="preview" type="button"
                            class="flex items-center gap-2 px-4 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 bg-white/80 dark:bg-zinc-900/80 text-blue-600 dark:text-indigo-300 hover:bg-blue-50/80 dark:hover:bg-zinc-800/80 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            {{ __('Preview') }}
                        </button>
                    @endif
                    <button wire:click="import" wire:loading.attr="disabled"
                        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white px-6 py-2.5 rounded-xl font-semibold shadow transition-all duration-200">
                        @if ($importing)
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            {{ __('Importing...') }}
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            {{ __('Import Payroll Records') }}
                        @endif
                    </button>
                </div>
            @endif

            <!-- Preview Table -->
            @if ($showPreview && !empty($previewHeaders))
                <div class="border-t border-blue-100 dark:border-zinc-700 pt-6 mt-6">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3">{{ __('Preview') }}</h3>
                    <div class="overflow-x-auto rounded-xl border border-blue-100 dark:border-zinc-700">
                        <table class="min-w-full divide-y divide-blue-100 dark:divide-zinc-700 text-sm">
                            <thead class="bg-blue-50/50 dark:bg-zinc-800">
                                <tr>
                                    @foreach ($previewHeaders as $header)
                                        <th class="px-4 py-2.5 text-left font-semibold text-zinc-600 dark:text-zinc-400 whitespace-nowrap">{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-blue-50 dark:divide-zinc-700">
                                @foreach ($previewData as $row)
                                    <tr class="hover:bg-blue-50/30 dark:hover:bg-zinc-800/50">
                                        @foreach ($row as $cell)
                                            <td class="px-4 py-2 text-zinc-700 dark:text-zinc-300 whitespace-nowrap">{{ $cell }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-zinc-400 mt-2">{{ __('Showing up to 10 rows') }}</p>
                </div>
            @endif
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
