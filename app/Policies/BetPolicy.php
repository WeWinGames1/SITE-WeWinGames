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
     */
    public function view(User $user, Bet $bet): bool
    {
        $return = false;
        switch ($bet->membership) {
            case 'Bronze':
                $return = true;
                break;
            case 'Silver':
                $return = $user->subscribed('silver') || $user->subscribed('gold') || $user->subscribed('platinum') || $user->hasRole('admin');
                break;
            case 'Gold':
                $return = $user->subscribed('gold') || $user->subscribed('platinum') || $user->hasRole('admin');
                break;
            case 'Platinum':
                $return = $user->subscribed('platinum') || $user->hasRole('admin');
                break;

        }

        return $return;
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
