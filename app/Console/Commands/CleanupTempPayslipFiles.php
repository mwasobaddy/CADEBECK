<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PayslipService;

class CleanupTempPayslipFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payslips:cleanup-temp {--days=1 : Number of days old files to delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up temporary payslip files older than specified days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');

        $this->info("Cleaning up temporary payslip files older than {$days} day(s)...");

        $payslipService = app(PayslipService::class);
        $deletedCount = $payslipService->cleanupOldTempFiles($days);

        $this->info("Cleanup completed. {$deletedCount} temporary files deleted.");

        return Command::SUCCESS;
    }
}
