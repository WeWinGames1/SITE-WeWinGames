<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Cashier\Events\WebhookReceived;
use Tests\TestCase;

class SendTwitterPurchaseConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.twitter.pixel_id', 'qfwd8');
        config()->set('services.twitter.conversion_token', 'secret-token');
        config()->set('services.twitter.api_version', '12');
        config()->set('services.twitter.events.purchase', 'tw-qfwd8-rdw3t');
    }

    private function makeUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'stripe_id' => 'cus_test123',
            'twclid' => 'tw-click-xyz',
            'landing_url' => 'https://wewingames.com/?twclid=tw-click-xyz',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function invoicePayload(array $overrides = []): array
    {
        return [
            'type' => 'invoice.payment_succeeded',
            'data' => ['object' => array_merge([
                'id' => 'in_test',
                'customer' => 'cus_test123',
                'billing_reason' => 'subscription_create',
                'amount_paid' => 6500,
                'currency' => 'usd',
                'payment_intent' => 'pi_first',
                'created' => 1_700_000_000,
            ], $overrides)],
        ];
    }

    /**
     * Stripe's 2025-03-31 "Basil" release removed payment_intent from the Invoice
     * object; the id now lives on the invoice payments list.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function basilInvoicePayload(array $overrides = []): array
    {
        $payload = $this->invoicePayload($overrides);
        unset($payload['data']['object']['payment_intent']);

        $payload['data']['object']['payments'] = [
            'object' => 'list',
            'data' => [
                ['payment' => ['type' => 'payment_intent', 'payment_intent' => 'pi_first']],
            ],
        ];

        return $payload;
    }

    public function test_it_sends_purchase_conversion_on_first_paid_invoice(): void
    {
        Http::fake(['ads-api.x.com/*' => Http::response(['ok' => true], 200)]);
        $this->makeUser();

        event(new WebhookReceived($this->invoicePayload()));

        Http::assertSent(function ($request) {
            $conversion = $request->data()['conversions'][0];

            return $request->url() === 'https://ads-api.x.com/12/measurement/conversions/qfwd8'
                && $conversion['event_id'] === 'tw-qfwd8-rdw3t'
                && $conversion['conversion_time'] === '2023-11-14T22:13:20.000Z'
                && $conversion['conversion_id'] === 'pi_first'
                && $conversion['value'] === 65.0
                && str_contains($request->body(), '"value":65.0')
                && $conversion['price_currency'] === 'USD'
                && $conversion['event_source_url'] === 'https://wewingames.com/?twclid=tw-click-xyz'
                && $conversion['identifiers'][0] === ['twclid' => 'tw-click-xyz'];
        });
    }

    public function test_it_reads_the_payment_intent_from_a_basil_invoice_payload(): void
    {
        Http::fake(['ads-api.x.com/*' => Http::response(['ok' => true], 200)]);
        $this->makeUser();

        event(new WebhookReceived($this->basilInvoicePayload()));

        // The dedup key must still be the PaymentIntent id the browser pixel
        // used — never the invoice id, which would double-count the purchase.
        Http::assertSent(fn ($request) => $request->data()['conversions'][0]['conversion_id'] === 'pi_first');
    }

    public function test_it_never_falls_back_to_the_invoice_id_as_the_dedup_key(): void
    {
        Http::fake(['ads-api.x.com/*' => Http::response(['ok' => true], 200)]);
        // Force the Stripe re-read fallback to fail so nothing resolves the id.
        config()->set('cashier.secret', '');
        $this->makeUser();

        $payload = $this->invoicePayload();
        unset($payload['data']['object']['payment_intent']);

        event(new WebhookReceived($payload));

        Http::assertSent(fn ($request) => ! array_key_exists('conversion_id', $request->data()['conversions'][0]));
    }

    public function test_it_sends_the_buyers_ip_and_user_agent_as_extra_identifiers(): void
    {
        Http::fake(['ads-api.x.com/*' => Http::response(['ok' => true], 200)]);
        $this->makeUser([
            'checkout_ip_address' => '203.0.113.9',
            'checkout_user_agent' => 'Mozilla/5.0 (iPhone)',
        ]);

        event(new WebhookReceived($this->invoicePayload()));

        Http::assertSent(function ($request) {
            $identifiers = $request->data()['conversions'][0]['identifiers'];

            return in_array(
                ['ip_address' => '203.0.113.9', 'user_agent' => 'Mozilla/5.0 (iPhone)'],
                $identifiers,
                true
            );
        });
    }

    public function test_it_sends_immediately_invoiced_plan_upgrades(): void
    {
        Http::fake(['ads-api.x.com/*' => Http::response(['ok' => true], 200)]);
        $this->makeUser();

        event(new WebhookReceived($this->invoicePayload([
            'billing_reason' => 'subscription_update',
            'amount_paid' => 2000,
            'payment_intent' => 'pi_upgrade',
        ])));

        Http::assertSent(function ($request) {
            $conversion = $request->data()['conversions'][0];

            return $conversion['conversion_id'] === 'pi_upgrade' && $conversion['value'] === 20.0;
        });
    }

    public function test_it_ignores_renewals(): void
    {
        Http::fake();
        $this->makeUser();

        event(new WebhookReceived($this->invoicePayload(['billing_reason' => 'subscription_cycle'])));

        Http::assertNothingSent();
    }

    public function test_it_ignores_free_or_trial_invoices(): void
    {
        Http::fake();
        $this->makeUser();

        event(new WebhookReceived($this->invoicePayload(['amount_paid' => 0])));

        Http::assertNothingSent();
    }

    public function test_it_is_idempotent_across_webhook_retries(): void
    {
        Http::fake(['ads-api.x.com/*' => Http::response(['ok' => true], 200)]);
        $this->makeUser();

        $payload = $this->invoicePayload();
        event(new WebhookReceived($payload));
        event(new WebhookReceived($payload));

        Http::assertSentCount(1);
    }

    public function test_it_releases_lock_and_retries_after_a_failed_send(): void
    {
        Http::fake([
            'ads-api.x.com/*' => Http::sequence()
                ->push('server error', 500)
                ->push(['ok' => true], 200),
        ]);
        $this->makeUser();
        $payload = $this->invoicePayload();

        event(new WebhookReceived($payload)); // fails -> lock released
        event(new WebhookReceived($payload)); // retries -> succeeds

        Http::assertSentCount(2);
    }

    public function test_it_swallows_cache_failures_without_failing_the_webhook(): void
    {
        Http::fake();
        $this->makeUser();
        // Simulate a Redis blip on the idempotency claim.
        Cache::shouldReceive('add')->andThrow(new \RuntimeException('cache down'));

        // Must not throw out of the webhook listener.
        event(new WebhookReceived($this->invoicePayload()));

        Http::assertNothingSent();
    }

    public function test_it_does_nothing_for_unknown_customer(): void
    {
        Http::fake();
        // No user with this stripe_id exists.

        event(new WebhookReceived($this->invoicePayload(['customer' => 'cus_missing'])));

        Http::assertNothingSent();
    }
}
