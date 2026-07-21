<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PageAssetsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->page = Page::create([
            'title' => 'Landing',
            'slug' => 'landing',
            'content' => '<h1>Hi</h1>',
            'published' => true,
        ]);
    }

    public function test_upload_with_page_id_tags_media_with_page(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)->post(route('admin.media-library.store'), [
            'files' => [
                UploadedFile::fake()->image('hero.jpg'),
                UploadedFile::fake()->image('logo.png'),
            ],
            'page_id' => $this->page->id,
        ]);

        $response->assertOk();
        $response->assertJsonCount(2, 'media');
        $response->assertJsonStructure(['media' => [['id', 'file_name', 'full_url']]]);

        $media = Media::where('custom_properties->page_id', $this->page->id)->get();
        $this->assertCount(2, $media);
        $this->assertSame('library', $media->first()->model_type);
        Storage::disk('public')->assertExists('media/'.$media->first()->id.'/'.$media->first()->file_name);
    }

    public function test_upload_without_page_id_still_works(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)->post(route('admin.media-library.store'), [
            'files' => [UploadedFile::fake()->image('plain.jpg')],
        ]);

        $response->assertOk();
        $this->assertSame([], Media::firstOrFail()->custom_properties);
    }

    public function test_upload_rejects_invalid_page_id(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('admin.media-library.store'), [
            'files' => [UploadedFile::fake()->image('hero.jpg')],
            'page_id' => 999999,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('page_id');
    }

    public function test_upload_accepts_non_image_web_assets(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)->post(route('admin.media-library.store'), [
            'files' => [UploadedFile::fake()->create('styles.css', 5, 'text/css')],
            'page_id' => $this->page->id,
        ]);

        $response->assertOk();
        $this->assertSame('styles.css', Media::firstOrFail()->name.'.css');
    }

    public function test_upload_rejects_disallowed_file_types(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('admin.media-library.store'), [
            'files' => [UploadedFile::fake()->create('shell.php', 5, 'application/x-php')],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('files.0');
    }

    public function test_page_edit_includes_only_that_pages_assets(): void
    {
        Storage::fake('public');

        $otherPage = Page::create([
            'title' => 'Other',
            'slug' => 'other',
            'content' => '<p>x</p>',
            'published' => true,
        ]);

        $this->actingAs($this->admin)->post(route('admin.media-library.store'), [
            'files' => [UploadedFile::fake()->image('mine.jpg')],
            'page_id' => $this->page->id,
        ])->assertOk();

        $this->actingAs($this->admin)->post(route('admin.media-library.store'), [
            'files' => [UploadedFile::fake()->image('theirs.jpg')],
            'page_id' => $otherPage->id,
        ])->assertOk();

        $this->actingAs($this->admin)
            ->get(route('admin.pages.edit', $this->page))
            ->assertInertia(fn ($page) => $page
                ->component('admin/PageEdit')
                ->has('assets', 1)
                ->where('assets.0.file_name', fn ($name) => str_contains($name, 'mine'))
            );
    }

    public function test_cannot_delete_media_referenced_by_a_page(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)->post(route('admin.media-library.store'), [
            'files' => [UploadedFile::fake()->image('used.jpg')],
            'page_id' => $this->page->id,
        ])->assertOk();

        $media = Media::firstOrFail();
        $this->page->update([
            'content' => '<img src="/storage/media/'.$media->id.'/'.$media->file_name.'">',
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.media-library.destroy', $media))
            ->assertStatus(422);

        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }
}
