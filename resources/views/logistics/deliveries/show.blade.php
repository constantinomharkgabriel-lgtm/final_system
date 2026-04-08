@extends('logistics.layouts.app')

@section('title', $delivery->tracking_number)
@section('header', 'Delivery #' . $delivery->tracking_number)
@section('subheader', 'Recipient: ' . $delivery->recipient_name)

@section('header-actions')
<div class="flex gap-2">
    @if(!in_array($delivery->status, ['completed', 'delivered', 'failed']))
    <a href="{{ route('logistics.deliveries.edit', $delivery) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Edit</a>
    @endif
    <span class="px-4 py-2 bg-purple-900 text-purple-300 rounded-lg">{{ ucfirst(str_replace('_', ' ', $delivery->status)) }}</span>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Delivery Details -->
    <div class="lg:col-span-2 space-y-4">
        <!-- Delivery Info Card -->
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold text-lg mb-4">Delivery Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 space-y-3">
                <div>
                    <p class="text-xs text-gray-400">Recipient</p>
                    <p class="font-medium text-white">{{ $delivery->recipient_name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Contact</p>
                    <p class="font-medium text-white">{{ $delivery->recipient_phone ?? 'N/A' }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-xs text-gray-400">Delivery Address</p>
                    <p class="text-white">{{ $delivery->delivery_address }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Scheduled Date</p>
                    <p class="text-white">{{ $delivery->scheduled_date?->format('M d, Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Order Number</p>
                    <p class="text-white">{{ $delivery->order?->order_number ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Delivery Location Map -->
        @if($delivery->latitude && $delivery->longitude)
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold text-lg mb-4">📍 Delivery Location</h3>
            <div id="deliveryMap" style="height: 400px; border-radius: 0.5rem; border: 1px solid #374151;"></div>
            <p class="text-xs text-gray-400 mt-2">Coordinates: {{ number_format($delivery->latitude, 6) }}, {{ number_format($delivery->longitude, 6) }}</p>
        </div>
        @endif

        <!-- Driver Assignment -->
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold text-lg mb-4">Driver Assignment</h3>
            @if($delivery->driver)
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-gray-400">Assigned Driver</p>
                    <p class="font-medium text-blue-400">
                        <a href="{{ route('logistics.drivers.show', $delivery->driver) }}" class="hover:underline">
                            {{ $delivery->driver->name }}
                        </a>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Vehicle</p>
                    <p class="text-white">{{ ucfirst($delivery->driver->vehicle_type) }} - {{ $delivery->driver->vehicle_plate ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Driver Phone</p>
                    <p class="text-white">{{ $delivery->driver->phone }}</p>
                </div>
            </div>
            @else
            <p class="text-gray-400">No driver assigned yet</p>
            @endif
        </div>

        <!-- Status Timeline -->
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold text-lg mb-4">Status History</h3>
            <div class="space-y-3 text-sm">
                <div class="flex items-center gap-3">
                    <span class="text-purple-400">✓</span>
                    <span>Current Status: <strong>{{ ucfirst(str_replace('_', ' ', $delivery->status)) }}</strong></span>
                </div>
                @if($delivery->delivered_at)
                <div class="flex items-center gap-3">
                    <span class="text-green-400">✓</span>
                    <span>Delivered at: <strong>{{ $delivery->delivered_at->format('M d, Y H:i') }}</strong></span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- COD & Payment Info -->
    <div class="lg:col-span-1">
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold text-lg mb-4">Payment Information</h3>
            <div class="space-y-4">
                <div>
                    <p class="text-xs text-gray-400">COD Amount</p>
                    <p class="text-2xl font-bold text-blue-400">₱{{ number_format($delivery->cod_amount ?? 0, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Amount Collected</p>
                    <p class="text-lg font-semibold {{ $delivery->cod_collected >= $delivery->cod_amount ? 'text-green-400' : 'text-yellow-400' }}">
                        ₱{{ number_format($delivery->cod_collected ?? 0, 2) }}
                    </p>
                </div>
                @if($delivery->cod_collected < $delivery->cod_amount)
                <div class="bg-yellow-900 border border-yellow-700 rounded p-3">
                    <p class="text-yellow-300 text-sm">
                        Pending: ₱{{ number_format($delivery->cod_amount - ($delivery->cod_collected ?? 0), 2) }}
                    </p>
                </div>
                @else
                <div class="bg-green-900 border border-green-700 rounded p-3">
                    <p class="text-green-300 text-sm">✓ Payment Complete</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
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
            .bindPopup(`<strong>Delivery Location</strong><br>{{ $delivery->recipient_name }}<br>{{ $delivery->delivery_address }}`)
            .openPopup();
    });
</script>
@endpush

@endsection
