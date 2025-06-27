<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user_id,
                'name' => $this->whenLoaded('user', fn() => $this->user->name),
            ],
            'sport' => [
                'id' => $this->sport_id,
                'name' => $this->whenLoaded('sport', fn() => $this->sport->name),
            ],
            'game' => [
                'id' => $this->game_id,
                'teams' => $this->whenLoaded('game', fn() => [
                    'home' => $this->game->homeTeam?->name,
                    'away' => $this->game->awayTeam?->name,
                ]),
                'date' => $this->whenLoaded('game', fn() => $this->game->game_date),
            ],
            'operator' => [
                'id' => $this->operator_id,
                'name' => $this->whenLoaded('operator', fn() => $this->operator->name),
            ],
            'bet_type' => $this->bet_type,
            'selection' => $this->selection,
            'odds' => $this->odds,
            'stake' => $this->stake,
            'potential_return' => $this->potential_return,
            'status' => $this->status,
            'profit' => $this->profit,
            'winning_amount' => $this->winning_amount,
            'description' => $this->description,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'settled_at' => $this->settled_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            
            // Legacy fields for backward compatibility
            'sports' => $this->sports,
            'league' => $this->league,
            'betting_date' => $this->betting_date?->toDateString(),
            'matches' => $this->matches,
            'markets' => $this->markets,
            'team_one' => $this->team_one,
            'team_two' => $this->team_two,
            'tips' => $this->tips,
            'wager_odds' => $this->wager_odds,
            'membership' => $this->membership,
            'referrer' => $this->referrer,
            'roi' => $this->roi,
            'wager_amount' => $this->wager_amount,
            'profit_amount' => $this->profit_amount,
        ];
    }
}