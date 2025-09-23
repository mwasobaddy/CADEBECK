<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Application;
use App\Models\JobAdvert;

class ApplicationStatusNotification extends Notification
{
    use Queueable;

    protected $application;
    protected $oldStatus;
    protected $newStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(Application $application, string $oldStatus, string $newStatus)
    {
        $this->application = $application;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $jobAdvert = $this->application->jobAdvert;
        
        return (new MailMessage)
                    ->subject('Application Status Update - ' . $jobAdvert->title)
                    ->view('emails.application-status', [
                        'application' => $this->application,
                        'jobAdvert' => $jobAdvert,
                        'oldStatus' => $this->oldStatus,
                        'newStatus' => $this->newStatus,
                        'applicantName' => $this->application->name,
                        'jobTitle' => $jobAdvert->title,
                        'companyName' => config('app.name', 'CADEBECK'),
                        'applicationDate' => $this->application->submitted_at ? $this->application->submitted_at->format('M d, Y') : 'Not specified',
                    ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'job_advert_id' => $this->application->job_advert_id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'applicant_name' => $this->application->name,
            'job_title' => $this->application->jobAdvert->title,
        ];
    }
}