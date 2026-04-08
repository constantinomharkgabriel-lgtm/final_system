# 🧪 Inventory System Testing Guide - Step by Step

## Prerequisites
Make sure you have:
- ✅ Laravel running on `http://localhost:8000`
- ✅ Vite running on `http://localhost:5173`
- ✅ All migrations executed (`php artisan migrate`)
- ✅ At least one farm owner account created

---

## Step-by-Step Testing

### **STEP 1: Test Egg Grading Service** (5 minutes)

**What it tests:** Auto-grading eggs from daily collections, creating inventory entries, auto-pricing products

**Go to:**
```
http://localhost:8000/test-inventory/egg-grading
```

**What you'll see:**
- ✅ Creates test flock (Araucana layers)
- ✅ Calls EggGradingService with 80 eggs
- ✅ Displays 6 created EggInventory entries (Grade A/B/C × Large/Small)
- ✅ Shows auto-created Products with correct pricing
- ✅ Shows InventoryTransaction audit log

**Look for:**
```
✓ Created EggCollection ID: 1
✓ Batch ID: FO-1-1-1712880000
✓ Eggs Collected: 80
✓ Eggs Broken: 5

Created EggInventory Entries:
Type     | Grade | Size  | Quantity | Expires    | Product ID
---------|-------|-------|----------|--------|-------------
Organic  | A     | Large | 45       | 2026-04-21 | 142
Organic  | A     | Small | 19       | 2026-04-21 | 143
Organic  | B     | Large | 8        | 2026-04-17 | 144
Organic  | B     | Small | 3        | 2026-04-17 | 145
Organic  | C     | Large | 4        | 2026-04-14 | 146
Organic  | C     | Small | 2        | 2026-04-14 | 147

Created Products with Auto-Pricing:
Product Name                    | Price    | Qty Available
--------------------------------|----------|---------------
Organic Grade A Large Eggs      | ₱16.18   | 45
Organic Grade A Small Eggs      | ₱12.13   | 19
Organic Grade B Large Eggs      | ₱12.58   | 8
Organic Grade B Small Eggs      | ₱9.43    | 3
Organic Grade C Large Eggs      | ₱8.98    | 4
Organic Grade C Small Eggs      | ₱6.74    | 2

✅ EGG GRADING TEST PASSED
```

**If something's wrong:**
- ❌ No farm owner found? Create one first in the app
- ❌ No flock created? Test creates it automatically, or create one manually
- ❌ Wrong egg type? Check breed_type in flock

---

### **STEP 2: Test Livestock Inventory Service** (3 minutes)

**What it tests:** Creating livestock inventory, calculating weeks until ready, auto-product creation

**Go to:**
```
http://localhost:8000/test-inventory/livestock
```

**What you'll see:**
- ✅ Creates test broiler flock (age 6 weeks)
- ✅ Creates LivestockInventory for 98 birds
- ✅ Calculates "2 weeks until ready" (broilers ready at 8 weeks)
- ✅ Shows status: "growing"
- ✅ Estimated ready date: 2 weeks from now

**Look for:**
```
✓ Created LivestockInventory ID: 1
✓ Type: Broiler
✓ Quantity Available: 98
✓ Age: 6 weeks
✓ Status: Growing
✓ Weeks Until Ready: 2
✓ Estimated Ready Date: 2026-04-21

Ready Age Charts:
- Broiler: 8 weeks (ready in 2 weeks) ✓
- Layer: 16-20 weeks
- Breeder: 20 weeks
- Fighting Cock: 24 weeks
- Native: 16 weeks
- Duck: 10 weeks
- Quail: 6 weeks
```

**If something's wrong:**
- ❌ Wrong weeks until ready? Check the breed mapping
- ❌ Status not showing? Refresh the page

---

### **STEP 3: Test Inventory Dashboard Service** (3 minutes)

**What it tests:** Aggregating all inventory data, calculating health score, generating alerts

**Go to:**
```
http://localhost:8000/test-inventory/dashboard
```

**What you'll see:**
- ✅ Total egg inventory summary
- ✅ By egg type breakdown (organic/brown/white)
- ✅ Livestock summary with breakdown by type
- ✅ Supply inventory summary
- ✅ Active alerts (if any)
- ✅ Health score 0-100

**Look for:**
```
📊 EGG INVENTORY SUMMARY:
Total Quantity: 81
Available: 45
Sold: 0
Expired: 0
Inventory Value: ₱2,850.00

EGG TYPES:
Type    | Total | Available | Sold | Revenue
--------|-------|-----------|------|--------
Organic | 81    | 45        | 0    | ₱0.00
Brown   | 0     | 0         | 0    | ₱0.00
White   | 0     | 0         | 0    | ₱0.00

🐔 LIVESTOCK INVENTORY SUMMARY:
Total Inventory: 98
Ready for Sale: 0
Growing: 98
Total Sold: 0

⚠️ ALERTS:
✓ No alerts - inventory is healthy!

📈 HEALTH SCORE:
85/100
✅ Excellent inventory health
```

**If something's wrong:**
- ❌ Health score is low? Might have expired eggs or out of stock supplies
- ❌ High alert count? Fix the issues mentioned in alerts

---

### **STEP 4: Test Inventory Deduction (Order Simulation)** (2 minutes)

**What it tests:** Deducting inventory when customer places order, creating transaction records

**Go to:**
```
http://localhost:8000/test-inventory/deduction
```

**What you'll see:**
- ✅ Finds fresh eggs in inventory
- ✅ Simulates customer order for 20 eggs
- ✅ Updates quantity_available and quantity_sold
- ✅ Creates InventoryTransaction record

**Look for:**
```
Found egg inventory:
Type: Organic
Grade: A
Size: Large
Current Available: 45

Simulating customer order for 20 eggs...

After deduction:
Available Before: 45
Quantity Sold: 20
Available After: 25
Total Sold: 20

InventoryTransaction Created:
Date: 2026-04-07
Type: sale
Quantity: 20
Total Amount: ₱323.60
Recorded By: 1

✅ INVENTORY DEDUCTION TEST PASSED
```

**If something's wrong:**
- ❌ No fresh eggs? Run STEP 1 first
- ❌ Quantity not decreasing? Check database permissions

---

### **STEP 5: Run All Tests at Once** (13 minutes)

**Go to:**
```
http://localhost:8000/test-inventory/all
```

**This runs all 4 tests above and shows:**
- Complete summary of egg grading
- Complete summary of livestock tracking
- Complete summary of dashboard
- Complete summary of order deduction

---

## Verification Checklist

After running tests, verify in the database (or view the results):

### ✅ Egg Grading Test
- [ ] `egg_collections` table has 1 new record
- [ ] `egg_inventory` table has 6 new records (3 grades × 2 sizes)
- [ ] `products` table has 6 new records with prices
- [ ] `inventory_transactions` table has 6 new "collection" records

**SQL to verify:**
```sql
-- Check egg collections
SELECT COUNT(*) as collections FROM egg_collections WHERE farm_owner_id = 1;
-- Should see: 1

-- Check inventory entries
SELECT COUNT(*) as inventories FROM egg_inventory WHERE farm_owner_id = 1;
-- Should see: 6 (or more if you ran test multiple times)

-- Check products
SELECT COUNT(*) as products FROM products WHERE farm_owner_id = 1 AND egg_inventory_id IS NOT NULL;
-- Should see: 6 (or more)

-- Check pricing
SELECT name, price FROM products WHERE farm_owner_id = 1 AND egg_inventory_id IS NOT NULL LIMIT 1;
-- Should see prices like ₱16.18, ₱12.13, etc.
```

### ✅ Livestock Test
- [ ] `livestock_inventory` table has records
- [ ] Status = "growing" for broilers under 8 weeks
- [ ] weeks_until_ready calculated correctly
- [ ] estimated_ready_date is future date

**SQL to verify:**
```sql
-- Check livestock inventory
SELECT COUNT(*) as livestock FROM livestock_inventory WHERE farm_owner_id = 1;

-- Check weeks calculation
SELECT livestock_type, age_weeks, weeks_until_ready, status 
FROM livestock_inventory 
WHERE farm_owner_id = 1;
-- Should see correct readiness for each type
```

### ✅ Deduction Test
- [ ] `egg_inventory` quantity_available decreased by 20
- [ ] quantity_sold increased by 20
- [ ] `inventory_transactions` has "sale" record

**SQL to verify:**
```sql
-- Check inventory deduction
SELECT quantity_available, quantity_sold 
FROM egg_inventory 
WHERE farm_owner_id = 1 
ORDER BY created_at DESC 
LIMIT 1;
-- Should see: quantity_available decreased, quantity_sold increased

-- Check transaction
SELECT transaction_type, quantity, total_amount 
FROM inventory_transactions 
WHERE farm_owner_id = 1 
AND transaction_type = 'sale'
ORDER BY created_at DESC 
LIMIT 1;
-- Should see sale transaction
```

---

## Common Issues & Solutions

### ❌ "No farm owner found"
**Solution:** Create a farm owner account first
1. Login as superadmin (or create farm owner manually)
2. Create farm owner record
3. Retry test

### ❌ "No flock created"
**Solution:** Test auto-creates flocks, but you can manually create:
1. Login as farm owner
2. Go to Flocks → Create
3. Fill in details
4. Save
5. Retry test

### ❌ "Error: Class not found"
**Solution:** Ensure migrations ran
```bash
cd c:\Users\lawrence tabutol\Downloads\Final_system\poultry-system
php artisan migrate
```

### ❌ "Wrong prices appearing"
**Solution:** Check breed_type mapping in EggGradingService
- Araucana, Ameraucana, Orpington, Sussex, Wyandotte, Java = **Organic** (1.5x)
- Rhode Island Red, New Hampshire, Buff Orpington = **Brown** (1.2x)
- Others = **White** (1.0x)

### ❌ "Weeks until ready is wrong"
**Solution:** Check livestock type ready ages:
- Broiler: 8 weeks
- Layer: 16-20 weeks
- Breeder: 20 weeks
- Fighting Cock: 24 weeks
- Native: 16 weeks
- Duck: 10 weeks
- Quail: 6 weeks

---

## Expected Results Summary

| Test | Input | Expected Output |
|------|-------|-----------------|
| **Egg Grading** | 80 eggs collected | 6 inventory entries + 6 products |
| **Livestock** | 98 broilers, age 6w | Status: Growing, Ready in 2 weeks |
| **Dashboard** | All inventory | Health score 80+, no alerts |
| **Deduction** | Order 20 eggs | Qty available: 45→25, sold: 0→20 |

---

## Next Steps (After Testing)

Once all tests pass:
1. ✅ Integrate EggGradingService into FlockRecordController
2. ✅ Update daily record form with grading review modal
3. ✅ Create livestock entry form
4. ✅ Build inventory dashboard (UI)
5. ✅ Update product form for inventory linking
6. ✅ Implement order checkout deduction

**See INVENTORY_IMPLEMENTATION_TASKS.md for detailed integration steps**

---

## Questions?

If tests fail or you need clarification:
1. Check error messages in test output
2. Verify database has data (check SQL queries above)
3. Check Laravel logs: `storage/logs/laravel.log`
4. Try refreshing browser and retrying test
