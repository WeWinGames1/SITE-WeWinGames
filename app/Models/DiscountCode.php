<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class DiscountCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_amount',
        'apply_to',
        'months_count',
        'max_uses',
        'max_uses_per_customer',
        'times_used',
        'valid_from',
        'valid_until',
        'applicable_products',
        'minimum_amount',
        'is_active',
        'stripe_coupon_id',
        'created_by',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'applicable_products' => 'array',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    protected $appends = [
        'percent_off',
        'amount_off',
    ];

    /**
     * Get the user who created the discount code
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the redemptions for this discount code
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(DiscountRedemption::class);
    }

    /**
     * Alias for redemptions() for backward compatibility
     */
    public function couponUsages(): HasMany
    {
        return $this->redemptions();
    }

    /**
     * Check if the code is valid for use
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check date validity
        $now = Carbon::now();
        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }
        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        // Check usage limits
        if ($this->max_uses && $this->times_used >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Check if the code can be used by a specific user
     */
    public function canBeUsedBy(User $user): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // Check per-user usage limit
        $userRedemptions = $this->redemptions()
            ->where('user_id', $user->id)
            ->count();

        return $userRedemptions < $this->max_uses_per_customer;
    }

    /**
     * Check if the code applies to a specific product
     */
    public function appliesToProduct(string $stripeProductId): bool
    {
        if (empty($this->applicable_products)) {
            return true; // Applies to all products
        }

        return in_array($stripeProductId, $this->applicable_products);
    }

    /**
     * Calculate the discount amount for a given price
     */
    public function calculateDiscount(float $originalPrice): float
    {
        if ($this->discount_type === 'percentage') {
            return $originalPrice * ($this->discount_amount / 100);
        }

        return min($this->discount_amount, $originalPrice);
    }

    /**
     * Get the formatted discount display
     */
    public function getFormattedDiscountAttribute(): string
    {
        if ($this->discount_type === 'percentage') {
            return $this->discount_amount . '%';
        }

        return '$' . number_format($this->discount_amount, 2);
    }

    /**
     * Get percent_off attribute for compatibility
     */
    public function getPercentOffAttribute(): ?float
    {
        return $this->discount_type === 'percentage' ? $this->discount_amount : null;
    }

    /**
     * Get amount_off attribute for compatibility (in cents)
     */
    public function getAmountOffAttribute(): ?int
    {
        return $this->discount_type === 'fixed' ? ($this->discount_amount * 100) : null;
    }

    /**
     * Scope for active codes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for valid codes
     */
    public function scopeValid($query)
    {
        $now = Carbon::now();
        return $query->active()
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $now);
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')
                    ->orWhereRaw('times_used < max_uses');
            });
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('times_used');
    }
}
