@extends('logistics.layouts.app')

@section('title', 'New Delivery')
@section('header', 'Create New Delivery')

@section('content')
<div class="max-w-2xl">
    <form action="{{ route('logistics.deliveries.store') }}" method="POST" class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        @csrf
        
        <h3 class="font-semibold text-lg mb-4 pb-2 border-b border-gray-600">Delivery Information</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Order *</label>
                <select name="order_id" required class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 @error('order_id') border-red-500 @enderror">
                    <option value="">Select Order</option>
                    @foreach($orders ?? [] as $order)
                        <option value="{{ $order->id }}">{{ $order->order_number }} - {{ $order->customer_name }}</option>
                    @endforeach
                </select>
                @error('order_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Scheduled Date *</label>
                <input type="date" name="scheduled_date" required class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 @error('scheduled_date') border-red-500 @enderror">
                @error('scheduled_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Assign Driver</label>
                <select name="driver_id" class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">Select Driver</option>
                    @foreach($drivers ?? [] as $driver)
                        <option value="{{ $driver->id }}">{{ $driver->name }} - {{ ucfirst($driver->vehicle_type) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Create Delivery</button>
            <a href="{{ route('logistics.deliveries.index') }}" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">Cancel</a>
        </div>
    </form>
</div>
@endsection
