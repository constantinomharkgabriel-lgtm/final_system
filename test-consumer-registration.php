<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

// Clean up test user if exists
User::where('email', 'testconsumer@example.com')->delete();

echo "============================================\n";
echo "Testing Consumer Registration Flow\n";
echo "============================================\n\n";

// Test 1: Mail Configuration
echo "1. Mail Configuration Check:\n";
echo "   - Default Mailer: " . config('mail.default') . "\n";
echo "   - SMTP Host: " . config('mail.mailers.smtp.host') . "\n";
echo "   - SMTP Port: " . config('mail.mailers.smtp.port') . "\n";
echo "   - SMTP Timeout: " . config('mail.mailers.smtp.timeout') . " seconds\n";
echo "   - Failover Config: " . json_encode(config('mail.mailers.failover')) . "\n\n";

// Test 2: Send Test Email
echo "2. Attempting to send test email...\n";
try {
    Mail::raw(
        'This is a test email from Poultry System Consumer Registration.',
        function ($message) {
            $message->to('testconsumer@example.com')
                ->subject('Poultry System Test Email');
        }
    );
    echo "   ✓ Test email sent successfully\n\n";
} catch (\Exception $e) {
    echo "   ✗ Test email failed: " . $e->getMessage() . "\n\n";
}

// Test 3: Check Logs
echo "3. Checking Log File:\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    echo "   ✓ Log file exists at: $logFile\n";
    $lastLines = shell_exec("tail -10 \"$logFile\"");
    echo "   Last 10 log entries:\n";
    foreach (explode("\n", $lastLines) as $line) {
        if (!empty($line)) {
            echo "   → " . substr($line, 0, 100) . "...\n";
        }
    }
} else {
    echo "   ✗ Log file not found\n";
}
echo "\n";

// Test 4: Clear caches
echo "4. Clearing application caches:\n";
try {
    Artisan::call('config:cache');
    echo "   ✓ Config cache cleared\n";
    Artisan::call('route:cache');
    echo "   ✓ Route cache cleared\n";
    Artisan::call('view:cache');
    echo "   ✓ View cache cleared\n";
} catch (\Exception $e) {
    echo "   ✗ Cache clear failed: " . $e->getMessage() . "\n";
}

echo "\n============================================\n";
echo "✓ Test Complete!\n";
echo "============================================\n";
echo "\nThe system is now ready for consumer registration.\n";
echo "Emails will be sent via SMTP (Gmail) or logged if SMTP fails.\n";
echo "\nTo register a consumer:\n";
echo "  1. Go to http://127.0.0.1:8000/consumer/register\n";
echo "  2. Fill in the form with valid details\n";
echo "  3. Click 'Complete Registration'\n";
echo "  4. Verification code will be sent to your email\n";
?>
