<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bet extends Model
{
    protected $fillable = [
        'sports',
        'league',
        'matches',
        'markets',
        'team_one',
        'team_one_logo',
        'team_two',
        'team_two_logo',
        'tips',
        'betting_date',
        'wager_odds',
        'membership',
        'roi',
        'wager_amount',
        'winning_amount',
        'profit_amount',
        'status',
        'referrer', 
        'place_fraction',
    ];

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
