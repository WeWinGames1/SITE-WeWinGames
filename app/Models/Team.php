<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'sport_id',
        'logo_url',
        'abbreviation',
        'city',
        'state',
        'country',
        'is_active',
    ];

    /**
     * The sport this team belongs to.
     */
    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
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
}
