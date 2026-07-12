<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RawPageTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_reddit_pixel_is_injected_into_a_full_document_raw_page(): void
    {
        config(['services.reddit.pixel_id' => 'rp_test_123']);

        Page::create([
            'title' => 'Raw',
            'slug' => 'raw-tracked',
            'content' => '',
            'render_mode' => 'blade_raw',
            'raw_html' => '<html><head><title>Raw</title></head><body><h1>HELLO</h1></body></html>',
            'published' => true,
        ]);

        $response = $this->get('/pages/raw-tracked');

        $response->assertStatus(200);
        // Tracking is injected into the existing document head.
        $response->assertSee('rp_test_123', false);
        $response->assertSee('redditstatic.com', false);
        // Original content is preserved.
        $response->assertSee('HELLO', false);
    }

    public function test_tracking_head_partial_renders_configured_pixels(): void
    {
        config(['services.reddit.pixel_id' => 'rp_partial_456']);

        $html = view('partials.tracking-head')->render();

        $this->assertStringContainsString('rp_partial_456', $html);
    }

    public function test_blade_raw_page_serves_a_relaxed_csp(): void
    {
        Page::create([
            'title' => 'Raw',
            'slug' => 'raw-csp',
            'content' => '',
            'render_mode' => 'blade_raw',
            'raw_html' => '<html><head></head><body><h1>HELLO</h1></body></html>',
            'published' => true,
        ]);

        $response = $this->get('/pages/raw-csp');

        $response->assertStatus(200);
        // Relaxed policy set by RawPageController is preserved (not overwritten
        // by the strict site-wide allowlist), so admin-pasted external
        // scripts/styles/fonts can load.
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self' https: 'unsafe-inline'", $csp);
        $this->assertStringNotContainsString('googletagmanager.com', $csp);
    }

    public function test_normal_page_keeps_the_strict_csp(): void
    {
        Page::create([
            'title' => 'Normal',
            'slug' => 'normal-csp',
            'content' => '<p>hi</p>',
            'render_mode' => 'normal',
            'published' => true,
        ]);

        $response = $this->get('/pages/normal-csp');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('googletagmanager.com', $csp);
    }
}
