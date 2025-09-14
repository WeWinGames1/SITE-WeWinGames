# Production Deployment Fix

## Issue
The production server is showing raw JSON instead of rendering the Inertia/Vue pages.

## Root Cause
The Inertia middleware is not recognizing the request as a normal page visit and is returning JSON response instead of HTML.

## Fix Steps

### 1. Clear All Caches
```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### 2. Rebuild Assets for Production
```bash
# Fix npm PATH if needed
export PATH="/opt/nvm/versions/node/v22.17.0/bin:$PATH"

# Clean install dependencies
rm -rf node_modules
npm install --maxsockets 1

# Build production assets
npm run build

# Verify manifest exists
ls -la public/build/manifest.json
```

### 3. Re-optimize Laravel
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
```

### 4. Check File Permissions
```bash
# Ensure storage is writable
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Ensure build directory is readable
chmod -R 755 public/build
```

### 5. Verify .htaccess (if using Apache)
Ensure your public/.htaccess file has the correct headers:
```apache
<IfModule mod_headers.c>
    # Force text/html for root requests
    <FilesMatch "^(?!.*\.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)).*$">
        Header set X-Inertia "true"
    </FilesMatch>
</IfModule>
```

### 6. Check Nginx Configuration (if using Nginx)
Ensure your nginx config includes:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
    
    # Add Inertia headers
    fastcgi_param HTTP_X_INERTIA true;
}
```

### 7. Environment Check
Ensure these are set in production .env:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://wewingames.com

# Ensure session config is correct
SESSION_DOMAIN=.wewingames.com
SESSION_SECURE_COOKIE=true
```

### 8. Test with curl
Test if the server responds correctly:
```bash
# This should return HTML
curl -H "Accept: text/html" https://wewingames.com/login

# This should return JSON
curl -H "Accept: application/json" -H "X-Inertia: true" https://wewingames.com/login
```

### 9. Emergency Fix (if nothing else works)
Add this to the top of `app/Http/Middleware/HandleInertiaRequests.php`:
```php
public function handle($request, $next)
{
    // Force HTML response for non-Inertia requests
    if (!$request->header('X-Inertia')) {
        $request->headers->set('Accept', 'text/html');
    }
    
    return parent::handle($request, $next);
}
```

### 10. Restart Services
```bash
# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Restart web server
sudo systemctl restart nginx  # or apache2
```

## Verification
After applying these fixes, the login page should render properly with the Bootstrap UI instead of showing raw JSON.