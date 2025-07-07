<?php

namespace App\Listeners;

use App\Events\NewBet;
use App\Services\UserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;

class NewBetNotification implements ShouldQueue
{
    private Collection $users;

    /**
     * Create the event listener.
     */
    public function __construct(UserService $user)
    {
        //
        $this->users = $user->getAllUsers();
    }

    /**
     * Handle the event.
     */
    public function handle(NewBet $event): void
    {
        //
        foreach ($this->users as $user) {
            if ($user->can('view', $event->bet)) {
                // Notify the user
                $user->notify(new \App\Notifications\NewBetPick($event->bet));
            }
        }
    }
}
