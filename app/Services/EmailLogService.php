<?php

namespace App\Services;

use App\Models\EmailLog;
use Illuminate\Mail\SentMessage;
use Symfony\Component\Mime\Email;

class EmailLogService
{
    /**
     * Log an email that is about to be sent
     */
    public static function logEmail(
        string $toEmail,
        string $toName,
        string $subject,
        ?string $templateKey = null,
        array $metadata = []
    ): EmailLog {
        return EmailLog::create([
            'to_email' => $toEmail,
            'to_name' => $toName,
            'subject' => $subject,
            'template_key' => $templateKey,
            'status' => EmailLog::STATUS_PENDING,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Update email log after successful send
     */
    public static function markAsSent(EmailLog $log, ?string $messageId = null): void
    {
        $log->update([
            'status' => EmailLog::STATUS_SENT,
            'message_id' => $messageId,
            'sent_at' => now(),
        ]);
    }

    /**
     * Update email log after failed send
     */
    public static function markAsFailed(EmailLog $log, string $errorMessage): void
    {
        $log->update([
            'status' => EmailLog::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Log email from Laravel mail event
     */
    public static function logFromMailEvent($event): ?EmailLog
    {
        $message = $event->message;
        
        // Get recipient
        $to = $message->getTo();
        if (empty($to)) {
            return null;
        }
        
        $recipient = array_key_first($to);
        $recipientData = $to[$recipient];
        
        // Handle both string names and Symfony Address objects
        if (is_object($recipientData) && method_exists($recipientData, 'getName')) {
            $recipientName = $recipientData->getName() ?? '';
        } else {
            $recipientName = is_string($recipientData) ? $recipientData : '';
        }
        
        // Extract template key from headers if available
        $templateKey = null;
        $headers = $message->getHeaders();
        if ($headers->has('X-Template-Key')) {
            $templateKey = $headers->get('X-Template-Key')->getValue();
        }
        
        // Extract metadata from headers if available
        $metadata = [];
        if ($headers->has('X-Email-Metadata')) {
            $metadata = json_decode($headers->get('X-Email-Metadata')->getValue(), true) ?? [];
        }
        
        return self::logEmail(
            $recipient,
            $recipientName,
            $message->getSubject() ?? 'No Subject',
            $templateKey,
            $metadata
        );
    }

    /**
     * Update log after email is sent
     */
    public static function updateFromSentMessage(?EmailLog $log, SentMessage $message): void
    {
        if (!$log) {
            return;
        }
        
        $symfonyMessage = $message->getOriginalMessage();
        $messageId = null;
        
        if ($symfonyMessage instanceof Email) {
            $headers = $symfonyMessage->getHeaders();
            if ($headers->has('Message-ID')) {
                $messageId = $headers->get('Message-ID')->getValue();
            }
        }
        
        self::markAsSent($log, $messageId);
    }
}