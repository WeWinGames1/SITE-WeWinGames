<?php

namespace Tests\Feature;

use App\Models\LandingPage;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditableHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_uses_built_in_welcome_by_default(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Welcome'));
    }

    public function test_homepage_renders_editable_page_when_enabled(): void
    {
        $landing = LandingPage::create([
            'title' => 'Editable Home',
            'slug' => 'editable-home',
            'content' => '<h1>Win rate {{stat:winRatio}}%</h1>[pricing]',
            'render_mode' => 'normal',
            'published' => true,
        ]);

        SiteSetting::set('enable_editable_home', true);
        SiteSetting::set('home_landing_page_id', $landing->id);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('EditableHome')
            ->where('title', 'Editable Home')
            ->has('segments', 2)
            ->where('segments.1.name', 'pricing')
        );
    }

    public function test_editable_home_falls_back_when_page_missing(): void
    {
        SiteSetting::set('enable_editable_home', true);
        SiteSetting::set('home_landing_page_id', 99999);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Welcome'));
    }
}
