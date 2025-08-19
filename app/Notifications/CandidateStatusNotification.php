<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CandidateStatusNotification extends Notification
{
    use Queueable;

    public string $status;
    public string $interviewDetails;

    public function __construct(string $status, string $interviewDetails = '')
    {
        $this->status = $status;
        $this->interviewDetails = $interviewDetails;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject(__('Your Application Status Update'));

        if ($this->status === 'Shortlisted') {
            $mail->line(__('Congratulations! You have been shortlisted.'));
        } elseif ($this->status === 'Rejected') {
            $mail->line(__('We regret to inform you that your application was not successful.'));
        } elseif ($this->status === 'Invited') {
            $mail->line(__('You are invited for an interview.'));
            if ($this->interviewDetails) {
                $mail->line(__('Interview Details:') . ' ' . $this->interviewDetails);
            }
        } else {
            $mail->line(__('Your application status has been updated.'));
        }

        $mail->line(__('Thank you for your interest in the position.'));
        return $mail;
    }
}
