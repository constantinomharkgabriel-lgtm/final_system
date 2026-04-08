@extends('farmowner.layouts.app')

@section('title', 'Inventory Management')
@section('header', 'Inventory Management')
@section('subheader', 'View and manage all your products available for sale')

@section('header-actions')
<div class="flex gap-2">
    <a href="{{ route('inventory.index', ['refresh' => 1]) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium text-sm">
        🔄 Refresh
    </a>
    <a href="{{ route('inventory.export') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-medium text-sm">
        📥 Export Report
    </a>
</div>
@endsection

@section('content')
<div>
@section('content')
<div>
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 border-l-4 border-blue-600">
            <p class="text-gray-300 text-sm mb-2">Total Items</p>
            <p class="text-3xl font-bold text-blue-600">{{ $stats['total_items'] ?? 0 }}</p>
        </div>
        
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 border-l-4 border-green-600">
            <p class="text-gray-300 text-sm mb-2">📦 Eggs Available</p>
            <p class="text-3xl font-bold text-green-600">{{ $stats['total_egg_quantity'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $stats['egg_items'] ?? 0 }} types</p>
        </div>
        
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 border-l-4 border-purple-600">
            <p class="text-gray-300 text-sm mb-2">🐔 Livestock Available</p>
            <p class="text-3xl font-bold text-purple-600">{{ $stats['total_livestock_quantity'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $stats['livestock_items'] ?? 0 }} types</p>
        </div>
        
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 border-l-4 {{ $stats['low_stock'] > 0 ? 'border-red-600' : 'border-gray-600' }}">
            <p class="text-gray-300 text-sm mb-2">⚠️ Low Stock</p>
            <p class="text-3xl font-bold {{ $stats['low_stock'] > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $stats['low_stock'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    @if($stats['low_stock'] > 0)
    <div class="mb-8 bg-red-900/30 border border-red-700 rounded-lg p-6">
        <h3 class="text-red-600 font-bold mb-4 flex items-center gap-2">🚨 Low Stock Alert</h3>
        <div class="space-y-2">
            @foreach($allInventory as $item)
                @if($item['status'] === 'low')
                <div class="flex items-center justify-between bg-gray-800 p-4 rounded border border-gray-700">
                    <div>
                        <p class="font-semibold text-white">{{ $item['name'] }}</p>
                        <p class="text-sm text-gray-300">
                            @if($item['type'] === 'egg')
                                Only {{ $item['quantity'] }} pieces remaining
                                @if($item['expiry_date'])
                                    • Expires in {{ $item['freshness_days'] }} days
                                @endif
                            @elseif($item['type'] === 'livestock')
                                Only {{ $item['quantity'] }} birds available
                            @endif
                        </p>
                    </div>
                    <div class="text-sm font-bold text-red-600 px-3 py-1 bg-red-900/50 rounded">
                        {{ $item['quantity'] }}/{{ $item['quantity_total'] }}
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    <!-- Filter Options -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 mb-8 flex gap-4 flex-wrap">
        <form method="GET" action="{{ route('inventory.index') }}" class="flex gap-4 w-full">
            <div>
                <label class="text-sm text-gray-300 block mb-1">Low Stock Threshold (%)</label>
                <input type="number" name="threshold" value="{{ $threshold }}" min="5" max="100" class="px-3 py-2 border border-gray-600 rounded bg-gray-700 text-white w-32 focus:border-blue-500">
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium">
                    Apply
                </button>
            </div>
        </form>
    </div>

    <!-- Inventory Table -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-900 border-b border-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Item</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Type</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Available</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Status</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Details</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($allInventory as $item)
                    <tr class="hover:bg-gray-700 transition">
                        <td class="px-6 py-4">
                            <p class="font-semibold text-white">{{ $item['name'] }}</p>
                            @if($item['type'] === 'egg' && isset($item['batch_id']))
                            <p class="text-xs text-gray-400">{{ $item['batch_id'] }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold 
                                {{ $item['type'] === 'egg' ? 'bg-green-900/50 text-green-400' : 'bg-purple-900/50 text-purple-400' }}">
                                {{ ucfirst($item['type']) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-lg font-bold text-white">{{ $item['quantity'] }}</p>
                            <p class="text-xs text-gray-400">{{ $item['unit'] }}</p>
                        </td>
                        <td class="px-6 py-4">
                            @if($item['status'] === 'low')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-900/50 text-red-400">
                                Low Stock
                            </span>
                            @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-900/50 text-green-400">
                                Available
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-300">
                            @if($item['type'] === 'egg')
                                @if($item['expiry_date'])
                                    Expires: {{ $item['freshness_days'] }}d
                                @endif
                                @if(isset($item['collection_date']))
                                    <div class="text-xs">{{ $item['collection_date']->format('M d') }}</div>
                                @endif
                            @elseif($item['type'] === 'livestock')
                                Ready: {{ $item['ready_date']->format('M d, Y') }}
                                @if($item['weight_kg'])
                                    <div class="text-xs">~{{ $item['weight_kg'] }} kg/bird</div>
                                @endif
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-3">
                                <a href="{{ route('inventory.show', [$item['type'], $item['id']]) }}" 
                                   class="text-blue-400 hover:text-blue-300 font-medium text-sm">
                                    View
                                </a>
                                <a href="{{ route('products.create') }}?inventory_type={{ $item['type'] }}&inventory_id={{ $item['id'] }}" 
                                   class="text-green-400 hover:text-green-300 font-medium text-sm">
                                    Create Product
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                            <p class="text-lg font-medium mb-2">No inventory items yet</p>
                            <p class="text-sm">Start by recording daily flock activities to auto-create egg inventory, or add livestock when ready for sale.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Info Box -->
    <div class="mt-8 bg-blue-900/30 border border-blue-700 rounded-lg p-6">
        <h3 class="font-semibold text-blue-400 mb-3">💡 How Inventory Works</h3>
        <ul class="text-sm text-gray-300 space-y-2 ml-4 list-disc">
            <li><strong class="text-white">Eggs:</strong> Auto-created when you record daily flock activity with eggs collected</li>
            <li><strong class="text-white">Livestock:</strong> Auto-created when livestock reaches ready-for-sale date</li>
            <li><strong class="text-white">Supplies:</strong> Can be used for vaccinations or marked for sale</li>
            <li><strong class="text-white">Low Stock Alerts:</strong> Automatically shown when inventory drops below threshold</li>
            <li><strong class="text-white">Create Products:</strong> Link products to inventory items - quantities auto-reduce when customers buy</li>
        </ul>
    </div>
</div>
@endsection
