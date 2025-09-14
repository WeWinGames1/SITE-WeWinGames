#!/bin/bash

# Production Fix Script for WeWinGames
# This script fixes the "Target class [view] does not exist" and missing file errors

echo "🚀 Starting production fix..."

# 1. Clear all caches
echo "📦 Clearing all caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# 2. Remove vendor directory and reinstall
echo "🔄 Reinstalling dependencies..."
rm -rf vendor
composer install --no-dev --optimize-autoloader

# 3. Clear Composer cache and dump autoload
echo "🔧 Regenerating autoload files..."
composer clear-cache
composer dump-autoload --optimize

# 4. Clear bootstrap cache
echo "🗑️ Clearing bootstrap cache..."
rm -rf bootstrap/cache/*
touch bootstrap/cache/.gitkeep

# 5. Regenerate all caches
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Set correct permissions
echo "🔐 Setting permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache

# 7. Check if all service providers exist
echo "✅ Verifying service providers..."
if [ ! -f "app/Providers/AppServiceProvider.php" ]; then
    echo "❌ ERROR: AppServiceProvider.php is missing!"
    exit 1
fi

# 8. Run artisan optimize
echo "🎯 Running final optimization..."
php artisan optimize

# 9. Restart PHP-FPM (adjust for your setup)
echo "🔄 Restarting services..."
# Uncomment the appropriate line for your server
# sudo systemctl restart php8.2-fpm
# sudo service php8.2-fpm restart
# For cPanel/WHM:
# /scripts/restartsrv_httpd
# /scripts/restartsrv_apache_php_fpm

echo "✅ Production fix completed!"
echo ""
echo "⚠️  IMPORTANT: If you're still seeing errors:"
echo "1. Make sure all files were uploaded correctly (check case sensitivity)"
echo "2. Verify PHP version matches local development (8.2+)"
echo "3. Check error logs: tail -f storage/logs/laravel.log"
echo "4. Ensure .env file exists and is properly configured"