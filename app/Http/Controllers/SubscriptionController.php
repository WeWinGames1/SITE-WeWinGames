<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\DiscountCode;
use App\Models\StripeProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\AuthenticationException;

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

        // Use the actual price from the database
        $correctPrice = $product->price;

        $plan = [
            'name' => ucfirst($product->tier),
            'price' => '$'.number_format($correctPrice, 0),
            'period' => $product->billing_period,
            'priceId' => $product->stripe_price_id,
            'productId' => $product->stripe_product_id,
        ];

        // Get user's payment methods
        $paymentMethods = [];
        if ($user->hasStripeId()) {
            try {
                $stripe = new \Stripe\StripeClient(config('cashier.secret'));
                // Fetch ALL payment methods (including Link)
                $stripeMethods = $stripe->paymentMethods->all([
                    'customer' => $user->stripe_id,
                    'limit' => 100,
                ]);

                foreach ($stripeMethods->data as $pm) {
                    if ($pm->type === 'card') {
                        $paymentMethods[] = [
                            'id' => $pm->id,
                            'brand' => ucfirst($pm->card->brand),
                            'last4' => $pm->card->last4,
                            'exp_month' => $pm->card->exp_month,
                            'exp_year' => $pm->card->exp_year,
                            'is_default' => $pm->id === optional($user->defaultPaymentMethod())->id,
                        ];
                    } elseif ($pm->type === 'link') {
                        // Link payment methods
                        $displayInfo = 'Link';

                        if (isset($pm->link->email)) {
                            $email = $pm->link->email;
                            $parts = explode('@', $email);
                            if (count($parts) == 2) {
                                $username = $parts[0];
                                $domain = $parts[1];
                                $maskedUsername = substr($username, 0, 2).'***';
                                $displayInfo = $maskedUsername.'@'.$domain;
                            }
                        }

                        $paymentMethods[] = [
                            'id' => $pm->id,
                            'brand' => 'Stripe Link',
                            'last4' => $displayInfo,
                            'exp_month' => null,
                            'exp_year' => null,
                            'is_default' => $pm->id === optional($user->defaultPaymentMethod())->id,
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Failed to fetch payment methods: '.$e->getMessage());
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
            // Get price ID from request (it should be passed from the checkout form)
            $priceId = $request->price_id;

            // Create or get the subscription
            $subscription = $user->newSubscription('default', $priceId);

            // Add affiliate code to metadata if available
            $affiliateCode = Cookie::get('affiliate_code');
            if ($affiliateCode && ! $user->affiliate_id) {
                $affiliate = Affiliate::where('code', $affiliateCode)
                    ->where('is_active', true)
                    ->first();

                if ($affiliate) {
                    $subscription->withMetadata([
                        'affiliate_code' => $affiliateCode,
                    ]);
                }
            }

            // Apply coupon if provided
            if ($request->filled('coupon')) {

                // Check database for other discount codes
                $discountCode = DiscountCode::where('code', $request->coupon)
                    ->active()
                    ->first();

                // Get product ID from price to check restrictions
                $stripeProductId = null;
                $stripeProduct = null;
                if ($priceId) {
                    $stripeProduct = StripeProduct::where('stripe_price_id', $priceId)->first();
                    if ($stripeProduct) {
                        $stripeProductId = $stripeProduct->stripe_product_id;
                    }
                }

                if ($discountCode && $discountCode->isValid() && $discountCode->canBeUsedBy($user)) {
                    // Check product restrictions
                    if ($stripeProductId && ! $discountCode->appliesToProduct($stripeProductId)) {
                        return back()->withErrors(['coupon' => 'This discount code does not apply to the selected product.']);
                    }
                    // If no Stripe coupon ID exists, create one
                    if (! $discountCode->stripe_coupon_id) {
                        $stripe = new \Stripe\StripeClient(config('cashier.secret'));
                        $couponData = [
                            'currency' => 'usd',
                        ];

                        // Set duration based on apply_to field
                        if ($discountCode->apply_to === 'first_payment') {
                            $couponData['duration'] = 'once';
                        } elseif ($discountCode->apply_to === 'forever') {
                            $couponData['duration'] = 'forever';
                        } else {
                            $couponData['duration'] = 'repeating';
                            $couponData['duration_in_months'] = $discountCode->months_count;
                        }

                        if ($discountCode->discount_type === 'percentage') {
                            $couponData['percent_off'] = $discountCode->discount_amount;
                        } else {
                            $couponData['amount_off'] = $discountCode->discount_amount * 100; // Convert to cents
                        }

                        $stripeCoupon = $stripe->coupons->create($couponData);
                        $discountCode->update(['stripe_coupon_id' => $stripeCoupon->id]);
                    }

                    $subscription->withCoupon($discountCode->stripe_coupon_id);

                    // Calculate the discount amount to store
                    $discountAmount = 0;
                    if ($stripeProduct) {
                        $basePrice = $stripeProduct->price;
                        if ($discountCode->discount_type === 'percentage') {
                            $discountAmount = $basePrice * ($discountCode->discount_amount / 100);
                        } else {
                            $discountAmount = $discountCode->discount_amount;
                        }
                    } else {
                        // If we can't find the product, log an error but don't fail the subscription
                        Log::warning('Could not find StripeProduct for price_id when calculating discount', [
                            'price_id' => $priceId,
                            'discount_code' => $discountCode->code,
                            'user_id' => $user->id
                        ]);
                        // Set a default discount amount based on the discount type
                        // This ensures the redemption record can be created
                        if ($discountCode->discount_type === 'fixed') {
                            $discountAmount = $discountCode->discount_amount;
                        } else {
                            // For percentage discounts, we'll store 0 since we don't know the base price
                            $discountAmount = 0;
                        }
                    }

                    // Track coupon usage
                    $discountCode->redemptions()->create([
                        'user_id' => $user->id,
                        'subscription_id' => null, // Will be updated after subscription is created
                        'discount_applied' => $discountAmount,
                    ]);

                    // Increment usage count
                    $discountCode->incrementUsage();
                }

            }

            // Create the subscription with the payment method
            $createdSubscription = $subscription->create($request->payment_method);

            // Check if subscription requires additional action (3D Secure)
            if ($createdSubscription->hasIncompletePayment()) {
                // Get the latest invoice's payment intent
                $latestInvoice = $createdSubscription->latestInvoice();
                
                if ($latestInvoice && $latestInvoice->payment_intent) {
                    // Return the client secret for 3D Secure authentication
                    return back()->with([
                        'requires_action' => true,
                        'payment_intent_client_secret' => $latestInvoice->payment_intent->client_secret,
                        'subscription_id' => $createdSubscription->id,
                    ]);
                }
            }

            return redirect()->route('dashboard')->with('success', 'Subscription activated successfully!');

        } catch (\Stripe\Exception\CardException $e) {
            // Card was declined or 3D Secure failed
            Log::error('Card payment failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'decline_code' => $e->getDeclineCode(),
                'stripe_error' => $e->getStripeCode(),
            ]);

            $errorMessage = match($e->getStripeCode()) {
                'authentication_required' => 'Your card requires additional authentication. Please try again and complete the verification process.',
                'card_declined' => 'Your card was declined. Please try a different card.',
                'insufficient_funds' => 'Your card has insufficient funds. Please try a different card.',
                'expired_card' => 'Your card has expired. Please use a different card.',
                'incorrect_cvc' => 'The CVC code is incorrect. Please check and try again.',
                'processing_error' => 'An error occurred while processing your card. Please try again.',
                default => 'Payment failed: ' . $e->getMessage(),
            };

            return back()->withErrors(['payment' => $errorMessage]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Invalid parameters were supplied to Stripe's API
            Log::error('Invalid Stripe request', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['payment' => 'Invalid payment request. Please contact support.']);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            // Authentication with Stripe's API failed
            Log::error('Stripe authentication failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['payment' => 'Payment system error. Please try again later.']);
        } catch (\Exception $e) {
            // Generic error handling
            Log::error('Subscription creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'type' => get_class($e),
            ]);

            return back()->withErrors(['payment' => 'An unexpected error occurred. Please try again or contact support.']);
        }
    }

    /**
     * Validate a discount code via AJAX
     */
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'product_id' => 'nullable|string', // Add product ID for validation
        ]);

        $user = $request->user();

        // Check database for other discount codes
        $discountCode = DiscountCode::where('code', $request->code)
            ->active()
            ->first();

        if (! $discountCode) {
            return response()->json(['valid' => false]);
        }

        // Check if code is valid
        if (! $discountCode->isValid()) {
            return response()->json(['valid' => false, 'message' => 'This discount code is no longer valid']);
        }

        // Check if user can use this code
        if (! $discountCode->canBeUsedBy($user)) {
            return response()->json(['valid' => false, 'message' => 'You have already used this discount code']);
        }

        // Check product restrictions if product ID is provided
        if ($request->filled('product_id') && ! $discountCode->appliesToProduct($request->product_id)) {
            return response()->json(['valid' => false, 'message' => 'This discount code does not apply to the selected product']);
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
            if (! $user->hasPaymentMethod()) {
                return redirect()->route('subscription.checkout', [
                    'subscription_name' => $validated['subscription_name'],
                    'subscription_price_id' => $priceId,
                ])->with('warning', 'Please add a payment method to switch plans.');
            }

            // Get current subscription
            $subscription = $user->subscription('default');

            if (! $subscription) {
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

        if (! $subscription) {
            return redirect()->route('billing.edit')
                ->with('error', 'No active subscription found.');
        }

        try {
            // Cancel at period end (graceful cancellation)
            $subscription->cancel();

            return redirect()->route('billing.edit')
                ->with('success', 'Your subscription has been cancelled. You will continue to have access until '.
                    $subscription->ends_at->format('F j, Y').'.');

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

        if (! $subscription || ! $subscription->onGracePeriod()) {
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
