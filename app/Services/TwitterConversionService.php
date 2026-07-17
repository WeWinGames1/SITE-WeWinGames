<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Server-side X (Twitter) Ads Conversion API sender.
 *
 * Mirrors the browser pixel events fired via useTwitterPixel.ts. The browser
 * event and this server event share the same event_id + conversion_id so X can
 * deduplicate the two into a single conversion.
 *
 * The X-Pixel-Token is a server-side secret and must never reach the browser.
 */
class TwitterConversionService
{
    private ?string $pixelId;

    private ?string $token;

    private string $apiVersion;

    public function __construct()
    {
        $this->pixelId = config('services.twitter.pixel_id');
        $this->token = config('services.twitter.conversion_token');
        $this->apiVersion = (string) config('services.twitter.api_version', '12');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->pixelId) && ! empty($this->token);
    }

    /**
     * Send the "Subscription Purchase" conversion for a paid subscription.
     *
     * @param  array{value?: float|int|string, currency?: string, conversion_id?: string, twclid?: string, event_source_url?: string, conversion_time?: string, ip_address?: string, user_agent?: string}  $data
     */
    public function sendPurchase(User $user, array $data = []): bool
    {
        return $this->sendConversion(config('services.twitter.events.purchase'), $user, $data);
    }

    /**
     * Send a conversion to the X Ads Conversion API.
     *
     * @param  array{value?: float|int|string, currency?: string, conversion_id?: string, twclid?: string, event_source_url?: string, conversion_time?: string, ip_address?: string, user_agent?: string}  $data
     */
    public function sendConversion(?string $eventId, User $user, array $data = []): bool
    {
        if (! $this->isConfigured() || empty($eventId)) {
            return false;
        }

        $identifiers = $this->buildIdentifiers($user, $data);

        // X requires at least one matching identifier (twclid, hashed email,
        // hashed phone, or ip+user_agent together).
        if (empty($identifiers)) {
            Log::warning('X CAPI: no matching identifier, skipping conversion', [
                'user_id' => $user->id,
                'event_id' => $eventId,
            ]);

            return false;
        }

        $conversion = [
            'conversion_time' => $data['conversion_time'] ?? now()->toIso8601String(),
            'event_id' => $eventId,
            'event_source_url' => $data['event_source_url'] ?? config('app.url'),
            'identifiers' => [$identifiers],
        ];

        if (! empty($data['conversion_id'])) {
            $conversion['conversion_id'] = (string) $data['conversion_id'];
        }

        if (isset($data['value']) && $data['value'] !== '') {
            $conversion['number_items'] = 1;
            $conversion['value'] = (float) $data['value'];
            $conversion['price_currency'] = $data['currency'] ?? 'USD';
        }

        try {
            $response = Http::withHeaders([
                'X-Pixel-Token' => $this->token,
                'Content-Type' => 'application/json',
            ])
                ->connectTimeout(5)
                ->timeout(10)
                ->post($this->endpoint(), [
                    'conversions' => [$conversion],
                ]);

            if ($response->failed()) {
                Log::error('X CAPI Error: '.$response->status().' '.$response->body(), [
                    'user_id' => $user->id,
                    'event_id' => $eventId,
                ]);

                return false;
            }

            Log::info("X CAPI ($eventId) sent successfully for user: {$user->id}");

            return true;
        } catch (\Throwable $e) {
            Log::error('X CAPI Exception: '.$e->getMessage(), [
                'user_id' => $user->id,
                'event_id' => $eventId,
            ]);

            return false;
        }
    }

    private function endpoint(): string
    {
        return "https://ads-api.x.com/{$this->apiVersion}/measurement/conversions/{$this->pixelId}";
    }

    /**
     * Build the identifier object. Only non-empty identifiers are included, and
     * ip_address + user_agent are only sent together (X treats them as a pair).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function buildIdentifiers(User $user, array $data): array
    {
        $identifiers = [];

        if (! empty($data['twclid'])) {
            $identifiers['twclid'] = (string) $data['twclid'];
        }

        if (! empty($user->email)) {
            $identifiers['hashed_email'] = hash('sha256', strtolower(trim($user->email)));
        }

        if (! empty($user->phone)) {
            $phone = $this->normalizePhone($user->phone);

            if ($phone !== '') {
                $identifiers['hashed_phone_number'] = hash('sha256', $phone);
            }
        }

        if (! empty($data['ip_address']) && ! empty($data['user_agent'])) {
            $identifiers['ip_address'] = (string) $data['ip_address'];
            $identifiers['user_agent'] = (string) $data['user_agent'];
        }

        return $identifiers;
    }

    /**
     * Normalize a phone number to E.164 digits (no '+') for hashing, defaulting
     * a bare 10-digit number to US (+1).
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if ($digits === '' || $digits === null) {
            return '';
        }

        if (strlen($digits) === 10) {
            $digits = '1'.$digits;
        }

        return $digits;
    }
}
