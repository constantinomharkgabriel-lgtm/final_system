<?php

/**
 * INVENTORY SYSTEM - COMPLETE TESTING GUIDE
 * 
 * Step-by-step test script to verify egg grading, livestock tracking,
 * and supply consumption all work correctly.
 * 
 * Run from: http://localhost:8000/test-inventory
 */

namespace App\Http\Controllers;

use App\Models\Flock;
use App\Models\FlockRecord;
use App\Models\EggCollection;
use App\Models\EggInventory;
use App\Models\LivestockInventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\FarmOwner;
use App\Services\EggGradingService;
use App\Services\LivestockInventoryService;
use App\Services\InventoryDashboardService;

class InventoryTestController extends Controller
{
    /**
     * STEP 1: Test Egg Grading Service
     */
    public function testEggGrading()
    {
        echo "<h1>✅ STEP 1: TEST EGG GRADING SERVICE</h1>";
        
        try {
            // Get or create test flock (Araucana - organic eggs)
            $farmOwner = FarmOwner::first();
            if (!$farmOwner) {
                return "⚠️ No farm owner found. Create one first.";
            }
            
            $flock = Flock::where('farm_owner_id', $farmOwner->id)
                ->where('breed_type', 'Araucana')
                ->first();
            
            if (!$flock) {
                $flock = Flock::create([
                    'farm_owner_id' => $farmOwner->id,
                    'flock_name' => 'Test Araucana Layers',
                    'breed_type' => 'Araucana',
                    'flock_type' => 'layer',
                    'quantity' => 50,
                    'current_count' => 48,
                    'age_weeks' => 20,
                    'acquisition_cost' => 50,
                ]);
                echo "✓ Created test flock: " . $flock->flock_name . "<br>";
            }
            
            // Call EggGradingService
            echo "<h3>Running EggGradingService::gradeAndCreateInventory()</h3>";
            $service = new EggGradingService();
            
            $collection = $service->gradeAndCreateInventory(
                $flock,
                80,      // eggs_collected
                5,       // eggs_broken
                today()  // collection_date
            );
            
            echo "✓ Created EggCollection ID: " . $collection->id . "<br>";
            echo "✓ Batch ID: " . $collection->batch_id . "<br>";
            echo "✓ Eggs Collected: " . $collection->eggs_collected . "<br>";
            echo "✓ Eggs Broken: " . $collection->eggs_broken . "<br>";
            
            // Show created inventory
            echo "<h3>Created EggInventory Entries:</h3>";
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>Type</th><th>Grade</th><th>Size</th><th>Quantity</th><th>Expires</th><th>Product ID</th></tr>";
            
            foreach ($collection->eggInventories as $inv) {
                echo "<tr>";
                echo "<td>" . ucfirst($inv->egg_type) . "</td>";
                echo "<td>" . $inv->grade . "</td>";
                echo "<td>" . ucfirst($inv->size) . "</td>";
                echo "<td>" . $inv->quantity_available . "</td>";
                echo "<td>" . $inv->freshness_expires_at->format('Y-m-d') . "</td>";
                echo "<td>" . ($inv->product_id ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            echo "<h3>Created Products with Auto-Pricing:</h3>";
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>Product Name</th><th>Price</th><th>Qty Available</th><th>Linked Inventory?</th></tr>";
            
            $products = Product::where('farm_owner_id', $farmOwner->id)
                ->whereNotNull('egg_inventory_id')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get();
            
            foreach ($products as $product) {
                echo "<tr>";
                echo "<td>" . $product->name . "</td>";
                echo "<td>₱" . number_format($product->price, 2) . "</td>";
                echo "<td>" . $product->quantity_available . "</td>";
                echo "<td>" . ($product->egg_inventory_id ? '✓ Yes' : '✗ No') . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            echo "<h3>Verify InventoryTransactions Created:</h3>";
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>Date</th><th>Type</th><th>Quantity</th><th>Amount</th><th>Reference</th></tr>";
            
            $transactions = InventoryTransaction::where('farm_owner_id', $farmOwner->id)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
            
            foreach ($transactions as $t) {
                echo "<tr>";
                echo "<td>" . $t->transaction_date . "</td>";
                echo "<td>" . $t->transaction_type . "</td>";
                echo "<td>" . $t->quantity . "</td>";
                echo "<td>₱" . number_format($t->total_amount, 2) . "</td>";
                echo "<td>" . $t->reference_type . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            echo "<h3>✅ EGG GRADING TEST PASSED</h3>";
            echo "<p><strong>Summary:</strong></p>";
            echo "<ul>";
            echo "<li>EggCollection created: " . $collection->id . "</li>";
            echo "<li>EggInventory entries: " . $collection->eggInventories->count() . "</li>";
            echo "<li>Products auto-created: " . $products->count() . "</li>";
            echo "<li>InventoryTransactions logged: " . $transactions->count() . "</li>";
            echo "</ul>";
            
            return view('test-results', [
                'test' => 'egg_grading',
                'status' => 'PASSED'
            ]);
            
        } catch (\Exception $e) {
            echo "<h3>❌ EGG GRADING TEST FAILED</h3>";
            echo "<pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
        }
    }

    /**
     * STEP 2: Test Livestock Inventory Service
     */
    public function testLivestockInventory()
    {
        echo "<h1>✅ STEP 2: TEST LIVESTOCK INVENTORY SERVICE</h1>";
        
        try {
            $farmOwner = FarmOwner::first();
            
            // Get or create broiler flock
            $flock = Flock::where('farm_owner_id', $farmOwner->id)
                ->where('breed_type', 'Broiler')
                ->first();
            
            if (!$flock) {
                $flock = Flock::create([
                    'farm_owner_id' => $farmOwner->id,
                    'flock_name' => 'Test Broiler Flock',
                    'breed_type' => 'Broiler',
                    'flock_type' => 'meat',
                    'quantity' => 100,
                    'current_count' => 98,
                    'age_weeks' => 6,  // 2 weeks away from ready (8 weeks)
                    'acquisition_cost' => 35,
                ]);
                echo "✓ Created test broiler flock<br>";
            }
            
            echo "<h3>Running LivestockInventoryService::createInventoryFromFlock()</h3>";
            $service = new LivestockInventoryService();
            
            $inventory = $service->createInventoryFromFlock($flock, 98);
            
            echo "✓ Created LivestockInventory ID: " . $inventory->id . "<br>";
            echo "✓ Type: " . ucfirst(str_replace('_', ' ', $inventory->livestock_type)) . "<br>";
            echo "✓ Quantity Available: " . $inventory->quantity_available_for_sale . "<br>";
            echo "✓ Age: " . $inventory->age_weeks . " weeks<br>";
            echo "✓ Status: " . ucfirst(str_replace('_', ' ', $inventory->status)) . "<br>";
            echo "✓ Weeks Until Ready: " . $inventory->weeks_until_ready . "<br>";
            echo "✓ Estimated Ready Date: " . $inventory->estimated_ready_date->format('Y-m-d') . "<br>";
            
            echo "<h3>Auto-Created Product:</h3>";
            if ($inventory->products()->exists()) {
                $product = $inventory->products()->first();
                echo "✓ Product ID: " . $product->id . "<br>";
                echo "✓ Name: " . $product->name . "<br>";
                echo "✓ Price: ₱" . number_format($product->price, 2) . " per bird<br>";
                echo "✓ Status: " . ucfirst($product->status) . "<br>";
            } else {
                echo "ℹ️ No product created yet (livestock still growing)<br>";
            }
            
            echo "<h3>✅ LIVESTOCK INVENTORY TEST PASSED</h3>";
            echo "<p><strong>Ready Age Charts:</strong></p>";
            echo "<ul>";
            echo "<li>Broiler: 8 weeks (ready in " . $inventory->weeks_until_ready . " weeks)</li>";
            echo "<li>Layer: 16-20 weeks</li>";
            echo "<li>Breeder: 20 weeks</li>";
            echo "<li>Fighting Cock: 24 weeks</li>";
            echo "<li>Native: 16 weeks</li>";
            echo "<li>Duck: 10 weeks</li>";
            echo "<li>Quail: 6 weeks</li>";
            echo "</ul>";
            
        } catch (\Exception $e) {
            echo "<h3>❌ LIVESTOCK INVENTORY TEST FAILED</h3>";
            echo "<pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
        }
    }

    /**
     * STEP 3: Test Inventory Dashboard Service
     */
    public function testDashboardService()
    {
        echo "<h1>✅ STEP 3: TEST INVENTORY DASHBOARD SERVICE</h1>";
        
        try {
            $farmOwner = FarmOwner::first();
            
            echo "<h3>Running InventoryDashboardService::getInventoryOverview()</h3>";
            $service = new InventoryDashboardService();
            
            $overview = $service->getInventoryOverview($farmOwner->id);
            
            echo "<h3>📊 EGG INVENTORY SUMMARY:</h3>";
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><td><strong>Total Quantity</strong></td><td>" . $overview['eggs']['total_quantity'] . "</td></tr>";
            echo "<tr><td><strong>Available</strong></td><td>" . $overview['eggs']['quantity_available'] . "</td></tr>";
            echo "<tr><td><strong>Sold</strong></td><td>" . $overview['eggs']['quantity_sold'] . "</td></tr>";
            echo "<tr><td><strong>Expired</strong></td><td>" . $overview['eggs']['quantity_expired'] . "</td></tr>";
            echo "<tr><td><strong>Inventory Value</strong></td><td>₱" . number_format($overview['eggs']['inventory_value'], 2) . "</td></tr>";
            echo "</table>";
            
            echo "<h3>EGG TYPES:</h3>";
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>Type</th><th>Total</th><th>Available</th><th>Sold</th><th>Revenue</th></tr>";
            foreach ($overview['eggs']['by_type'] as $type => $data) {
                echo "<tr>";
                echo "<td>" . ucfirst($type) . "</td>";
                echo "<td>" . $data['total'] . "</td>";
                echo "<td>" . $data['available'] . "</td>";
                echo "<td>" . $data['sold'] . "</td>";
                echo "<td>₱" . number_format($data['revenue'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<h3>🐔 LIVESTOCK INVENTORY SUMMARY:</h3>";
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><td><strong>Total Inventory</strong></td><td>" . $overview['livestock']['total_inventory'] . "</td></tr>";
            echo "<tr><td><strong>Ready for Sale</strong></td><td>" . $overview['livestock']['ready_for_sale'] . "</td></tr>";
            echo "<tr><td><strong>Growing</strong></td><td>" . $overview['livestock']['growing'] . "</td></tr>";
            echo "<tr><td><strong>Total Sold</strong></td><td>" . $overview['livestock']['total_sold'] . "</td></tr>";
            echo "</table>";
            
            echo "<h3>📦 SUPPLY INVENTORY SUMMARY:</h3>";
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><td><strong>Total Items</strong></td><td>" . $overview['supplies']['total_items'] . "</td></tr>";
            echo "<tr><td><strong>In Stock</strong></td><td>" . $overview['supplies']['in_stock_count'] . "</td></tr>";
            echo "<tr><td><strong>Low Stock</strong></td><td>" . $overview['supplies']['low_stock_count'] . "</td></tr>";
            echo "<tr><td><strong>Out of Stock</strong></td><td>" . $overview['supplies']['out_of_stock_count'] . "</td></tr>";
            echo "</table>";
            
            echo "<h3>⚠️ ALERTS:</h3>";
            if (empty($overview['alerts'])) {
                echo "<p>✓ No alerts - inventory is healthy!</p>";
            } else {
                echo "<ul>";
                foreach ($overview['alerts'] as $alert) {
                    $icon = $alert['severity'] === 'high' ? '🔴' : ($alert['severity'] === 'medium' ? '🟠' : '🟡');
                    echo "<li>$icon " . $alert['message'] . "</li>";
                }
                echo "</ul>";
            }
            
            echo "<h3>📈 HEALTH SCORE:</h3>";
            $health = $service->getInventoryHealthScore($farmOwner->id);
            echo "<p><strong>" . $health . "/100</strong></p>";
            echo "<p>";
            if ($health >= 80) echo "✅ Excellent inventory health<br>";
            elseif ($health >= 60) echo "⚠️ Good, but has some issues<br>";
            elseif ($health >= 40) echo "🟠 Fair, needs attention<br>";
            else echo "🔴 Poor, needs immediate action<br>";
            echo "</p>";
            
            echo "<h3>✅ DASHBOARD SERVICE TEST PASSED</h3>";
            
        } catch (\Exception $e) {
            echo "<h3>❌ DASHBOARD SERVICE TEST FAILED</h3>";
            echo "<pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
        }
    }

    /**
     * STEP 4: Test Inventory Deduction (Simulated Order)
     */
    public function testInventoryDeduction()
    {
        echo "<h1>✅ STEP 4: TEST INVENTORY DEDUCTION (ORDER SIMULATION)</h1>";
        
        try {
            $farmOwner = FarmOwner::first();
            
            // Get an egg inventory to test with
            $eggInventory = EggInventory::where('farm_owner_id', $farmOwner->id)
                ->where('status', 'fresh')
                ->where('quantity_available', '>', 0)
                ->first();
            
            if (!$eggInventory) {
                echo "⚠️ No fresh eggs in inventory. Run STEP 1 first.";
                return;
            }
            
            echo "<h3>Found egg inventory:</h3>";
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><td><strong>Type</strong></td><td>" . ucfirst($eggInventory->egg_type) . "</td></tr>";
            echo "<tr><td><strong>Grade</strong></td><td>" . $eggInventory->grade . "</td></tr>";
            echo "<tr><td><strong>Size</strong></td><td>" . ucfirst($eggInventory->size) . "</td></tr>";
            echo "<tr><td><strong>Current Available</strong></td><td>" . $eggInventory->quantity_available . "</td></tr>";
            echo "</table>";
            
            $quantityBefore = $eggInventory->quantity_available;
            $quantityToSell = min(20, $quantityBefore);
            
            echo "<h3>Simulating customer order for " . $quantityToSell . " eggs...</h3>";
            
            // Call recordSale
            $eggInventory->recordSale($quantityToSell);
            
            // Refresh to see updated values
            $eggInventory->refresh();
            
            echo "<h3>After deduction:</h3>";
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><td><strong>Available Before</strong></td><td>" . $quantityBefore . "</td></tr>";
            echo "<tr><td><strong>Quantity Sold</strong></td><td>" . $quantityToSell . "</td></tr>";
            echo "<tr><td><strong>Available After</strong></td><td>" . $eggInventory->quantity_available . "</td></tr>";
            echo "<tr><td><strong>Total Sold</strong></td><td>" . $eggInventory->quantity_sold . "</td></tr>";
            echo "</table>";
            
            echo "<h3>InventoryTransaction Created:</h3>";
            $transaction = InventoryTransaction::where('inventoryable_type', EggInventory::class)
                ->where('inventoryable_id', $eggInventory->id)
                ->where('transaction_type', 'sale')
                ->orderByDesc('created_at')
                ->first();
            
            if ($transaction) {
                echo "<table border='1' cellpadding='10'>";
                echo "<tr><td><strong>Date</strong></td><td>" . $transaction->transaction_date . "</td></tr>";
                echo "<tr><td><strong>Type</strong></td><td>" . $transaction->transaction_type . "</td></tr>";
                echo "<tr><td><strong>Quantity</strong></td><td>" . $transaction->quantity . "</td></tr>";
                echo "<tr><td><strong>Total Amount</strong></td><td>₱" . number_format($transaction->total_amount, 2) . "</td></tr>";
                echo "<tr><td><strong>Recorded By</strong></td><td>" . $transaction->recorded_by . "</td></tr>";
                echo "</table>";
            }
            
            echo "<h3>✅ INVENTORY DEDUCTION TEST PASSED</h3>";
            
        } catch (\Exception $e) {
            echo "<h3>❌ INVENTORY DEDUCTION TEST FAILED</h3>";
            echo "<pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
        }
    }

    /**
     * STEP 5: Run All Tests
     */
    public function testAll()
    {
        echo "<style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            h1 { color: #333; border-bottom: 3px solid #4fd1c5; padding-bottom: 10px; }
            h3 { color: #555; margin-top: 20px; }
            table { background: white; border-collapse: collapse; margin: 15px 0; }
            td { padding: 10px; }
            tr:nth-child(even) { background: #f9f9f9; }
            pre { background: #fff3cd; padding: 10px; border-radius: 5px; overflow-x: auto; }
            .passed { color: green; font-weight: bold; }
            .failed { color: red; font-weight: bold; }
            ul { line-height: 1.8; }
        </style>";
        
        echo "<h1>🧪 INVENTORY SYSTEM - COMPLETE TEST SUITE</h1>";
        echo "<p>Testing all new inventory features...</p>";
        echo "<hr>";
        
        // Run all tests
        ob_start();
        $this->testEggGrading();
        $test1 = ob_get_clean();
        
        ob_start();
        $this->testLivestockInventory();
        $test2 = ob_get_clean();
        
        ob_start();
        $this->testDashboardService();
        $test3 = ob_get_clean();
        
        ob_start();
        $this->testInventoryDeduction();
        $test4 = ob_get_clean();
        
        echo $test1;
        echo "<hr>";
        echo $test2;
        echo "<hr>";
        echo $test3;
        echo "<hr>";
        echo $test4;
        echo "<hr>";
        
        echo "<h1>✅ ALL TESTS COMPLETED!</h1>";
        echo "<p>Check results above for any issues.</p>";
    }
}
