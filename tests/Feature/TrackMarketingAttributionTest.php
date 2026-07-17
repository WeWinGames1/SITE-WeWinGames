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
}
