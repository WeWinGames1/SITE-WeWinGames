<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StripeProduct;
use App\Models\DiscountCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Cashier;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    /**
     * Show the custom checkout page
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'subscription_name' => 'required|string|in:silver,gold,platinum',
            'subscription_price_id' => 'required|string',
        ]);
        
        $user = $request->user();
        
        // Get the product details
        $product = StripeProduct::where('stripe_price_id', $request->subscription_price_id)
            ->where('is_active', true)
            ->firstOrFail();
        
        // Override price with correct pricing
        $prices = [
            'silver' => ['monthly' => 45, 'weekly' => 17, 'daily' => 5],
            'gold' => ['monthly' => 65, 'weekly' => 29, 'daily' => 8],
            'platinum' => ['monthly' => 80, 'weekly' => 49, 'daily' => 12],
        ];
        
        $tier = strtolower($product->tier);
        $period = $product->billing_period;
        $correctPrice = $prices[$tier][$period] ?? $product->price;
        
        $plan = [
            'name' => ucfirst($product->tier),
            'price' => '$' . number_format($correctPrice, 0),
            'period' => $product->billing_period,
            'priceId' => $product->stripe_price_id,
        ];
        
        // Get user's payment methods
        $paymentMethods = [];
        if ($user->hasStripeId()) {
            try {
                $stripe = new \Stripe\StripeClient(config('cashier.secret'));
                $stripeMethods = $stripe->paymentMethods->all([
                    'customer' => $user->stripe_id,
                    'type' => 'card',
                    'limit' => 100
                ]);
                
                foreach ($stripeMethods->data as $pm) {
                    $paymentMethods[] = [
                        'id' => $pm->id,
                        'brand' => ucfirst($pm->card->brand),
                        'last4' => $pm->card->last4,
                        'exp_month' => $pm->card->exp_month,
                        'exp_year' => $pm->card->exp_year,
                        'is_default' => $pm->id === optional($user->defaultPaymentMethod())->id,
                    ];
                }
            } catch (\Exception $e) {
                \Log::error('Failed to fetch payment methods: ' . $e->getMessage());
            }
        }
        
        // Get active discount codes
        $discountCodes = DiscountCode::active()->get();
        
        return Inertia::render('Checkout', [
            'plan' => $plan,
            'stripeKey' => config('cashier.key'),
            'discountCodes' => $discountCodes,
            'paymentMethods' => $paymentMethods,
            'hasStripeId' => $user->hasStripeId(),
        ]);
    }
    
    /**
     * Process the subscription payment
     */
    public function process(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'coupon' => 'nullable|string',
        ]);
        
        $user = Auth::user();
        
        try {
            // Get price ID from session or request
            $priceId = session('checkout_price_id', $request->price_id);
            
            // Create or get the subscription
            $subscription = $user->newSubscription('default', $priceId);
            
            // Apply coupon if provided
            if ($request->filled('coupon')) {
                if ($request->coupon === 'PLATINUM10FIRST') {
                    // For the special Platinum first month discount
                    // Create a one-time coupon in Stripe
                    $stripe = new \Stripe\StripeClient(config('cashier.secret'));
                    $coupon = $stripe->coupons->create([
                        'amount_off' => 7000, // $70 off in cents
                        'currency' => 'usd',
                        'duration' => 'once',
                        'name' => 'Platinum First Month - $10',
                    ]);
                    
                    $subscription->withCoupon($coupon->id);
                } else {
                    // Check database for other discount codes
                    $discountCode = DiscountCode::where('code', $request->coupon)
                        ->active()
                        ->first();
                        
                    if ($discountCode) {
                        $subscription->withCoupon($discountCode->stripe_coupon_id);
                        
                        // Track coupon usage
                        $discountCode->couponUsages()->create([
                            'user_id' => $user->id,
                            'subscription_id' => null, // Will be updated after subscription is created
                        ]);
                    }
                }
            }
            
            // Create the subscription with the payment method
            $subscription->create($request->payment_method);
            
            return redirect()->route('dashboard')->with('success', 'Subscription activated successfully!');
            
        } catch (\Exception $e) {
            Log::error('Subscription creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['payment' => 'Payment failed. Please try again.']);
        }
    }
    
    /**
     * Validate a discount code via AJAX
     */
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);
        
        // Special handling for PLATINUM10FIRST code
        if ($request->code === 'PLATINUM10FIRST') {
            // Check if this is a new customer (no previous subscriptions)
            $user = $request->user();
            if ($user->subscriptions()->count() > 0) {
                return response()->json(['valid' => false, 'message' => 'This code is only for first-time subscribers']);
            }
            
            return response()->json([
                'valid' => true,
                'discount' => [
                    'percent_off' => null,
                    'amount_off' => 7000, // $70 off (making it $10 for first month)
                ],
            ]);
        }
        
        // Check database for other discount codes
        $discountCode = DiscountCode::where('code', $request->code)
            ->active()
            ->first();
            
        if (!$discountCode) {
            return response()->json(['valid' => false]);
        }
        
        // Check usage limits
        if ($discountCode->hasReachedLimit()) {
            return response()->json(['valid' => false]);
        }
        
        // Check per-customer limit
        if ($discountCode->hasReachedCustomerLimit($request->user())) {
            return response()->json(['valid' => false]);
        }
        
        return response()->json([
            'valid' => true,
            'discount' => [
                'percent_off' => $discountCode->percent_off,
                'amount_off' => $discountCode->amount_off,
            ],
        ]);
    }
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