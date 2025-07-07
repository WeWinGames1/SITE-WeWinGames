<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImportBlogPostsSqlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the path where SQL file will be placed
        $sqlFile = database_path('seeders/sql/blog_posts.sql');

        if (! file_exists($sqlFile)) {
            $this->command->error("SQL file not found at: {$sqlFile}");
            $this->command->info('Please place your blog_posts.sql file at: database/seeders/sql/blog_posts.sql');

            return;
        }

        $this->command->info('Importing blog posts from SQL file...');

        try {
            $sql = file_get_contents($sqlFile);

            // Split by semicolon to handle multiple statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));

            $count = 0;
            foreach ($statements as $statement) {
                if (! empty($statement)) {
                    DB::unprepared($statement);
                    $count++;
                }
            }

            $this->command->info("Successfully imported {$count} SQL statements!");

        } catch (\Exception $e) {
            $this->command->error('Import failed: '.$e->getMessage());
        }
    }
}
