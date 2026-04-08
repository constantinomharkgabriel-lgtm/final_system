<?php
require 'vendor/autoload.php';

use App\Models\Driver;

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

echo "=== Checking Drivers Table ===\n\n";

$drivers = Driver::withTrashed()->select('id', 'name', 'phone', 'email', 'is_verified', 'deleted_at')->get();

if ($drivers->isEmpty()) {
    echo "No drivers found in database.\n";
} else {
    foreach ($drivers as $driver) {
        echo "ID: {$driver->id}\n";
        echo "  Name: {$driver->name}\n";
        echo "  Phone: " . ($driver->phone ?? 'NULL') . "\n";
        echo "  Email: {$driver->email}\n";
        echo "  Verified: " . ($driver->is_verified ? 'Yes' : 'No') . "\n";
        echo "  Deleted: " . ($driver->deleted_at ? 'Yes (' . $driver->deleted_at . ')' : 'No') . "\n";
        echo "\n";
    }
}

// Check for duplicate phones
echo "\n=== Checking for Duplicate Phones ===\n";
$phoneCounts = Driver::withTrashed()
    ->select('phone')
    ->groupBy('phone')
    ->havingRaw('COUNT(*) > 1')
    ->get();

if ($phoneCounts->isEmpty()) {
    echo "No duplicate phones found.\n";
} else {
   foreach ($phoneCounts as $phoneGroup) {
        echo "Phone '" . ($phoneGroup->phone ?? 'NULL') . "' appears multiple times\n";
    }
}
