<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RedditConversionService
{
    private string $pixelId;
    private string $token;
    private string $baseUrl = 'https://ads-api.reddit.com/api/v2.0/conversions/events/';

    public function __construct()
    {
        $this->pixelId = config('services.reddit.pixel_id');
        $this->token = config('services.reddit.conversion_token');
    }

    public function sendEvent(string $eventName, User $user, array $additionalData = [])
    {
        if (empty($this->pixelId) || empty($this->token)) {
            return;
        }

        try {
            $payload = [
                'test_mode' => config('app.env') !== 'production',
                'events' => [
                    [
                        'event_at' => now()->toIso8601String(),
                        'event_type' => [
                            'tracking_type' => $eventName,
                        ],
                        'user' => [
                            'email' => hash('sha256', strtolower(trim($user->email))),
                        ],
                    ],
                ],
            ];

            if ($user->phone) {
                // Remove non-numeric characters for hashing
                $phone = preg_replace('/[^0-9]/', '', $user->phone);
                $payload['events'][0]['user']['phone'] = hash('sha256', $phone);
            }

            // Merge additional data (like currency, value) into metadata
            if (!empty($additionalData)) {
                $payload['events'][0]['metadata'] = $additionalData;
            }

            $response = Http::withToken($this->token)
                ->post($this->baseUrl . $this->pixelId, $payload);

            if ($response->failed()) {
                Log::error('Reddit CAPI Error: ' . $response->body());
            } else {
                Log::info("Reddit CAPI ($eventName) sent successfully for user: {$user->id}");
            }

        } catch (\Exception $e) {
            Log::error('Reddit CAPI Exception: ' . $e->getMessage());
        }
    }
}
