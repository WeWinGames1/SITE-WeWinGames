<?php

/**
 * Temporary fix for view service registration issue
 * Run this on production if the view service is not being registered
 */
echo "Attempting to fix view service registration...\n\n";

// Clear all caches
echo "1. Clearing all caches...\n";
system('php artisan cache:clear');
system('php artisan config:clear');
system('php artisan route:clear');
system('php artisan view:clear');
system('php artisan clear-compiled');

echo "\n2. Removing bootstrap cache...\n";
system('rm -rf bootstrap/cache/*');

echo "\n3. Regenerating composer autoload...\n";
system('composer dump-autoload -o');

echo "\n4. Creating cache directories...\n";
system('mkdir -p bootstrap/cache');
system('mkdir -p storage/framework/views');
system('mkdir -p storage/framework/cache');
system('mkdir -p storage/framework/sessions');

echo "\n5. Setting permissions...\n";
system('chmod -R 775 bootstrap/cache');
system('chmod -R 775 storage');

echo "\n6. Rebuilding Laravel cache...\n";
system('php artisan optimize');

echo "\n7. Checking if view service is now registered...\n";
try {
    // Load composer autoloader first
    require_once 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    if (method_exists($app, 'bound') && $app->bound('view')) {
        echo "✓ SUCCESS: View service is now registered!\n";
    } else {
        echo "✗ FAILED: View service is still not registered.\n";
        echo "\nTrying alternative fix...\n";

        // Alternative: manually register the view service provider
        echo "Creating temporary ViewServiceProvider registration...\n";

        $content = '<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // View service should be registered by framework
    }

    public function boot(): void
    {
        // Ensure view paths are set
        if ($this->app->bound("view")) {
            $this->app["view"]->addLocation(resource_path("views"));
        }
    }
}
';

        file_put_contents('app/Providers/ViewServiceProvider.php', $content);

        // Add to providers
        $providers = file_get_contents('bootstrap/providers.php');
        if (strpos($providers, 'ViewServiceProvider') === false) {
            $providers = str_replace(
                'App\Providers\RepositoryServiceProvider::class,',
                'App\Providers\RepositoryServiceProvider::class,'."\n    App\Providers\ViewServiceProvider::class,",
                $providers
            );
            file_put_contents('bootstrap/providers.php', $providers);
        }

        echo "Added ViewServiceProvider to bootstrap/providers.php\n";
        echo "Run 'composer dump-autoload -o' and 'php artisan optimize' again.\n";
    }
} catch (Exception $e) {
    echo '✗ ERROR: '.$e->getMessage()."\n";
}

echo "\nFix script complete.\n";
