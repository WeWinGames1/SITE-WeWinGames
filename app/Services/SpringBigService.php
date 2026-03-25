<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpringBigService
{
    protected string $apiKey;

    protected string $merchantId;

    protected bool $enabled;

    protected string $baseUrl = 'https://gamma.api.springbig.technology/pos/v1';

    public function __construct()
    {
        $this->apiKey = config('services.springbig.api_key') ?? '';
        $this->merchantId = config('services.springbig.merchant_id') ?? '';
        $this->enabled = (bool) config('services.springbig.enabled', false);
    }

    /**
     * Check if Spring Big integration is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled && ! empty($this->apiKey);
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
            if (! empty($this->merchantId)) {
                $headers['AUTH-TOKEN'] = $this->merchantId;
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

            $memberData = [
                'member' => [
                    'pos_user' => (string) $user->id,
                    'pos_type' => 'wewingames',
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $user->email,
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

            if (! empty($this->merchantId)) {
                $headers['AUTH-TOKEN'] = $this->merchantId;
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
