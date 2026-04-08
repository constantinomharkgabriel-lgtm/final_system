@extends('department.layouts.app')

@section('title', 'Income Records')
@section('header', 'Income Records')

@section('sidebar-links')
    <a href="{{ route('department.finance.dashboard') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        🏠 Back to Finance Dashboard
    </a>
    <a href="{{ route('income.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium bg-orange-500/20 text-orange-400 border border-orange-500/30">
        💰 Income Records
    </a>
    <a href="{{ route('income.create') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        ➕ Add Income
    </a>
@endsection

@section('content')
{{-- Stats Cards --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-gray-800 border border-green-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">This Month</p>
        <p class="text-2xl font-bold text-green-400 mt-1">₱{{ number_format($stats['total_this_month'] ?? 0, 2) }}</p>
    </div>
    <div class="bg-gray-800 border border-yellow-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Pending</p>
        <p class="text-2xl font-bold text-yellow-400 mt-1">₱{{ number_format($stats['pending'] ?? 0, 2) }}</p>
    </div>
    <div class="bg-gray-800 border border-blue-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Total Records</p>
        <p class="text-2xl font-bold text-blue-400 mt-1">{{ $stats['total_count'] ?? 0 }}</p>
    </div>
</div>

{{-- Filters --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg p-4 mb-6">
    <form method="GET" class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Payment Status</label>
            <select name="payment_status" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-sm text-white">
                <option value="">All</option>
                <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Partial</option>
                <option value="received" {{ request('payment_status') == 'received' ? 'selected' : '' }}>Received</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Month</label>
            <input type="month" name="month" value="{{ request('month') }}" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-sm text-white">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-orange-600 hover:bg-orange-700 rounded text-sm font-medium text-white transition">
                Filter
            </button>
        </div>
    </form>
</div>

{{-- Income Table --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-700/50 text-gray-400 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Income #</th>
                    <th class="px-4 py-3 text-left">Source</th>
                    <th class="px-4 py-3 text-left">Description</th>
                    <th class="px-4 py-3 text-left">Amount</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($incomes as $income)
                <tr class="hover:bg-gray-700/30 transition">
                    <td class="px-4 py-3 font-mono text-gray-300">{{ $income->income_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-300">{{ ucfirst($income->source ?? 'N/A') }}</td>
                    <td class="px-4 py-3 text-gray-300">{{ $income->description ?? '—' }}</td>
                    <td class="px-4 py-3 text-white font-medium">₱{{ number_format($income->amount, 2) }}</td>
                    <td class="px-4 py-3">
                        @php
                            $colors = [
                                'pending' => 'bg-yellow-900 text-yellow-300',
                                'received'    => 'bg-green-900 text-green-300',
                                'partial' => 'bg-blue-900 text-blue-300',
                            ];
                            $color = $colors[$income->payment_status] ?? 'bg-gray-700 text-gray-300';
                        @endphp
                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold {{ $color }}">
                            {{ ucfirst(str_replace('_', ' ', $income->payment_status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-400">{{ $income->income_date->format('M d, Y') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('income.show', $income) }}" class="text-orange-400 hover:text-orange-300 text-xs font-medium">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                        No income records found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pagination --}}
<div class="mt-4">
    {{ $incomes->links('pagination::tailwind') }}
</div>
@endsection
