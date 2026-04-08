<?php

namespace App\Http\Controllers;

use App\Models\FarmOwner;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FarmOwnerController extends Controller
{
    public function show_registration_form()
    {
        return view('farmowner.register');
    }

    public function register(Request $request)
    {
        $user = Auth::user();
        $existingFarmOwner = $user->farmOwner;

        // Check if user has a rejected farm owner record
        $isRejectedRetry = $existingFarmOwner && $existingFarmOwner->permit_status === 'rejected';

        // Build validation rules
        $rules = [
            'farm_name' => 'required|string|max:255',
            'farm_address' => 'required|string|max:500',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'business_registration_number' => 'required|string',
        ];

        // If not a rejected retry and user already has an approved/pending farm, reject
        if ($existingFarmOwner && !$isRejectedRetry) {
            return redirect()->back()->with('error', 'You already have a farm registered. Contact admin to modify it.');
        }

        // For rejected retry, skip uniqueness check for same farm data
        if (!$isRejectedRetry) {
            $rules['farm_name'][] = 'unique:farm_owners';
            $rules['business_registration_number'][] = 'unique:farm_owners';
        } else {
            // For retries, check if farm_name/business_reg are different
            if ($request->farm_name !== $existingFarmOwner->farm_name) {
                $rules['farm_name'][] = 'unique:farm_owners';
            }
            if ($request->business_registration_number !== $existingFarmOwner->business_registration_number) {
                $rules['business_registration_number'][] = 'unique:farm_owners';
            }
        }

        $validated = $request->validate($rules);

        if ($isRejectedRetry) {
            // Update existing farm owner record
            $existingFarmOwner->update([
                'farm_name' => $validated['farm_name'],
                'farm_address' => $validated['farm_address'],
                'city' => $validated['city'],
                'province' => $validated['province'],
                'postal_code' => $validated['postal_code'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'business_registration_number' => $validated['business_registration_number'],
                'permit_status' => 'pending', // Reset to pending
            ]);

            Log::info('Rejected farm owner resubmitted registration', [
                'user_id' => $user->id,
                'farm_id' => $existingFarmOwner->id,
                'farm_name' => $validated['farm_name']
            ]);

            return redirect()->back()->with('success', 'Farm registration resubmitted. Awaiting admin verification.');
        } else {
            // Create new farm owner
            $farm_owner = FarmOwner::create([
                'user_id' => $user->id,
                ...$validated,
                'permit_status' => 'pending',
                'subscription_status' => 'inactive',
            ]);

            Log::info('Farm owner registration submitted', ['user_id' => $user->id, 'farm_id' => $farm_owner->id]);

            return redirect()->route('farmowner.dashboard')->with('success', 'Farm registered. Awaiting admin approval.');
        }
    }

    public function dashboard()
    {
        $user = Auth::user();
        $farm_owner = $user->farmOwner;

        if (!$farm_owner) {
            return redirect()->route('farmowner.login');
        }

        $stats = cache()->remember("farm_{$farm_owner->id}_stats", 300, function () use ($farm_owner) {
            return [
                'total_products' => $farm_owner->products()->count(),
                'total_orders' => $farm_owner->orders()->count(),
                'active_subscription' => $farm_owner->subscriptions()->where('status', 'active')->where('ends_at', '>', now())->exists(),
                'permit_status' => $farm_owner->permit_status,
            ];
        });

        $products = $farm_owner->products()
            ->select('id', 'farm_owner_id', 'name', 'category', 'price', 'quantity_available', 'created_at')
            ->latest('created_at')
            ->limit(5)
            ->get();

        $recent_orders = $farm_owner->orders()
            ->select('id', 'farm_owner_id', 'consumer_id', 'total_amount', 'status', 'created_at')
            ->with('consumer:id,name')
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('farmowner.dashboard', compact('farm_owner', 'stats', 'products', 'recent_orders'));
    }

    public function profile()
    {
        $farm_owner = Auth::user()->farmOwner;
        
        if (!$farm_owner) {
            return redirect()->route('farmowner.register');
        }

        return view('farmowner.profile', compact('farm_owner'));
    }

    public function update_profile(Request $request)
    {
        $farm_owner = Auth::user()->farmOwner;

        if (!$farm_owner) {
            return redirect()->route('farmowner.register');
        }

        $validated = $request->validate([
            'farm_address' => 'string|max:500',
            'city' => 'string|max:255',
            'province' => 'string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $farm_owner->update($validated);

        return redirect()->back()->with('success', 'Farm profile updated');
    }

    public function subscriptions()
    {
        $farm_owner = Auth::user()->farmOwner;

        if (!$farm_owner) {
            return redirect()->route('farmowner.register');
        }

        $subscriptions = $farm_owner->subscriptions()
            ->withoutTrashed()
            ->select('id', 'farm_owner_id', 'plan_type', 'status', 'started_at', 'ends_at', 'created_at')
            ->latest('created_at')
            ->paginate(10);

        $activeSubscription = $farm_owner->subscriptions()
            ->withoutTrashed()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->select('id', 'farm_owner_id', 'plan_type', 'status', 'product_limit', 'ends_at')
            ->latest('ends_at')
            ->first();

        $currentProducts = $farm_owner->products()->count();

        // Check if farm owner has EVER used the free plan (including expired ones, excluding soft-deleted)
        $hasFreeSubscription = $farm_owner->subscriptions()
            ->withoutTrashed()
            ->where('plan_type', 'free')
            ->exists();

        // Get plans from SubscriptionController
        $subscriptionController = app(\App\Http\Controllers\SubscriptionController::class);
        $plans = (new \ReflectionClass($subscriptionController))->getProperty('plans');
        $plans->setAccessible(true);
        $plans = $plans->getValue($subscriptionController);

        return view('farmowner.subscriptions', compact('subscriptions', 'activeSubscription', 'currentProducts', 'plans', 'hasFreeSubscription'));
    }
}
