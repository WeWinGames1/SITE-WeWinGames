<?php

namespace App\Services;

use App\Mail\CompleteYourAccountMail;
use App\Models\Affiliate;
use App\Models\DiscountCode;
use App\Models\StripeProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
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
     * @return array{success: bool, user?: User, subscription?: mixed, error?: string}
     */
    public function processCheckout(Request $request, string $priceId, string $paymentMethod): array
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

        try {
            return DB::transaction(function () use ($request, $email, $priceId, $paymentMethod) {
                // Get affiliate if present
                $affiliateId = $this->getAffiliateId();

                // Create provisional user
                $user = User::create([
                    'name' => $request->input('name'),
                    'email' => $email,
                    'phone' => $request->input('phone'),
                    'password' => Hash::make(bin2hex(random_bytes(16))), // Random password
                    'status' => 'pending_setup',
                    'registration_type' => 'quick_checkout',
                    'affiliate_id' => $affiliateId,
                    'registration_ip' => $request->ip(),
                    'registration_user_agent' => $request->userAgent(),
                ]);

                // Assign default role
                $user->assignRole('user');

                // Create Stripe customer
                $user->createAsStripeCustomer([
                    'metadata' => [
                        'registration_ip' => $request->ip(),
                        'registration_date' => now()->toDateTimeString(),
                        'affiliate_code' => Cookie::get('affiliate_code') ?? '',
                        'registration_type' => 'quick_checkout',
                    ],
                ]);

                // Create subscription
                $subscription = $user->newSubscription('default', $priceId);

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

                        // Check product restrictions
                        if (! $stripeProduct || $discountCode->appliesToProduct($stripeProduct->stripe_product_id)) {
                            $stripeCouponId = $this->ensureStripeCouponExists($discountCode);
                            if ($stripeCouponId) {
                                $subscription = $subscription->withCoupon($stripeCouponId);
                            }
                        }
                    }
                }

                // Create the subscription with the payment method
                $createdSubscription = $subscription->create($paymentMethod);

                // Track coupon usage
                if ($couponCode) {
                    $this->trackCouponUsage($user, $couponCode, $priceId, $createdSubscription->id);
                }

                // Generate completion token
                $user->generateCompletionToken();

                // Log activity
                activity()
                    ->performedOn($user)
                    ->causedBy($user)
                    ->withProperties([
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'affiliate_id' => $user->affiliate_id,
                        'subscription_id' => $createdSubscription->id,
                        'registration_type' => 'quick_checkout',
                    ])
                    ->log('quick_checkout_completed');

                // Log successful registration
                $this->securityService->logRegistrationAttempt($request, true);

                // Sync to external services (async - don't block checkout)
                $this->syncToExternalServices($user);

                // Send completion email
                $this->sendCompletionEmail($user);

                return [
                    'success' => true,
                    'user' => $user,
                    'subscription' => $createdSubscription,
                ];
            });
        } catch (\Stripe\Exception\CardException $e) {
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
        } catch (\Exception $e) {
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
            ->where('registration_type', 'quick_checkout')
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

        $discountAmount = 0;
        if ($stripeProduct) {
            $basePrice = $stripeProduct->price;
            if ($discountCode->discount_type === 'percentage') {
                $discountAmount = $basePrice * ($discountCode->discount_amount / 100);
            } else {
                $discountAmount = $discountCode->discount_amount;
            }
        }

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
