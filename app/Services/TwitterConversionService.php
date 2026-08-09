<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
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
        if (! $this->isConfigured()) {
            return false;
        }

        $conversion = $this->buildConversion($eventId, $user, $data);

        if ($conversion === null) {
            return false;
        }

        try {
            // JSON_PRESERVE_ZERO_FRACTION keeps whole-number values as doubles
            // ("value": 65.0, not 65) — X rejects integer literals for value.
            $response = Http::withHeaders([
                'X-Pixel-Token' => $this->token,
            ])
                ->connectTimeout(5)
                ->timeout(10)
                ->withBody(
                    (string) json_encode(['conversions' => [$conversion]], JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR),
                    'application/json'
                )
                ->post($this->endpoint());

            if ($response->failed()) {
                Log::channel('capi')->error('X CAPI Error: '.$response->status().' '.$response->body(), [
                    'user_id' => $user->id,
                    'event_id' => $eventId,
                ]);

                return false;
            }

            Log::channel('capi')->info("X CAPI ($eventId) sent successfully for user: {$user->id}", [
                'conversion_id' => $conversion['conversion_id'] ?? null,
                'identifiers' => array_map(array_key_first(...), $conversion['identifiers']),
                'value' => $conversion['value'] ?? null,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::channel('capi')->error('X CAPI Exception: '.$e->getMessage(), [
                'user_id' => $user->id,
                'event_id' => $eventId,
            ]);

            return false;
        }
    }

    /**
     * Build the request body for a purchase conversion without sending it —
     * used by the twitter:test-conversion command's dry run.
     *
     * @param  array<string, mixed>  $data
     * @return array{configured: bool, endpoint: ?string, payload: ?array<string, mixed>}
     */
    public function previewPurchase(User $user, array $data = []): array
    {
        $conversion = $this->buildConversion(config('services.twitter.events.purchase'), $user, $data);

        return [
            'configured' => $this->isConfigured(),
            'endpoint' => $this->isConfigured() ? $this->endpoint() : null,
            'payload' => $conversion ? ['conversions' => [$conversion]] : null,
        ];
    }

    public function endpoint(): string
    {
        return "https://ads-api.x.com/{$this->apiVersion}/measurement/conversions/{$this->pixelId}";
    }

    /**
     * Build a single conversion object, or null when the event id is missing or
     * the user has no matching identifier (X requires at least one).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function buildConversion(?string $eventId, User $user, array $data): ?array
    {
        if (empty($eventId)) {
            return null;
        }

        $identifiers = $this->buildIdentifiers($user, $data);

        if (empty($identifiers)) {
            Log::channel('capi')->warning('X CAPI: no matching identifier, skipping conversion', [
                'user_id' => $user->id,
                'event_id' => $eventId,
            ]);

            return null;
        }

        $conversion = [
            'conversion_time' => $this->formatConversionTime($data['conversion_time'] ?? null),
            'event_id' => $eventId,
            'event_source_url' => $data['event_source_url'] ?? config('app.url'),
            'identifiers' => $identifiers,
        ];

        if (! empty($data['conversion_id'])) {
            $conversion['conversion_id'] = (string) $data['conversion_id'];
        }

        if (isset($data['value']) && $data['value'] !== '') {
            $conversion['number_items'] = 1;
            $conversion['value'] = (float) $data['value'];
            $conversion['price_currency'] = $data['currency'] ?? 'USD';
        }

        return $conversion;
    }

    /**
     * X rejects offset-style ISO-8601 — conversion_time must be UTC with
     * milliseconds and a literal Z suffix (yyyy-MM-ddTHH:mm:ss.SSSZ).
     */
    private function formatConversionTime(?string $time): string
    {
        $carbon = $time ? Carbon::parse($time) : now();

        return $carbon->utc()->format('Y-m-d\TH:i:s.v\Z');
    }

    /**
     * Build the identifiers list.
     *
     * X requires one identifier type per object — the array is how multiple
     * identifiers are supplied, not multiple keys in a single object. More
     * objects means a higher match rate. ip_address + user_agent are the one
     * exception: X treats them as a pair that must share an object, and they
     * only count alongside at least one primary identifier.
     *
     * @param  array<string, mixed>  $data
     * @return list<array<string, string>>
     */
    private function buildIdentifiers(User $user, array $data): array
    {
        $identifiers = [];

        if (! empty($data['twclid'])) {
            $identifiers[] = ['twclid' => (string) $data['twclid']];
        }

        if (! empty($user->email)) {
            $identifiers[] = ['hashed_email' => hash('sha256', strtolower(trim($user->email)))];
        }

        if (! empty($user->phone)) {
            $phone = $this->normalizePhone($user->phone);

            if ($phone !== '') {
                $identifiers[] = ['hashed_phone_number' => hash('sha256', $phone)];
            }
        }

        if ($identifiers !== [] && ! empty($data['ip_address']) && ! empty($data['user_agent'])) {
            $identifiers[] = [
                'ip_address' => (string) $data['ip_address'],
                'user_agent' => (string) $data['user_agent'],
            ];
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
