<?php

namespace App\Listeners;

use App\Models\Affiliate;
use App\Services\SendGridService;
use Laravel\Cashier\Events\WebhookReceived;

class BindAffiliateOnSubscription
{
    protected SendGridService $sendGridService;

    /**
     * Create the event listener.
     */
    public function __construct(SendGridService $sendGridService)
    {
        $this->sendGridService = $sendGridService;
    }

    /**
     * Handle the event.
     */
    public function handle(WebhookReceived $event): void
    {
        // Handle subscription created or updated
        if (! in_array($event->payload['type'], ['customer.subscription.created', 'customer.subscription.updated'])) {
            return;
        }

        $stripeCustomerId = $event->payload['data']['object']['customer'] ?? null;
        if (! $stripeCustomerId) {
            return;
        }

        // Find user by Stripe customer ID
        $user = \App\Models\User::where('stripe_id', $stripeCustomerId)->first();
        if (! $user) {
            return;
        }

        // Check if user already has an affiliate
        if ($user->affiliate_id) {
            return;
        }

        // Check for affiliate code in Stripe metadata
        $metadata = $event->payload['data']['object']['metadata'] ?? [];
        $affiliateCode = $metadata['affiliate_code'] ?? null;

        if (! $affiliateCode) {
            return;
        }

        // Find affiliate
        $affiliate = Affiliate::where('code', $affiliateCode)
            ->where('is_active', true)
            ->first();

        if (! $affiliate) {
            return;
        }

        // Get subscription plan details
        $plan = $event->payload['data']['object']['items']['data'][0]['price']['nickname'] ?? 'Unknown Plan';

        // Bind user to affiliate
        $user->bindToAffiliate($affiliate, $plan);

        // Update SendGrid contact
        try {
            $this->sendGridService->updateContactSubscription($user);
        } catch (\Exception $e) {
            \Log::error('Failed to update SendGrid contact after affiliate binding: '.$e->getMessage());
        }
    }
}
