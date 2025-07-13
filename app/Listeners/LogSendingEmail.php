<?php

namespace App\Listeners;

use App\Services\EmailLogService;
use Illuminate\Mail\Events\MessageSending;

class LogSendingEmail
{
    /**
     * Handle the event.
     */
    public function handle(MessageSending $event): void
    {
        // Store the log in a temporary property so we can update it after sending
        $log = EmailLogService::logFromMailEvent($event);
        
        if ($log) {
            // Store the log ID in the message headers so we can retrieve it after sending
            $event->message->getHeaders()->addTextHeader('X-Email-Log-Id', $log->id);
        }
    }
}