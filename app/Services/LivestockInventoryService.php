<?php

namespace App\Services;

use App\Models\Flock;
use App\Models\LivestockInventory;
use App\Models\Product;
use Carbon\Carbon;

class LivestockInventoryService
{
    /**
     * Create or update livestock inventory for a flock ready to sell
     */
    public function createInventoryFromFlock(Flock $flock, ?int $quantityAvailable = null): LivestockInventory
    {
        // Use flock current_count or provided quantity
        $qty = $quantityAvailable ?? $flock->current_count;
        
        // Check if inventory already exists for this flock
        $inventory = LivestockInventory::where('flock_id', $flock->id)->first();
        
        if ($inventory) {
            // Update existing
            $inventory->update([
                'quantity_available_for_sale' => $qty,
                'last_updated' => today(),
            ]);
        } else {
            // Create new
            $inventory = LivestockInventory::create([
                'farm_owner_id' => $flock->farm_owner_id,
                'flock_id' => $flock->id,
                'livestock_type' => $this->getFlockType($flock),
                'age_weeks' => $flock->age_weeks ?? 0,
                'quantity_available_for_sale' => $qty,
                'quantity_reserved' => 0,
                'quantity_sold' => 0,
                'average_weight_kg' => $this->estimateWeight($flock),
                'status' => 'growing',
                'acquisition_cost_per_unit' => $flock->acquisition_cost ?? 50,
                'last_updated' => today(),
            ]);
        }
        
        // Update age and readiness
        $this->updateReadiness($inventory);
        
        return $inventory;
    }

    /**
     * Update readiness status (weeks until ready)
     */
    public function updateReadiness(LivestockInventory $inventory): void
    {
        $standardAge = $inventory->getStandardReadyAge();
        $inventory->weeks_until_ready = max($standardAge - $inventory->age_weeks, 0);
        
        if ($inventory->weeks_until_ready <= 0) {
            $inventory->estimated_ready_date = today();
            $inventory->ready_date = today();
            $inventory->status = 'ready_for_sale';
        } else {
            $inventory->estimated_ready_date = now()->addWeeks($inventory->weeks_until_ready);
            $inventory->status = 'growing';
        }
        
        $inventory->last_updated = today();
        $inventory->save();
        
        // Auto-create product if ready
        if ($inventory->status === 'ready_for_sale') {
            $this->createOrUpdateProduct($inventory);
        }
    }

    /**
     * Update age from daily record
     */
    public function updateFlockAge(Flock $flock, int $weekAge): void
    {
        $inventory = LivestockInventory::where('flock_id', $flock->id)->first();
        
        if ($inventory) {
            $inventory->age_weeks = $weekAge;
            $this->updateReadiness($inventory);
        }
    }

    /**
     * Record a sale of livestock
     */
    public function recordSale(LivestockInventory $inventory, int $quantity, float $unitPrice = null): bool
    {
        if (!$inventory->recordSale($quantity)) {
            return false;
        }
        
        // Update product quantity
        if ($inventory->products()->exists()) {
            $product = $inventory->products()->first();
            $product->update([
                'quantity_available' => $inventory->quantity_available_for_sale,
                'quantity_sold' => $inventory->quantity_sold,
            ]);
        }
        
        return true;
    }

    /**
     * Get flock type from breed
     */
    private function getFlockType(Flock $flock): string
    {
        $flock_type = $flock->flock_type; // From DB
        $breed = $flock->breed_type;
        
        if ($flock_type === 'layer') return 'layer';
        if ($flock_type === 'breeder') return 'breeder';
        if (in_array($breed, ['Shama', 'Kulalong', 'Sweater', 'Kelso'])) return 'fighting_cock';
        if (in_array($breed, ['Banaba', 'Inahin', 'Talisay'])) return 'native';
        if (in_array($flock_type, ['duck', 'quail'])) return $flock_type;
        
        return 'broiler'; // Default
    }

    /**
     * Estimate average weight by type & age
     */
    private function estimateWeight(Flock $flock): float
    {
        $type = $this->getFlockType($flock);
        $age = $flock->age_weeks ?? 0;
        
        return match ($type) {
            'broiler' => min(2.5 * ($age / 8), 2.5),           // 2.5kg at 8 weeks
            'layer' => min(1.8 * ($age / 16), 1.8),            // 1.8kg at 16 weeks
            'breeder' => min(2.2 * ($age / 20), 2.2),          // 2.2kg at 20 weeks
            'fighting_cock' => min(2.0 * ($age / 24), 2.0),    // 2.0kg at 24 weeks
            'native' => min(1.5 * ($age / 16), 1.5),           // 1.5kg at 16 weeks
            'duck' => min(3.0 * ($age / 10), 3.0),             // 3.0kg at 10 weeks
            'quail' => min(0.25 * ($age / 6), 0.25),           // 0.25kg at 6 weeks
            default => 1.5,
        };
    }

    /**
     * Create or update product for livestock
     */
    private function createOrUpdateProduct(LivestockInventory $inventory): Product
    {
        $flock = $inventory->flock;
        $type = $inventory->livestock_type;
        
        // Determine unit and quantity representation
        $unit = match ($type) {
            'layer', 'breeder' => 'bird',
            'fighting_cock' => 'bird',
            'native' => 'bird',
            'broiler' => 'kg',
            'duck' => 'bird',
            'quail' => 'bird',
            default => 'bird',
        };
        
        // Calculate quantity for product
        $displayQty = ($unit === 'kg') 
            ? $inventory->quantity_available_for_sale * $inventory->average_weight_kg
            : $inventory->quantity_available_for_sale;
        
        $name = $flock->farm_name . " - " . ucfirst(str_replace('_', ' ', $type));
        $price = $this->calculateLivestockPrice($type, $inventory->average_weight_kg);
        
        $product = Product::where('farm_owner_id', $inventory->farm_owner_id)
            ->where('livestock_type', $type)
            ->where('livestock_inventory_id', $inventory->id)
            ->first();
        
        if ($product) {
            $product->update([
                'quantity_available' => $displayQty,
            ]);
            return $product;
        }
        
        return Product::create([
            'farm_owner_id' => $inventory->farm_owner_id,
            'sku' => strtoupper($type . '-' . $flock->id . '-' . uniqid()),
            'name' => $name,
            'description' => "$type from {$flock->farm_name}, Ready for sale",
            'category' => 'live_stock',
            'livestock_type' => $type,
            'unit' => $unit,
            'price' => $price,
            'cost_price' => $inventory->total_cost_per_unit,
            'quantity_available' => $displayQty,
            'quantity_sold' => 0,
            'status' => 'active',
            'livestock_inventory_id' => $inventory->id,
            'auto_sync_inventory' => true,
            'minimum_order' => ($unit === 'kg') ? 5 : 1,
            'published_at' => now(),
        ]);
    }

    /**
     * Calculate livestock selling price
     */
    private function calculateLivestockPrice(string $type, float $avgWeight): float
    {
        $pricePerKg = match ($type) {
            'broiler' => 150,              // ₱150/kg broiler
            'layer' => 200,                // ₱200 per bird layer
            'breeder' => 250,              // ₱250 per bird breeder
            'fighting_cock' => 300,        // ₱300 per bird fighting cock
            'native' => 180,               // ₱180 per bird
            'duck' => 280,                 // ₱280 per bird
            'quail' => 80,                 // ₱80 per bird
            default => 150,
        };
        
        if ($type === 'broiler') {
            return $pricePerKg * $avgWeight;
        }
        
        return $pricePerKg;
    }
}
