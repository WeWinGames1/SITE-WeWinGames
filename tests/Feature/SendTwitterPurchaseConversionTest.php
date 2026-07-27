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

    private function makeUser(): User
    {
        return User::factory()->create([
            'stripe_id' => 'cus_test123',
            'twclid' => 'tw-click-xyz',
            'landing_url' => 'https://wewingames.com/?twclid=tw-click-xyz',
        ]);
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
                'customer' => 'cus_test123',
                'billing_reason' => 'subscription_create',
                'amount_paid' => 6500,
                'currency' => 'usd',
                'payment_intent' => 'pi_first',
                'created' => 1_700_000_000,
            ], $overrides)],
        ];
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
                && $conversion['identifiers'][0]['twclid'] === 'tw-click-xyz';
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
