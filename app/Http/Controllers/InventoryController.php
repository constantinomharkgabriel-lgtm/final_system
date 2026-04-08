<?php

namespace App\Http\Controllers;

use App\Models\EggInventory;
use App\Models\LivestockInventory;
use App\Models\SupplyItem;
use App\Models\FarmOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class InventoryController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesFarmOwner;

    /**
     * Display all inventory items (eggs, livestock, supplies)
     */
    public function index()
    {
        $farmOwner = $this->getFarmOwner();

        // Get low stock threshold (default: show if quantity < 20% of max)
        $threshold = request('threshold', 20);

        // Egg Inventory
        $eggInventory = EggInventory::where('farm_owner_id', $farmOwner->id)
            ->where('quantity_available', '>', 0)
            ->with('flock')
            ->get()
            ->map(function ($item) use ($threshold) {
                return [
                    'id' => $item->id,
                    'type' => 'egg',
                    'name' => "Eggs: Grade {$item->grade} ({$item->size})",
                    'batch_id' => $item->batch_id,
                    'quantity' => $item->quantity_available,
                    'quantity_total' => $item->quantity_total,
                    'unit' => 'pieces',
                    'expiry_date' => $item->freshness_expires_at,
                    'freshness_days' => $this->calculateFreshness($item->freshness_expires_at),
                    'status' => $item->quantity_available <= ($item->quantity_total * $threshold / 100) ? 'low' : 'ok',
                    'flock' => $item->flock?->batch_name ?? 'Unknown',
                    'collection_date' => $item->collection_date,
                ];
            });

        // Livestock Inventory
        $livestockInventory = LivestockInventory::where('farm_owner_id', $farmOwner->id)
            ->where('quantity_available_for_sale', '>', 0)
            ->get()
            ->map(function ($item) use ($threshold) {
                return [
                    'id' => $item->id,
                    'type' => 'livestock',
                    'name' => ucfirst($item->livestock_type) . " (" . $item->breed . ")",
                    'quantity' => $item->quantity_available_for_sale,
                    'quantity_total' => $item->quantity_available_for_sale + $item->quantity_sold,
                    'unit' => 'birds',
                    'status' => $item->quantity_available_for_sale <= (($item->quantity_available_for_sale + $item->quantity_sold) * $threshold / 100) ? 'low' : 'ok',
                    'ready_date' => $item->estimated_ready_date,
                    'weight_kg' => $item->avg_weight_kg,
                ];
            });

        // Supply Items (for selling - optional)
        $supplyInventory = collect();

        // Count items by status (cache for 60 seconds only, or force refresh)
        $forceRefresh = request('refresh') === '1';
        if ($forceRefresh) {
            Cache::forget("farm_{$farmOwner->id}_inventory_stats");
        }
        
        $stats = Cache::remember("farm_{$farmOwner->id}_inventory_stats", 60, function () use ($eggInventory, $livestockInventory) {
            $lowStock = $eggInventory->concat($livestockInventory)
                ->filter(fn($item) => $item['status'] === 'low')
                ->count();

            return [
                'total_items' => $eggInventory->count() + $livestockInventory->count(),
                'low_stock' => $lowStock,
                'egg_items' => $eggInventory->count(),
                'livestock_items' => $livestockInventory->count(),
                'total_egg_quantity' => $eggInventory->sum('quantity'),
                'total_livestock_quantity' => $livestockInventory->sum('quantity'),
            ];
        });

        // Combine and sort by status (low stock first)
        $allInventory = $eggInventory->concat($livestockInventory)->concat($supplyInventory)
            ->sortBy(fn($item) => $item['status'] === 'low' ? 0 : 1);

        return view('farmowner.inventory.index', compact('allInventory', 'stats', 'threshold'));
    }

    /**
     * Show inventory item details
     */
    public function show($type, $id)
    {
        $farmOwner = $this->getFarmOwner();

        $item = match ($type) {
            'egg' => EggInventory::where('farm_owner_id', $farmOwner->id)->findOrFail($id),
            'livestock' => LivestockInventory::where('farm_owner_id', $farmOwner->id)->findOrFail($id),
            'supply' => SupplyItem::where('farm_owner_id', $farmOwner->id)->findOrFail($id),
            default => abort(404),
        };

        return view('farmowner.inventory.show', compact('item', 'type'));
    }

    /**
     * Get available inventory for product linking
     * Used by ProductController (AJAX)
     */
    public function getAvailableForProduct()
    {
        $farmOwner = $this->getFarmOwner();

        $data = [
            'eggs' => EggInventory::where('farm_owner_id', $farmOwner->id)
                ->where('quantity_available', '>', 0)
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'label' => "Grade {$item->grade} ({$item->size}) - {$item->quantity_available} available",
                    'quantity' => $item->quantity_available,
                    'type' => 'egg',
                ]),
            'livestock' => LivestockInventory::where('farm_owner_id', $farmOwner->id)
                ->where('quantity_available_for_sale', '>', 0)
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'label' => ucfirst($item->livestock_type) . " - {$item->quantity_available_for_sale} available",
                    'quantity' => $item->quantity_available_for_sale,
                    'type' => 'livestock',
                ]),
        ];

        return response()->json($data);
    }

    /**
     * Calculate freshness days remaining
     */
    private function calculateFreshness($expiryDate)
    {
        if (!$expiryDate) {
            return null;
        }
        $days = $expiryDate->diffInDays(now(), false);
        return max(0, $days);
    }

    /**
     * Generate low stock alerts
     */
    public function getLowStockAlerts()
    {
        $farmOwner = $this->getFarmOwner();

        $alerts = [];

        // Egg inventory low stock
        $lowEggs = EggInventory::where('farm_owner_id', $farmOwner->id)
            ->where('quantity_available', '>', 0)
            ->where('quantity_available', '<=', 10) // Low threshold
            ->get();

        foreach ($lowEggs as $egg) {
            $alerts[] = [
                'type' => 'warning',
                'title' => "Low Egg Stock",
                'message' => "Grade {$egg->grade} ({$egg->size}) eggs: Only {$egg->quantity_available} remaining",
                'item_type' => 'egg',
                'item_id' => $egg->id,
            ];
        }

        // Livestock inventory low stock
        $lowLivestock = LivestockInventory::where('farm_owner_id', $farmOwner->id)
            ->where('quantity_available_for_sale', '>', 0)
            ->where('quantity_available_for_sale', '<=', 20) // Low threshold
            ->get();

        foreach ($lowLivestock as $livestock) {
            $alerts[] = [
                'type' => 'warning',
                'title' => "Low Livestock Stock",
                'message' => ucfirst($livestock->livestock_type) . ": Only {$livestock->quantity_available_for_sale} available",
                'item_type' => 'livestock',
                'item_id' => $livestock->id,
            ];
        }

        // Expiring eggs
        $expiringEggs = EggInventory::where('farm_owner_id', $farmOwner->id)
            ->where('quantity_available', '>', 0)
            ->whereBetween('freshness_expires_at', [now(), now()->addDays(3)])
            ->get();

        foreach ($expiringEggs as $egg) {
            $daysLeft = $egg->freshness_expires_at->diffInDays(now());
            $alerts[] = [
                'type' => 'danger',
                'title' => "Expiring Soon",
                'message' => "Grade {$egg->grade} eggs expire in {$daysLeft} days",
                'item_type' => 'egg',
                'item_id' => $egg->id,
            ];
        }

        return response()->json(['alerts' => $alerts]);
    }

    /**
     * Export inventory report
     */
    public function export()
    {
        $farmOwner = $this->getFarmOwner();

        $data = "Inventory Report - " . now()->format('Y-m-d H:i:s') . "\n\n";

        $data .= "EGG INVENTORY\n";
        $data .= str_repeat("-", 80) . "\n";
        EggInventory::where('farm_owner_id', $farmOwner->id)
            ->where('quantity_available', '>', 0)
            ->each(function ($egg) use (&$data) {
                $data .= "Grade {$egg->grade} ({$egg->size}) | Qty: {$egg->quantity_available} | Expires: {$egg->expires_at}\n";
            });

        $data .= "\n\nLIVESTOCK INVENTORY\n";
        $data .= str_repeat("-", 80) . "\n";
        LivestockInventory::where('farm_owner_id', $farmOwner->id)
            ->where('quantity_available_for_sale', '>', 0)
            ->each(function ($livestock) use (&$data) {
                $data .= ucfirst($livestock->livestock_type) . " | Qty: {$livestock->quantity_available_for_sale} | Ready: {$livestock->estimated_ready_date}\n";
            });

        return response()->streamDownload(
            fn() => print($data),
            'inventory-report-' . now()->format('Y-m-d') . '.txt'
        );
    }
}
