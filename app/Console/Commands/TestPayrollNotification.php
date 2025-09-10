<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Payroll;
use App\Notifications\PayrollProcessedNotification;

class TestPayrollNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:payroll-notification {--employee_id= : The employee ID to test with}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test payroll notification system by sending a notification to an employee';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $employeeId = $this->option('employee_id');

        if (!$employeeId) {
            $employee = Employee::with('user')->first();
            if (!$employee) {
                $this->error('No employees found in the database. Please create an employee first.');
                return;
            }
            $employeeId = $employee->id;
        }

        $employee = Employee::with('user')->find($employeeId);
        if (!$employee) {
            $this->error("Employee with ID {$employeeId} not found.");
            return;
        }

        $employeeName = trim(($employee->user->first_name ?? '') . ' ' . ($employee->user->other_names ?? ''));
        $this->info("Testing payroll notification for employee: {$employeeName} (ID: {$employee->id})");

        // Create a test payroll record
        $payroll = Payroll::create([
            'employee_id' => $employee->id,
            'payroll_period' => now()->format('m/Y'),
            'pay_date' => now()->endOfMonth(),
            'basic_salary' => 50000,
            'gross_pay' => 55000,
            'net_pay' => 48000,
            'status' => 'processed',
        ]);

        $this->info("Payroll ID: {$payroll->id}");
        $this->info("Sending notification...");

        try {
            $employee->notify(new PayrollProcessedNotification($payroll));
            $this->info("✅ Notification sent successfully!");
            $this->info("Check your mail logs or email service for the notification.");
        } catch (\Exception $e) {
            $this->error("❌ Failed to send notification: " . $e->getMessage());
        }

        // Clean up test data
        $payroll->delete();
        $this->info("Test payroll record cleaned up.");
    }
}
