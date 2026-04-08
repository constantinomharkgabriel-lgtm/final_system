<?php
$dbDsn = 'pgsql:host=aws-1-ap-southeast-2.pooler.supabase.com;port=5432;dbname=postgres;sslmode=require';
$dbUser = 'postgres.fcociwpbmvcadlomdrkm';  
$dbPass = 'Lawrencetabutol_31';

try {
    $pdo = new PDO($dbDsn, $dbUser, $dbPass);
    
    // Set search path
    $pdo->exec("SET search_path TO laravel, public");
    
    echo "═══════════════════════════════════════════════════════════\n";
    echo "SUBSCRIPTION DIAGNOSTICS\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    // 1. Total subscriptions
    echo "1. SUBSCRIPTION COUNT\n";
    echo "─────────────────────────────────────────────────────────\n";
    $result = $pdo->query("SELECT COUNT(*) as count FROM subscriptions WHERE deleted_at IS NULL");
    $total = $result->fetch(PDO::FETCH_ASSOC);
    echo "Total: " . $total['count'] . "\n\n";
    
    // 2. By plan type
    echo "2. SUBSCRIPTIONS BY PLAN TYPE\n";
    echo "─────────────────────────────────────────────────────────\n";
    $result = $pdo->query("SELECT plan_type, COUNT(*) as count FROM subscriptions WHERE deleted_at IS NULL GROUP BY plan_type ORDER BY plan_type");
    $plans = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($plans as $row) {
        echo "  {$row['plan_type']}: {$row['count']}\n";
    }
    echo "\n";
    
    // 3. Active subscriptions
    echo "3. ACTIVE SUBSCRIPTIONS (status='active' AND ends_at > NOW())\n";
    echo "─────────────────────────────────────────────────────────\n";
    $result = $pdo->query("SELECT id, farm_owner_id, plan_type, product_limit, ends_at FROM subscriptions WHERE status='active' AND ends_at > NOW() AND deleted_at IS NULL LIMIT 10");
    $active = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "Active Count: " . count($active) . "\n";
    foreach ($active as $row) {
        $daysLeft = max(0, intdiv((strtotime($row['ends_at']) - time()), 86400));
        echo "  ✅ Farm #{$row['farm_owner_id']}: {$row['plan_type']} (limit: {$row['product_limit']} products, {$daysLeft} days left)\n";
    }
    echo "\n";
    
    // 4. Inactive subscriptions
    echo "4. INACTIVE OR EXPIRED SUBSCRIPTIONS\n";
    echo "─────────────────────────────────────────────────────────\n";
    $result = $pdo->query("SELECT id, farm_owner_id, plan_type, status, ends_at FROM subscriptions WHERE (status != 'active' OR ends_at <= NOW()) AND deleted_at IS NULL LIMIT 10");
    $inactive = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "Inactive Count: " . count($inactive) . "\n";
    foreach ($inactive as $row) {
        echo "  ❌ Farm #{$row['farm_owner_id']}: {$row['plan_type']} ({$row['status']}, ended: {$row['ends_at']})\n";
    }
    echo "\n";
    
    // 5. Product usage vs limits
    echo "5. PRODUCT USAGE VS SUBSCRIPTION LIMITS\n";
    echo "─────────────────────────────────────────────────────────\n";
    $result = $pdo->query("
        SELECT 
            s.farm_owner_id, 
            s.plan_type, 
            s.product_limit,
            COUNT(DISTINCT p.id) as product_count
        FROM subscriptions s
        LEFT JOIN products p ON s.farm_owner_id = p.farm_owner_id
        WHERE s.status='active' AND s.ends_at > NOW() AND s.deleted_at IS NULL
        GROUP BY s.id, s.farm_owner_id, s.plan_type, s.product_limit
        LIMIT 10
    ");
    $usage = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($usage as $row) {
        $limit = $row['product_limit'] === null ? '∞' : $row['product_limit'];
        $status = ($row['product_count'] > $row['product_limit'] && $row['product_limit'] !== null) ? "⚠️" : "✅";
        echo "  $status Farm #{$row['farm_owner_id']} ({$row['plan_type']}): {$row['product_count']} / $limit products\n";
    }
    echo "\n";
    
    // 6. Free vs Paid breakdown
    echo "6. FREE VS PAID PLANS\n";
    echo "─────────────────────────────────────────────────────────\n";
    $result = $pdo->query("SELECT COUNT(*) as free_count FROM subscriptions WHERE plan_type='free' AND deleted_at IS NULL");
    $free = $result->fetch(PDO::FETCH_ASSOC);
    $result = $pdo->query("SELECT COUNT(*) as paid_count FROM subscriptions WHERE plan_type IN ('starter', 'professional', 'enterprise') AND deleted_at IS NULL");
    $paid = $result->fetch(PDO::FETCH_ASSOC);
    echo "  Free Plans: " . $free['free_count'] . "\n";
    echo "  Paid Plans: " . $paid['paid_count'] . "\n";
    echo "\n";
    
    // 7. Check farm owners
    echo "7. FARM OWNERS & SUBSCRIPTION STATUS\n";
    echo "─────────────────────────────────────────────────────────\n";
    $result = $pdo->query("SELECT COUNT(*) as count FROM farm_owners");
    $foCount = $result->fetch(PDO::FETCH_ASSOC);
    echo "Total Farm Owners: " . $foCount['count'] . "\n";
    
    $result = $pdo->query("
        SELECT subscription_status, COUNT(*) as count 
        FROM farm_owners 
        GROUP BY subscription_status
    ");
    $foStatus = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($foStatus as $row) {
        echo "  - {$row['subscription_status']}: {$row['count']}\n";
    }
    echo "\n";
    
    // 8. List first few farm owners with their subscriptions
    echo "8. SAMPLE FARM OWNERS WITH SUBSCRIPTIONS\n";
    echo "─────────────────────────────────────────────────────────\n";
    $result = $pdo->query("
        SELECT 
            fo.id, 
            fo.farm_name, 
            fo.subscription_status, 
            s.plan_type,
            s.status,
            s.ends_at,
            COUNT(DISTINCT p.id) as product_count
        FROM farm_owners fo
        LEFT JOIN subscriptions s ON fo.id = s.farm_owner_id AND s.deleted_at IS NULL
        LEFT JOIN products p ON fo.id = p.farm_owner_id
        GROUP BY fo.id, fo.farm_name, fo.subscription_status, s.plan_type, s.status, s.ends_at
        ORDER BY fo.id DESC
        LIMIT 10
    ");
    $foSubs = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($foSubs as $row) {
        $subStatus = $row['plan_type'] ? "{$row['plan_type']} ({$row['status']})" : "NO SUBSCRIPTION";
        $icon = ($row['status'] === 'active' && $row['ends_at'] && strtotime($row['ends_at']) > time()) ? "✅" : "❌";
        echo "$icon Farm #{$row['id']} ({$row['farm_name']}): $subStatus - {$row['product_count']} products\n";
    }
    echo "\n";
    
    echo "═══════════════════════════════════════════════════════════\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    print_r($e);
}
