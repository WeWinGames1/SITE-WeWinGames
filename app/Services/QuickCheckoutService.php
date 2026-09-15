<?php

namespace App\Services;

use App\Mail\CompleteYourAccountMail;
use App\Models\Affiliate;
use App\Models\DiscountCode;
use App\Models\StripeProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Subscription;

class QuickCheckoutService
{
    public function __construct(
        protected RegistrationSecurityService $securityService,
        protected SendGridService $sendGridService,
        protected SpringBigService $springBigService
    ) {}

    /**
     * Process the quick checkout: create user, Stripe customer, and subscription
     *
     * Returns `requires_action` when Stripe still needs the customer to confirm
     * the PaymentIntent in the browser (Cash App Pay hand-off, 3DS challenge);
     * the checkout is then finished by completePendingPayment() on the return leg.
     *
     * @return array{success: bool, requires_action?: bool, user?: User, subscription?: mixed, error?: string, message?: string, client_secret?: string, completion_token?: string, payment_intent_id?: ?string, amount_paid?: ?float}
     */
    public function processCheckout(Request $request, string $priceId, string $paymentMethod, ?int $trialDays = null, ?string $registrationType = null): array
    {
        $email = strtolower($request->input('email'));

        $phone = $request->input('phone');

        // A pending_setup user whose payment never completed (abandoned Cash App
        // or 3DS redirect) is not a real account. Reclaim it so the customer can
        // retry instead of colliding with the unique email/phone constraints —
        // the matching rules in QuickCheckoutRequest let those rows through for
        // exactly this check to settle, against Stripe rather than a stale status.
        $existingUsers = User::where('email', $email)
            ->when($phone, fn ($query) => $query->orWhere('phone', $phone))
            ->get();

        foreach ($existingUsers as $existingUser) {
            if ($this->isAbandonedCheckout($existingUser)) {
                continue;
            }

            return [
                'success' => false,
                'error' => 'email_exists',
                'message' => $existingUser->email === $email
                    ? 'An account with this email already exists. Please log in instead.'
                    : 'An account with this phone number already exists. Please log in instead.',
            ];
        }

        // Only once every match is known to be abandoned, so a blocking account
        // is never deleted on the way to rejecting the attempt.
        foreach ($existingUsers as $existingUser) {
            $this->cleanupFailedCheckout($existingUser);
        }

        // Perform security checks
        $securityCheck = $this->securityService->canRegister($request);
        if (! $securityCheck['allowed']) {
            $this->securityService->logRegistrationAttempt($request, false);

            return [
                'success' => false,
                'error' => 'security_check_failed',
                'message' => $securityCheck['reason'],
            ];
        }

        // Default registration type
        $registrationType = $registrationType ?? 'quick_checkout';

        // Prevent concurrent double-submits (double-click / network retry) from
        // creating two Stripe customers and charging the card twice.
        $lockKey = 'quick-checkout-lock:'.$email;
        if (! Cache::add($lockKey, true, 60)) {
            return [
                'success' => false,
                'error' => 'in_progress',
                'message' => 'A checkout for this email is already being processed. Please wait a moment and try again.',
            ];
        }

        $user = null;

        try {
            $affiliateId = $this->getAffiliateId();

            // Create and commit the local user BEFORE any Stripe call. Stripe side
            // effects are not transactional, so a customer/subscription created here
            // could not be rolled back — committing the user first guarantees a paid
            // customer always has a recoverable local account.
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $email,
                'phone' => $request->input('phone'),
                'password' => Hash::make(bin2hex(random_bytes(16))), // Random password
                'status' => 'pending_setup',
                'registration_type' => $registrationType,
                'affiliate_id' => $affiliateId,
                'registration_ip' => $request->ip(),
                'registration_user_agent' => $request->userAgent(),
                // Same values, but under the checkout keys the Conversion API
                // listeners read — registration_* can drift from the paying
                // browser on every later purchase.
                'checkout_ip_address' => $request->ip(),
                'checkout_user_agent' => $request->userAgent(),
                ...$this->marketingAttribution($request),
            ]);

            $user->assignRole('user');

            // Create Stripe customer
            $user->createAsStripeCustomer([
                'metadata' => [
                    'registration_ip' => $request->ip(),
                    'registration_date' => now()->toDateTimeString(),
                    'affiliate_code' => Cookie::get('affiliate_code') ?? '',
                    'registration_type' => $registrationType,
                    'twclid' => $request->cookie('twclid') ?? '',
                ],
            ]);

            // Create subscription
            $subscription = $user->newSubscription('default', $priceId);

            // Add trial period if specified
            if ($trialDays && $trialDays > 0) {
                $subscription = $subscription->trialDays($trialDays);
            }

            // Handle affiliate metadata
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

            // Apply discount code if provided
            $couponCode = $request->input('coupon');
            if ($couponCode) {
                $discountCode = DiscountCode::where('code', $couponCode)
                    ->active()
                    ->first();

                if ($discountCode && $discountCode->isValid()) {
                    $stripeProduct = StripeProduct::where('stripe_price_id', $priceId)->first();

                    // Check product restrictions (fail closed: skip coupon if the
                    // price has no matching local product to validate against).
                    if ($stripeProduct && $discountCode->appliesToProduct($stripeProduct->stripe_product_id)) {
                        $stripeCouponId = $this->ensureStripeCouponExists($discountCode);
                        if ($stripeCouponId) {
                            $subscription = $subscription->withCoupon($stripeCouponId);
                        }
                    }
                }
            }

            // Create the subscription WITHOUT letting Cashier confirm the
            // PaymentIntent server-side. A server-side confirm cannot pass the
            // return_url that redirect-based methods (Cash App Pay) require, and
            // cannot answer a 3DS challenge either — both come back as a 400 that
            // used to take the entire signup down with it. Confirmation is done in
            // the browser instead; quick-checkout.return finishes the job.
            $createdSubscription = $subscription->ignoreIncompletePayments()->create($paymentMethod);

            $context = [
                'coupon' => $couponCode,
                'price_id' => $priceId,
                'registration_type' => $registrationType,
                'trial_days' => $trialDays,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            $payment = $createdSubscription->hasIncompletePayment()
                ? $createdSubscription->latestPayment()
                : null;

            if ($payment && ! $payment->isSucceeded() && ! $payment->isProcessing()) {
                // Nothing charged yet. Hand the browser the client secret so
                // Stripe.js can run the Cash App hand-off / 3DS challenge, and park
                // everything the return leg needs to finish the signup.
                $token = $user->generateCompletionToken();

                Cache::put($this->pendingContextKey($token), $context, now()->addHours(24));

                $this->logRegistrationSuccess($request);

                return [
                    'success' => true,
                    'requires_action' => true,
                    'user' => $user,
                    'subscription' => $createdSubscription,
                    'client_secret' => $payment->clientSecret(),
                    'completion_token' => $token,
                ];
            }

            // Paid outright (or $0 / trial) — no customer action needed.
            $this->finalizeCheckout($user, $createdSubscription, $context);

            $this->logRegistrationSuccess($request);

            $paymentDetails = $this->extractPaymentDetails($createdSubscription);

            return [
                'success' => true,
                'requires_action' => false,
                'user' => $user,
                'subscription' => $createdSubscription,
                'payment_intent_id' => $paymentDetails['id'],
                'amount_paid' => $paymentDetails['amount'],
            ];
        } catch (\Stripe\Exception\CardException $e) {
            // No successful charge — remove the provisional user and Stripe customer.
            $this->cleanupFailedCheckout($user);

            Log::error('Quick checkout card error', [
                'email' => $email,
                'error' => $e->getMessage(),
                'decline_code' => $e->getDeclineCode(),
            ]);

            return [
                'success' => false,
                'error' => 'card_error',
                'message' => $this->getCardErrorMessage($e),
            ];
        } catch (\Throwable $e) {
            $this->cleanupFailedCheckout($user);

            Log::error('Quick checkout failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->securityService->logRegistrationAttempt($request, false);

            return [
                'success' => false,
                'error' => 'general_error',
                'message' => 'An unexpected error occurred. Please try again or contact support.',
            ];
        } finally {
            Cache::forget($lockKey);
        }
    }

    /**
     * Remove a provisional user and its Stripe customer after a failed checkout
     * so a declined/errored attempt never leaves orphaned records or charges.
     */
    protected function cleanupFailedCheckout(?User $user): void
    {
        if (! $user) {
            return;
        }

        try {
            if ($user->hasStripeId()) {
                // Deleting the Stripe customer also cancels any attached subscription.
                $stripe = new \Stripe\StripeClient(config('cashier.secret'));
                $stripe->customers->delete($user->stripe_id);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to remove Stripe customer after failed checkout', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $user->delete();
        } catch (\Throwable $e) {
            Log::error('Failed to remove user after failed checkout', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * A checkout that never got paid for: a pending_setup user with no
     * subscription, or only subscriptions Stripe never moved past `incomplete`.
     * Such a record holds the email and phone hostage without representing a
     * customer, so the next attempt is allowed to reclaim it.
     *
     * The local `stripe_status` is deliberately NOT trusted here. It still reads
     * `incomplete` for the entire time a customer is away in Cash App, so a
     * second tab reaching this check could otherwise delete the Stripe customer
     * of a payment that has already gone through. Ask Stripe, and fail closed
     * when it cannot answer.
     */
    protected function isAbandonedCheckout(User $user): bool
    {
        if (! $user->needsRegistrationCompletion()) {
            return false;
        }

        $subscriptions = $user->subscriptions()->get();

        if ($subscriptions->isEmpty()) {
            return true;
        }

        foreach ($subscriptions as $subscription) {
            try {
                // One call answers both questions: the live subscription status
                // and the state of the PaymentIntent behind its latest invoice.
                $stripeSubscription = $subscription->asStripeSubscription(['latest_invoice.payment_intent']);
            } catch (\Throwable $e) {
                Log::warning('Quick checkout: could not verify a subscription before reclaiming an account', [
                    'user_id' => $user->id,
                    'stripe_id' => $subscription->stripe_id,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }

            $subscription->stripe_status = $stripeSubscription->status;
            $subscription->save();

            if (! in_array($stripeSubscription->status, ['incomplete', 'incomplete_expired'], true)) {
                return false;
            }

            // An incomplete subscription whose payment is already succeeded or
            // still settling is a sale in flight, not an abandoned attempt.
            $paymentIntent = $stripeSubscription->latest_invoice?->payment_intent;

            if ($paymentIntent && in_array($paymentIntent->status, ['succeeded', 'processing'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Record a successful registration attempt without ever letting a logging
     * failure escape: in processCheckout this runs after the charge, where an
     * exception would reach the outer catch and delete the paid account.
     */
    protected function logRegistrationSuccess(Request $request): void
    {
        try {
            $this->securityService->logRegistrationAttempt($request, true);
        } catch (\Throwable $e) {
            Log::error('Quick checkout: failed to log the registration attempt', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cache key holding everything the return leg needs to finish a checkout
     * whose payment was still waiting on the customer. Doubles as a once-only
     * latch: the key is forgotten on finalize, so a replayed return URL cannot
     * re-run the bookkeeping.
     */
    protected function pendingContextKey(string $token): string
    {
        return 'quick-checkout-pending:'.$token;
    }

    /**
     * Finish a checkout whose PaymentIntent was confirmed in the browser
     * (Cash App hand-off or 3DS challenge) and the customer has come back.
     *
     * @return array{success: bool, error?: string, message?: string, processing?: bool, coupon?: ?string, payment_intent_id?: ?string, amount_paid?: ?float}
     */
    public function completePendingPayment(User $user): array
    {
        $subscription = $user->subscriptions()->latest('id')->first();

        if (! $subscription) {
            // Nothing to verify against, so nothing is deleted here — the next
            // attempt reclaims the account through isAbandonedCheckout(), which
            // confirms with Stripe first.
            return [
                'success' => false,
                'error' => 'no_subscription',
                'message' => 'We could not find your payment. Please try again.',
            ];
        }

        try {
            $subscription->syncStripeStatus();
            $payment = $subscription->latestPayment();
        } catch (\Throwable $e) {
            Log::error('Quick checkout return: failed to read payment state', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'lookup_failed',
                'message' => 'We could not confirm your payment. Please contact support before trying again.',
            ];
        }

        // No PaymentIntent at all means a $0 / trial subscription: nothing to wait on.
        if ($payment && ! $payment->isSucceeded() && ! $payment->isProcessing()) {
            // Only a terminal failure justifies deleting the provisional account.
            // A payment still awaiting the customer is left alone so they can
            // finish it from the Cash App prompt that is still open.
            if ($payment->isCanceled() || $payment->requiresPaymentMethod()) {
                $this->cleanupFailedCheckout($user);

                return [
                    'success' => false,
                    'error' => 'payment_failed',
                    'message' => 'Your payment was not completed. Please try again with a different payment method.',
                ];
            }

            return [
                'success' => false,
                'error' => 'payment_incomplete',
                'message' => 'Your payment has not been completed yet. Please finish it in Cash App, or try again.',
            ];
        }

        // Atomic pull doubles as the once-only latch: a refreshed or replayed
        // return URL finds nothing left and skips the bookkeeping instead of
        // double-counting a coupon redemption or resending the email.
        $context = Cache::pull($this->pendingContextKey((string) $user->completion_token));

        if ($context !== null) {
            $this->finalizeCheckout($user, $subscription, $context);
        }

        return [
            'success' => true,
            'processing' => (bool) $payment?->isProcessing(),
            'coupon' => $context['coupon'] ?? null,
            // Already loaded above — re-fetching would be a second Stripe call
            // for a PaymentIntent we are holding.
            'payment_intent_id' => $payment?->id,
            'amount_paid' => $payment ? $payment->rawAmount() / 100 : null,
        ];
    }

    /**
     * Post-charge bookkeeping. EVERY step here is best-effort: the money has
     * already moved, so a failure must never bubble up to a caller that would
     * delete the paid account. Worst case the customer recovers through the
     * forgot-password fallback.
     *
     * Callers are responsible for running this exactly once per checkout.
     *
     * @param  array{coupon?: ?string, price_id?: ?string, registration_type?: ?string, trial_days?: ?int, ip_address?: ?string, user_agent?: ?string}  $context
     */
    protected function finalizeCheckout(User $user, Subscription $subscription, array $context): void
    {
        try {
            if (! $user->hasValidCompletionToken()) {
                $user->generateCompletionToken();
            }

            $registrationType = $context['registration_type'] ?? $user->registration_type ?? 'quick_checkout';

            if (! empty($context['coupon']) && ! empty($context['price_id'])) {
                $this->trackCouponUsage($user, $context['coupon'], $context['price_id'], $subscription->id);
            }

            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'ip_address' => $context['ip_address'] ?? null,
                    'user_agent' => $context['user_agent'] ?? null,
                    'affiliate_id' => $user->affiliate_id,
                    'subscription_id' => $subscription->id,
                    'registration_type' => $registrationType,
                    'trial_days' => $context['trial_days'] ?? null,
                ])
                ->log($registrationType === 'affiliate_trial' ? 'affiliate_trial_completed' : 'quick_checkout_completed');
        } catch (\Throwable $e) {
            Log::error('Quick checkout post-charge bookkeeping failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Best-effort external sync + completion email (each swallows its own errors).
        $this->syncToExternalServices($user);
        $this->sendCompletionEmail($user);
    }

    /**
     * Complete user registration (set password, Discord username)
     */
    public function completeRegistration(User $user, string $password, ?string $discordUsername = null): array
    {
        try {
            $user->password = Hash::make($password);
            $user->status = 'active';
            $user->discord_username = $discordUsername ? strtolower($discordUsername) : null;
            $user->clearCompletionToken();

            // Mark email as verified since they paid
            if (! $user->email_verified_at) {
                $user->email_verified_at = now();
            }

            $user->save();

            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'registration_type' => 'quick_checkout',
                ])
                ->log('registration_completed');

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Complete registration failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to complete registration. Please try again.',
            ];
        }
    }

    /**
     * Find user by completion token
     */
    public function findUserByToken(string $token): ?User
    {
        return User::where('completion_token', $token)
            ->where('status', 'pending_setup')
            ->whereIn('registration_type', ['quick_checkout', 'affiliate_trial'])
            ->where('completion_token_expires_at', '>', now())
            ->first();
    }

    /**
     * Resend completion email
     */
    public function resendCompletionEmail(User $user): bool
    {
        if (! $user->needsRegistrationCompletion()) {
            return false;
        }

        // Regenerate token
        $user->generateCompletionToken();
        $this->sendCompletionEmail($user);

        return true;
    }

    /**
     * Marketing attribution captured from first-party cookies (set by the
     * TrackMarketingAttribution middleware) to persist on the new user for the
     * server-side X Conversion API purchase event.
     *
     * @return array{twclid: ?string, utm_source: ?string, utm_medium: ?string, utm_campaign: ?string, utm_content: ?string, landing_url: ?string}
     */
    protected function marketingAttribution(Request $request): array
    {
        return [
            'twclid' => $request->cookie('twclid'),
            'utm_source' => $request->cookie('utm_source'),
            'utm_medium' => $request->cookie('utm_medium'),
            'utm_campaign' => $request->cookie('utm_campaign'),
            'utm_content' => $request->cookie('utm_content'),
            'landing_url' => $request->cookie('landing_url'),
        ];
    }

    /**
     * Best-effort extraction of the Stripe PaymentIntent behind a freshly created
     * subscription: its id becomes the shared browser/server conversion_id, and
     * its amount is the value both sides report, so a browser event and its
     * server twin can never disagree.
     *
     * Uses Cashier's latestPayment() which expands latest_invoice.payment_intent
     * so the id is available (latestInvoice() leaves payment_intent as a bare id
     * string and would break dedup).
     *
     * @return array{id: ?string, amount: ?float}
     */
    protected function extractPaymentDetails(mixed $subscription): array
    {
        try {
            $payment = $subscription->latestPayment();

            return [
                'id' => $payment?->id,
                'amount' => $payment ? $payment->rawAmount() / 100 : null,
            ];
        } catch (\Throwable $e) {
            return ['id' => null, 'amount' => null];
        }
    }

    protected function getAffiliateId(): ?int
    {
        $affiliateCode = Cookie::get('affiliate_code');
        if (! $affiliateCode) {
            return null;
        }

        $affiliate = Affiliate::where('code', $affiliateCode)
            ->where('is_active', true)
            ->first();

        return $affiliate?->id;
    }

    protected function ensureStripeCouponExists(DiscountCode $discountCode): ?string
    {
        if ($discountCode->stripe_coupon_id) {
            return $discountCode->stripe_coupon_id;
        }

        try {
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $couponData = ['currency' => 'usd'];

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
                $couponData['amount_off'] = $discountCode->discount_amount * 100;
            }

            $stripeCoupon = $stripe->coupons->create($couponData);
            $discountCode->update(['stripe_coupon_id' => $stripeCoupon->id]);

            return $stripeCoupon->id;
        } catch (\Exception $e) {
            Log::error('Failed to create Stripe coupon', [
                'discount_code' => $discountCode->code,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function trackCouponUsage(User $user, string $couponCode, string $priceId, int $subscriptionId): void
    {
        $discountCode = DiscountCode::where('code', $couponCode)->active()->first();

        if (! $discountCode || ! $discountCode->isValid()) {
            return;
        }

        $stripeProduct = StripeProduct::where('stripe_price_id', $priceId)->first();

        // calculateDiscount() caps a fixed discount at the product price so the
        // tracked redemption value never exceeds what was actually charged.
        $discountAmount = $stripeProduct
            ? $discountCode->calculateDiscount((float) $stripeProduct->price)
            : 0;

        $discountCode->redemptions()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscriptionId,
            'discount_applied' => $discountAmount,
        ]);

        $discountCode->incrementUsage();
    }

    protected function syncToExternalServices(User $user): void
    {
        try {
            $this->sendGridService->syncContact($user);
        } catch (\Exception $e) {
            Log::error('Failed to sync user to SendGrid: '.$e->getMessage());
        }

        try {
            $this->springBigService->createMember($user);
        } catch (\Exception $e) {
            Log::error('Failed to sync user to Spring Big: '.$e->getMessage());
        }
    }

    protected function sendCompletionEmail(User $user): void
    {
        try {
            Mail::to($user->email)->send(new CompleteYourAccountMail($user));
        } catch (\Exception $e) {
            Log::error('Failed to send completion email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function getCardErrorMessage(\Stripe\Exception\CardException $e): string
    {
        return match ($e->getStripeCode()) {
            'authentication_required' => 'Your card requires additional authentication. Please try again and complete the verification process.',
            'card_declined' => 'Your card was declined. Please try a different card.',
            'insufficient_funds' => 'Your card has insufficient funds. Please try a different card.',
            'expired_card' => 'Your card has expired. Please use a different card.',
            'incorrect_cvc' => 'The CVC code is incorrect. Please check and try again.',
            'processing_error' => 'An error occurred while processing your card. Please try again.',
            default => 'Payment failed: '.$e->getMessage(),
        };
    }
}
