<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackMarketingAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_captures_click_id_and_utm_params_into_cookies(): void
    {
        $response = $this->get('/login?twclid=abc123&utm_source=x-ads&utm_campaign=summer');

        $response->assertCookie('twclid');
        $response->assertCookie('utm_source');
        $response->assertCookie('utm_campaign');
        $response->assertCookie('landing_url');
    }

    public function test_it_does_not_set_cookies_without_tracking_params(): void
    {
        $response = $this->get('/login');

        $response->assertCookieMissing('twclid');
        $response->assertCookieMissing('landing_url');
    }

    public function test_it_caps_oversized_tracking_values(): void
    {
        // An oversized query param must be truncated before it is stored, so it
        // can neither bloat the cookie nor overflow the VARCHAR(255) user column.
        $response = $this->get('/login?twclid='.str_repeat('a', 5000));

        $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === 'twclid');

        $this->assertNotNull($cookie);
        // A 255-char value encrypts to well under 1500 bytes; a 5000-char value
        // would produce a multi-KB cookie, so this proves truncation occurred.
        $this->assertLessThan(1500, strlen($cookie->getValue()));
    }
}
