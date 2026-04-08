@extends('department.layouts.app')

@section('title', 'Payroll Management')
@section('header', 'Payroll Management')

@section('sidebar-links')
    <a href="{{ route('department.finance.dashboard') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        🏠 Back to Finance Dashboard
    </a>
    <a href="{{ route('payroll.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium bg-orange-500/20 text-orange-400 border border-orange-500/30">
        👔 Payroll Records
    </a>
    @if(Auth::user()->role === 'hr')
    <a href="{{ route('payroll.create') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        ➕ Create Batch
    </a>
    @endif
@endsection

@section('content')
{{-- Stats Cards --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gray-800 border border-blue-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Total Records</p>
        <p class="text-2xl font-bold text-blue-400 mt-1">{{ $stats['total'] ?? 0 }}</p>
    </div>
    <div class="bg-gray-800 border border-yellow-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Pending Approval</p>
        <p class="text-2xl font-bold text-yellow-400 mt-1">{{ $stats['pending'] ?? 0 }}</p>
    </div>
    <div class="bg-gray-800 border border-orange-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Approved</p>
        <p class="text-2xl font-bold text-orange-400 mt-1">{{ $stats['approved'] ?? 0 }}</p>
    </div>
    <div class="bg-gray-800 border border-green-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Paid</p>
        <p class="text-2xl font-bold text-green-400 mt-1">{{ $stats['paid'] ?? 0 }}</p>
    </div>
</div>

{{-- Filters --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg p-4 mb-6">
    <form method="GET" class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Status</label>
            <select name="status" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-sm text-white">
                <option value="">All</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="finance_approved" {{ request('status') == 'finance_approved' ? 'selected' : '' }}>Finance Approved</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="released" {{ request('status') == 'released' ? 'selected' : '' }}>Released</option>
                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
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

{{-- Payroll Table --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-700/50 text-gray-400 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Period</th>
                    <th class="px-4 py-3 text-left">Payroll ID</th>
                    <th class="px-4 py-3 text-left">Net Pay</th>
                    <th class="px-4 py-3 text-left">Employee</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Created</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($payrolls as $payroll)
                <tr class="hover:bg-gray-700/30 transition">
                    <td class="px-4 py-3 text-gray-300">{{ $payroll->period_start->format('M d') }} - {{ $payroll->period_end->format('M d, Y') }}</td>
                    <td class="px-4 py-3 font-mono text-gray-300">{{ $payroll->id }}</td>
                    <td class="px-4 py-3 text-white font-medium">₱{{ number_format($payroll->net_pay, 2) }}</td>
                    <td class="px-4 py-3 text-gray-300">{{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}</td>
                    <td class="px-4 py-3">
                        @php
                            $colors = [
                                'pending' => 'bg-yellow-900 text-yellow-300',
                                'finance_approved'    => 'bg-blue-900 text-blue-300',
                                'approved' => 'bg-orange-900 text-orange-300',
                                'released' => 'bg-purple-900 text-purple-300',
                                'paid' => 'bg-green-900 text-green-300',
                            ];
                            $status = $payroll->workflow_status ?? $payroll->status;
                            $color = $colors[$status] ?? 'bg-gray-700 text-gray-300';
                        @endphp
                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold {{ $color }}">
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-400">{{ $payroll->created_at->format('M d, Y') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('payroll.show', $payroll) }}" class="text-orange-400 hover:text-orange-300 text-xs font-medium">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                        No payroll records found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pagination --}}
<div class="mt-4">
    {{ $payrolls->links('pagination::tailwind') }}
</div>
@endsection
