<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Profile - Poultry System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <img class="h-8 w-auto" src="/images/logo.png" alt="Logo">
                        <span class="ml-3 text-lg font-semibold text-gray-900">Driver Profile</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('driver.dashboard') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="{{ route('driver.deliveries') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                            Deliveries
                        </a>
                        <a href="{{ route('driver.earnings') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                            Earnings
                        </a>
                        <form action="{{ route('driver.logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Personal Information -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Personal Information</h2>
                        
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Full Name</label>
                                    <p class="mt-1 text-gray-900">{{ $user->name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Email</label>
                                    <p class="mt-1 text-gray-900">{{ $user->email }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                                    <p class="mt-1 text-gray-900">{{ $driver->phone ?? 'Not provided' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Driver Code</label>
                                    <p class="mt-1 text-gray-900">{{ $driver->driver_code }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- License Information -->
                    <div class="bg-white rounded-lg shadow p-6 mt-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-6">License Information</h3>
                        
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">License Number</label>
                                    <p class="mt-1 text-gray-900">{{ $driver->license_number ?? 'Not provided' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">License Expiry</label>
                                    <p class="mt-1 text-gray-900">
                                        @if($driver->license_expiry)
                                            {{ $driver->license_expiry->format('M d, Y') }}
                                            @if($driver->is_license_expiring)
                                                <span class="text-red-600 text-sm font-medium">(Expiring Soon)</span>
                                            @endif
                                        @else
                                            Not provided
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle Information -->
                    <div class="bg-white rounded-lg shadow p-6 mt-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-6">Vehicle Information</h3>
                        
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Vehicle Type</label>
                                    <p class="mt-1 text-gray-900">{{ ucfirst($driver->vehicle_type ?? 'Not provided') }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Vehicle Plate</label>
                                    <p class="mt-1 text-gray-900">{{ $driver->vehicle_plate ?? 'Not provided' }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Vehicle Model</label>
                                    <p class="mt-1 text-gray-900">{{ $driver->vehicle_model ?? 'Not provided' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Delivery Fee</label>
                                    <p class="mt-1 text-gray-900">₱{{ number_format($driver->delivery_fee ?? 0, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Sidebar -->
                <div>
                    <!-- Verification Status -->
                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Account Status</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-gray-600">Email Verification</p>
                                <div class="mt-2 flex items-center">
                                    <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="ml-2 text-green-600 font-medium">Verified</span>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ $driver->verified_at->format('M d, Y \a\t H:i') }}
                                </p>
                            </div>

                            <div>
                                <p class="text-sm text-gray-600">Account Status</p>
                                <p class="mt-2">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        {{ ucfirst($driver->status) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Statistics</h3>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Completed Deliveries</span>
                                <span class="text-2xl font-bold text-gray-900">{{ $driver->completed_deliveries ?? 0 }}</span>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Total Earnings</span>
                                <span class="text-2xl font-bold text-emerald-600">₱{{ number_format($driver->total_earnings ?? 0, 2) }}</span>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Average Rating</span>
                                <div class="flex items-center">
                                    <span class="text-2xl font-bold text-yellow-600">{{ $driver->rating ?? 'N/A' }}</span>
                                    @if($driver->rating)
                                        <span class="text-yellow-400 ml-1">★</span>
                                    @endif
                                </div>
                            </div>

                            <div class="pt-4 border-t border-gray-200">
                                <a href="{{ route('driver.earnings') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                    View Earnings Details →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
