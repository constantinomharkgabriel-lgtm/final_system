<?php
// Quick database schema inspection
$env_file = '.env';
$env = [];
foreach (file($env_file) as $line) {
    $line = trim($line);
    if (empty($line) || $line[0] === '#') continue;
    if (strpos($line, '=') === false) continue;
    list($key, $val) = explode('=', $line, 2);
    $env[trim($key)] = trim(trim($val), '"\'');
}

$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
    $env['DB_HOST'], $env['DB_PORT'], $env['DB_DATABASE'], $env['DB_SSLMODE']);
$pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD']);

// Get farm_owners columns
echo "=== farm_owners table columns ===\n";
$cols = $pdo->query("
    SELECT column_name FROM information_schema.columns
    WHERE table_schema='laravel' AND table_name='farm_owners'
    ORDER BY ordinal_position
")->fetchAll(PDO::FETCH_COLUMN);
echo implode(', ', $cols) . "\n\n";

// Get subscription data
echo "=== Current Subscriptions ===\n";
$subs = $pdo->query('SELECT * FROM laravel.subscriptions')
    ->fetchAll(PDO::FETCH_ASSOC);
echo "Total: " . count($subs) . "\n";
foreach ($subs as $sub) {
    echo "  - ID {$sub['id']}: Farm {$sub['farm_owner_id']}, Plan: {$sub['plan_type']}, Status: {$sub['status']}, Expires: {$sub['ends_at']}\n";
}

// Get farm owners
echo "\n=== Farm Owners ===\n";
$farms = $pdo->query('SELECT * FROM laravel.farm_owners')->fetchAll(PDO::FETCH_ASSOC);
echo "Total: " . count($farms) . "\n";
foreach ($farms as $farm) {
    echo "  - ID {$farm['id']}: " . json_encode(array_slice($farm, 0, 5)) . "\n";
}
?>
