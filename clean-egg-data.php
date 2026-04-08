<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EggCollection;
use App\Models\EggInventory;
use App\Models\FlockRecord;

echo "\n=== CLEANING EGG SYSTEM DATA ===\n\n";

// Get all flock records that have eggs
$flockRecords = FlockRecord::where('eggs_collected', '>', 0)->get();

echo "Processing " . count($flockRecords) . " flock records with eggs...\n\n";

foreach ($flockRecords as $record) {
    echo "Flock {$record->flock_id}, Date: {$record->record_date}, Eggs Collected: {$record->eggs_collected}\n";
    
    // Find ALL egg collections for this date
    $collections = EggCollection::where('flock_id', $record->flock_id)
        ->whereDate('collection_date', $record->record_date)
        ->orderBy('created_at')
        ->get();
    
    echo "  Found " . count($collections) . " egg collections\n";
    
    if (count($collections) > 1) {
        echo "  DUPLICATE FOUND! Cleaning up...\n";
        
        // Keep the first one, delete others
        foreach ($collections->skip(1) as $dup) {
            echo "    Deleting collection {$dup->id} (batch: {$dup->batch_id})...\n";
            $dup->delete();
        }
    }
    
    // Now check the remaining collection
    $collection = EggCollection::where('flock_id', $record->flock_id)
        ->whereDate('collection_date', $record->record_date)
        ->first();
    
    if ($collection) {
        echo "  EggCollection: {$collection->eggs_collected} eggs (should be {$record->eggs_collected})\n";
        
        if ($collection->eggs_collected != $record->eggs_collected) {
            echo "  MISMATCH! Updating EggCollection...\n";
            $collection->update([
                'eggs_collected' => $record->eggs_collected,
            ]);
        }
        
        // Check inventory for this collection
        $invs = EggInventory::where('egg_collection_id', $collection->id)->get();
        echo "  EggInventory items: " . count($invs) . "\n";
        foreach ($invs as $inv) {
            echo "    - Grade {$inv->grade} {$inv->size}: {$inv->quantity_total} total\n";
        }
    }
    
    echo "\n";
}

echo "=== CLEANING COMPLETE ===\n\n";
