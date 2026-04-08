@extends('logistics.layouts.app')

@section('title', 'Delivery Schedule')
@section('header', 'Delivery Schedule')
@section('subheader', 'View and manage scheduled deliveries')

@section('content')
<div class="space-y-8">
    <!-- Today -->
    <div>
        <h2 class="text-xl font-semibold text-white mb-4">Today</h2>
        <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Tracking #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Recipient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-600">
                        @forelse($today as $delivery)
                        <tr class="hover:bg-gray-700">
                            <td class="px-6 py-4 font-mono text-sm">{{ $delivery->tracking_number }}</td>
                            <td class="px-6 py-4">{{ $delivery->recipient_name }}</td>
                            <td class="px-6 py-4 text-gray-300">{{ $delivery->scheduled_time_from ?? 'N/A' }}</td>
                            <td class="px-6 py-4">{{ $delivery->driver?->name ?? 'Unassigned' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    @if(in_array($delivery->status, ['completed', 'delivered'], true)) bg-green-900 text-green-300
                                    @elseif($delivery->status === 'out_for_delivery') bg-blue-900 text-blue-300
                                    @elseif($delivery->status === 'assigned') bg-purple-900 text-purple-300
                                    @elseif(in_array($delivery->status, ['preparing', 'packed'], true)) bg-yellow-900 text-yellow-300
                                    @else bg-gray-700 text-gray-300 @endif">
                                    {{ ucfirst(str_replace('_', ' ', $delivery->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('logistics.deliveries.show', $delivery) }}" class="text-blue-400 hover:text-blue-300 text-sm">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-400">No deliveries scheduled for today.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tomorrow -->
    <div>
        <h2 class="text-xl font-semibold text-white mb-4">Tomorrow</h2>
        <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Tracking #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Recipient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-600">
                        @forelse($tomorrow as $delivery)
                        <tr class="hover:bg-gray-700">
                            <td class="px-6 py-4 font-mono text-sm">{{ $delivery->tracking_number }}</td>
                            <td class="px-6 py-4">{{ $delivery->recipient_name }}</td>
                            <td class="px-6 py-4 text-gray-300">{{ $delivery->scheduled_date?->format('M d') }}</td>
                            <td class="px-6 py-4">{{ $delivery->driver?->name ?? 'Unassigned' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    @if(in_array($delivery->status, ['completed', 'delivered'], true)) bg-green-900 text-green-300
                                    @elseif($delivery->status === 'out_for_delivery') bg-blue-900 text-blue-300
                                    @elseif($delivery->status === 'assigned') bg-purple-900 text-purple-300
                                    @elseif(in_array($delivery->status, ['preparing', 'packed'], true)) bg-yellow-900 text-yellow-300
                                    @else bg-gray-700 text-gray-300 @endif">
                                    {{ ucfirst(str_replace('_', ' ', $delivery->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('logistics.deliveries.show', $delivery) }}" class="text-blue-400 hover:text-blue-300 text-sm">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-400">No deliveries scheduled for tomorrow.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Unscheduled -->
    <div>
        <h2 class="text-xl font-semibold text-white mb-4">Unscheduled Deliveries</h2>
        <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Tracking #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Recipient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-600">
                        @forelse($unscheduled as $delivery)
                        <tr class="hover:bg-gray-700">
                            <td class="px-6 py-4 font-mono text-sm">{{ $delivery->tracking_number }}</td>
                            <td class="px-6 py-4">{{ $delivery->recipient_name }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    @if(in_array($delivery->status, ['completed', 'delivered'], true)) bg-green-900 text-green-300
                                    @elseif($delivery->status === 'out_for_delivery') bg-blue-900 text-blue-300
                                    @elseif(in_array($delivery->status, ['preparing', 'packed'], true)) bg-yellow-900 text-yellow-300
                                    @else bg-gray-700 text-gray-300 @endif">
                                    {{ ucfirst(str_replace('_', ' ', $delivery->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('logistics.deliveries.edit', $delivery) }}" class="text-green-400 hover:text-green-300 text-sm">Schedule</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-400">All deliveries are scheduled.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
