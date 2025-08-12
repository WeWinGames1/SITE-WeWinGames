<?php
// Temporary debug script for production
// Place this in the public directory and access it via browser
// DELETE THIS FILE AFTER DEBUGGING!

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Check if blog_categories table exists
try {
    $tableExists = \Illuminate\Support\Facades\Schema::hasTable('blog_categories');
    echo "Table 'blog_categories' exists: " . ($tableExists ? 'YES' : 'NO') . "<br><br>";
    
    if ($tableExists) {
        // Check column structure
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('blog_categories');
        echo "Columns in blog_categories table:<br>";
        echo "<pre>" . print_r($columns, true) . "</pre><br>";
        
        // Count categories
        $count = \App\Models\BlogCategory::count();
        echo "Number of categories: " . $count . "<br><br>";
        
        // List all categories
        $categories = \App\Models\BlogCategory::all();
        echo "Categories in database:<br>";
        foreach ($categories as $category) {
            echo "- ID: {$category->id}, Name: {$category->name}, Slug: {$category->slug}, Active: " . ($category->is_active ? 'Yes' : 'No') . "<br>";
        }
        
        // Check if any posts have categories
        echo "<br>Posts with categories:<br>";
        $postsWithCategories = \App\Models\Post::whereNotNull('category')->select('category')->distinct()->get();
        foreach ($postsWithCategories as $post) {
            echo "- Category slug used in posts: {$post->category}<br>";
        }
        
        // Test the controller directly
        echo "<br>Testing controller response:<br>";
        $controller = new \App\Http\Controllers\Admin\BlogCategoryController();
        $response = $controller->index();
        echo "Controller response status: " . $response->status() . "<br>";
        echo "Controller response data:<br>";
        echo "<pre>" . json_encode(json_decode($response->content()), JSON_PRETTY_PRINT) . "</pre>";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}