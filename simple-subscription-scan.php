<?php
// Simple database connection test
$dbDsn = 'pgsql:host=aws-1-ap-southeast-2.pooler.supabase.com;port=5432;dbname=postgres;sslmode=require';
$dbUser = 'postgres.fcociwpbmvcadlomdrkm';  
$dbPass = 'Lawrencetabutol_31';

try {
    $pdo = new PDO($dbDsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "═══════════════════════════════════════════════════════════\n";
    echo "SUBSCRIPTION PLAN DIAGNOSTIC SCAN\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    // 1. Check subscriptions
    echo "1. SUBSCRIPTION RECORDS IN DATABASE\n";
    echo "─────────────────────────────────────────────────────────\n";
    $result = $pdo->query("SELECT id, farm_owner_id, plan_type, status, ends_at FROM subscriptions LIMIT 20");
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "Total Subscriptions Found: " . count($rows) . "\n\n";
    
    foreach ($rows as $row) {
        $isActive = ($row['status'] === 'active' && strtotime($row['ends_at']) > time());
        $icon = $isActive ? "✅" : "❌";
        echo "$icon ID {$row['id']}: {$row['plan_type']} ({$row['status']}) - Farm #{$row['farm_owner_id']}\n";
        echo "   Ends: {$row['ends_at']}\n";
    }
    echo "\n";
    
    // 2. Count by plan type
    echo "2. SUBSCRIPTIONS BY PLAN TYPE\n";
    echo "─────────────────────────────────────────────────────────\n";
    $result = $pdo->query("SELECT plan_type, COUNT(*) as count FROM subscriptions GROUP BY plan_type");
    $planCounts = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($planCounts as $row) {
        echo "  - {$row['plan_type']}: {$row['count']}\n";
    }
    echo "\n";
    
    // 3. Active subscriptions
    echo "3. ACTIVE SUBSCRIPTIONS (status='active' AND ends_at > NOW())\n";
    echo "─────────────────────────────────────────────────────────\n";
    $result = $pdo->query("SELECT id, farm_owner_id, plan_type FROM subscriptions WHERE status='active' AND ends_at > NOW()");
    $activeSubs = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "Active Count: " . count($activeSubs) . "\n";
    foreach ($activeSubs as $row) {
        echo "  ✅ Farm #{$row['farm_owner_id']}: {$row['plan_type']}\n";
    }
    echo "\n";
    
    // 4. Inactive subscriptions
    echo "4. INACTIVE SUBSCRIPTIONS\n";
    echo "─────────────────────────────────────────────────────────\n";
    $result = $pdo->query("SELECT id, farm_owner_id, plan_type, status, ends_at FROM subscriptions WHERE status != 'active' OR ends_at IS NULL OR ends_at <= NOW()");
    $inactiveSubs = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "Inactive Count: " . count($inactiveSubs) . "\n";
    foreach ($inactiveSubs as $row) {
        echo "  ❌ Farm #{$row['farm_owner_id']}: {$row['plan_type']} ({$row['status']})\n";
    }
    echo "\n";
    
    // 5. Farm owners with no subscription
    echo "5. FARM OWNERS WITH NO SUBSCRIPTION\n";
    echo "─────────────────────────────────────────────────────────\n";
    $result = $pdo->query("SELECT id FROM farm_owners WHERE id NOT IN (SELECT DISTINCT farm_owner_id FROM subscriptions WHERE deleted_at IS NULL) LIMIT 10");
    $noSubs = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "Count: " . count($noSubs) . "\n";
    echo "\n";
    
    // 6. Subscription limits
    echo "6. SUBSCRIPTION PLAN LIMITS\n";
    echo "─────────────────────────────────────────────────────────\n";
    $result = $pdo->query("SELECT plan_type, MAX(product_limit) as max_products, MAX(order_limit) as max_orders FROM subscriptions GROUP BY plan_type");
    $limits = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($limits as $row) {
        echo "  {$row['plan_type']}: {$row['max_products']} products, {$row['max_orders']} orders\n";
    }
    echo "\n";
    
    // 7. Check if free plan is preventing upgrades
    echo "7. FREE PLAN STATUS\n";
    echo "─────────────────────────────────────────────────────────\n";
    $result = $pdo->query("SELECT COUNT(*) as free_count FROM subscriptions WHERE plan_type='free'");
    $freeCount = $result->fetch(PDO::FETCH_ASSOC);
    echo "Free Plan Subscriptions: " . $freeCount['free_count'] . "\n";
    $result = $pdo->query("SELECT COUNT(DISTINCT farm_owner_id) as farm_count FROM subscriptions WHERE plan_type='free'");
    $freeOwners = $result->fetch(PDO::FETCH_ASSOC);
    echo "Farms with Free Plan: " . $freeOwners['farm_count'] . "\n";
    
    // Check if they're blocked from upgrading
    $result = $pdo->query("
        SELECT DISTINCT s.farm_owner_id 
        FROM subscriptions s 
        WHERE s.plan_type='free' 
        AND s.farm_owner_id IN (SELECT farm_owner_id FROM subscriptions WHERE plan_type IN ('starter', 'professional', 'enterprise'))
    ");
    $upgraded = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "Farms that Upgraded from Free: " . count($upgraded) . "\n";
    echo "\n";
    
    echo "═══════════════════════════════════════════════════════════\n";
    
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "\n";
    echo "\nMake sure PostgreSQL is running and credentials are correct.\n";
}
