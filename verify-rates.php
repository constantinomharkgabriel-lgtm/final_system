<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n========================================\n";
echo "   EMPLOYEE DAILY RATES\n";
echo "========================================\n\n";

$employees = \App\Models\Employee::select('id', 'first_name', 'last_name', 'daily_rate')->get();
foreach($employees as $e) {
    $basicPay = $e->daily_rate * 22;
    echo sprintf("✓ %s %s (ID: %d)\n", $e->first_name, $e->last_name, $e->id);
    echo sprintf("  Daily Rate: ₱%.2f\n", $e->daily_rate);
    echo sprintf("  Monthly (22 days): ₱%.2f\n\n", $basicPay);
}
