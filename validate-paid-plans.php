<?php
/**
 * Simple database validation for paid plans test mode
 * Does NOT initialize Laravel framework (avoids IP validation error)
 */

// Load environment variables using safe parsing
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    echo "ERROR: .env file not found\n";
    exit(1);
}

$env = [];
foreach (file($envFile) as $line) {
    $line = trim($line);
    if (empty($line) || $line[0] === '#') continue;
    if (strpos($line, '=') === false) continue;
    
    list($key, $value) = explode('=', $line, 2);
    $env[trim($key)] = trim(trim($value), '"\'');
}

// Connect to database directly with Supabase SSL
$host = $env['DB_HOST'] ?? 'localhost';
$db = $env['DB_DATABASE'] ?? 'postgres';
$user = $env['DB_USERNAME'] ?? 'postgres';
$pass = $env['DB_PASSWORD'] ?? '';
$port = $env['DB_PORT'] ?? 5432;
$sslmode = $env['DB_SSLMODE'] ?? 'require';

echo "=== PAID PLANS DATABASE VALIDATION ===\n\n";
echo "Connecting to: postgres@$host:$port/$db (SSL: $sslmode)\n";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=$sslmode";
    $pdo = new PDO(
        $dsn,
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✓ Database connected\n\n";
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Check subscription table structure
echo "--- TABLE STRUCTURE CHECK ---\n";
$schema = $pdo->query("
    SELECT column_name, data_type, is_nullable
    FROM information_schema.columns
    WHERE table_schema = 'laravel' AND table_name = 'subscriptions'
    ORDER BY ordinal_position
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($schema)) {
    echo "✗ Subscriptions table not found\n";
    exit(1);
}

echo "✓ Subscriptions table exists with " . count($schema) . " columns:\n";
foreach ($schema as $col) {
    echo "  - {$col['column_name']}: {$col['data_type']} " . 
         ($col['is_nullable'] === 'YES' ? '(nullable)' : '(NOT NULL)') . "\n";
}

// Get summary of all subscriptions (with schema)
echo "\n--- CURRENT SUBSCRIPTIONS ---\n";
$summary = $pdo->query("
    SELECT 
        plan_type,
        COUNT(*) as count,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
        COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired
    FROM laravel.subscriptions
    GROUP BY plan_type
    ORDER BY plan_type
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($summary)) {
    echo "No subscriptions found\n";
} else {
    foreach ($summary as $row) {
        echo "  {$row['plan']}: {$row['count']} total " .
             "({$row['active']} active, {$row['cancelled']} cancelled, {$row['expired']} expired)\n";
    }
}

// Show plan limits configuration
echo "\n--- PLAN LIMITS (FROM CODE) ---\n";
$planLimits = [
    'free' => ['product_limit' => 1, 'order_limit' => 10, 'price' => 0],
    'starter' => ['product_limit' => 2, 'order_limit' => 50, 'price' => 100],
    'professional' => ['product_limit' => 10, 'order_limit' => 200, 'price' => 500],
    'enterprise' => ['product_limit' => null, 'order_limit' => null, 'price' => 1200],
];

foreach ($planLimits as $plan => $limits) {
    $productLimit = $limits['product_limit'] ?? 'unlimited';
    echo "  {$plan}: ₱{$limits['price']} - {$productLimit} products, {$limits['order_limit']} orders/month\n";
}

// Check if any farm has paid plan active
echo "\n--- FARM SUBSCRIPTION STATUS ---\n";
$farms = $pdo->query("
    SELECT 
        fo.id,
        fo.email,
        COALESCE(s.plan_type, 'NONE') as current_plan,
        s.status,
        s.ends_at,
        CASE 
            WHEN s.plan_type = 'free' THEN 1
            WHEN s.plan_type = 'starter' THEN 2
            WHEN s.plan_type = 'professional' THEN 10
            ELSE NULL
        END as product_limit
    FROM laravel.farm_owners fo
    LEFT JOIN (
        SELECT DISTINCT ON (farm_owner_id) 
            farm_owner_id, plan_type, status, ends_at
        FROM laravel.subscriptions
        WHERE status = 'active' AND (ends_at > NOW() OR ends_at IS NULL)
        ORDER BY farm_owner_id, ends_at DESC
    ) s ON fo.id = s.farm_owner_id
    ORDER BY fo.email
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($farms)) {
    echo "No farms found\n";
} else {
    foreach ($farms as $farm) {
        $planStatus = $farm['current_plan'] . 
                      ($farm['status'] === 'active' ? ' (ACTIVE)' : ($farm['status'] ? " ({$farm['status']})" : ''));
        $limit = $farm['product_limit'] ?? 'unlimited';
        echo "  {$farm['email']}: $planStatus - Product Limit: $limit\n";
    }
}

// Check products per farm
echo "\n--- PRODUCTS PER FARM ---\n";
$products = $pdo->query("
    SELECT 
        fo.email,
        COUNT(p.id) as product_count,
        COALESCE(s.plan_type, 'NONE') as subscription_plan
    FROM laravel.farm_owners fo
    LEFT JOIN laravel.products p ON fo.id = p.farm_owner_id
    LEFT JOIN (
        SELECT DISTINCT ON (farm_owner_id) 
            farm_owner_id, plan_type
        FROM laravel.subscriptions
        WHERE status = 'active' AND (ends_at > NOW() OR ends_at IS NULL)
        ORDER BY farm_owner_id, ends_at DESC
    ) s ON fo.id = s.farm_owner_id
    GROUP BY fo.id, fo.email, s.plan_type
    ORDER BY fo.email
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($products)) {
    echo "No farm/product data\n";
} else {
    foreach ($products as $row) {
        echo "  {$row['email']}: {$row['product_count']} products (plan: {$row['subscription_plan']})\n";
    }
}

// Test readiness assessment
echo "\n--- READY FOR TESTING? ---\n";
$paidPlanCount = array_sum(array_filter(array_map(function($row) {
    return $row['plan'] !== 'free' && $row['plan'] !== 'NONE' ? $row['count'] : 0;
}, $summary)));

if ($paidPlanCount > 0) {
    echo "✓ Paid plans exist in database: Ready for testing\n";
} else {
    echo "✗ No paid plans in database yet\n";
    echo "  NEXT: Have farm owner visit subscription page and click 'Activate Now (Testing)' \n";
    echo "        on starter or professional plan to test activation\n";
}

echo "\n✓ Validation complete\n";
$pdo = null;
?>
