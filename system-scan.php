<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== SYSTEM SCAN ===\n\n";

echo "--- All Users in System ---\n";
$users = \App\Models\User::all();
if ($users->count() === 0) {
    echo "NO USERS FOUND!\n";
} else {
    foreach ($users as $user) {
        echo "ID: {$user->id} | Email: {$user->email} | Name: {$user->name}\n";
    }
}

echo "\n--- All FarmOwners in System ---\n";
$farmOwners = \App\Models\FarmOwner::all();
if ($farmOwners->count() === 0) {
    echo "NO FARMOWNERS FOUND!\n";
} else {
    foreach ($farmOwners as $fo) {
        echo "ID: {$fo->id} | UserID: {$fo->user_id} | Farm: {$fo->farm_name}\n";
    }
}

echo "\n--- All Subscriptions in System ---\n";
$subscriptions = \App\Models\Subscription::all();
if ($subscriptions->count() === 0) {
    echo "NO SUBSCRIPTIONS FOUND!\n";
} else {
    foreach ($subscriptions as $sub) {
        echo "ID: {$sub->id} | FarmOwnerID: {$sub->farm_owner_id} | Plan: {$sub->plan_type} | Status: {$sub->status}\n";
    }
}

// Find the logged-in user from the request context
echo "\n--- Checking from HTTP Request Context ---\n";
if (function_exists('auth')) {
    $currentUser = auth()->user();
    if ($currentUser) {
        echo "Logged in as: {$currentUser->email} (ID: {$currentUser->id})\n";
        $fo = \App\Models\FarmOwner::where('user_id', $currentUser->id)->first();
        if ($fo) {
            echo "Farm Owner: {$fo->farm_name} (ID: {$fo->id})\n";
            $subs = $fo->subscriptions()->get();
            echo "Subscriptions: " . $subs->count() . "\n";
            foreach ($subs as $sub) {
                echo "  - {$sub->plan_type} | Status: {$sub->status}\n";
            }
        } else {
            echo "No FarmOwner found!\n";
        }
    } else {
        echo "No user logged in\n";
    }
}
