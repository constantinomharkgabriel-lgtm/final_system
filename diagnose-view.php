<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\FarmOwner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     SUBSCRIPTION VIEW DIAGNOSTIC - EXACT SIMULATION        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Simulate exactly what happens in the controller when page loads
$user = User::find(2);

if (!$user) {
    echo "❌ User not found!\n";
    exit;
}

echo "User: {$user->email} (ID: {$user->id})\n";

$farmOwner = FarmOwner::where('user_id', $user->id)->first();

if (!$farmOwner) {
    echo "❌ FarmOwner not found!\n";
    exit;
}

echo "FarmOwner: {$farmOwner->farm_name} (ID: {$farmOwner->id})\n\n";

// This is EXACTLY what the controller does
echo "=== Simulating Controller Logic ===\n";
echo "Step 1: Get all subscriptions\n";

$allSubscriptions = $farmOwner->subscriptions()->get();
echo "Count: {$allSubscriptions->count()}\n";
foreach ($allSubscriptions as $sub) {
    echo "  - ID:{$sub->id} | Plan:{$sub->plan_type} | Status:{$sub->status} | Ends:{$sub->ends_at} | Deleted:{$sub->deleted_at}\n";
}

echo "\nStep 2: Check for free subscription using exists()\n";
$hasFreeSubscription = $farmOwner->subscriptions()
    ->where('plan_type', 'free')
    ->exists();
echo "hasFreeSubscription = " . ($hasFreeSubscription ? 'TRUE ✓' : 'FALSE ✗') . "\n";

// Debug: Let's also try without the exists() to see what it returns
echo "\nStep 3: Get free subscription (if it exists)\n";
$freeSub = $farmOwner->subscriptions()
    ->where('plan_type', 'free')
    ->first();

if ($freeSub) {
    echo "Found FREE subscription:\n";
    echo "  ID: {$freeSub->id}\n";
    echo "  Plan: {$freeSub->plan_type}\n";
    echo "  Status: {$freeSub->status}\n";
    echo "  Ends: {$freeSub->ends_at}\n";
    echo "  Deleted: {$freeSub->deleted_at}\n";
} else {
    echo "❌ No free subscription found!\n";
}

echo "\nStep 4: Get active subscription\n";
$activeSubscription = $farmOwner->subscriptions()
    ->where('status', 'active')
    ->where('ends_at', '>', now())
    ->latest()
    ->first();

if ($activeSubscription) {
    echo "Found ACTIVE subscription:\n";
    echo "  ID: {$activeSubscription->id}\n";
    echo "  Plan: {$activeSubscription->plan_type}\n";
    echo "  Status: {$activeSubscription->status}\n";
    echo "  Ends: {$activeSubscription->ends_at}\n";
} else {
    echo "❌ No active subscription found!\n";
}

echo "\n=== Variables Passed to View ===\n";
echo "\$hasFreeSubscription = " . ($hasFreeSubscription ? 'TRUE' : 'FALSE') . "\n";
echo "\$activeSubscription = " . ($activeSubscription ? "ID:{$activeSubscription->id}" : 'null') . "\n";
echo "\$farmOwner = ID:{$farmOwner->id}\n";

echo "\n=== View Blade Logic ===\n";
$key = 'free';
$userHasFreeSubscription = $hasFreeSubscription ?? false;
$isFreePlanSubscribed = $userHasFreeSubscription && $key === 'free';

echo "foreach plan as \$key => \$plan:\n";
echo "  When \$key = 'free':\n";
echo "    \$userHasFreeSubscription = " . ($userHasFreeSubscription ? 'TRUE' : 'FALSE') . "\n";
echo "    \$isFreePlanSubscribed = " . ($isFreePlanSubscribed ? 'TRUE' : 'FALSE') . "\n";

echo "\n=== EXPECTED VIEW OUTPUT ===\n";
if ($isFreePlanSubscribed) {
    echo "✓ FREE PLAN CARD SHOULD SHOW:\n";
    echo "  - Button: 'Already Active' (DISABLED, GREY)\n";
    echo "  - Badge: '✓ Current Plan' (GREEN)\n";
    echo "  - Status: NOT CLICKABLE\n";
} else {
    echo "✗ FREE PLAN CARD SHOWS:\n";
    echo "  - Button: 'Start Free Trial' (CLICKABLE, GREEN)\n";
    echo "  - Badge: 'Free Trial' (GREY)\n";
    echo "  - Status: CLICKABLE\n";
}

echo "\n✓ Diagnostic complete!\n";
