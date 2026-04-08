<?php

namespace App\Http\Controllers;

use App\Models\FarmOwner;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DIAGNOSTIC CONTROLLER - Debug subscription payment flow
 * Access via: GET /debug-subscription-system
 */
class SubscriptionDiagnosticsController extends Controller
{
    /**
     * Full system diagnostic
     */
    public function diagnose()
    {
        $diagnostics = [];

        // 1. Check if any subscriptions exist
        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $diagnostics['subscriptions'] = [
            'total' => $totalSubscriptions,
            'active' => $activeSubscriptions,
            'count_by_status' => Subscription::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->mapWithKeys(fn($item) => [$item->status => $item->count])
                ->toArray(),
        ];

        // 2. Check farm owners
        $totalFarmOwners = FarmOwner::count();
        $activeSubscriptionStatus = FarmOwner::where('subscription_status', 'active')->count();
        $inactiveSubscriptionStatus = FarmOwner::where('subscription_status', 'inactive')->count();
        $diagnostics['farm_owners'] = [
            'total' => $totalFarmOwners,
            'with_active_status' => $activeSubscriptionStatus,
            'with_inactive_status' => $inactiveSubscriptionStatus,
        ];

        // 3. Check sample farm owner details
        $sampleFarmOwner = FarmOwner::with(['subscriptions' => function ($q) {
            $q->select('id', 'farm_owner_id', 'plan_type', 'status', 'ended_at', 'created_at');
        }])->first();

        if ($sampleFarmOwner) {
            $diagnostics['sample_farm_owner'] = [
                'id' => $sampleFarmOwner->id,
                'farm_name' => $sampleFarmOwner->farm_name,
                'subscription_status' => $sampleFarmOwner->subscription_status,
                'subscriptions_in_db' => $sampleFarmOwner->subscriptions->toArray(),
                'has_active_subscription_query' => $sampleFarmOwner->subscriptions()
                    ->where('status', 'active')
                    ->where('ends_at', '>', now())
                    ->exists(),
            ];
        }

        // 4. Check recent subscriptions
        $recentSubscriptions = Subscription::with('farmOwner:id,farm_name,subscription_status')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'farm_owner_id' => $sub->farm_owner_id,
                    'farm_name' => $sub->farmOwner?->farm_name,
                    'farm_subscription_status' => $sub->farmOwner?->subscription_status,
                    'subscription_plan' => $sub->plan_type,
                    'subscription_status' => $sub->status,
                    'created_at' => $sub->created_at,
                    'ended_at' => $sub->ends_at,
                ];
            })
            ->toArray();

        $diagnostics['recent_subscriptions'] = $recentSubscriptions;

        // 5. Check if there's a mismatch between farm_owner.subscription_status and subscriptions table
        $mismatches = DB::select("
            SELECT fo.id, fo.farm_name, fo.subscription_status,
                   CASE WHEN s.id IS NOT NULL THEN 'has_active_subscription' ELSE 'no_active_subscription' END as actual_status
            FROM farm_owners fo
            LEFT JOIN subscriptions s ON fo.id = s.farm_owner_id 
                AND s.status = 'active' 
                AND s.ends_at > NOW()
            WHERE fo.subscription_status = 'active' AND s.id IS NULL
            LIMIT 10
        ");

        $diagnostics['mismatches'] = $mismatches;

        // 6. Check logs for recent errors
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $lines = array_slice(file($logFile), -50);
            $recentLogs = array_filter($lines, function ($line) {
                return strpos($line, 'Subscription') !== false || strpos($line, 'Payment') !== false;
            });
            $diagnostics['recent_logs'] = array_slice($recentLogs, -10);
        }

        return response()->json($diagnostics, 200);
    }

    /**
     * Find all farms with subscription_status = active but NO actual subscription record
     */
    public function findMissingSubscriptions()
    {
        $mismatched = FarmOwner::where('subscription_status', 'active')
            ->withCount([
                'subscriptions as active_subs' => function ($q) {
                    $q->where('status', 'active')->where('ends_at', '>', now());
                }
            ])
            ->having('active_subs', '=', 0)
            ->with('user:id,name,email')
            ->get();

        return response()->json([
            'mismatched_count' => $mismatched->count(),
            'farms' => $mismatched->map(function ($farm) {
                return [
                    'id' => $farm->id,
                    'farm_name' => $farm->farm_name,
                    'subscription_status' => $farm->subscription_status,
                    'user' => $farm->user ? ['name' => $farm->user->name, 'email' => $farm->user->email] : null,
                    'subscriptions_count' => $farm->subscriptions()->count(),
                ];
            }),
        ]);
    }

    /**
     * Manually fix a farm owner subscription
     * POST /fix-farm-subscription/{farm_owner_id}/{plan}
     */
    public function fixSubscription($farmOwnerId, $plan = 'starter')
    {
        $farmOwner = FarmOwner::findOrFail($farmOwnerId);

        if (!$farmOwner) {
            return response()->json(['error' => 'Farm not found'], 404);
        }

        // Fix 1: Update farm_owner.subscription_status to active
        $farmOwner->update(['subscription_status' => 'active']);

        // Fix 2: Create subscription if doesn't exist
        $existingActive = $farmOwner->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();

        if (!$existingActive) {
            $plans = [
                'starter' => [
                    'monthly_cost' => 30,
                    'product_limit' => 10,
                    'order_limit' => 100,
                    'commission_rate' => 5.00,
                    'months' => 1,
                ],
                'professional' => [
                    'monthly_cost' => 500,
                    'product_limit' => 10,
                    'order_limit' => 200,
                    'commission_rate' => 3.00,
                    'months' => 1,
                ],
                'enterprise' => [
                    'monthly_cost' => 1200,
                    'product_limit' => null,
                    'order_limit' => null,
                    'commission_rate' => 1.50,
                    'months' => 1,
                ],
            ];

            $planConfig = $plans[$plan] ?? $plans['starter'];

            Subscription::create([
                'farm_owner_id' => $farmOwner->id,
                'plan_type' => $plan,
                'monthly_cost' => $planConfig['monthly_cost'],
                'product_limit' => $planConfig['product_limit'],
                'order_limit' => $planConfig['order_limit'],
                'commission_rate' => $planConfig['commission_rate'],
                'status' => 'active',
                'started_at' => now(),
                'ends_at' => now()->addMonths($planConfig['months']),
                'renewal_at' => now()->addMonths($planConfig['months'])->subDays(3),
            ]);
        }

        // Fix 3: Clear cache
        \Illuminate\Support\Facades\Cache::forget("farm_{$farmOwner->id}_stats");

        Log::info('Subscription fixed manually', [
            'farm_owner_id' => $farmOwner->id,
            'plan' => $plan,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription fixed for ' . $farmOwner->farm_name,
            'farm_owner' => [
                'id' => $farmOwner->id,
                'farm_name' => $farmOwner->farm_name,
                'subscription_status' => $farmOwner->subscription_status,
            ],
        ]);
    }

    /**
     * Fix ALL mismatched subscriptions at once
     */
    public function fixAllMismatched()
    {
        $mismatched = FarmOwner::where('subscription_status', 'active')
            ->withCount([
                'subscriptions as active_subs' => function ($q) {
                    $q->where('status', 'active')->where('ends_at', '>', now());
                }
            ])
            ->having('active_subs', '=', 0)
            ->get();

        $fixed = [];

        foreach ($mismatched as $farm) {
            // Create subscription
            Subscription::create([
                'farm_owner_id' => $farm->id,
                'plan_type' => 'starter',
                'monthly_cost' => 30,
                'product_limit' => 10,
                'order_limit' => 100,
                'commission_rate' => 5.00,
                'status' => 'active',
                'started_at' => now(),
                'ends_at' => now()->addMonths(1),
                'renewal_at' => now()->addMonths(1)->subDays(3),
            ]);

            // Clear cache
            \Illuminate\Support\Facades\Cache::forget("farm_{$farm->id}_stats");

            $fixed[] = [
                'id' => $farm->id,
                'farm_name' => $farm->farm_name,
            ];
        }

        Log::info('Bulk subscription fix completed', ['count' => count($fixed)]);

        return response()->json([
            'success' => true,
            'message' => 'Fixed ' . count($fixed) . ' subscription mismatches',
            'fixed' => $fixed,
        ]);
    }
}
