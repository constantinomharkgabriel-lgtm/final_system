<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EggInventory;

echo "\n=== FINAL INVENTORY VERIFICATION ===\n\n";

$invs = EggInventory::where('flock_id', 1)
    ->whereDate('collection_date', '2026-04-07')
    ->orderBy('grade')
    ->orderBy('size')
    ->get();

echo "EggInventory Items for Apr 07:\n";
$total = 0;
foreach ($invs as $inv) {
    echo "  Grade {$inv->grade} {$inv->size}: {$inv->quantity_total} total, {$inv->quantity_available} available\n";
    $total += $inv->quantity_total;
}

echo "\nTotal Eggs: $total\n";
echo "\nExpected: 75 (50 initial + 25 update)\n";
echo "Match: " . ($total === 75 ? "✅ YES" : "❌ NO") . "\n";

echo "\n";
