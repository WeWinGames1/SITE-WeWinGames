<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpringBigService
{
    protected string $apiKey;

    protected string $authToken;

    protected string $baseUrl;

    protected bool $enabled;

    public function __construct()
    {
        $this->enabled = (bool) config('services.springbig.enabled', false);
        $this->baseUrl = config('services.springbig.base_url') ?? 'https://production.api.springbig.technology/pos/v1';
        $this->apiKey = config('services.springbig.api_key') ?? '';
        $this->authToken = config('services.springbig.auth_token') ?? '';
    }

    /**
     * Check if Spring Big integration is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled && ! empty($this->apiKey);
    }

    /**
     * Get the custom group name based on user's subscription tier
     */
    public function getCustomGroupForUser(User $user): string
    {
        // Check for admin override first
        if ($user->admin_override && $user->override_tier) {
            return 'custom_group_'.strtolower($user->override_tier);
        }

        // Get active subscription
        $subscription = $user->subscriptions()
            ->whereNull('ends_at')
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->first();

        if (! $subscription) {
            // Check if user ever had a subscription (canceled)
            $hasHadSubscription = $user->subscriptions()->exists();
            if ($hasHadSubscription) {
                return 'custom_group_canceled';
            }

            return 'custom_group_free';
        }

        // Get tier from subscription
        $tier = null;
        if ($subscription->stripe_price) {
            $stripeProduct = \App\Models\StripeProduct::where('stripe_price_id', $subscription->stripe_price)
                ->first();

            if ($stripeProduct) {
                $tier = strtolower($stripeProduct->tier);
            } else {
                // Fallback to config
                $priceToTier = config('stripe.price_to_tier', []);
                if (isset($priceToTier[$subscription->stripe_price]['tier'])) {
                    $tier = strtolower($priceToTier[$subscription->stripe_price]['tier']);
                }
            }
        }

        // Map tier to custom group
        return match ($tier) {
            'gold' => 'custom_group_gold',
            'platinum' => 'custom_group_platinum',
            'silver' => 'custom_group_silver',
            default => 'custom_group_free',
        };
    }

    /**
     * Create a new member in Spring Big
     */
    public function createMember(User $user): ?array
    {
        if (! $this->isEnabled()) {
            Log::debug('Spring Big integration is disabled, skipping member creation', [
                'user_id' => $user->id,
            ]);

            return null;
        }

        try {
            // Parse first and last name from full name
            $nameParts = explode(' ', $user->name, 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';

            // Determine custom group based on subscription
            $customGroup = $this->getCustomGroupForUser($user);

            // Build member payload
            // pos_type is a label identifying your system to Spring Big
            $memberData = [
                'member' => [
                    'pos_user' => (string) $user->id,
                    'pos_type' => 'wewingames',
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $user->email,
                    'allowed_email' => true,
                    $customGroup => true,
                ],
            ];

            // Add phone if available (format: 10 digits)
            if ($user->phone) {
                $phone = preg_replace('/[^0-9]/', '', $user->phone);
                if (strlen($phone) === 10) {
                    $memberData['member']['phone_number'] = $phone;
                    $memberData['member']['allowed_sms'] = true;
                } elseif (strlen($phone) === 11 && str_starts_with($phone, '1')) {
                    // Handle US country code
                    $memberData['member']['phone_number'] = substr($phone, 1);
                    $memberData['member']['allowed_sms'] = true;
                }
            }

            // Build headers - x-api-key is required, AUTH-TOKEN (merchant ID) may be optional
            $headers = [
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ];

            // Include merchant ID as AUTH-TOKEN if provided
            if (! empty($this->authToken)) {
                $headers['AUTH-TOKEN'] = $this->authToken;
            }

            $response = Http::withHeaders($headers)->post($this->baseUrl.'/members', $memberData);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('Spring Big member created successfully', [
                    'user_id' => $user->id,
                    'springbig_member_id' => $responseData['members'][0]['id'] ?? null,
                ]);

                return $responseData;
            }

            // Handle error responses
            $status = $response->status();
            $responseData = $response->json();

            if ($status === 422) {
                Log::warning('Spring Big validation error', [
                    'user_id' => $user->id,
                    'errors' => $responseData,
                ]);
            } else {
                Log::error('Spring Big member creation failed', [
                    'user_id' => $user->id,
                    'status' => $status,
                    'response' => $responseData,
                ]);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Spring Big member creation exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Don't rethrow - Spring Big sync is non-critical
            return null;
        }
    }

    /**
     * Update an existing member in Spring Big
     */
    public function updateMember(User $user): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $nameParts = explode(' ', $user->name, 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';

            // Determine custom group based on subscription
            $customGroup = $this->getCustomGroupForUser($user);

            // Reset all custom groups to false, then set the current one to true
            $memberData = [
                'member' => [
                    'pos_user' => (string) $user->id,
                    'pos_type' => 'wewingames',
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $user->email,
                    'custom_group_free' => false,
                    'custom_group_gold' => false,
                    'custom_group_platinum' => false,
                    'custom_group_canceled' => false,
                    $customGroup => true,
                ],
            ];

            if ($user->phone) {
                $phone = preg_replace('/[^0-9]/', '', $user->phone);
                if (strlen($phone) === 10) {
                    $memberData['member']['phone_number'] = $phone;
                } elseif (strlen($phone) === 11 && str_starts_with($phone, '1')) {
                    $memberData['member']['phone_number'] = substr($phone, 1);
                }
            }

            $headers = [
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ];

            if (! empty($this->authToken)) {
                $headers['AUTH-TOKEN'] = $this->authToken;
            }

            $response = Http::withHeaders($headers)->put($this->baseUrl.'/members', $memberData);

            if ($response->successful()) {
                Log::info('Spring Big member updated successfully', [
                    'user_id' => $user->id,
                ]);

                return $response->json();
            }

            Log::error('Spring Big member update failed', [
                'user_id' => $user->id,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Spring Big member update exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
