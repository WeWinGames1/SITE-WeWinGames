<?php

namespace App\Listeners;

use App\Jobs\SyncDiscordRolesJob;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

class SyncDiscordRolesOnSubscriptionChange
{
    public const SYNC_DELAY_SECONDS = 30;

    /**
     * Stripe events that should trigger a Discord role sync.
     * These events can affect subscription status and therefore Discord access.
     */
    private array $relevantEvents = [
        // Subscription lifecycle events
        'customer.subscription.created',
        'customer.subscription.updated',
        'customer.subscription.deleted',
        'customer.subscription.paused',
        'customer.subscription.resumed',

        // Payment events that affect subscription status
        'invoice.payment_failed',
        'invoice.payment_succeeded',

        // Customer events
        'customer.deleted',
    ];

    /**
     * Handle the event.
     */
    public function handle(WebhookReceived $event): void
    {
        $payload = $event->payload;
        $type = $payload['type'] ?? '';

        if (! in_array($type, $this->relevantEvents)) {
            return;
        }

        // Get the Stripe customer ID from the appropriate location based on event type
        $stripeCustomerId = $this->extractCustomerId($payload, $type);

        if (! $stripeCustomerId) {
            Log::debug('No Stripe customer ID found in webhook payload', [
                'event_type' => $type,
            ]);

            return;
        }

        // Find the user by Stripe customer ID
        $user = User::where('stripe_id', $stripeCustomerId)->first();

        if (! $user) {
            Log::debug('No user found for Stripe customer', [
                'stripe_customer_id' => $stripeCustomerId,
                'event_type' => $type,
            ]);

            return;
        }

        // Only sync if user has Discord connected
        if (! $user->discord_id) {
            Log::debug('User has no Discord connected, skipping role sync', [
                'user_id' => $user->id,
                'event_type' => $type,
            ]);

            return;
        }

        // Log the event that triggered the sync
        $this->logSubscriptionEvent($user, $type, $payload);

        // WebhookReceived fires before Cashier writes the subscription change to the
        // database, so the job is delayed until well after the webhook has been handled
        SyncDiscordRolesJob::dispatch($user)->delay(now()->addSeconds(self::SYNC_DELAY_SECONDS));

        Log::info('Dispatched Discord role sync job', [
            'user_id' => $user->id,
            'event_type' => $type,
        ]);
    }

    /**
     * Extract the Stripe customer ID from the webhook payload
     */
    private function extractCustomerId(array $payload, string $type): ?string
    {
        $object = $payload['data']['object'] ?? [];

        // For customer events, the customer ID is the object ID itself
        if (str_starts_with($type, 'customer.') && ! str_contains($type, 'subscription')) {
            return $object['id'] ?? null;
        }

        // For invoice events
        if (str_starts_with($type, 'invoice.')) {
            return $object['customer'] ?? null;
        }

        // For subscription events
        return $object['customer'] ?? null;
    }

    /**
     * Log significant subscription events for audit purposes
     */
    private function logSubscriptionEvent(User $user, string $type, array $payload): void
    {
        $significantEvents = [
            'customer.subscription.deleted' => 'Subscription cancelled - removing Discord roles',
            'customer.subscription.paused' => 'Subscription paused - removing Discord roles',
            'invoice.payment_failed' => 'Payment failed - may affect Discord access',
            'customer.deleted' => 'Customer deleted - removing Discord roles',
        ];

        if (isset($significantEvents[$type])) {
            Log::warning($significantEvents[$type], [
                'user_id' => $user->id,
                'email' => $user->email,
                'discord_id' => $user->discord_id,
                'event_type' => $type,
            ]);

            // Log to activity log if available
            if (function_exists('activity')) {
                activity()
                    ->performedOn($user)
                    ->withProperties([
                        'event_type' => $type,
                        'discord_id' => $user->discord_id,
                    ])
                    ->log("Discord role sync triggered: {$significantEvents[$type]}");
            }
        }
    }
}
