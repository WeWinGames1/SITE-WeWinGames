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
        'bet_type', // New column
        'markets',
        'wager_type',
        'wager_name', // New column
        'team_one',
        'team_one_logo',
        'team_two',
        'team_two_logo',
        'tips',
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

    // Accessor for formatted sport name
    public function getSportsAttribute($value)
    {
        // Preserve 'UFC' and 'MMA' as uppercase
        if (in_array(strtoupper($value), ['UFC', 'MMA'])) {
            return strtoupper($value);
        }

        return ucfirst(strtolower($value));
    }
}
