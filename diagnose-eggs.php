<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Flock;
use App\Models\FlockRecord;
use App\Models\EggInventory;
use App\Models\EggCollection;

echo "\n=== EGG SYSTEM DIAGNOSIS ===\n\n";

// Check Flocks
$flocks = Flock::all();
echo "1. FLOCKS: " . count($flocks) . " found\n";
foreach ($flocks as $flock) {
    echo "   - Flock ID: {$flock->id}, Breed: {$flock->breed_type}, Status: {$flock->status}\n";
}

// Check Flock Records
$records = FlockRecord::all();
echo "\n2. FLOCK RECORDS: " . count($records) . " found\n";
foreach ($records as $rec) {
    echo "   - Date: {$rec->record_date}, Flock: {$rec->flock_id}, Eggs: {$rec->eggs_collected}\n";
}

// Check Egg Collections
$collections = EggCollection::all();
echo "\n3. EGG COLLECTIONS: " . count($collections) . " found\n";
foreach ($collections as $col) {
    echo "   - Date: {$col->collection_date}, Flock: {$col->flock_id}, Eggs: {$col->eggs_collected}, Batch: {$col->batch_id}\n";
}

// Check Egg Inventory
$inventories = EggInventory::all();
echo "\n4. EGG INVENTORY: " . count($inventories) . " found\n";
foreach ($inventories as $inv) {
    echo "   - Flock: {$inv->flock_id}, Type: {$inv->egg_type}, Grade: {$inv->grade}, Size: {$inv->size}, Total: {$inv->quantity_total}, Available: {$inv->quantity_available}\n";
}

// Check the last flock record for detailed info
if ($records->count() > 0) {
    echo "\n5. LAST FLOCK RECORD DETAILS:\n";
    $lastRecord = $records->last();
    echo "   Date: {$lastRecord->record_date}\n";
    echo "   Flock ID: {$lastRecord->flock_id}\n";
    echo "   Eggs Collected: {$lastRecord->eggs_collected}\n";
    echo "   Flock Breed: {$lastRecord->flock->breed_type}\n";
    
    // Check if there's a matching EggCollection
    $collection = EggCollection::where('flock_id', $lastRecord->flock_id)
        ->where('collection_date', $lastRecord->record_date)
        ->first();
    
    if ($collection) {
        echo "\n6. MATCHING EGG COLLECTION FOUND:\n";
        echo "   ID: {$collection->id}\n";
        echo "   Eggs Collected: {$collection->eggs_collected}\n";
        echo "   Graded A: {$collection->graded_a}\n";
        echo "   Graded B: {$collection->graded_b}\n";
        echo "   Graded C: {$collection->graded_c}\n";
        
        // Check related inventories
        $invs = EggInventory::where('egg_collection_id', $collection->id)->get();
        echo "\n7. INVENTORY FOR THIS COLLECTION: " . count($invs) . "\n";
        foreach ($invs as $inv) {
            echo "   - Grade {$inv->grade} {$inv->size}: {$inv->quantity_total} total, {$inv->quantity_available} available\n";
        }
    } else {
        echo "\nNO EGG COLLECTION FOUND FOR THIS RECORD\n";
    }
}

echo "\n";
