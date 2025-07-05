#!/bin/bash

# Laravel Production Emergency Fix Script
# This script fixes critical bootstrap and autoload issues

echo "Laravel Production Emergency Fix"
echo "================================"
echo ""

# Step 1: Clean everything
echo "Step 1: Cleaning all caches and compiled files..."
rm -rf bootstrap/cache/*
rm -rf storage/framework/cache/*
rm -rf storage/framework/views/*
rm -rf storage/framework/sessions/*

# Step 2: Ensure directories exist
echo ""
echo "Step 2: Creating required directories..."
mkdir -p bootstrap/cache
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p storage/app/public

# Step 3: Set permissions
echo ""
echo "Step 3: Setting correct permissions..."
chmod -R 775 bootstrap/cache
chmod -R 775 storage
chmod -R 775 storage/framework
chmod -R 775 storage/logs

# Step 4: Regenerate Composer autoload
echo ""
echo "Step 4: Regenerating Composer autoload..."
composer dump-autoload -o

# Step 5: Check if autoload works
echo ""
echo "Step 5: Testing Composer autoload..."
php -r "require 'vendor/autoload.php'; echo 'Autoload works!\n';"

# Step 6: Clear Laravel caches (without optimize)
echo ""
echo "Step 6: Clearing Laravel caches..."
php artisan cache:clear 2>/dev/null || echo "Cache clear failed"
php artisan config:clear 2>/dev/null || echo "Config clear failed"
php artisan route:clear 2>/dev/null || echo "Route clear failed"
php artisan view:clear 2>/dev/null || echo "View clear failed"

# Step 7: Test basic Laravel functionality
echo ""
echo "Step 7: Testing Laravel bootstrap..."
php artisan --version

# Step 8: Regenerate optimized files
echo ""
echo "Step 8: Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 9: Final test
echo ""
echo "Step 9: Running final diagnostic..."
php diagnose-production.php

echo ""
echo "Emergency fix complete!"
echo ""
echo "If you still see errors:"
echo "1. Check PHP version: php -v (should be 8.2+)"
echo "2. Check error logs: tail -n 50 storage/logs/laravel.log"
echo "3. Try manually running: composer install --no-dev --optimize-autoloader"