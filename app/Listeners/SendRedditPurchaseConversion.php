<?php

namespace App\Listeners;

use App\Listeners\Concerns\ResolvesFirstPaidPurchase;
use App\Services\RedditConversionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Fires the server-side Reddit "Purchase" conversion once a subscription's first
 * paid invoice is confirmed by Stripe. Same semantics as the X listener: runs
 * synchronously inside the webhook, never lets a failure break the webhook
 * response, and shares the conversion_id (PaymentIntent id) with the browser
 * Reddit pixel for deduplication.
 */
class SendRedditPurchaseConversion
{
    use ResolvesFirstPaidPurchase;

    public function __construct(private RedditConversionService $reddit) {}

    public function handle(WebhookReceived $event): void
    {
        try {
            if (! $this->reddit->isConfigured()) {
                return;
            }

            $purchase = $this->resolveFirstPaidPurchase($event);

            if ($purchase === null) {
                return;
            }

            $this->send($purchase);
        } catch (\Throwable $e) {
            Log::error('Reddit CAPI purchase listener failed: '.$e->getMessage());
        }
    }

    /**
     * @param  array{user: \App\Models\User, conversionId: ?string, value: float, currency: string, conversionTime: string}  $purchase
     */
    private function send(array $purchase): void
    {
        $conversionId = $purchase['conversionId'];

        $lockKey = $conversionId ? 'reddit-conversion-sent:'.$conversionId : null;

        if ($lockKey && ! Cache::add($lockKey, true, now()->addDay())) {
            return;
        }

        $sent = $this->reddit->sendPurchase($purchase['user'], [
            'value' => $purchase['value'],
            'currency' => $purchase['currency'],
            'conversion_id' => $conversionId,
            'conversion_time' => $purchase['conversionTime'],
        ]);

        if ($lockKey && ! $sent) {
            Cache::forget($lockKey);
        }
    }
}
