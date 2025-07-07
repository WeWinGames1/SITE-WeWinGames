<?php

/**
 * Fix for view service binding issue in Laravel 12
 */

require_once 'vendor/autoload.php';

echo "Attempting to fix view service binding...\n\n";

// First, let's check what providers are being loaded
echo "1. Checking registered providers:\n";
$providers = require 'bootstrap/providers.php';
foreach ($providers as $provider) {
    echo "   - $provider\n";
}

// Check if ViewServiceProvider should be manually added
echo "\n2. Checking if Illuminate ViewServiceProvider exists:\n";
$viewProviderClass = 'Illuminate\View\ViewServiceProvider';
if (class_exists($viewProviderClass)) {
    echo "   ✓ ViewServiceProvider class exists\n";

    // Check if it's in the default providers
    $defaultProviders = [
        'Illuminate\Auth\AuthServiceProvider',
        'Illuminate\Broadcasting\BroadcastServiceProvider',
        'Illuminate\Bus\BusServiceProvider',
        'Illuminate\Cache\CacheServiceProvider',
        'Illuminate\Foundation\Providers\ConsoleSupportServiceProvider',
        'Illuminate\Cookie\CookieServiceProvider',
        'Illuminate\Database\DatabaseServiceProvider',
        'Illuminate\Encryption\EncryptionServiceProvider',
        'Illuminate\Filesystem\FilesystemServiceProvider',
        'Illuminate\Foundation\Providers\FoundationServiceProvider',
        'Illuminate\Hashing\HashServiceProvider',
        'Illuminate\Mail\MailServiceProvider',
        'Illuminate\Notifications\NotificationServiceProvider',
        'Illuminate\Pagination\PaginationServiceProvider',
        'Illuminate\Pipeline\PipelineServiceProvider',
        'Illuminate\Queue\QueueServiceProvider',
        'Illuminate\Redis\RedisServiceProvider',
        'Illuminate\Auth\Passwords\PasswordResetServiceProvider',
        'Illuminate\Session\SessionServiceProvider',
        'Illuminate\Translation\TranslationServiceProvider',
        'Illuminate\Validation\ValidationServiceProvider',
        'Illuminate\View\ViewServiceProvider',
    ];

    echo "\n3. Creating a ViewServiceProvider override:\n";

    $customProvider = '<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\ViewServiceProvider as BaseViewServiceProvider;

class CustomViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Manually register the view service if not already registered
        if (!$this->app->bound("view")) {
            $baseProvider = new BaseViewServiceProvider($this->app);
            $baseProvider->register();
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
';

    file_put_contents('app/Providers/CustomViewServiceProvider.php', $customProvider);
    echo "   ✓ Created app/Providers/CustomViewServiceProvider.php\n";

    // Add to providers.php if not already there
    $providersContent = file_get_contents('bootstrap/providers.php');
    if (strpos($providersContent, 'CustomViewServiceProvider') === false) {
        $providersContent = str_replace(
            'return [',
            "return [\n    App\Providers\CustomViewServiceProvider::class,",
            $providersContent
        );
        file_put_contents('bootstrap/providers.php', $providersContent);
        echo "   ✓ Added CustomViewServiceProvider to bootstrap/providers.php\n";
    }

    echo "\n4. Clear caches and regenerate:\n";
    echo "   Run these commands:\n";
    echo "   rm -rf bootstrap/cache/*\n";
    echo "   composer dump-autoload -o\n";
    echo "   php artisan optimize\n";

} else {
    echo "   ✗ ViewServiceProvider class does not exist - Laravel installation may be incomplete\n";
}

echo "\nDone.\n";
