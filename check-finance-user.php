<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Employee;
use App\Models\Payroll;

// Find Fabian
$fabian = User::where('email', 'torio.fabianreiner@ncst.edu.ph')->first();

if (!$fabian) {
    echo "❌ User not found\n";
    exit;
}

echo "=== FINANCE USER ===\n";
echo "ID: " . $fabian->id . "\n";
echo "Name: " . $fabian->name . "\n";
echo "Email: " . $fabian->email . "\n";
echo "Role (raw): " . $fabian->role . "\n";
echo "isFinance(): " . ($fabian->isFinance() ? 'YES' : 'NO') . "\n";
echo "isDepartmentRole(): " . ($fabian->isDepartmentRole() ? 'YES' : 'NO') . "\n";

// Check if employee
$employee = Employee::where('user_id', $fabian->id)->first();
if ($employee) {
    echo "\n=== EMPLOYEE RECORD ===\n";
    echo "Employee ID: " . $employee->id . "\n";
    echo "Name: " . $employee->full_name . "\n";
    echo "Farm Owner ID: " . $employee->farm_owner_id . "\n";
    echo "Department: " . $employee->department . "\n";
} else {
    echo "\n❌ NO EMPLOYEE RECORD\n";
}

// Check recent payroll
echo "\n=== PAYROLL STATUS ===\n";
$payroll = Payroll::where('workflow_status', 'pending_finance')->first();
if ($payroll) {
    echo "Payroll found: " . $payroll->payroll_period . "\n";
    echo "Farm Owner ID: " . $payroll->farm_owner_id . "\n";
    echo "Workflow Status: " . $payroll->workflow_status . "\n";
    echo "Status: " . $payroll->status . "\n";
} else {
    echo "No pending_finance payroll found\n";
}
?>
