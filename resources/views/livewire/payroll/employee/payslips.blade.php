<?php
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Payslip;
use App\Models\Payroll;
use App\Services\PayslipService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public bool $showFilters = false;
    public string $search = '';
    public string $periodFilter = '';
    public int $perPage = 10;
    public ?Payslip $selectedPayslip = null;
    public bool $showPayslipModal = false;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public bool $isSearching = false;
    public bool $isFiltering = false;
    public bool $isPaginating = false;
    public bool $isLoadingData = false;
    public bool $isLoadingDownload = false;
    public bool $showUploadModal = false;
    public $uploadedFile;
    public string $uploadPeriod = '';
    public string $uploadPayDate = '';
    public array $availableUploadPeriods = [];

    public function mount(): void
    {
        // Ensure only employees can access their own payslips
        if (!Auth::user()->hasRole('Employee')) {
            abort(403, 'Access denied. Only employees can view payslips.');
        }

        $this->uploadPayDate = now()->format('Y-m-d');
        $this->availableUploadPeriods = [
            now()->format('m/Y'),
            now()->subMonth()->format('m/Y'),
            now()->subMonths(2)->format('m/Y'),
            now()->subMonths(3)->format('m/Y'),
            now()->subMonths(4)->format('m/Y'),
            now()->subMonths(5)->format('m/Y'),
            now()->subMonths(6)->format('m/Y'),
        ];
    }

    public function openUploadModal(): void
    {
        if (!Auth::user()->can('upload_external_payslip')) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('You do not have permission to upload external payslips.')
            ]);
            return;
        }
        $this->showUploadModal = true;
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->uploadedFile = null;
        $this->uploadPeriod = '';
        $this->uploadPayDate = now()->format('Y-m-d');
    }

    public function uploadExternalPayslip(): void
    {
        if (!Auth::user()->can('upload_external_payslip')) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('You do not have permission to upload external payslips.')
            ]);
            return;
        }

        $this->validate([
            'uploadedFile' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            'uploadPeriod' => 'required|string',
            'uploadPayDate' => 'required|date',
        ]);

        try {
            $employeeId = Auth::user()->employee->id;

            $existingPayslip = Payslip::where('employee_id', $employeeId)
                ->where('payroll_period', $this->uploadPeriod)
                ->first();

            if ($existingPayslip && $existingPayslip->is_external) {
                $existingPayslip->deleteExternalFile();
                $existingPayslip->delete();
            } elseif ($existingPayslip) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => __('A generated payslip already exists for this period. Delete it first to upload an external one.')
                ]);
                return;
            }

            $file = $this->uploadedFile;
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $newFileName = 'external_payslip_' . $employeeId . '_' . time() . '.' . $extension;

            $path = $file->storeAs('payslips/external', $newFileName, 'public');

            $payslip = Payslip::create([
                'employee_id' => $employeeId,
                'payslip_number' => Payslip::generateUniquePayslipNumber(),
                'payroll_period' => $this->uploadPeriod,
                'pay_date' => $this->uploadPayDate,
                'is_external' => true,
                'external_file_path' => $path,
                'external_file_name' => $originalName,
                'external_uploaded_by' => Auth::id(),
                'external_uploaded_at' => now(),
            ]);

            $this->closeUploadModal();
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __('External payslip uploaded successfully.')
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('Error uploading payslip: ') . $e->getMessage()
            ]);
        }
    }

    public function downloadExternalPayslip(Payslip $payslip): void
    {
        if ($payslip->employee_id !== Auth::user()->employee->id) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('Access denied. You can only download your own payslips.')
            ]);
            return;
        }

        if (!$payslip->is_external || !$payslip->external_file_path) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('External payslip file not found.')
            ]);
            return;
        }

        if (!Storage::disk('public')->exists($payslip->external_file_path)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('Payslip file could not be found on server.')
            ]);
            return;
        }

        $payslip->markAsDownloaded();

        $this->isLoadingDownload = true;
        $downloadPath = storage_path('app/public/' . $payslip->external_file_path);
        $this->isLoadingDownload = false;

        response()->download($downloadPath, $payslip->external_file_name);
    }

    public function deleteExternalPayslip(Payslip $payslip): void
    {
        if (!Auth::user()->can('delete_external_payslip')) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('You do not have permission to delete external payslips.')
            ]);
            return;
        }

        if ($payslip->employee_id !== Auth::user()->employee->id) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('Access denied. You can only delete your own payslips.')
            ]);
            return;
        }

        if (!$payslip->is_external) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('Cannot delete generated payslips from here.')
            ]);
            return;
        }

        $payslip->deleteExternalFile();
        $payslip->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => __('External payslip deleted successfully.')
        ]);
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

    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
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

        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        if ($this->sortField === 'payslip_number') {
            $query->orderBy('payslip_number', $direction);
        } elseif ($this->sortField === 'period') {
            $query->whereHas('payroll', function($q) use ($direction) {
                $q->orderBy('payroll_period', $direction);
            });
        } elseif ($this->sortField === 'pay_date') {
            $query->whereHas('payroll', function($q) use ($direction) {
                $q->orderBy('pay_date', $direction);
            });
        } elseif ($this->sortField === 'net_pay') {
            $query->whereHas('payroll', function($q) use ($direction) {
                $q->orderBy('net_pay', $direction);
            });
        } else {
            $query->orderBy('created_at', $direction);
        }

        return $query->paginate($this->perPage);
    }

    public function getPeriodsProperty()
    {
        return Payroll::where('employee_id', Auth::user()->employee->id)
            ->distinct()
            ->pluck('payroll_period')
            ->sort()
            ->reverse();
    }

    public function updatedSearch(): void
    {
        $this->isSearching = true;
        $this->resetPage();
        $this->isSearching = false;
    }

    public function updatedPeriodFilter(): void
    {
        $this->isFiltering = true;
        $this->resetPage();
        $this->isFiltering = false;
    }

    public function updatedPage(): void
    {
        $this->isPaginating = true;
        $this->isPaginating = false;
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
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

        $this->isLoadingDownload = true;

        // Use route() helper to generate download URL
        if (Storage::disk('public')->exists($payslip->file_path)) {
            $this->isLoadingDownload = false;
            return redirect()->route('payslip.download', ['payslip' => $payslip->id]);
        }

        // If file doesn't exist, regenerate it
        $payslipService = app(PayslipService::class);
        $newPayslip = $payslipService->regeneratePayslip($payslip);

        if ($newPayslip && Storage::disk('public')->exists($newPayslip->file_path)) {
            $this->isLoadingDownload = false;
            return redirect()->route('payslip.download', ['payslip' => $newPayslip->id]);
        }

        $this->isLoadingDownload = false;
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

    public function shouldShowSkeleton(): bool
    {
        return $this->isLoadingDownload || 
               $this->isSearching || 
               $this->isFiltering || 
               $this->isPaginating ||
               $this->isLoadingData;
    }
};
?>

<div class="relative max-w-6xl mx-auto md:px-4 md:py-8">
    <!-- SVG Blobs Background -->
    <svg class="fixed -top-24 right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0" viewBox="0 0 400 400"
        fill="none">
        <ellipse cx="200" cy="200" rx="180" ry="120" fill="url(#blob1)" />
        <defs>
            <radialGradient id="blob1" cx="0" cy="0" r="1"
                gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                <stop stop-color="#38bdf8" />
                <stop offset="1" stop-color="#6366f1" />
            </radialGradient>
        </defs>
    </svg>
    <svg class="fixed -bottom-24 -right-32 w-96 h-96 opacity-30 blur-2xl pointer-events-none z-0"
        viewBox="0 0 400 400" fill="none">
        <ellipse cx="200" cy="200" rx="160" ry="100" fill="url(#blob2)" />
        <defs>
            <radialGradient id="blob2" cx="0" cy="0" r="1"
                gradientTransform="rotate(90 200 200) scale(200 200)" gradientUnits="userSpaceOnUse">
                <stop stop-color="#34d399" />
                <stop offset="1" stop-color="#f472b6" />
            </radialGradient>
        </defs>
    </svg>

    <!-- Breadcrumbs -->
    <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-full shadow-lg p-4 mb-8 z-10 relative border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <nav class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('payroll.employee') }}" class="border rounded-full py-2 px-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ request()->routeIs('payroll.employee') ? 'bg-green-600 dark:bg-green-700 text-white dark:text-zinc-200 border-green-400 dark:border-green-500' : '' }}" wire:navigate>
                    {{ __('My Payslips') }}
                </a>
            </div>
        </nav>
    </div>

    <!-- Card Container for Table -->
    <div class="relative bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xl rounded-xl shadow-2xl p-6 transition-all duration-300 hover:shadow-3xl border border-blue-100 dark:border-zinc-800 ring-1 ring-blue-200/30 dark:ring-zinc-700/40">
        <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-4">
            <div class="flex items-center gap-3 mb-8">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-800 via-green-500 to-blue-500 tracking-tight drop-shadow-lg relative inline-block">
                    {{ __('My Payslips') }}
                    <span class="absolute -bottom-2 left-0 w-[120px] h-1 rounded-full bg-gradient-to-r from-green-800 via-green-500 to-blue-500"></span>
                </h1>
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
                        placeholder="{{ __('Search payslips...') }}">
                </div>
                <button type="button" wire:click="toggleFilters"
                    class="flex items-center gap-1 px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 bg-white/80 dark:bg-zinc-900/80 text-blue-600 dark:text-indigo-300 hover:bg-blue-50/80 dark:hover:bg-zinc-800/80 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h8m-8 6h16"></path>
                    </svg>
                    <span class="hidden lg:inline">{{ __('Filters') }}</span>
                </button>
                @can('upload_external_payslip')
                <button type="button" wire:click="openUploadModal"
                    class="flex items-center gap-1 px-3 py-2 rounded-3xl border border-green-200 dark:border-green-700 bg-green-50/80 dark:bg-green-900/20 text-green-600 dark:text-green-400 hover:bg-green-100/80 dark:hover:bg-green-900/40 shadow-sm backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-green-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    <span class="hidden lg:inline">{{ __('Upload Payslip') }}</span>
                </button>
                @endcan
            </div>
        </div>

        <!-- Filter Options -->
        <div>
            @if ($showFilters ?? false)
                <div class="flex flex-wrap gap-6 mt-6 items-center animate-fade-in">
                    <select wire:model.live="periodFilter"
                        class="px-3 py-2 rounded-3xl border border-blue-200 dark:border-indigo-700 focus:ring-2 focus:ring-blue-400 dark:bg-zinc-800/80 dark:text-white shadow-sm bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md">
                        <option value="">{{ __('All Periods') }}</option>
                        @foreach($this->periods as $period)
                            <option value="{{ $period }}">{{ $period }}</option>
                        @endforeach
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

        <!-- Payslips Table -->
        <div class="overflow-x-auto bg-transparent mt-6">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead>
                    <tr class="h-16 bg-zinc-800/5 dark:bg-white/10 text-zinc-600 dark:text-white/70">
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('payslip_number')">
                            {{ __('Payslip Number') }}
                            @if($this->sortField === 'payslip_number')
                                <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    @endif
                                </svg>
                            @endif
                        </th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('period')">
                            {{ __('Period') }}
                            @if($this->sortField === 'period')
                                <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    @endif
                                </svg>
                            @endif
                        </th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('pay_date')">
                            {{ __('Pay Date') }}
                            @if($this->sortField === 'pay_date')
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
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wider">
                            {{ __('Status') }}
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
                                    <div class="h-4 w-32 bg-blue-100 dark:bg-zinc-800 rounded"></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="h-4 w-24 bg-blue-100 dark:bg-zinc-800 rounded"></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="h-4 w-28 bg-blue-100 dark:bg-zinc-800 rounded"></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="h-4 w-24 bg-green-100 dark:bg-zinc-800 rounded"></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="h-6 w-16 bg-gray-100 dark:bg-zinc-800 rounded-full"></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex gap-2">
                                        <div class="h-8 w-8 bg-gray-100 dark:bg-zinc-800 rounded"></div>
                                        <div class="h-8 w-8 bg-gray-100 dark:bg-zinc-800 rounded"></div>
                                    </div>
                                </td>
                            </tr>
                        @endfor
                    @else
                        @forelse(($this->payslips ?? []) as $payslip)
                            <tr class="hover:bg-gray-100 dark:hover:bg-white/20 group border-b border-gray-200 dark:border-gray-700 transition-all duration-500 ease-in-out" wire:loading.class.delay="opacity-50 dark:opacity-40">
                                <td class="px-5 py-4 text-gray-900 dark:text-white font-bold">
                                    {{ $payslip->payslip_number }}
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300 font-semibold">
                                    {{ $payslip->payroll->payroll_period }}
                                </td>
                                <td class="px-5 py-4 font-semibold">
                                    <span class="truncate text-blue-600 dark:text-blue-400">
                                        {{ $payslip->payroll->pay_date?->translatedFormat('j M Y') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300 font-bold">
                                    <span class="text-green-600 dark:text-green-400">
                                        £{{ number_format($payslip->payroll->net_pay, 2) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    @if($payslip->email_sent_at)
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold shadow bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            {{ __('Emailed') }}
                                        </span>
                                    @else
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold shadow bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ __('Generated') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span class="flex gap-2">
                                        <flux:button
                                            wire:click="viewPayslip({{ $payslip->id }})"
                                            variant="primary"
                                            color="blue"
                                            size="sm"
                                            icon="eye"
                                        />
                                        <flux:button
                                            wire:click="downloadPayslip({{ $payslip->id }})"
                                            variant="primary"
                                            color="green"
                                            size="sm"
                                            icon="arrow-down-tray"
                                            :disabled="$isLoadingDownload"
                                        />
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-8 h-8 text-gray-300 dark:text-zinc-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        {{ __('No payslips found.') }}
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
            
            <!-- Pagination -->
            <div class="mt-6">
                @if($this->payslips && !$this->shouldShowSkeleton())
                    {{ $this->payslips->links() }}
                @endif
            </div>
        </div>
    </div>

    <!-- Payslip Modal -->
    @if($showPayslipModal && $selectedPayslip)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition p-4">
            <div class="bg-white dark:bg-zinc-900 backdrop-blur-xl rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto border border-gray-100 dark:border-zinc-800">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{ __('Payslip Details') }}
                            @if($selectedPayslip->is_external)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    {{ __('External') }}
                                </span>
                            @endif
                        </h2>
                        <button wire:click="closeModal" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Payslip Preview -->
                    <div class="border rounded-xl p-6 bg-gradient-to-br from-gray-50/80 to-blue-50/80 dark:from-gray-800/80 dark:to-gray-700/80 backdrop-blur-md">
                        <div class="text-center mb-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $selectedPayslip->payslip_number }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">
                                {{ $selectedPayslip->payroll->payroll_period }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
<div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md rounded-lg p-4 border border-green-200 dark:border-green-800">
                                <h4 class="font-bold text-green-900 dark:text-green-100 mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                    {{ __('Earnings') }}
                                </h4>
                                <div class="space-y-2 text-sm">
                                    @if($selectedPayslip->payroll->basic_salary > 0)
                                        <div class="flex justify-between">
                                            <span>{{ __('Basic Salary') }}:</span>
                                            <span class="font-semibold">£{{ number_format($selectedPayslip->payroll->basic_salary, 2) }}</span>
                                        </div>
                                    @endif
                                    @if($selectedPayslip->payroll->total_allowances > 0)
                                        <div class="flex justify-between">
                                            <span>{{ __('Allowances') }}:</span>
                                            <span class="font-semibold">£{{ number_format($selectedPayslip->payroll->total_allowances, 2) }}</span>
                                        </div>
                                    @endif
                                    <div class="flex justify-between font-bold border-t pt-2 text-green-700 dark:text-green-400">
                                        <span>{{ __('Gross Pay') }}:</span>
                                        <span>£{{ number_format($selectedPayslip->payroll->gross_pay, 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md rounded-lg p-4 border border-red-200 dark:border-red-800">
                                <h4 class="font-bold text-red-900 dark:text-red-100 mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                    {{ __('Deductions') }}
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span>{{ __('Tax & Deductions') }}:</span>
                                        <span class="font-semibold">£{{ number_format($selectedPayslip->payroll->total_deductions, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between font-bold border-t pt-2 text-red-700 dark:text-red-400">
                                        <span>{{ __('Net Pay') }}:</span>
                                        <span>£{{ number_format($selectedPayslip->payroll->net_pay, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                            </div>
                        </div>
                                    @endif
                                    <div class="flex justify-between font-bold border-t pt-2 text-green-600 dark:text-green-400">
                                        <span>{{ __('Gross Pay') }}:</span>
                                        <span>£{{ number_format($selectedPayslip->payroll->gross_pay, 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md rounded-lg p-4 border border-red-200 dark:border-red-800">
                                <h4 class="font-bold text-red-900 dark:text-red-100 mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"></path>
                                    </svg>
                                    {{ __('Deductions') }}
                                </h4>
                                <div class="space-y-2 text-sm">
                                    @if($selectedPayslip->payroll->paye_tax > 0)
                                        <div class="flex justify-between">
                                            <span>{{ __('Income Tax (PAYE)') }}:</span>
                                            <span class="font-semibold">£{{ number_format($selectedPayslip->payroll->paye_tax, 2) }}</span>
                                        </div>
                                    @endif
                                    @if($selectedPayslip->payroll->national_insurance > 0)
                                        <div class="flex justify-between">
                                            <span>{{ __('National Insurance') }}:</span>
                                            <span class="font-semibold">£{{ number_format($selectedPayslip->payroll->national_insurance, 2) }}</span>
                                        </div>
                                    @endif
                                    @if($selectedPayslip->payroll->student_loan_deduction > 0)
                                        <div class="flex justify-between">
                                            <span>{{ __('Student Loan') }}:</span>
                                            <span class="font-semibold">£{{ number_format($selectedPayslip->payroll->student_loan_deduction, 2) }}</span>
                                        </div>
                                    @endif
                                    @if($selectedPayslip->payroll->pension_contribution > 0)
                                        <div class="flex justify-between">
                                            <span>{{ __('Pension') }}:</span>
                                            <span class="font-semibold">£{{ number_format($selectedPayslip->payroll->pension_contribution, 2) }}</span>
                                        </div>
                                    @endif
                                    @if($selectedPayslip->payroll->total_deductions > 0)
                                        <div class="flex justify-between">
                                            <span>{{ __('Total Deductions') }}:</span>
                                            <span class="font-semibold">£{{ number_format($selectedPayslip->payroll->total_deductions, 2) }}</span>
                                        </div>
                                    @endif
                                    <div class="flex justify-between font-bold border-t pt-2 text-red-600 dark:text-red-400">
                                        <span>{{ __('Net Pay') }}:</span>
                                        <span>£{{ number_format($selectedPayslip->payroll->net_pay, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Employee Information -->
                        <div class="mt-6 p-4 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md rounded-lg border border-blue-200 dark:border-blue-800">
                            <h4 class="font-bold text-blue-900 dark:text-blue-100 mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                {{ __('Employee Information') }}
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div class="flex justify-between">
                                    <span>{{ __('Employee ID') }}:</span>
                                    <span class="font-semibold">{{ $selectedPayslip->payroll->employee->staff_number ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>{{ __('Name') }}:</span>
                                    <span class="font-semibold">{{ $selectedPayslip->payroll->employee->user->first_name ?? '' }} {{ $selectedPayslip->payroll->employee->user->other_names ?? '' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>{{ __('Department') }}:</span>
                                    <span class="font-semibold">{{ $selectedPayslip->payroll->employee->department->name ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>{{ __('Designation') }}:</span>
                                    <span class="font-semibold">{{ $selectedPayslip->payroll->employee->designation->name ?? 'N/A' }}</span>
                                </div>
                                @if($selectedPayslip->payroll->tax_code)
                                <div class="flex justify-between">
                                    <span>{{ __('Tax Code') }}:</span>
                                    <span class="font-semibold">{{ $selectedPayslip->payroll->tax_code }}</span>
                                </div>
                                @endif
                                @if($selectedPayslip->payroll->nic_category)
                                <div class="flex justify-between">
                                    <span>{{ __('NI Category') }}:</span>
                                    <span class="font-semibold">{{ $selectedPayslip->payroll->nic_category }}</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            @if($selectedPayslip->is_external)
                                <button wire:click="downloadExternalPayslip({{ $selectedPayslip->id }})"
                                    class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center gap-2"
                                    @if ($isLoadingDownload) disabled @endif>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    {{ $isLoadingDownload ? __('Downloading...') : __('Download') }}
                                </button>
                                @can('delete_external_payslip')
                                <button wire:click="deleteExternalPayslip({{ $selectedPayslip->id }})"
                                    class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center gap-2"
                                    onclick="return confirm('Are you sure you want to delete this external payslip?')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    {{ __('Delete') }}
                                </button>
                                @endcan
                            @else
                                <button wire:click="downloadPayslip({{ $selectedPayslip->id }})"
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center gap-2"
                                    @if ($isLoadingDownload) disabled @endif>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    {{ $isLoadingDownload ? __('Downloading...') : __('Download PDF') }}
                                </button>
                            @endif
                            <button wire:click="closeModal"
                                class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                                {{ __('Close') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Upload External Payslip Modal -->
    @if($showUploadModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeUploadModal"></div>
                <div class="relative bg-white dark:bg-zinc-900 rounded-xl shadow-2xl p-6 w-full max-w-md z-10">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ __('Upload External Payslip') }}
                        </h2>
                        <button wire:click="closeUploadModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="uploadExternalPayslip" enctype="multipart/form-data">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('Payroll Period') }}
                                </label>
                                <select wire:model="uploadPeriod" required
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                                    <option value="">{{ __('Select Period') }}</option>
                                    @foreach($availableUploadPeriods as $period)
                                        <option value="{{ $period }}">{{ $period }}</option>
                                    @endforeach
                                </select>
                                @error('uploadPeriod') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('Pay Date') }}
                                </label>
                                <input type="date" wire:model="uploadPayDate" required
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                                @error('uploadPayDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('Payslip File') }}
                                </label>
                                <input type="file" wire:model="uploadedFile" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                <p class="text-xs text-gray-500 mt-1">{{ __('Max size: 5MB. Supported: PDF, DOC, DOCX, JPG, PNG') }}</p>
                                @error('uploadedFile') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="flex gap-3 mt-6">
                            <button type="submit"
                                class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
                                {{ __('Upload') }}
                            </button>
                            <button type="button" wire:click="closeUploadModal"
                                class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
                                {{ __('Cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>