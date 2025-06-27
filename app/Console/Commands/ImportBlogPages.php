<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Page;
use Illuminate\Support\Str;
use App\Services\PageService;

class ImportBlogPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:blog-pages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Vue blog pages as database Pages';

    /**
     * Execute the console command.
     */
    public function handle(PageService $pages)
    {
        $blogDir = resource_path('js/pages/blog');
        $files = glob($blogDir . '/*.vue');
        $imported = 0;

        foreach ($files as $file) {
            $content = file_get_contents($file);

            // Extract <h1>...</h1> as title
            if (preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $content, $titleMatch)) {
                $title = trim(strip_tags($titleMatch[1]));
            } else {
                $title = basename($file, '.vue');
            }

            // Extract everything inside <template>...</template>
            if (preg_match('/<template>(.*?)<\/template>/si', $content, $templateMatch)) {
                $templateHtml = trim($templateMatch[1]);
            } else {
                $templateHtml = '';
            }

            // Remove layout wrappers (e.g. <WelcomeLayout>...</WelcomeLayout>)
            $templateHtml = preg_replace('/<WelcomeLayout[^>]*>|<\/WelcomeLayout>/si', '', $templateHtml);

            // Remove outer divs if you want just the inner content
            $templateHtml = preg_replace('/^\s*<div[^>]*>|<\/div>\s*$/si', '', $templateHtml);

            // Slug from filename (hyphenated)
            $slug = Str::slug($title);
            
            // Extract first image src as featured_image
            $featured_image = null;
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $templateHtml, $imgMatch)) {
                $featured_image = $imgMatch[1];
            }
            
            // Skip if already imported
            if (\App\Models\Page::where('slug', $slug)->exists()) {
                $this->info("Skipping existing page: $slug");
                continue;
            }

            $pages->create([
                'title' => $title,
                'slug' => $slug,
                'content' => $templateHtml,
                'featured_image' => $featured_image,
                'published' => true,
            ]);
            $this->info("Imported: $title ($slug)");
            $imported++;
        }

        $this->info("Imported $imported blog pages.");
        return 0;
    }
}
