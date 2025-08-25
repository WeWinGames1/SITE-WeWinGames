<?php

namespace App\Notifications\Messages;

use Illuminate\Notifications\Messages\MailMessage;

class LoggedMailMessage extends MailMessage
{
    protected ?string $templateKey = null;
    protected array $logMetadata = [];

    /**
     * Set the template key for logging
     */
    public function templateKey(string $key): self
    {
        $this->templateKey = $key;
        return $this;
    }

    /**
     * Set metadata for logging
     */
    public function withLogMetadata(array $metadata): self
    {
        $this->logMetadata = $metadata;
        return $this;
    }

    /**
     * Get the template key
     */
    public function getTemplateKey(): ?string
    {
        return $this->templateKey;
    }

    /**
     * Get the metadata
     */
    public function getMetadata(): array
    {
        return $this->logMetadata;
    }
}