#!/bin/bash

# Export blog posts from local database
echo "Exporting blog posts from local database..."

# Create export directory
mkdir -p _DEV/blog-export

# Export posts table to SQL
php artisan db:table posts > _DEV/blog-export/posts.sql

# Alternative: Export as JSON
php artisan tinker --execute="
    \$posts = \App\Models\Post::all();
    file_put_contents('_DEV/blog-export/posts.json', \$posts->toJson(JSON_PRETTY_PRINT));
    echo 'Exported ' . \$posts->count() . ' posts to _DEV/blog-export/posts.json';
"

echo "Blog posts exported successfully!"
echo "Files created:"
echo "  - _DEV/blog-export/posts.sql"
echo "  - _DEV/blog-export/posts.json"