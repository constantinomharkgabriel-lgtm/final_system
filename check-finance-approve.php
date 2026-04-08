<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Payroll;
use Illuminate\Support\Facades\Auth;

// Simulate Fabian login
$fabian = User::where('email', 'torio.fabianreiner@ncst.edu.ph')->first();
Auth::login($fabian);

echo "=== LOGGED IN AS ===\n";
echo "Name: " . Auth::user()->name . "\n";
echo "Role: " . Auth::user()->role . "\n";
echo "isFinance(): " . (Auth::user()->isFinance() ? 'YES' : 'NO') . "\n";

// Get pending_finance payroll
$payroll = Payroll::where('workflow_status', 'pending_finance')->first();

if (!$payroll) {
    echo "\n❌ No pending_finance payroll found\n";
    exit;
}

echo "\n=== PAYROLL OBJECT ===\n";
echo "ID: " . $payroll->id . "\n";
echo "Period: " . $payroll->payroll_period . "\n";
echo "Farm Owner ID: " . $payroll->farm_owner_id . "\n";
echo "Workflow Status: '" . $payroll->workflow_status . "' (type: " . gettype($payroll->workflow_status) . ")\n";
echo "Workflow Status === 'pending_finance': " . (($payroll->workflow_status ?? 'draft') === 'pending_finance' ? 'YES' : 'NO') . "\n";
echo "Status: " . $payroll->status . "\n";

// Check button visibility logic
echo "\n=== BUTTON VISIBILITY CHECK ===\n";
echo "isFinance(): " . (Auth::user()?->isFinance() ? 'YES' : 'NO') . "\n";
echo "workflow_status === 'pending_finance': " . ((($payroll->workflow_status ?? 'draft') === 'pending_finance') ? 'YES' : 'NO') . "\n";

$showButton = Auth::user()?->isFinance() && (($payroll->workflow_status ?? 'draft') === 'pending_finance');
echo "SHOULD SHOW BUTTON: " . ($showButton ? '✓ YES' : '✗ NO') . "\n";

// Try the financeApprove logic
echo "\n=== FINANCE APPROVE SIMULATION ===\n";
try {
    $farmOwnerId = $payroll->farm_owner_id;
    $user = Auth::user();
    $userId = $user->id;
    
    echo "Farm Owner ID matches: " . ($payroll->farm_owner_id === $farmOwnerId ? 'YES' : 'NO') . "\n";
    echo "isFinance() passes: " . ($user?->isFinance() ? 'YES' : 'NO') . "\n";
    echo "Would update to workflow_status='finance_approved'\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
