<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BetTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'bet_id',
        'team_id',
        'team_name',
        'position',
        'role',
        'spread',
        'line',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    /**
     * Get the bet that owns this team entry.
     */
    public function bet(): BelongsTo
    {
        return $this->belongsTo(Bet::class);
    }

    /**
     * Get the team.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the display name (team name or original name if team not found).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->team ? $this->team->name : ($this->team_name ?: 'Unknown Team');
    }

    /**
     * Scope to get teams in position order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}
