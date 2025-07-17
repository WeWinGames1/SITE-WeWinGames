<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SportPreference extends Model
{
    protected $fillable = [
        'sport_name',
        'priority',
        'is_active'
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean'
    ];

    /**
     * Scope to get active sports ordered by priority
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('priority', 'asc');
    }
}
