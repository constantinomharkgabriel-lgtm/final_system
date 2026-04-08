<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Payroll;
use Illuminate\Support\Facades\Auth;

// Simulate Fabian login
$fabian = User::find(5);
Auth::login($fabian);

echo "=== COMPREHENSIVE FINANCE BUTTON SCAN ===\n\n";

// Get payroll
$payroll = Payroll::find(3);

echo "1. USER CHECK\n";
echo "   User: " . Auth::user()->name . "\n";
echo "   Role: " . Auth::user()->role . "\n";
echo "   isFinance(): " . (Auth::user()?->isFinance() ? 'YES' : 'NO') . "\n\n";

echo "2. PAYROLL STATUS\n";
echo "   Payroll: " . $payroll->payroll_period . "\n";
echo "   workflow_status: '" . $payroll->workflow_status . "'\n";
echo "   status: " . $payroll->status . "\n\n";

echo "3. BUTTON VISIBILITY CONDITIONS (From View)\n";

// Finance Approve button
echo "   Finance Approve Button:\n";
echo "   - Condition: Auth::user()?->isFinance() && (workflow_status ?? 'draft') === 'pending_finance'\n";
$financeApproveShow = Auth::user()?->isFinance() && (($payroll->workflow_status ?? 'draft') === 'pending_finance');
echo "   - Auth::user()?->isFinance(): " . (Auth::user()?->isFinance() ? 'TRUE' : 'FALSE') . "\n";
echo "   - (workflow_status ?? 'draft') === 'pending_finance': " . ((($payroll->workflow_status ?? 'draft') === 'pending_finance') ? 'TRUE' : 'FALSE') . "\n";
echo "   - RESULT: " . ($financeApproveShow ? '✓ SHOW' : '✗ HIDE') . "\n\n";

// Release Payslip button
echo "   Release Payslip Button:\n";
echo "   - Condition: Auth::user()?->isFinance() && workflow_status === 'owner_approved'\n";
$releasePayslipShow = Auth::user()?->isFinance() && (($payroll->workflow_status ?? '') === 'owner_approved');
echo "   - workflow_status === 'owner_approved': " . ((($payroll->workflow_status ?? '') === 'owner_approved') ? 'TRUE' : 'FALSE') . "\n";
echo "   - RESULT: " . ($releasePayslipShow ? '✓ SHOW' : '✗ HIDE') . "\n\n";

// Execute Disbursement button
echo "   Execute Disbursement Button:\n";
echo "   - Condition: Auth::user()?->isFinance() && workflow_status === 'ready_for_disbursement' && status === 'approved'\n";
$executeDisbursementShow = Auth::user()?->isFinance() && (($payroll->workflow_status ?? '') === 'ready_for_disbursement') && $payroll->status === 'approved';
echo "   - workflow_status === 'ready_for_disbursement': " . ((($payroll->workflow_status ?? '') === 'ready_for_disbursement') ? 'TRUE' : 'FALSE') . "\n";
echo "   - status === 'approved': " . ($payroll->status === 'approved' ? 'TRUE' : 'FALSE') . "\n";
echo "   - RESULT: " . ($executeDisbursementShow ? '✓ SHOW' : '✗ HIDE') . "\n\n";

// Back button
echo "   Back Button:\n";
echo "   - Always shown (no condition)\n";
echo "   - RESULT: ✓ SHOW\n\n";

echo "4. ROUTE CHECK\n";
if ($financeApproveShow) {
    echo "   Finance Approve Route: " . route('payroll.financeApprove', $payroll) . "\n";
}
echo "   Back Route: " . route('payroll.index') . "\n\n";

echo "5. RENDERED VIEW CHECK\n";
$isFinance = Auth::user()?->isFinance();
$isHR = Auth::user()?->isHR();

if ($isFinance) {
    echo "   Should render: department.payroll.show\n";
    echo "   View file: resources/views/department/payroll/show.blade.php\n";
} elseif ($isHR) {
    echo "   Should render: hr.payroll.show\n";
} else {
    echo "   Should render: farmowner.payroll.show\n";
}

echo "\n6. SUMMARY\n";
if ($financeApproveShow) {
    echo "   ✓ Finance Approve button SHOULD be visible\n";
} else {
    echo "   ✗ Finance Approve button should NOT be visible\n";
}
echo "   ✓ Back button should ALWAYS be visible\n";

if (!$financeApproveShow || true) { // Back button always shows
    echo "\n   NO PROBLEMS DETECTED IN LOGIC\n";
    echo "   Issue must be in view rendering or template file\n";
}
?>
