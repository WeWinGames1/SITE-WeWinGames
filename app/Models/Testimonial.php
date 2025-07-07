<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = [
        'name',
        'title',
        'stars',
        'review',
        'image',
        'review_date',
        'published',
        'sort_order',
    ];

    protected $casts = [
        'review_date' => 'date',
        'published' => 'boolean',
        'stars' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the initials from the name
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';

        foreach ($words as $word) {
            if (! empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }

        return substr($initials, 0, 2); // Return maximum 2 characters
    }

    /**
     * Get formatted review date (Month Year)
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->review_date->format('F Y');
    }

    /**
     * Scope to get published testimonials
     */
    public function scopePublished($query)
    {
        return $query->where('published', true);
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
        return $query->published()
            ->ordered()
            ->select(['id', 'name', 'title', 'stars', 'review', 'image', 'review_date']);
    }
}
