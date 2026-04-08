<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\FlockRecord;
use App\Services\EggGradingService;
use App\Models\Flock;

echo "\n=== FORCE CLEANING AND REBUILDING EGG DATA ===\n\n";

$flock_id = 1;
$date = '2026-04-07';

// Force delete using raw query
$deleted = DB::table('egg_inventory')
    ->where('flock_id', $flock_id)
    ->whereDate('collection_date', $date)
    ->delete();
echo "✓ Deleted $deleted egg inventory records\n";

$deleted = DB::table('egg_collections')
    ->where('flock_id', $flock_id)
    ->whereDate('collection_date', $date)
    ->delete();
echo "✓ Deleted $deleted egg collection records\n\n";

// Get flock and record
$flock = Flock::find($flock_id);
$record = FlockRecord::where('flock_id', $flock_id)
    ->whereDate('record_date', $date)
    ->first();

echo "Rebuilding with:\n";
echo "  Flock: {$flock->id} ({$flock->breed_type})\n";
echo "  Eggs Collected: {$record->eggs_collected}\n";
echo "  Date: $date\n\n";

// Rebuild the egg collection
$eggService = new EggGradingService();
$collection = $eggService->gradeAndCreateInventory(
    $flock,
    (int)$record->eggs_collected,
    (int)($record->eggs_broken ?? 0),
    $date,
    false  // First time, not incremental
);

echo "✓ EggCollection created: {$collection->id}\n";
echo "  Eggs: {$collection->eggs_collected}\n";
echo "  Grade A: {$collection->graded_a}\n";
echo "  Grade B: {$collection->graded_b}\n";
echo "  Grade C: {$collection->graded_c}\n\n";

// Verify inventory
$total = 0;
$invs = DB::table('egg_inventory')
    ->where('flock_id', $flock_id)
    ->whereDate('collection_date', $date)
    ->get();

echo "✓ EggInventory items: " . count($invs) . "\n";
foreach ($invs as $inv) {
    $total += $inv->quantity_total;
    echo "  - Grade {$inv->grade} {$inv->size}: {$inv->quantity_total} eggs\n";
}

echo "\n";
if ($total == $record->eggs_collected) {
    echo "✅ SUCCESS! Total inventory: $total (matches FlockRecord: {$record->eggs_collected})\n";
} else {
    echo "⚠️  WARNING! Total inventory: $total (doesn't match FlockRecord: {$record->eggs_collected})\n";
}

echo "\n";
