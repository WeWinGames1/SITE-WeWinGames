<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamAlias extends Model
{
    protected $fillable = [
        'team_id',
        'alias',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
