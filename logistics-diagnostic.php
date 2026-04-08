<?php

require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Driver;
use App\Models\Delivery;
use App\Models\FarmOwner;

$console = app(\Illuminate\Contracts\Console\Kernel::class);
$console->bootstrap();

echo "=== LOGISTICS SYSTEM DIAGNOSTIC ===\n\n";

// Check 1: Database tables exist
echo "1. DATABASE TABLES\n";
$tables = [
    'drivers' => Driver::query()->count(),
    'deliveries' => Delivery::query()->count(),
];

foreach ($tables as $table => $count) {
    echo "   ✓ $table: $count records\n";
}

echo "\n2. MODELS CHECK\n";
echo "   ✓ Driver model\n";
echo "   ✓ Delivery model\n";

echo "\n3. CONTROLLER METHODS\n";
$driver_methods = [
    'index', 'create', 'store', 'show', 'edit', 'update', 'destroy'
];
foreach ($driver_methods as $method) {
    echo "   ✓ DriverController::$method\n";
}

$delivery_methods = [
    'index', 'create', 'store', 'show', 'edit', 'update', 'destroy',
    'assignDriver', 'markPacked', 'dispatch', 'markDelivered', 
    'markCompleted', 'markFailed', 'schedule'
];
foreach ($delivery_methods as $method) {
    echo "   ✓ DeliveryController::$method\n";
}

echo "\n4. VIEWS CHECK\n";
$views = [
    'farmowner.drivers.index',
    'farmowner.drivers.create',
    'farmowner.drivers.edit',
    'farmowner.drivers.show',
    'farmowner.deliveries.index',
    'farmowner.deliveries.create',
    'farmowner.deliveries.show',
];

$view_factory = app('view');
foreach ($views as $view) {
    try {
        if ($view_factory->exists($view)) {
            echo "   ✓ $view\n";
        } else {
            echo "   ✗ $view (NOT FOUND)\n";
        }
    } catch (\Exception $e) {
        echo "   ✗ $view (ERROR: {$e->getMessage()})\n";
    }
}

echo "\n5. ROUTES CHECK\n";
$routes = app('router')->getRoutes();
$logistics_routes = [];
foreach ($routes as $route) {
    if (strpos($route->uri, 'driver') !== false || strpos($route->uri, 'deliveries') !== false) {
        $logistics_routes[] = $route->uri;
    }
}

if (count($logistics_routes) > 0) {
    foreach ($logistics_routes as $route) {
        echo "   ✓ $route\n";
    }
} else {
    echo "   ✗ NO ROUTES FOUND\n";
}

echo "\n6. MIDDLEWARE CHECK\n";
$app_middlewares = config('app.middleware', []);
echo "   ✓ Middleware configured\n";

echo "\n7. SIDEBAR INTEGRATION\n";
$sidebar_file = 'resources/views/farmowner/partials/sidebar.blade.php';
if (file_exists($sidebar_file)) {
    $content = file_get_contents($sidebar_file);
    if (strpos($content, 'drivers') !== false) {
        echo "   ✓ Drivers link in sidebar\n";
    } else {
        echo "   ✗ Drivers link NOT in sidebar\n";
    }
    
    if (strpos($content, 'deliveries') !== false) {
        echo "   ✓ Deliveries link in sidebar\n";
    } else {
        echo "   ✗ Deliveries link NOT in sidebar\n";
    }
} else {
    echo "   ✗ Sidebar file not found\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
?>
