<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    /** @use HasFactory<\Database\Factories\GameFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'game_date',
        'sport_id',
        'operator_id',
        'game_name',
        'props',
        'line',
        'wager_team',
        'post_availablity',
        'odds',
        'type',
        'subsection',
        'team1',
        'team2',
        'team1_img',
        'team2_img',
    ];

    protected $casts = [
        'game_date' => 'datetime',
    ];

    /**
     * The sport this game belongs to.
     */
    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    /**
     * The operator for this game.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    /**
     * Bets placed on this game.
     */
    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class);
    }

    /**
     * Get the home team name (team1).
     */
    public function getHomeTeamAttribute()
    {
        return $this->team1;
    }

    /**
     * Get the away team name (team2).
     */
    public function getAwayTeamAttribute()
    {
        return $this->team2;
    }

    /**
     * Accessor for homeTeam to maintain compatibility.
     */
    public function homeTeam()
    {
        return (object) ['name' => $this->team1, 'img' => $this->team1_img];
    }

    /**
     * Accessor for awayTeam to maintain compatibility.
     */
    public function awayTeam()
    {
        return (object) ['name' => $this->team2, 'img' => $this->team2_img];
    }
}
