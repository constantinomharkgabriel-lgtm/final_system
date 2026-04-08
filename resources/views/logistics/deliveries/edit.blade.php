@extends('logistics.layouts.app')

@section('title', 'Edit Delivery')
@section('header', 'Edit Delivery')

@section('content')
<div class="max-w-2xl">
    <form action="{{ route('logistics.deliveries.update', $delivery) }}" method="POST" class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        @csrf
        @method('PUT')
        
        <h3 class="font-semibold text-lg mb-4 pb-2 border-b border-gray-600">Delivery Information</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Tracking Number</label>
                <input type="text" value="{{ $delivery->tracking_number }}" disabled class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-700 text-gray-400">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Recipient *</label>
                <input type="text" name="recipient_name" value="{{ old('recipient_name', $delivery->recipient_name) }}" required class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 @error('recipient_name') border-red-500 @enderror">
                @error('recipient_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Recipient Phone *</label>
                <input type="text" name="recipient_phone" value="{{ old('recipient_phone', $delivery->recipient_phone) }}" required placeholder="+639123456789 or 09123456789" class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 @error('recipient_phone') border-red-500 @enderror">
                @error('recipient_phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Delivery Address *</label>
                <textarea name="delivery_address" required class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 @error('delivery_address') border-red-500 @enderror" rows="3">{{ old('delivery_address', $delivery->delivery_address) }}</textarea>
                @error('delivery_address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Scheduled Date *</label>
                <input type="date" name="scheduled_date" value="{{ old('scheduled_date', $delivery->scheduled_date?->format('Y-m-d')) }}" required class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 @error('scheduled_date') border-red-500 @enderror">
                @error('scheduled_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Assign Driver</label>
                <select name="driver_id" class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">Select Driver</option>
                    @foreach($drivers ?? [] as $driver)
                        <option value="{{ $driver->id }}" {{ old('driver_id', $delivery->driver_id) == $driver->id ? 'selected' : '' }}>
                            {{ $driver->name }} - {{ ucfirst($driver->vehicle_type) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="preparing" {{ $delivery->status === 'preparing' ? 'selected' : '' }}>Preparing</option>
                    <option value="packed" {{ $delivery->status === 'packed' ? 'selected' : '' }}>Packed</option>
                    <option value="assigned" {{ $delivery->status === 'assigned' ? 'selected' : '' }}>Assigned</option>
                    <option value="out_for_delivery" {{ $delivery->status === 'out_for_delivery' ? 'selected' : '' }}>Out for Delivery</option>
                    <option value="delivered" {{ $delivery->status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="completed" {{ $delivery->status === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="failed" {{ $delivery->status === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Update Delivery</button>
            <a href="{{ route('logistics.deliveries.show', $delivery) }}" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">Cancel</a>
        </div>
    </form>
</div>
@endsection
