<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings - Driver Portal</title>
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
                        <span class="ml-3 text-lg font-semibold text-gray-900">Earnings</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('driver.dashboard') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="{{ route('driver.deliveries') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                            Deliveries
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
            <!-- Earnings Summary -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Total Earnings</p>
                            <p class="text-3xl font-bold text-emerald-600 mt-2">
                                ₱{{ number_format($stats['total_earnings'], 2) }}
                            </p>
                        </div>
                        <div class="bg-emerald-100 rounded-full p-3">
                            <svg class="h-6 w-6 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Pending Earnings</p>
                            <p class="text-3xl font-bold text-blue-600 mt-2">
                                ₱{{ number_format($stats['pending_earnings'], 2) }}
                            </p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-3">
                            <svg class="h-6 w-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.5H7a1 1 0 100 2h4a1 1 0 001-1v-4.5z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Completed Deliveries</p>
                            <p class="text-3xl font-bold text-green-600 mt-2">
                                {{ $stats['completed_deliveries'] }}
                            </p>
                        </div>
                        <div class="bg-green-100 rounded-full p-3">
                            <svg class="h-6 w-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Average per Delivery</p>
                            <p class="text-3xl font-bold text-purple-600 mt-2">
                                ₱{{ number_format($stats['average_per_delivery'], 2) }}
                            </p>
                        </div>
                        <div class="bg-purple-100 rounded-full p-3">
                            <svg class="h-6 w-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Earnings History -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Recent Completed Deliveries</h2>
                
                @if($earningsHistory->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Delivery ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completed At</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rating</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($earningsHistory as $delivery)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #{{ $delivery->id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $delivery->consumer_name ?? $delivery->order->consumer_name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600">
                                            +₱{{ number_format($delivery->delivery_fee ?? 0, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            @if($delivery->completed_at)
                                                {{ $delivery->completed_at->format('M d, Y H:i') }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="flex items-center">
                                                @if($delivery->rating)
                                                    <span class="text-yellow-400 mr-1">★</span>
                                                    <span class="text-gray-900">{{ $delivery->rating }}</span>
                                                @else
                                                    <span class="text-gray-500">Not rated</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="bg-gray-50 rounded-lg p-8 text-center">
                        <p class="text-gray-600">No completed deliveries yet. Complete your first delivery to see earnings!</p>
                    </div>
                @endif
            </div>

            <!-- Earnings Tips -->
            <div class="bg-blue-50 rounded-lg shadow p-6 mt-6">
                <h3 class="text-lg font-bold text-blue-900 mb-4">💡 Tips to Increase Your Earnings</h3>
                <ul class="space-y-2 text-blue-800 text-sm">
                    <li>✓ Accept more deliveries to increase your completed count</li>
                    <li>✓ Maintain a high rating by delivering on time and in good condition</li>
                    <li>✓ Check the earnings page regularly to track your progress</li>
                    <li>✓ Complete deliveries quickly to accept more tasks</li>
                    <li>✓ Handle items with care - customers may rate based on condition</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
