<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use Illuminate\Database\Seeder;

class BlogCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['slug' => 'betting-education', 'name' => 'Betting Education', 'order_column' => 1],
            ['slug' => 'sports-analysis', 'name' => 'Sports Analysis', 'order_column' => 2],
            ['slug' => 'industry-news', 'name' => 'Industry News', 'order_column' => 3],
            ['slug' => 'tips-strategies', 'name' => 'Tips & Strategies', 'order_column' => 4],
            ['slug' => 'beginners-guide', 'name' => 'Beginners Guide', 'order_column' => 5],
            ['slug' => 'advanced-betting', 'name' => 'Advanced Betting', 'order_column' => 6],
        ];

        foreach ($categories as $category) {
            BlogCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
