<?php

namespace App\Policies;

use App\Models\Bet;
use App\Models\User;

class BetPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-bets') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     *
     * Tier access levels:
     * - Free: Any authenticated user can view (no subscription required)
     * - Gold: Requires Gold or Platinum subscription
     * - Platinum: Requires Platinum subscription only
     */
    public function view(User $user, Bet $bet): bool
    {
        // Admins can always view all bets
        if ($user->hasRole('admin')) {
            return true;
        }

        switch ($bet->membership) {
            case 'Free':
            case 'Bronze':  // Legacy support - treat as Free
            case 'Silver':  // Legacy support - treat as Free
                return true;
            case 'Gold':
                return $user->subscribed('gold') || $user->subscribed('platinum');
            case 'Platinum':
                return $user->subscribed('platinum');
            default:
                return false;
        }
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create-bets') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Bet $bet): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Bet $bet): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Bet $bet): bool
    {
        return $user->can('create-bets') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Bet $bet): bool
    {
        return $user->can('create-bets') || $user->hasRole('admin');
    }
}
