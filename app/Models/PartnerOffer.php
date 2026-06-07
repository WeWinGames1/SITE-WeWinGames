<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'offer_text',
        'description',
        'external_url',
        'internal_url',
        'discount_code_id',
        'type',
        'badge_text',
        'show_on_picks',
        'show_on_offers_page',
        'sort_order',
        'is_active',
        'click_count',
    ];

    protected function casts(): array
    {
        return [
            'show_on_picks' => 'boolean',
            'show_on_offers_page' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'click_count' => 'integer',
        ];
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(PartnerOfferClick::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPicks($query)
    {
        return $query->active()->where('show_on_picks', true)->orderBy('sort_order');
    }

    public function scopeForOffersPage($query)
    {
        return $query->active()->where('show_on_offers_page', true)->orderBy('sort_order');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function getClickUrl(): string
    {
        if ($this->discount_code_id && $this->discountCode) {
            return route('quick-checkout', ['discountCode' => $this->discountCode->code]);
        }

        return $this->external_url;
    }

    public function incrementClickCount(): void
    {
        $this->increment('click_count');
    }
}
