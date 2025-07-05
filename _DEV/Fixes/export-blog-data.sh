#!/bin/bash

echo "Exporting blog post data..."

# Method 1: Export as INSERT statements
echo "Creating SQL export with INSERT statements..."
php artisan tinker --execute="
\$posts = \App\Models\Post::all();
\$sql = '';
foreach (\$posts as \$post) {
    \$values = [
        'id' => \$post->id,
        'title' => addslashes(\$post->title),
        'slug' => \$post->slug,
        'excerpt' => \$post->excerpt ? addslashes(\$post->excerpt) : 'NULL',
        'content' => addslashes(\$post->content),
        'featured_image' => \$post->featured_image ? \"'\" . \$post->featured_image . \"'\" : 'NULL',
        'user_id' => \$post->user_id,
        'is_published' => \$post->is_published,
        'published_at' => \$post->published_at ? \"'\" . \$post->published_at . \"'\" : 'NULL',
        'category' => \$post->category ? \"'\" . \$post->category . \"'\" : 'NULL',
        'tags' => \$post->tags ? \"'\" . addslashes(json_encode(\$post->tags)) . \"'\" : 'NULL',
        'views_count' => \$post->views_count,
        'created_at' => \"'\" . \$post->created_at . \"'\",
        'updated_at' => \"'\" . \$post->updated_at . \"'\",
        'seo_title' => \$post->seo_title ? \"'\" . addslashes(\$post->seo_title) . \"'\" : 'NULL',
        'seo_description' => \$post->seo_description ? \"'\" . addslashes(\$post->seo_description) . \"'\" : 'NULL',
        'seo_keywords' => \$post->seo_keywords ? \"'\" . addslashes(\$post->seo_keywords) . \"'\" : 'NULL',
    ];
    
    \$sql .= \"INSERT INTO posts (id, title, slug, excerpt, content, featured_image, user_id, is_published, published_at, category, tags, views_count, created_at, updated_at, seo_title, seo_description, seo_keywords) VALUES (\"
        . \$values['id'] . \", '\"
        . \$values['title'] . \"', '\"
        . \$values['slug'] . \"', \"
        . (\$values['excerpt'] === 'NULL' ? 'NULL' : \"'\" . \$values['excerpt'] . \"'\") . \", '\"
        . \$values['content'] . \"', \"
        . \$values['featured_image'] . \", \"
        . \$values['user_id'] . \", \"
        . \$values['is_published'] . \", \"
        . \$values['published_at'] . \", \"
        . \$values['category'] . \", \"
        . \$values['tags'] . \", \"
        . \$values['views_count'] . \", \"
        . \$values['created_at'] . \", \"
        . \$values['updated_at'] . \", \"
        . \$values['seo_title'] . \", \"
        . \$values['seo_description'] . \", \"
        . \$values['seo_keywords'] . \");\\n\";
}
file_put_contents('database/seeders/sql/posts_data.sql', \$sql);
echo 'Exported ' . \$posts->count() . ' posts to database/seeders/sql/posts_data.sql';
"

# Method 2: Using mysqldump (if available)
if command -v mysqldump &> /dev/null; then
    echo ""
    echo "Also creating mysqldump export..."
    source .env
    mysqldump -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" posts --no-create-info --complete-insert > database/seeders/sql/posts_mysqldump.sql
    echo "Created database/seeders/sql/posts_mysqldump.sql"
fi

echo ""
echo "Export complete! Upload one of these files to production and import."