#!/bin/bash

# Emergency fix for Scribe package error in production

echo "🔧 Fixing Scribe package error..."

# Option 1: Remove Scribe config from production (RECOMMENDED)
echo "Option 1: Removing Scribe config (recommended for production)"
rm -f config/scribe.php
echo "✅ Scribe config removed"

# Clear config cache
php artisan config:clear

# Try composer install again
echo "🔄 Running composer install..."
composer install --no-dev --optimize-autoloader

# If that works, optimize
php artisan optimize

echo "✅ Fix completed!"
echo ""
echo "Note: Scribe is a development tool for API documentation."
echo "It should not be needed in production."