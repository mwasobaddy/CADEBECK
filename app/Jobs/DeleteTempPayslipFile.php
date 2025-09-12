<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class DeleteTempPayslipFile implements ShouldQueue
{
    use Queueable;

    protected string $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Delete the temporary payslip file
        if (Storage::disk('public')->exists($this->filePath)) {
            Storage::disk('public')->delete($this->filePath);

            \Log::info('Temporary payslip file deleted', [
                'file_path' => $this->filePath
            ]);
        }
    }
}
