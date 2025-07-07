<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;

class SubscriptionCreated implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle received Stripe webhooks.
     */
    public function handle(WebhookReceived $event): void
    {
        if ($event->payload['type'] === 'customer.subscription.created') {
            $stripeId = $event->payload['data']['object']['customer'] ?? null;
            if (! $stripeId) {
                return;
            }
            $user = Cashier::findBillable($stripeId);
            // revoke the user's permissions
            $user->syncPermissions(['view-bets']);
            $user->notify(new \App\Notifications\SubscriptionStarted);
        }
    }
}
