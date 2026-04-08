<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Employee;
use App\Models\FarmOwner;
use Illuminate\Support\Facades\Auth;

// Simulate Fabian login
$fabian = User::find(5); // Fabian  with ID 5
Auth::login($fabian);

echo "=== SIMULATING FINANCE USER ===\n";
echo "User: " . Auth::user()->name . "\n";
echo "Role: " . Auth::user()->role . "\n";

// Simulate getFarmOwner() logic
$user = Auth::user();

if ($user->isFarmOwner()) {
    echo "Is Farm Owner: YES\n";
    $farmOwner = FarmOwner::where('user_id', $user->id)->first();
} else {
    echo "Is Farm Owner: NO - Looking for employee record\n";
    $employee = Employee::where('user_id', $user->id)->first();
    
    if ($employee && $employee->farm_owner_id) {
        echo "Employee found: " . $employee->full_name . "\n";
        echo "Employee farm_owner_id: " . $employee->farm_owner_id . "\n";
        $farmOwner = FarmOwner::findOrFail($employee->farm_owner_id);
    } else {
        echo "No employee record found!\n";
        exit(1);
    }
}

echo "\n=== FARM OWNER RESOLVED ===\n";
echo "Farm Owner ID: " . $farmOwner->id . "\n";

// Now check if Finance can access the payroll
\App\Models\Payroll::find(3);
$payroll = \App\Models\Payroll::find(3);

echo "\n=== PAYROLL CHECK ===\n";
echo "Payroll ID: " . $payroll->id . "\n";
echo "Payroll farm_owner_id: " . $payroll->farm_owner_id . "\n";
echo "Resolved farm_owner_id: " . $farmOwner->id . "\n";
echo "Match: " . (($payroll->farm_owner_id === $farmOwner->id) ? 'YES ✓' : 'NO ✗') . "\n";

if ($payroll->farm_owner_id !== $farmOwner->id) {
    echo "\n❌ PROBLEM: Farm owner IDs don't match - Finance would get 403 error!\n";
}
?>
