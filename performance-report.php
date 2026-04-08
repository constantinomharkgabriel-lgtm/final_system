<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         PERFORMANCE DIAGNOSTIC REPORT                      ║\n";
echo "║                 April 4, 2026                              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "1. CONFIGURATION CHECKS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "APP_DEBUG: " . (config('app.debug') ? '❌ TRUE (SLOW!)' : '✓ FALSE (FAST)') . "\n";
echo "APP_ENV: " . config('app.env') . "\n";
echo "Cache Driver: " . config('cache.default') . "\n";
echo "Session Driver: " . config('session.driver') . "\n";
echo "Database: " . config('database.default') . "\n";

echo "\n2. CACHE STATUS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$cacheFiles = glob(storage_path('framework/cache/data/*'));
echo "Cached bootstrap files: " . count($cacheFiles) . "\n";
if (file_exists(bootstrap_path('cache/config.php'))) {
    echo "✓ Config cached\n";
} else {
    echo "❌ Config NOT cached\n";
}
if (file_exists(bootstrap_path('cache/routes.php'))) {
    echo "✓ Routes cached\n";
} else {
    echo "❌ Routes NOT cached\n";
}
if (file_exists(bootstrap_path('cache/services.php'))) {
    echo "✓ Services cached\n";
} else {
    echo "❌ Services NOT cached\n";
}

echo "\n3. FILES & DEPENDENCIES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Composer files scanned\n";
$composerSize = filesize('composer.json') / 1024;
echo "composer.json: " . round($composerSize, 2) . " KB\n";

$vendorSize = 0;
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('vendor'));
foreach ($files as $file) {
    if ($file->isFile()) {
        $vendorSize += $file->getSize();
    }
}
echo "vendor/ directory: " . round($vendorSize / 1024 / 1024, 2) . " MB\n";

echo "\n4. PROBLEM SUMMARY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$issues = [];
if (config('app.debug')) {
    $issues[] = "⚠️  APP_DEBUG=true (causes 50%+ performance loss)";
}
if (!file_exists(bootstrap_path('cache/config.php'))) {
    $issues[] = "⚠️  Config not cached (run php artisan config:cache)";
}
if (!file_exists(bootstrap_path('cache/routes.php'))) {
    $issues[] = "⚠️  Routes not cached (run php artisan route:cache)";
}

if (empty($issues)) {
    echo "✓ All major issues resolved!\n";
} else {
    foreach ($issues as $issue) {
        echo $issue . "\n";
    }
}

echo "\n5. RECOMMENDED ACTIONS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "1. Turn OFF APP_DEBUG in .env\n";
echo "2. Cache configuration: php artisan config:cache\n";
echo "3. Cache routes: php artisan route:cache\n";
echo "4. Cache views: php artisan view:cache\n";
echo "5. Optimize autoloader: composer dump-autoload -o\n";
echo "6. Clear all caches: php artisan optimize\n";

echo "\n✓ Report complete!\n";
