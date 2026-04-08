# Inventory System - Complete Architecture

## System Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        POULTRY FARM OWNER                       │
└─────────────────────────────────────────────────────────────────┘
                              │
                ┌─────────────┼─────────────┐
                │             │             │
                ▼             ▼             ▼
        ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
        │ Daily Record │ │ Vaccination  │ │ Feed/Supply  │
        │   (Eggs)     │ │   (Vaccine)  │ │  Consumed    │
        └──────────────┘ └──────────────┘ └──────────────┘
                │             │             │
         [FlockRecord]  [Vaccination]  [SupplyItem]
                │             │             │
                └─────────────┼─────────────┘
                              │
                ┌─────────────┴─────────────┐
                │                           │
        ┌───────▼────────────┐      ┌───────▼────────────┐
        │ AUTO-GRADE EGGS    │      │ AUTO-TRACK SUPPLY  │
        │ (EggGradingService)│      │ (SupplyConsumption)│
        └────────┬───────────┘      └────────┬───────────┘
                 │                           │
        ┌────────▼──────────────┐    ┌───────▼────────────┐
        │   EggCollection       │    │InventoryTransaction
        │ (batch tracking)      │    │ (audit trail)
        └────────┬──────────────┘    └─────────────────────┘
                 │
        ┌────────▼──────────────────────────────────┐
        │     EggInventory (x6 per collection)      │
        │ Type×Grade×Size: Organic/Brown/White ×   │
        │           A/B/C × Large/Small            │
        │                                           │
        │ Status tracking:                          │
        │ fresh → expiring_soon → expired          │
        └────────┬──────────────────────────────────┘
                 │
        ┌────────▼──────────────────────────────────┐
        │    Auto-Created Products + Pricing        │
        │ (linked via egg_inventory_id)            │
        │ Price = Grade×Type×Size multipliers      │
        └────────┬──────────────────────────────────┘
                 │
        ┌────────▼──────────────────────────────────┐
        │   Marketplace (Products awaiting orders)  │
        └────────┬──────────────────────────────────┘
                 │
        ┌────────▼──────────────────────────────────┐
        │    Customer Places Order                   │
        └────────┬──────────────────────────────────┘
                 │
        ┌────────▼──────────────────────────────────┐
        │ DEDUCT FROM INVENTORY                     │
        │ EggInventory.recordSale(qty)              │
        │ Create InventoryTransaction               │
        │ Update Product quantity_available         │
        └────────┬──────────────────────────────────┘
                 │
        ┌────────▼──────────────────────────────────┐
        │   Dashboard Shows:                        │
        │ • Inventory levels                        │
        │ • Revenue/Costs                           │
        │ • Health Score                            │
        │ • Alerts (expired, low stock)             │
        └────────────────────────────────────────────┘
```

## Database Schema

```
┌──────────────────────┐
│   egg_collections    │
├──────────────────────┤
│ id                   │
│ farm_owner_id (FK)   │
│ flock_id (FK)        │
│ collection_date      │
│ eggs_collected       │
│ eggs_broken          │
│ graded_a/b/c         │
│ batch_id (UNIQUE)    │
│ created_at           │
└──────────────────────┘
         │
         ├─── hasMany ───┐
         │               ▼
         │      ┌───────────────────────────┐
         │      │   egg_inventory (x6)      │
         │      ├───────────────────────────┤
         │      │ id                        │
         │      │ farm_owner_id             │
         │      │ flock_id                  │
         │      │ batch_id (ties to collection)
         │      │ egg_type: organic/brown/white
         │      │ grade: A/B/C              │
         │      │ size: large/small         │
         │      │ quantity_total            │
         │      │ quantity_available        │
         │      │ quantity_sold             │
         │      │ quantity_expired          │
         │      │ freshness_expires_at      │
         │      │ status: fresh/expiring/expired
         │      │ collection_date           │
         │      │ UNIQUE(farm,flock,type,grade,size,date)
         │      └───────────────────────────┘
         │               │
         │               ├─── belongsTo ───┐
         │               │                 ▼
         │               │        ┌──────────────────┐
         │               │        │    products      │
         │               │        ├──────────────────┤
         │               │        │ id               │
         │               │        │ egg_inventory_id │
         │               │        │ name             │
         │               │        │ sku              │
         │               │        │ price (dynamic)  │
         │               │        │ quantity_available
         │               │        │ quantity_sold    │
         │               │        │ auto_sync_inventory
         │               │        └──────────────────┘
         │               │                 │
         │               │                 └─ On Order: recordSale()
         │               │
         │               └─ Creates InventoryTransaction (sale)
         │
         └─ Creates InventoryTransaction (collection)


┌────────────────────────────────┐
│  livestock_inventory           │
├────────────────────────────────┤
│ id                             │
│ farm_owner_id (FK)             │
│ flock_id (FK)                  │
│ livestock_type: broiler/layer/ │
│   breeder/fighting_cock/native/│
│   duck/quail                   │
│ age_weeks                      │
│ weeks_until_ready (computed)   │
│ estimated_ready_date           │
│ quantity_available_for_sale    │
│ quantity_reserved              │
│ quantity_sold                  │
│ average_weight_kg              │
│ status: growing/ready_for_sale/│
│   sold_out                     │
│ acquisition_cost_per_unit      │
│ ready_date                     │
│ last_updated                   │
│ created_at                     │
└────────────────────────────────┘
         │
         ├─── Auto-Creates when ready_for_sale
         │
         ▼
    ┌──────────────────┐
    │    products      │
    ├──────────────────┤
    │livestock_inventory_id
    │ price (by type)  │
    │ quantity_available
    │ auto_sync_inventory
    └──────────────────┘


┌──────────────────────────────────┐
│  supply_items                    │
├──────────────────────────────────┤
│ id                               │
│ farm_owner_id (FK)               │
│ name                             │
│ category: feeds/vaccines/        │
│   medications/supplements/etc    │
│ quantity_on_hand                 │
│ minimum_stock                    │
│ unit_cost                        │
│ status: in_stock/low/            │
│   out_of_stock/expiring/expired  │
│ expiration_date                  │
│ created_at                       │
└──────────────────────────────────┘
         │
         └─ On consumption: decrement quantity_on_hand
            Create InventoryTransaction (consumption)


┌────────────────────────────────────────┐
│  inventory_transactions (Audit Trail)  │
├────────────────────────────────────────┤
│ id                                     │
│ farm_owner_id                          │
│ inventoryable_type: EggInventory|      │
│   LivestockInventory|SupplyItem        │
│ inventoryable_id                       │
│ transaction_type: collection/sale/     │
│   consumption/expiry/adjustment/loss   │
│ quantity                               │
│ unit_price                             │
│ total_amount                           │
│ reference_id (Order/Vaccination/etc)   │
│ reference_type                         │
│ recorded_by (user_id)                  │
│ transaction_date                       │
│ notes                                  │
│ created_at                             │
└────────────────────────────────────────┘
```

## Service Architecture

### 1. EggGradingService
```
INPUT: Flock, eggs_collected, eggs_broken, date
│
├─ determineEggType(flock)
│  └─ Returns: 'organic' | 'brown' | 'white'
│     Based on breed_type in flock
│
├─ calculateGradeDistribution(eggs)
│  └─ Returns: {A: 75%, B: 15%, C: 10%}
│
├─ calculateLargePercentage(flock)
│  └─ Returns: 60-80% breed-specific size distribution
│
├─ createEggCollection()
│  └─ Creates batch with unique batch_id
│
├─ For each grade (A, B, C):
│  ├─ createOrUpdateProduct()
│  │  └─ Auto-creates 2 products (large + small)
│  └─ createEggInventory()
│     └─ Creates 2 entries (large + small)
│        Sets freshness_expires_at based on grade:
│        A: +14 days, B: +10 days, C: +7 days
│
└─ syncAllProductInventories()
   └─ Updates product quantities from inventory (FIFO)

OUTPUT: EggCollection with 6 EggInventory + 6 Products auto-created
```

### 2. LivestockInventoryService
```
INPUT: Flock, quantity_available_for_sale
│
├─ getFlockType(flock)
│  └─ Determines: broiler|layer|breeder|fighting_cock|native|duck|quail
│
├─ estimateWeight(flock)
│  └─ Breed-specific weight estimates by age
│
├─ createInventoryFromFlock()
│  └─ Creates/updates LivestockInventory
│     Calls updateReadiness()
│
├─ updateReadiness()
│  ├─ Calculates weeks_until_ready
│  ├─ If ready: sets status = 'ready_for_sale'
│  └─ Calls createOrUpdateProduct()
│
└─ recordSale(qty)
   └─ Deducts quantity_available_for_sale
      Updates quantity_sold
      Creates InventoryTransaction

OUTPUT: LivestockInventory with auto-product if ready
```

### 3. SupplyConsumptionService
```
INPUT: Vaccination | FlockRecord | Flock + medicine_name
│
├─ recordVaccineConsumption(vaccination)
│  ├─ Finds matching SupplyItem by vaccine_type
│  ├─ Deducts quantity_on_hand
│  ├─ Creates InventoryTransaction
│  └─ Triggers notifyLowStock() if needed
│
├─ recordFeedConsumption(flockRecord, qty)
│  ├─ Auto-determines feed type by flock age
│  ├─ Finds SupplyItem by name
│  ├─ Deducts quantity_on_hand
│  ├─ Creates InventoryTransaction
│  └─ Triggers alert if low stock
│
├─ recordMedicineConsumption()
│  └─ Same pattern as vaccine/feed
│
├─ getPredictedConsumption()
│  ├─ Analyzes last 60 days of consumption
│  ├─ Calculates average daily cost
│  └─ Forecasts next 30 days
│
└─ getUpcomingSupplyNeeds()
   └─ Returns feed, vaccines, medicine recommendations

OUTPUT: InventoryTransaction + updated SupplyItem quantities
```

### 4. InventoryDashboardService
```
INPUT: farm_owner_id
│
├─ getEggInventorySummary()
│  ├─ Total quantity | Available | Sold | Expired
│  ├─ By type (organic/brown/white)
│  ├─ By grade (A/B/C)
│  ├─ Freshness status (fresh/expiring/expiring_today/expired)
│  └─ Inventory value (qty × price)
│
├─ getLivestockInventorySummary()
│  ├─ Total available | Ready for sale | Growing
│  ├─ By type with breakdown
│  ├─ Weeks until ready for each type
│  └─ Estimated revenue
│
├─ getSupplyInventorySummary()
│  ├─ In stock | Low stock | Out of stock counts
│  ├─ By category with details
│  ├─ Expiring soon | Expired alerts
│  └─ Total inventory value
│
├─ getInventoryAlerts()
│  ├─ Expired eggs alert (high severity)
│  ├─ Eggs expiring today alert
│  ├─ Low livestock alert
│  ├─ Out of stock supplies alert
│  └─ Low stock supplies alert
│
├─ getRecentTransactions(days)
│  └─ Last 50 transactions grouped by type
│
├─ getInventoryHealthScore()
│  ├─ Base: 100 points
│  ├─ -5 per expired egg batch (max -20)
│  ├─ -3 per out of stock supply (max -15)
│  ├─ +10 for 3+ livestock types ready
│  ├─ +10 if 50%+ eggs sold
│  └─ Result: 0-100 score
│
└─ getInventoryMetrics(days)
   ├─ Total revenue (sales)
   ├─ Total consumption cost
   ├─ Net profit (revenue - cost)
   ├─ Daily breakdown
   └─ Trend analysis

OUTPUT: Complete dashboard data structure
```

## Query Scopes Reference

```php
// EggInventory Scopes
::byFarmOwner($id)              // Filter by farm owner
::inStock()                     // Only quantity_available > 0
::byGrade('A')                  // Filter by grade
::byType('organic')             // Filter by type
::bySize('large')               // Filter by size
::fresh()                       // Status = 'fresh'
::expiringToday()              // Expires today

// LivestockInventory Scopes
::byFarmOwner($id)              // Filter by farm owner
::byType('broiler')             // Filter by type
::readyForSale()                // Status = 'ready_for_sale'
::growing()                     // Status = 'growing'

// SupplyItem Scopes
::byFarmOwner($id)              // Filter by farm owner
::byCategory('feeds')           // Filter by category
::lowStock()                    // quantity_on_hand <= reorder_point
::outOfStock()                  // quantity_on_hand <= 0
::expiringSoon(30)              // Expires within days
::expired()                     // Expiration date passed

// InventoryTransaction Scopes
::byFarmOwner($id)              // Filter by farm owner
::byType('sale')                // Filter by transaction type
::byDate($date)                 // Filter by date
```

## Key Formulas

### Egg Pricing Formula
```
Price per piece = BasePrice × TypeMultiplier × SizeMultiplier

Where:
  BasePrice by grade:
    Grade A: ₱8.99
    Grade B: ₱6.99
    Grade C: ₱4.99
  
  TypeMultiplier:
    Organic: 1.5
    Brown: 1.2
    White: 1.0
  
  SizeMultiplier:
    Large: 1.2
    Small: 0.9

Examples:
  Organic Grade A Large = 8.99 × 1.5 × 1.2 = ₱16.18
  Brown Grade B Small = 6.99 × 1.2 × 0.9 = ₱7.55
  White Grade C Large = 4.99 × 1.0 × 1.2 = ₱5.99
```

### Livestock Ready Age
```
Broiler: 8 weeks (2.5 kg) - Cost: ₱375 (2.5 × ₱150/kg)
Layer: 16-20 weeks (1.8 kg) - Price: ₱200/bird
Breeder: 20 weeks (2.2 kg) - Price: ₱250/bird
Fighting Cock: 24 weeks (2.0 kg) - Price: ₱300/bird
Native: 16 weeks (1.5 kg) - Price: ₱180/bird
Duck: 10 weeks (3.0 kg) - Price: ₱280/bird
Quail: 6 weeks (0.25 kg) - Price: ₱80/bird
```

### Feed Consumption Rate (kg/bird/day)
```
Chicks (age < 4w): 15g/day
Young (age 4-8w): 25g/day
Growing (age 8-16w): 50g/day
Pre-layer (age 16-20w): 80g/day
Mature (age > 20w): 100g/day
```

## Ready-to-Use Examples

### Example 1: Grade eggs from daily collection
```php
$eggService = new EggGradingService();
$collection = $eggService->gradeAndCreateInventory(
    $flock,           // Flock instance
    80,              // eggs_collected
    5,               // eggs_broken
    '2026-04-07'     // collection_date
);

// Results in:
// - 1 EggCollection record
// - 6 EggInventory entries (3 grades × 2 sizes)
// - 6 Product records with auto-pricing
// - 6 InventoryTransaction (collection type)

echo "Created " . $collection->eggInventories->count() . " inventory entries";
```

### Example 2: Track livestock ready to sell
```php
$livestockService = new LivestockInventoryService();
$inventory = $livestockService->createInventoryFromFlock(
    $flock,
    50  // quantity available for sale
);

// If broiler:
echo $inventory->weeks_until_ready . " weeks until ready";
// If already ready:
echo "Ready NOW! Price: ₱" . $inventory->average_weight_kg * 150;
```

### Example 3: Record vaccine consumption
```php
$supplyService = new SupplyConsumptionService();
$supplyService->recordVaccineConsumption($vaccination);

// Automatically:
// - Finds matching vaccine supply
// - Deducts 1 unit from quantity_on_hand
// - Creates InventoryTransaction
// - Alerts if low stock
```

### Example 4: Get inventory overview
```php
$dashboardService = new InventoryDashboardService();
$overview = $dashboardService->getInventoryOverview($farmOwnerId);

echo "Active eggs: " . $overview['eggs']['quantity_available'];
echo "Ready to sell: " . $overview['livestock']['ready_for_sale'];
echo "Total value: ₱" . $overview['eggs']['inventory_value'];
echo "Health score: " . $dashboardService->getInventoryHealthScore($farmOwnerId) . "/100";
```

## Integration Checklist

- [ ] EggGradingService integrated in FlockRecordController
- [ ] Show grading review modal after daily record save
- [ ] LivestockInventoryService integrated when adding animals
- [ ] SupplyConsumptionService called on vaccine/feed records
- [ ] Order checkout deducts inventory via recordSale()
- [ ] Dashboard displays InventoryDashboardService overview
- [ ] Alerts system shown on dashboard
- [ ] Scheduled commands for: egg freshness, livestock readiness, low stock alerts
- [ ] All InventoryTransaction records logging correctly
- [ ] Products properly linked to inventory entries
- [ ] auto_sync_inventory flag working correctly
