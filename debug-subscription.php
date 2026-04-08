<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\FarmOwner;
use App\Models\Subscription;

echo "=== COMPREHENSIVE SUBSCRIPTION SYSTEM DEBUG ===\n\n";

// Get the test user
$user = User::find(2);
if (!$user) {
    echo "ERROR: User ID 2 not found!\n";
    exit;
}

echo "User Info:\n";
echo "  ID: {$user->id}\n";
echo "  Email: {$user->email}\n";
echo "  Name: {$user->name}\n\n";

// Get farm owner
$farmOwner = FarmOwner::where('user_id', $user->id)->first();
if (!$farmOwner) {
    echo "ERROR: FarmOwner not found!\n";
    exit;
}

echo "FarmOwner Info:\n";
echo "  ID: {$farmOwner->id}\n";
echo "  Name: {$farmOwner->farm_name}\n";
echo "  User ID: {$farmOwner->user_id}\n\n";

// Get subscriptions
$allSubs = $farmOwner->subscriptions()->get();
echo "All Subscriptions ({$allSubs->count()}):\n";
foreach ($allSubs as $sub) {
    echo "  - ID: {$sub->id}\n";
    echo "    Plan: {$sub->plan_type}\n";
    echo "    Status: {$sub->status}\n";
    echo "    Created: {$sub->created_at}\n";
    echo "    Ends: {$sub->ends_at}\n";
    echo "    Is Active: " . ($sub->isActive() ? 'YES' : 'NO') . "\n";
}

echo "\n--- Free Subscription Check (Same as Controller) ---\n";

// THIS IS EXACTLY WHAT THE CONTROLLER DOES:
$hasFreeSubscription = $farmOwner->subscriptions()
    ->where('plan_type', 'free')
    ->exists();

echo "hasFreeSubscription = " . ($hasFreeSubscription ? 'TRUE' : 'FALSE') . "\n";

if ($hasFreeSubscription) {
    $freeSub = $farmOwner->subscriptions()->where('plan_type', 'free')->first();
    echo "\nFree Subscription Found:\n";
    echo "  ID: {$freeSub->id}\n";
    echo "  Status: {$freeSub->status}\n";
    echo "  Ends: {$freeSub->ends_at}\n";
    echo "  Is Active: " . ($freeSub->isActive() ? 'YES' : 'NO') . "\n";
    
    // Check what query was used by the controller
    echo "\n--- Query Analysis ---\n";
    $query = $farmOwner->subscriptions()->where('plan_type', 'free');
    echo "Query SQL: " . $query->toSql() . "\n";
    echo "Bindings: " . json_encode($query->getBindings()) . "\n";
}

echo "\n--- Active Subscription Check ---\n";
$activeSubscription = $farmOwner->subscriptions()
    ->where('status', 'active')
    ->where('ends_at', '>', now())
    ->latest()
    ->first();

if ($activeSubscription) {
    echo "Active Subscription Found:\n";
    echo "  ID: {$activeSubscription->id}\n";
    echo "  Plan: {$activeSubscription->plan_type}\n";
    echo "  Ends: {$activeSubscription->ends_at}\n";
} else {
    echo "No active subscription found\n";
}

echo "\n--- View Variable Transmission Simulation ---\n";
echo "Variables that would be passed to view:\n";
echo "  'hasFreeSubscription' => " . ($hasFreeSubscription ? 'true' : 'false') . "\n";
echo "  'activeSubscription' => " . ($activeSubscription ? $activeSubscription->id : 'null') . "\n";
echo "  'farmOwner' => " . ($farmOwner ? $farmOwner->id : 'null') . "\n";

echo "\n✓ Debug complete!\n";
