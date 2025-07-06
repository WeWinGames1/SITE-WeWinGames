<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = [
        'question',
        'answer',
        'category',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    /**
     * Scope to get active FAQs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('created_at', 'desc');
    }
    
    /**
     * Scope for frontend display
     */
    public function scopeForDisplay($query)
    {
        return $query->active()
                    ->ordered()
                    ->select(['id', 'question', 'answer', 'category']);
    }

    /**
     * Get FAQs grouped by category
     */
    public static function getGroupedByCategory()
    {
        return self::forDisplay()
                   ->get()
                   ->groupBy('category')
                   ->map(function ($faqs) {
                       return $faqs->values();
                   });
    }

    /**
     * Get unique categories
     */
    public static function getCategories()
    {
        return self::whereNotNull('category')
                   ->distinct()
                   ->pluck('category')
                   ->sort()
                   ->values();
    }
}
