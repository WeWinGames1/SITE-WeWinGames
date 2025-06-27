<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdatePageFeaturedImage extends Command
{
    protected $signature = 'page:update-featured-image {slug} {image_url}';
    protected $description = 'Update a Page\'s featured image by downloading an image from a URL';

    public function handle()
    {
        $slug = $this->argument('slug');
        $imageUrl = $this->argument('image_url');

        $page = Page::where('slug', $slug)->first();

        if (!$page) {
            $this->error("Page with slug '{$slug}' not found.");
            return 1;
        }

        $this->info("Downloading image from: $imageUrl");
        $imageContents = @file_get_contents($imageUrl);

        if ($imageContents === false) {
            $this->error("Failed to download image from URL.");
            return 1;
        }

        $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        $filename = 'pages/' . $slug . '-' . Str::random(8) . '.' . $extension;

        Storage::disk('public')->put($filename, $imageContents);

        $page->featured_image = $filename;
        $page->save();

        $this->info("Featured image updated for page '{$page->title}'.");
        return 0;
    }
}