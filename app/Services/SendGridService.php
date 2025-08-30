<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendGridService
{
    protected string $apiKey;

    protected string $baseUrl = 'https://api.sendgrid.com/v3';

    public function __construct()
    {
        $this->apiKey = config('services.sendgrid.api_key', '');
    }

    /**
     * Sync user contact to SendGrid
     */
    public function syncContact(User $user): void
    {
        if (empty($this->apiKey)) {
            Log::warning('SendGrid API key not configured');

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
                Log::error('SendGrid contact sync failed', [
                    'user_id' => $user->id,
                    'response' => $response->json(),
                    'status' => $response->status(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SendGrid contact sync exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
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
        if (empty($this->apiKey)) {
            return;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->put($this->baseUrl."/marketing/lists/{$listId}/contacts", [
                    'contact_ids' => [$this->getContactId($user->email)],
                ]);

            if (! $response->successful()) {
                Log::error('Failed to add contact to SendGrid list', [
                    'user_id' => $user->id,
                    'list_id' => $listId,
                    'response' => $response->json(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SendGrid add to list exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
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
