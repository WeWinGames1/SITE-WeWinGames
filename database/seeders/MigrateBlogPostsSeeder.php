<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MigrateBlogPostsSeeder extends Seeder
{
    /**
     * Run all blog post seeders for production migration
     */
    public function run(): void
    {
        $this->command->info('Starting blog post migration...');
        
        // Run each blog post seeder
        $this->call([
            BlogPostsSeeder::class,
            BettingEducationPostsSeeder::class,
            SampleBlogPostsSeeder::class,
        ]);
        
        $this->command->info('Blog post migration completed! 28 posts have been created.');
    }
}