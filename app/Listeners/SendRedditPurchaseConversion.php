<?php

namespace App\Listeners;

use App\Listeners\Concerns\ResolvesPaidPurchase;
use App\Services\RedditConversionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Fires the server-side Reddit "Purchase" conversion once a subscription's paid
 * invoice is confirmed by Stripe. Same semantics as the X listener: runs
 * synchronously inside the webhook, never lets a failure break the webhook
 * response, and shares the conversion_id (PaymentIntent id) with the browser
 * Reddit pixel for deduplication.
 */
class SendRedditPurchaseConversion
{
    use ResolvesPaidPurchase;

    public function __construct(private RedditConversionService $reddit) {}

    public function handle(WebhookReceived $event): void
    {
        try {
            if (! $this->reddit->isConfigured()) {
                return;
            }

            $purchase = $this->resolvePaidPurchase($event);

            if ($purchase === null) {
                return;
            }

            $this->send($purchase);
        } catch (\Throwable $e) {
            Log::error('Reddit CAPI purchase listener failed: '.$e->getMessage());
        }
    }

    /**
     * @param  array{user: \App\Models\User, conversionId: ?string, invoiceId: ?string, value: float, currency: string, conversionTime: string}  $purchase
     */
    private function send(array $purchase): void
    {
        $conversionId = $purchase['conversionId'];

        $lockReference = $conversionId ?: $purchase['invoiceId'];
        $lockKey = $lockReference ? 'reddit-conversion-sent:'.$lockReference : null;

        if ($lockKey && ! Cache::add($lockKey, true, now()->addDay())) {
            return;
        }

        $user = $purchase['user'];

        $sent = $this->reddit->sendPurchase($user, [
            'value' => $purchase['value'],
            'currency' => $purchase['currency'],
            'conversion_id' => $conversionId,
            'conversion_time' => $purchase['conversionTime'],
            'ip_address' => $user->checkout_ip_address,
            'user_agent' => $user->checkout_user_agent,
        ]);

        if ($lockKey && ! $sent) {
            Cache::forget($lockKey);
        }
    }
}
