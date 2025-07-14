<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'url',
        'icon',
        'recipients_type',
        'tier',
        'sent_count',
        'failed_count',
        'sent_by',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who sent this notification.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Get the recipients type label.
     */
    public function getRecipientsLabelAttribute(): string
    {
        return match($this->recipients_type) {
            'all' => 'All Push Subscribers',
            'push_enabled' => 'Push Enabled Users',
            'tier' => ucfirst($this->tier) . ' Tier',
            default => 'Unknown',
        };
    }

    /**
     * Get the success rate.
     */
    public function getSuccessRateAttribute(): float
    {
        $total = $this->sent_count + $this->failed_count;
        return $total > 0 ? round(($this->sent_count / $total) * 100, 1) : 0;
    }
}