@extends('department.layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-6 text-orange-400">Delivery Schedule</h1>

    <div class="mb-8">
        <h2 class="text-lg font-semibold text-white mb-2">Today</h2>
        <table class="min-w-full bg-gray-800 rounded-lg overflow-hidden mb-4">
            <thead class="bg-gray-700 text-gray-400">
                <tr>
                    <th class="px-4 py-2">Tracking #</th>
                    <th class="px-4 py-2">Recipient</th>
                    <th class="px-4 py-2">Time</th>
                    <th class="px-4 py-2">Driver</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($today as $delivery)
                <tr>
                    <td class="px-4 py-2">{{ $delivery->tracking_number }}</td>
                    <td class="px-4 py-2">{{ $delivery->recipient_name }}</td>
                    <td class="px-4 py-2">{{ $delivery->scheduled_time_from ?? 'N/A' }}</td>
                    <td class="px-4 py-2">{{ $delivery->driver->name ?? 'Unassigned' }}</td>
                    <td class="px-4 py-2">{{ ucfirst($delivery->status) }}</td>
                    <td class="px-4 py-2">
                        @if(!$delivery->driver_id)
                        <form action="{{ route('deliveries.assignDriver', $delivery) }}" method="POST" class="inline">
                            @csrf
                            <select name="driver_id" class="bg-gray-900 text-white rounded px-2 py-1 text-sm">
                                <option value="">Assign Driver</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="ml-2 px-2 py-1 bg-orange-500 text-white rounded text-xs">Assign</button>
                        </form>
                        @endif
                        @if($delivery->status === 'preparing')
                        <form action="{{ route('deliveries.markPacked', $delivery) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="ml-2 px-2 py-1 bg-yellow-500 text-white rounded text-xs">Mark Packed</button>
                        </form>
                        @endif
                        @if($delivery->status === 'packed')
                        <form action="{{ route('deliveries.dispatch', $delivery) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="ml-2 px-2 py-1 bg-blue-500 text-white rounded text-xs">Dispatch</button>
                        </form>
                        @endif
                        @if($delivery->status === 'dispatched')
                        <form action="{{ route('deliveries.markDelivered', $delivery) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="ml-2 px-2 py-1 bg-green-500 text-white rounded text-xs">Mark Delivered</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-4 text-center text-gray-400">No deliveries scheduled for today.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mb-8">
        <h2 class="text-lg font-semibold text-white mb-2">Tomorrow</h2>
        <table class="min-w-full bg-gray-800 rounded-lg overflow-hidden mb-4">
            <thead class="bg-gray-700 text-gray-400">
                <tr>
                    <th class="px-4 py-2">Tracking #</th>
                    <th class="px-4 py-2">Recipient</th>
                    <th class="px-4 py-2">Time</th>
                    <th class="px-4 py-2">Driver</th>
                    <th class="px-4 py-2">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tomorrow as $delivery)
                <tr>
                    <td class="px-4 py-2">{{ $delivery->tracking_number }}</td>
                    <td class="px-4 py-2">{{ $delivery->recipient_name }}</td>
                    <td class="px-4 py-2">{{ $delivery->scheduled_time_from ?? 'N/A' }}</td>
                    <td class="px-4 py-2">{{ $delivery->driver->name ?? 'Unassigned' }}</td>
                    <td class="px-4 py-2">{{ ucfirst($delivery->status) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-4 text-center text-gray-400">No deliveries scheduled for tomorrow.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mb-8">
        <h2 class="text-lg font-semibold text-white mb-2">Unscheduled Deliveries</h2>
        <table class="min-w-full bg-gray-800 rounded-lg overflow-hidden mb-4">
            <thead class="bg-gray-700 text-gray-400">
                <tr>
                    <th class="px-4 py-2">Tracking #</th>
                    <th class="px-4 py-2">Recipient</th>
                    <th class="px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($unscheduled as $delivery)
                <tr>
                    <td class="px-4 py-2">{{ $delivery->tracking_number }}</td>
                    <td class="px-4 py-2">{{ $delivery->recipient_name }}</td>
                    <td class="px-4 py-2">
                        <form action="{{ route('deliveries.assignDriver', $delivery) }}" method="POST" class="inline">
                            @csrf
                            <select name="driver_id" class="bg-gray-900 text-white rounded px-2 py-1 text-sm">
                                <option value="">Assign Driver</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="ml-2 px-2 py-1 bg-orange-500 text-white rounded text-xs">Assign</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-4 py-4 text-center text-gray-400">No unscheduled deliveries.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mb-8">
        <h2 class="text-lg font-semibold text-white mb-2">Available Drivers</h2>
        <ul class="list-disc pl-6 text-gray-300">
            @foreach($drivers as $driver)
                <li>{{ $driver->name }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
