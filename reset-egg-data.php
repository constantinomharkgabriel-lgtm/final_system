<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EggCollection;
use App\Models\EggInventory;
use App\Models\FlockRecord;
use App\Services\EggGradingService;

echo "\n=== RESETTING EGG DATA FOR FLOCK ===\n\n";

// Get flock 1
$flock = \App\Models\Flock::find(1);
if (!$flock) {
    echo "Flock not found!\n";
    exit();
}

echo "Flock: {$flock->id} ({$flock->breed_type})\n";
echo "Farm Owner: {$flock->farm_owner_id}\n\n";

// Find the Apr 07 record
$record = FlockRecord::where('flock_id', 1)
    ->whereDate('record_date', '2026-04-07')
    ->first();

if (!$record) {
    echo "No record found for Apr 07\n";
    exit();
}

echo "FlockRecord Apr 07:\n";
echo "  Eggs Collected: {$record->eggs_collected}\n";
echo "  Eggs Broken: {$record->eggs_broken}\n\n";

// DELETE all egg collections for this flock/date
$deletedCollections = EggCollection::where('flock_id', 1)
    ->whereDate('collection_date', '2026-04-07')
    ->delete();
echo "Deleted {$deletedCollections} egg collections\n";

// DELETE all inventories for this flock/date
$deletedInventories = EggInventory::where('flock_id', 1)
    ->whereDate('collection_date', '2026-04-07')
    ->delete();
echo "Deleted {$deletedInventories} inventory items\n\n";

echo "REBUILDING EGG COLLECTION & INVENTORY...\n";

// Now rebuild the egg collection and inventory with the correct data
$eggService = new EggGradingService();
try {
    $collection = $eggService->gradeAndCreateInventory(
        $flock,
        $record->eggs_collected,  // 50
        $record->eggs_broken,      // 0
        $record->record_date->toDateString(),
        false  // NOT an incremental update, first time
    );
    
    echo "\n✓ EggCollection created successfully\n";
    echo "  ID: {$collection->id}\n";
    echo "  Eggs Collected: {$collection->eggs_collected}\n";
    echo "  Graded A: {$collection->graded_a}\n";
    echo "  Graded B: {$collection->graded_b}\n";
    echo "  Graded C: {$collection->graded_c}\n";
    
    // Check inventories
    $invs = EggInventory::where('egg_collection_id', $collection->id)->get();
    echo "\n✓ EggInventory items created: " . count($invs) . "\n";
    
    $total = 0;
    foreach ($invs as $inv) {
        $total += $inv->quantity_total;
        echo "  - Grade {$inv->grade} {$inv->size}: {$inv->quantity_total} eggs\n";
    }
    echo "\n  TOTAL EGGS IN INVENTORY: {$total}\n";
    
    if ($total == $record->eggs_collected) {
        echo "  ✓ CORRECT! Matches FlockRecord ({$record->eggs_collected})\n";
    } else {
        echo "  ❌ ERROR! Should be {$record->eggs_collected} but got {$total}\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n";
