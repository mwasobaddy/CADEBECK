<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Payslip;
use Illuminate\Support\Facades\Storage;

class PayslipNotification extends Notification
{
    use Queueable;

    public Payslip $payslip;
    public string $subject;
    public string $message;

    public function __construct(Payslip $payslip, ?string $subject = null, ?string $message = null)
    {
        $this->payslip = $payslip;
        $this->subject = $subject ?? __('Your Payslip for') . ' ' . $payslip->payroll_period;
        $this->message = $message ?? __('Your payslip has been generated and is attached to this email.');
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $payslip = $this->payslip;
        $payroll = $payslip->payroll;
        $employee = $payroll->employee;

        $mail = (new MailMessage)
            ->subject($this->subject)
            ->greeting(__('Hello') . ' ' . $employee->first_name . ',')
            ->line($this->message)
            ->line(__('Payroll Period:') . ' ' . $payslip->payroll_period)
            ->line(__('Pay Date:') . ' ' . ($payroll->pay_date ? $payroll->pay_date->format('M d, Y') : 'N/A'))
            ->line(__('Gross Pay:') . ' KES ' . number_format($payroll->gross_pay, 2))
            ->line(__('Net Pay:') . ' KES ' . number_format($payroll->net_pay, 2))
            ->line(__(''))
            ->line(__('Please find your payslip attached to this email.'))
            ->line(__('If you have any questions about your payslip, please contact the HR department.'))
            ->salutation(__('Best regards,') . "\n" . __('CADEBECK HR Team'));

        // Attach the payslip PDF if file exists
        if (Storage::disk('public')->exists($payslip->file_path)) {
            $filePath = Storage::disk('public')->path($payslip->file_path);
            $mail->attach($filePath, [
                'as' => $payslip->file_name,
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }

    public function toArray($notifiable)
    {
        return [
            'payslip_id' => $this->payslip->id,
            'payroll_period' => $this->payslip->payroll_period,
            'subject' => $this->subject,
            'message' => $this->message,
        ];
    }
}
