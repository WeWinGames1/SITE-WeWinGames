<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Sport extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sport) {
            if (empty($sport->slug)) {
                $sport->slug = Str::slug($sport->name);
            }
        });

        static::updating(function ($sport) {
            if ($sport->isDirty('name') && empty($sport->slug)) {
                $sport->slug = Str::slug($sport->name);
            }
        });
    }

    public function leagues()
    {
        return $this->hasMany(League::class);
    }

    public function teams()
    {
        return $this->hasManyThrough(Team::class, League::class);
    }
}
