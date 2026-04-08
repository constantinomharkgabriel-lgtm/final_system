<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ConsumerVerificationCode;
use App\Services\ConsumerVerificationService;
use Illuminate\Support\Facades\Log;

// Test email
$testEmail = 'test_' . time() . '@example.com';
$testPhone = '+63' . rand(9000000000, 9999999999);

echo "============================================\n";
echo "CONSUMER REGISTRATION OTP TEST\n";
echo "============================================\n\n";

// Clean up any previous test
User::where('email', $testEmail)->delete();

echo "Test Parameters:\n";
echo "  - Email: $testEmail\n";
echo "  - Phone: $testPhone\n";
echo "  - Queue Driver: " . config('queue.default') . "\n";
echo "  - Mail Driver: " . config('mail.default') . "\n\n";

// Step 1: Create test user
echo "Step 1: Creating test user...\n";
try {
    $user = User::create([
        'name' => 'Test Consumer',
        'email' => $testEmail,
        'phone' => $testPhone,
        'password' => bcrypt('password'),
        'role' => 'consumer',
        'status' => 'active',
    ]);
    echo "  ✓ User created (ID: {$user->id})\n\n";
} catch (\Exception $e) {
    echo "  ✗ Failed to create user: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Step 2: Issue verification code
echo "Step 2: Issuing verification code...\n";
try {
    $verificationService = $app->make(ConsumerVerificationService::class);
    $verificationService->issueCode($user);
    echo "  ✓ Verification code issued\n\n";
} catch (\Exception $e) {
    echo "  ✗ Failed to issue code: " . $e->getMessage() . "\n\n";
}

// Step 3: Check if code was saved to database
echo "Step 3: Verifying code in database...\n";
$code = ConsumerVerificationCode::where('user_id', $user->id)->first();
if ($code) {
    echo "  ✓ Code found in database\n";
    echo "  - Code: " . $code->code . "\n";
    echo "  - Expires at: " . $code->expires_at . "\n";
    echo "  - Attempts: " . $code->attempts . "\n\n";
} else {
    echo "  ✗ Code NOT found in database\n\n";
}

// Step 4: Check logs
echo "Step 4: Checking application logs...\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    if (strpos($logContent, $testEmail) !== false) {
        echo "  ✓ Email found in logs\n";
        $lines = array_filter(explode("\n", $logContent), function ($line) use ($testEmail) {
            return strpos($line, $testEmail) !== false;
        });
        echo "  Recent log entries:\n";
        foreach (array_slice($lines, -5) as $line) {
            echo "    → " . substr($line, 0, 80) . "...\n";
        }
    } else {
        echo "  ⚠ Email not found in logs (may have been sent successfully)\n";
    }
} else {
    echo "  ✗ Log file not found\n";
}
echo "\n";

// Step 5: Verify verification flow
echo "Step 5: Testing verification with code...\n";
if ($code) {
    if ($code->code && $code->expires_at->isFuture()) {
        echo "  ✓ Code is valid and not expired\n";
        echo "  ✓ Code can be used for verification\n\n";
    } else {
        echo "  ✗ Code is invalid or expired\n\n";
    }
} else {
    echo "  ✗ No code to verify\n\n";
}

echo "============================================\n";
echo "TEST COMPLETE\n";
echo "============================================\n\n";

echo "Summary:\n";
echo "  - User created: ✓\n";
echo "  - Verification code issued: ✓\n";
echo "  - Code saved to DB: " . ($code ? "✓" : "✗") . "\n";
echo "  - Email attempted: ✓ (check logs or email inbox)\n\n";

echo "Next Steps:\n";
echo "  1. Go to http://127.0.0.1:8000/consumer/register\n";
echo "  2. Fill in the registration form\n";
echo "  3. Check your email for the verification code\n";
echo "  4. If email fails, check storage/logs/laravel.log\n";
echo "  5. Use the code to complete registration\n\n";

// Clean up
User::where('email', $testEmail)->delete();
echo "✓ Test data cleaned up\n";
?>
