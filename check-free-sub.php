<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('email', 'farmowner@test.com')->first();
echo "=== FREE SUBSCRIPTION CHECK ===\n\n";

if (!$user) {
    echo "ERROR: User 'farmowner@test.com' not found!\n";
    exit;
}

echo "✓ User found: {$user->email} (ID: {$user->id})\n";

$farmOwner = \App\Models\FarmOwner::where('user_id', $user->id)->first();
if (!$farmOwner) {
    echo "ERROR: FarmOwner not found for this user!\n";
    exit;
}

echo "✓ FarmOwner found (ID: {$farmOwner->id})\n\n";

echo "--- All Subscriptions for FarmOwner ---\n";
$subs = $farmOwner->subscriptions()->get();
if ($subs->count() === 0) {
    echo "NO SUBSCRIPTIONS FOUND!\n";
} else {
    foreach ($subs as $sub) {
        echo "ID: {$sub->id} | Plan: {$sub->plan_type} | Status: {$sub->status} | Ends: {$sub->ends_at}\n";
    }
}

echo "\n--- Free Subscription Check ---\n";
$hasFree = $farmOwner->subscriptions()->where('plan_type', 'free')->exists();
echo "Has Free Subscription: " . ($hasFree ? "YES ✓" : "NO ✗") . "\n";

if ($hasFree) {
    $freeSub = $farmOwner->subscriptions()->where('plan_type', 'free')->first();
    echo "\nFree Subscription Details:\n";
    echo "  ID: {$freeSub->id}\n";
    echo "  Status: {$freeSub->status}\n";
    echo "  Created: {$freeSub->created_at}\n";
    echo "  Ends: {$freeSub->ends_at}\n";
    echo "  Is Active: " . ($freeSub->isActive() ? "YES" : "NO") . "\n";
}
