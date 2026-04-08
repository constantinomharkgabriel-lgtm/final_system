@extends('farmowner.layouts.app')

@section('title', 'Subscriptions')
@section('header', 'Subscription Plans')
@section('subheader', 'Manage your farm subscription')

@section('content')
                <!-- Subscription Required Alert -->
                @if(session('error'))
                <div class="bg-red-900/50 border border-red-600 text-red-300 p-4 rounded-lg mb-6">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🚫</span>
                        <p class="font-semibold">{{ session('error') }}</p>
                    </div>
                </div>
                @endif

                <!-- Current Subscription Status -->
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-bold text-white mb-4">Your Current Subscription</h3>
                    
                    @if($activeSubscription)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-green-900/30 p-4 rounded-lg border border-green-700">
                            <p class="text-sm text-gray-400 mb-2">Plan Type</p>
                            <p class="text-2xl font-bold text-green-600">{{ ucfirst($activeSubscription->plan_type ?? 'Standard') }}</p>
                        </div>
                        
                        <div class="bg-blue-900/30 p-4 rounded-lg border border-blue-700">
                            <p class="text-sm text-gray-400 mb-2">Status</p>
                            <p class="px-3 py-1 rounded font-semibold bg-green-900 text-green-300 w-fit">✓ Active</p>
                        </div>

                        <div class="bg-purple-900/30 p-4 rounded-lg border border-purple-700">
                            <p class="text-sm text-gray-400 mb-2">Products Used</p>
                            <p class="font-bold {{ $activeSubscription->product_limit && $currentProducts >= $activeSubscription->product_limit ? 'text-red-400' : 'text-purple-600' }}">
                                {{ $currentProducts }} / {{ $activeSubscription->product_limit ?? '∞' }}
                            </p>
                        </div>

                        <div class="bg-orange-900/30 p-4 rounded-lg border border-orange-700">
                            <p class="text-sm text-gray-400 mb-2">Days Remaining</p>
                            <p class="font-bold text-orange-600">{{ max(0, (int) now()->diffInDays($activeSubscription->ends_at, false)) }} days</p>
                        </div>
                    </div>

                    @if($activeSubscription->product_limit && $currentProducts >= $activeSubscription->product_limit)
                    <div class="mt-4 bg-yellow-900/30 p-4 rounded-lg border border-yellow-600">
                        <p class="text-yellow-400 font-semibold">⚠️ Product Limit Reached</p>
                        <p class="text-yellow-500 text-sm mt-1">You've used all {{ $activeSubscription->product_limit }} product slots. Upgrade your plan to add more products.</p>
                    </div>
                    @endif
                    @else
                    <div class="bg-yellow-900/30 p-4 rounded-lg border border-yellow-200">
                        <p class="text-yellow-700 font-semibold">⚠️ No Active Subscription</p>
                        <p class="text-yellow-600 text-sm mt-1">Upgrade to a paid plan to unlock premium features and reach more customers.</p>
                    </div>
                    @endif
                </div>

                <!-- Available Plans -->
                <div>
                    <h3 class="text-lg font-bold text-white mb-4">Available Plans</h3>
                    
                    @php
                        $plans = isset($plans) ? $plans : [];
                        $colCount = count((array) $plans);
                        $gridClass = 'md:grid-cols-' . $colCount;
                    @endphp
                    <div class="grid grid-cols-1 {{ $gridClass }} gap-8 w-full max-w-6xl">
                        @foreach($plans as $key => $plan)
                            @php
                                // Check if this is the free plan and it has been used
                                $isFreePlanUsed = $key === 'free' && ($hasFreeSubscription ?? false);
                            @endphp
                            <div class="bg-[#111827] border-2 {{ $plan['is_free'] ?? false ? 'border-green-500' : ($key === 'professional' ? 'border-[#ed8936]' : 'border-gray-800') }} p-8 rounded-3xl shadow-2xl flex flex-col h-full @if($key === 'professional') transform scale-105 relative @endif">
                                @if($plan['is_free'] ?? false)
                                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-green-500 text-[#111827] text-[10px] font-black px-4 py-1 rounded-full uppercase">Free Trial</div>
                                @elseif($key === 'professional')
                                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-[#ed8936] text-[#111827] text-[10px] font-black px-4 py-1 rounded-full uppercase">Best Value</div>
                                @endif
                                <h3 class="text-white text-xl font-black uppercase mb-2">{{ $plan['label'] ?? ucfirst($key) . ' Plan' }}</h3>
                                <div class="flex items-baseline gap-2 mb-6">
                                    <span class="text-3xl font-black text-[#ed8936]">
                                        {{ $plan['is_free'] ?? false ? '₱0' : '₱' . number_format($plan['monthly_cost']) }}
                                    </span>
                                    @if(!($plan['is_free'] ?? false) && isset($plan['monthly_cost']))
                                        <span class="text-gray-500 line-through text-sm">
                                            @if($key === 'starter') ₱60 @elseif($key === 'professional') ₱1,000 @elseif($key === 'enterprise') ₱1,500 @endif
                                        </span>
                                    @endif
                                    <span class="text-gray-500 text-xs">/ {{ $plan['months'] ?? 1 }} mo</span>
                                </div>
                                <ul class="text-gray-400 text-sm space-y-4 mb-8 flex-grow">
                                    @if($key === 'free')
                                        <li class="flex items-center gap-2">✅ Basic Stock Tracking</li>
                                        <li class="flex items-center gap-2">✅ 1 Product Listing</li>
                                        <li class="flex items-center gap-2">✅ Up to 10 Orders/Month</li>
                                        <li class="flex items-center gap-2">✅ 1 Month Free</li>
                                    @elseif($key === 'starter')
                                        <li class="flex items-center gap-2">✅ Basic Stock Tracking</li>
                                        <li class="flex items-center gap-2">✅ List 2 Products in Market</li>
                                        <li class="flex items-center gap-2">✅ Weekly PDF Reports</li>
                                    @elseif($key === 'professional')
                                        <li class="flex items-center gap-2 text-white">⭐ Up to 10 Marketplace Listings</li>
                                        <li class="flex items-center gap-2 text-white">⭐ Full Annual Analytics</li>
                                        <li class="flex items-center gap-2 text-white">⭐ Priority 1-on-1 Support</li>
                                        <li class="flex items-center gap-2 text-white">⭐ Predictive Stock Alerts</li>
                                    @elseif($key === 'enterprise')
                                        <li class="flex items-center gap-2">✅ Unlimited Product Listings</li>
                                        <li class="flex items-center gap-2">✅ Unlimited Orders</li>
                                        <li class="flex items-center gap-2">✅ 24/7 Priority Support</li>
                                    @endif
                                </ul>
                                @if($isFreePlanUsed)
                                    <div class="w-full bg-gray-500 text-white py-4 rounded-2xl text-center font-bold cursor-not-allowed opacity-75 transition-all duration-300">
                                        ✓ Already Used
                                    </div>
                                @else
                                    @if($key === 'free')
                                        <a href="{{ route('subscription.pay', ['plan' => $key]) }}" class="w-full {{ $plan['is_free'] ?? false ? 'bg-green-500 hover:bg-green-600 text-[#111827]' : '' }} py-4 rounded-2xl text-center transition-all duration-300 font-bold">
                                            Start Free Trial
                                        </a>
                                    @elseif($key === 'enterprise')
                                        <a href="mailto:support@poultry.com" class="w-full bg-gray-800 hover:bg-[#ed8936] text-white font-bold py-4 rounded-2xl text-center transition-all duration-300">
                                            Contact Sales
                                        </a>
                                    @else
                                        <!-- Paid Plans: Test Mode + PayMongo -->
                                        <div class="space-y-2">
                                            <!-- Test Mode Button (Direct Activation) -->
                                            <a href="{{ route('subscription.pay', ['plan' => $key, 'test_mode' => 'true']) }}" class="block w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-2xl text-center transition-all duration-300">
                                                Activate Now (Testing)
                                            </a>
                                            <!-- PayMongo Button -->
                                            <a href="{{ route('subscription.pay', ['plan' => $key]) }}" class="block w-full {{ $key === 'professional' ? 'bg-[#ed8936] hover:bg-[#fbd38d] text-[#111827] font-black shadow-[0_0_20px_rgba(237,137,54,0.3)]' : 'bg-gray-800 hover:bg-[#ed8936] text-white' }} py-3 px-4 rounded-2xl text-center transition-all duration-300 font-bold">
                                                {{ $key === 'professional' ? 'Subscribe Now' : 'Get Started' }}
                                            </a>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
@endsection
