<?php
/**
 * Payroll Testing Script
 * Creates attendance records and payroll batch for testing payroll workflow
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Payroll;
use App\Models\FarmOwner;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "\n========================================\n";
echo "   PAYROLL TESTING SETUP\n";
echo "========================================\n\n";

// Get test data
$farmOwner = FarmOwner::where('id', '>', 0)->first();
if (!$farmOwner) {
    echo "❌ No farm owner found. Create one first.\n";
    exit(1);
}

$employee = Employee::where('farm_owner_id', $farmOwner->id)->first();
if (!$employee) {
    echo "❌ No employee found. Create one first.\n";
    exit(1);
}

echo "✅ Farm Owner: {$farmOwner->business_name}\n";
echo "✅ Test Employee: {$employee->first_name} {$employee->last_name}\n";
echo "✅ Position: {$employee->position}\n";
echo "✅ Daily Rate: ₱{$employee->daily_rate}\n";
echo "✅ Monthly Salary: ₱{$employee->monthly_salary}\n\n";

// Create attendance records for April 1-30, 2026 (22 working days = Monday-Friday, no Sundays)
echo "Creating attendance records for April 2026...\n";

$attendanceCount = 0;
$workDays = 0;

// Calculate which days are NOT Sundays
for ($day = 1; $day <= 30; $day++) {
    $date = Carbon::create(2026, 4, $day);
    
    // Skip Sundays (Sunday = 0)
    if ($date->dayOfWeek === 0) {
        echo "  [Skipping {$date->format('M d, Y')} - Sunday]\n";
        continue;
    }
    
    $workDays++;
    
    // Create attendance record
    $attendance = Attendance::updateOrCreate(
        [
            'employee_id' => $employee->id,
            'work_date' => $date->toDateString(),
        ],
        [
            'farm_owner_id' => $farmOwner->id,
            'user_id' => $employee->user_id,
            'status' => 'present',
            'hours_worked' => 8.0,
            'regular_hours' => 8.0,
            'overtime_hours' => 0,
            'late_minutes' => 0,
            'notes' => 'Test attendance record',
        ]
    );
    
    $attendanceCount++;
    echo "  ✓ {$date->format('M d, Y')} (Monday-Friday)\n";
}

echo "\n✅ Created {$attendanceCount} attendance records for {$workDays} working days\n\n";

// Calculate expected payroll
$dailyRate = $employee->daily_rate ?: ($employee->monthly_salary / 22);
$monthlyCalculated = $dailyRate * $workDays;

echo "========================================\n";
echo "   EXPECTED PAYROLL CALCULATION\n";
echo "========================================\n";
echo "Daily Rate: ₱{$dailyRate}\n";
echo "Working Days (no Sundays): {$workDays}\n";
echo "Calculated Monthly: ₱{$monthlyCalculated}\n";
echo "Formula: ₱{$dailyRate} × {$workDays} days = ₱{$monthlyCalculated}\n\n";

// Generate payroll using the batch method
echo "========================================\n";
echo "   GENERATING PAYROLL BATCH\n";
echo "========================================\n\n";

$periodStart = Carbon::create(2026, 4, 1);
$periodEnd = Carbon::create(2026, 4, 30);

// Get attendance for the period
$attendance = Attendance::where('employee_id', $employee->id)
    ->whereBetween('work_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
    ->whereIn('status', ['present', 'late', 'half_day'])
    ->get();

$daysWorked = $attendance->where('status', 'present')->count()
    + $attendance->where('status', 'late')->count()
    + ($attendance->where('status', 'half_day')->count() * 0.5);

$overtimeHours = $attendance->sum('overtime_hours');
$dailyRateUsed = (float) ($employee->daily_rate ?: ((float) $employee->monthly_salary / 22));
$basicPay = $dailyRateUsed * $daysWorked;
$hourlyRate = $dailyRateUsed / 8;
$overtimePay = $overtimeHours * ($hourlyRate * 1.25);
$lateMinutes = (float) $attendance->sum('late_minutes');
$lateDeduction = round(($lateMinutes / 60) * $hourlyRate, 2);

echo "Attendance Summary:\n";
echo "  Days Worked: {$daysWorked}\n";
echo "  Overtime Hours: {$overtimeHours}\n";
echo "  Late Minutes: {$lateMinutes}\n";
echo "  Hourly Rate: ₱{$hourlyRate}\n\n";

echo "Payroll Calculation:\n";
echo "  Basic Pay: ₱{$basicPay}\n";
echo "  Overtime Pay: ₱{$overtimePay}\n";
echo "  Late Deduction: ₱{$lateDeduction}\n";
echo "  Gross Pay: ₱" . ($basicPay + $overtimePay) . "\n";
echo "  Net Pay: ₱" . (($basicPay + $overtimePay) - $lateDeduction) . "\n\n";

// Create payroll record
$count = Payroll::byFarmOwner($farmOwner->id)->whereYear('created_at', now()->year)->count() + 1;
$payrollPeriod = 'PAY-' . now()->format('Y') . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);

$payroll = Payroll::create([
    'farm_owner_id' => $farmOwner->id,
    'employee_id' => $employee->id,
    'processed_by' => User::first()->id, // Use first user as processor
    'payroll_period' => $payrollPeriod,
    'period_start' => $periodStart,
    'period_end' => $periodEnd,
    'pay_date' => $periodEnd,
    'days_worked' => $daysWorked,
    'hours_worked' => (float) $attendance->sum('hours_worked'),
    'regular_hours' => max(0, (float) $attendance->sum('hours_worked') - $overtimeHours),
    'overtime_hours' => $overtimeHours,
    'hourly_rate' => $hourlyRate,
    'basic_pay' => $basicPay,
    'overtime_pay' => $overtimePay,
    'late_deduction' => $lateDeduction,
    'gross_pay' => $basicPay + $overtimePay,
    'total_deductions' => $lateDeduction,
    'net_pay' => ($basicPay + $overtimePay) - $lateDeduction,
    'workflow_status' => 'pending_finance',
    'status' => 'pending',
    'notes' => 'Test payroll batch for April 2026',
]);

echo "========================================\n";
echo "   PAYROLL CREATED SUCCESSFULLY ✅\n";
echo "========================================\n\n";

echo "Payroll Record ID: {$payroll->id}\n";
echo "Payroll Period: {$payroll->payroll_period}\n";
echo "Status: {$payroll->status}\n";
echo "Workflow Status: {$payroll->workflow_status}\n";
echo "Period: {$payroll->period_start->format('M d, Y')} - {$payroll->period_end->format('M d, Y')}\n";
echo "Net Pay: ₱{$payroll->net_pay}\n\n";

echo "========================================\n";
echo "   NEXT STEPS FOR TESTING\n";
echo "========================================\n\n";

echo "1. LOGIN AS HR:\n";
echo "   - Go to HR Dashboard → Payroll\n";
echo "   - You'll see the payroll record with status 'Pending'\n";
echo "   - Workflow Status: Pending Finance Approval\n\n";

echo "2. LOGIN AS FINANCE:\n";
echo "   - Go to Finance Dashboard → Payroll\n";
echo "   - Click on the payroll record\n";
echo "   - Click 'Finance Approve' to approve\n";
echo "   - Status changes to 'Finance Approved'\n\n";

echo "3. LOGIN AS FARM OWNER:\n";
echo "   - Go to Farm Owner Dashboard → Payroll\n";
echo "   - Click on the payroll record\n";
echo "   - Click 'Approve' to finalize\n";
echo "   - Status changes to 'Approved'\n";
echo "   - Click 'Release Payslip' to generate payslip\n\n";

echo "4. VERIFY PAYSLIP:\n";
echo "   - Payslip should show:\n";
echo "     * Employee: {$employee->first_name} {$employee->last_name}\n";
echo "     * Period: April 1-30, 2026\n";
echo "     * Basic Pay: ₱{$basicPay}\n";
echo "     * Net Pay: ₱" . (($basicPay + $overtimePay) - $lateDeduction) . "\n\n";

echo "5. FINAL DISBURSEMENT:\n";
echo "   - Click 'Prepare for Disbursement'\n";
echo "   - As Finance: Click 'Execute Disbursement'\n";
echo "   - Status changes to 'Paid'\n\n";

echo "========================================\n\n";

?>
