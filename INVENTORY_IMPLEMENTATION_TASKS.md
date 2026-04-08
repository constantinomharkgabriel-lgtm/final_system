# Inventory System Implementation Checklist

## Phase 1: Controller Integration (5 hours)

### 1.1 FlockRecord Controller - Egg Grading Integration
**Status:** ⬜ NOT STARTED

**File:** `app/Http/Controllers/FlockRecordController.php`

**Tasks:**
- [ ] Add `use App\Services\EggGradingService;` at top
- [ ] In `store()` method after creating FlockRecord:
  ```php
  if ($validated['eggs_collected'] > 0) {
      $service = new EggGradingService();
      $collection = $service->gradeAndCreateInventory(
          $flock,
          $validated['eggs_collected'],
          $validated['eggs_broken'],
          $validated['record_date']
      );
  }
  ```
- [ ] Return success response with products created count
- [ ] Test: Log daily record with 100 eggs → Should create 6 EggInventory + 6 Products

**Estimated Time:** 1 hour

---

### 1.2 Vaccination Controller - Supply Deduction Integration
**Status:** ⬜ NOT STARTED

**File:** `app/Http/Controllers/VaccinationController.php`

**Tasks:**
- [ ] Add `use App\Services\SupplyConsumptionService;` at top
- [ ] In `store()` method after creating Vaccination:
  ```php
  $service = new SupplyConsumptionService();
  $service->recordVaccineConsumption($vaccination);
  ```
- [ ] Catch exception if supply not found (log but don't fail)
- [ ] Test: Create vaccination → Check SupplyItem quantity decreased

**Estimated Time:** 0.5 hours

---

### 1.3 FlockRecord Controller - Feed Deduction Integration
**Status:** ⬜ NOT STARTED

**File:** `app/Http/Controllers/FlockRecordController.php` (same file as 1.1)

**Tasks:**
- [ ] Add `use App\Services\SupplyConsumptionService;` at top
- [ ] In `store()` method after creating FlockRecord:
  ```php
  $service = new SupplyConsumptionService();
  $service->recordFeedConsumption(
      $record,
      $validated['feed_consumed_kg']
  );
  
  // Get upcoming needs
  $needs = $service->getUpcomingSupplyNeeds($flock);
  ```
- [ ] Return upcoming needs in response
- [ ] Test: Log daily record with feed → SupplyItem quantity decreases

**Estimated Time:** 1 hour

---

### 1.4 Order Checkout Controller - Inventory Deduction
**Status:** ⬜ NOT STARTED

**File:** `app/Http/Controllers/CheckoutController.php` or similar

**Tasks:**
- [ ] Find order checkout/processing controller
- [ ] After payment confirmation:
  ```php
  foreach ($order->items as $item) {
      $product = $item->product;
      $quantity = $item->quantity;
      
      // Deduct from inventory
      if ($product->egg_inventory_id) {
          $inventory = EggInventory::find($product->egg_inventory_id);
          if ($inventory) $inventory->recordSale($quantity);
      } elseif ($product->livestock_inventory_id) {
          $inventory = LivestockInventory::find($product->livestock_inventory_id);
          if ($inventory) $inventory->recordSale($quantity);
      }
      
      // Update product quantity
      $product->decrement('quantity_available', $quantity);
      $product->increment('quantity_sold', $quantity);
  }
  ```
- [ ] Log errors if inventory not found
- [ ] Test: Place order for 20 eggs → Inventory decreases by 20

**Estimated Time:** 1.5 hours

---

### 1.5 Livestock Controller - Create/Add Livestock
**Status:** ⬜ NOT STARTED

**File:** Create `app/Http/Controllers/LivestockController.php` or update existing

**Tasks:**
- [ ] Add `use App\Services\LivestockInventoryService;` at top
- [ ] Create `create()` or `store()` method:
  ```php
  $service = new LivestockInventoryService();
  $inventory = $service->createInventoryFromFlock(
      $flock,
      $request->quantity_available_for_sale
  );
  ```
- [ ] Return: inventory_id, weeks_until_ready, estimated_ready_date, status
- [ ] Test: Add 50 broilers to flock → Shows "Ready in 8 weeks"

**Estimated Time:** 1 hour

---

## Phase 2: Dashboard (3 hours)

### 2.1 Dashboard Controller - Inventory Overview
**Status:** ⬜ NOT STARTED

**File:** Create `app/Http/Controllers/InventoryDashboardController.php`

**Tasks:**
- [ ] Create controller with `index()` method
- [ ] Use `InventoryDashboardService`:
  ```php
  $service = new InventoryDashboardService();
  $overview = $service->getInventoryOverview($farmOwnerId);
  $health = $service->getInventoryHealthScore($farmOwnerId);
  $metrics = $service->getInventoryMetrics($farmOwnerId, 30);
  ```
- [ ] Pass to view
- [ ] Test: Load dashboard → Shows eggs/livestock/supplies summary

**Estimated Time:** 1 hour

---

### 2.2 Dashboard Blade View
**Status:** ⬜ NOT STARTED

**File:** Create `resources/views/inventory/dashboard.blade.php`

**Sections to Include:**
- [ ] **Egg Inventory Card**
  - Total | Available | Sold | Expired
  - By type breakdown
  - Freshness status (fresh/expiring/expired)
  - Inventory value

- [ ] **Livestock Card**
  - Ready for sale | Growing | Sold
  - By type with weeks until ready
  - Total investment
  - Estimated revenue

- [ ] **Supplies Card**
  - In stock | Low stock | Out of stock counts
  - By category breakdown
  - Critical items highlight

- [ ] **Health Score Widget**
  - 0-100 score with visual gauge
  - Main issues affecting score

- [ ] **Alerts Section** (sticky)
  - Expired eggs (red)
  - Expiring today (orange)
  - Low livestock (yellow)
  - Out of stock (red)

- [ ] **Charts Section**
  - Revenue vs Cost (30 days)
  - Daily collection trend
  - Sales by product type

**Estimated Time:** 2 hours

---

## Phase 3: Forms & UI (4 hours)

### 3.1 Daily Egg Log Form - Grading Review
**Status:** ⬜ NOT STARTED

**File:** `resources/views/flocks/daily-record.blade.php`

**Current Flow:**
1. Farm owner fills: eggs_collected, eggs_broken
2. Hits submit
3. Currently just saves record

**New Flow:**
1. Farm owner fills form
2. Hits submit
3. **NEW:** Modal shows "Grading Review"
   - Proposed grades: A: 60, B: 12, C: 8
   - Proposed sizes: Large: 70%, Small: 30%
   - Expected products: "Organic Grade A Large: 42 eggs @ ₱16.18 each"
   - Allow adjust or confirm

**Implementation:**
- [ ] Create Livewire component or Alpine.js component for modal
- [ ] Controller returns grading preview (don't save yet)
- [ ] User can proceed or edit estimates
- [ ] Only save if confirmed
- [ ] Show success: "6 products created, 280 eggs in inventory"

**Estimated Time:** 1.5 hours

---

### 3.2 Add Livestock Form
**Status:** ⬜ NOT STARTED

**File:** Create `resources/views/livestock/create.blade.php`

**Form Fields:**
- [ ] Select flock (from farm owner's flocks)
- [ ] Select livestock type (dropdown: broiler, layer, breeder, etc.)
- [ ] Enter quantity available for sale (number input)
- [ ] Display: Average weight estimate (auto-calculated)
- [ ] Display: Weeks until ready (auto-calculated from type)
- [ ] Display: Estimated ready date (auto-calculated)
- [ ] Submit button

**Validation:**
- [ ] Flock exists and belongs to farm owner
- [ ] Quantity > 0
- [ ] Livestock type valid

**On Submit:**
- [ ] Call LivestockInventoryService
- [ ] Show created inventory
- [ ] Redirect to livestock list

**Estimated Time:** 1.5 hours

---

### 3.3 Product Marketplace Form - Inventory Linking
**Status:** ⬜ NOT STARTED

**File:** `resources/views/products/create.blade.php` (modify existing)

**New Section: Inventory Linking**
- [ ] Radio: "Is this from farm inventory?" (Yes/No)
- [ ] If Yes:
  - [ ] Select inventory type: "Eggs" or "Livestock"
  - [ ] If Eggs:
    - [ ] Shows: "Grade A Organic Large (42 available)"
    - [ ] Shows: Auto-price "₱16.18 per egg"
    - [ ] User can override price
    - [ ] Quantity to sell: (defaults to available)
  - [ ] If Livestock:
    - [ ] Shows: "Broiler birds (25 ready to sell)"
    - [ ] Shows: Auto-price "₱375 per bird"
    - [ ] User can override
    - [ ] Quantity to sell: (defaults to available)
- [ ] If No: Manual product entry (existing flow)

**Validation:**
- [ ] If linked to inventory, must set quantity_to_sell
- [ ] Can't sell more than available

**On Submit:**
- [ ] Create Product with egg_inventory_id or livestock_inventory_id
- [ ] Set auto_sync_inventory = true
- [ ] Success: "Product added to marketplace, linked to inventory"

**Estimated Time:** 1.5 hours

---

## Phase 4: Scheduled Tasks (2 hours)

### 4.1 Egg Freshness Updater
**Status:** ⬜ NOT STARTED

**File:** Create `app/Console/Commands/UpdateEggFreshness.php`

**Command:**
```bash
php artisan inventory:update-egg-freshness
```

**What it does:**
- [ ] Find all EggInventory where status = 'fresh'
- [ ] Check freshness_expires_at
- [ ] If today: set status = 'expiring_soon'
- [ ] If past: set status = 'expired'
- [ ] Log count updated

**Schedule:** Daily (midnight)
- [ ] Add to `app/Console/Kernel.php`:
  ```php
  $schedule->command('inventory:update-egg-freshness')->daily();
  ```

**Estimated Time:** 0.5 hours

---

### 4.2 Livestock Readiness Updater
**Status:** ⬜ NOT STARTED

**File:** Create `app/Console/Commands/UpdateLivestockReadiness.php`

**Command:**
```bash
php artisan inventory:update-livestock-readiness
```

**What it does:**
- [ ] Find all LivestockInventory where status = 'growing'
- [ ] Increment age_weeks by 1
- [ ] Call `updateReadiness()`
- [ ] If ready: log "X birds now ready for sale"
- [ ] Send notification to farm owner

**Schedule:** Weekly (Monday morning)
- [ ] Add to Kernel:
  ```php
  $schedule->command('inventory:update-livestock-readiness')->weekly();
  ```

**Estimated Time:** 0.5 hours

---

### 4.3 Low Stock Alert Sender
**Status:** ⬜ NOT STARTED

**File:** Create `app/Console/Commands/SendLowStockAlerts.php`

**Command:**
```bash
php artisan inventory:send-low-stock-alerts
```

**What it does:**
- [ ] Find all SupplyItem with lowStock() or outOfStock()
- [ ] Group by farm_owner_id
- [ ] For each farm owner:
  - [ ] Show list of low/out-of-stock items
  - [ ] Send email/SMS notification
  - [ ] Log alert sent

**Schedule:** Daily (morning)
- [ ] Add to Kernel:
  ```php
  $schedule->command('inventory:send-low-stock-alerts')->daily();
  ```

**Estimated Time:** 1 hour

---

## Phase 5: Testing & Validation (3 hours)

### 5.1 Egg Grading End-to-End Test
**Status:** ⬜ NOT STARTED

**Test Scenario:**
1. [ ] Create flock: Araucana layers (organic eggs)
2. [ ] Log daily record: 100 eggs collected, 5 broken
3. [ ] Verify results:
   - [ ] EggCollection created
   - [ ] 6 EggInventory entries (A/B/C × Large/Small)
   - [ ] Grade distribution correct: A≈75, B≈15, C≈10
   - [ ] Size distribution correct: 70% large
   - [ ] 6 Products auto-created
   - [ ] Prices correct: Organic × grade × size multipliers
   - [ ] freshness_expires_at: A+14d, B+10d, C+7d

**Pass/Fail:** ___

---

### 5.2 Inventory Deduction Test
**Status:** ⬜ NOT STARTED

**Test Scenario:**
1. [ ] After egg grading, Grade A Large has 52 eggs available
2. [ ] Customer orders 20 eggs of this product
3. [ ] Process order
4. [ ] Verify:
   - [ ] EggInventory quantity_available: 52 → 32
   - [ ] Product quantity_available: 52 → 32
   - [ ] InventoryTransaction created (sale type)
   - [ ] quantity_sold incremented

**Pass/Fail:** ___

---

### 5.3 Supply Consumption Test
**Status:** ⬜ NOT STARTED

**Test Scenario:**
1. [ ] Add SupplyItem: "Newcastle Vaccine" qty=50
2. [ ] Create Vaccination record for this vaccine
3. [ ] Verify:
   - [ ] SupplyItem quantity_on_hand: 50 → 49
   - [ ] InventoryTransaction created (consumption type)
   - [ ] If stock reaches minimum: low stock alert triggered

**Pass/Fail:** ___

---

### 5.4 Livestock Readiness Test
**Status:** ⬜ NOT STARTED

**Test Scenario:**
1. [ ] Add broiler flock, age 7 weeks
2. [ ] Create LivestockInventory for this flock (50 birds)
3. [ ] Status shows: "growing"
4. [ ] Weeks until ready: 1
5. [ ] Run UpdateLivestockReadiness command (simulating 1 week)
6. [ ] Age becomes 8 weeks
7. [ ] Status changes to: "ready_for_sale"
8. [ ] Product auto-created for marketplace

**Pass/Fail:** ___

---

### 5.5 Dashboard Load Test
**Status:** ⬜ NOT STARTED

**Test Scenario:**
1. [ ] Navigate to inventory dashboard
2. [ ] Verify displays:
   - [ ] Egg summary (450 total, 200 available, 250 sold)
   - [ ] Livestock summary (85 total, 20 ready)
   - [ ] Supply summary (24 items, 1 out of stock)
   - [ ] Health score (85/100)
   - [ ] Alerts (3 alerts shown)
   - [ ] Charts load without error

**Performance:** Should load in < 2 seconds

**Pass/Fail:** ___

---

## Priority Order

**🔴 CRITICAL (Do First):**
1. 1.1 - Egg grading in FlockRecord
2. 1.2 - Vaccine consumption tracking
3. 1.4 - Order checkout inventory deduction
4. 2.1 - Dashboard overview
5. 5.1 - Egg grading test

**🟡 HIGH (Do Second):**
6. 1.3 - Feed consumption tracking
7. 3.1 - Grading review modal
8. 2.2 - Dashboard view
9. 5.2 - Deduction test

**🟢 MEDIUM (Do Third):**
10. 1.5 - Livestock controller
11. 3.2 - Add livestock form
12. 3.3 - Product form modifications
13. 4.1 - Freshness updater
14. 5.3 - Supply test

**🔵 LOWER (Do Last):**
15. 4.2 - Livestock updater
16. 4.3 - Alert sender
17. 5.4 - Livestock test
18. 5.5 - Dashboard test

---

## Progress Tracking

| Phase | Task | Status | Time | Completed |
|-------|------|--------|------|-----------|
| 1 | 1.1 Flask Record | ⬜ | 1h | |
| 1 | 1.2 Vaccination | ⬜ | 0.5h | |
| 1 | 1.3 Feed | ⬜ | 1h | |
| 1 | 1.4 Checkout | ⬜ | 1.5h | |
| 1 | 1.5 Livestock | ⬜ | 1h | |
| 2 | 2.1 Dashboard Controller | ⬜ | 1h | |
| 2 | 2.2 Dashboard View | ⬜ | 2h | |
| 3 | 3.1 Egg Log Form | ⬜ | 1.5h | |
| 3 | 3.2 Livestock Form | ⬜ | 1.5h | |
| 3 | 3.3 Product Form | ⬜ | 1.5h | |
| 4 | 4.1 Freshness Command | ⬜ | 0.5h | |
| 4 | 4.2 Readiness Command | ⬜ | 0.5h | |
| 4 | 4.3 Alert Command | ⬜ | 1h | |
| 5 | 5.1 Test Grading | ⬜ | 0.5h | |
| 5 | 5.2 Test Deduction | ⬜ | 0.5h | |
| 5 | 5.3 Test Supply | ⬜ | 0.5h | |
| 5 | 5.4 Test Livestock | ⬜ | 0.5h | |
| 5 | 5.5 Test Dashboard | ⬜ | 0.5h | |

**Total Estimated Time:** 17.5 hours
**Priority:** Focus on Phase 1 (Controllers) first - they unlock all other phases

---

## Files Ready to Reference

✅ `INVENTORY_SYSTEM_ARCHITECTURE.md` - Database & service architecture
✅ `INVENTORY_SYSTEM_INTEGRATION.md` - Detailed integration guide with examples
✅ `app/Services/EggGradingService.php` - Auto-grading service
✅ `app/Services/LivestockInventoryService.php` - Livestock tracking service
✅ `app/Services/SupplyConsumptionService.php` - Supply deduction service
✅ `app/Services/InventoryDashboardService.php` - Dashboard aggregation service
