<?php
require 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;

// Load environment
$env = parse_ini_file('.env');
if (!$env) {
    echo "Error: Could not load .env file\n";
    exit(1);
}

// Initialize Eloquent for raw queries
DB::connection()->setAsGlobal();

echo "=== Cleaning Up Bad Driver Data ===\n\n";

try {
    // Get database connection to run raw query
    $pdo = new PDO(
        'sqlite:database/database.sqlite',
        $env['DB_USERNAME'] ?? '',
        $env['DB_PASSWORD'] ?? ''
    );
    
    echo "1. Checking for duplicate 'pending' phones in drivers...\n";
    
    // Check duplicate pending phones
    $stmt = $pdo->query("
        SELECT phone, COUNT(*) as count, GROUP_CONCAT(id) as ids 
        FROM drivers 
        WHERE phone = 'pending' 
        GROUP BY phone 
        HAVING COUNT(*) > 1
    ");
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($duplicates)) {
        echo "   Found duplicate pending phones:\n";
        foreach ($duplicates as $dup) {
            echo "   - Phone: " . ($dup['phone'] ?? 'NULL') . " | Count: {$dup['count']} | IDs: {$dup['ids']}\n";
            
            // Keep first one, delete rest
            $ids = explode(',', $dup['ids']);
            $keepId = $ids[0];
            $deleteIds = array_slice($ids, 1);
            
            foreach ($deleteIds as $id) {
                $pdo->exec("UPDATE drivers SET deleted_at = CURRENT_TIMESTAMP WHERE id = $id");
                echo "     ✓ Soft-deleted driver ID $id (keeping ID $keepId)\n";
            }
        }
    } else {
        echo "   ✓ No duplicate pending phones found\n";
    }
    
    echo "\n2. Changing 'pending' to NULL for safer operation...\n";
    $updated = $pdo->exec("UPDATE drivers SET phone = NULL WHERE phone = 'pending' AND deleted_at IS NULL");
    echo "   ✓ Updated $updated active driver records\n";
    
    echo "\n3. Checking overall driver data...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM drivers WHERE deleted_at IS NULL");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Total active drivers: {$result['count']}\n";
    
    $stmt = $pdo->query("SELECT phone, COUNT(*) as count FROM drivers WHERE deleted_at IS NULL GROUP BY phone");
    $phones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Phone distribution:\n";
    foreach ($phones as $phone) {
        echo "     - " . ($phone['phone'] ?? 'NULL (unset)') . ": {$phone['count']} drivers\n";
    }
    
    echo "\n✓ Cleanup completed successfully!\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
