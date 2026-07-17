<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Server-side Reddit Conversions API sender.
 *
 * Mirrors the browser Reddit pixel Purchase event. Browser and server events
 * share the same conversion_id (Stripe PaymentIntent id) so Reddit dedupes them
 * into a single conversion. The conversion token must stay server-side only.
 */
class RedditConversionService
{
    private ?string $pixelId;

    private ?string $token;

    private string $baseUrl = 'https://ads-api.reddit.com/api/v2.0/conversions/events/';

    public function __construct()
    {
        $this->pixelId = config('services.reddit.pixel_id');
        $this->token = config('services.reddit.conversion_token');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->pixelId) && ! empty($this->token);
    }

    /**
     * @param  array{value?: float|int|string, currency?: string, conversion_id?: string, click_id?: string, conversion_time?: string}  $data
     */
    public function sendPurchase(User $user, array $data = []): bool
    {
        return $this->sendEvent('Purchase', $user, $data);
    }

    /**
     * @param  array{value?: float|int|string, currency?: string, conversion_id?: string, click_id?: string, conversion_time?: string}  $data
     */
    public function sendEvent(string $eventName, User $user, array $data = []): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $event = [
            'event_at' => $data['conversion_time'] ?? now()->toIso8601String(),
            'event_type' => ['tracking_type' => $eventName],
            'user' => $this->buildUser($user),
        ];

        if (! empty($data['click_id'])) {
            $event['click_id'] = (string) $data['click_id'];
        }

        $metadata = array_filter([
            'currency' => $data['currency'] ?? null,
            'value_decimal' => isset($data['value']) && $data['value'] !== '' ? (float) $data['value'] : null,
            'item_count' => isset($data['value']) && $data['value'] !== '' ? 1 : null,
            'conversion_id' => $data['conversion_id'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        if (! empty($metadata)) {
            $event['event_metadata'] = $metadata;
        }

        try {
            $response = Http::withToken($this->token)
                ->connectTimeout(5)
                ->timeout(10)
                ->post($this->baseUrl.$this->pixelId, [
                    'test_mode' => config('app.env') !== 'production',
                    'events' => [$event],
                ]);

            if ($response->failed()) {
                Log::error('Reddit CAPI Error: '.$response->status().' '.$response->body(), [
                    'user_id' => $user->id,
                ]);

                return false;
            }

            Log::info("Reddit CAPI ($eventName) sent successfully for user: {$user->id}");

            return true;
        } catch (\Throwable $e) {
            Log::error('Reddit CAPI Exception: '.$e->getMessage(), [
                'user_id' => $user->id,
            ]);

            return false;
        }
    }

    /**
     * @return array<string, string>
     */
    private function buildUser(User $user): array
    {
        $matched = [];

        if (! empty($user->email)) {
            $matched['email'] = hash('sha256', strtolower(trim($user->email)));
        }

        if (! empty($user->phone)) {
            $digits = preg_replace('/[^0-9]/', '', $user->phone);

            if ($digits !== '' && $digits !== null) {
                $matched['phone_number'] = hash('sha256', $digits);
            }
        }

        return $matched;
    }
}
