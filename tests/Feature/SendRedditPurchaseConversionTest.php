<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Cashier\Events\WebhookReceived;
use Tests\TestCase;

class SendRedditPurchaseConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.reddit.pixel_id', 'a2_reddit');
        config()->set('services.reddit.conversion_token', 'reddit-token');
        // Keep the X listener dormant for these assertions.
        config()->set('services.twitter.pixel_id', null);
        config()->set('services.twitter.conversion_token', null);
    }

    private function makeUser(): User
    {
        return User::factory()->create([
            'stripe_id' => 'cus_reddit',
            'email' => 'reddit@example.com',
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
                'customer' => 'cus_reddit',
                'billing_reason' => 'subscription_create',
                'amount_paid' => 6500,
                'currency' => 'usd',
                'payment_intent' => 'pi_first',
                'created' => 1_700_000_000,
            ], $overrides)],
        ];
    }

    public function test_it_sends_reddit_purchase_on_first_paid_invoice(): void
    {
        Http::fake(['ads-api.reddit.com/*' => Http::response(['ok' => true], 200)]);
        $this->makeUser();

        event(new WebhookReceived($this->invoicePayload()));

        Http::assertSent(function ($request) {
            $event = $request->data()['events'][0];

            return str_contains($request->url(), 'ads-api.reddit.com')
                && $event['event_type']['tracking_type'] === 'Purchase'
                && $event['event_metadata']['conversion_id'] === 'pi_first'
                && $event['event_metadata']['value_decimal'] === 65.0
                && $event['event_metadata']['currency'] === 'USD'
                && $event['user']['email'] === hash('sha256', 'reddit@example.com');
        });
    }

    public function test_it_ignores_renewals(): void
    {
        Http::fake();
        $this->makeUser();

        event(new WebhookReceived($this->invoicePayload(['billing_reason' => 'subscription_cycle'])));

        Http::assertNothingSent();
    }

    public function test_it_ignores_free_invoices(): void
    {
        Http::fake();
        $this->makeUser();

        event(new WebhookReceived($this->invoicePayload(['amount_paid' => 0])));

        Http::assertNothingSent();
    }

    public function test_it_is_idempotent_across_webhook_retries(): void
    {
        Http::fake(['ads-api.reddit.com/*' => Http::response(['ok' => true], 200)]);
        $this->makeUser();

        $payload = $this->invoicePayload();
        event(new WebhookReceived($payload));
        event(new WebhookReceived($payload));

        Http::assertSentCount(1);
    }
}
