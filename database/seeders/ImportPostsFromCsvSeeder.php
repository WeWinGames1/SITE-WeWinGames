<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportPostsFromCsvSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing posts
        DB::table('posts')->truncate();
        
        $csvFile = base_path('_DEV/posts.csv');
        
        if (!file_exists($csvFile)) {
            $this->command->error('CSV file not found: ' . $csvFile);
            return;
        }
        
        $file = fopen($csvFile, 'r');
        
        // Skip header row
        $header = fgetcsv($file);
        
        $count = 0;
        
        while (($row = fgetcsv($file)) !== FALSE) {
            try {
                // Map CSV columns to database fields
                $postData = [
                    'id' => $row[0],
                    'title' => $row[1],
                    'slug' => $row[2],
                    'excerpt' => $row[3],
                    'content' => $row[4],
                    'featured_image' => $row[5] ?: null,
                    'user_id' => $row[6],
                    'is_published' => $row[7] == '1',
                    'published_at' => $row[8] ?: null,
                    'meta' => $row[9] ? json_decode($row[9], true) : null,
                    'category' => $row[10],
                    'tags' => $row[11] ? json_decode($row[11], true) : [],
                    'seo_title' => $row[12],
                    'seo_description' => $row[13],
                    'seo_keywords' => $row[14],
                    'views_count' => (int)$row[15],
                    'created_at' => $row[16],
                    'updated_at' => $row[17],
                ];
                
                // Create the post
                Post::create($postData);
                
                $count++;
                
                if ($count % 10 == 0) {
                    $this->command->info("Imported {$count} posts...");
                }
                
            } catch (\Exception $e) {
                $this->command->error("Error importing post: " . $e->getMessage());
                $this->command->error("Row data: " . implode(', ', $row));
            }
        }
        
        fclose($file);
        
        $this->command->info("Successfully imported {$count} posts from CSV!");
    }
}