<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Subscription;
use App\Models\FarmOwner;
use App\Models\PayMongoWebhookEvent;
use App\Services\PayMongoService;

class SubscriptionController extends Controller
{
    protected PayMongoService $paymongo;

    /**
     * Plan configuration: prices in centavos, limits, durations.
     */
    protected array $plans = [
        'free' => [
            'amount'          => 0,           // FREE - No charge
            'product_limit'   => 1,           // 1 product only
            'order_limit'     => 10,          // 10 orders/month
            'commission_rate' => 0.00,        // No commission
            'monthly_cost'    => 0,
            'months'          => 1,           // 1 month free trial
            'label'           => 'Free Plan (Trial)',
            'is_free'         => true,
        ],
        'starter' => [
            'amount'          => 10000,      // ₱100.00 in centavos (PayMongo minimum)
            'product_limit'   => 2,
            'order_limit'     => 50,
            'commission_rate' => 5.00,
            'monthly_cost'    => 100,
            'months'          => 1,
            'label'           => 'Starter Plan',
        ],
        'professional' => [
            'amount'          => 50000,      // ₱500.00
            'product_limit'   => 10,
            'order_limit'     => 200,
            'commission_rate' => 3.00,
            'monthly_cost'    => 500,
            'months'          => 1,
            'label'           => 'Professional Plan',
        ],
        'enterprise' => [
            'amount'          => 120000,     // ₱1,200.00
            'product_limit'   => null,       // unlimited
            'order_limit'     => null,       // unlimited
            'commission_rate' => 1.50,
            'monthly_cost'    => 1200,
            'months'          => 1,
            'label'           => 'Enterprise Plan',
        ],
    ];

    public function __construct(PayMongoService $paymongo)
    {
        $this->paymongo = $paymongo;
    }

    /**
     * Show the subscription selection page.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Must have authenticated user
        if (!$user) {
            return redirect()->route('login');
        }

        $farmOwner = FarmOwner::where('user_id', $user->id)->first();

        // COMPREHENSIVE DEBUG LOGGING
        \Log::info('=== SUBSCRIPTION INDEX DEBUG ===', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'farm_owner_exists' => $farmOwner ? true : false,
            'farm_owner_id' => $farmOwner?->id,
        ]);

// Check if farm owner has EVER used the free plan (including expired ones)
            $hasFreeSubscription = false;
            $activeSubscription = null;
            
            if ($farmOwner) {
                // Get ALL subscriptions first (excluding soft-deleted)
                $allSubscriptions = $farmOwner->subscriptions()->withoutTrashed()->get();
                
                \Log::info('All Farm Owner Subscriptions:', [
                    'farm_owner_id' => $farmOwner->id,
                    'count' => $allSubscriptions->count(),
                    'subscriptions' => $allSubscriptions->map(fn($s) => [
                        'id' => $s->id,
                        'plan_type' => $s->plan_type,
                        'status' => $s->status,
                        'deleted_at' => $s->deleted_at,
                    ])->toArray(),
                ]);

                // Check if they have ANY free plan subscription (excluding soft-deleted)
                $hasFreeSubscription = $farmOwner->subscriptions()
                    ->withoutTrashed()
                    ->where('plan_type', 'free')
                    ->exists();
                
                \Log::info('Free Subscription Check:', [
                    'farm_owner_id' => $farmOwner->id,
                    'has_free_subscription' => $hasFreeSubscription,
                ]);

                // Get active subscription (excluding soft-deleted)
                $activeSubscription = $farmOwner->subscriptions()
                    ->withoutTrashed()
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->latest()
                ->first();
                
            \Log::info('Active Subscription:', [
                'farm_owner_id' => $farmOwner->id,
                'has_active' => $activeSubscription ? true : false,
                'plan_type' => $activeSubscription?->plan_type,
            ]);
        }

        \Log::info('Passing to view:', [
            'hasFreeSubscription' => $hasFreeSubscription,
            'has_plans' => count($this->plans),
        ]);

        // Pass all plans without filtering (we'll disable free in the view if already subscribed)
        $response = view('auth.subscription-select', [
            'plans' => $this->plans,
            'hasFreeSubscription' => $hasFreeSubscription,
            'activeSubscription' => $activeSubscription,
            'farmOwner' => $farmOwner,
        ]);

        // Prevent browser caching of this page
        return $response
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Create a PayMongo Checkout Session and redirect user.
     */
    public function pay(Request $request)
    {
        try {
            $plan = $request->query('plan');
            $testMode = $request->query('test_mode', false);  // NEW: Allow test mode

            if (!array_key_exists($plan, $this->plans)) {
                return back()->withErrors(['plan' => 'Invalid plan selected.']);
            }

            $planConfig = $this->plans[$plan];
            $user = Auth::user();
            $farmOwner = FarmOwner::where('user_id', $user->id)->first();

            if (!$farmOwner) {
                return back()->withErrors(['payment' => 'Farm owner profile not found.']);
            }

            // Check if already has active subscription for this plan
            $existingActive = $farmOwner->subscriptions()
                ->withoutTrashed()
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->where('plan_type', $plan)
                ->first();

            if ($existingActive) {
                return redirect()->route('farmowner.subscriptions')
                    ->with('info', 'You already have an active ' . ucfirst($plan) . ' subscription.');
            }

            // **TEST MODE: Directly create subscription for testing (bypass PayMongo)**
            if ($testMode || env('SUBSCRIPTION_TEST_MODE', false)) {
                try {
                    $this->createSubscriptionRecord($farmOwner, $plan, null, null);

                    Log::info('TEST MODE: Subscription created', [
                        'user_id' => $user->id,
                        'farm_owner_id' => $farmOwner->id,
                        'plan' => $plan,
                    ]);

                    return redirect()->route('payment.success', ['plan' => $plan])
                        ->with('success', 'Subscription activated! (' . ucfirst($plan) . ' Plan)');
                } catch (\Exception $e) {
                    Log::error('TEST MODE: Subscription creation failed', [
                        'user_id' => $user->id,
                        'plan' => $plan,
                        'error' => $e->getMessage(),
                    ]);
                    return back()->withErrors(['payment' => 'Failed to activate plan. ' . $e->getMessage()]);
                }
            }

            // **FREE PLAN: Special check - prevent subscribing twice**
            if ($planConfig['is_free'] ?? false) {
                $hasPreviousFreeSubscription = $farmOwner->subscriptions()
                    ->withoutTrashed()
                    ->where('plan_type', 'free')
                    ->exists();

                if ($hasPreviousFreeSubscription) {
                    return redirect()->route('farmowner.subscriptions')
                        ->with('error', 'You have already used the free trial. Please upgrade to a paid plan to continue.');
                }

                try {
                    $this->createSubscriptionRecord($farmOwner, $plan, null, null);

                    Log::info('Free plan activated', [
                        'user_id' => $user->id,
                        'farm_owner_id' => $farmOwner->id,
                        'plan' => $plan,
                    ]);

                    // Redirect to success page
                    return redirect()->route('payment.success', ['plan' => $plan])
                        ->with('success', 'Free plan activated! You can now list 1 product for 1 month.');
                } catch (\Exception $e) {
                    Log::error('Free plan activation failed', [
                        'user_id' => $user->id,
                        'plan' => $plan,
                        'error' => $e->getMessage(),
                    ]);
                    return back()->withErrors(['payment' => 'Failed to activate free plan. Please try again.']);
                }
            }

            // **PAID PLANS: Proceed to PayMongo**
            if (empty(config('services.paymongo.secret_key')) || empty(config('services.paymongo.public_key'))) {
                return back()->withErrors(['payment' => 'PayMongo keys are not configured. Please set PAYMONGO_SECRET_KEY and PAYMONGO_PUBLIC_KEY in .env']);
            }

            // Create PayMongo Checkout Session
            $checkoutData = $this->paymongo->createCheckoutSession([
                'amount'        => $planConfig['amount'],
                'plan_name'     => "Poultry System - {$planConfig['label']}",
                'description'   => "{$planConfig['label']} - ₱" . number_format($planConfig['monthly_cost']) . "/month",
                'plan'          => $plan,
                'user_id'       => (string) $user->id,
                'farm_owner_id' => (string) $farmOwner->id,
                'success_url'   => route('payment.success', ['plan' => $plan, 'session_id' => '{id}']),
                'cancel_url'    => route('farmowner.subscriptions'),
            ]);

            if (!$checkoutData) {
                // Fallback to Payment Link if checkout session fails
                return $this->payViaLink($plan, $planConfig, $user, $farmOwner);
            }

            $checkoutUrl = $checkoutData['attributes']['checkout_url'] ?? null;

            if (!$checkoutUrl) {
                Log::error('PayMongo: No checkout_url in response', ['data' => $checkoutData]);
                return back()->withErrors(['payment' => 'Could not create checkout session. Please try again.']);
            }

            // Store checkout session ID for verification on success
            Cache::put(
                "checkout_session_{$user->id}_{$plan}",
                $checkoutData['id'],
                now()->addHours(2)
            );

            return redirect($checkoutUrl);

        } catch (\Exception $e) {
            Log::error('Subscription pay error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (str_contains($e->getMessage(), 'cURL error 60')) {
                return back()->withErrors([
                    'payment' => 'PayMongo SSL verification failed in local environment. Set PAYMONGO_VERIFY_SSL=false in .env and run php artisan config:clear.',
                ]);
            }

            return back()->withErrors(['payment' => 'An unexpected error occurred. Please try again.']);
        }
    }

    /**
     * Fallback: Create a Payment Link if Checkout Session fails.
     */
    protected function payViaLink(string $plan, array $planConfig, $user, FarmOwner $farmOwner)
    {
        $remarks = "USER_ID:{$user->id}|FARM_OWNER_ID:{$farmOwner->id}|PLAN:{$plan}";

        $linkData = $this->paymongo->createPaymentLink([
            'amount'      => $planConfig['amount'],
            'description' => "Poultry System - {$planConfig['label']}",
            'remarks'     => $remarks,
        ]);

        if (!$linkData) {
            return back()->withErrors(['payment' => 'Could not generate payment link. Please try again.']);
        }

        $checkoutUrl = $linkData['attributes']['checkout_url'] ?? null;

        if (!$checkoutUrl) {
            return back()->withErrors(['payment' => 'Payment link was created but checkout URL is missing.']);
        }

        return redirect($checkoutUrl);
    }

    /**
     * Handle PayMongo webhook events.
     * This is called by PayMongo when a payment event occurs.
     */
    public function handleWebhook(Request $request)
    {
        $rawPayload = $request->getContent();
        $signatureHeader = $request->header('Paymongo-Signature', '');

        // Verify webhook signature
        if (!$this->paymongo->verifyWebhookSignature($rawPayload, $signatureHeader)) {
            Log::warning('PayMongo webhook: Invalid signature');
            return response()->json(['status' => 'invalid_signature'], 403);
        }

        $payload = $request->json()->all();
        $eventType = $payload['data']['attributes']['type'] ?? null;
        $eventId = $payload['data']['id'] ?? null;

        if (!$eventId) {
            Log::warning('PayMongo webhook: missing event id', ['payload' => $payload]);
            return response()->json(['status' => 'missing_event_id'], 400);
        }

        $eventAction = 'process';
        $eventLog = null;

        DB::transaction(function () use ($eventId, $eventType, $rawPayload, &$eventAction, &$eventLog): void {
            $existing = PayMongoWebhookEvent::where('event_id', $eventId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->status === 'processed') {
                    $eventAction = 'duplicate';
                    $eventLog = $existing;
                    return;
                }

                if ($existing->status === 'processing' && $existing->updated_at && $existing->updated_at->gt(now()->subMinutes(2))) {
                    $eventAction = 'in_progress';
                    $eventLog = $existing;
                    return;
                }

                $existing->update([
                    'event_type' => $eventType,
                    'status' => 'processing',
                    'payload' => $rawPayload,
                    'response_code' => null,
                    'processed_at' => null,
                    'error_message' => null,
                ]);

                $eventLog = $existing;
                return;
            }

            $eventLog = PayMongoWebhookEvent::create([
                'event_id' => $eventId,
                'event_type' => $eventType,
                'status' => 'processing',
                'payload' => $rawPayload,
            ]);
        });

        if ($eventAction === 'duplicate') {
            return response()->json(['status' => 'duplicate_ignored'], 200);
        }

        if ($eventAction === 'in_progress') {
            return response()->json(['status' => 'processing'], 202);
        }

        Log::info('PayMongo webhook received', ['type' => $eventType]);

        try {
            // Handle checkout session payment completed
            if ($eventType === 'checkout_session.payment.paid') {
                $response = $this->handleCheckoutPayment($payload);
            } elseif ($eventType === 'link.payment.paid') {
                // Handle payment link paid (fallback method)
                $response = $this->handleLinkPayment($payload);
            } else {
                $response = response()->json(['status' => 'event_not_handled'], 200);
            }

            $eventLog?->update([
                'status' => 'processed',
                'response_code' => $response->getStatusCode(),
                'processed_at' => now(),
                'error_message' => null,
            ]);

            return $response;
        } catch (\Throwable $e) {
            $eventLog?->update([
                'status' => 'failed',
                'response_code' => 500,
                'processed_at' => now(),
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
            ]);

            Log::error('PayMongo webhook processing failed', [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Process a checkout session payment event.
     */
    protected function handleCheckoutPayment(array $payload): \Illuminate\Http\JsonResponse
    {
        $checkoutData = $payload['data']['attributes']['data'] ?? null;

        if (!$checkoutData) {
            Log::warning('PayMongo webhook: missing checkout data');
            return response()->json(['status' => 'error'], 400);
        }

        $attributes = $checkoutData['attributes'] ?? [];
        $metadata = $attributes['metadata'] ?? [];
        $payments = $attributes['payments'] ?? [];

        if (($metadata['purpose'] ?? null) === 'marketplace_order') {
            return $this->activateMarketplaceOrder(
                (string) ($metadata['order_id'] ?? ''),
                $checkoutData['id'] ?? null,
                !empty($payments) ? ($payments[0]['id'] ?? null) : null,
                $metadata['payment_method'] ?? null,
            );
        }

        $userId = $metadata['user_id'] ?? null;
        $farmOwnerId = $metadata['farm_owner_id'] ?? null;
        $plan = $metadata['plan'] ?? 'starter';
        $paymentId = !empty($payments) ? ($payments[0]['id'] ?? null) : null;

        if (!$userId || !$farmOwnerId) {
            Log::warning('PayMongo webhook: missing user/farm_owner metadata', $metadata);
            return response()->json(['status' => 'error'], 400);
        }

        return $this->activateSubscription($userId, $farmOwnerId, $plan, $checkoutData['id'] ?? null, $paymentId);
    }

    /**
     * Process a payment link paid event.
     */
    protected function handleLinkPayment(array $payload): \Illuminate\Http\JsonResponse
    {
        $linkData = $payload['data']['attributes']['data'] ?? null;
        $attributes = $linkData['attributes'] ?? [];

        $remarks = $attributes['remarks'] ?? '';
        if (!str_contains($remarks, 'USER_ID:')) {
            Log::warning('PayMongo webhook: invalid remarks', ['remarks' => $remarks]);
            return response()->json(['status' => 'error'], 400);
        }

        // Parse remarks: USER_ID:x|FARM_OWNER_ID:y|PLAN:z
        $parts = collect(explode('|', $remarks))->mapWithKeys(function ($part) {
            $segments = explode(':', $part, 2);
            return [($segments[0] ?? '') => ($segments[1] ?? '')];
        });

        if (($parts['PURPOSE'] ?? null) === 'MARKETPLACE_ORDER') {
            return $this->activateMarketplaceOrder(
                (string) ($parts['ORDER_ID'] ?? ''),
                $linkData['id'] ?? null,
                $linkData['id'] ?? null,
                isset($parts['PAYMENT_METHOD']) ? strtolower((string) $parts['PAYMENT_METHOD']) : null,
            );
        }

        $userId = $parts['USER_ID'] ?? null;
        $farmOwnerId = $parts['FARM_OWNER_ID'] ?? null;
        $plan = $parts['PLAN'] ?? 'starter';
        $paymentId = $linkData['id'] ?? null;

        if (!$userId) {
            Log::warning('PayMongo webhook: no USER_ID in remarks');
            return response()->json(['status' => 'error'], 400);
        }

        // If farm_owner_id not in remarks, look it up
        if (!$farmOwnerId) {
            $farmOwner = FarmOwner::where('user_id', $userId)->first();
            $farmOwnerId = $farmOwner?->id;
        }

        return $this->activateSubscription($userId, $farmOwnerId, $plan, $paymentId);
    }

    /**
     * Activate a subscription after successful payment (for webhooks).
     */
    protected function activateSubscription(
        string $userId,
        ?string $farmOwnerId,
        string $plan,
        ?string $paymongoId = null,
        ?string $paymentMethodId = null
    ): \Illuminate\Http\JsonResponse {
        $user = User::find($userId);
        if (!$user) {
            Log::warning('PayMongo webhook: user not found', ['user_id' => $userId]);
            return response()->json(['status' => 'error'], 404);
        }

        $farmOwner = $farmOwnerId
            ? FarmOwner::find($farmOwnerId)
            : FarmOwner::where('user_id', $userId)->first();

        if (!$farmOwner) {
            Log::warning('PayMongo webhook: farm owner not found', ['user_id' => $userId]);
            return response()->json(['status' => 'error'], 404);
        }

        // Validate plan type - only accept valid plans
        if (!array_key_exists($plan, $this->plans)) {
            $plan = 'starter';
        }

        // Check for duplicate payment (idempotency)
        if ($paymongoId) {
            $existing = Subscription::where('paymongo_subscription_id', $paymongoId)->first();
            if ($existing) {
                Log::info('PayMongo webhook: duplicate payment ignored', ['paymongo_id' => $paymongoId]);
                return response()->json(['status' => 'already_processed'], 200);
            }
        }

        // Create the subscription record
        $this->createSubscriptionRecord($farmOwner, $plan, $paymongoId, $paymentMethodId);

        Log::info("Subscription activated", [
            'user_id' => $userId,
            'farm_owner_id' => $farmOwner->id,
            'plan' => $plan,
            'paymongo_id' => $paymongoId,
        ]);

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Create subscription record in database (shared logic for web and webhooks).
     */
    private function createSubscriptionRecord(
        FarmOwner $farmOwner,
        string $plan,
        ?string $paymongoId = null,
        ?string $paymentMethodId = null
    ): void {
        $planConfig = $this->plans[$plan];

        DB::transaction(function () use ($farmOwner, $plan, $planConfig, $paymongoId, $paymentMethodId) {
            // Expire existing active subscriptions
            $farmOwner->subscriptions()
                ->where('status', 'active')
                ->update([
                    'status' => 'expired',
                    'ends_at' => now(),
                ]);

            // Create new subscription
            Subscription::create([
                'farm_owner_id'              => $farmOwner->id,
                'plan_type'                  => $plan,
                'monthly_cost'               => $planConfig['monthly_cost'],
                'product_limit'              => $planConfig['product_limit'],
                'order_limit'                => $planConfig['order_limit'],
                'commission_rate'            => $planConfig['commission_rate'],
                'status'                     => 'active',
                'started_at'                 => now(),
                'ends_at'                    => now()->addMonths($planConfig['months']),
                'renewal_at'                 => now()->addMonths($planConfig['months'])->subDays(3),
                'paymongo_subscription_id'   => $paymongoId,
                'paymongo_payment_method_id' => $paymentMethodId,
            ]);

            // Update farm owner status
            $farmOwner->update(['subscription_status' => 'active']);

            Cache::forget("farm_{$farmOwner->id}_stats");
        });
    }

    protected function activateMarketplaceOrder(
        string $orderId,
        ?string $paymongoId = null,
        ?string $paymentId = null,
        ?string $paymentMethod = null
    ): \Illuminate\Http\JsonResponse {
        if ($orderId === '') {
            Log::warning('PayMongo order webhook: missing order id');
            return response()->json(['status' => 'error'], 400);
        }

        $order = \App\Models\Order::with(['consumer:id,name', 'farmOwner:id,user_id,farm_name'])->find($orderId);
        if (!$order) {
            Log::warning('PayMongo order webhook: order not found', ['order_id' => $orderId]);
            return response()->json(['status' => 'error'], 404);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['status' => 'already_processed'], 200);
        }

        $order->update([
            'payment_status' => 'paid',
            'payment_method' => $paymentMethod ?: $order->payment_method,
            'paymongo_payment_id' => $order->paymongo_payment_id ?: ($paymentId ?: $paymongoId),
        ]);

        if ($order->consumer_id) {
            \App\Models\Notification::create([
                'user_id' => $order->consumer_id,
                'title' => 'Payment Confirmed',
                'message' => "Payment for order {$order->order_number} was confirmed via PayMongo.",
                'type' => 'system',
                'channel' => 'in_app',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        if ($order->farmOwner?->user_id) {
            \App\Models\Notification::create([
                'user_id' => $order->farmOwner->user_id,
                'title' => 'Customer Payment Received',
                'message' => "Order {$order->order_number} has been paid online.",
                'type' => 'alert',
                'channel' => 'in_app',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        Log::info('Marketplace order marked as paid', [
            'order_id' => $order->id,
            'paymongo_id' => $paymongoId,
            'payment_id' => $paymentId,
        ]);

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Success page after payment — verify with PayMongo and activate if webhook hasn't fired yet.
     */
    public function success(Request $request)
    {
        $user = Auth::user();
        $plan = $request->query('plan', 'starter');
        $farmOwner = FarmOwner::where('user_id', $user->id)->first();

        if (!$farmOwner) {
            return redirect()->route('farmowner.subscriptions')->with('error', 'Farm not found.');
        }

        // Try to verify payment via cached checkout session
        $subscriptionCreated = false;
        if (array_key_exists($plan, $this->plans)) {
            $cacheKey = "checkout_session_{$user->id}_{$plan}";
            $checkoutSessionId = Cache::get($cacheKey);

            if ($checkoutSessionId) {
                // Retrieve checkout session from PayMongo to verify payment
                $sessionData = $this->paymongo->retrieveCheckoutSession($checkoutSessionId);

                if ($sessionData) {
                    $status = $sessionData['attributes']['payment_intent']['attributes']['status'] ?? null;
                    $payments = $sessionData['attributes']['payments'] ?? [];

                    // If payment succeeded and no active subscription yet, activate it
                    if (($status === 'succeeded' || !empty($payments)) && 
                        !$farmOwner->subscriptions()->where('status', 'active')->where('paymongo_subscription_id', $checkoutSessionId)->exists()) {
                        
                        $paymentId = !empty($payments) ? ($payments[0]['id'] ?? null) : null;
                        $this->createSubscriptionRecord($farmOwner, $plan, $checkoutSessionId, $paymentId);
                        $subscriptionCreated = true;

                        Log::info('Payment success - subscription activated', [
                            'user_id' => $user->id,
                            'farm_owner_id' => $farmOwner->id,
                            'plan' => $plan,
                        ]);
                    }
                }

                Cache::forget($cacheKey);
            }
        }

        // Clear the dashboard cache to reflect new subscription status immediately
        Cache::forget("farm_{$farmOwner->id}_stats");

        // Get the current active subscription to display
        $activeSubscription = $farmOwner->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest()
            ->first();

        // Refresh farm owner to get latest subscription_status
        $farmOwner->refresh();

        Log::info('Payment success page loaded', [
            'user_id' => $user->id,
            'farm_owner_id' => $farmOwner->id,
            'subscription_created' => $subscriptionCreated,
            'farm_subscription_status' => $farmOwner->subscription_status,
            'has_active_subscription' => $activeSubscription ? true : false,
        ]);

        return view('auth.payment-success', [
            'subscription' => $activeSubscription,
            'plan' => $plan,
            'subscriptionCreated' => $subscriptionCreated,
        ]);
    }
}