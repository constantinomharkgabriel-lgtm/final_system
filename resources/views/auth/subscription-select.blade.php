@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-green-50 to-blue-50 px-4 py-12">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Upgrade Your Plan</h1>
            <p class="text-xl text-gray-600">Choose the perfect plan for your poultry farm</p>
        </div>

        <!-- System Status Info -->
        <div class="bg-blue-50 border-l-4 border-blue-600 p-4 mb-8 max-w-2xl mx-auto">
            <p class="text-sm text-blue-900 font-mono">
                System Status: {{ $hasFreeSubscription ? '✓ Free subscription ACTIVE' : '✗ No free subscription' }}<br>
                User: {{ Auth::user()->email ?? 'Not logged in' }}<br>
                Free Plan Status: {{ $hasFreeSubscription ? 'SUBSCRIBED (should show Already Active)' : 'NOT SUBSCRIBED (should show Start Free Trial)' }}
            </p>
        </div>

        <!-- Plans Grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 max-w-7xl mx-auto">
            @foreach(['free' => ['name' => 'Free Trial', 'price' => '₱0', 'description' => 'Perfect for getting started', 'features' => ['Up to 100 chickens', 'Basic monitoring', 'Email support'], 'color' => 'gray'], 'starter' => ['name' => 'Starter', 'price' => '₱999', 'description' => 'For small farms', 'features' => ['Up to 500 chickens', 'Advanced monitoring', 'Priority support'], 'color' => 'blue'], 'professional' => ['name' => 'Professional', 'price' => '₱2,999', 'description' => 'For growing farms', 'features' => ['Up to 2000 chickens', 'Full analytics', '24/7 support'], 'color' => 'purple'], 'enterprise' => ['name' => 'Enterprise', 'price' => 'Custom', 'description' => 'Custom solutions', 'features' => ['Unlimited chickens', 'API access', 'Dedicated support'], 'color' => 'indigo']] as $key => $plan)
                @php
                    // Determine if user has free subscription and this is the free plan
                    $userHasFreeSubscription = $hasFreeSubscription ?? false;
                    $isFreePlanSubscribed = $userHasFreeSubscription && $key === 'free';
                    
                    // Color classes based on plan
                    $colorMap = [
                        'gray' => 'bg-gray-50 border-gray-200 hover:border-gray-300',
                        'blue' => 'bg-blue-50 border-blue-200 hover:border-blue-300',
                        'purple' => 'bg-purple-50 border-purple-200 hover:border-purple-300',
                        'indigo' => 'bg-indigo-50 border-indigo-200 hover:border-indigo-300'
                    ];
                    $badgeColorMap = [
                        'gray' => 'bg-gray-100 text-gray-800',
                        'blue' => 'bg-blue-100 text-blue-800',
                        'purple' => 'bg-purple-100 text-purple-800',
                        'indigo' => 'bg-indigo-100 text-indigo-800'
                    ];
                    $buttonColorMap = [
                        'gray' => 'bg-gray-600 hover:bg-gray-700',
                        'blue' => 'bg-blue-600 hover:bg-blue-700',
                        'purple' => 'bg-purple-600 hover:bg-purple-700',
                        'indigo' => 'bg-indigo-600 hover:bg-indigo-700'
                    ];
                    $color = $plan['color'];
                @endphp

                <div class="relative">
                    <!-- Card Background -->
                    <div class="h-full border-2 rounded-xl transition-all duration-300 {{ $colorMap[$color] }} {{ $isFreePlanSubscribed ? 'opacity-60 border-green-400' : '' }}">
                        
                        <!-- Debug line for FREE PLAN ONLY - VERY VISIBLE -->
                        @if($key === 'free')
                            <div style="background-color: #ff0000; color: #ffffff; padding: 8px; font-size: 14px; font-weight: bold; text-align: center;">
                                DEBUG: isFreePlanSubscribed = {{ $isFreePlanSubscribed ? '🟢 TRUE' : '🔴 FALSE' }} | userHasFreeSubscription = {{ $userHasFreeSubscription ? '🟢 TRUE' : '🔴 FALSE' }}
                            </div>
                        @endif
                        
                        <!-- Badge -->
                        <div class="absolute -top-4 right-6">
                            @if($isFreePlanSubscribed)
                                <span class="inline-block px-4 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                    ✓ Current Plan
                                </span>
                            @else
                                <span class="inline-block px-4 py-1 rounded-full text-sm font-semibold {{ $badgeColorMap[$color] }}">
                                    {{ $plan['name'] }}
                                </span>
                            @endif
                        </div>

                        <!-- Card Content -->
                        <div class="p-8">
                            <!-- Plan Name & Price -->
                            <div class="mb-6">
                                <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan['name'] }}</h3>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-4xl font-bold text-gray-900">{{ $plan['price'] }}</span>
                                    @if($key !== 'free' && $key !== 'enterprise')
                                        <span class="text-gray-600">/month</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Description -->
                            <p class="text-gray-600 mb-6">{{ $plan['description'] }}</p>

                            <!-- Features List -->
                            <ul class="space-y-3 mb-8">
                                @foreach($plan['features'] as $feature)
                                    <li class="flex items-center gap-3 text-gray-700">
                                        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            </ul>

                            <!-- Action Button -->
                            @if($isFreePlanSubscribed)
                                <!-- Already Active State -->
                                <div class="w-full bg-gray-500 text-white py-3 px-6 rounded-lg text-center font-semibold cursor-not-allowed opacity-75">
                                    Already Active
                                </div>
                                @if($activeSubscription && $key === 'free')
                                    <p class="text-sm text-gray-600 text-center mt-3">
                                        Expires: {{ \Carbon\Carbon::parse($activeSubscription->ends_at)->format('F d, Y') }}
                                    </p>
                                @endif
                            @else
                                <!-- Active/Clickable State -->
                                @if($key === 'free')
                                    <a href="{{ route('subscription.pay', ['plan' => 'free']) }}" class="block w-full {{ $buttonColorMap[$color] }} text-white py-3 px-6 rounded-lg text-center font-semibold transition-all hover:shadow-lg">
                                        Start Free Trial
                                    </a>
                                @elseif($key === 'enterprise')
                                    <a href="mailto:support@poultry.com" class="block w-full {{ $buttonColorMap[$color] }} text-white py-3 px-6 rounded-lg text-center font-semibold transition-all hover:shadow-lg">
                                        Contact Sales
                                    </a>
                                @else
                                    <!-- Paid Plans: Show both PayMongo and Test Mode buttons -->
                                    <div class="space-y-2">
                                        <!-- PayMongo Button -->
                                        <form action="{{ route('subscription.pay') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="plan" value="{{ $key }}">
                                            <button type="submit" class="w-full {{ $buttonColorMap[$color] }} text-white py-3 px-6 rounded-lg font-semibold transition-all hover:shadow-lg">
                                                Subscribe via PayMongo
                                            </button>
                                        </form>
                                        
                                        <!-- Test Mode Button (Direct Activation) -->
                                        <a href="{{ route('subscription.pay', ['plan' => $key, 'test_mode' => 'true']) }}" class="block w-full bg-purple-600 hover:bg-purple-700 text-white py-2 px-6 rounded-lg font-semibold transition-all text-sm">
                                            Activate (Test Mode)
                                        </a>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Footer Info -->
        <div class="text-center mt-12 text-gray-600">
            <p class="mb-4">Need help choosing a plan? <a href="mailto:support@poultry.com" class="text-green-600 hover:text-green-700 font-semibold">Contact our support team</a></p>
            <p class="text-sm">All plans come with a 7-day money-back guarantee</p>
        </div>
    </div>
</div>
@endsection
