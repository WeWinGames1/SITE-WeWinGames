<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class BlogCategory extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'order_column',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    /**
     * Scope for active categories.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope for ordered categories.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order_column')->orderBy('name');
    }
    
    /**
     * Get posts for this category.
     */
    public function posts()
    {
        return $this->hasMany(Post::class, 'category', 'slug');
    }
    
    /**
     * Get posts count for this category.
     */
    public function getPostsCountAttribute(): int
    {
        return $this->posts()->count();
    }
    
    protected $appends = ['posts_count'];
}
