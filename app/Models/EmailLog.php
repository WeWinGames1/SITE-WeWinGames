<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'to_email',
        'to_name',
        'subject',
        'template_key',
        'status',
        'message_id',
        'error_message',
        'metadata',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';

    const STATUS_SENT = 'sent';

    const STATUS_FAILED = 'failed';

    const STATUS_DELIVERED = 'delivered';

    const STATUS_OPENED = 'opened';

    const STATUS_CLICKED = 'clicked';

    const STATUS_BOUNCED = 'bounced';

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-warning',
            self::STATUS_SENT => 'bg-info',
            self::STATUS_FAILED => 'bg-danger',
            self::STATUS_DELIVERED => 'bg-success',
            self::STATUS_OPENED => 'bg-primary',
            self::STATUS_CLICKED => 'bg-primary',
            self::STATUS_BOUNCED => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * Get human-readable status
     */
    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }
}
