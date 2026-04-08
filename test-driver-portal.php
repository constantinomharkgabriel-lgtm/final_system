<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Driver;
use App\Models\User;
use App\Models\FarmOwner;

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║  DRIVER PORTAL SYSTEM TEST                               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Test 1: Check database for drivers
echo "TEST 1: Database Driver Check\n";
echo "─────────────────────────────────────────────────────────────\n";

$totalDrivers = Driver::count();
$verifiedDrivers = Driver::verified()->count();
$unverifiedDrivers = Driver::unverified()->count();

echo "✓ Total drivers in database: $totalDrivers\n";
echo "✓ Verified drivers: $verifiedDrivers\n";
echo "✓ Unverified drivers: $unverifiedDrivers\n\n";

// Test 2: Check specific verified driver
echo "TEST 2: Verified Driver Access\n";
echo "─────────────────────────────────────────────────────────────\n";

$verifiedDriver = Driver::verified()->first();

if ($verifiedDriver) {
    echo "✓ Found verified driver\n";
    echo "  • Name: {$verifiedDriver->name}\n";
    echo "  • Email: {$verifiedDriver->email}\n";
    echo "  • Is Verified: " . ($verifiedDriver->is_verified ? '✓ Yes' : '✗ No') . "\n";
    echo "  • Verified At: " . ($verifiedDriver->verified_at ? $verifiedDriver->verified_at->format('Y-m-d H:i:s') : 'N/A') . "\n";
    
    // Test 3: Check User relationship
    echo "\nTEST 3: User-Driver Relationship\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $user = $verifiedDriver->user;
    if ($user) {
        echo "✓ User relationship working\n";
        echo "  • User ID: {$user->id}\n";
        echo "  • User Name: {$user->name}\n";
        echo "  • User Email: {$user->email}\n";
        echo "  • User Role: {$user->role}\n";
        
        // Test 4: Check reverse relationship
        echo "\nTEST 4: Reverse Driver Relationship (User->Driver)\n";
        echo "─────────────────────────────────────────────────────────────\n";
        
        $driverViaUser = $user->driver;
        if ($driverViaUser && $driverViaUser->id === $verifiedDriver->id) {
            echo "✓ Reverse relationship working correctly\n";
            echo "  • Can access driver from user: YES\n";
        } else {
            echo "✗ Reverse relationship failed\n";
        }
        
    } else {
        echo "✗ User relationship not found\n";
    }
    
} else {
    if ($unverifiedDrivers > 0) {
        echo "ℹ No verified drivers yet, but unverified drivers found\n";
        echo "  You can:\n";
        echo "  1. Run test-driver-verification.php to create and verify a test driver\n";
        echo "  2. Add a driver via HR portal and verify email\n";
    } else {
        echo "⚠ No drivers found in database\n";
        echo "  First add a driver via HR portal, then verify the email\n";
    }
}

// Test 5: Check Driver Portal Routes
echo "\n\nTEST 5: Driver Portal Routes\n";
echo "─────────────────────────────────────────────────────────────\n";

$routes = [
    'driver.dashboard' => '/driver/dashboard',
    'driver.deliveries' => '/driver/deliveries',
    'driver.profile' => '/driver/profile',
    'driver.earnings' => '/driver/earnings',
    'driver.login' => '/driver/login',
    'driver.verification.pending' => '/driver/verification-pending',
];

$registeredRoutes = collect(\Illuminate\Support\Facades\Route::getRoutes())
    ->pluck('name')
    ->filter(fn($name) => strpos($name, 'driver') !== false)
    ->toArray();

echo "Configured driver routes:\n";
foreach ($routes as $name => $path) {
    $exists = in_array($name, $registeredRoutes);
    echo ($exists ? "✓" : "✗") . " {$name} → {$path}\n";
}

// Test 6: Check Controllers
echo "\n\nTEST 6: Driver Controllers\n";
echo "─────────────────────────────────────────────────────────────\n";

$controllers = [
    'DriverPortalController' => 'App\Http\Controllers\DriverPortalController',
    'DriverAuthController' => 'App\Http\Controllers\DriverAuthController',
    'DriverVerificationController' => 'App\Http\Controllers\DriverVerificationController',
];

foreach ($controllers as $name => $class) {
    $exists = class_exists($class);
    echo ($exists ? "✓" : "✗") . " $name\n";
}

// Test 7: Check Views
echo "\n\nTEST 7: Driver Portal Views\n";
echo "─────────────────────────────────────────────────────────────\n";

$views = [
    'driver.auth.login',
    'driver.auth.verification-pending',
    'driver.dashboard',
    'driver.profile',
    'driver.deliveries.index',
    'driver.deliveries.show',
    'driver.earnings',
];

foreach ($views as $view) {
    $path = str_replace('.', '/', $view);
    $filePath = "resources/views/$path.blade.php";
    $exists = file_exists($filePath);
    echo ($exists ? "✓" : "✗") . " $view\n";
}

// Summary
echo "\n\n╔════════════════════════════════════════════════════════════╗\n";
echo "║  NEXT STEPS                                               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

if ($verifiedDrivers > 0) {
    echo "✓ Driver portal is READY TO USE!\n\n";
    echo "To test the driver portal:\n";
    echo "1. Navigate to: http://localhost:8000/driver/login\n";
    echo "2. Log in with the verified driver's credentials\n";
    echo "3. You should be redirected to: http://localhost:8000/driver/dashboard\n";
    echo "\nDriver portal features:\n";
    echo "  • Dashboard: View deliveries summary and recent deliveries\n";
    echo "  • Deliveries: List, accept, and manage deliveries\n";
    echo "  • Profile: View and edit driver profile\n";
    echo "  • Earnings: Track earnings and completed deliveries\n";
} else if ($unverifiedDrivers > 0) {
    echo "✓ Unverified drivers exist!\n\n";
    echo "To activate a driver:\n";
    echo "1. Run: php test-driver-verification.php\n";
    echo "2. Or manually verify a driver by clicking the email link\n";
    echo "3. Then test the driver portal login\n";
} else {
    echo "⚠ No drivers found in the system\n\n";
    echo "To test the driver portal:\n";
    echo "1. Add a driver via HR portal (main system)\n";
    echo "2. Verify the driver's email (click the link)\n";
    echo "3. Then navigate to the driver login page\n";
}

echo "\n";
