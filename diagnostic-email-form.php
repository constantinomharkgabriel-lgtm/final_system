<?php
/**
 * EMAIL & FORM SUBMISSION DIAGNOSTIC SCAN
 * 
 * Issues to diagnose:
 * 1. Form reloads/refreshes instead of redirecting
 * 2. Employee not appearing in list after creation
 * 3. Verification email not being sent to Gmail
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

echo "\n════════════════════════════════════════════════════════\n";
echo "🔍 EMAIL & FORM SUBMISSION DIAGNOSTIC SCAN\n";
echo "════════════════════════════════════════════════════════\n\n";

// SCAN 1: Check Mail Configuration
echo "SCAN 1: Mail Configuration\n";
echo "──────────────────────────\n";

$mailDriver = config('mail.default');
$mailFrom = config('mail.from');
echo "✓ Mail Driver: " . $mailDriver . "\n";
echo "✓ From Address: " . json_encode($mailFrom) . "\n";

if ($mailDriver === 'failover') {
    echo "✓ Failover Mail: ENABLED\n";
    $smtpHost = config('mail.mailers.smtp.host');
    $smtpPort = config('mail.mailers.smtp.port');
    $smtpEncryption = config('mail.mailers.smtp.encryption');
    
    echo "  - SMTP Host: " . $smtpHost . "\n";
    echo "  - SMTP Port: " . $smtpPort . "\n";
    echo "  - SMTP Encryption: " . $smtpEncryption . "\n";
    
    $logMailer = config('mail.mailers.log');
    echo "  - Log Mailer: " . json_encode($logMailer) . "\n";
}

// SCAN 2: Check Laravel Log Configuration
echo "\nSCAN 2: Laravel Logging Configuration\n";
echo "────────────────────────────────────\n";

$logChannel = config('logging.default');
echo "✓ Log Channel: " . $logChannel . "\n";

$logPath = storage_path('logs/laravel.log');
if (file_exists($logPath)) {
    $fileSize = filesize($logPath);
    $lastModified = filemtime($logPath);
    echo "✓ Log File: " . $logPath . "\n";
    echo "  - Size: " . round($fileSize / 1024) . " KB\n";
    echo "  - Last Modified: " . date('Y-m-d H:i:s', $lastModified) . "\n";
} else {
    echo "❌ Log file not found\n";
}

// SCAN 3: Check Recent Log Entries
echo "\nSCAN 3: Recent Log Entries (Last 100 lines)\n";
echo "──────────────────────────────────────────\n";

if (file_exists($logPath)) {
    $logContent = file_get_contents($logPath);
    $lines = explode("\n", $logContent);
    $recentLines = array_slice($lines, -100);
    
    // Look for errors or relevant messages
    $errorCount = 0;
    $warningCount = 0;
    $infoCount = 0;
    
    foreach ($recentLines as $line) {
        if (strpos($line, '"ERROR"') !== false || strpos($line, 'error') !== false) {
            $errorCount++;
        } elseif (strpos($line, '"WARNING"') !== false || strpos($line, 'warning') !== false) {
            $warningCount++;
        } elseif (strpos($line, '"INFO"') !== false || strpos($line, 'info') !== false) {
            $infoCount++;
        }
    }
    
    echo "✓ Recent log summary:\n";
    echo "  - Errors: " . $errorCount . "\n";
    echo "  - Warnings: " . $warningCount . "\n";
    echo "  - Info messages: " . $infoCount . "\n";
    
    // Show last 10 lines (most recent)
    echo "\n✓ Last 10 log lines:\n";
    $last10 = array_slice($recentLines, -10);
    foreach ($last10 as $line) {
        if (trim($line)) {
            echo "  " . substr($line, 0, 150) . "...\n";
        }
    }
}

// SCAN 4: Check .env file for mail configuration
echo "\nSCAN 4: Environment Mail Settings (.env)\n";
echo "────────────────────────────────────────\n";

$envPath = base_path('.env');
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    $lines = explode("\n", $envContent);
    
    $mailSettings = [];
    foreach ($lines as $line) {
        if (strpos($line, 'MAIL_') === 0 || strpos($line, 'QUEUE_') === 0) {
            $mailSettings[] = $line;
        }
    }
    
    echo "✓ Mail-related env settings:\n";
    foreach ($mailSettings as $setting) {
        // Hide sensitive values
        if (strpos($setting, 'PASSWORD') !== false) {
            echo "  " . substr($setting, 0, strpos($setting, '=') + 2) . "***\n";
        } else {
            echo "  " . $setting . "\n";
        }
    }
} else {
    echo "❌ .env file not found\n";
}

// SCAN 5: Check Employee Model for issues
echo "\nSCAN 5: Employee Model Scopes\n";
echo "────────────────────────────\n";

$employeeModel = '\App\Models\Employee';
echo "✓ Employee model methods:\n";

$methods = get_class_methods($employeeModel);
$scopeMethods = array_filter($methods, function($method) {
    return strpos($method, 'scope') === 0 || strpos($method, 'by') === 0;
});

foreach ($scopeMethods as $method) {
    echo "  - " . $method . "\n";
}

// SCAN 6: Check routes for employees
echo "\nSCAN 6: Employee Routes\n";
echo "──────────────────────\n";

$routes = \Illuminate\Support\Facades\Route::getRoutes();
$employeeRoutes = [];
foreach ($routes as $route) {
    if (strpos($route->uri, 'employees') !== false) {
        $employeeRoutes[] = [
            'method' => implode('|', $route->methods),
            'uri' => $route->uri,
            'action' => $route->action['controller'] ?? 'N/A',
        ];
    }
}

if (count($employeeRoutes) > 0) {
    echo "✓ Found " . count($employeeRoutes) . " employee routes:\n";
    foreach ($employeeRoutes as $route) {
        echo "  - " . $route['method'] . " " . $route['uri'] . "\n";
        echo "    → " . $route['action'] . "\n";
    }
} else {
    echo "❌ No employee routes found\n";
}

// SCAN 7: Check if employees are actually being saved
echo "\nSCAN 7: Recent Employees in Database\n";
echo "───────────────────────────────────\n";

$recentEmployees = \App\Models\Employee::orderBy('created_at', 'desc')->limit(5)->get();
echo "✓ Last 5 employees created:\n";
foreach ($recentEmployees as $emp) {
    echo "  - " . $emp->employee_id . " | " . $emp->first_name . " " . $emp->last_name . " | Created: " . $emp->created_at . "\n";
}

// SCAN 8: Check email sending history
echo "\nSCAN 8: Recent Drivers (for verification status)\n";
echo "───────────────────────────────────────────────\n";

$recentDrivers = \App\Models\Driver::orderBy('created_at', 'desc')->limit(5)->get();
echo "✓ Last 5 drivers created:\n";
foreach ($recentDrivers as $driver) {
    echo "  - " . $driver->driver_code . " | " . $driver->email . " | Verified: " . ($driver->is_verified ? 'YES' : 'NO') . " | Created: " . $driver->created_at . "\n";
}

// SCAN 9: Check mail log file if logging to file
echo "\nSCAN 9: Mail Log (if using log driver)\n";
echo "────────────────────────────────────\n";

if ($mailDriver === 'log' || $mailDriver === 'failover') {
    $mailLogPath = storage_path('logs/mail.log');
    if (file_exists($mailLogPath)) {
        $mailLogContent = file_get_contents($mailLogPath);
        if (strlen($mailLogContent) > 0) {
            echo "✓ Mail log file exists and has content\n";
            echo "  - Size: " . round(filesize($mailLogPath) / 1024) . " KB\n";
            
            // Show last few entries
            $mailLines = array_slice(explode("\n", $mailLogContent), -5);
            echo "  - Last entries:\n";
            foreach ($mailLines as $line) {
                if (trim($line)) {
                    echo "    " . substr($line, 0, 100) . "\n";
                }
            }
        } else {
            echo "⚠ Mail log file exists but is empty\n";
        }
    } else {
        echo "⚠ Mail log file not found at: " . $mailLogPath . "\n";
    }
}

// SCAN 10: Redirect/Response check
echo "\nSCAN 10: Form Redirect Configuration\n";
echo "───────────────────────────────────\n";

$routeName = 'employees.index';
try {
    $url = route($routeName);
    echo "✓ employees.index route resolves to: " . $url . "\n";
} catch (\Exception $e) {
    echo "❌ Route resolution failed: " . $e->getMessage() . "\n";
}

echo "\n════════════════════════════════════════════════════════\n";
echo "✅ DIAGNOSTIC SCAN COMPLETE\n";
echo "════════════════════════════════════════════════════════\n\n";

echo "📝 SUMMARY OF FINDINGS:\n";
echo "  Mail Driver: " . $mailDriver . "\n";
echo "  Total Employees: " . \App\Models\Employee::count() . "\n";
echo "  Total Drivers: " . \App\Models\Driver::count() . "\n";
echo "  Recent Errors in Log: " . $errorCount . "\n";
echo "\n";
