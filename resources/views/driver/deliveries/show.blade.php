<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Details - Driver Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <img class="h-8 w-auto" src="/images/logo.png" alt="Logo">
                        <span class="ml-3 text-lg font-semibold text-gray-900">Delivery Details</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('driver.deliveries') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                            Back to Deliveries
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
        <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
            @if($delivery)
                <!-- Delivery Header -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">
                                Delivery #{{ $delivery->id }}
                            </h1>
                            <p class="text-gray-600 mt-2">
                                Created on {{ $delivery->created_at->format('M d, Y \a\t H:i') }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full
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
                        </div>
                    </div>
                </div>

                <!-- Delivery Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Customer Info -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Customer Information</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name</label>
                                <p class="mt-1 text-gray-900">{{ $delivery->consumer_name ?? $delivery->order->consumer_name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phone</label>
                                <p class="mt-1 text-gray-900">{{ $delivery->consumer_phone ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <p class="mt-1 text-gray-900">{{ $delivery->consumer_email ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Info -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Delivery Information</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Delivery Fee</label>
                                <p class="mt-1 text-2xl font-bold text-emerald-600">₱{{ number_format($delivery->delivery_fee ?? 0, 2) }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Scheduled Date</label>
                                <p class="mt-1 text-gray-900">
                                    @if($delivery->scheduled_date)
                                        {{ Carbon\Carbon::parse($delivery->scheduled_date)->format('M d, Y H:i') }}
                                    @else
                                        ASAP
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delivery Location -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">📍 Delivery Location</h2>
                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <p class="text-gray-900 text-md">{{ $delivery->delivery_address ?? 'No address provided' }}</p>
                        @if($delivery->latitude && $delivery->longitude)
                            <p class="text-xs text-gray-600 mt-2">
                                📌 Coordinates: {{ number_format($delivery->latitude, 6) }}, {{ number_format($delivery->longitude, 6) }}
                            </p>
                        @endif
                    </div>
                    
                    <!-- Map Display -->
                    @if($delivery->latitude && $delivery->longitude)
                    <div id="deliveryMap" style="height: 400px; border-radius: 0.5rem; border: 1px solid #d1d5db;"></div>
                    @endif
                </div>

                <!-- Items in Delivery (if order details available) -->
                @if($delivery->order && $delivery->order->items->count() > 0)
                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Items in Delivery</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($delivery->order->items as $item)
                                        <tr>
                                            <td class="px-6 py-4 text-sm text-gray-900">{{ $item->product->name ?? 'Unknown' }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-900">{{ $item->quantity }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-900">₱{{ number_format($item->price ?? 0, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Actions -->
                @if(in_array($delivery->status, ['pending', 'accepted']))
                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Actions</h2>
                        <div class="flex gap-4">
                            @if($delivery->status === 'pending')
                                <form action="{{ route('driver.deliveries.accept', $delivery) }}" method="POST" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition">
                                        Accept Delivery
                                    </button>
                                </form>
                                <form action="{{ route('driver.deliveries.reject', $delivery) }}" method="POST" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition" onclick="return confirm('Are you sure you want to reject this delivery?')">
                                        Reject Delivery
                                    </button>
                                </form>
                            @elseif($delivery->status === 'accepted')
                                <form action="{{ route('driver.deliveries.start', $delivery) }}" method="POST" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition">
                                        Start Delivery
                                    </button>
                                </form>
                                <form action="{{ route('driver.deliveries.reject', $delivery) }}" method="POST" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition" onclick="return confirm('Are you sure you want to reject this delivery?')">
                                        Reject Delivery
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @elseif($delivery->status === 'on_delivery')
                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Mark as Completed</h2>
                        <form action="{{ route('driver.deliveries.complete', $delivery) }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                                <textarea name="notes" rows="3" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm" placeholder="Any notes about the delivery..."></textarea>
                            </div>
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition">
                                Mark as Completed
                            </button>
                        </form>
                    </div>
                @endif

                <!-- Completion Info -->
                @if($delivery->status === 'completed')
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Completion Details</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Completed At</label>
                                <p class="mt-1 text-gray-900">{{ $delivery->completed_at->format('M d, Y H:i') }}</p>
                            </div>
                            @if($delivery->notes)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                                    <p class="mt-1 text-gray-900">{{ $delivery->notes }}</p>
                                </div>
                            @endif
                            <div class="pt-4 border-t border-gray-200">
                                <p class="text-sm text-green-600 font-medium">
                                    ✓ ₱{{ number_format($delivery->delivery_fee ?? 0, 2) }} earned
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <p class="text-gray-600 text-lg">Delivery not found</p>
                    <a href="{{ route('driver.deliveries') }}" class="mt-4 text-blue-600 hover:text-blue-700">
                        Back to Deliveries
                    </a>
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script>
        // Display delivery location on map
        document.addEventListener('DOMContentLoaded', function() {
            const mapContainer = document.getElementById('deliveryMap');
            if (!mapContainer) return;

            const lat = {{ $delivery->latitude ?? 'null' }};
            const lng = {{ $delivery->longitude ?? 'null' }};

            if (!lat || !lng) return;

            // Initialize map centered on delivery location
            const map = L.map('deliveryMap').setView([lat, lng], 16);

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);

            // Add marker for delivery location
            L.marker([lat, lng])
                .addTo(map)
                .bindPopup(`<strong>Delivery Location</strong><br>{{ $delivery->recipient_name ?? $delivery->consumer_name ?? 'Customer' }}<br>{{ $delivery->delivery_address }}`)
                .openPopup();
        });
    </script>
</body>
</html>
