<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Broadcasting\DatabaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ExamNotification extends Notification
{
    use Queueable;
    private $exam;

    /**
     * Create a new notification instance.
     */
    public function __construct($exam)
    {
        $this->exam = $exam;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // return ['mail'];
        return [DatabaseChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        //From get lang/notifications/en.php dir.
        return [
            "title" => __('notifications.examUpdate.title'), 
            "body"  => __("notifications.examUpdate.body", ['name' => $this->exam->title]),
        ];
    }
}
