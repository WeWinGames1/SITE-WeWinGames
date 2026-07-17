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

        // Clean up orphaned redemptions from previous failed attempts
        $this->cleanupOrphanedRedemptions($user);

        // Attribute this purchase to the most recent X ad click, if any, so the
        // server-side Conversion API event can include the twclid.
        if ($request->cookie('twclid')) {
            $user->forceFill([
                'twclid' => $request->cookie('twclid'),
                'utm_source' => $request->cookie('utm_source') ?: $user->utm_source,
                'utm_medium' => $request->cookie('utm_medium') ?: $user->utm_medium,
                'utm_campaign' => $request->cookie('utm_campaign') ?: $user->utm_campaign,
                'utm_content' => $request->cookie('utm_content') ?: $user->utm_content,
                'landing_url' => $request->cookie('landing_url') ?: $user->landing_url,
            ])->save();
        }

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
                    $subscription = $subscription->withMetadata([
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

                    $subscription = $subscription->withCoupon($discountCode->stripe_coupon_id);
                }

            }

            // Create the subscription with the payment method
            $createdSubscription = $subscription->create($request->payment_method);

            // Track coupon usage AFTER successful subscription creation
            if ($request->filled('coupon')) {
                $discountCode = DiscountCode::where('code', $request->coupon)->active()->first();

                if ($discountCode && $discountCode->isValid() && $discountCode->canBeUsedBy($user)) {
                    // Get product for discount calculation
                    $stripeProduct = StripeProduct::where('stripe_price_id', $priceId)->first();

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
                            'user_id' => $user->id,
                        ]);
                        // Set a default discount amount based on the discount type
                        if ($discountCode->discount_type === 'fixed') {
                            $discountAmount = $discountCode->discount_amount;
                        } else {
                            // For percentage discounts, we'll store 0 since we don't know the base price
                            $discountAmount = 0;
                        }
                    }

                    // Create redemption record with subscription ID
                    $discountCode->redemptions()->create([
                        'user_id' => $user->id,
                        'subscription_id' => $createdSubscription->id,
                        'discount_applied' => $discountAmount,
                    ]);

                    // Increment usage count
                    $discountCode->incrementUsage();
                }
            }

            // Log successful subscription creation
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'subscription_id' => $createdSubscription->id,
                    'stripe_id' => $createdSubscription->stripe_id,
                    'price_id' => $priceId,
                    'coupon_used' => $request->coupon ?? null,
                ])
                ->log('subscription_created');

            // Get purchase data for tracking
            $stripeProduct = StripeProduct::where('stripe_price_id', $priceId)->first();
            $purchaseValue = $stripeProduct ? $stripeProduct->price : 0;

            // Apply discount to purchase value for tracking
            if ($request->filled('coupon')) {
                $discountCode = DiscountCode::where('code', $request->coupon)->active()->first();
                if ($discountCode) {
                    if ($discountCode->discount_type === 'percentage') {
                        $purchaseValue = $purchaseValue * (1 - $discountCode->discount_amount / 100);
                    } else {
                        $purchaseValue = max(0, $purchaseValue - $discountCode->discount_amount);
                    }
                }
            }

            $purchaseData = [
                'plan_name' => $stripeProduct ? ucfirst($stripeProduct->tier).' Plan' : 'Subscription',
                'plan_price' => $purchaseValue,
                'billing_period' => $stripeProduct->billing_period ?? 'monthly',
                // Shared browser/server conversion_id (Stripe PaymentIntent id) so
                // X deduplicates the browser pixel + server Conversion API events.
                'conversion_id' => $this->extractPaymentIntentId($createdSubscription),
            ];

            // Check if subscription requires additional action (3D Secure)
            if ($createdSubscription->hasIncompletePayment()) {
                // Get the latest invoice's payment intent
                $latestInvoice = $createdSubscription->latestInvoice();

                if ($latestInvoice && $latestInvoice->payment_intent) {
                    // Return the client secret for 3D Secure authentication
                    // Also include purchase data so frontend can track after 3DS completion
                    return back()->with([
                        'requires_action' => true,
                        'payment_intent_client_secret' => $latestInvoice->payment_intent->client_secret,
                        'subscription_id' => $createdSubscription->id,
                        'purchase_data' => $purchaseData,
                    ]);
                }
            }

            return redirect()->route('dashboard')->with([
                'success' => 'Subscription activated successfully!',
                'purchase_data' => $purchaseData,
            ]);

        } catch (\Stripe\Exception\CardException $e) {
            // Card was declined or 3D Secure failed - report to Sentry for visibility
            \Sentry\captureException($e);

            Log::error('Card payment failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'decline_code' => $e->getDeclineCode(),
                'stripe_error' => $e->getStripeCode(),
            ]);

            // Log activity for failed subscription attempt
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'error_type' => 'card_error',
                    'stripe_code' => $e->getStripeCode(),
                    'decline_code' => $e->getDeclineCode(),
                    'coupon_used' => $request->coupon ?? null,
                ])
                ->log('subscription_attempt_failed');

            $errorMessage = match ($e->getStripeCode()) {
                'authentication_required' => 'Your card requires additional authentication. Please try again and complete the verification process.',
                'card_declined' => 'Your card was declined. Please try a different card.',
                'insufficient_funds' => 'Your card has insufficient funds. Please try a different card.',
                'expired_card' => 'Your card has expired. Please use a different card.',
                'incorrect_cvc' => 'The CVC code is incorrect. Please check and try again.',
                'processing_error' => 'An error occurred while processing your card. Please try again.',
                default => 'Payment failed: '.$e->getMessage(),
            };

            return back()->withErrors(['payment' => $errorMessage]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Invalid parameters were supplied to Stripe's API - report to Sentry for visibility
            \Sentry\captureException($e);

            Log::error('Invalid Stripe request', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Log activity for failed subscription attempt
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'error_type' => 'invalid_request',
                    'error_message' => $e->getMessage(),
                    'coupon_used' => $request->coupon ?? null,
                ])
                ->log('subscription_attempt_failed');

            return back()->withErrors(['payment' => 'Invalid payment request. Please contact support.']);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            // Authentication with Stripe's API failed - report to Sentry for visibility
            \Sentry\captureException($e);

            Log::error('Stripe authentication failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Log activity for failed subscription attempt
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'error_type' => 'authentication_error',
                    'error_message' => $e->getMessage(),
                    'coupon_used' => $request->coupon ?? null,
                ])
                ->log('subscription_attempt_failed');

            return back()->withErrors(['payment' => 'Payment system error. Please try again later.']);
        } catch (\Exception $e) {
            // Generic error handling - report to Sentry for visibility
            \Sentry\captureException($e);

            Log::error('Subscription creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'type' => get_class($e),
            ]);

            // Log activity for failed subscription attempt
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'error_type' => 'general_error',
                    'error_message' => $e->getMessage(),
                    'exception_class' => get_class($e),
                    'coupon_used' => $request->coupon ?? null,
                ])
                ->log('subscription_attempt_failed');

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
     * Log client-side Stripe.js errors for debugging
     * This catches errors that occur before form submission (card validation, etc.)
     */
    public function logClientError(Request $request)
    {
        $request->validate([
            'error_code' => 'nullable|string|max:100',
            'error_message' => 'required|string|max:500',
            'error_type' => 'nullable|string|max:100',
            'context' => 'nullable|string|max:100',
        ]);

        $user = $request->user();

        // Log to Laravel logs
        Log::error('Client-side Stripe error', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'error_code' => $request->error_code,
            'error_message' => $request->error_message,
            'error_type' => $request->error_type,
            'context' => $request->context ?? 'checkout',
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
        ]);

        // Report to Sentry as a message (not exception since it's client-side)
        \Sentry\captureMessage('Client-side Stripe error: '.$request->error_message, \Sentry\Severity::error());

        // Log activity for tracking
        if ($user) {
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'error_type' => 'client_stripe_error',
                    'error_code' => $request->error_code,
                    'error_message' => $request->error_message,
                    'stripe_error_type' => $request->error_type,
                ])
                ->log('subscription_attempt_failed');
        }

        return response()->json(['logged' => true]);
    }

    /**
     * Handle subscription plan switching with proration
     */
    public function switchPlan(Request $request)
    {
        $validated = $request->validate([
            'price_id' => 'required|string',
            'subscription_name' => 'required|string|in:silver,gold,platinum',
            'coupon' => 'nullable|string',
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

            // Get product details for tracking
            $stripeProduct = StripeProduct::where('stripe_price_id', $priceId)->first();
            $purchaseValue = $stripeProduct ? $stripeProduct->price : 0;

            $purchaseData = [
                'plan_name' => $stripeProduct ? ucfirst($stripeProduct->tier).' Plan' : 'Subscription',
                'plan_price' => $purchaseValue,
                'billing_period' => $stripeProduct->billing_period ?? 'monthly',
            ];

            if (! $subscription) {
                // No current subscription, create new one
                $newSubscription = $user->newSubscription('default', $priceId);

                // Apply coupon if provided (for first-time subscribers)
                if ($request->filled('coupon')) {
                    $discountCode = DiscountCode::where('code', $request->coupon)->active()->first();
                    if ($discountCode && $discountCode->isValid() && $discountCode->canBeUsedBy($user)) {
                        $newSubscription = $newSubscription->withCoupon($discountCode->stripe_coupon_id);
                    }
                }

                $newSubscription->create();

                return redirect()->route('billing.edit')->with([
                    'success' => 'Successfully subscribed to the new plan!',
                    'purchase_data' => $purchaseData,
                ]);
            }

            // Switch to new plan with proration
            // Stripe automatically handles proration when swapping plans
            $subscription->swap($priceId);

            // Get the new plan details for the success message
            $stripePrices = config('stripe.price_to_tier', []);
            $planDetails = $stripePrices[$priceId] ?? ['tier' => 'new', 'period' => ''];

            return redirect()->route('billing.edit')->with([
                'success' => "Successfully switched to {$planDetails['tier']} {$planDetails['period']} plan! Your billing has been prorated automatically.",
                'purchase_data' => $purchaseData,
            ]);

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

    /**
     * Clean up orphaned discount redemptions from failed subscription attempts
     *
     * This method finds and removes discount code redemptions that were created
     * but never attached to a successful subscription (subscription_id is null).
     * It also decrements the usage count for those codes.
     */
    /**
     * Best-effort extraction of the Stripe PaymentIntent id from a freshly
     * created subscription, used as the shared browser/server conversion_id.
     *
     * Uses Cashier's latestPayment() which expands latest_invoice.payment_intent
     * so the id is available (latestInvoice() leaves payment_intent as a bare id
     * string and would break dedup).
     */
    private function extractPaymentIntentId(mixed $subscription): ?string
    {
        try {
            return $subscription->latestPayment()?->id;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function cleanupOrphanedRedemptions($user)
    {
        try {
            // Find orphaned redemptions (older than 1 hour to avoid race conditions)
            $orphanedRedemptions = \App\Models\DiscountRedemption::where('user_id', $user->id)
                ->whereNull('subscription_id')
                ->where('created_at', '<', now()->subHour())
                ->get();

            if ($orphanedRedemptions->isEmpty()) {
                return;
            }

            foreach ($orphanedRedemptions as $redemption) {
                $discountCode = $redemption->discountCode;

                if ($discountCode) {
                    // Log the cleanup activity
                    activity()
                        ->performedOn($user)
                        ->causedBy($user)
                        ->withProperties([
                            'discount_code' => $discountCode->code,
                            'discount_amount' => $redemption->discount_applied,
                            'failed_at' => $redemption->created_at,
                            'reason' => 'Failed subscription attempt - discount code restored',
                        ])
                        ->log('discount_code_restored');

                    // Decrement the usage count
                    $discountCode->decrement('times_used');

                    Log::info('Cleaned up orphaned discount redemption', [
                        'user_id' => $user->id,
                        'discount_code' => $discountCode->code,
                        'redemption_id' => $redemption->id,
                        'created_at' => $redemption->created_at,
                    ]);
                }

                // Delete the orphaned redemption record
                $redemption->delete();
            }

            Log::info('Orphaned redemptions cleanup completed', [
                'user_id' => $user->id,
                'cleaned_count' => $orphanedRedemptions->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to cleanup orphaned redemptions', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            // Don't fail the subscription process if cleanup fails
        }
    }
}
