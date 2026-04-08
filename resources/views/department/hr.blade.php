@extends('department.layouts.app')

@section('title', 'HR Dashboard')
@section('header', 'HR Dashboard')

@section('sidebar-links')
    <a href="{{ route('department.hr.dashboard') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium bg-purple-500/20 text-purple-400 border border-purple-500/30">
        🏠 Dashboard
    </a>
    <a href="{{ route('hr.users.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        👥 Department Users
    </a>
    <a href="{{ route('employees.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        👔 Employees
    </a>
    <a href="{{ route('attendance.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        📋 Attendance
    </a>
    <a href="{{ route('payroll.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        💰 Payroll
    </a>
@endsection

@section('content')
{{-- Stats Cards --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Total Employees</p>
        <p class="text-2xl font-bold text-white mt-1">{{ $stats['total_employees'] }}</p>
    </div>
    <div class="bg-gray-800 border border-green-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Active Employees</p>
        <p class="text-2xl font-bold text-green-400 mt-1">{{ $stats['active_employees'] }}</p>
    </div>
    <div class="bg-gray-800 border border-yellow-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Pending Hires</p>
        <p class="text-2xl font-bold text-yellow-400 mt-1">{{ $stats['pending_hires'] }}</p>
    </div>
    <div class="bg-gray-800 border border-blue-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Departments</p>
        <p class="text-2xl font-bold text-blue-400 mt-1">{{ $stats['departments'] }}</p>
    </div>
</div>

{{-- Quick Actions --}}
<div class="grid grid-cols-2 md:grid-cols-2 gap-4 mb-6">
    <a href="{{ route('employees.create') }}"
       class="bg-gray-800 border border-gray-700 hover:border-purple-500 rounded-lg p-4 transition group">
        <p class="text-lg font-semibold text-purple-400 group-hover:text-purple-300">➕ Add Employee</p>
        <p class="text-sm text-gray-400 mt-1">Create new employee record</p>
    </a>
    <a href="{{ route('hr.users.index') }}"
       class="bg-gray-800 border border-gray-700 hover:border-purple-500 rounded-lg p-4 transition group">
        <p class="text-lg font-semibold text-purple-400 group-hover:text-purple-300">👥 Users</p>
        <p class="text-sm text-gray-400 mt-1">Manage department staff accounts</p>
    </a>
</div>

{{-- Recent Employees Table --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-700">
        <h3 class="text-base font-semibold text-white">Recent Employees</h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-700/50 border-b border-gray-700">
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Employee ID</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Name</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Department</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Position</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Type</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Joined</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($recentEmployees as $employee)
                <tr class="hover:bg-gray-700/50 transition">
                    <td class="px-5 py-3 text-sm text-gray-300">{{ $employee->employee_id }}</td>
                    <td class="px-5 py-3 text-sm text-gray-300 font-medium">{{ $employee->first_name }} {{ $employee->last_name }}</td>
                    <td class="px-5 py-3 text-sm text-gray-300">{{ ucfirst(str_replace('_', ' ', $employee->department)) }}</td>
                    <td class="px-5 py-3 text-sm text-gray-300">{{ $employee->position ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-sm text-gray-300">{{ ucfirst(str_replace('_', ' ', $employee->employment_type ?? 'N/A')) }}</td>
                    <td class="px-5 py-3 text-sm">
                        @if($employee->status === 'active')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-900/50 text-green-400">Active</span>
                        @elseif($employee->status === 'pending')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-900/50 text-yellow-400">Pending</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-900/50 text-red-400">{{ ucfirst($employee->status) }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-sm text-gray-400">{{ $employee->created_at?->format('M d, Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-8 text-center text-gray-400">
                        No employees found. <a href="{{ route('employees.create') }}" class="text-purple-400 hover:text-purple-300">Create one</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
