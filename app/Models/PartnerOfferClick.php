<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerOfferClick extends Model
{
    protected $fillable = [
        'partner_offer_id',
        'user_id',
        'ip_address',
        'user_agent',
        'referer',
        'source_page',
    ];

    public function partnerOffer(): BelongsTo
    {
        return $this->belongsTo(PartnerOffer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
