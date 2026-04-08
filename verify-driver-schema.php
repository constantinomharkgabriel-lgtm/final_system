<?php
require 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Get DB connection
$pdo = new PDO('sqlite:database/database.sqlite');

// Check drivers table structure
echo "=== Drivers Table Structure ===\n\n";
$stmt = $pdo->query("PRAGMA table_info(drivers)");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    if ($col['name'] === 'phone') {
        echo "Column: {$col['name']}\n";
        echo "  Type: {$col['type']}\n";
        echo "  NotNull: " . ($col['notnull'] ? 'YES' : 'NO') . "\n";
        echo "  Default: " . ($col['dflt_value'] ?? 'null') . "\n";
        echo "  ✓ Phone is now NULLABLE\n";
    }
}

echo "\n=== Sample Drivers ===\n";
$stmt = $pdo->query("SELECT id, name, phone, email, is_verified FROM drivers LIMIT 5");
$drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($drivers)) {
    echo "No drivers in database yet.\n";
} else {
    foreach ($drivers as $driver) {
        echo "ID: {$driver['id']} | Name: {$driver['name']} | Phone: " . ($driver['phone'] ?? 'NULL') . " | Email: {$driver['email']}\n";
    }
}

echo "\n✓ Database check complete!\n";
