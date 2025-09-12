<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Payroll;

class PayrollApprovalNotification extends Notification
{
    use Queueable;

    public Payroll $payroll;
    public string $subject;
    public string $message;

    public function __construct(Payroll $payroll, ?string $subject = null, ?string $message = null)
    {
        $this->payroll = $payroll;
        $this->subject = $subject ?? 'Payroll Processing Initiated for ' . $payroll->payroll_period;
        $this->message = $message ?? 'Your payroll has been initiated for processing and will be reviewed shortly.';
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $payroll = $this->payroll;
        $employee = $payroll->employee;

        // Use rich HTML template for better presentation
        return (new MailMessage)
            ->subject($this->subject)
            ->view('emails.payroll-approval', [
                'payroll' => $payroll,
                'employee' => $employee,
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'payroll_id' => $this->payroll->id,
            'payroll_period' => $this->payroll->payroll_period,
            'subject' => $this->subject,
            'message' => $this->message,
            'type' => 'payroll_approval',
            'action_url' => route('employee.payroll-history'),
        ];
    }
}