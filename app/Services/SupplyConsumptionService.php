<?php

namespace App\Services;

use App\Models\Supply;
use App\Models\SupplyItem;
use App\Models\Vaccination;
use App\Models\InventoryTransaction;
use Carbon\Carbon;

class SupplyConsumptionService
{
    /**
     * Record vaccine consumption when vaccination is administered
     */
    public function recordVaccineConsumption(Vaccination $vaccination): void
    {
        if (!$vaccination->vaccine_type) {
            return;
        }
        
        // Find supply item matching vaccine type
        $supply = SupplyItem::where('farm_owner_id', $vaccination->farm_owner_id)
            ->where('name', 'LIKE', '%' . $vaccination->vaccine_type . '%')
            ->orderByDesc('created_at')
            ->first();
        
        if ($supply && $supply->quantity_on_hand > 0) {
            // Deduct quantity
            $consumedQty = min(1, $supply->quantity_on_hand); // 1 dose per application
            $supply->quantity_on_hand -= $consumedQty;
            $supply->save();
            
            // Record transaction
            InventoryTransaction::create([
                'farm_owner_id' => $vaccination->farm_owner_id,
                'inventoryable_type' => SupplyItem::class,
                'inventoryable_id' => $supply->id,
                'transaction_type' => 'consumption',
                'quantity' => $consumedQty,
                'unit_price' => $supply->unit_cost,
                'total_amount' => $consumedQty * $supply->unit_cost,
                'reference_id' => $vaccination->id,
                'reference_type' => Vaccination::class,
                'notes' => "Vaccine administered to {$vaccination->flock->flock_name}",
                'recorded_by' => auth()?->id(),
                'transaction_date' => today(),
            ]);
            
            // Notify if stock running low
            if ($supply->quantity_on_hand <= $supply->minimum_stock) {
                $this->notifyLowStock($supply);
            }
        }
    }

    /**
     * Record feed consumption from daily records
     */
    public function recordFeedConsumption(
        \App\Models\FlockRecord $record,
        float $quantityFed,
        ?string $feedType = null
    ): void {
        $flock = $record->flock;
        
        // Determine feed type if not provided
        if (!$feedType) {
            $age = $flock->age_weeks ?? 0;
            if ($age < 8) {
                $feedType = 'Starter Feed';
            } elseif ($age < 16) {
                $feedType = 'Grower Feed';
            } else {
                $feedType = 'Layer/Maintenance Feed';
            }
        }
        
        // Find supply item
        $supply = SupplyItem::where('farm_owner_id', $flock->farm_owner_id)
            ->where('name', 'LIKE', '%' . $feedType . '%')
            ->orderByDesc('created_at')
            ->first();
        
        if ($supply && $supply->quantity_on_hand > 0) {
            $consumedQty = min($quantityFed, $supply->quantity_on_hand);
            $supply->quantity_on_hand -= $consumedQty;
            $supply->save();
            
            // Record transaction
            InventoryTransaction::create([
                'farm_owner_id' => $flock->farm_owner_id,
                'inventoryable_type' => SupplyItem::class,
                'inventoryable_id' => $supply->id,
                'transaction_type' => 'consumption',
                'quantity' => $consumedQty,
                'unit_price' => $supply->unit_cost,
                'total_amount' => $consumedQty * $supply->unit_cost,
                'reference_id' => $record->id,
                'reference_type' => \App\Models\FlockRecord::class,
                'notes' => "Feed consumed by {$flock->flock_name} ({$flock->current_count} birds)",
                'recorded_by' => auth()?->id(),
                'transaction_date' => $record->record_date,
            ]);
            
            // Notify if low stock
            if ($supply->quantity_on_hand <= $supply->minimum_stock) {
                $this->notifyLowStock($supply);
            }
        }
    }

    /**
     * Record medicine/treatment consumption
     */
    public function recordMedicineConsumption(
        \App\Models\Flock $flock,
        string $medicineName,
        float $quantity
    ): void {
        $supply = SupplyItem::where('farm_owner_id', $flock->farm_owner_id)
            ->where('name', 'LIKE', '%' . $medicineName . '%')
            ->orderByDesc('created_at')
            ->first();
        
        if ($supply && $supply->quantity_on_hand > 0) {
            $consumedQty = min($quantity, $supply->quantity_on_hand);
            $supply->quantity_on_hand -= $consumedQty;
            $supply->save();
            
            // Record transaction
            InventoryTransaction::create([
                'farm_owner_id' => $flock->farm_owner_id,
                'inventoryable_type' => SupplyItem::class,
                'inventoryable_id' => $supply->id,
                'transaction_type' => 'consumption',
                'quantity' => $consumedQty,
                'unit_price' => $supply->unit_cost,
                'total_amount' => $consumedQty * $supply->unit_cost,
                'reference_id' => $flock->id,
                'reference_type' => \App\Models\Flock::class,
                'notes' => "{$medicineName} administered to {$flock->flock_name}",
                'recorded_by' => auth()?->id(),
                'transaction_date' => today(),
            ]);
            
            if ($supply->quantity_on_hand <= $supply->minimum_stock) {
                $this->notifyLowStock($supply);
            }
        }
    }

    /**
     * Get consumption report for a period
     */
    public function getConsumptionReport(int $farmOwnerId, Carbon $startDate, Carbon $endDate): array
    {
        $transactions = InventoryTransaction::where('farm_owner_id', $farmOwnerId)
            ->where('transaction_type', 'consumption')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();
        
        return [
            'total_transactions' => $transactions->count(),
            'total_cost' => $transactions->sum('total_amount'),
            'by_supply' => $transactions->groupBy('inventoryable_id')->map(function($group) {
                return [
                    'quantity' => $group->sum('quantity'),
                    'total_cost' => $group->sum('total_amount'),
                ];
            }),
            'daily_consumption' => $transactions->groupBy(function($item) {
                return $item->transaction_date->format('Y-m-d');
            })->map(function($group) {
                return [
                    'count' => $group->count(),
                    'cost' => $group->sum('total_amount'),
                ];
            }),
        ];
    }

    /**
     * Get predicted consumption for next period
     */
    public function getPredictedConsumption(
        \App\Models\Flock $flock,
        int $daysAhead = 30
    ): array {
        // Get historical consumption rate
        $thirtyDaysAgo = now()->subDays(60);
        $historicalTransactions = InventoryTransaction::where('farm_owner_id', $flock->farm_owner_id)
            ->where('reference_type', \App\Models\FlockRecord::class)
            ->where('transaction_type', 'consumption')
            ->where('transaction_date', '>=', $thirtyDaysAgo)
            ->get();
        
        if ($historicalTransactions->isEmpty()) {
            // Use default consumption estimates
            return $this->getDefaultConsumptionEstimates($flock, $daysAhead);
        }
        
        $avgDailyCost = $historicalTransactions->sum('total_amount') / 30;
        $predictedCost = $avgDailyCost * $daysAhead;
        
        return [
            'average_daily_cost' => $avgDailyCost,
            'predicted_cost_next_period' => $predictedCost,
            'based_on_days' => 30,
            'forecast_days' => $daysAhead,
        ];
    }

    /**
     * Default consumption estimates by flock type
     */
    private function getDefaultConsumptionEstimates(\App\Models\Flock $flock, int $daysAhead): array
    {
        $age = $flock->age_weeks ?? 0;
        $count = $flock->current_count ?? 0;
        
        // kg/bird/day consumption
        $feedRate = match (true) {
            $age < 4 => 0.015,     // Chicks: 15g/day
            $age < 8 => 0.025,     // Young: 25g/day
            $age < 16 => 0.05,     // Growing: 50g/day
            $age < 20 => 0.08,     // Pre-layer: 80g/day
            default => 0.1,        // Mature: 100g/day
        };
        
        // Feed price estimates
        $feedPrice = match (true) {
            $age < 8 => 25,        // Starter/Grower: ₱25/kg
            default => 20,         // Layer: ₱20/kg
        };
        
        $dailyFeedQty = $count * $feedRate;
        $dailyFeedCost = $dailyFeedQty * $feedPrice;
        $vaccineSupplementCost = 50; // ₱50/day for vaccines, medicine
        
        $totalDailyCost = $dailyFeedCost + $vaccineSupplementCost;
        
        return [
            'daily_feed_quantity_kg' => $dailyFeedQty,
            'daily_feed_cost' => $dailyFeedCost,
            'daily_vaccine_supplement_cost' => $vaccineSupplementCost,
            'average_daily_cost' => $totalDailyCost,
            'predicted_cost_next_period' => $totalDailyCost * $daysAhead,
            'forecast_days' => $daysAhead,
            'note' => 'Based on default consumption estimates',
        ];
    }

    /**
     * Get low stock alert
     */
    public function notifyLowStock(SupplyItem $supply): void
    {
        // Send notification (implement based on your notification system)
        $message = "⚠️ Low stock alert: {$supply->supply_name} has only {$supply->quantity_available} units remaining";
        
        // Could send email, SMS, or database notification
        // Example:
        // Auth::user()?->notify(new LowStockNotification($supply));
    }

    /**
     * Get supply needs based on flock age and schedule
     */
    public function getUpcomingSupplyNeeds(\App\Models\Flock $flock): array
    {
        $age = $flock->age_weeks ?? 0;
        $needs = [];
        
        // Feed recommendations
        $needs['feed'] = $this->getFeedRecommendation($age, $flock->current_count);
        
        // Vaccination schedule
        $needs['vaccinations'] = $this->getVaccinationSchedule($age, $flock->flock_type);
        
        // Medicine/supplement schedule
        $needs['medicine'] = $this->getMedicineSchedule($age, $flock->current_count);
        
        return $needs;
    }

    private function getFeedRecommendation(int $age, int $birdCount): array
    {
        $feedType = match (true) {
            $age < 4 => 'Starter Feed',
            $age < 8 => 'Grower Feed',
            default => 'Layer/Maintenance Feed',
        };
        
        $dailyQty = match (true) {
            $age < 4 => $birdCount * 0.015,
            $age < 8 => $birdCount * 0.025,
            $age < 16 => $birdCount * 0.05,
            $age < 20 => $birdCount * 0.08,
            default => $birdCount * 0.1,
        };
        
        return [
            'name' => $feedType,
            'daily_quantity_kg' => $dailyQty,
            'monthly_quantity_kg' => $dailyQty * 30,
            'priority' => 'critical',
        ];
    }

    private function getVaccinationSchedule(int $age, string $flockType): array
    {
        $schedule = [];
        
        if ($age == 1) {
            $schedule[] = ['name' => 'Newcastle (NY)', 'weeks' => 1, 'priority' => 'critical'];
        }
        if ($age == 2) {
            $schedule[] = ['name' => 'IBD (Gumboro)', 'weeks' => 2, 'priority' => 'critical'];
        }
        if ($age == 4) {
            $schedule[] = ['name' => 'Newcastle Booster', 'weeks' => 4, 'priority' => 'high'];
        }
        
        return $schedule;
    }

    private function getMedicineSchedule(int $age, string $flockType): array
    {
        $schedule = [];
        
        if ($age < 10) {
            $schedule[] = ['name' => 'Vitamin & Electrolyte Supplement', 'frequency' => 'Twice weekly', 'priority' => 'high'];
        }
        
        if ($flockType === 'layer' && $age > 12) {
            $schedule[] = ['name' => 'Calcium Supplement (Shell Strength)', 'frequency' => 'Continuous', 'priority' => 'high'];
        }
        
        return $schedule;
    }
}
