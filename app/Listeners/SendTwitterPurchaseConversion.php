<?php

namespace App\Listeners;

use App\Listeners\Concerns\ResolvesPaidPurchase;
use App\Services\TwitterConversionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Fires the server-side X (Twitter) "Subscription Purchase" conversion once a
 * subscription's paid invoice is confirmed by Stripe.
 *
 * Runs synchronously inside the Stripe webhook request (like the app's other
 * WebhookReceived listeners) so it does not depend on a queue worker being
 * online. The Conversion API call is time-bounded and swallows its own errors,
 * and the whole listener is wrapped so a cache/DB hiccup can never fail the
 * webhook response. The conversion_id (PaymentIntent id) matches the browser
 * pixel event so X deduplicates the browser + server events into one conversion.
 */
class SendTwitterPurchaseConversion
{
    use ResolvesPaidPurchase;

    public function __construct(private TwitterConversionService $twitter) {}

    public function handle(WebhookReceived $event): void
    {
        // Tracking-only: a cache/DB hiccup here must never bubble up and fail
        // Cashier's webhook response (which would block its own state sync and
        // trigger a Stripe redelivery).
        try {
            if (! $this->twitter->isConfigured()) {
                return;
            }

            $purchase = $this->resolvePaidPurchase($event);

            if ($purchase === null) {
                return;
            }

            $this->send($purchase);
        } catch (\Throwable $e) {
            Log::error('X CAPI purchase listener failed: '.$e->getMessage());
        }
    }

    /**
     * @param  array{user: \App\Models\User, conversionId: ?string, invoiceId: ?string, value: float, currency: string, conversionTime: string}  $purchase
     */
    private function send(array $purchase): void
    {
        $conversionId = $purchase['conversionId'];

        // Concurrency + Stripe-retry guard: claim this transaction before sending
        // so duplicate/simultaneous webhooks don't double-send. Keyed on the
        // invoice when the PaymentIntent could not be resolved, so the claim
        // still holds for a purchase that ends up sent without a dedup id.
        $lockReference = $conversionId ?: $purchase['invoiceId'];
        $lockKey = $lockReference ? 'twitter-conversion-sent:'.$lockReference : null;

        if ($lockKey && ! Cache::add($lockKey, true, now()->addDay())) {
            return;
        }

        $user = $purchase['user'];

        $sent = $this->twitter->sendPurchase($user, [
            'value' => $purchase['value'],
            'currency' => $purchase['currency'],
            'conversion_id' => $conversionId,
            'twclid' => $user->twclid,
            'event_source_url' => $user->landing_url ?: config('app.url'),
            'conversion_time' => $purchase['conversionTime'],
            'ip_address' => $user->checkout_ip_address,
            'user_agent' => $user->checkout_user_agent,
        ]);

        // Release the lock on a transient failure so a webhook redelivery or
        // manual replay can retry instead of being permanently deduped away.
        if ($lockKey && ! $sent) {
            Cache::forget($lockKey);
        }
    }
}
