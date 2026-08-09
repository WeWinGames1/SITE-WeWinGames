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
     * @return array{success: bool, user?: User, subscription?: mixed, error?: string, payment_intent_id?: ?string, amount_paid?: ?float}
     */
    public function processCheckout(Request $request, string $priceId, string $paymentMethod, ?int $trialDays = null, ?string $registrationType = null): array
    {
        $email = strtolower($request->input('email'));

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            return [
                'success' => false,
                'error' => 'email_exists',
                'message' => 'An account with this email already exists. Please log in instead.',
            ];
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

            // Create the subscription with the payment method (the actual charge)
            $createdSubscription = $subscription->create($paymentMethod);

            // The card has now been charged. EVERY remaining step is best-effort: a
            // failure here must never reach the outer catch (which would delete the
            // paid account and cancel the subscription). The completion token is the
            // only piece needed for the redirect; if it fails the user can still
            // recover via the forgot-password fallback.
            try {
                $user->generateCompletionToken();

                if ($couponCode) {
                    $this->trackCouponUsage($user, $couponCode, $priceId, $createdSubscription->id);
                }

                activity()
                    ->performedOn($user)
                    ->causedBy($user)
                    ->withProperties([
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'affiliate_id' => $user->affiliate_id,
                        'subscription_id' => $createdSubscription->id,
                        'registration_type' => $registrationType,
                        'trial_days' => $trialDays,
                    ])
                    ->log($registrationType === 'affiliate_trial' ? 'affiliate_trial_completed' : 'quick_checkout_completed');

                $this->securityService->logRegistrationAttempt($request, true);
            } catch (\Throwable $e) {
                Log::error('Quick checkout post-charge bookkeeping failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Best-effort external sync + completion email (each swallows its own errors).
            $this->syncToExternalServices($user);
            $this->sendCompletionEmail($user);

            $payment = $this->extractPaymentDetails($createdSubscription);

            return [
                'success' => true,
                'user' => $user,
                'subscription' => $createdSubscription,
                'payment_intent_id' => $payment['id'],
                'amount_paid' => $payment['amount'],
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
