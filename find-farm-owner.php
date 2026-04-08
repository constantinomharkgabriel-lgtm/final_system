<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\FarmOwner;
use App\Models\Payroll;

echo "=== AVAILABLE FARM OWNER ACCOUNTS ===\n\n";

$farmOwners = User::where('role', 'farm_owner')->get();

if ($farmOwners->isEmpty()) {
    echo "❌ No farm owner accounts found\n";
} else {
    foreach ($farmOwners as $owner) {
        echo "Farm Owner: " . $owner->name . "\n";
        echo "Email: " . $owner->email . "\n";
        
        // Get farm owner details
        $farm = FarmOwner::where('user_id', $owner->id)->first();
        if ($farm) {
            echo "Farm ID: " . $farm->id . "\n";
        }
        echo "\n";
    }
}

// Check payroll association
echo "=== PAYROLL CHECK ===\n";
$payroll = Payroll::find(3);
echo "Payroll: " . $payroll->payroll_period . "\n";
echo "Farm Owner ID: " . $payroll->farm_owner_id . "\n";

$owner = FarmOwner::find($payroll->farm_owner_id);
if ($owner) {
    $user = User::find($owner->user_id);
    if ($user) {
        echo "Associated Farm Owner: " . $user->name . "\n";
        echo "Email: " . $user->email . "\n";
    }
}
?>
