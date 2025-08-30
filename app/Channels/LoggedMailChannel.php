<?php

namespace App\Channels;

use App\Notifications\Messages\LoggedMailMessage;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;

class LoggedMailChannel extends MailChannel
{
    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification): void
    {
        $message = $notification->toMail($notifiable);

        if (! $notifiable->routeNotificationFor('mail', $notification)) {
            return;
        }

        $this->mailer->send(
            $this->buildView($message),
            array_merge($message->data(), $this->additionalMessageData($notification)),
            function ($mailMessage) use ($notifiable, $notification, $message) {
                $this->buildMessage($mailMessage, $notifiable, $notification, $message);

                // Add custom headers if it's a LoggedMailMessage
                if ($message instanceof LoggedMailMessage) {
                    if ($templateKey = $message->getTemplateKey()) {
                        $mailMessage->getHeaders()->addTextHeader('X-Template-Key', $templateKey);
                    }

                    if ($metadata = $message->getMetadata()) {
                        $mailMessage->getHeaders()->addTextHeader('X-Email-Metadata', json_encode($metadata));
                    }
                }
            }
        );
    }
}
