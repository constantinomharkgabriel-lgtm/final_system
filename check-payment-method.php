<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payroll;

$payroll = Payroll::find(3);

echo "=== CURRENT PAYROLL STATE ===\n\n";
echo "Payroll: " . $payroll->payroll_period . "\n";
echo "Workflow Status: " . $payroll->workflow_status . "\n";
echo "Status: " . $payroll->status . "\n";
echo "Payment Method: " . ($payroll->payment_method ? ucfirst(str_replace('_', ' ', $payroll->payment_method)) : 'NOT YET SELECTED') . "\n";
echo "Disbursement Reference: " . ($payroll->disbursement_reference ?? 'None') . "\n\n";

if (!$payroll->payment_method) {
    echo "⚠️  PAYMENT METHOD NOT SET YET\n\n";
    echo "This means Farm Owner hasn't prepared the disbursement yet.\n";
    echo "Farm Owner needs to:\n";
    echo "1. Login to their account\n";
    echo "2. Go to Payroll Management → View payroll\n";
    echo "3. Select payment method (Cash/Bank Transfer/Check/GCash)\n";
    echo "4. Click 'Prepare Disbursement' button\n";
    echo "\nAfter that, you can execute disbursement.\n";
} else {
    echo "✓ PAYMENT METHOD IS SET: " . strtoupper(str_replace('_', ' ', $payroll->payment_method)) . "\n\n";
    echo "Reference number based on payment method:\n";
    
    $references = [
        'cash' => 'CASH-001 or Receipt number',
        'bank_transfer' => 'Bank transaction ID (e.g., TXN20260400123456)',
        'check' => 'Check number (e.g., CHK-001234)',
        'gcash' => 'GCash transaction ID (e.g., 123456789012)'
    ];
    
    echo "For " . $payroll->payment_method . ": " . $references[$payroll->payment_method] . "\n\n";
    echo "You can now execute disbursement with the reference number.\n";
}
?>
