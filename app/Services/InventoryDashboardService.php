<?php

namespace App\Services;

use App\Models\EggInventory;
use App\Models\LivestockInventory;
use App\Models\SupplyItem;
use App\Models\InventoryTransaction;
use App\Models\Flock;
use Carbon\Carbon;

class InventoryDashboardService
{
    /**
     * Get complete inventory overview for a farm owner
     */
    public function getInventoryOverview(int $farmOwnerId): array
    {
        return [
            'eggs' => $this->getEggInventorySummary($farmOwnerId),
            'livestock' => $this->getLivestockInventorySummary($farmOwnerId),
            'supplies' => $this->getSupplyInventorySummary($farmOwnerId),
            'transactions' => $this->getRecentTransactions($farmOwnerId, 7),
            'alerts' => $this->getInventoryAlerts($farmOwnerId),
        ];
    }

    /**
     * Get egg inventory summary
     */
    public function getEggInventorySummary(int $farmOwnerId): array
    {
        $eggs = EggInventory::byFarmOwner($farmOwnerId)->get();
        
        $totalQuantity = $eggs->sum('quantity_total');
        $totalAvailable = $eggs->sum('quantity_available');
        $totalSold = $eggs->sum('quantity_sold');
        $totalExpired = $eggs->sum('quantity_expired');
        
        $byType = $eggs->groupBy('egg_type')->map(function($group) {
            return [
                'total' => $group->sum('quantity_total'),
                'available' => $group->sum('quantity_available'),
                'sold' => $group->sum('quantity_sold'),
                'expired' => $group->sum('quantity_expired'),
                'revenue' => $group->sum(function($item) {
                    return $item->quantity_sold * $this->getEggPrice($item->egg_type, $item->grade, $item->size);
                }),
            ];
        });
        
        $byGrade = $eggs->groupBy('grade')->map(function($group) {
            return [
                'total' => $group->sum('quantity_total'),
                'available' => $group->sum('quantity_available'),
                'sold' => $group->sum('quantity_sold'),
            ];
        });
        
        $expiringToday = $eggs->fresh()->expiringToday()->count();
        $expiringSoon = $eggs->fresh()
            ->where('freshness_expires_at', '<=', now()->addDays(2))
            ->where('freshness_expires_at', '>', now())
            ->count();
        
        return [
            'total_quantity' => $totalQuantity,
            'quantity_available' => $totalAvailable,
            'quantity_sold' => $totalSold,
            'quantity_expired' => $totalExpired,
            'inventory_value' => $this->calculateEggInventoryValue($eggs),
            'by_type' => $byType,
            'by_grade' => $byGrade,
            'freshness_status' => [
                'fresh' => $eggs->where('status', 'fresh')->count(),
                'expiring_today' => $expiringToday,
                'expiring_soon' => $expiringSoon,
                'expired' => $eggs->where('status', 'expired')->count(),
            ],
            'product_count' => $eggs->distinct('product_id')->count(),
        ];
    }

    /**
     * Get livestock inventory summary
     */
    public function getLivestockInventorySummary(int $farmOwnerId): array
    {
        $livestock = LivestockInventory::where('farm_owner_id', $farmOwnerId)->get();
        
        $readyForSale = $livestock->where('status', 'ready_for_sale');
        $growing = $livestock->where('status', 'growing');
        $sold = $livestock->where('status', 'sold_out');
        
        $byType = $livestock->groupBy('livestock_type')->map(function($group) {
            $readyCount = $group->where('status', 'ready_for_sale')->sum('quantity_available_for_sale');
            $soldCount = $group->sum('quantity_sold');
            
            return [
                'total_available' => $group->sum('quantity_available_for_sale'),
                'ready_for_sale' => $readyCount,
                'growing' => $group->sum('quantity_available_for_sale') - $readyCount,
                'total_sold' => $soldCount,
                'average_age_weeks' => $group->avg('age_weeks'),
                'estimated_revenue' => $this->calculateLivestockRevenue($group),
            ];
        });
        
        return [
            'total_inventory' => $livestock->sum('quantity_available_for_sale'),
            'ready_for_sale' => $readyForSale->sum('quantity_available_for_sale'),
            'growing' => $growing->sum('quantity_available_for_sale'),
            'total_sold' => $livestock->sum('quantity_sold'),
            'total_investment' => $livestock->sum(function($item) {
                return $item->quantity_available_for_sale * $item->average_weight_kg * 150; // Approximate cost
            }),
            'by_type' => $byType,
            'count_by_status' => [
                'growing' => $growing->count(),
                'ready_for_sale' => $readyForSale->count(),
                'sold_out' => $sold->count(),
            ],
            'weeks_until_ready' => $this->getUpcomingReadyInventory($livestock),
        ];
    }

    /**
     * Get supply inventory summary
     */
    public function getSupplyInventorySummary(int $farmOwnerId): array
    {
        $supplies = SupplyItem::byFarmOwner($farmOwnerId)->whereNull('deleted_at')->get();
        
        $inStock = $supplies->where('status', '!=', 'out_of_stock');
        $lowStock = $supplies->lowStock();
        $outOfStock = $supplies->outOfStock();
        $expiring = $supplies->expiringSoon();
        $expired = $supplies->expired();
        
        $byCategory = $supplies->groupBy('category')->map(function($group) {
            return [
                'count' => $group->count(),
                'total_value' => $group->sum('quantity_on_hand') * $group->first()?->unit_cost,
                'low_stock_count' => $group->lowStock()->count(),
                'out_of_stock_count' => $group->outOfStock()->count(),
                'items' => $group->map(function($item) {
                    return [
                        'name' => $item->name,
                        'quantity' => $item->quantity_on_hand,
                        'unit' => $item->unit,
                        'status' => $item->status,
                    ];
                }),
            ];
        });
        
        return [
            'total_items' => $supplies->count(),
            'in_stock_count' => $inStock->count(),
            'low_stock_count' => $lowStock->count(),
            'out_of_stock_count' => $outOfStock->count(),
            'expiring_soon_count' => $expiring->count(),
            'expired_count' => $expired->count(),
            'total_inventory_value' => $supplies->sum('quantity_on_hand') * $supplies->avg('unit_cost'),
            'by_category' => $byCategory,
            'critical_items' => [
                'feeds' => $supplies->where('category', 'feeds')->outOfStock()->count(),
                'vaccines' => $supplies->where('category', 'vaccines')->outOfStock()->count(),
                'medications' => $supplies->where('category', 'medications')->outOfStock()->count(),
            ],
        ];
    }

    /**
     * Get recent inventory transactions
     */
    public function getRecentTransactions(int $farmOwnerId, int $days = 7): array
    {
        $startDate = now()->subDays($days);
        
        $transactions = InventoryTransaction::where('farm_owner_id', $farmOwnerId)
            ->where('transaction_date', '>=', $startDate)
            ->orderByDesc('transaction_date')
            ->limit(50)
            ->get();
        
        return $transactions->groupBy('transaction_type')->map(function($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('total_amount'),
                'total_quantity' => $group->sum('quantity'),
                'transactions' => $group->map(function($t) {
                    return [
                        'date' => $t->transaction_date,
                        'type' => $t->transaction_type,
                        'quantity' => $t->quantity,
                        'amount' => $t->total_amount,
                        'reference' => $t->reference_type,
                    ];
                }),
            ];
        })->all();
    }

    /**
     * Get inventory alerts
     */
    public function getInventoryAlerts(int $farmOwnerId): array
    {
        $alerts = [];
        
        // Low egg inventory
        $expiredEggs = EggInventory::byFarmOwner($farmOwnerId)->where('status', 'expired')->count();
        if ($expiredEggs > 0) {
            $alerts[] = [
                'type' => 'expired_eggs',
                'severity' => 'high',
                'message' => "You have $expiredEggs batches of expired eggs. Remove from inventory.",
                'action' => 'view_expired_eggs',
            ];
        }
        
        // Expiring soon
        $expiringToday = EggInventory::byFarmOwner($farmOwnerId)
            ->fresh()
            ->expiringToday()
            ->count();
        if ($expiringToday > 0) {
            $alerts[] = [
                'type' => 'eggs_expiring_today',
                'severity' => 'medium',
                'message' => "$expiringToday batches of eggs expire today. Prioritize sales.",
                'action' => 'prioritize_sales',
            ];
        }
        
        // Low livestock stock
        $lowLivestock = LivestockInventory::where('farm_owner_id', $farmOwnerId)
            ->where('status', 'ready_for_sale')
            ->where('quantity_available_for_sale', '<', 5)
            ->count();
        if ($lowLivestock > 0) {
            $alerts[] = [
                'type' => 'low_livestock',
                'severity' => 'medium',
                'message' => "$lowLivestock livestock types have less than 5 units available for sale.",
                'action' => 'view_livestock',
            ];
        }
        
        // Out of stock supplies
        $outOfStockSupplies = SupplyItem::byFarmOwner($farmOwnerId)
            ->outOfStock()
            ->count();
        if ($outOfStockSupplies > 0) {
            $alerts[] = [
                'type' => 'out_of_stock_supplies',
                'severity' => 'high',
                'message' => "$outOfStockSupplies essential supplies are out of stock.",
                'action' => 'order_supplies',
            ];
        }
        
        // Critical low supplies
        $criticalSupplies = SupplyItem::byFarmOwner($farmOwnerId)
            ->lowStock()
            ->count();
        if ($criticalSupplies > 0 && $criticalSupplies > $outOfStockSupplies) {
            $alerts[] = [
                'type' => 'low_stock_supplies',
                'severity' => 'medium',
                'message' => ($criticalSupplies - $outOfStockSupplies) . " supplies are running low on stock.",
                'action' => 'reorder_supplies',
            ];
        }
        
        return $alerts;
    }

    /**
     * Get upcoming ready inventory
     */
    private function getUpcomingReadyInventory($livestock): array
    {
        return $livestock
            ->where('status', 'growing')
            ->sortBy('weeks_until_ready')
            ->map(function($item) {
                return [
                    'type' => $item->livestock_type,
                    'quantity' => $item->quantity_available_for_sale,
                    'weeks_until_ready' => $item->weeks_until_ready,
                    'ready_date' => $item->estimated_ready_date,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Calculate egg price by type, grade, size
     */
    private function getEggPrice(string $type, string $grade, string $size): float
    {
        $basePrice = match ($grade) {
            'A' => 8.99,
            'B' => 6.99,
            'C' => 4.99,
            default => 6.99,
        };
        
        $typeMultiplier = match ($type) {
            'organic' => 1.5,
            'brown' => 1.2,
            'white' => 1.0,
            default => 1.0,
        };
        
        $sizeMultiplier = match ($size) {
            'large' => 1.2,
            'small' => 0.9,
            default => 1.0,
        };
        
        return $basePrice * $typeMultiplier * $sizeMultiplier;
    }

    /**
     * Calculate egg inventory total value
     */
    private function calculateEggInventoryValue($eggs): float
    {
        return $eggs->sum(function($item) {
            $price = $this->getEggPrice($item->egg_type, $item->grade, $item->size);
            return $item->quantity_available * $price;
        });
    }

    /**
     * Calculate livestock revenue
     */
    private function calculateLivestockRevenue($group): float
    {
        $basePrice = match ($group->first()->livestock_type) {
            'broiler' => 150,
            'layer' => 200,
            'breeder' => 250,
            'fighting_cock' => 300,
            'native' => 180,
            'duck' => 280,
            'quail' => 80,
            default => 150,
        };
        
        return $group->sum('quantity_sold') * $basePrice;
    }

    /**
     * Get inventory health score (0-100)
     */
    public function getInventoryHealthScore(int $farmOwnerId): int
    {
        $score = 100;
        
        // Deduct for expired items
        $expiredEggs = EggInventory::byFarmOwner($farmOwnerId)->where('status', 'expired')->count();
        $score -= min(20, $expiredEggs * 5);
        
        // Deduct for out of stock supplies
        $outOfStock = SupplyItem::byFarmOwner($farmOwnerId)->outOfStock()->count();
        $score -= min(15, $outOfStock * 3);
        
        // Bonus for balanced inventory
        $livestock = LivestockInventory::where('farm_owner_id', $farmOwnerId)
            ->where('status', 'ready_for_sale')
            ->count();
        if ($livestock >= 3) {
            $score += 10;
        }
        
        // Bonus for good egg turnover
        $eggs = EggInventory::byFarmOwner($farmOwnerId)->get();
        $soldPercentage = $eggs->isEmpty() ? 0 : ($eggs->sum('quantity_sold') / ($eggs->sum('quantity_total') + 1)) * 100;
        if ($soldPercentage > 50) {
            $score += 10;
        }
        
        return min(100, max(0, $score));
    }

    /**
     * Get inventory metrics for analytics
     */
    public function getInventoryMetrics(int $farmOwnerId, int $daysBack = 30): array
    {
        $startDate = now()->subDays($daysBack);
        
        $transactions = InventoryTransaction::where('farm_owner_id', $farmOwnerId)
            ->where('transaction_date', '>=', $startDate)
            ->get();
        
        $dailyMetrics = $transactions->groupBy(function($t) {
            return $t->transaction_date->format('Y-m-d');
        })->map(function($group) {
            return [
                'date' => $group->first()->transaction_date,
                'sales_revenue' => $group->where('transaction_type', 'sale')->sum('total_amount'),
                'consumption_cost' => $group->where('transaction_type', 'consumption')->sum('total_amount'),
                'collection_count' => $group->where('transaction_type', 'collection')->count(),
            ];
        });
        
        return [
            'period_days' => $daysBack,
            'total_revenue' => $transactions->where('transaction_type', 'sale')->sum('total_amount'),
            'total_consumption_cost' => $transactions->where('transaction_type', 'consumption')->sum('total_amount'),
            'net_profit' => $transactions->where('transaction_type', 'sale')->sum('total_amount') 
                - $transactions->where('transaction_type', 'consumption')->sum('total_amount'),
            'daily_metrics' => $dailyMetrics->values(),
        ];
    }
}
