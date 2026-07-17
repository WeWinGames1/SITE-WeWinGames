<?php

namespace App\Listeners;

use App\Services\TwitterConversionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Fires the server-side X (Twitter) "Subscription Purchase" conversion once a
 * subscription's first paid invoice is confirmed by Stripe.
 *
 * Runs synchronously inside the Stripe webhook request (like the app's other
 * WebhookReceived listeners) so it does not depend on a queue worker being
 * online. The Conversion API call is time-bounded and swallows its own errors,
 * so it can neither hang nor fail the webhook response.
 *
 * Deliberately scoped to the first paid charge (billing_reason
 * "subscription_create" with amount_paid > 0) so it never counts automatic
 * renewals, failed payments, or free / 100%-off / trial-only invoices. The
 * conversion_id (PaymentIntent id) matches the browser pixel event so X
 * deduplicates the browser + server events into one conversion.
 */
class SendTwitterPurchaseConversion
{
    public function __construct(private TwitterConversionService $twitter) {}

    public function handle(WebhookReceived $event): void
    {
        if (($event->payload['type'] ?? null) !== 'invoice.payment_succeeded') {
            return;
        }

        if (! $this->twitter->isConfigured()) {
            return;
        }

        $invoice = $event->payload['data']['object'] ?? [];

        // First paid charge only — excludes renewals ("subscription_cycle"),
        // manual invoices, and subscription updates.
        if (($invoice['billing_reason'] ?? null) !== 'subscription_create') {
            return;
        }

        // Excludes free / 100%-off / trial-only invoices where nothing was collected.
        if ((int) ($invoice['amount_paid'] ?? 0) <= 0) {
            return;
        }

        $stripeId = $invoice['customer'] ?? null;

        if (! $stripeId) {
            return;
        }

        $user = Cashier::findBillable($stripeId);

        if (! $user) {
            return;
        }

        $conversionId = $invoice['payment_intent'] ?? $invoice['id'] ?? null;

        // Concurrency + Stripe-retry guard: claim this transaction before sending
        // so duplicate/simultaneous webhooks don't double-send.
        $lockKey = $conversionId ? 'twitter-conversion-sent:'.$conversionId : null;

        if ($lockKey && ! Cache::add($lockKey, true, now()->addDay())) {
            return;
        }

        $sent = $this->twitter->sendPurchase($user, [
            'value' => ((int) $invoice['amount_paid']) / 100,
            'currency' => strtoupper($invoice['currency'] ?? 'usd'),
            'conversion_id' => $conversionId,
            'twclid' => $user->twclid,
            'event_source_url' => $user->landing_url ?: config('app.url'),
            'conversion_time' => isset($invoice['created'])
                ? Carbon::createFromTimestamp($invoice['created'])->toIso8601String()
                : now()->toIso8601String(),
        ]);

        // Release the lock on a transient failure so a webhook redelivery or
        // manual replay can retry instead of being permanently deduped away.
        if ($lockKey && ! $sent) {
            Cache::forget($lockKey);
        }
    }
}
