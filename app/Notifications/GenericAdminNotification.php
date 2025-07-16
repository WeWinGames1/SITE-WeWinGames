<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class GenericAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $title;

    public string $body;

    public function __construct($title, $body)
    {
        $this->title = $title;
        $this->body = $body;
    }

    public function via($notifiable)
    {
        $channels = [];
        if ($notifiable->notification_preferences['email'] ?? false) {
            $channels[] = 'mail';
        }
        if ($notifiable->notification_preferences['push'] ?? false) {
            $channels[] = WebPushChannel::class;
        }
        $channels[] = 'database';

        return $channels;
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello!')
            ->view('emails.generic-notification', [
                'body' => $this->body,
                'title' => $this->title,
            ]);
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->icon('/images/icons/icon-192x192.png')
            ->body($this->body)
            ->action('View Dashboard', url('/dashboard'));
    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
        ];
    }
}
