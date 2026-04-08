@extends('department.layouts.app')

@section('title', 'Expenses - Finance')
@section('header', 'Expense Management')

@section('sidebar-links')
    <a href="{{ route('department.finance.dashboard') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        🏠 Back to Finance Dashboard
    </a>
    <a href="{{ route('expenses.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium bg-orange-500/20 text-orange-400 border border-orange-500/30">
        💸 Expenses
    </a>
    <a href="{{ route('expenses.create') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        ➕ Add Expense
    </a>
@endsection

@section('content')
{{-- Stats Cards --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-gray-800 border border-red-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">This Month</p>
        <p class="text-2xl font-bold text-red-400 mt-1">₱{{ number_format($stats['total_this_month'], 2) }}</p>
    </div>
    <div class="bg-gray-800 border border-yellow-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Unpaid</p>
        <p class="text-2xl font-bold text-yellow-400 mt-1">₱{{ number_format($stats['unpaid'], 2) }}</p>
    </div>
    <div class="bg-gray-800 border border-blue-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Total Categories</p>
        <p class="text-2xl font-bold text-blue-400 mt-1">{{ count($stats['by_category']) }}</p>
    </div>
</div>

{{-- Filters --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg p-4 mb-6">
    <form method="GET" class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Category</label>
            <select name="category" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-sm text-white">
                <option value="">All Categories</option>
                <option value="feeds" {{ request('category') == 'feeds' ? 'selected' : '' }}>Feeds</option>
                <option value="vaccines" {{ request('category') == 'vaccines' ? 'selected' : '' }}>Vaccines</option>
                <option value="medications" {{ request('category') == 'medications' ? 'selected' : '' }}>Medications</option>
                <option value="utilities" {{ request('category') == 'utilities' ? 'selected' : '' }}>Utilities</option>
                <option value="labor" {{ request('category') == 'labor' ? 'selected' : '' }}>Labor</option>
                <option value="equipment" {{ request('category') == 'equipment' ? 'selected' : '' }}>Equipment</option>
                <option value="maintenance" {{ request('category') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                <option value="transportation" {{ request('category') == 'transportation' ? 'selected' : '' }}>Transportation</option>
                <option value="marketing" {{ request('category') == 'marketing' ? 'selected' : '' }}>Marketing</option>
                <option value="taxes" {{ request('category') == 'taxes' ? 'selected' : '' }}>Taxes</option>
                <option value="insurance" {{ request('category') == 'insurance' ? 'selected' : '' }}>Insurance</option>
                <option value="miscellaneous" {{ request('category') == 'miscellaneous' ? 'selected' : '' }}>Miscellaneous</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Payment Status</label>
            <select name="payment_status" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-sm text-white">
                <option value="">All</option>
                <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Partial</option>
                <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="overdue" {{ request('payment_status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
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

{{-- Expenses Table --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-700/50 text-gray-400 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Expense #</th>
                    <th class="px-4 py-3 text-left">Category</th>
                    <th class="px-4 py-3 text-left">Description</th>
                    <th class="px-4 py-3 text-left">Amount</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($expenses as $expense)
                <tr class="hover:bg-gray-700/30 transition">
                    <td class="px-4 py-3 font-mono text-gray-300">{{ $expense->expense_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-300">{{ ucfirst($expense->category) }}</td>
                    <td class="px-4 py-3 text-gray-300">{{ $expense->description }}</td>
                    <td class="px-4 py-3 text-white font-medium">₱{{ number_format($expense->total_amount, 2) }}</td>
                    <td class="px-4 py-3">
                        @php
                            $colors = [
                                'pending' => 'bg-yellow-900 text-yellow-300',
                                'paid'    => 'bg-green-900 text-green-300',
                                'overdue' => 'bg-red-900 text-red-300',
                                'partial' => 'bg-blue-900 text-blue-300',
                            ];
                            $color = $colors[$expense->payment_status] ?? 'bg-gray-700 text-gray-300';
                        @endphp
                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold {{ $color }}">
                            {{ ucfirst(str_replace('_', ' ', $expense->payment_status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-400">{{ $expense->expense_date->format('M d, Y') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('expenses.show', $expense) }}" class="text-orange-400 hover:text-orange-300 text-xs font-medium">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                        No expenses found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pagination --}}
<div class="mt-4">
    {{ $expenses->links('pagination::tailwind') }}
</div>
@endsection
