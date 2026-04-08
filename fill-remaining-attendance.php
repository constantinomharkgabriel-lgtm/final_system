<?php
/**
 * Auto-fill Remaining Attendance for April 2026
 * Fills all working days except Sundays (5, 12, 19, 26)
 * Already done: 1, 2, 3, 4
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\FarmOwner;
use Carbon\Carbon;

echo "\n========================================\n";
echo "   FILLING REMAINING APRIL ATTENDANCE\n";
echo "========================================\n\n";

$farmOwner = FarmOwner::where('id', '>', 0)->first();
if (!$farmOwner) {
    echo "❌ No farm owner found\n";
    exit(1);
}

$employees = Employee::where('farm_owner_id', $farmOwner->id)->active()->get();
if ($employees->isEmpty()) {
    echo "❌ No employees found\n";
    exit(1);
}

echo "✅ Farm Owner ID: {$farmOwner->id}\n";
echo "✅ Employees Found: " . $employees->count() . "\n";
foreach ($employees as $emp) {
    echo "   - {$emp->first_name} {$emp->last_name} (ID: {$emp->id})\n";
}
echo "\n";

// Remaining dates (already have 1-4, skip Sundays 5, 12, 19, 26)
$remainingDates = [6, 7, 8, 9, 10, 13, 14, 15, 16, 17, 20, 21, 22, 23, 24, 27, 28, 29, 30];

$recordCount = 0;
echo "📝 Creating attendance records:\n\n";

foreach ($remainingDates as $day) {
    $workDate = Carbon::create(2026, 4, $day);
    
    foreach ($employees as $employee) {
        // Check if already exists
        $existing = Attendance::where('employee_id', $employee->id)
            ->whereDate('work_date', $workDate->toDateString())
            ->first();
        
        if (!$existing) {
            Attendance::create([
                'farm_owner_id' => $farmOwner->id,
                'employee_id' => $employee->id,
                'user_id' => $employee->user_id,
                'work_date' => $workDate->toDateString(),
                'status' => 'present',
                'time_in' => $workDate->copy()->setTime(8, 0),
                'time_out' => $workDate->copy()->setTime(17, 0),
                'hours_worked' => 8.0,
                'regular_hours' => 8.0,
                'overtime_hours' => 0,
                'late_minutes' => 0,
                'notes' => 'Auto-generated attendance',
            ]);
            $recordCount++;
        }
    }
    
    echo "   ✓ April {$day} ({$workDate->format('l')})\n";
}

echo "\n========================================\n";
echo "   ✅ COMPLETE!\n";
echo "========================================\n\n";

echo "✓ Records Created: {$recordCount}\n";
echo "✓ Working Days Added: " . count($remainingDates) . "\n";
echo "✓ Total for Month: 22 working days (1-4 already done + 19 just added)\n\n";

// Verify totals
foreach ($employees as $emp) {
    $attendance = Attendance::where('employee_id', $emp->id)
        ->whereMonth('work_date', 4)
        ->whereYear('work_date', 2026)
        ->get();
    
    $presentDays = $attendance->where('status', 'present')->count();
    $totalHours = $attendance->sum('hours_worked');
    
    echo "📊 {$emp->first_name} {$emp->last_name}:\n";
    echo "   • Present Days: {$presentDays}\n";
    echo "   • Total Hours: {$totalHours}h\n";
    echo "   • Status: " . ($presentDays >= 22 ? "✅ READY FOR PAYROLL" : "⏳ Pending") . "\n\n";
}

echo "========================================\n";
echo "   NEXT STEPS:\n";
echo "========================================\n\n";

echo "1. Go to: Attendance Report\n";
echo "   URL: http://127.0.0.1:8000/farm-owner/attendance/report\n\n";

echo "2. Verify Status:\n";
echo "   ✓ Both employees show 22 working days\n";
echo "   ✓ Both show 176 total hours\n";
echo "   ✓ Status: READY FOR PAYROLL\n\n";

echo "3. Create Payroll:\n";
echo "   Click: '👔 Create Payroll Batch →'\n\n";

echo "4. Start Workflow:\n";
echo "   • HR generates batch\n";
echo "   • Finance approves\n";
echo "   • Farm Owner approves & releases payslip\n";
echo "   • Finance executes disbursement\n\n";

?>
