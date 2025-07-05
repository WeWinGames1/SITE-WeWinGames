#!/bin/bash

echo "Fixing CustomViewServiceProvider issue..."

# Clear all caches
echo "1. Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Clear compiled files
echo "2. Clearing compiled files..."
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/routes-v7.php
rm -f bootstrap/cache/services.php
rm -f bootstrap/cache/packages.php

# Clear composer cache
echo "3. Clearing composer cache..."
composer clear-cache

# Dump autoload without scripts
echo "4. Regenerating autoload files..."
composer dump-autoload --no-scripts

# Now run the scripts
echo "5. Running post-autoload scripts..."
composer dump-autoload

# Try to optimize again
echo "6. Running optimize..."
php artisan optimize:clear

echo "Done! The issue should be resolved."