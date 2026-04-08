<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

echo "============================================\n";
echo "GMAIL SMTP TEST\n";
echo "============================================\n\n";

// Check configuration
echo "Configuration Check:\n";
echo "  - Mail Driver: " . config('mail.default') . "\n";
echo "  - SMTP Host: " . config('mail.mailers.smtp.host') . "\n";
echo "  - SMTP Port: " . config('mail.mailers.smtp.port') . "\n";
echo "  - SMTP Username: " . config('mail.mailers.smtp.username') . "\n";
echo "  - SMTP Encryption: " . config('mail.mailers.smtp.encryption') . "\n";
echo "  - SMTP Scheme: " . config('mail.mailers.smtp.scheme') . "\n";
echo "  - Mail From: " . config('mail.from.address') . "\n\n";

// Test sending
echo "Attempting to send test email...\n";
try {
    Mail::mailer('smtp')->raw(
        "This is a test email sent directly via SMTP.\n\nTimestamp: " . date('Y-m-d H:i:s') . "\n\nIf you received this, Gmail SMTP is working correctly!",
        function ($message) {
            $message->to('johntanan20@gmail.com', 'Test Recipient')
                ->subject('Poultry System - Gmail SMTP Test (Direct)')
                ->from(config('mail.from.address'), config('mail.from.name'));
        }
    );
    echo "  ✓ Email sent via SMTP mailer\n\n";
} catch (\Exception $e) {
    echo "  ✗ SMTP Failed: " . $e->getMessage() . "\n\n";
    echo "Attempting fallback to LOG...\n";
    try {
        Mail::mailer('log')->raw(
            "This is a fallback test (logged, not sent)",
            function ($message) {
                $message->to('johntanan20@gmail.com')
                    ->subject('Poultry System - Fallback Test')
                    ->from(config('mail.from.address'), config('mail.from.name'));
            }
        );
        echo "  ✓ Email logged to laravel.log\n\n";
    } catch (\Exception $e2) {
        echo "  ✗ Fallback also failed: " . $e2->getMessage() . "\n\n";
    }
}

echo "============================================\n";
echo "Check your email at: johntanan20@gmail.com\n";
echo "Or check logs at: storage/logs/laravel.log\n";
echo "============================================\n";
?>
