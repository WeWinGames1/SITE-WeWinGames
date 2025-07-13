<?php

namespace App\Listeners;

use App\Models\EmailLog;
use App\Services\EmailLogService;
use Illuminate\Mail\Events\MessageSent;

class LogSentEmail
{
    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        // Get the log ID from the headers
        $headers = $event->sent->getOriginalMessage()->getHeaders();
        
        if ($headers->has('X-Email-Log-Id')) {
            $logId = $headers->get('X-Email-Log-Id')->getValue();
            $log = EmailLog::find($logId);
            
            if ($log) {
                EmailLogService::updateFromSentMessage($log, $event->sent);
            }
        }
    }
}