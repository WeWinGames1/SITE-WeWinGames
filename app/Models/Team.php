<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'sport_id',
        'league_id',
        'logo_url',
        'abbreviation',
        'city',
        'state',
        'country',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * The sport this team belongs to.
     */
    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    /**
     * The league this team belongs to.
     */
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    /**
     * The aliases for this team.
     */
    public function aliases(): HasMany
    {
        return $this->hasMany(TeamAlias::class);
    }

    /**
     * Games where this team is the home team.
     */
    public function homeGames(): HasMany
    {
        return $this->hasMany(Game::class, 'team_one_id');
    }

    /**
     * Games where this team is the away team.
     */
    public function awayGames(): HasMany
    {
        return $this->hasMany(Game::class, 'team_two_id');
    }

    /**
     * Bets where this team is team one.
     */
    public function betsAsTeamOne(): HasMany
    {
        return $this->hasMany(Bet::class, 'team_one_id');
    }

    /**
     * Bets where this team is team two.
     */
    public function betsAsTeamTwo(): HasMany
    {
        return $this->hasMany(Bet::class, 'team_two_id');
    }

    /**
     * Boot method to auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($team) {
            if (empty($team->slug)) {
                // Generate a unique slug
                $baseSlug = Str::slug($team->name);
                $slug = $baseSlug;
                $counter = 1;

                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$counter;
                    $counter++;
                }

                $team->slug = $slug;
            }
        });

        static::updating(function ($team) {
            if ($team->isDirty('name') && empty($team->slug)) {
                // Generate a unique slug
                $baseSlug = Str::slug($team->name);
                $slug = $baseSlug;
                $counter = 1;

                // Exclude current team from check
                while (static::where('slug', $slug)->where('id', '!=', $team->id)->exists()) {
                    $slug = $baseSlug.'-'.$counter;
                    $counter++;
                }

                $team->slug = $slug;
            }
        });

        // Clear cache when teams are updated or deleted
        static::updated(function ($team) {
            // Clear all team lookup cache entries
            Cache::flush(); // Note: In production, use Redis with tags
        });

        static::deleted(function ($team) {
            // Clear all team lookup cache entries
            Cache::flush(); // Note: In production, use Redis with tags
        });
    }

    /**
     * Find a team by name or alias within a specific sport and league (with caching)
     */
    public static function findByNameOrAlias($name, $sportId = null, $leagueId = null)
    {
        // Create a unique cache key
        $cacheKey = 'team_lookup_'.md5(strtolower($name).'_'.$sportId.'_'.$leagueId);

        // Cache for 1 hour
        return Cache::remember($cacheKey, 3600, function () use ($name, $sportId, $leagueId) {
            $query = static::query();

            if ($sportId) {
                $query->where('sport_id', $sportId);
            }

            if ($leagueId) {
                $query->where('league_id', $leagueId);
            }

            // First try exact name match
            $team = $query->where('name', $name)->first();

            if ($team) {
                return $team;
            }

            // Then try alias match
            $alias = TeamAlias::where('alias', $name)->first();

            if ($alias) {
                $team = $alias->team;

                // Verify sport and league if specified
                if ($sportId && $team->sport_id != $sportId) {
                    return null;
                }

                if ($leagueId && $team->league_id != $leagueId) {
                    return null;
                }

                return $team;
            }

            return null;
        });
    }

    /**
     * Get all names including aliases
     */
    public function getAllNames()
    {
        $names = [$this->name];

        foreach ($this->aliases as $alias) {
            $names[] = $alias->alias;
        }

        return $names;
    }
}
