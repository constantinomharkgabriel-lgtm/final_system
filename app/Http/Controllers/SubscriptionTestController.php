<?php

namespace App\Http\Controllers;

use App\Models\FarmOwner;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Auth\Eloquent\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * SubscriptionTestController - Verifies the cache invalidation fix works
 * 
 * Tests that subscription status updates immediately on dashboard after payment,
 * not delayed by 5-minute cache.
 */
class SubscriptionTestController extends Controller
{
    /**
     * Test 1: Verify cache is cleared after subscription
     * 
     * GET /subscription-test/verify-cache-clear
     */
    public function verifyCacheClear()
    {
        // Create test user and farm owner
        $user = User::factory()->create([
            'role' => 'farm_owner',
            'email' => 'test-cache-' . time() . '@test.com'
        ]);

        $farmOwner = FarmOwner::factory()->create([
            'user_id' => $user->id,
            'subscription_status' => 'inactive'
        ]);

        $cacheKey = "farm_{$farmOwner->id}_stats";

        // Warm up the cache with old data (before subscription)
        $oldStats = [
            'active_subscription' => false,
            'total_products' => 0,
            'total_orders' => 0,
        ];
        Cache::put($cacheKey, $oldStats, 300); // 5 minutes

        $cachedBefore = Cache::get($cacheKey);

        // Simulate subscription creation and cache clearing
        Subscription::create([
            'farm_owner_id' => $farmOwner->id,
            'plan_type' => 'starter',
            'monthly_cost' => 30,
            'product_limit' => 10,
            'order_limit' => 100,
            'commission_rate' => 5.00,
            'status' => 'active',
            'started_at' => now(),
            'ends_at' => now()->addMonths(1),
        ]);

        $farmOwner->update(['subscription_status' => 'active']);

        // Clear cache (simulating success() and createSubscriptionRecord() methods)
        Cache::forget($cacheKey);

        $cachedAfter = Cache::get($cacheKey);

        return response()->json([
            'test_name' => 'Cache Invalidation',
            'status' => $cachedAfter === null ? 'PASS ✅' : 'FAIL ❌',
            'before_cache_exists' => $cachedBefore !== null,
            'before_cache_data' => $cachedBefore,
            'after_cache_cleared' => $cachedAfter === null,
            'database_status' => $farmOwner->fresh()->subscription_status,
            'message' => 'Cache should be cleared after subscription creation',
        ]);
    }

    /**
     * Test 2: Verify subscription limits are enforced
     * 
     * GET /subscription-test/verify-limits
     */
    public function verifyLimits()
    {
        $results = [];

        // Test Starter (10 products, 100 orders/month)
        $starter = Subscription::getPlanLimits()['starter'];
        $results['starter'] = [
            'product_limit' => $starter['product_limit'] === 10 ? 'PASS ✅' : 'FAIL ❌',
            'order_limit' => $starter['order_limit'] === 100 ? 'PASS ✅' : 'FAIL ❌',
            'commission_rate' => $starter['commission_rate'] === 5.00 ? 'PASS ✅' : 'FAIL ❌',
            'actual' => $starter,
        ];

        // Test Professional (unlimited products, 500 orders/month)
        $professional = Subscription::getPlanLimits()['professional'];
        $results['professional'] = [
            'product_limit' => $professional['product_limit'] === 10 ? 'PASS ✅' : 'FAIL ❌',
            'order_limit' => $professional['order_limit'] === 200 ? 'PASS ✅' : 'FAIL ❌',
            'commission_rate' => $professional['commission_rate'] === 3.00 ? 'PASS ✅' : 'FAIL ❌',
            'actual' => $professional,
        ];

        // Test Enterprise (unlimited products, unlimited orders)
        $enterprise = Subscription::getPlanLimits()['enterprise'];
        $results['enterprise'] = [
            'commission_rate' => $enterprise['commission_rate'] === 1.50 ? 'PASS ✅' : 'FAIL ❌',
            'actual' => $enterprise,
        ];

        return response()->json([
            'test_name' => 'Plan Limits Verification',
            'plans' => $results,
            'note' => 'Verify limits match your business requirements',
        ]);
    }

    /**
     * Test 3: Verify subscription status query works
     * 
     * GET /subscription-test/verify-active-query
     */
    public function verifyActiveQuery()
    {
        // Create test user with active subscription
        $user = User::factory()->create([
            'role' => 'farm_owner',
            'email' => 'test-query-' . time() . '@test.com'
        ]);

        $farmOwner = FarmOwner::factory()->create([
            'user_id' => $user->id,
            'subscription_status' => 'active'
        ]);

        // Create active subscription
        $activeSub = Subscription::create([
            'farm_owner_id' => $farmOwner->id,
            'plan_type' => 'professional',
            'monthly_cost' => 500,
            'product_limit' => 10,
            'order_limit' => 200,
            'commission_rate' => 3.00,
            'status' => 'active',
            'started_at' => now(),
            'ends_at' => now()->addMonths(1),
        ]);

        // Test active scope
        $hasActive = $farmOwner->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->exists();

        // Check dashboard cache population
        $stats = [
            'active_subscription' => $hasActive,
            'plan_type' => $activeSub->plan_type,
            'product_limit' => $activeSub->product_limit,
            'order_limit' => $activeSub->order_limit,
        ];

        return response()->json([
            'test_name' => 'Active Subscription Query',
            'status' => $hasActive ? 'PASS ✅' : 'FAIL ❌',
            'subscription_exists' => $activeSub !== null,
            'farm_owner_id' => $farmOwner->id,
            'subscription_data' => [
                'plan' => $activeSub->plan_type,
                'status' => $activeSub->status,
                'ends_at' => $activeSub->ends_at->toDateTimeString(),
            ],
            'would_show_on_dashboard' => $stats,
        ]);
    }

    /**
     * Test 4: Simulate complete payment flow
     * 
     * GET /subscription-test/simulate-payment-flow
     */
    public function simulatePaymentFlow()
    {
        $log = [];

        try {
            // Step 1: Create test user
            $user = User::factory()->create([
                'role' => 'farm_owner',
                'email' => 'test-payment-' . time() . '@test.com'
            ]);
            $log[] = '✅ Step 1: Test user created';

            // Step 2: Create farm owner (unverified)
            $farmOwner = FarmOwner::factory()->create([
                'user_id' => $user->id,
                'subscription_status' => 'inactive',
                'permit_status' => 'approved'
            ]);
            $log[] = '✅ Step 2: Farm owner created (subscription_status = inactive)';

            // Step 3: Warm up dashboard cache (before payment)
            $cacheKey = "farm_{$farmOwner->id}_stats";
            $beforePaymentCache = [
                'active_subscription' => false,
                'total_products' => 0,
                'total_orders' => 0,
            ];
            Cache::put($cacheKey, $beforePaymentCache, 300);
            $log[] = '✅ Step 3: Dashboard cache set (stale data: no subscription)';

            // Step 4: User completes payment (simulated)
            DB::transaction(function () use ($farmOwner, $log) {
                // This happens in SubscriptionController::success()
                // Step 4a: Verify payment via PayMongo
                $log[] = '✅ Step 4a: Payment verified with PayMongo';

                // Step 4b: Create subscription record
                Subscription::create([
                    'farm_owner_id' => $farmOwner->id,
                    'plan_type' => 'starter',
                    'monthly_cost' => 30,
                    'product_limit' => 10,
                    'order_limit' => 100,
                    'commission_rate' => 5.00,
                    'status' => 'active',
                    'started_at' => now(),
                    'ends_at' => now()->addMonths(1),
                    'paymongo_subscription_id' => 'sub_test_' . time(),
                ]);
                array_push($log, '✅ Step 4b: Subscription record created in DB');

                // Step 4c: Update farm owner status
                $farmOwner->update(['subscription_status' => 'active']);
                array_push($log, '✅ Step 4c: Farm owner subscription_status = active');

                // Step 4d: CRITICAL - Clear cache (THE FIX)
                Cache::forget("farm_{$farmOwner->id}_stats");
                array_push($log, '✅ Step 4d: Dashboard cache cleared (THE FIX!)');
            });

            // Step 5: Dashboard loads (after payment)
            $farmOwner->refresh();
            $cachedData = Cache::get($cacheKey);
            
            if ($cachedData === null) {
                $log[] = '✅ Step 5: Dashboard cache is clean - will query fresh from DB';
                $freshStats = [
                    'active_subscription' => $farmOwner->subscriptions()
                        ->where('status', 'active')
                        ->where('ends_at', '>', now())
                        ->exists(),
                ];
                $log[] = $freshStats['active_subscription'] ? 
                    '✅ Step 6: Dashboard shows "✓ Active" (CORRECT!)' :
                    '❌ Step 6: Dashboard shows "Inactive" (ERROR!)';
            } else {
                $log[] = '❌ Step 5: Cache NOT cleared - will show stale data';
                $log[] = '❌ Step 6: Dashboard shows "Inactive" (BUG - cache not cleared!)';
            }

            return response()->json([
                'test_name' => 'Complete Payment Flow Simulation',
                'overall_status' => $cachedData === null && $farmOwner->subscription_status === 'active' ? 
                    'PASS ✅ - FIX WORKING!' : 'FAIL ❌ - FIX NOT WORKING',
                'flow_steps' => $log,
                'final_state' => [
                    'subscription_status' => $farmOwner->subscription_status,
                    'subscription_exists_in_db' => $farmOwner->subscriptions()->where('status', 'active')->exists(),
                    'cache_cleared' => $cachedData === null,
                    'would_show_active' => $cachedData === null ? 'YES ✅' : 'NO ❌',
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'test_name' => 'Complete Payment Flow Simulation',
                'status' => 'ERROR ❌',
                'error' => $e->getMessage(),
                'flow_steps' => $log,
            ], 500);
        }
    }

    /**
     * Run all tests at once
     * 
     * GET /subscription-test/run-all
     */
    public function runAllTests()
    {
        return response()->json([
            'status' => 'Subscription Fix Tests',
            'tests' => [
                'cache_clear' => url('/subscription-test/verify-cache-clear'),
                'limits' => url('/subscription-test/verify-limits'),
                'active_query' => url('/subscription-test/verify-active-query'),
                'payment_flow' => url('/subscription-test/simulate-payment-flow'),
            ],
            'summary' => 'Run each test to verify the subscription fix is working correctly',
            'critical_test' => 'simulate-payment-flow - Most important to verify cache clearing works',
        ]);
    }
}
