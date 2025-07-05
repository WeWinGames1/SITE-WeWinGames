# Production Deployment Fix

## Issue: Missing AppServiceProvider and View Binding Errors

The following errors are occurring on production:
1. `AppServiceProvider.php`: Failed to open stream: No such file or directory
2. `Target class [view] does not exist`

## Root Cause
These errors typically occur when:
1. Files are missing on production server
2. Composer autoload hasn't been regenerated
3. Laravel's compiled files are outdated
4. The bootstrap/providers.php file is missing or outdated
5. View service provider isn't properly registered

## Fix Steps (Run on Production Server)

1. **Ensure all files are uploaded**:
   ```bash
   # Check if AppServiceProvider.php exists
   ls -la app/Providers/AppServiceProvider.php
   
   # Check if bootstrap/providers.php exists
   ls -la bootstrap/providers.php
   
   # Check file permissions
   ls -la bootstrap/cache/
   ```

2. **Clear all caches**:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   php artisan clear-compiled
   ```

3. **Regenerate Composer autoload**:
   ```bash
   composer dump-autoload -o
   ```

4. **Clear Laravel bootstrap cache**:
   ```bash
   rm -rf bootstrap/cache/*
   php artisan optimize:clear
   ```

5. **Regenerate optimized files**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan optimize
   ```

6. **Check file permissions**:
   ```bash
   # Ensure bootstrap/cache is writable
   chmod -R 775 bootstrap/cache
   chmod -R 775 storage
   ```

7. **If using OPcache, reset it**:
   ```bash
   # If you have CLI access to reset OPcache
   php -r "opcache_reset();"
   ```

## Prevention
1. Always run `composer dump-autoload -o` after deployment
2. Clear caches as part of deployment process
3. Ensure all files are properly uploaded (check .gitignore)
4. Use deployment scripts to automate these steps

## View Service Not Registered Fix
If diagnostics show "View service is NOT registered":

```bash
# Run the automated fix script
php fix-view-service.php
```

Or manually:
```bash
# 1. Clear bootstrap cache completely
rm -rf bootstrap/cache/*

# 2. Ensure cache directories exist
mkdir -p bootstrap/cache
mkdir -p storage/framework/views
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions

# 3. Set permissions
chmod -R 775 bootstrap/cache
chmod -R 775 storage

# 4. Regenerate everything
composer dump-autoload -o
php artisan optimize
```

## Emergency Fallback
If the above doesn't work:
```bash
# Force composer to rebuild everything
rm -rf vendor
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan optimize

# If still failing, check Laravel version
php artisan --version

# Ensure you're on Laravel 12
composer show laravel/framework
```