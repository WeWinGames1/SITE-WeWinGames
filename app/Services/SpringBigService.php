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

    // External Group settings
    protected bool $externalGroupEnabled;

    protected string $externalGroupBaseUrl;

    protected ?string $externalGroupId;

    protected array $segmentIds;

    public function __construct()
    {
        $this->enabled = (bool) config('services.springbig.enabled', false);
        $this->baseUrl = config('services.springbig.base_url') ?? 'https://production.api.springbig.technology/pos/v1';
        $this->apiKey = config('services.springbig.api_key') ?? '';
        $this->authToken = config('services.springbig.auth_token') ?? '';

        // External Group config
        $this->externalGroupEnabled = (bool) config('services.springbig.external_group_enabled', false);
        $this->externalGroupBaseUrl = config('services.springbig.external_group_base_url') ?? 'https://production.api.springbig.technology/general/v1';
        $this->externalGroupId = config('services.springbig.external_group_id');
        $this->segmentIds = config('services.springbig.segment_ids', []);
    }

    /**
     * Check if Spring Big integration is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled && ! empty($this->apiKey);
    }

    /**
     * Check if External Group integration is enabled
     */
    public function isExternalGroupEnabled(): bool
    {
        return $this->externalGroupEnabled && ! empty($this->apiKey) && ! empty($this->externalGroupId);
    }

    /**
     * Get tier name based on user's subscription
     */
    public function getTierForUser(User $user): string
    {
        // Check for admin override first
        if ($user->admin_override && $user->override_tier) {
            return strtolower($user->override_tier);
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
                return 'canceled';
            }

            return 'free';
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

        return $tier ?? 'free';
    }

    /**
     * Get the custom group name based on user's subscription tier
     * Returns comma-separated string for custom_group_list field
     */
    public function getCustomGroupForUser(User $user): string
    {
        $tier = $this->getTierForUser($user);

        return $tier;
    }

    /**
     * Build headers for POS API calls
     */
    protected function buildHeaders(): array
    {
        $headers = [
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];

        if (! empty($this->authToken)) {
            $headers['AUTH-TOKEN'] = $this->authToken;
        }

        return $headers;
    }

    /**
     * Build headers for External Group API calls
     */
    protected function buildExternalGroupHeaders(): array
    {
        return $this->buildHeaders();
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

            // Determine custom group based on subscription (comma-separated)
            $customGroup = $this->getCustomGroupForUser($user);

            // Build member payload with custom_group_list as comma-separated string
            $memberData = [
                'member' => [
                    'pos_user' => (string) $user->id,
                    'pos_type' => 'wewingames',
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $user->email,
                    'allowed_email' => true,
                    'custom_group_list' => $customGroup,
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

            $response = Http::withHeaders($this->buildHeaders())->post($this->baseUrl.'/members', $memberData);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('Spring Big member created successfully', [
                    'user_id' => $user->id,
                    'springbig_member_id' => $responseData['members'][0]['id'] ?? null,
                    'custom_group_list' => $customGroup,
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

            // Determine custom group based on subscription (comma-separated)
            $customGroup = $this->getCustomGroupForUser($user);

            // Build member payload with custom_group_list
            $memberData = [
                'member' => [
                    'pos_user' => (string) $user->id,
                    'pos_type' => 'wewingames',
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $user->email,
                    'custom_group_list' => $customGroup,
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

            $response = Http::withHeaders($this->buildHeaders())->put($this->baseUrl.'/members', $memberData);

            if ($response->successful()) {
                Log::info('Spring Big member updated successfully', [
                    'user_id' => $user->id,
                    'custom_group_list' => $customGroup,
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

    // =========================================================================
    // External Group API Methods
    // =========================================================================

    /**
     * Create an external group (one-time setup)
     * Returns group ID to store in config
     */
    public function createExternalGroup(string $name, string $description = ''): ?array
    {
        if (! $this->enabled || empty($this->apiKey)) {
            Log::debug('Spring Big disabled, skipping external group creation');

            return null;
        }

        try {
            $response = Http::withHeaders($this->buildExternalGroupHeaders())
                ->post($this->externalGroupBaseUrl.'/external_group', [
                    'group_name' => $name,
                    'group_description' => $description,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Spring Big external group created', [
                    'group_id' => $data['id'] ?? null,
                    'group_name' => $name,
                ]);

                return $data;
            }

            Log::error('Spring Big external group creation failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Spring Big external group creation exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get all external groups
     */
    public function getExternalGroups(): ?array
    {
        if (! $this->enabled || empty($this->apiKey)) {
            return null;
        }

        try {
            $response = Http::withHeaders($this->buildExternalGroupHeaders())
                ->get($this->externalGroupBaseUrl.'/external_group');

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Spring Big get external groups failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Spring Big get external groups exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create segments for all tiers (one-time setup)
     * Returns array of segment data with IDs to store in config
     */
    public function createSegments(): ?array
    {
        if (! $this->enabled || empty($this->apiKey)) {
            return null;
        }

        $tiers = ['free', 'silver', 'gold', 'platinum', 'canceled'];
        $segments = [];

        foreach ($tiers as $tier) {
            $segments[] = [
                'name' => 'WeWinGames '.ucfirst($tier),
                'desc' => ucfirst($tier).' tier members from WeWinGames',
                'recipients' => [
                    'phone_numbers' => [],
                    'email' => [],
                ],
            ];
        }

        try {
            $response = Http::withHeaders($this->buildExternalGroupHeaders())
                ->post($this->externalGroupBaseUrl.'/external_group_segments', [
                    'group_segments' => $segments,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Spring Big segments created', ['response' => $data]);

                return $data;
            }

            Log::error('Spring Big segments creation failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Spring Big segments creation exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get all external group segments
     */
    public function getSegments(): ?array
    {
        if (! $this->enabled || empty($this->apiKey)) {
            return null;
        }

        try {
            $response = Http::withHeaders($this->buildExternalGroupHeaders())
                ->get($this->externalGroupBaseUrl.'/external_group_segments');

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Spring Big get segments failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Spring Big get segments exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Add user to a segment by tier
     */
    public function addUserToSegment(User $user, string $tier): ?array
    {
        if (! $this->isExternalGroupEnabled()) {
            Log::debug('Spring Big external group disabled, skipping segment add');

            return null;
        }

        $segmentId = $this->segmentIds[$tier] ?? null;
        if (! $segmentId) {
            Log::warning('Spring Big segment ID not configured for tier', ['tier' => $tier]);

            return null;
        }

        // Get phone number (10 digits)
        $phoneNumber = null;
        if ($user->phone) {
            $phone = preg_replace('/[^0-9]/', '', $user->phone);
            if (strlen($phone) === 10) {
                $phoneNumber = (int) $phone;
            } elseif (strlen($phone) === 11 && str_starts_with($phone, '1')) {
                $phoneNumber = (int) substr($phone, 1);
            }
        }

        try {
            $payload = [
                'group_segment' => [
                    'add_recipients' => [
                        'phone_numbers' => $phoneNumber ? [$phoneNumber] : [],
                        'email' => $user->email ? [$user->email] : [],
                    ],
                    'remove_recipients' => [
                        'phone_numbers' => [],
                        'email' => [],
                    ],
                ],
            ];

            $response = Http::withHeaders($this->buildExternalGroupHeaders())
                ->put($this->externalGroupBaseUrl.'/external_group_segments/'.$segmentId, $payload);

            if ($response->successful()) {
                Log::info('Spring Big user added to segment', [
                    'user_id' => $user->id,
                    'tier' => $tier,
                    'segment_id' => $segmentId,
                ]);

                return $response->json();
            }

            Log::error('Spring Big add user to segment failed', [
                'user_id' => $user->id,
                'tier' => $tier,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Spring Big add user to segment exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Remove user from a segment
     */
    public function removeUserFromSegment(User $user, string $tier): ?array
    {
        if (! $this->isExternalGroupEnabled()) {
            return null;
        }

        $segmentId = $this->segmentIds[$tier] ?? null;
        if (! $segmentId) {
            Log::warning('Spring Big segment ID not configured for tier', ['tier' => $tier]);

            return null;
        }

        // Get phone number (10 digits)
        $phoneNumber = null;
        if ($user->phone) {
            $phone = preg_replace('/[^0-9]/', '', $user->phone);
            if (strlen($phone) === 10) {
                $phoneNumber = (int) $phone;
            } elseif (strlen($phone) === 11 && str_starts_with($phone, '1')) {
                $phoneNumber = (int) substr($phone, 1);
            }
        }

        try {
            $payload = [
                'group_segment' => [
                    'add_recipients' => [
                        'phone_numbers' => [],
                        'email' => [],
                    ],
                    'remove_recipients' => [
                        'phone_numbers' => $phoneNumber ? [$phoneNumber] : [],
                        'email' => $user->email ? [$user->email] : [],
                    ],
                ],
            ];

            $response = Http::withHeaders($this->buildExternalGroupHeaders())
                ->put($this->externalGroupBaseUrl.'/external_group_segments/'.$segmentId, $payload);

            if ($response->successful()) {
                Log::info('Spring Big user removed from segment', [
                    'user_id' => $user->id,
                    'tier' => $tier,
                    'segment_id' => $segmentId,
                ]);

                return $response->json();
            }

            Log::error('Spring Big remove user from segment failed', [
                'user_id' => $user->id,
                'tier' => $tier,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Spring Big remove user from segment exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Update user's segment when subscription changes
     * Removes from old tier segment, adds to new tier segment
     */
    public function updateUserSegment(User $user, ?string $oldTier = null): ?array
    {
        if (! $this->isExternalGroupEnabled()) {
            return null;
        }

        $newTier = $this->getTierForUser($user);

        // Remove from old tier if provided and different
        if ($oldTier && $oldTier !== $newTier) {
            $this->removeUserFromSegment($user, $oldTier);
        }

        // Add to new tier
        return $this->addUserToSegment($user, $newTier);
    }

    /**
     * Sync user to correct segment based on current subscription
     * Removes from all other tier segments, adds to current tier
     */
    public function syncUserSegment(User $user): ?array
    {
        if (! $this->isExternalGroupEnabled()) {
            return null;
        }

        $currentTier = $this->getTierForUser($user);
        $allTiers = ['free', 'silver', 'gold', 'platinum', 'canceled'];

        // Remove from all other tiers
        foreach ($allTiers as $tier) {
            if ($tier !== $currentTier && ! empty($this->segmentIds[$tier])) {
                $this->removeUserFromSegment($user, $tier);
            }
        }

        // Add to current tier
        return $this->addUserToSegment($user, $currentTier);
    }
}
