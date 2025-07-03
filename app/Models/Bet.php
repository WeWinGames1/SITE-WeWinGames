<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bet extends Model
{
    protected $fillable = [
        'sports',
        'league',
        'month',
        'matches',
        'markets',
        'wager_type',
        'team_one',
        'team_one_logo',
        'team_two',
        'team_two_logo',
        'tips',
        'betting_date',
        'wager_odds',
        'membership',
        'level',
        'code',
        'roi',
        'wager_amount',
        'winning_amount',
        'profit_amount',
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
