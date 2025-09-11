<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\Employee;
use App\Models\Payslip;
use App\Services\PayslipService;

class TestPayslipEmail extends Command
{
    protected $signature = 'test:payslip-email {employee_id?} {--all : Send test emails to all employees with payslips}';
    protected $description = 'Test payslip email functionality';

    public function handle()
    {
        $this->info('🧪 Testing Payslip Email Configuration');
        $this->info('=====================================');

        // Check mail configuration
        $this->checkMailConfiguration();

        $payslipService = app(PayslipService::class);

        if ($this->option('all')) {
            $this->sendToAllEmployees($payslipService);
        } elseif ($this->argument('employee_id')) {
            $this->sendToSpecificEmployee($this->argument('employee_id'), $payslipService);
        } else {
            $this->sendTestEmail($payslipService);
        }

        $this->info('✅ Email test completed!');
        return Command::SUCCESS;
    }

    protected function checkMailConfiguration()
    {
        $this->info('📧 Checking Mail Configuration...');

        $config = [
            'Mailer' => config('mail.default'),
            'Host' => config('mail.mailers.smtp.host'),
            'Port' => config('mail.mailers.smtp.port'),
            'Username' => config('mail.mailers.smtp.username') ? 'Set' : 'Not Set',
            'Password' => config('mail.mailers.smtp.password') ? 'Set' : 'Not Set',
            'From Address' => config('mail.from.address'),
            'From Name' => config('mail.from.name'),
        ];

        foreach ($config as $key => $value) {
            $this->line("  {$key}: <comment>{$value}</comment>");
        }

        $this->newLine();
    }

    protected function sendTestEmail(PayslipService $payslipService)
    {
        $this->info('📤 Sending Test Email...');

        // Create a dummy payslip for testing
        $employee = Employee::with('user')->first();

        if (!$employee) {
            $this->error('❌ No employees found in database. Please create some test data first.');
            return;
        }

        if (!$employee->user || !$employee->user->email) {
            $this->error('❌ Employee does not have an associated user with email address.');
            return;
        }

        // Find or create a test payslip
        $payslip = Payslip::where('employee_id', $employee->id)->first();

        if (!$payslip) {
            $this->warn('⚠️  No payslips found for this employee. Creating a test payslip...');

            // This would require a payroll record - for now, just show the email would work
            $this->info('💡 To test with real payslips, first process payroll for employees.');
            return;
        }

        $result = $payslipService->sendPayslipEmail(
            $payslip,
            'Test Payslip Email - ' . now()->format('Y-m-d H:i:s'),
            'This is a test email to verify payslip email functionality.'
        );

        if ($result) {
            $this->info('✅ Test email sent successfully!');
            $this->line("   To: <comment>{$employee->user->email}</comment>");
            $this->line("   Subject: <comment>Test Payslip Email</comment>");
        } else {
            $this->error('❌ Failed to send test email. Check logs for details.');
        }
    }

    protected function sendToSpecificEmployee($employeeId, PayslipService $payslipService)
    {
        $employee = Employee::with('user')->find($employeeId);

        if (!$employee) {
            $this->error("❌ Employee with ID {$employeeId} not found.");
            return;
        }

        $payslip = Payslip::where('employee_id', $employee->id)->first();

        if (!$payslip) {
            $this->error("❌ No payslips found for employee {$employee->first_name} {$employee->other_names}.");
            return;
        }

        $this->info("📤 Sending payslip email to {$employee->first_name} {$employee->other_names}...");

        $result = $payslipService->sendPayslipEmail($payslip);

        if ($result) {
            $this->info('✅ Payslip email sent successfully!');
        } else {
            $this->error('❌ Failed to send payslip email.');
        }
    }

    protected function sendToAllEmployees(PayslipService $payslipService)
    {
        $payslips = Payslip::with(['payroll.employee.user'])
            ->whereHas('payroll.employee.user')
            ->get();

        if ($payslips->isEmpty()) {
            $this->error('❌ No payslips found with associated employees and users.');
            return;
        }

        $this->info("📤 Sending payslip emails to {$payslips->count()} employees...");
        $this->newLine();

        $bar = $this->output->createProgressBar($payslips->count());
        $bar->start();

        $sent = 0;
        $failed = 0;

        foreach ($payslips as $payslip) {
            $result = $payslipService->sendPayslipEmail($payslip);

            if ($result) {
                $sent++;
            } else {
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("📊 Email Summary:");
        $this->line("   ✅ Sent: <info>{$sent}</info>");
        $this->line("   ❌ Failed: <error>{$failed}</error>");
        $this->line("   📧 Total: <comment>" . ($sent + $failed) . "</comment>");
    }
}
