@extends(auth()->user()?->isHR() ? 'hr.layouts.app' : 'farmowner.layouts.app')

@section('title', 'Attendance Report')
@section('header', 'Attendance Report')
@section('subheader', 'Summary of attendance from ' . $startDate->format('F d, Y') . ' to ' . $endDate->format('F d, Y'))

@section('header-actions')
<a href="{{ route('attendance.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">← Back</a>
<button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">🖨️ Print</button>
@endsection

@section('content')

<!-- Report Filters -->
<div class="bg-gray-800 border border-gray-700 rounded-lg p-4 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Start Date</label>
            <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}"
                class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">End Date</label>
            <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}"
                class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-white font-medium transition">
                🔍 Generate Report
            </button>
        </div>
    </form>
</div>

<!-- Report Summary Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 border-l-4 border-green-600">
        <p class="text-gray-400 text-xs uppercase">Total Employees</p>
        <p class="text-3xl font-bold text-green-600">{{ $employees->count() }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 border-l-4 border-blue-600">
        <p class="text-gray-400 text-xs uppercase">Total Present Days</p>
        <p class="text-3xl font-bold text-blue-600">{{ $employees->sum('present_days') }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 border-l-4 border-red-600">
        <p class="text-gray-400 text-xs uppercase">Total Absent Days</p>
        <p class="text-3xl font-bold text-red-600">{{ $employees->sum('absent_days') }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 border-l-4 border-purple-600">
        <p class="text-gray-400 text-xs uppercase">Total Hours</p>
        <p class="text-3xl font-bold text-purple-600">{{ number_format($employees->sum('total_hours'), 1) }}</p>
    </div>
</div>

<!-- Detailed Attendance Report -->
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-700">
        <h3 class="font-semibold text-lg text-white">
            📋 Employee Attendance Summary
        </h3>
        <p class="text-sm text-gray-400 mt-1">
            Period: <strong>{{ $startDate->format('M d, Y') }}</strong> to <strong>{{ $endDate->format('M d, Y') }}</strong>
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700 sticky top-0">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Position</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-300 uppercase">Present</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-300 uppercase">Absent</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-300 uppercase">Leave</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-300 uppercase">Hours</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-300 uppercase">OT Hours</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-300 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($employees as $item)
                <tr class="hover:bg-gray-700/50 transition">
                    <td class="px-6 py-4">
                        <div class="font-medium text-white">{{ $item['employee']->first_name }} {{ $item['employee']->last_name }}</div>
                        <div class="text-sm text-gray-400">ID: {{ $item['employee']->employee_id }}</div>
                    </td>
                    <td class="px-6 py-4 text-gray-300">{{ $item['employee']->position }}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-block px-3 py-1 bg-green-900 text-green-300 rounded-full text-sm font-medium">
                            {{ $item['present_days'] }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-block px-3 py-1 bg-red-900 text-red-300 rounded-full text-sm font-medium">
                            {{ $item['absent_days'] }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-block px-3 py-1 bg-yellow-900 text-yellow-300 rounded-full text-sm font-medium">
                            {{ $item['leave_days'] }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center text-white font-medium">
                        {{ number_format($item['total_hours'], 1) }} h
                    </td>
                    <td class="px-6 py-4 text-center text-white font-medium">
                        {{ number_format($item['overtime_hours'], 1) }} h
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                            $workDays = $item['present_days'] + $item['absent_days'];
                            $status = ($workDays >= 20) ? 'Ready' : 'Pending';
                            $color = ($workDays >= 20) ? 'bg-green-900 text-green-300' : 'bg-yellow-900 text-yellow-300';
                        @endphp
                        <span class="inline-block px-2 py-1 {{ $color }} rounded text-xs font-medium">
                            {{ $status }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                        📭 No attendance records found for the selected period.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Table Footer Summary -->
    <div class="bg-gray-700/50 px-6 py-4 border-t border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-gray-400 uppercase">Total Present Days</p>
                <p class="text-2xl font-bold text-green-400">{{ $employees->sum('present_days') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase">Total Absent Days</p>
                <p class="text-2xl font-bold text-red-400">{{ $employees->sum('absent_days') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase">Total Leave Days</p>
                <p class="text-2xl font-bold text-yellow-400">{{ $employees->sum('leave_days') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase">Total Overtime Hours</p>
                <p class="text-2xl font-bold text-purple-400">{{ number_format($employees->sum('overtime_hours'), 1) }} h</p>
            </div>
        </div>
    </div>
</div>

<!-- Next Steps -->
<div class="mt-6 bg-blue-900/30 border border-blue-700 rounded-lg p-4">
    <p class="text-sm text-blue-300 mb-2">
        ℹ️ <strong>Next Step:</strong> Once attendance is finalized, proceed to <strong>Payroll</strong> to generate payroll batches.
    </p>
    <a href="{{ route('payroll.create') }}" class="inline-block mt-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
        👔 Create Payroll Batch →
    </a>
</div>

@endsection

@section('print-styles')
<style>
    @media print {
        body { background: white; }
        .print\:hidden { display: none !important; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
    }
</style>
@endsection
