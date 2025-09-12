<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Services\PayslipService;
use Symfony\Component\HttpFoundation\Response;

class TempFileCleanup
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only run cleanup for authenticated users and not on API routes
        if (auth()->check() && !$request->is('api/*')) {
            $this->performCleanupIfNeeded();
        }

        return $next($request);
    }

    /**
     * Perform cleanup only if it hasn't been done recently
     */
    protected function performCleanupIfNeeded(): void
    {
        $cacheKey = 'temp_file_cleanup_last_run';
        $lastRun = Cache::get($cacheKey);

        // Only run cleanup if it hasn't been run in the last 6 hours
        if (!$lastRun || now()->diffInHours($lastRun) >= 6) {
            try {
                $payslipService = app(PayslipService::class);
                $deletedCount = $payslipService->cleanupOldTempFiles(1); // Clean files older than 1 day

                if ($deletedCount > 0) {
                    \Log::info("Automatic temp file cleanup completed", [
                        'deleted_files' => $deletedCount,
                        'triggered_by' => auth()->user()->email ?? 'unknown'
                    ]);
                }

                // Update last run time
                Cache::put($cacheKey, now(), now()->addHours(6));

            } catch (\Exception $e) {
                \Log::error('Automatic temp file cleanup failed', [
                    'error' => $e->getMessage(),
                    'user' => auth()->user()->email ?? 'unknown'
                ]);
            }
        }
    }
}
