<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'discount_code_id',
        'user_id',
        'subscription_id',
        'discount_applied',
        'stripe_invoice_id',
    ];

    protected $casts = [
        'discount_applied' => 'decimal:2',
    ];

    /**
     * Get the discount code
     */
    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    /**
     * Get the user who redeemed the code
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
