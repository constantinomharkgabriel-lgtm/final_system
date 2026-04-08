<?php

use Illuminate\Database\Eloquent\Model;

require __DIR__ . '/bootstrap/app.php';

if (file_exists(__DIR__ . '/.env.local')) {
    \Dotenv\Dotenv::createImmutable(__DIR__, '.env.local')->load();
} else {
    \Dotenv\Dotenv::createImmutable(__DIR__)->load();
}

// Use raw DB query to check
$db = DB::connection();

echo "=== Checking Drivers in Database ===\n\n";

$drivers = $db->select('SELECT id, name, phone, email, is_verified, deleted_at FROM drivers ORDER BY id DESC');

if (empty($drivers)) {
    echo "No drivers found.\n";
} else {
    foreach ($drivers as $driver) {
        echo "ID: {$driver->id}\n";
        echo "  Name: {$driver->name}\n";
        echo "  Phone: " . ($driver->phone ?? 'NULL') . "\n";
        echo "  Email: {$driver->email}\n";
        echo "  Verified: " . ($driver->is_verified ? 'Yes' : 'No') . "\n";
        echo "  Deleted: " . ($driver->deleted_at ? 'Yes' : 'No') . "\n\n";
    }
}

// Check for duplicate phones
echo "\n=== Checking Duplicate Phones ===\n";
$duplicates = $db->select('SELECT phone, COUNT(*) as count FROM drivers GROUP BY phone HAVING COUNT(*) > 1');

if (empty($duplicates)) {
    echo "No duplicate phones found.\n";
} else {
    foreach ($duplicates as $dup) {
        echo "Phone '" . ($dup->phone ?? 'NULL') . "' appears {$dup->count} times\n";
    }
}
