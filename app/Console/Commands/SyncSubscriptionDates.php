<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Laravel\Cashier\Subscription;

class SyncSubscriptionDates extends Command
{
    protected $signature = 'subscriptions:sync-dates';
    protected $description = 'Sync subscription period dates from Stripe';

    public function handle()
    {
        $this->info('Syncing subscription dates from Stripe...');
        
        $stripe = new \Stripe\StripeClient(config('cashier.secret'));
        
        // Get all active subscriptions
        $subscriptions = Subscription::where('stripe_status', 'active')->get();
        
        foreach ($subscriptions as $subscription) {
            try {
                // Fetch subscription from Stripe
                $stripeSubscription = $stripe->subscriptions->retrieve($subscription->stripe_id);
                
                // Update period dates
                $subscription->current_period_start = $stripeSubscription->current_period_start ? 
                    date('Y-m-d H:i:s', $stripeSubscription->current_period_start) : null;
                $subscription->current_period_end = $stripeSubscription->current_period_end ? 
                    date('Y-m-d H:i:s', $stripeSubscription->current_period_end) : null;
                
                $subscription->save();
                
                $this->info("Updated subscription {$subscription->id} for user {$subscription->user_id}");
            } catch (\Exception $e) {
                $this->error("Failed to update subscription {$subscription->id}: " . $e->getMessage());
            }
        }
        
        $this->info('Sync complete!');
    }
}