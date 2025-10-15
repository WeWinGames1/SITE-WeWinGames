<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bet extends Model
{
    protected $fillable = [
        'user_id',
        'sport_id',
        'game_id',
        'sport',    // New column name
        'sports',   // Keep old column name for compatibility
        'league',
        'month',
        'game',     // New column
        'matches',
        'wager_type', // New column
        'markets',
        'wager_name', // New column
        'team_one',
        'team_one_id',
        'team_one_logo',
        'team_two',
        'team_two_id',
        'team_two_logo',
        'tips',
        'premium_notes',
        'premium_notes_enabled',
        'premium_notes_heading',
        'betting_date',
        'game_date',  // New column
        'odds',       // New column name
        'wager_odds', // Keep old column name for compatibility
        'membership',
        'level',
        'code',
        'roi',
        'roi_net',    // New column
        'wager_amount',
        'winning_amount',
        'profit_amount',
        'profits',    // New column
        'status',
        'referrer',
        'place_fraction',
        'is_parlay',
        'parlay_legs',
        'is_each_way',
        'each_way_stake',
        'place_payout',
        // New fields for position and dead heat calculations
        'finishing_position',
        'places_paid',
        'position_numeric',
        'is_dead_heat',
        'dead_heat_players',
        'dead_heat_spots',
        'bet_result_type',
        'place_terms_denominator',
        'golf_place',
        'golf_place_fraction',
        // MetaBet integration fields
        'metabet_query_id',
        'metabet_game_name',
        'metabet_linked_at',
    ];

    protected $casts = [
        'is_parlay' => 'boolean',
        'parlay_legs' => 'integer',
        'is_each_way' => 'boolean',
        'betting_date' => 'datetime',
        'game_date' => 'datetime',
        'premium_notes_enabled' => 'boolean',
        'each_way_stake' => 'decimal:2',
        'place_payout' => 'decimal:2',
        'place_fraction' => 'decimal:2',
        'places_paid' => 'integer',
        'position_numeric' => 'integer',
        'is_dead_heat' => 'boolean',
        'dead_heat_players' => 'integer',
        'dead_heat_spots' => 'decimal:4',
        'place_terms_denominator' => 'integer',
        'golf_place' => 'boolean',
        'golf_place_fraction' => 'decimal:4',
        'metabet_linked_at' => 'datetime',
    ];

    /**
     * Hide relationship objects from JSON serialization to prevent
     * displaying raw JSON data on frontend bet cards
     */
    protected $hidden = [
        'teamOne',
        'teamTwo',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function teamOne()
    {
        return $this->belongsTo(Team::class, 'team_one_id');
    }

    public function teamTwo()
    {
        return $this->belongsTo(Team::class, 'team_two_id');
    }

    // Parlay relationships
    public function betTeams()
    {
        return $this->hasMany(BetTeam::class)->ordered();
    }

    public function parlayTeams()
    {
        return $this->belongsToMany(Team::class, 'bet_teams')
            ->withPivot(['position', 'role', 'spread', 'line', 'team_name'])
            ->orderBy('bet_teams.position');
    }

    // Accessor for formatted sport name
    public function getSportsAttribute($value)
    {
        // Preserve 'UFC' and 'MMA' as uppercase
        if (in_array(strtoupper($value), ['UFC', 'MMA'])) {
            return strtoupper($value);
        }

        return ucfirst(strtolower($value));
    }

    // Helper methods for parlays
    public function addParlayTeam(Team $team, array $attributes = [])
    {
        $position = $this->betTeams()->max('position') ?? 0;

        return $this->betTeams()->create(array_merge([
            'team_id' => $team->id,
            'team_name' => $team->name,
            'position' => $position + 1,
            'role' => 'parlay',
        ], $attributes));
    }

    public function updateParlayInfo()
    {
        $legCount = $this->betTeams()->count();

        $this->update([
            'is_parlay' => $legCount > 2,
            'parlay_legs' => $legCount,
        ]);
    }

    /**
     * Get all teams involved in the bet (including parlay teams)
     */
    public function getAllTeams()
    {
        if ($this->is_parlay) {
            return $this->parlayTeams;
        }

        $teams = collect();

        if ($this->teamOne) {
            $teams->push($this->teamOne);
        }

        if ($this->teamTwo) {
            $teams->push($this->teamTwo);
        }

        return $teams;
    }

    /**
     * Check if this bet involves a specific team
     */
    public function involvesTeam($teamId)
    {
        if ($this->is_parlay) {
            return $this->parlayTeams()->where('teams.id', $teamId)->exists();
        }

        return $this->team_one_id == $teamId || $this->team_two_id == $teamId;
    }
}
