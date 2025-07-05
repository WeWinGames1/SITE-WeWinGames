# SQL Import Directory

Place your SQL files here for import via seeders.

## Usage

1. Place your `blog_posts.sql` file in this directory
2. Run the seeder:
   ```bash
   php artisan db:seed --class=ImportBlogPostsSqlSeeder
   ```

## Note

SQL files in this directory are gitignored to prevent accidentally committing large database dumps.