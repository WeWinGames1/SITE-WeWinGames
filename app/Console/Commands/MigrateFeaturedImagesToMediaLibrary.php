<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateFeaturedImagesToMediaLibrary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:migrate-featured-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing post featured images to the media library';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of featured images to media library...');

        $posts = Post::whereNotNull('featured_image')->get();
        $total = $posts->count();
        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        $this->info("Found {$total} posts with featured images.");

        foreach ($posts as $post) {
            $this->info("Processing post ID {$post->id}: {$post->title}");

            // Check if already has media
            if ($post->getFirstMedia('featured-image')) {
                $this->warn('  - Post already has media in library, skipping.');
                $skipped++;

                continue;
            }

            // Check if the featured image exists
            if (! Storage::disk('public')->exists($post->featured_image)) {
                $this->error("  - Featured image not found: {$post->featured_image}");
                $failed++;

                continue;
            }

            try {
                // Get the full path to the image
                $fullPath = Storage::disk('public')->path($post->featured_image);

                // Add to media library
                $media = $post->addMedia($fullPath)
                    ->preservingOriginal()
                    ->toMediaCollection('featured-image');

                $this->info("  - Successfully migrated to media library (ID: {$media->id})");
                $migrated++;
            } catch (\Exception $e) {
                $this->error("  - Failed to migrate: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("\nMigration complete!");
        $this->info("Total posts: {$total}");
        $this->info("Migrated: {$migrated}");
        $this->info("Skipped: {$skipped}");
        $this->info("Failed: {$failed}");

        if ($migrated > 0) {
            $this->info("\nNote: Original featured_image paths have been preserved in the database.");
            $this->info('The media library will now be used as the primary source for featured images.');
        }
    }
}
