<?php

namespace Tests\Feature\Admin;

use App\Models\LandingPage;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PagePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_preview_unsaved_full_document_html(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.pages.preview'), [
            'html_b64' => base64_encode(
                '<html><head><title>P</title></head><body><h1>PREVIEW-MARKER</h1><script src="https://example.com/w.js"></script></body></html>'
            ),
            'title' => 'Draft',
        ]);

        $response->assertStatus(200);
        $response->assertSee('PREVIEW-MARKER', false);
        $this->assertStringContainsString("script-src 'self' https:", $response->headers->get('Content-Security-Policy'));
        $response->assertDontSee('id="app"', false);
    }

    public function test_preview_wraps_html_fragments(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.pages.preview'), [
            'html_b64' => base64_encode('<h1>FRAGMENT-MARKER</h1>'),
        ]);

        $response->assertStatus(200);
        $response->assertSee('FRAGMENT-MARKER', false);
        $response->assertSee('<!DOCTYPE html>', false);
    }

    public function test_preview_omits_tracking_pixels(): void
    {
        config(['services.reddit.pixel_id' => 'TESTPIXEL123']);

        $response = $this->actingAs($this->admin)->post(route('admin.pages.preview'), [
            'html_b64' => base64_encode('<html><head></head><body><h1>NO-TRACKING</h1></body></html>'),
        ]);

        $response->assertStatus(200);
        $response->assertDontSee('redditstatic.com', false);
    }

    public function test_guests_cannot_use_preview(): void
    {
        $this->post(route('admin.pages.preview'), ['html_b64' => base64_encode('<h1>x</h1>')])
            ->assertRedirect();
    }

    public function test_tracking_is_not_rendered_on_admin_routes(): void
    {
        config(['services.reddit.pixel_id' => 'TESTPIXEL123']);

        $this->actingAs($this->admin)
            ->get(route('admin.pages.index'))
            ->assertOk()
            ->assertDontSee('redditstatic.com', false);
    }

    public function test_tracking_still_renders_on_public_pages(): void
    {
        config(['services.reddit.pixel_id' => 'TESTPIXEL123']);

        Page::create([
            'title' => 'Pub',
            'slug' => 'pub',
            'content' => '<p>x</p>',
            'published' => true,
        ]);

        $this->get('/pages/pub')
            ->assertOk()
            ->assertSee('redditstatic.com', false);
    }

    public function test_admin_can_view_unpublished_page_but_guest_cannot(): void
    {
        Page::create([
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'content' => '<h1>DRAFT-PAGE</h1>',
            'published' => false,
        ]);

        $this->get('/pages/draft-page')->assertStatus(404);

        $this->actingAs($this->admin)
            ->get('/pages/draft-page')
            ->assertStatus(200);
    }

    public function test_admin_can_view_unpublished_landing_page_but_guest_cannot(): void
    {
        LandingPage::create([
            'title' => 'Draft Landing',
            'slug' => 'draft-landing',
            'content' => '<h1>DRAFT-LANDING</h1>',
            'published' => false,
        ]);

        $this->get('/landing/draft-landing')->assertStatus(404);

        $this->actingAs($this->admin)
            ->get('/landing/draft-landing')
            ->assertStatus(200);
    }

    public function test_regular_user_cannot_view_unpublished_page(): void
    {
        Page::create([
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'content' => '<h1>DRAFT-PAGE</h1>',
            'published' => false,
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/pages/draft-page')
            ->assertStatus(404);
    }
}
