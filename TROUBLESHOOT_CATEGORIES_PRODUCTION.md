# Troubleshooting: Blog Categories Not Loading on Production

## Issue Description
When clicking "Manage Categories" in the admin blog posts page, no categories appear on production (but works locally).

## Troubleshooting Steps

### 1. Check if Migration Has Been Run
SSH into production and run:
```bash
php artisan migrate:status | grep blog_categories
```

If the migration hasn't been run:
```bash
php artisan migrate --force
```

### 2. Check if Categories Exist in Database
```bash
php artisan tinker
>>> \App\Models\BlogCategory::count()
>>> \App\Models\BlogCategory::all()
```

### 3. Clear All Caches
```bash
php artisan optimize:clear
```

### 4. Check Browser Console
1. Open browser developer tools (F12)
2. Go to Network tab
3. Click "Manage Categories"
4. Look for the request to `/admin/blog-categories`
5. Check the response status and data

### 5. Check Laravel Logs
```bash
tail -n 50 storage/logs/laravel.log
```

### 6. Test the Route Directly
Try accessing the URL directly in browser:
```
https://yoursite.com/admin/blog-categories
```

### 7. Check Route Registration
```bash
php artisan route:list | grep blog-categories
```

### 8. Debug the Controller
Add logging to the controller temporarily:

```php
// In app/Http/Controllers/Admin/BlogCategoryController.php
public function index(): JsonResponse
{
    \Log::info('BlogCategory index called');
    $categories = BlogCategory::ordered()->get();
    \Log::info('Categories found: ' . $categories->count());
    
    return response()->json($categories);
}
```

### 9. Check Permissions
Ensure the user has admin permissions:
```bash
php artisan tinker
>>> $user = \App\Models\User::where('email', 'your-email@example.com')->first();
>>> $user->is_admin
```

### 10. Check if it's a Frontend Build Issue
The production build might be outdated:
```bash
npm run build
```

## Common Fixes

### Fix 1: Database Migration Missing
If the blog_categories table doesn't exist:
```bash
php artisan migrate --force
```

### Fix 2: No Categories in Database
If the table exists but has no data:
```bash
php artisan db:seed --class=BlogCategorySeeder
```

Or create categories manually:
```bash
php artisan tinker
>>> \App\Models\BlogCategory::create(['name' => 'General', 'slug' => 'general']);
>>> \App\Models\BlogCategory::create(['name' => 'Sports Analysis', 'slug' => 'sports-analysis']);
>>> \App\Models\BlogCategory::create(['name' => 'Betting Tips', 'slug' => 'betting-tips']);
```

### Fix 3: Route Cache Issue
```bash
php artisan route:clear
php artisan route:cache
```

### Fix 4: CORS/CSRF Issue
Check if CSRF token is being sent properly. The error might be a 419 status code.

## Quick SQL Check
Run this SQL directly on production database:
```sql
-- Check if table exists
SHOW TABLES LIKE 'blog_categories';

-- Check if any records exist
SELECT * FROM blog_categories;

-- Check posts with categories
SELECT DISTINCT category FROM posts WHERE category IS NOT NULL;
```

## Emergency Fix
If you need categories working immediately, you can manually insert them:
```sql
INSERT INTO blog_categories (name, slug, order_column, is_active, created_at, updated_at) VALUES
('General', 'general', 0, 1, NOW(), NOW()),
('Sports Analysis', 'sports-analysis', 1, 1, NOW(), NOW()),
('Betting Tips', 'betting-tips', 2, 1, NOW(), NOW()),
('NFL', 'nfl', 3, 1, NOW(), NOW()),
('NBA', 'nba', 4, 1, NOW(), NOW()),
('MLB', 'mlb', 5, 1, NOW(), NOW()),
('NHL', 'nhl', 6, 1, NOW(), NOW()),
('Soccer', 'soccer', 7, 1, NOW(), NOW()),
('Golf', 'golf', 8, 1, NOW(), NOW());
```