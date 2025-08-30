<?php

namespace App\Console\Commands;

use App\Models\BlogCategory;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DebugCategories extends Command
{
    protected $signature = 'debug:categories';

    protected $description = 'Debug blog categories issue';

    public function handle()
    {
        $this->info('=== Blog Categories Debug ===');

        // Check if table exists
        $tableExists = Schema::hasTable('blog_categories');
        $this->info('Table blog_categories exists: '.($tableExists ? 'YES' : 'NO'));

        if (! $tableExists) {
            $this->error('Table does not exist! Run migrations.');

            return;
        }

        // Check columns
        $columns = Schema::getColumnListing('blog_categories');
        $this->info('Columns: '.implode(', ', $columns));

        // Count categories
        $count = BlogCategory::count();
        $this->info("Total categories: {$count}");

        // List categories
        if ($count > 0) {
            $this->info("\nCategories in database:");
            $categories = BlogCategory::all();
            foreach ($categories as $category) {
                $this->line("  - {$category->name} (slug: {$category->slug}, active: ".($category->is_active ? 'yes' : 'no').')');
            }
        } else {
            $this->warn('No categories found in database!');

            // Create default categories
            if ($this->confirm('Would you like to create default categories?')) {
                $this->createDefaultCategories();
            }
        }

        // Check posts with categories
        $this->info("\nChecking posts with categories:");
        $categoryUsage = Post::whereNotNull('category')
            ->select('category')
            ->distinct()
            ->pluck('category');

        if ($categoryUsage->count() > 0) {
            $this->info('Category slugs used in posts:');
            foreach ($categoryUsage as $slug) {
                $exists = BlogCategory::where('slug', $slug)->exists();
                $status = $exists ? '✓' : '✗ (missing in blog_categories)';
                $this->line("  - {$slug} {$status}");
            }
        } else {
            $this->info('No posts have categories assigned.');
        }

        // Test the index route
        $this->info("\nTesting BlogCategoryController::index():");
        try {
            $controller = new \App\Http\Controllers\Admin\BlogCategoryController;
            $response = $controller->index();
            $data = json_decode($response->content());
            $this->info("Response status: {$response->status()}");
            $this->info('Categories returned: '.count($data));
        } catch (\Exception $e) {
            $this->error('Error calling controller: '.$e->getMessage());
        }
    }

    private function createDefaultCategories()
    {
        $categories = [
            ['name' => 'General', 'slug' => 'general'],
            ['name' => 'Sports Analysis', 'slug' => 'sports-analysis'],
            ['name' => 'Betting Tips', 'slug' => 'betting-tips'],
            ['name' => 'NFL', 'slug' => 'nfl'],
            ['name' => 'NBA', 'slug' => 'nba'],
            ['name' => 'MLB', 'slug' => 'mlb'],
            ['name' => 'NHL', 'slug' => 'nhl'],
            ['name' => 'Soccer', 'slug' => 'soccer'],
            ['name' => 'Golf', 'slug' => 'golf'],
            ['name' => 'Strategy', 'slug' => 'strategy'],
            ['name' => 'News', 'slug' => 'news'],
        ];

        foreach ($categories as $index => $category) {
            BlogCategory::create([
                'name' => $category['name'],
                'slug' => $category['slug'],
                'order_column' => $index,
                'is_active' => true,
            ]);
            $this->info("Created category: {$category['name']}");
        }
    }
}
