<?php
/**
 * Auto-fill Attendance for April 2026
 * Creates attendance records for all working days (skipping Sundays)
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\FarmOwner;
use Carbon\Carbon;

echo "\n========================================\n";
echo "   AUTO-FILLING ATTENDANCE FOR APRIL\n";
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

echo "✅ Farm Owner: {$farmOwner->business_name}\n";
echo "✅ Employees: " . $employees->count() . "\n\n";

// Correct dates for April 2026 (excluding Sundays)
$workingDates = [3, 4, 6, 7, 8, 9, 10, 13, 14, 15, 16, 17, 20, 21, 22, 23, 24, 27, 28, 29, 30];

$recordCount = 0;

echo "Creating attendance records:\n";

foreach ($workingDates as $day) {
    $workDate = Carbon::create(2026, 4, $day);
    
    foreach ($employees as $employee) {
        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'work_date' => $workDate->toDateString(),
            ],
            [
                'farm_owner_id' => $farmOwner->id,
                'user_id' => $employee->user_id,
                'status' => 'present',
                'time_in' => $workDate->copy()->setTime(8, 0),
                'time_out' => $workDate->copy()->setTime(17, 0),
                'hours_worked' => 8.0,
                'regular_hours' => 8.0,
                'overtime_hours' => 0,
                'late_minutes' => 0,
                'notes' => 'Auto-generated test attendance',
            ]
        );
        
        $recordCount++;
    }
    
    echo "  ✓ {$workDate->format('M d, Y')} ({$workDate->format('l')})\n";
}

echo "\n========================================\n";
echo "   ✅ ATTENDANCE AUTO-FILLED!\n";
echo "========================================\n\n";

echo "Total Records Created: {$recordCount}\n";
echo "Days: " . count($workingDates) . " working days\n";
echo "Employees: " . $employees->count() . "\n";
echo "Records: {$recordCount} total\n\n";

echo "NEXT STEP:\n";
echo "1. Go to: Attendance → Report\n";
echo "2. Period should show April 1-30, 2026\n";
echo "3. Both employees should show:\n";
echo "   ✓ 22 working days present\n";
echo "   ✓ 176 hours total\n";
echo "   ✓ Status: READY\n";
echo "4. Click: 'Create Payroll Batch'\n";
echo "5. Start Payroll Workflow!\n\n";

?>
