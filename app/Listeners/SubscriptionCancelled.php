<?php

namespace App\Listeners;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SubscriptionCancelled implements ShouldQueue
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
        if ($event->payload['type'] === 'customer.subscription.deleted') {
            $stripeId = $event->payload['data']['object']['customer'] ?? null;
            if (!$stripeId) {
                return;
            }
            $user = Cashier::findBillable($stripeId);
            // revoke the user's permissions
            $user->syncPermissions([]);
            $user->notify(new \App\Notifications\SubscriptionCancelled());
        }
    }
}
