<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class AdminPushNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
        public ?string $icon = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        $message = (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->action('View', $this->url ?? '/')
            ->options([
                'TTL' => 1000,
            ]);

        if ($this->icon) {
            $message->icon($this->icon);
        }

        // Add custom data that will be available in the service worker
        $message->data([
            'url' => $this->url ?? '/',
            'created_at' => now()->toIso8601String(),
        ]);

        return $message;
    }
}
