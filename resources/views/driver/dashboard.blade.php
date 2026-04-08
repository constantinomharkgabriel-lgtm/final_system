<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - Poultry System</title>
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
                        <span class="ml-3 text-lg font-semibold text-gray-900">Driver Portal</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('driver.deliveries') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                            Deliveries
                        </a>
                        <a href="{{ route('driver.earnings') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                            Earnings
                        </a>
                        <a href="{{ route('driver.profile') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                            Profile
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
            <!-- Success Message -->
            @if(session('success'))
                <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Welcome Section -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">
                            Welcome, {{ Auth::user()->name }}!
                        </h1>
                        <p class="mt-2 text-gray-600">
                            Welcome to your driver portal. Here you can manage deliveries and track your earnings.
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Driver ID</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $driver->driver_code }}</p>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Pending Deliveries</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['pending_deliveries'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Active Deliveries</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $stats['active_deliveries'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Completed Deliveries</p>
                    <p class="text-3xl font-bold text-green-600">{{ $stats['completed_deliveries'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Total Earnings</p>
                    <p class="text-3xl font-bold text-emerald-600">
                        ₱{{ number_format($stats['total_earnings'], 2) }}
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Rating</p>
                    <div class="flex items-center">
                        <span class="text-3xl font-bold text-yellow-600">{{ $stats['rating'] ?? 'N/A' }}</span>
                        <span class="text-yellow-400 ml-1">★</span>
                    </div>
                </div>
            </div>

            <!-- Recent Deliveries -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-900">Recent Deliveries</h2>
                    <a href="{{ route('driver.deliveries') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                        View All
                    </a>
                </div>

                @if($recentDeliveries->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Delivery ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($recentDeliveries as $delivery)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #{{ $delivery->id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $delivery->consumer_name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ substr($delivery->delivery_address ?? 'N/A', 0, 50) }}...
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                                @if($delivery->status === 'completed') bg-green-100 text-green-800
                                                @elseif($delivery->status === 'on_delivery') bg-blue-100 text-blue-800
                                                @elseif($delivery->status === 'accepted') bg-purple-100 text-purple-800
                                                @elseif($delivery->status === 'failed') bg-red-100 text-red-800
                                                @else bg-gray-100 text-gray-800
                                                @endif
                                            ">
                                                {{ ucfirst(str_replace('_', ' ', $delivery->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            ₱{{ number_format($delivery->delivery_fee ?? 0, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="{{ route('driver.deliveries.show', $delivery) }}" class="text-blue-600 hover:text-blue-700">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="bg-gray-50 rounded-lg p-6 text-center">
                        <p class="text-gray-600">No deliveries yet. Check back soon!</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
