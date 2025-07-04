<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;

class SubscriptionController extends Controller
{
    /**
     * Handle subscription plan switching with proration
     */
    public function switchPlan(Request $request)
    {
        $validated = $request->validate([
            'price_id' => 'required|string',
            'subscription_name' => 'required|string|in:silver,gold,platinum',
        ]);
        
        $user = $request->user();
        $priceId = $validated['price_id'];
        
        try {
            // Check if user has a payment method
            if (!$user->hasPaymentMethod()) {
                return redirect()->route('subscription.checkout', [
                    'subscription_name' => $validated['subscription_name'],
                    'subscription_price_id' => $priceId,
                ])->with('warning', 'Please add a payment method to switch plans.');
            }
            
            // Get current subscription
            $subscription = $user->subscription('default');
            
            if (!$subscription) {
                // No current subscription, create new one
                $user->newSubscription('default', $priceId)->create();
                
                return redirect()->route('billing.edit')
                    ->with('success', 'Successfully subscribed to the new plan!');
            }
            
            // Switch to new plan with proration
            // Stripe automatically handles proration when swapping plans
            $subscription->swap($priceId);
            
            // Get the new plan details for the success message
            $stripePrices = config('stripe.price_to_tier', []);
            $planDetails = $stripePrices[$priceId] ?? ['tier' => 'new', 'period' => ''];
            
            return redirect()->route('billing.edit')
                ->with('success', "Successfully switched to {$planDetails['tier']} {$planDetails['period']} plan! Your billing has been prorated automatically.");
                
        } catch (\Exception $e) {
            Log::error('Subscription switch failed', [
                'user_id' => $user->id,
                'price_id' => $priceId,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->route('billing.edit')
                ->with('error', 'Failed to switch plans. Please try again or contact support.');
        }
    }
    
    /**
     * Cancel subscription
     */
    public function cancel(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription('default');
        
        if (!$subscription) {
            return redirect()->route('billing.edit')
                ->with('error', 'No active subscription found.');
        }
        
        try {
            // Cancel at period end (graceful cancellation)
            $subscription->cancel();
            
            return redirect()->route('billing.edit')
                ->with('success', 'Your subscription has been cancelled. You will continue to have access until ' . 
                    $subscription->ends_at->format('F j, Y') . '.');
                    
        } catch (\Exception $e) {
            Log::error('Subscription cancellation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->route('billing.edit')
                ->with('error', 'Failed to cancel subscription. Please try again or contact support.');
        }
    }
    
    /**
     * Resume a cancelled subscription
     */
    public function resume(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription('default');
        
        if (!$subscription || !$subscription->onGracePeriod()) {
            return redirect()->route('billing.edit')
                ->with('error', 'No cancelled subscription found to resume.');
        }
        
        try {
            $subscription->resume();
            
            return redirect()->route('billing.edit')
                ->with('success', 'Your subscription has been resumed successfully!');
                
        } catch (\Exception $e) {
            Log::error('Subscription resume failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->route('billing.edit')
                ->with('error', 'Failed to resume subscription. Please try again or contact support.');
        }
    }
}