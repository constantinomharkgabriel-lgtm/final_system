<?php
/**
 * Subscription Plan Diagnostic Scanner
 * Scans the entire subscription system for issues
 */

require __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Subscription;
use App\Models\FarmOwner;
use App\Models\User;

// Get Laravel app
$app = app();

echo "═══════════════════════════════════════════════════════════\n";
echo "SUBSCRIPTION PLAN DIAGNOSTIC SCAN\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// 1. Check PayMongo Configuration
echo "1. PAYMONGO CONFIGURATION\n";
echo "─────────────────────────────────────────────────────────\n";
$secretKey = config('services.paymongo.secret_key');
$publicKey = config('services.paymongo.public_key');
echo "Secret Key: " . (empty($secretKey) ? "❌ NOT SET" : "✅ SET (" . substr($secretKey, 0, 10) . "...)") . "\n";
echo "Public Key: " . (empty($publicKey) ? "❌ NOT SET" : "✅ SET (" . substr($publicKey, 0, 10) . "...)") . "\n";
echo "\n";

// 2. Database Subscriptions Table Status
echo "2. DATABASE SUBSCRIPTIONS TABLE\n";
echo "─────────────────────────────────────────────────────────\n";
try {
    $subscriptions = DB::table('subscriptions')->get();
    echo "Total Subscriptions: " . count($subscriptions) . "\n";
    
    if (count($subscriptions) > 0) {
        echo "\nSubscription Details:\n";
        foreach ($subscriptions as $sub) {
            $status = ($sub->status === 'active' && strtotime($sub->ends_at) > time()) ? "✅" : "❌";
            echo "  $status ID {$sub->id}: {$sub->plan_type} - {$sub->status} (ends: {$sub->ends_at})\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error querying subscriptions: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. Farm Owner Status
echo "3. FARM OWNER SUBSCRIPTION STATUS\n";
echo "─────────────────────────────────────────────────────────\n";
$farmOwners = DB::table('farm_owners')
    ->whereNotNull('user_id')
    ->limit(5)
    ->get();

foreach ($farmOwners as $fo) {
    echo "Farm Owner #{$fo->id}:\n";
    echo "  - Subscription Status: {$fo->subscription_status}\n";
    
    // Get their subscriptions
    $subs = DB::table('subscriptions')
        ->where('farm_owner_id', $fo->id)
        ->withoutTrashed()
        ->get();
    
    echo "  - Active Subscriptions: " . count($subs) . "\n";
    foreach ($subs as $sub) {
        $isActive = ($sub->status === 'active' && strtotime($sub->ends_at) > time());
        $status = $isActive ? "✅ ACTIVE" : "❌ INACTIVE";
        echo "    • {$sub->plan_type}: $status (ends: {$sub->ends_at})\n";
    }
    
    // Get product count
    $products = DB::table('products')
        ->where('farm_owner_id', $fo->id)
        ->count();
    echo "  - Products: $products\n";
}
echo "\n";

// 4. Product Limits Enforcement Status
echo "4. PRODUCT LIMITS ENFORCEMENT\n";
echo "─────────────────────────────────────────────────────────\n";
$limitIssues = DB::table('farm_owners as fo')
    ->selectRaw('fo.id, s.plan_type, s.product_limit, count(p.id) as product_count')
    ->leftJoin('subscriptions as s', function ($join) {
        $join->on('fo.id', '=', 's.farm_owner_id')
             ->where('s.status', '=', 'active')
             ->where('s.ends_at', '>', DB::raw('NOW()'));
    })
    ->leftJoin('products as p', 'fo.id', '=', 'p.farm_owner_id')
    ->groupBy('fo.id', 's.plan_type', 's.product_limit')
    ->having(DB::raw('count(p.id)'), '>', DB::raw('s.product_limit'))
    ->get();

echo "Limit Violations: " . count($limitIssues) . "\n";
foreach ($limitIssues as $issue) {
    $planType = $issue->plan_type ?? 'UNKNOWN';
    $limit = $issue->product_limit ?? 'UNLIMITED';
    echo "  ⚠️  Farm Owner #{$issue->id}: $issue->product_count products vs $limit limit\n";
}
echo "\n";

// 5. Subscription Activation Issues
echo "5. SUBSCRIPTION ACTIVATION STATUS\n";
echo "─────────────────────────────────────────────────────────\n";
$inactiveSubs = DB::table('subscriptions')
    ->where('status', '!=', 'active')
    ->orWhereNull('ends_at')
    ->get();
echo "Inactive or Incomplete Subscriptions: " . count($inactiveSubs) . "\n";
foreach ($inactiveSubs as $sub) {
    $reason = ($sub->status !== 'active') ? "Status: {$sub->status}" : "Missing ends_at";
    echo "  ❌ Farm Owner #{$sub->farm_owner_id}: $reason\n";
}
echo "\n";

// 6. PayMongo Integration Status
echo "6. PAYMONGO INTEGRATION\n";
echo "─────────────────────────────────────────────────────────\n";
$withPaymongoId = DB::table('subscriptions')
    ->whereNotNull('paymongo_subscription_id')
    ->count();
$totalSubs = DB::table('subscriptions')->count();
echo "Subscriptions with PayMongo ID: $withPaymongoId / $totalSubs\n";
$missingPaymongo = $totalSubs - $withPaymongoId;
echo "Subscriptions WITHOUT PayMongo ID: $missingPaymongo (these are likely FREE plan)\n";
echo "\n";

// 7. Free vs Paid Plans
echo "7. FREE VS PAID PLANS BREAKDOWN\n";
echo "─────────────────────────────────────────────────────────\n";
$planCounts = DB::table('subscriptions')
    ->select('plan_type', DB::raw('count(*) as count'))
    ->groupBy('plan_type')
    ->get();

$total = 0;
foreach ($planCounts as $row) {
    echo "  - {$row->plan_type}: {$row->count}\n";
    $total += $row->count;
}
echo "Total: $total\n";
echo "\n";

// 8. Recommendations
echo "8. RECOMMENDATIONS\n";
echo "─────────────────────────────────────────────────────────\n";
if (empty($secretKey) || empty($publicKey)) {
    echo "⚠️  CRITICAL: PayMongo keys not configured!\n";
    echo "   → Set PAYMONGO_SECRET_KEY and PAYMONGO_PUBLIC_KEY in .env\n";
}
if ($missingPaymongo > 0 && $missingPaymongo == $total) {
    echo "⚠️  CRITICAL: No paid plans found (all are free)!\n";
    echo "   → Paid plans may not be created or webhook not processing\n";
}
if (count($limitIssues) > 0) {
    echo "⚠️  WARNING: Some farm owners have exceeded product limits!\n";
    echo "   → Product limit enforcement not working\n";
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "SCAN COMPLETE\n";
echo "═══════════════════════════════════════════════════════════\n";
