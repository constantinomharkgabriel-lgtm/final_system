<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

set_time_limit(300);

use App\Models\Flock;
use App\Models\FlockRecord;
use App\Services\EggGradingService;

echo "\n=== TESTING EGG UPDATE 50->75 ===\n\n";

$flock = Flock::find(1);
$existing_record = FlockRecord::where('flock_id', 1)
    ->whereDate('record_date', '2026-04-07')
    ->first();

echo "Current State:\n";
echo "  FlockRecord eggs_collected: {$existing_record->eggs_collected}\n\n";

echo "Step 1: Simulating update to 75 eggs...\n";
$start = microtime(true);

// Get the existing record's previous value
$prevEggs = $existing_record->eggs_collected; // 50
$newEggs = 75; // New value

echo "  Previous: $prevEggs\n";
echo "  New: $newEggs\n";
echo "  Difference: " . ($newEggs - $prevEggs) . "\n\n";

// Update the record in database
$existing_record->update(['eggs_collected' => $newEggs]);
echo "  ✓ FlockRecord updated\n\n";

// Now call the service with the new parameters
echo "Step 2: Calling EggGradingService...\n";
$service_start = microtime(true);

try {
    $eggService = new EggGradingService();
    $collection = $eggService->gradeAndCreateInventory(
        $flock,
        $newEggs,  // 75
        0,         // eggs_broken
        '2026-04-07',
        true,      // isIncremental (this is an update)
        $prevEggs  // 50 (previous value)
    );
    
    $service_time = microtime(true) - $service_start;
    
    echo "  ✓ Service completed in " . round($service_time, 2) . " seconds\n";
    echo "  Collection eggs: {$collection->eggs_collected}\n";
    echo "  Grade A: {$collection->graded_a}\n";
    echo "  Grade B: {$collection->graded_b}\n";
    echo "  Grade C: {$collection->graded_c}\n\n";
    
    // Check inventory totals
    $invs = \App\Models\EggInventory::where('flock_id', 1)
        ->whereDate('collection_date', '2026-04-07')
        ->get();
    
    $total = $invs->sum('quantity_total');
    echo "Step 3: Verify Inventory\n";
    echo "  Items: " . count($invs) . "\n";
    echo "  Total Eggs: $total\n";
    
    if ($total === $newEggs) {
        echo "  ✅ SUCCESS! Inventory matches new collection\n";
    } else {
        echo "  ⚠️  WARNING! Expected $newEggs but got $total\n";
    }
    
} catch (\Throwable $e) {
    echo "  ❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

$total_time = microtime(true) - $start;
echo "\nTotal time: " . round($total_time, 2) . " seconds\n";
echo "\n";
