<?php

namespace App\Notifications\Messages;

use Illuminate\Notifications\Messages\MailMessage;

class LoggedMailMessage extends MailMessage
{
    protected ?string $templateKey = null;
    protected array $metadata = [];

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
    public function metadata(array $metadata): self
    {
        $this->metadata = $metadata;
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
        return $this->metadata;
    }
}