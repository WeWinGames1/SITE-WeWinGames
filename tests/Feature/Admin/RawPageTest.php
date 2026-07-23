<?php

namespace Tests\Feature\Admin;

use App\Models\LandingPage;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RawPageTest extends TestCase
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

    public function test_admin_can_create_a_blade_raw_page(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.pages.store'), [
            'title' => 'Raw Landing',
            'slug' => 'raw-landing',
            'render_mode' => 'blade_raw',
            'raw_html' => '<html><head><title>Raw</title></head><body><h1>RAW-MARKER</h1><script>console.log("hi")</script></body></html>',
            'published' => true,
        ]);

        $response->assertRedirect(route('admin.pages.index'));

        $page = Page::where('slug', 'raw-landing')->first();
        $this->assertNotNull($page);
        $this->assertSame('blade_raw', $page->render_mode);
        $this->assertStringContainsString('RAW-MARKER', $page->raw_html);
    }

    public function test_blade_raw_page_renders_standalone_html_with_scripts(): void
    {
        Page::create([
            'title' => 'Raw',
            'slug' => 'raw-page',
            'content' => '',
            'render_mode' => 'blade_raw',
            'raw_html' => '<html><head><title>Raw</title></head><body><h1>RAW-MARKER</h1><script>console.log("run-me")</script></body></html>',
            'published' => true,
        ]);

        $response = $this->get('/pages/raw-page');

        $response->assertStatus(200);
        $response->assertSee('RAW-MARKER', false);
        // Scripts are echoed verbatim (they will execute in the browser).
        $response->assertSee('run-me', false);
        // It is NOT rendered inside the Inertia/Vue app shell.
        $response->assertDontSee('id="app"', false);
    }

    public function test_inertia_raw_page_renders_without_chrome_via_inertia(): void
    {
        Page::create([
            'title' => 'No Chrome',
            'slug' => 'no-chrome',
            'content' => '<h1>HELLO</h1>',
            'render_mode' => 'inertia_raw',
            'published' => true,
        ]);

        $response = $this->get('/pages/no-chrome');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('PageShow')
            ->where('page.render_mode', 'inertia_raw')
        );
    }

    public function test_normal_page_still_renders_through_inertia(): void
    {
        Page::create([
            'title' => 'Normal',
            'slug' => 'normal-page',
            'content' => '<p>hi</p>',
            'render_mode' => 'normal',
            'published' => true,
        ]);

        $response = $this->get('/pages/normal-page');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('PageShow'));
    }

    public function test_invalid_render_mode_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.pages.store'), [
            'title' => 'Bad',
            'slug' => 'bad-mode',
            'content' => '<p>x</p>',
            'render_mode' => 'nonsense',
            'published' => true,
        ]);

        $response->assertSessionHasErrors('render_mode');
    }

    public function test_pasted_raw_html_is_not_stripped_or_escaped_on_save(): void
    {
        $html = '<html><head><style>@media screen { .a { color: \'red\'; } } div[style*="margin: 14px 0;"] { margin: 0 !important; }</style>'
            .'</head><body><table><tr><td>&nbsp;CELL</td></tr></table><script>console.log("keep-me")</script></body></html>';

        $this->actingAs($this->admin)->post(route('admin.pages.store'), [
            'title' => 'Email Template',
            'slug' => 'email-template',
            'render_mode' => 'blade_raw',
            'raw_html' => $html,
            'published' => true,
        ])->assertRedirect(route('admin.pages.index'));

        $saved = Page::where('slug', 'email-template')->firstOrFail()->raw_html;

        $this->assertSame($html, $saved);
        $this->assertStringContainsString('<style>', $saved);
        $this->assertStringContainsString("color: 'red';", $saved);
        $this->assertStringContainsString('<table>', $saved);
        $this->assertStringContainsString('&nbsp;', $saved);
        $this->assertStringContainsString('<script>', $saved);
        $this->assertStringNotContainsString('&apos;', $saved);
        $this->assertStringNotContainsString('&quot;', $saved);
    }

    public function test_pasted_raw_html_survives_update_too(): void
    {
        $page = Page::create([
            'title' => 'Update Me',
            'slug' => 'update-me',
            'content' => '<p>x</p>',
            'published' => true,
        ]);

        $html = '<html><body><style>.b { font-family: \'Fira Sans\'; }</style><h1>UPDATED</h1></body></html>';

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), [
            'title' => 'Update Me',
            'slug' => 'update-me',
            'render_mode' => 'blade_raw',
            'raw_html' => $html,
            'published' => true,
        ])->assertRedirect(route('admin.pages.index'));

        $this->assertSame($html, $page->fresh()->raw_html);
    }

    public function test_admin_can_import_a_blade_raw_page_from_html_file_upload(): void
    {
        $html = '<html><head></head><body><h1>UPLOADED</h1></body></html>';
        $file = UploadedFile::fake()->createWithContent('page.html', $html);

        $response = $this->actingAs($this->admin)->post(route('admin.pages.store'), [
            'title' => 'Uploaded',
            'slug' => 'uploaded-page',
            'render_mode' => 'blade_raw',
            'html_file' => $file,
            'published' => true,
        ]);

        $response->assertRedirect(route('admin.pages.index'));
        $page = Page::where('slug', 'uploaded-page')->first();
        $this->assertStringContainsString('UPLOADED', $page->raw_html);
    }

    public function test_landing_page_supports_blade_raw(): void
    {
        LandingPage::create([
            'title' => 'Raw Landing',
            'slug' => 'raw-landing-public',
            'content' => '',
            'render_mode' => 'blade_raw',
            'raw_html' => '<html><body><h1>LANDING-RAW</h1></body></html>',
            'published' => true,
        ]);

        $response = $this->get('/landing/raw-landing-public');

        $response->assertStatus(200);
        $response->assertSee('LANDING-RAW', false);
        $response->assertDontSee('id="app"', false);
    }
}
