<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class TemplatedEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected EmailTemplate $template;

    protected array $data;

    protected array $emailMetadata;

    /**
     * Create a new message instance.
     */
    public function __construct(EmailTemplate $template, array $data = [], array $metadata = [])
    {
        $this->template = $template;
        $this->data = $data;
        $this->emailMetadata = $metadata;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $rendered = $this->template->render($this->data);

        $envelope = new Envelope(
            subject: $rendered['subject'],
            from: [$rendered['from_email'] => $rendered['from_name']],
        );

        // Add reply-to if different from from_email
        if (isset($rendered['reply_to_email']) && $rendered['reply_to_email'] !== $rendered['from_email']) {
            $envelope->replyTo($rendered['reply_to_email'], $rendered['from_name']);
        }

        return $envelope;
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $rendered = $this->template->render($this->data);

        return new Content(
            html: $rendered['body_html'],
            text: $rendered['body_text'],
        );
    }

    /**
     * Get the message headers.
     */
    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Template-Key' => $this->template->key,
                'X-Email-Metadata' => json_encode($this->emailMetadata),
            ],
        );
    }
}
