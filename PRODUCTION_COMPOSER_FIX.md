# Production Composer Fix

## Error
```
Class "Knuckles\Scribe\Config\AuthIn" not found
Script @php artisan package:discover --ansi handling the post-autoload-dump event returned with error code 1
```

## Solution

### Option 1: Quick Fix (Recommended)
Run these commands on your production server:

```bash
# 1. Remove the problematic config file
rm -f config/scribe.php

# 2. Clear all caches
php artisan config:clear
rm -rf bootstrap/cache/*
touch bootstrap/cache/.gitkeep

# 3. Install composer dependencies without dev packages
composer install --no-dev --optimize-autoloader

# 4. Optimize
php artisan optimize
```

### Option 2: If you need to keep scribe.php
Upload the modified `config/scribe.php` file that includes the class check at the top.

### Option 3: Nuclear Option
```bash
# Complete reset
rm -rf vendor
rm -f composer.lock
rm -rf bootstrap/cache/*
touch bootstrap/cache/.gitkeep

# Fresh install
composer install --no-dev --optimize-autoloader
php artisan optimize
```

## Why This Happened
- `knuckleswtf/scribe` is a development dependency
- The config file tries to use classes from this package
- In production with `--no-dev`, these classes don't exist
- Laravel tries to load the config file anyway

## Prevention
1. Don't commit development tool configs to production
2. Or add checks like we did in the modified scribe.php
3. Use environment-specific config loading

## Verify Fix
After running the fix:
```bash
php artisan about
php artisan config:cache
```

Both commands should run without errors.