<?php

namespace App\Listeners\Concerns;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;

trait ResolvesPaidPurchase
{
    /**
     * Billing reasons that represent money actually changing hands for a new or
     * upgraded plan. Renewals ("subscription_cycle") are deliberately excluded —
     * ad platforms should only see acquisition, not recurring revenue.
     *
     * @var list<string>
     */
    private array $purchaseBillingReasons = ['subscription_create', 'subscription_update'];

    /**
     * Resolve a Stripe webhook into a paid subscription purchase, or null if it
     * is not one.
     *
     * Scoped to invoices with amount_paid > 0 whose billing reason is a new
     * subscription or an immediately-invoiced plan change, so it never matches
     * renewals, failed payments, or free / 100%-off / trial-only invoices.
     *
     * @return array{user: User, conversionId: ?string, invoiceId: ?string, value: float, currency: string, conversionTime: string}|null
     */
    protected function resolvePaidPurchase(WebhookReceived $event): ?array
    {
        if (($event->payload['type'] ?? null) !== 'invoice.payment_succeeded') {
            return null;
        }

        $invoice = $event->payload['data']['object'] ?? [];

        if (! in_array($invoice['billing_reason'] ?? null, $this->purchaseBillingReasons, true)) {
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
            'conversionId' => $this->resolvePaymentIntentId($invoice),
            'invoiceId' => $invoice['id'] ?? null,
            'value' => ((int) $invoice['amount_paid']) / 100,
            'currency' => strtoupper($invoice['currency'] ?? 'usd'),
            'conversionTime' => isset($invoice['created'])
                ? Carbon::createFromTimestamp($invoice['created'])->toIso8601String()
                : now()->toIso8601String(),
        ];
    }

    /**
     * Resolve the Stripe PaymentIntent id for an invoice, across API versions.
     *
     * This id is the deduplication key shared with the browser pixels, which get
     * theirs from Cashier's latestPayment(). It must be the PaymentIntent id and
     * nothing else — falling back to the invoice id would hand the ad platforms
     * two different keys for one purchase and double-count it.
     *
     * Stripe's 2025-03-31 "Basil" release removed payment_intent from the Invoice
     * object in favour of the invoice payments list, so the webhook payload shape
     * depends on the API version configured on the webhook endpoint. Both shapes
     * are read here, and anything else is resolved by re-reading the invoice
     * through Cashier's own pinned API version, which still exposes the field.
     *
     * @param  array<string, mixed>  $invoice
     */
    private function resolvePaymentIntentId(array $invoice): ?string
    {
        if ($id = $this->extractId($invoice['payment_intent'] ?? null)) {
            return $id;
        }

        foreach ($invoice['payments']['data'] ?? [] as $payment) {
            if ($id = $this->extractId($payment['payment']['payment_intent'] ?? null)) {
                return $id;
            }
        }

        return $this->fetchPaymentIntentIdFromStripe($invoice['id'] ?? null);
    }

    /**
     * Re-read the invoice through Cashier's pinned API version (which predates
     * the Basil removal) to recover the PaymentIntent id.
     *
     * This is a synchronous Stripe call inside the webhook request, so it is
     * logged every time: if it shows up on every purchase, the webhook payload
     * is not inlining the ids and the extra round trip is the steady state
     * rather than an edge case. The result is memoized for the request because
     * the X and Reddit listeners both resolve the same invoice — without it one
     * webhook would make the same call twice.
     */
    private function fetchPaymentIntentIdFromStripe(?string $invoiceId): ?string
    {
        if (! $invoiceId) {
            return null;
        }

        $memoKey = 'capi.payment-intent.'.$invoiceId;

        if (app()->bound($memoKey)) {
            return app()->make($memoKey)['id'];
        }

        try {
            $paymentIntentId = $this->extractId(Cashier::stripe()->invoices->retrieve($invoiceId)->payment_intent ?? null);

            Log::channel('capi')->info('CAPI: resolved PaymentIntent via Stripe re-read', [
                'invoice_id' => $invoiceId,
                'payment_intent_id' => $paymentIntentId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('capi')->warning('CAPI: could not resolve PaymentIntent for invoice', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            $paymentIntentId = null;
        }

        // Wrapped: the container treats a bare null instance as unbound, which
        // would re-issue the call for the second listener.
        app()->instance($memoKey, ['id' => $paymentIntentId]);

        return $paymentIntentId;
    }

    /**
     * Stripe returns these fields either as a bare id string or as an expanded
     * object, depending on the call.
     */
    private function extractId(mixed $value): ?string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        $id = match (true) {
            is_array($value) => $value['id'] ?? null,
            is_object($value) => $value->id ?? null,
            default => null,
        };

        return is_string($id) && $id !== '' ? $id : null;
    }
}
