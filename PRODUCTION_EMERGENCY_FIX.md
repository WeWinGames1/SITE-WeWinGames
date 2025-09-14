# Production Emergency Fix Guide

## Errors Being Fixed
1. `Target class [view] does not exist`
2. `AppServiceProvider.php: Failed to open stream: No such file or directory`

## Root Causes
1. **Missing or corrupted vendor autoload files**
2. **Cache corruption**
3. **Case sensitivity issues (Linux vs macOS)**
4. **Incomplete file upload**

## Immediate Fix Steps

### Step 1: Verify Files Exist
```bash
# SSH into production server
cd /home/n8e4255/SITE-WeWinGames

# Check if AppServiceProvider exists
ls -la app/Providers/AppServiceProvider.php

# If missing, check case sensitivity
find . -iname "appserviceprovider.php" 2>/dev/null
```

### Step 2: Clear Everything
```bash
# Clear all Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# Clear compiled files
rm -rf bootstrap/cache/*
touch bootstrap/cache/.gitkeep

# Clear vendor directory
rm -rf vendor
```

### Step 3: Reinstall Dependencies
```bash
# Install composer dependencies
composer install --no-dev --optimize-autoloader

# If composer fails, try:
composer install --no-dev --optimize-autoloader --no-scripts
php artisan package:discover
```

### Step 4: Regenerate Everything
```bash
# Dump autoload
composer dump-autoload --optimize

# Regenerate caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 5: Fix Permissions
```bash
# Set correct permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chown -R $(whoami):$(whoami) storage
chown -R $(whoami):$(whoami) bootstrap/cache
```

### Step 6: Verify Installation
```bash
# Test artisan
php artisan about

# Check for errors
tail -f storage/logs/laravel.log
```

## If Still Broken

### Option 1: Manual Provider Registration
Create a temporary fix in `bootstrap/app.php`:
```php
// Add before return statement
if (!class_exists(\Illuminate\Support\Facades\View::class)) {
    app()->register(\Illuminate\View\ViewServiceProvider::class);
}
```

### Option 2: Re-upload Files
1. Download fresh copy from Git
2. Ensure ALL files are uploaded (including hidden files)
3. Use BINARY mode for file transfer
4. Verify file permissions after upload

### Option 3: Check PHP Version
```bash
php -v
# Should be 8.2 or higher

# Check loaded extensions
php -m | grep -E "(mbstring|openssl|pdo|tokenizer|xml|ctype|json|bcmath)"
```

### Option 4: Emergency Composer Fix
```bash
# Remove composer files
rm -f composer.lock
rm -rf vendor

# Clear composer cache
composer clear-cache

# Reinstall with verbose output
composer install --no-dev -vvv

# If timeout issues:
COMPOSER_PROCESS_TIMEOUT=2000 composer install --no-dev
```

## Verification Commands
```bash
# Check if view facade is available
php artisan tinker
>>> View::exists('welcome')
>>> exit

# List all registered providers
php artisan package:discover

# Check autoload files
ls -la vendor/composer/
cat vendor/composer/autoload_psr4.php | grep App
```

## Prevention for Future
1. Always use deployment script
2. Test on staging first
3. Keep local/production PHP versions in sync
4. Use Git deployment instead of FTP when possible
5. Run `composer install` after every deployment

## Emergency Contact
If none of these work:
1. Check with hosting provider for:
   - PHP error logs
   - Apache/Nginx error logs
   - System logs
2. Verify server meets Laravel 11 requirements
3. Consider rolling back to last working version