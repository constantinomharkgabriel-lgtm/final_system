<?php
/**
 * DRIVER CREATION DIAGNOSTIC SCRIPT
 * Check why drivers aren't appearing in Logistics sidebar
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Driver;
use App\Models\Employee;
use App\Models\User;
use App\Models\FarmOwner;

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║         DRIVER CREATION DIAGNOSTIC                      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// 1. Check if any drivers exist
$totalDrivers = Driver::count();
echo "📊 TOTAL DRIVERS IN DATABASE: $totalDrivers\n";

if ($totalDrivers === 0) {
    echo "   ⚠️  No drivers found! This means:\n";
    echo "      - No employees with driver role have been created yet, OR\n";
    echo "      - Driver creation logic is NOT working\n\n";
} else {
    echo "   ✓ Drivers exist in database\n\n";
}

// 2. Check per farm owner
echo "─────────────────────────────────────────────────────────────\n";
echo "📋 DRIVERS BY FARM OWNER:\n";
echo "─────────────────────────────────────────────────────────────\n";

$farmOwners = FarmOwner::select('id')->get();

foreach ($farmOwners as $farmOwner) {
    $count = Driver::byFarmOwner($farmOwner->id)->count();
    echo "  Farm Owner ID: {$farmOwner->id}\n";
    echo "  Total Drivers: $count\n";
    
    if ($count > 0) {
        $drivers = Driver::byFarmOwner($farmOwner->id)->get();
        foreach ($drivers as $driver) {
            echo "    • {$driver->name} - Status: {$driver->status}\n";
        }
    }
    echo "\n";
}

// 3. Check employees with driver role
echo "─────────────────────────────────────────────────────────────\n";
echo "👤 EMPLOYEES WITH DRIVER ROLE:\n";
echo "─────────────────────────────────────────────────────────────\n";

$employeeRole = \App\Models\Role::where('name', 'driver')->first();

if (!$employeeRole) {
    echo "❌ Driver role NOT FOUND in database!\n";
    echo "   This is the root cause - no role to assign to employees.\n";
} else {
    echo "✓ Driver role exists (ID: {$employeeRole->id})\n\n";
    
    $employeesWithDriverRole = Employee::whereHas('roles', function ($query) {
        $query->where('name', 'driver');
    })->get();
    
    echo "Total employees with driver role: " . count($employeesWithDriverRole) . "\n\n";
    
    foreach ($employeesWithDriverRole as $emp) {
        echo "Employee: {$emp->full_name}\n";
        echo "  Email: {$emp->email}\n";
        echo "  Department: {$emp->department}\n";
        echo "  Phone: {$emp->phone}\n";
        echo "  Roles: " . $emp->roles->pluck('name')->join(', ') . "\n";
        
        // Check if driver profile exists
        $driver = Driver::where('employee_id', $emp->id)->first();
        
        if ($driver) {
            echo "  ✓ DRIVER PROFILE EXISTS:\n";
            echo "    - Driver ID: {$driver->id}\n";
            echo "    - Farm Owner ID: {$driver->farm_owner_id}\n";
            echo "    - Vehicle: {$driver->vehicle_type} ({$driver->vehicle_plate})\n";
            echo "    - Status: {$driver->status}\n";
        } else {
            echo "  ❌ NO DRIVER PROFILE! (This is the problem)\n";
        }
        echo "\n";
    }
}

// 4. Check drivers without linked employees
echo "─────────────────────────────────────────────────────────────\n";
echo "🔍 ORPHANED DRIVERS (no employee link):\n";
echo "─────────────────────────────────────────────────────────────\n";

$orphaned = Driver::whereNull('employee_id')->get();
if (count($orphaned) > 0) {
    echo "Found " . count($orphaned) . " drivers with no employee:\n";
    foreach ($orphaned as $driver) {
        echo "  • {$driver->name} (ID: {$driver->id})\n";
    }
} else {
    echo "✓ No orphaned drivers\n";
}

// 5. What to check next
echo "\n─────────────────────────────────────────────────────────────\n";
echo "💡 DIAGNOSIS GUIDE:\n";
echo "─────────────────────────────────────────────────────────────\n";

if ($totalDrivers === 0 && count($employeesWithDriverRole) === 0) {
    echo "✓ STEP 1: Create a test employee with driver role:\n";
    echo "   1. Go to http://127.0.0.1:8000/farm-owner/employees/create\n";
    echo "   2. Fill basic info\n";
    echo "   3. Select Department: 'Driver' OR Check Role: 'Driver'\n";
    echo "   4. You SHOULD see 'Driver Details' section appear\n";
    echo "   5. Fill vehicle info (Tricycle, plate, license)\n";
    echo "   6. Click [Create Employee]\n";
    echo "   7. Check Logistics → Drivers\n\n";
    echo "✓ STEP 2: Run this script again to verify creation\n";
}

if (count($employeesWithDriverRole) > 0 && $totalDrivers === 0) {
    echo "❌ PROBLEM: Employees have driver role but no driver profiles!\n";
    echo "   This means the Driver::create() call is failing.\n\n";
    echo "   Possible causes:\n";
    echo "   1. farm_owner_id not being passed correctly\n";
    echo "   2. Missing required fields (vehicle_type?) in form\n";
    echo "   3. Validation error in driver creation\n";
    echo "   4. Database constraint issue\n\n";
    echo "   Check: storage/logs/laravel.log for errors\n";
}

if ($totalDrivers > 0 && count($employeesWithDriverRole) > count(Driver::get())) {
    echo "⚠️  WARNING: Some employees have driver role but no driver profile\n";
    echo "   This could be caused by:\n";
    echo "   1. Employees created before this feature was added\n";
    echo "   2. Driver creation failed silently\n\n";
    echo "   Run: php create-missing-driver-profiles.php\n";
}

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║         END OF DIAGNOSTIC                              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";
