# Inventory System Integration Guide

## System Overview

The inventory system consists of three core services working together:

1. **EggGradingService** - Auto-grades eggs from daily collections
2. **LivestockInventoryService** - Manages birds ready for marketplace
3. **SupplyConsumptionService** - Tracks vaccine, feed, and medicine usage
4. **InventoryDashboardService** - Provides comprehensive inventory overview

**Database Models:**
- `EggCollection` - Daily egg batch tracking
- `EggInventory` - Multi-dimensional egg inventory (type/grade/size)
- `LivestockInventory` - Animals ready for sale
- `SupplyItem` - Farm supplies (feeds, vaccines, medicines)
- `InventoryTransaction` - Audit trail for all movements
- `Product` - Updated with inventory links

---

## 1. EggGradingService Integration

### When to Call
**Location:** FlockRecord Controller - in `store()` or `update()` method when farm owner logs eggs collected

### Usage

```php
use App\Services\EggGradingService;

class FlockRecordController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'flock_id' => 'required|exists:flocks,id',
            'record_date' => 'required|date',
            'eggs_collected' => 'required|integer|min:0',
            'eggs_broken' => 'required|integer|min:0',
            'feed_consumed_kg' => 'required|numeric',
            'water_consumed_liters' => 'required|numeric',
        ]);
        
        $flock = Flock::findOrFail($validated['flock_id']);
        
        // Create daily record
        $record = FlockRecord::create([
            'flock_id' => $flock->id,
            'recorded_by' => auth()->id(),
            'record_date' => $validated['record_date'],
            'eggs_collected' => $validated['eggs_collected'],
            'eggs_broken' => $validated['eggs_broken'],
            'feed_consumed_kg' => $validated['feed_consumed_kg'],
            'water_consumed_liters' => $validated['water_consumed_liters'],
        ]);
        
        // AUTO-GRADE EGGS
        if ($validated['eggs_collected'] > 0) {
            $eggService = new EggGradingService();
            $collection = $eggService->gradeAndCreateInventory(
                $flock,
                $validated['eggs_collected'],
                $validated['eggs_broken'],
                $validated['record_date']
            );
            
            return response()->json([
                'message' => 'Daily record created and eggs graded',
                'record_id' => $record->id,
                'egg_collection' => $collection,
                'products_created' => $collection->eggInventories->count(),
            ]);
        }
        
        return response()->json([
            'message' => 'Daily record created',
            'record_id' => $record->id,
        ]);
    }
}
```

### What EggGradingService Returns

```php
$collection = EggCollection {
    batch_id: "FO-5-12-1712880000",       // Unique batch identifier
    farm_owner_id: 5,
    flock_id: 12,
    collection_date: "2026-04-07",
    eggs_collected: 80,
    eggs_broken: 5,
    eggInventories: Collection [          // 6 entries: 3 grades × 2 sizes
        {
            egg_type: "organic",           // or "brown", "white"
            grade: "A",                    // or "B", "C"
            size: "large",                 // or "small"
            quantity_total: 45,
            quantity_available: 45,
            freshness_expires_at: "2026-04-21",  // 14 days for grade A
            status: "fresh",
            batch_id: "FO-5-12-1712880000",
            product_id: 142,
        },
        // ... 5 more entries
    ]
};
```

### Auto-Pricing Calculation

```python
Price = Base × TypeMultiplier × SizeMultiplier

Grade A (₱8.99):
  - Organic Large  = 8.99 × 1.5 × 1.2 = ₱16.18
  - Organic Small  = 8.99 × 1.5 × 0.9 = ₱12.13
  - Brown Large    = 8.99 × 1.2 × 1.2 = ₱12.95
  - Brown Small    = 8.99 × 1.2 × 0.9 = ₱9.71
  - White Large    = 8.99 × 1.0 × 1.2 = ₱10.79
  - White Small    = 8.99 × 1.0 × 0.9 = ₱8.09

Grade B (₱6.99):
  - Organic Large  = 6.99 × 1.5 × 1.2 = ₱12.58
  - Organic Small  = 6.99 × 1.5 × 0.9 = ₱9.43
  - Brown Large    = 6.99 × 1.2 × 1.2 = ₱10.07
  - Brown Small    = 6.99 × 1.2 × 0.9 = ₱7.55
  - White Large    = 6.99 × 1.0 × 1.2 = ₱8.39
  - White Small    = 6.99 × 1.0 × 0.9 = ₱6.29

Grade C (₱4.99):
  - Organic Large  = 4.99 × 1.5 × 1.2 = ₱8.98
  - Organic Small  = 4.99 × 1.5 × 0.9 = ₱6.74
  - Brown Large    = 4.99 × 1.2 × 1.2 = ₱7.19
  - Brown Small    = 4.99 × 1.2 × 0.9 = ₱5.39
  - White Large    = 4.99 × 1.0 × 1.2 = ₱5.99
  - White Small    = 4.99 × 1.0 × 0.9 = ₱4.49
```

---

## 2. LivestockInventoryService Integration

### When to Call

**Location 1:** When livestock reaches ready age
```php
// Called periodically from Artisan command
class UpdateLivestockReadiness extends Command
{
    public function handle()
    {
        $service = new LivestockInventoryService();
        
        // Get all growing livestock
        $growing = LivestockInventory::where('status', 'growing')->get();
        
        foreach ($growing as $inventory) {
            $inventory->age_weeks++;
            $service->updateReadiness($inventory);
            
            if ($inventory->status === 'ready_for_sale') {
                // Send notification to farm owner
                // ...
            }
        }
    }
}
```

**Location 2:** When farm owner adds livestock for sale
```php
class LivestockController extends Controller
{
    public function create(Request $request)
    {
        $validated = $request->validate([
            'flock_id' => 'required|exists:flocks,id',
            'quantity_available_for_sale' => 'required|integer|min:1',
        ]);
        
        $flock = Flock::findOrFail($validated['flock_id']);
        $service = new LivestockInventoryService();
        
        // Create or update inventory
        $inventory = $service->createInventoryFromFlock(
            $flock,
            $validated['quantity_available_for_sale']
        );
        
        return response()->json([
            'message' => 'Livestock added to marketplace',
            'inventory_id' => $inventory->id,
            'weeks_until_ready' => $inventory->weeks_until_ready,
            'status' => $inventory->status,
        ]);
    }
}
```

### Livestock Type Ready Ages

| Type | Ready Age | Typical Weight |
|------|-----------|----------------|
| Broiler | 8 weeks | 2.5 kg |
| Layer | 16-20 weeks | 1.8 kg |
| Breeder | 20 weeks | 2.2 kg |
| Fighting Cock | 24 weeks | 2.0 kg |
| Native | 16 weeks | 1.5 kg |
| Duck | 10 weeks | 3.0 kg |
| Quail | 6 weeks | 0.25 kg |

---

## 3. SupplyConsumptionService Integration

### When to Call

**Location 1:** After vaccination is recorded
```php
class VaccinationController extends Controller
{
    public function store(Request $request)
    {
        $vaccination = Vaccination::create([...]);
        
        // Record vaccine consumption
        $consumptionService = new SupplyConsumptionService();
        $consumptionService->recordVaccineConsumption($vaccination);
        
        return response()->json(['message' => 'Vaccination recorded and supply deducted']);
    }
}
```

**Location 2:** After daily feed record is logged
```php
class FlockRecordController extends Controller
{
    public function store(Request $request)
    {
        $record = FlockRecord::create([...]);
        
        // Record feed consumption
        $consumptionService = new SupplyConsumptionService();
        $consumptionService->recordFeedConsumption(
            $record,
            $record->feed_consumed_kg
        );
        
        // Get upcoming needs
        $needs = $consumptionService->getUpcomingSupplyNeeds(
            $record->flock
        );
        
        return response()->json([
            'message' => 'Record created',
            'upcoming_needs' => $needs,
        ]);
    }
}
```

### Supply Needs Response

```php
[
    'feed' => [
        'name' => 'Grower Feed',
        'daily_quantity_kg' => 2.5,
        'monthly_quantity_kg' => 75,
        'priority' => 'critical',
    ],
    'vaccinations' => [
        [
            'name' => 'Newcastle Booster',
            'weeks' => 4,
            'priority' => 'high',
        ],
    ],
    'medicine' => [
        [
            'name' => 'Vitamin & Electrolyte Supplement',
            'frequency' => 'Twice weekly',
            'priority' => 'high',
        ],
    ],
]
```

---

## 4. InventoryDashboardService Integration

### Dashboard Controller

```php
class InventoryDashboardController extends Controller
{
    public function index()
    {
        $dashboardService = new InventoryDashboardService();
        $farmOwnerId = auth()->user()->farm_owner_id;
        
        $inventory = $dashboardService->getInventoryOverview($farmOwnerId);
        $healthScore = $dashboardService->getInventoryHealthScore($farmOwnerId);
        $metrics = $dashboardService->getInventoryMetrics($farmOwnerId, 30);
        
        return view('inventory.dashboard', [
            'inventory' => $inventory,
            'health_score' => $healthScore,
            'metrics' => $metrics,
        ]);
    }
}
```

### Dashboard Data Structure

```php
[
    'eggs' => [
        'total_quantity' => 450,
        'quantity_available' => 200,
        'quantity_sold' => 250,
        'inventory_value' => 3450.50,
        'by_type' => [
            'organic' => [
                'total' => 200,
                'available' => 80,
                'sold' => 120,
                'revenue' => 2100,
            ],
            // ...
        ],
        'freshness_status' => [
            'fresh' => 150,
            'expiring_today' => 5,
            'expiring_soon' => 25,
            'expired' => 0,
        ],
    ],
    'livestock' => [
        'total_inventory' => 85,
        'ready_for_sale' => 20,
        'growing' => 65,
        'total_sold' => 45,
        'by_type' => [
            'broiler' => [
                'total_available' => 50,
                'ready_for_sale' => 15,
                'growing' => 35,
                'total_sold' => 25,
            ],
            // ...
        ],
        'weeks_until_ready' => [
            [
                'type' => 'layer',
                'quantity' => 30,
                'weeks_until_ready' => 6,
                'ready_date' => '2026-05-19',
            ],
        ],
    ],
    'supplies' => [
        'total_items' => 24,
        'in_stock_count' => 20,
        'low_stock_count' => 3,
        'out_of_stock_count' => 1,
        'by_category' => [
            'feeds' => [
                'count' => 8,
                'total_value' => 5000,
                'low_stock_count' => 2,
                'items' => [
                    ['name' => 'Starter Feed', 'quantity' => 100, 'status' => 'in_stock'],
                    // ...
                ],
            ],
        ],
    ],
    'alerts' => [
        [
            'type' => 'eggs_expiring_today',
            'severity' => 'medium',
            'message' => '5 batches of eggs expire today',
        ],
        // ...
    ],
]
```

---

## 5. Order Processing Integration

### When Customer Places Order

```php
class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        $order = Order::create([...]);
        
        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            $quantity = $item['quantity'];
            
            // Find linked inventory
            if ($product->egg_inventory_id) {
                $inventory = EggInventory::find($product->egg_inventory_id);
                $inventory->recordSale($quantity);
            } elseif ($product->livestock_inventory_id) {
                $inventory = LivestockInventory::find($product->livestock_inventory_id);
                $inventory->recordSale($quantity);
            }
            
            // Update product
            $product->decrement('quantity_available', $quantity);
            $product->increment('quantity_sold', $quantity);
            
            // If product linked with auto-sync, update from inventory
            if ($product->auto_sync_inventory) {
                $product->update([
                    'quantity_available' => $inventory->quantity_available_for_sale,
                ]);
            }
        }
        
        return response()->json(['order_id' => $order->id]);
    }
}
```

---

## 6. Scheduled Tasks (Artisan Commands)

### Update Egg Freshness Status

```php
// app/Console/Commands/UpdateEggFreshness.php

class UpdateEggFreshness extends Command
{
    public function handle()
    {
        EggInventory::updateFreshStatus();
        
        $this->info('Egg freshness status updated');
    }
}

// Schedule in app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('inventory:update-egg-freshness')->daily();
}
```

### Update Livestock Readiness

```php
// app/Console/Commands/UpdateLivestockReadiness.php

class UpdateLivestockReadiness extends Command
{
    public function handle()
    {
        $service = new LivestockInventoryService();
        
        $livestock = LivestockInventory::where('status', 'growing')->get();
        foreach ($livestock as $item) {
            $service->updateReadiness($item);
        }
        
        $this->info('Livestock readiness updated');
    }
}

// Schedule
$schedule->command('inventory:update-livestock-readiness')->weekly();
```

### Send Low Stock Alerts

```php
// app/Console/Commands/SendLowStockAlerts.php

class SendLowStockAlerts extends Command
{
    public function handle()
    {
        $supplies = SupplyItem::lowStock()->get();
        
        foreach ($supplies->groupBy('farm_owner_id') as $farmOwnerId => $items) {
            $farmOwner = FarmOwner::find($farmOwnerId);
            
            // Send email/SMS notification
            Notification::send($farmOwner->user, new LowStockNotification($items));
        }
    }
}

// Schedule
$schedule->command('inventory:send-low-stock-alerts')->daily();
```

---

## 7. Query Scopes & Usage

### EggInventory Scopes

```php
// Get fresh eggs ready to sell
$freshEggs = EggInventory::byFarmOwner($farmOwnerId)
    ->fresh()
    ->inStock()
    ->get();

// Get "Grade A" eggs expiring this week
$pendingExpiry = EggInventory::byFarmOwner($farmOwnerId)
    ->byGrade('A')
    ->where('freshness_expires_at', '<=', now()->addDays(7))
    ->fresh()
    ->get();

// Get all "Organic" inventory
$organic = EggInventory::byType('organic')->get();

// Get "Large" eggs specifically
$large = EggInventory::bySize('large')->get();

// Get expired eggs for cleanup
$expired = EggInventory::where('status', 'expired')->get();
```

### SupplyItem Scopes

```php
// Get low stock alerts
$lowStock = SupplyItem::byFarmOwner($farmOwnerId)
    ->lowStock()
    ->get();

// Get out of stock items
$outOfStock = SupplyItem::outOfStock()->get();

// Get vaccines expiring in 30 days
$expiringVaccines = SupplyItem::byCategory('vaccines')
    ->expiringSoon(30)
    ->get();

// Get all feeds with critical status
$criticalFeeds = SupplyItem::where('category', 'feeds')
    ->where('status', 'out_of_stock')
    ->get();
```

### LivestockInventory Scopes

```php
// Get all broilers ready for sale
$readyBroilers = LivestockInventory::byType('broiler')
    ->readyForSale()
    ->get();

// Get livestock still growing
$growing = LivestockInventory::growing()->get();

// Get layer hens ready for marketplace
$readyLayers = LivestockInventory::byType('layer')
    ->ready_for_sale()
    ->get();
```

---

## 8. Error Handling

### Common Issues & Solutions

**Issue: Inventory not deducting after order**
```php
// Check 1: Is product linked to inventory?
if (!$product->egg_inventory_id && !$product->livestock_inventory_id) {
    // Product not linked, manually update quantity_available
    $product->decrement('quantity_available', $qty);
}

// Check 2: Is auto_sync_inventory enabled?
if ($product->auto_sync_inventory) {
    // Should update automatically when inventory changes
}

// Check 3: Is inventory actually available?
$inventory->refresh();
if ($inventory->quantity_available_for_sale < $qty) {
    throw new InsufficientInventoryException();
}
```

**Issue: Eggs not grading automatically**
```php
// Verify FlockRecord has eggs_collected > 0
if ($record->eggs_collected === 0) {
    // Service won't run
}

// Verify flock has valid breed_type
if (!$flock->breed_type) {
    // Service defaults to 'white' eggs
}

// Check migration applied
// php artisan migrate --path=database/migrations/2026_04_07_*
```

**Issue: Supply consumption not tracking**
```php
// Verify Vaccination/FlockRecord has farm_owner_id
$vaccination->farm_owner_id; // Must exist

// Verify SupplyItem exists for the vaccine name
SupplyItem::where('farm_owner_id', $farmOwnerId)
    ->where('name', 'LIKE', '%' . $vaccineName . '%')
    ->exists();

// Check quantity_on_hand > 0
if ($supply->quantity_on_hand <= 0) {
    // Won't deduct
}
```

---

## 9. API Endpoints (To Be Implemented)

```
GET  /api/inventory/overview               - Get complete inventory
GET  /api/inventory/eggs                   - Egg inventory details
GET  /api/inventory/livestock              - Livestock details
GET  /api/inventory/supplies               - Supply details
GET  /api/inventory/health-score           - Inventory health score
GET  /api/inventory/alerts                 - Current alerts
GET  /api/inventory/transactions?days=30   - Recent transactions
POST /api/inventory/egg-grading/manual    - Manual grading override
POST /api/inventory/livestock/add          - Add livestock for sale
GET  /api/inventory/consumption/forecast   - Consumption prediction
```

---

## 10. Testing Checklist

- [ ] Daily egg record creates EggCollection & EggInventory
- [ ] Pricing calculated correctly (type/grade/size multipliers)
- [ ] Products auto-created with correct prices
- [ ] Vaccination records deduct vaccine supply
- [ ] Feed consumption records deduct feed supply
- [ ] Order checkout deducts inventory quantities
- [ ] Egg freshness status updates after expiry date
- [ ] Livestock readiness updates when age reached
- [ ] Dashboard shows correct totals
- [ ] Alerts trigger for out-of-stock supplies
- [ ] Alerts trigger for expiring eggs
- [ ] Alerts trigger for low livestock availability
