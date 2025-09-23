<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\LeaveRequest;

class LeaveRequestNotification extends Notification
{

    protected $leaveRequest;
    // protected $delay;

    /**
     * Create a new notification instance.
     */
    // public function __construct(LeaveRequest $leaveRequest, $delay = 0)
    public function __construct(LeaveRequest $leaveRequest)
    {
        $this->leaveRequest = $leaveRequest;
        // $this->delay = $delay;
        
        // Set the delay on the notification itself if specified
        // if ($delay > 0) {
        //     $this->delay(now()->addSeconds($delay));
        // }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        // Load necessary relationships to ensure data is available in the email template
        $this->leaveRequest->load([
            'employee.user',
            'employee.supervisor.user',
            'employee.department',
            'employee.designation'
        ]);
        
        return (new MailMessage)
                    ->subject('New Leave Request Submitted')
                    ->view('emails.leave-request', [
                        'leaveRequest' => $this->leaveRequest,
                        'employee' => $this->leaveRequest->employee,
                        'user' => $this->leaveRequest->user,
                        'editUrl' => url('/all-leave/' . $this->leaveRequest->id . '/edit'),
                    ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}