<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendGridService
{
    protected string $apiKey;

    protected bool $marketingEnabled;

    protected string $baseUrl = 'https://api.sendgrid.com/v3';

    public function __construct()
    {
        $this->apiKey = config('services.sendgrid.api_key', '');
        $this->marketingEnabled = config('services.sendgrid.marketing_enabled', false);
    }

    /**
     * Check if marketing features are enabled
     */
    public function isMarketingEnabled(): bool
    {
        return $this->marketingEnabled && ! empty($this->apiKey);
    }

    /**
     * Sync user contact to SendGrid Marketing
     */
    public function syncContact(User $user): void
    {
        if (! $this->isMarketingEnabled()) {
            return;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->put($this->baseUrl.'/marketing/contacts', [
                    'contacts' => [
                        [
                            'email' => $user->email,
                            'first_name' => explode(' ', $user->name)[0] ?? '',
                            'last_name' => explode(' ', $user->name, 2)[1] ?? '',
                            'custom_fields' => [
                                'discord_username' => $user->discord_username ?? '',
                                'registration_date' => $user->created_at->toDateString(),
                                'subscription_status' => $user->subscribed() ? 'active' : 'free',
                                'subscription_tier' => $user->getCurrentTier() ?? 'free',
                                'affiliate_code' => $user->affiliate?->code ?? '',
                            ],
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                $status = $response->status();
                $responseData = $response->json();

                // Handle specific error cases
                if ($status === 403) {
                    // Permission error - API key missing Marketing scope
                    Log::error('SendGrid API key missing Marketing permission scope. Please update your API key permissions in SendGrid.', [
                        'user_id' => $user->id,
                        'status' => $status,
                    ]);
                    \Sentry\captureMessage('SendGrid API key missing Marketing permission scope', \Sentry\Severity::error());
                } else {
                    Log::error('SendGrid contact sync failed', [
                        'user_id' => $user->id,
                        'response' => $responseData,
                        'status' => $status,
                    ]);
                    \Sentry\captureMessage("SendGrid contact sync failed with status {$status}", \Sentry\Severity::error());
                }
            }
        } catch (\Exception $e) {
            Log::error('SendGrid contact sync exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
            // Don't rethrow - SendGrid sync is non-critical and shouldn't break user flows
        }
    }

    /**
     * Update contact when subscription changes
     */
    public function updateContactSubscription(User $user): void
    {
        $this->syncContact($user);
    }

    /**
     * Add contact to list
     */
    public function addToList(User $user, string $listId): void
    {
        if (! $this->isMarketingEnabled()) {
            return;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->put($this->baseUrl."/marketing/lists/{$listId}/contacts", [
                    'contact_ids' => [$this->getContactId($user->email)],
                ]);

            if (! $response->successful()) {
                $status = $response->status();

                if ($status === 403) {
                    Log::error('SendGrid API key missing Marketing permission scope for list operations.', [
                        'user_id' => $user->id,
                        'list_id' => $listId,
                        'status' => $status,
                    ]);
                } else {
                    Log::error('Failed to add contact to SendGrid list', [
                        'user_id' => $user->id,
                        'list_id' => $listId,
                        'response' => $response->json(),
                        'status' => $status,
                    ]);
                }
                \Sentry\captureMessage("SendGrid add to list failed with status {$status}", \Sentry\Severity::error());
            }
        } catch (\Exception $e) {
            Log::error('SendGrid add to list exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
        }
    }

    /**
     * Get contact ID by email
     */
    protected function getContactId(string $email): ?string
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->post($this->baseUrl.'/marketing/contacts/search', [
                    'query' => "email = '{$email}'",
                ]);

            if ($response->successful() && isset($response->json()['result'][0]['id'])) {
                return $response->json()['result'][0]['id'];
            }
        } catch (\Exception $e) {
            Log::error('Failed to get SendGrid contact ID', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
