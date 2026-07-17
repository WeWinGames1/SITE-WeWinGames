<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TestTwitterConversionCommandTest extends TestCase
{
    use RefreshDatabase;

    private function configure(): void
    {
        config()->set('services.twitter.pixel_id', 'qfwd8');
        config()->set('services.twitter.conversion_token', 'secret-token');
        config()->set('services.twitter.api_version', '12');
        config()->set('services.twitter.events.purchase', 'tw-qfwd8-rdw3t');
    }

    public function test_it_fails_when_not_configured(): void
    {
        config()->set('services.twitter.pixel_id', null);
        config()->set('services.twitter.conversion_token', null);

        $this->artisan('twitter:test-conversion', ['--user' => '1'])
            ->assertExitCode(1);
    }

    public function test_dry_run_prints_payload_without_sending(): void
    {
        $this->configure();
        Http::fake();
        $user = User::factory()->create(['twclid' => 'tw-click-1']);

        $this->artisan('twitter:test-conversion', ['--user' => (string) $user->id, '--dry-run' => true])
            ->expectsOutputToContain('measurement/conversions/qfwd8')
            ->assertExitCode(0);

        Http::assertNothingSent();
    }

    public function test_it_sends_with_force(): void
    {
        $this->configure();
        Http::fake(['ads-api.x.com/*' => Http::response(['ok' => true], 200)]);
        $user = User::factory()->create();

        $this->artisan('twitter:test-conversion', ['--user' => $user->email, '--force' => true])
            ->assertExitCode(0);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'qfwd8'));
    }
}
