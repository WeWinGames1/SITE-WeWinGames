<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MediaLibraryPaginationTest extends TestCase
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

    public function test_index_exposes_next_page_url_and_serves_second_page(): void
    {
        $this->createLibraryMedia(30);

        $this->actingAs($this->admin)
            ->get(route('admin.media-library.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/MediaLibrary/Index')
                ->has('media.data', 24)
                ->where('media.current_page', 1)
                ->where('media.last_page', 2)
                ->where('media.prev_page_url', null)
                ->where('media.next_page_url', route('admin.media-library.index', ['page' => 2]))
            );

        $this->actingAs($this->admin)
            ->get(route('admin.media-library.index', ['page' => 2]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('media.data', 6)
                ->where('media.current_page', 2)
                ->where('media.next_page_url', null)
                ->where('media.prev_page_url', route('admin.media-library.index', ['page' => 1]))
            );
    }

    public function test_picker_serves_older_media_on_later_pages(): void
    {
        $this->createLibraryMedia(15);

        $this->actingAs($this->admin)
            ->getJson(route('admin.media-library.picker', ['page' => 2]))
            ->assertOk()
            ->assertJsonPath('current_page', 2)
            ->assertJsonPath('last_page', 2)
            ->assertJsonCount(3, 'data');
    }

    private function createLibraryMedia(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            Media::create([
                'model_type' => 'library',
                'model_id' => 0,
                'uuid' => (string) Str::uuid(),
                'collection_name' => 'library',
                'name' => "image-{$i}",
                'file_name' => "image-{$i}.jpg",
                'mime_type' => 'image/jpeg',
                'disk' => 'public',
                'conversions_disk' => 'public',
                'size' => 1024,
                'manipulations' => [],
                'custom_properties' => [],
                'generated_conversions' => [],
                'responsive_images' => [],
                'order_column' => 1,
            ]);
        }
    }
}
