<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deliveries - Driver Portal</title>
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
                        <span class="ml-3 text-lg font-semibold text-gray-900">Driver Deliveries</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('driver.dashboard') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="{{ route('driver.profile') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                            Profile
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
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-gray-600 text-xs text-center">TOTAL</p>
                    <p class="text-2xl font-bold text-gray-900 text-center">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-gray-600 text-xs text-center">PENDING</p>
                    <p class="text-2xl font-bold text-gray-900 text-center">{{ $stats['pending'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-gray-600 text-xs text-center">ACCEPTED</p>
                    <p class="text-2xl font-bold text-purple-600 text-center">{{ $stats['accepted'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-gray-600 text-xs text-center">ON DELIVERY</p>
                    <p class="text-2xl font-bold text-blue-600 text-center">{{ $stats['on_delivery'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-gray-600 text-xs text-center">COMPLETED</p>
                    <p class="text-2xl font-bold text-green-600 text-center">{{ $stats['completed'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-gray-600 text-xs text-center">FAILED</p>
                    <p class="text-2xl font-bold text-red-600 text-center">{{ $stats['failed'] }}</p>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="GET" action="{{ route('driver.deliveries') }}" class="flex gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Status</label>
                        <select name="status" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm">
                            <option value="">All Deliveries</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                            <option value="on_delivery" {{ request('status') === 'on_delivery' ? 'selected' : '' }}>On Delivery</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition">
                        Filter
                    </button>
                </form>
            </div>

            <!-- Deliveries Table -->
            <div class="bg-white rounded-lg shadow">
                @if($deliveries->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($deliveries as $delivery)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #{{ $delivery->id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $delivery->consumer_name ?? $delivery->order->consumer_name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ substr($delivery->delivery_address ?? 'N/A', 0, 40) }}{{ strlen($delivery->delivery_address ?? '') > 40 ? '...' : '' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                                @if($delivery->status === 'completed') bg-green-100 text-green-800
                                                @elseif($delivery->status === 'on_delivery') bg-blue-100 text-blue-800
                                                @elseif($delivery->status === 'accepted') bg-purple-100 text-purple-800
                                                @elseif($delivery->status === 'pending') bg-yellow-100 text-yellow-800
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            @if($delivery->created_at)
                                                {{ $delivery->created_at->format('M d, Y H:i') }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="{{ route('driver.deliveries.show', $delivery) }}" class="text-blue-600 hover:text-blue-700 font-medium">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="bg-white px-6 py-4 border-t border-gray-200">
                        {{ $deliveries->links() }}
                    </div>
                @else
                    <div class="bg-gray-50 rounded-lg p-12 text-center">
                        <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p class="text-gray-600 text-lg">No deliveries found</p>
                        <p class="text-gray-500 text-sm mt-2">Check back soon for delivery opportunities!</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
