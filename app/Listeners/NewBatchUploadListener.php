<?php

namespace App\Listeners;

use App\Events\NewBatchUpload;
use App\Services\UserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\InteractsWithQueue;

class NewBatchUploadListener implements ShouldQueue
{
    public Collection $users;
    /**
     * Create the event listener.
     */
    public function __construct(UserService $userService)
    {
        //
        $this->users = $userService->getAllUsers();
    }

    /**
     * Handle the event.
     */
    public function handle(NewBatchUpload $event): void
    {
        //
        foreach ($this->users as $user) {
          
                // Notify the user
            $user->notify(new \App\Notifications\NewBatchUploadNotification($event->bets));
            
        }
    }
}
