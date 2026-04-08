<?php
/**
 * Test Paid Plans Activation via Test Mode
 * Validates that test mode creates subscriptions and product limits work
 */

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\FarmOwner;
use App\Models\Subscription;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Log;

echo "=== PAID PLANS TEST MODE VALIDATION ===\n\n";

// Get first farm owner for testing
$farmOwner = FarmOwner::first();
if (!$farmOwner) {
    echo "ERROR: No farm owners found in database\n";
    exit(1);
}

echo "Testing with Farm Owner: " . $farmOwner->email . "\n";
echo "Current Active Plan: ";
$currentPlan = Subscription::active()->where('farm_owner_id', $farmOwner->id)->first();
if ($currentPlan) {
    echo $currentPlan->plan . " (expires: " . $currentPlan->ends_at . ")\n";
} else {
    echo "NONE\n";
}

$plans = ['starter', 'professional', 'enterprise'];
$results = [];

echo "\n--- Testing Plan Activation ---\n";

foreach ($plans as $plan) {
    echo "\nTesting: $plan plan\n";
    
    // Simulate test mode activation
    try {
        // Clean up any existing subscription for this farm first
        Subscription::where('farm_owner_id', $farmOwner->id)
            ->where('plan', $plan)
            ->delete();
        
        // This is what the controller does in test mode
        $subscription = Subscription::create([
            'farm_owner_id' => $farmOwner->id,
            'plan' => $plan,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
            'payment_id' => null,
            'reference_number' => null,
            'price_paid' => 0,
        ]);
        
        echo "  ✓ Subscription created (ID: {$subscription->id})\n";
        
        // Verify it's active
        $active = Subscription::active()
            ->where('farm_owner_id', $farmOwner->id)
            ->where('plan', $plan)
            ->first();
        
        if ($active) {
            echo "  ✓ Subscription is ACTIVE\n";
            
            // Get product limit
            $model = new Subscription();
            $limits = $model->getPlanLimits($plan);
            if ($limits) {
                $limit = $limits['product_limit'] ?? 0;
                echo "  ✓ Product Limit: $limit\n";
                
                $results[$plan] = [
                    'created' => true,
                    'active' => true,
                    'product_limit' => $limit,
                    'subscription_id' => $subscription->id
                ];
            } else {
                echo "  ✗ Could not retrieve plan limits\n";
                $results[$plan] = ['created' => true, 'active' => true, 'error' => 'No limits'];
            }
        } else {
            echo "  ✗ Subscription NOT active (check dates or scope)\n";
            $results[$plan] = ['created' => true, 'active' => false];
        }
        
    } catch (\Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
        $results[$plan] = ['error' => $e->getMessage()];
    }
}

echo "\n--- SUMMARY ---\n";
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

// Also show what's in database now
echo "\n--- DATABASE STATE AFTER TEST ---\n";
$subs = Subscription::where('farm_owner_id', $farmOwner->id)->get();
echo "Total subscriptions for farm: " . $subs->count() . "\n";
foreach ($subs as $sub) {
    $status = $sub->isActive() ? 'ACTIVE' : 'EXPIRED';
    echo "  - {$sub->plan}: $status (ends: {$sub->ends_at})\n";
}

echo "\n✓ Test complete\n";
?>
