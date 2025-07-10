<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class League extends Model
{
    /** @use HasFactory<\Database\Factories\LeagueFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'sport_id',
        'abbreviation',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($league) {
            if (empty($league->slug)) {
                $league->slug = Str::slug($league->name);
            }
        });

        static::updating(function ($league) {
            if ($league->isDirty('name') && empty($league->slug)) {
                $league->slug = Str::slug($league->name);
            }
        });
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }
}
