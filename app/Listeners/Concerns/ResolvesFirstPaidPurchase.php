<?php

namespace App\Listeners\Concerns;

use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;

trait ResolvesFirstPaidPurchase
{
    /**
     * Resolve a Stripe webhook into a first-paid-subscription purchase, or null
     * if it is not one.
     *
     * Scoped to the first paid charge (billing_reason "subscription_create" with
     * amount_paid > 0) so it never matches renewals ("subscription_cycle"),
     * failed payments, or free / 100%-off / trial-only invoices. The conversion
     * id is the Stripe PaymentIntent id, shared with the browser pixel for dedup.
     *
     * @return array{user: User, conversionId: ?string, value: float, currency: string, conversionTime: string}|null
     */
    protected function resolveFirstPaidPurchase(WebhookReceived $event): ?array
    {
        if (($event->payload['type'] ?? null) !== 'invoice.payment_succeeded') {
            return null;
        }

        $invoice = $event->payload['data']['object'] ?? [];

        if (($invoice['billing_reason'] ?? null) !== 'subscription_create') {
            return null;
        }

        if ((int) ($invoice['amount_paid'] ?? 0) <= 0) {
            return null;
        }

        $stripeId = $invoice['customer'] ?? null;

        if (! $stripeId) {
            return null;
        }

        $user = Cashier::findBillable($stripeId);

        if (! $user) {
            return null;
        }

        return [
            'user' => $user,
            'conversionId' => $invoice['payment_intent'] ?? $invoice['id'] ?? null,
            'value' => ((int) $invoice['amount_paid']) / 100,
            'currency' => strtoupper($invoice['currency'] ?? 'usd'),
            'conversionTime' => isset($invoice['created'])
                ? Carbon::createFromTimestamp($invoice['created'])->toIso8601String()
                : now()->toIso8601String(),
        ];
    }
}
