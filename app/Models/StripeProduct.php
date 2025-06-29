<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StripeProduct extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'tier',
        'billing_period',
        'price',
        'stripe_product_id',
        'stripe_price_id',
        'is_active',
        'features',
        'sort_order',
        'badge_text',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'features' => 'array',
    ];

    /**
     * Get the formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Get the display name with period
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->tier . ' ' . ucfirst($this->billing_period);
    }

    /**
     * Check if this product is connected to Stripe
     */
    public function isConnectedToStripe(): bool
    {
        return !empty($this->stripe_product_id) && !empty($this->stripe_price_id);
    }

    /**
     * Scope to get only active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get products by tier
     */
    public function scopeByTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }

    /**
     * Scope to get products by billing period
     */
    public function scopeByBillingPeriod($query, string $period)
    {
        return $query->where('billing_period', $period);
    }

    /**
     * Get products ordered for display
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('tier');
    }
}
