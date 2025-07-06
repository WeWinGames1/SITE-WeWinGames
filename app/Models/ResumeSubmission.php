<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResumeSubmission extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'position',
        'about',
        'ip_address',
        'user_agent',
        'status',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the full name of the applicant
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Scope for new submissions
     */
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    /**
     * Scope for reviewed submissions
     */
    public function scopeReviewed($query)
    {
        return $query->where('status', 'reviewed');
    }
}
