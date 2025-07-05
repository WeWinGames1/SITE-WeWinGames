<?php
/**
 * Simple test to verify Composer autoload is working
 */

echo "Testing Composer Autoload\n";
echo "========================\n\n";

// Test 1: Can we load the autoloader?
echo "1. Loading vendor/autoload.php... ";
if (!file_exists('vendor/autoload.php')) {
    die("FAILED: vendor/autoload.php does not exist. Run 'composer install'\n");
}

require_once 'vendor/autoload.php';
echo "OK\n";

// Test 2: Can we load Laravel classes?
echo "2. Testing Laravel Foundation classes... ";
if (!class_exists('Illuminate\Foundation\Application')) {
    die("FAILED: Cannot load Illuminate\Foundation\Application\n");
}
echo "OK\n";

// Test 3: Can we load app classes?
echo "3. Testing App classes... ";
if (!class_exists('App\Providers\AppServiceProvider')) {
    die("FAILED: Cannot load App\Providers\AppServiceProvider\n");
}
echo "OK\n";

// Test 4: Can we bootstrap Laravel?
echo "4. Bootstrapping Laravel... ";
try {
    $app = require_once 'bootstrap/app.php';
    echo "OK\n";
    
    // Test 5: Is view service registered?
    echo "5. Checking view service... ";
    if ($app->bound('view')) {
        echo "OK - View service is registered\n";
    } else {
        echo "FAILED - View service is NOT registered\n";
    }
} catch (Exception $e) {
    echo "FAILED\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}

echo "\nIf all tests pass except #5, run:\n";
echo "  rm -rf bootstrap/cache/*\n";
echo "  php artisan optimize\n";