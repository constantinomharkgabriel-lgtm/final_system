<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n════════════════════════════════════════════════════════\n";
echo "🔍 EMPLOYEE CREATION DEBUG TEST\n";
echo "════════════════════════════════════════════════════════\n\n";

try {
    // 1. Check if we have a farm owner
    $farmOwner = \App\Models\FarmOwner::first();
    if (!$farmOwner) {
        echo "❌ ERROR: No farm owner found in database!\n";
        echo "   First create a farm owner before testing employee creation.\n";
        exit(1);
    }
    echo "✓ Farm owner found: " . $farmOwner->farm_name . "\n\n";

    // 2. Check validation rules
    echo "📋 CHECKING VALIDATION RULES\n";
    echo "─────────────────────────────\n";
    
    $validator = \Illuminate\Support\Facades\Validator::make(
        [
            'first_name' => 'TestDriver',
            'last_name' => 'Debug',
            'email' => 'testdriver' . time() . '@test.com',
            'password' => 'TestPassword123!',
            'password_confirmation' => 'TestPassword123!',
            'phone' => '09' . time() % 10000000,
            'department' => 'logistics',
            'position' => 'Driver',
            'employment_type' => 'full_time',
            'hire_date' => now()->toDateString(),
            'daily_rate' => 500,
            'roles' => ['driver'],
            'vehicle_type' => 'motorcycle',
            'vehicle_plate' => 'TEST' . time(),
            'license_number' => 'DL-DEBUG-' . time(),
            'delivery_fee' => 50,
        ],
        [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'department' => 'required|in:farm_operations,hr,finance,logistics,sales,admin',
            'position' => 'required|string|max:100',
            'employment_type' => 'required|in:full_time,part_time,contract,seasonal',
            'hire_date' => 'required|date',
            'daily_rate' => 'nullable|numeric|min:0',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
            'vehicle_type' => 'nullable|in:motorcycle,tricycle,van,truck',
            'vehicle_plate' => 'nullable|string|max:20',
            'license_number' => 'nullable|string|max:50',
            'delivery_fee' => 'nullable|numeric|min:0',
        ]
    );

    if ($validator->fails()) {
        echo "❌ VALIDATION FAILED:\n";
        foreach ($validator->errors()->all() as $error) {
            echo "   • " . $error . "\n";
        }
        exit(1);
    }
    echo "✓ All validation rules passed\n\n";

    // 3. Check if driver role exists
    echo "🔐 CHECKING ROLES\n";
    echo "─────────────────\n";
    $driverRole = \App\Models\Role::where('name', 'driver')->first();
    if (!$driverRole) {
        echo "❌ ERROR: Driver role not found in database!\n";
        echo "   Please seed the roles table first.\n";
        exit(1);
    }
    echo "✓ Driver role exists (ID: " . $driverRole->id . ")\n\n";

    // 4. Test database transaction
    echo "💾 TESTING DATABASE TRANSACTION\n";
    echo "───────────────────────────────\n";
    
    $testEmail = 'testdriver' . time() . '@test.com';
    $testPhone = '09' . str_pad(time() % 10000, 7, '0', STR_PAD_LEFT);
    
    try {
        $transactionStarted = false;
        $employeeCreated = false;
        $driverCreated = false;
        $rolesAssigned = false;
        
        \Illuminate\Support\Facades\DB::transaction(function () use ($farmOwner, $testEmail, $testPhone, &$transactionStarted, &$employeeCreated, &$driverCreated, &$rolesAssigned) {
            $transactionStarted = true;
            echo "   → Transaction started\n";
            
            // Create user
            $user = \App\Models\User::create([
                'name' => 'TestDriver Debug',
                'email' => $testEmail,
                'password' => \Illuminate\Support\Facades\Hash::make('TestPassword123!'),
                'phone' => $testPhone,
                'role' => 'logistics',
                'status' => 'active',
                'email_verified_at' => null,
            ]);
            echo "   → User created (ID: " . $user->id . ")\n";

            // Create employee
            $employee = \App\Models\Employee::create([
                'farm_owner_id' => $farmOwner->id,
                'user_id' => $user->id,
                'employee_id' => 'EMP-DEBUG-' . time(),
                'first_name' => 'TestDriver',
                'last_name' => 'Debug',
                'department' => 'logistics',
                'position' => 'Driver',
                'employment_type' => 'full_time',
                'hire_date' => now()->toDateString(),
                'daily_rate' => 500,
                'status' => 'active',
            ]);
            $employeeCreated = true;
            echo "   → Employee created (ID: " . $employee->id . ")\n";

            // Assign role
            $employee->assignRole('driver');
            $rolesAssigned = true;
            echo "   → Driver role assigned\n";

            // Create driver profile
            $driver = \App\Models\Driver::create([
                'farm_owner_id' => $farmOwner->id,
                'employee_id' => $employee->id,
                'user_id' => $user->id,
                'driver_code' => 'DBG-' . time(),
                'name' => 'TestDriver Debug',
                'email' => $testEmail,
                'phone' => $testPhone,
                'vehicle_type' => 'motorcycle',
                'vehicle_plate' => 'DBG' . time(),
                'license_number' => 'DL-DBG-' . time(),
                'delivery_fee' => 50,
                'status' => 'available',
                'is_verified' => false,
            ]);
            $driverCreated = true;
            echo "   → Driver profile created (ID: " . $driver->id . ")\n";
        });
        
        echo "✓ Transaction completed successfully\n\n";
        
    } catch (\Throwable $e) {
        echo "❌ TRANSACTION ERROR:\n";
        echo "   Error: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        exit(1);
    }

    // 5. Verify data was saved
    echo "✔️ VERIFYING DATA SAVED\n";
    echo "──────────────────────\n";
    
    $savedEmployee = \App\Models\Employee::where('employee_id', 'LIKE', 'EMP-DEBUG%')->latest()->first();
    if ($savedEmployee) {
        echo "✓ Employee found in database\n";
        echo "   Email: " . $savedEmployee->user->email . "\n";
        echo "   Department: " . $savedEmployee->department . "\n";
        echo "   Role: driver=" . ($savedEmployee->hasRole('driver') ? 'YES' : 'NO') . "\n";
        
        $driver = $savedEmployee->driver;
        if ($driver) {
            echo "   Driver Profile:\n";
            echo "     - ID: " . $driver->id . "\n";
            echo "     - Phone: " . $driver->phone . "\n";
            echo "     - Verified: " . ($driver->is_verified ? 'Yes' : 'No') . "\n";
        } else {
            echo "   ❌ WARNING: No driver profile found!\n";
        }
    } else {
        echo "❌ ERROR: Employee not found in database!\n";
        exit(1);
    }

    echo "\n✅ ALL TESTS PASSED\n";
    echo "════════════════════════════════════════════════════════\n";

} catch (\Throwable $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "   " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
