#!/bin/bash

# Laravel 12 Provider Registration Fix
# Fixes the "view service not registered" issue

echo "Laravel 12 Provider Registration Fix"
echo "===================================="
echo ""

# Step 1: Clear ALL caches
echo "Step 1: Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
rm -rf bootstrap/cache/*.php

# Step 2: Clear compiled files
echo ""
echo "Step 2: Clearing compiled files..."
php artisan clear-compiled
php artisan optimize:clear

# Step 3: Regenerate composer autoload
echo ""
echo "Step 3: Regenerating composer autoload..."
composer dump-autoload -o

# Step 4: Manually trigger provider registration
echo ""
echo "Step 4: Testing provider registration..."
php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking critical services:\n";
$services = ["view", "files", "events", "db", "cache", "config"];
foreach ($services as $service) {
    $bound = $app->bound($service);
    echo sprintf("  %-10s %s\n", $service, $bound ? "✓ Registered" : "✗ NOT Registered");
}
'

# Step 5: Rebuild optimized files
echo ""
echo "Step 5: Rebuilding optimized files..."
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

# Step 6: Final test
echo ""
echo "Step 6: Final test..."
php test-autoload.php

echo ""
echo "Fix complete!"
echo ""
echo "If the view service is still not registered:"
echo "1. Check if APP_ENV is set correctly in .env"
echo "2. Ensure bootstrap/app.php has ->withProviders() call"
echo "3. Check for any custom service provider errors in storage/logs/laravel.log"