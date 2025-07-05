<?php
/**
 * Production Diagnostic Script
 * Run this to check for common deployment issues
 */

echo "Laravel Production Diagnostics\n";
echo "==============================\n\n";

// Check PHP version
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Laravel should work with PHP 8.2 or higher\n\n";

// Check if key files exist
$files_to_check = [
    'app/Providers/AppServiceProvider.php',
    'bootstrap/providers.php',
    'bootstrap/app.php',
    'vendor/autoload.php',
    'bootstrap/cache/packages.php',
    'bootstrap/cache/services.php',
];

echo "Checking critical files:\n";
foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    $status = $exists ? "✓ EXISTS" : "✗ MISSING";
    echo sprintf("%-50s %s\n", $file, $status);
    
    if ($exists && strpos($file, 'bootstrap/cache') !== false) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo sprintf("  Permissions: %s\n", $perms);
    }
}

echo "\n";

// Check directory permissions
$dirs_to_check = [
    'bootstrap/cache',
    'storage',
    'storage/app',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
];

echo "Checking directory permissions:\n";
foreach ($dirs_to_check as $dir) {
    if (is_dir($dir)) {
        $writable = is_writable($dir);
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $status = $writable ? "✓ WRITABLE" : "✗ NOT WRITABLE";
        echo sprintf("%-50s %s (perms: %s)\n", $dir, $status, $perms);
    } else {
        echo sprintf("%-50s ✗ DOES NOT EXIST\n", $dir);
    }
}

echo "\n";

// Check if composer autoload is working
echo "Checking Composer autoload:\n";
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    
    $classes_to_check = [
        'App\Providers\AppServiceProvider',
        'Illuminate\Support\ServiceProvider',
        'Illuminate\View\ViewServiceProvider',
    ];
    
    foreach ($classes_to_check as $class) {
        $exists = class_exists($class);
        $status = $exists ? "✓ FOUND" : "✗ NOT FOUND";
        echo sprintf("%-50s %s\n", $class, $status);
    }
} else {
    echo "✗ vendor/autoload.php not found - run 'composer install'\n";
}

echo "\n";

// Check Laravel bootstrap
echo "Checking Laravel bootstrap:\n";
if (file_exists('bootstrap/app.php')) {
    try {
        $app = require_once 'bootstrap/app.php';
        echo "✓ Bootstrap file loaded successfully\n";
        
        // Check if view service is registered
        if (method_exists($app, 'bound') && $app->bound('view')) {
            echo "✓ View service is registered\n";
        } else {
            echo "✗ View service is NOT registered\n";
        }
    } catch (Exception $e) {
        echo "✗ Error loading bootstrap: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ bootstrap/app.php not found\n";
}

echo "\n";
echo "Diagnostics complete.\n";