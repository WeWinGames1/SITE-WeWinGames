<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgebaseArticle extends Model
{
    protected $fillable = [
        'page_identifier',
        'title',
        'content',
        'sections',
        'screenshot_path',
        'order',
        'is_active',
        'type',
    ];

    protected $casts = [
        'sections' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPage($query, string $pageIdentifier)
    {
        return $query->where('page_identifier', $pageIdentifier);
    }

    public function scopeFrontend($query)
    {
        return $query->where('type', 'frontend');
    }

    public function scopeAdmin($query)
    {
        return $query->where('type', 'admin');
    }
}
