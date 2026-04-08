<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Employee;
use App\Models\FarmOwner;
use App\Models\Payroll;

// Find HR user
$user = User::where('role', 'hr')->first();
if (!$user) {
    echo "No HR user found\n";
    exit;
}

echo "=== HR USER ===\n";
echo "ID: " . $user->id . "\n";
echo "Name: " . $user->name . "\n";
echo "Email: " . $user->email . "\n";
echo "Role: " . $user->role . "\n";

// Check if user has employee record
$employee = Employee::where('user_id', $user->id)->first();
if ($employee) {
    echo "\n=== EMPLOYEE RECORD ===\n";
    echo "Employee ID: " . $employee->id . "\n";
    echo "Name: " . $employee->full_name . "\n";
    echo "Farm Owner ID: " . $employee->farm_owner_id . "\n";
} else {
    echo "\n=== NO EMPLOYEE RECORD ===\n";
}

// Check farm owner
$farmOwner = FarmOwner::where('user_id', $user->id)->first();
if ($farmOwner) {
    echo "\n=== FARM OWNER (Direct) ===\n";
    echo "Farm Owner ID: " . $farmOwner->id . "\n";
    echo "Business Name: " . $farmOwner->business_name . "\n";
} else {
    echo "\n=== NOT A FARM OWNER ===\n";
}

// Check payroll records
echo "\n=== PAYROLL RECORDS ===\n";
if ($employee && $employee->farm_owner_id) {
    $payrolls = Payroll::where('farm_owner_id', $employee->farm_owner_id)->limit(3)->get();
    echo "Found " . $payrolls->count() . " payroll records\n";
    foreach ($payrolls as $p) {
        echo "- Payroll #" . $p->payroll_period . " (ID: " . $p->id . ", Farm Owner: " . $p->farm_owner_id . ")\n";
    }
} else {
    echo "Cannot find payrolls for this user\n";
}
?>
