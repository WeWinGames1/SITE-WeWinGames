<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\TwitterConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TwitterConversionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function configure(): void
    {
        config()->set('services.twitter.pixel_id', 'qfwd8');
        config()->set('services.twitter.conversion_token', 'secret-token');
        config()->set('services.twitter.api_version', '12');
        config()->set('services.twitter.events.purchase', 'tw-qfwd8-rdw3t');
    }

    public function test_it_does_not_send_when_not_configured(): void
    {
        config()->set('services.twitter.pixel_id', null);
        config()->set('services.twitter.conversion_token', null);
        Http::fake();

        $user = User::factory()->create();
        $service = new TwitterConversionService;

        $this->assertFalse($service->sendPurchase($user, ['value' => 65]));
        Http::assertNothingSent();
    }

    public function test_it_posts_purchase_with_hashed_identifiers_and_dedup_id(): void
    {
        $this->configure();
        Http::fake(['ads-api.x.com/*' => Http::response(['success' => true], 200)]);

        $user = User::factory()->create([
            'email' => 'Buyer@Example.com',
            'phone' => '(415) 555-0132',
        ]);
        $service = new TwitterConversionService;

        $result = $service->sendPurchase($user, [
            'value' => 65,
            'currency' => 'USD',
            'conversion_id' => 'pi_123',
            'twclid' => 'tw-click-abc',
            'event_source_url' => 'https://wewingames.com/checkout',
        ]);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            $conversion = $request->data()['conversions'][0];

            return $request->url() === 'https://ads-api.x.com/12/measurement/conversions/qfwd8'
                && $request->hasHeader('X-Pixel-Token', 'secret-token')
                && $conversion['event_id'] === 'tw-qfwd8-rdw3t'
                && $conversion['conversion_id'] === 'pi_123'
                && $conversion['value'] === 65.0
                && $conversion['price_currency'] === 'USD'
                && $conversion['event_source_url'] === 'https://wewingames.com/checkout'
                // X requires one identifier type per object, not one object with
                // several keys — sending the combined form loses the match.
                && $conversion['identifiers'] === [
                    ['twclid' => 'tw-click-abc'],
                    ['hashed_email' => hash('sha256', 'buyer@example.com')],
                    ['hashed_phone_number' => hash('sha256', '14155550132')],
                ];
        });
    }

    public function test_it_appends_ip_and_user_agent_as_a_paired_identifier(): void
    {
        $this->configure();
        Http::fake(['ads-api.x.com/*' => Http::response(['success' => true], 200)]);

        $user = User::factory()->create(['email' => 'buyer@example.com', 'phone' => null]);
        $service = new TwitterConversionService;

        $service->sendPurchase($user, [
            'value' => 65,
            'conversion_id' => 'pi_123',
            'ip_address' => '203.0.113.9',
            'user_agent' => 'Mozilla/5.0 (iPhone)',
        ]);

        Http::assertSent(function ($request) {
            $identifiers = $request->data()['conversions'][0]['identifiers'];

            return $identifiers === [
                ['hashed_email' => hash('sha256', 'buyer@example.com')],
                ['ip_address' => '203.0.113.9', 'user_agent' => 'Mozilla/5.0 (iPhone)'],
            ];
        });
    }

    public function test_it_skips_when_ip_and_user_agent_are_the_only_identifiers(): void
    {
        $this->configure();
        Http::fake();

        // X does not accept ip/user agent as a primary identifier.
        $user = User::factory()->make(['email' => '', 'phone' => null]);
        $service = new TwitterConversionService;

        $this->assertFalse($service->sendPurchase($user, [
            'value' => 65,
            'ip_address' => '203.0.113.9',
            'user_agent' => 'Mozilla/5.0 (iPhone)',
        ]));

        Http::assertNothingSent();
    }

    public function test_it_returns_false_on_api_failure(): void
    {
        $this->configure();
        Http::fake(['ads-api.x.com/*' => Http::response('bad request', 400)]);

        $user = User::factory()->create();
        $service = new TwitterConversionService;

        $this->assertFalse($service->sendPurchase($user, ['value' => 10, 'conversion_id' => 'pi_x']));
    }
}
