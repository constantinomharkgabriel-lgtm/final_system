<?php

namespace App\Services;

use App\Models\Flock;
use App\Models\EggCollection;
use App\Models\EggInventory;
use App\Models\Product;
use Carbon\Carbon;

class EggGradingService
{
    /**
     * Auto-grade and create inventory entries from daily record
     * 
     * @param Flock $flock
     * @param int $eggsCollected - Total eggs collected for the day
     * @param int $eggsBroken
     * @param string $date
     * @param bool $isIncremental - If true, this is an update to existing record
     * @param int $prevEggsCollected - Previous eggs collected (when updating)
     */
    public function gradeAndCreateInventory(
        Flock $flock, 
        int $eggsCollected, 
        int $eggsBroken, 
        string $date, 
        bool $isIncremental = false,
        int $prevEggsCollected = 0
    )
    {
        // Calculate the actual change in eggs
        $eggsToAdd = $eggsCollected;
        if ($isIncremental) {
            $eggsToAdd = $eggsCollected - $prevEggsCollected;
        }
        
        // If no net change in eggs, just return early
        if ($eggsToAdd <= 0) {
            return EggCollection::where('farm_owner_id', $flock->farm_owner_id)
                ->where('flock_id', $flock->id)
                ->where('collection_date', Carbon::parse($date)->startOfDay())
                ->first();
        }

        // Step 1: Determine egg type from flock breed
        $eggType = $this->determineEggType($flock);
        
        // Step 2: Auto-grade distribution (based on NEW eggs being added)
        $grades = $this->calculateGradeDistribution($eggsToAdd);
        
        // Parse date once for consistency
        $collectionDate = Carbon::parse($date)->startOfDay();
        
        // Step 3: Update or create egg collection record
        // IMPORTANT: Store the ACTUAL eggs_collected value (total for the day, not the difference)
        $collection = EggCollection::updateOrCreate(
            [
                'farm_owner_id' => $flock->farm_owner_id,
                'flock_id' => $flock->id,
                'collection_date' => $collectionDate,
            ],
            [
                'eggs_collected' => $eggsCollected,  // TOTAL eggs for the day (from FlockRecord)
                'eggs_broken' => $eggsBroken,
                'graded_a' => $grades['A'],         // Grade distribution for TODAY's collection
                'graded_b' => $grades['B'],
                'graded_c' => $grades['C'],
                'batch_id' => "FO-{$flock->farm_owner_id}-{$flock->id}-" . now()->format('YmdHis'),
            ]
        );
        
        // Step 4: Update or create inventory entries for each grade & size combination (add only NEW eggs)
        foreach (['A', 'B', 'C'] as $grade) {
            $gradeTotal = $grades[$grade];
            if ($gradeTotal <= 0) continue;
            
            // Calculate size distribution
            $largePercentage = $this->calculateLargePercentage($flock);
            $largeCnt = ceil($gradeTotal * $largePercentage);
            $smallCnt = $gradeTotal - $largeCnt;
            
            // Set freshness expire days based on grade
            $expireDays = match ($grade) {
                'A' => 14,
                'B' => 10,
                'C' => 7,
            };
            
            // Handle LARGE eggs
            if ($largeCnt > 0) {
                $existingLarge = EggInventory::where('farm_owner_id', $flock->farm_owner_id)
                    ->where('flock_id', $flock->id)
                    ->where('egg_type', $eggType)
                    ->where('grade', $grade)
                    ->where('size', 'large')
                    ->whereDate('collection_date', $collectionDate->toDateString())
                    ->first();
                
                // Calculate expiry date (use copy to avoid mutating $collectionDate)
                $expiryDate = $collectionDate->copy()->addDays($expireDays);
                
                if ($existingLarge) {
                    // UPDATE: ADD new eggs to existing quantity
                    $quantitySold = $existingLarge->quantity_sold ?? 0;
                    $newTotal = $existingLarge->quantity_total + $largeCnt;
                    $newAvailable = $newTotal - $quantitySold;
                    $existingLarge->update([
                        'quantity_total' => $newTotal,
                        'quantity_available' => $newAvailable,
                        'freshness_expires_at' => $expiryDate,
                    ]);
                    $largEggInventory = $existingLarge;
                } else {
                    // CREATE: New inventory item
                    $largEggInventory = EggInventory::create([
                        'farm_owner_id' => $flock->farm_owner_id,
                        'flock_id' => $flock->id,
                        'egg_collection_id' => $collection->id,
                        'egg_type' => $eggType,
                        'grade' => $grade,
                        'size' => 'large',
                        'quantity_total' => $largeCnt,
                        'quantity_available' => $largeCnt,
                        'quantity_sold' => 0,
                        'quantity_expired' => 0,
                        'collection_date' => $collectionDate,
                        'freshness_expires_at' => $expiryDate,
                        'status' => 'fresh',
                        'batch_id' => $collection->batch_id,
                    ]);
                    
                    // Only create product on FIRST inventory creation (not on updates)
                    $this->createOrUpdateProduct(
                        $flock->farm_owner_id,
                        $eggType,
                        $grade,
                        'large',
                        $largEggInventory->id
                    );
                }
            }
            
            // Handle SMALL eggs
            if ($smallCnt > 0) {
                $existingSmall = EggInventory::where('farm_owner_id', $flock->farm_owner_id)
                    ->where('flock_id', $flock->id)
                    ->where('egg_type', $eggType)
                    ->where('grade', $grade)
                    ->where('size', 'small')
                    ->whereDate('collection_date', $collectionDate->toDateString())
                    ->first();
                
                if ($existingSmall) {
                    // UPDATE: ADD new eggs to existing quantity
                    $quantitySold = $existingSmall->quantity_sold ?? 0;
                    $newTotal = $existingSmall->quantity_total + $smallCnt;
                    $newAvailable = $newTotal - $quantitySold;
                    $existingSmall->update([
                        'quantity_total' => $newTotal,
                        'quantity_available' => $newAvailable,
                        'freshness_expires_at' => $expiryDate,
                    ]);
                    $smallEggInventory = $existingSmall;
                } else {
                    // CREATE: New inventory item
                    $smallEggInventory = EggInventory::create([
                        'farm_owner_id' => $flock->farm_owner_id,
                        'flock_id' => $flock->id,
                        'egg_collection_id' => $collection->id,
                        'egg_type' => $eggType,
                        'grade' => $grade,
                        'size' => 'small',
                        'quantity_total' => $smallCnt,
                        'quantity_available' => $smallCnt,
                        'quantity_sold' => 0,
                        'quantity_expired' => 0,
                        'collection_date' => $collectionDate,
                        'freshness_expires_at' => $expiryDate,
                        'status' => 'fresh',
                        'batch_id' => $collection->batch_id,
                    ]);
                    
                    // Only create product on FIRST inventory creation (not on updates)
                    $this->createOrUpdateProduct(
                        $flock->farm_owner_id,
                        $eggType,
                        $grade,
                        'small',
                        $smallEggInventory->id
                    );
                }
            }
        }
        
        // Clear inventory cache to force refresh
        \Illuminate\Support\Facades\Cache::forget("farm_{$flock->farm_owner_id}_inventory_stats");
        
        return $collection;
    }

    /**
     * Determine egg type from flock breed
     */
    private function determineEggType(Flock $flock): string
    {
        $organicBreeds = ['Araucana', 'Ameraucana', 'Orpington', 'Sussex', 'Wyandotte', 'Java'];
        $brownBreeds = ['Rhode Island Red', 'New Hampshire', 'Buff Orpington'];
        
        if (in_array($flock->breed_type, $organicBreeds)) {
            return 'organic';
        } elseif (in_array($flock->breed_type, $brownBreeds)) {
            return 'brown';
        }
        
        return 'white'; // Default for Leghorn, etc
    }

    /**
     * Calculate grade distribution percentages
     */
    private function calculateGradeDistribution(int $eggsCollected): array
    {
        $gradeA = ceil($eggsCollected * 0.75);  // 75%
        $gradeB = ceil(($eggsCollected - $gradeA) * 0.67); // ~15%
        $gradeC = $eggsCollected - $gradeA - $gradeB; // Remainder ~10%
        
        return [
            'A' => max($gradeA, 0),
            'B' => max($gradeB, 0),
            'C' => max($gradeC, 0),
        ];
    }

    /**
     * Calculate large egg percentage by breed
     */
    private function calculateLargePercentage(Flock $flock): float
    {
        $breedSizes = [
            'Orpington' => 0.80,    // 80% large eggs
            'Sussex' => 0.75,       // 75% large
            'Wyandotte' => 0.78,    // 78% large
            'Java' => 0.75,         // 75% large
            'Rhode Island Red' => 0.70,
            'New Hampshire' => 0.70,
            'Leg horn' => 0.65,     // 65% large
            'Araucana' => 0.60,     // 60% large
            'Ameraucana' => 0.60,   // 60% large
            'Bantam' => 0.20,       // 20% large (mostly small)
        ];
        
        return $breedSizes[$flock->breed_type] ?? 0.70; // 70% default
    }

    /**
     * Create product if not exists, or update existing
     */
    private function createOrUpdateProduct(int $farmOwnerId, string $eggType, string $grade, string $size, int $eggInventoryId): Product
    {
        $productName = ucfirst($eggType) . " Eggs - Grade $grade - " . ucfirst($size);
        $price = $this->calculatePrice($eggType, $grade, $size);
        
        $product = Product::where('farm_owner_id', $farmOwnerId)
            ->where('egg_type', $eggType)
            ->where('egg_grade', $grade)
            ->where('egg_size', $size)
            ->where('category', 'eggs')
            ->first();
        
        if ($product) {
            $product->update(['egg_inventory_id' => $eggInventoryId]);
            return $product;
        }
        
        return Product::create([
            'farm_owner_id' => $farmOwnerId,
            'sku' => strtoupper("EGG-$eggType-$grade-$size-" . uniqid()),
            'name' => $productName,
            'description' => "Premium $eggType eggs, Grade $grade, $size size",
            'category' => 'eggs',
            'egg_type' => $eggType,
            'egg_grade' => $grade,
            'egg_size' => $size,
            'price' => $price,
            'cost_price' => $this->calculateCost($eggType, $grade),
            'unit' => 'dozen',
            'quantity_available' => 0,  // Will be calculated from inventory
            'status' => 'active',
            'egg_inventory_id' => $eggInventoryId,
            'auto_sync_inventory' => true,
            'minimum_order' => 1,
            'published_at' => now(),
        ]);
    }

    /**
     * Calculate selling price by type & grade & size
     */
    private function calculatePrice(string $eggType, string $grade, string $size): float
    {
        $basePrice = [
            'A' => 8.99,
            'B' => 6.99,
            'C' => 4.99,
        ];
        
        // Multipliers
        $typeMultiplier = match ($eggType) {
            'organic' => 1.5,   // Organic = 50% premium
            'brown' => 1.2,     // Brown = 20% premium
            default => 1.0,     // White = baseline
        };
        
        $sizeMultiplier = ($size === 'large') ? 1.2 : 0.9; // Large +20%, Small -10%
        
        return round($basePrice[$grade] * $typeMultiplier * $sizeMultiplier, 2);
    }

    /**
     * Calculate cost price
     */
    private function calculateCost(string $eggType, string $grade): float
    {
        $baseCost = [
            'A' => 3.50,
            'B' => 2.50,
            'C' => 1.50,
        ];
        
        $typeMultiplier = ($eggType === 'organic') ? 1.3 : 1.0;
        
        return round($baseCost[$grade] * $typeMultiplier, 2);
    }

    /**
     * Update all product quantities from their inventory
     */
    public function syncAllProductInventories(int $farmOwnerId): void
    {
        $products = Product::where('farm_owner_id', $farmOwnerId)
            ->where('category', 'eggs')
            ->where('auto_sync_inventory', true)
            ->get();
        
        foreach ($products as $product) {
            if ($product->egg_inventory_id) {
                $eggInventory = EggInventory::find($product->egg_inventory_id);
                if ($eggInventory) {
                    $product->update([
                        'quantity_available' => $eggInventory->quantity_available,
                    ]);
                }
            }
        }
    }
}
