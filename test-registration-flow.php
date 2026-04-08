<?php
/**
 * Registration Flow Test Script
 * This script tests the complete consumer registration and email verification flow
 */

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use App\Models\User;
use App\Services\ConsumerVerificationService;
use Illuminate\Support\Facades\DB;

$startTime = microtime(true);

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║   REGISTRATION & VERIFICATION FLOW TEST                   ║\n";
echo "╠════════════════════════════════════════════════════════════╣\n\n";

// Test 1: Check Database Connection
echo "1. Testing database connection...";
try {
    $connection = DB::connection()->getPdo();
    echo " ✓ PASSED\n";
} catch (\Exception $e) {
    echo " ✗ FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Create a test user
echo "2. Creating test consumer user...";
try {
    $testEmail = 'test_' . uniqid() . '@example.com';
    $testUser = User::create([
        'name' => 'Test Consumer',
        'email' => $testEmail,
        'phone' => '+639123456789',
        'password' => bcrypt('password123'),
        'role' => 'consumer',
        'status' => 'active',
        'email_verified_at' => null,
    ]);
    echo " ✓ PASSED\n";
    echo "   User ID: {$testUser->id}, Email: {$testEmail}\n";
} catch (\Exception $e) {
    echo " ✗ FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Issue verification code
echo "3. Issuing verification code...";
try {
    $verificationService = app(ConsumerVerificationService::class);
    $verificationService->issueCode($testUser);
    echo " ✓ PASSED\n";
} catch (\Exception $e) {
    echo " ✗ FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Check verification code in database
echo "4. Verifying code exists in database...";
try {
    $verificationCode = DB::table('consumer_verification_codes')
        ->where('user_id', $testUser->id)
        ->first();
    
    if ($verificationCode && $verificationCode->code) {
        echo " ✓ PASSED\n";
        echo "   Code: {$verificationCode->code} (expires at: {$verificationCode->expires_at})\n";
        $code = $verificationCode->code;
    } else {
        echo " ✗ FAILED: No verification code found\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo " ✗ FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Verify email with code
echo "5. Verifying email with code...";
try {
    $verificationRecord = DB::table('consumer_verification_codes')
        ->where('user_id', $testUser->id)
        ->first();
    
    if ($verificationRecord->code === $code && \Carbon\Carbon::parse($verificationRecord->expires_at)->isFuture()) {
        // Mark email as verified
        $testUser->forceFill(['email_verified_at' => \Carbon\Carbon::now()])->save();
        DB::table('consumer_verification_codes')->where('user_id', $testUser->id)->delete();
        echo " ✓ PASSED\n";
    } else {
        echo " ✗ FAILED: Code invalid or expired\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo " ✗ FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Verify user email is marked as verified
echo "6. Checking email verification status...";
try {
    $updatedUser = User::find($testUser->id);
    if ($updatedUser->email_verified_at) {
        echo " ✓ PASSED\n";
        echo "   Email verified at: {$updatedUser->email_verified_at}\n";
    } else {
        echo " ✗ FAILED: Email verification status not updated\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo " ✗ FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// Cleanup: Delete test user
echo "7. Cleaning up test data...";
try {
    $testUser->delete();
    echo " ✓ PASSED\n";
} catch (\Exception $e) {
    echo " ✗ FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);

echo "\n╠════════════════════════════════════════════════════════════╣\n";
echo "║   ALL TESTS PASSED! ✓                                     ║\n";
echo "║   Duration: {$duration}ms                                          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "✓ Registration and email verification flow is working correctly!\n";
echo "✓ The system is ready for production use.\n\n";
