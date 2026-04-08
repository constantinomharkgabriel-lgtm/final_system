@extends('department.layouts.app')

@section('title', 'Payroll Details')
@section('header', 'Payroll Details')
@section('subheader', $payroll->payroll_period)

@section('header-actions')
<div class="flex flex-wrap gap-2">
    @if(Auth::user()?->isFinance() && ($payroll->workflow_status ?? 'draft') === 'pending_finance')
    <form method="POST" action="{{ route('payroll.financeApprove', $payroll) }}">
        @csrf
        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Finance Approve</button>
    </form>
    @endif

    @if(Auth::user()?->isFinance() && ($payroll->workflow_status ?? '') === 'owner_approved')
    <form method="POST" action="{{ route('payroll.releasePayslip', $payroll) }}">
        @csrf
        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Release Payslip</button>
    </form>
    @endif

    @if(Auth::user()?->isFinance() && ($payroll->workflow_status ?? '') === 'ready_for_disbursement' && $payroll->status === 'approved')
    <form method="POST" action="{{ route('payroll.executeDisbursement', $payroll) }}" class="flex gap-2">
        @csrf
        <input
            type="text"
            name="disbursement_reference"
            placeholder="Reference # (required for non-cash)"
            class="rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white"
        >
        <button type="submit" class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700">Execute Disbursement</button>
    </form>
    @endif

    <a href="{{ route('payroll.index') }}" class="px-4 py-2 rounded-lg bg-gray-600 text-white hover:bg-gray-500">Back</a>
</div>
@endsection

@section('content')
<div class="mb-6 rounded-lg border border-blue-700 bg-blue-900/30 px-4 py-3 text-sm text-blue-200">
    Finance approves payroll, requests owner approval, then processes disbursement after owner releases the payslip.
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 rounded-lg border border-gray-700 bg-gray-800 p-6">
        <h3 class="mb-4 text-lg font-bold text-white">Payroll Summary</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><p class="text-gray-400">Payroll Period</p><p class="font-semibold text-white">{{ $payroll->payroll_period }}</p></div>
            <div><p class="text-gray-400">Status</p><p class="font-semibold text-white">{{ ucfirst($payroll->status) }}</p></div>
            <div><p class="text-gray-400">Workflow</p><p class="font-semibold text-white">{{ ucfirst(str_replace('_', ' ', $payroll->workflow_status ?? 'draft')) }}</p></div>
            <div><p class="text-gray-400">Employee</p><p class="font-semibold text-white">{{ $payroll->employee?->full_name ?? 'N/A' }}</p></div>
            <div><p class="text-gray-400">Position</p><p class="font-semibold text-white">{{ $payroll->employee?->position ?? 'N/A' }}</p></div>
            <div><p class="text-gray-400">Performance Rating</p><p class="font-semibold text-white">{{ $payroll->employee?->performance_rating ?? 3 }}/5</p></div>
            <div><p class="text-gray-400">Period Start</p><p class="font-semibold text-white">{{ $payroll->period_start?->format('M d, Y') }}</p></div>
            <div><p class="text-gray-400">Period End</p><p class="font-semibold text-white">{{ $payroll->period_end?->format('M d, Y') }}</p></div>
            <div><p class="text-gray-400">Processed By</p><p class="font-semibold text-white">{{ $payroll->processedBy?->name ?? 'N/A' }}</p></div>
            <div><p class="text-gray-400">Pay Date</p><p class="font-semibold text-white">{{ $payroll->pay_date?->format('M d, Y') ?? 'Not yet paid' }}</p></div>
            <div><p class="text-gray-400">Finance Approved By</p><p class="font-semibold text-white">{{ $payroll->financeApprovedBy?->name ?? 'Pending' }}</p></div>
            <div><p class="text-gray-400">Owner Approved By</p><p class="font-semibold text-white">{{ $payroll->ownerApprovedBy?->name ?? 'Pending' }}</p></div>
            <div><p class="text-gray-400">Payslip Released At</p><p class="font-semibold text-white">{{ $payroll->payslip_released_at?->format('M d, Y h:i A') ?? 'Not yet released' }}</p></div>
            <div><p class="text-gray-400">Prepared For Disbursement By</p><p class="font-semibold text-white">{{ $payroll->disbursementPreparedBy?->name ?? 'Pending' }}</p></div>
            <div><p class="text-gray-400">Disbursed By</p><p class="font-semibold text-white">{{ $payroll->disbursedBy?->name ?? 'Pending' }}</p></div>
            <div><p class="text-gray-400">Disbursement Reference</p><p class="font-semibold text-white">{{ $payroll->disbursement_reference ?? 'N/A' }}</p></div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-700 bg-gray-800 p-6">
        <h3 class="mb-4 text-lg font-bold text-white">Net Pay</h3>
        <p class="text-3xl font-bold text-green-500">₱{{ number_format($payroll->net_pay ?? 0, 2) }}</p>
        <div class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-gray-400">Gross Pay</span><span class="text-white">₱{{ number_format($payroll->gross_pay ?? 0, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-400">Total Deductions</span><span class="text-red-300">₱{{ number_format($payroll->total_deductions ?? 0, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-400">Hours Worked</span><span class="text-white">{{ number_format($payroll->hours_worked ?? 0, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-400">Regular Hours</span><span class="text-white">{{ number_format($payroll->regular_hours ?? 0, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-400">Overtime Hours</span><span class="text-white">{{ number_format($payroll->overtime_hours ?? 0, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-400">Hourly Rate</span><span class="text-white">₱{{ number_format($payroll->hourly_rate ?? 0, 4) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-400">Payment Method</span><span class="text-white">{{ $payroll->payment_method ? ucfirst(str_replace('_', ' ', $payroll->payment_method)) : 'Pending' }}</span></div>
        </div>
    </div>
</div>

<div class="mt-6 rounded-lg border border-gray-700 bg-gray-800 p-6">
    <h3 class="mb-4 text-lg font-bold text-white">Earnings and Deductions</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
        <div>
            <h4 class="mb-3 font-semibold text-green-400">Earnings</h4>
            <div class="space-y-2">
                <div class="flex justify-between"><span class="text-gray-400">Basic Pay</span><span class="text-white">₱{{ number_format($payroll->basic_pay ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Overtime Pay</span><span class="text-white">₱{{ number_format($payroll->overtime_pay ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Holiday Pay</span><span class="text-white">₱{{ number_format($payroll->holiday_pay ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Allowances</span><span class="text-white">₱{{ number_format($payroll->allowances ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Bonuses</span><span class="text-white">₱{{ number_format($payroll->bonuses ?? 0, 2) }}</span></div>
            </div>
        </div>
        <div>
            <h4 class="mb-3 font-semibold text-red-400">Deductions</h4>
            <div class="space-y-2">
                <div class="flex justify-between"><span class="text-gray-400">SSS</span><span class="text-white">₱{{ number_format($payroll->sss_deduction ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">PhilHealth</span><span class="text-white">₱{{ number_format($payroll->philhealth_deduction ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Pag-IBIG</span><span class="text-white">₱{{ number_format($payroll->pagibig_deduction ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Tax</span><span class="text-white">₱{{ number_format($payroll->tax_deduction ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Late Deduction</span><span class="text-white">₱{{ number_format($payroll->late_deduction ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Loan</span><span class="text-white">₱{{ number_format($payroll->loan_deduction ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Insurance</span><span class="text-white">₱{{ number_format($payroll->insurance_deduction ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Reimbursements</span><span class="text-white">₱{{ number_format($payroll->reimbursement_deduction ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Other</span><span class="text-white">₱{{ number_format($payroll->other_deductions ?? 0, 2) }}</span></div>
            </div>
        </div>
    </div>

    @if($payroll->notes)
    <div class="mt-6 rounded-lg border border-gray-700 bg-gray-900/40 p-4">
        <p class="mb-1 text-sm font-medium text-gray-300">Notes</p>
        <p class="text-sm text-gray-400">{{ $payroll->notes }}</p>
    </div>
    @endif

    <div class="mt-4 rounded-lg border border-gray-700 bg-gray-900/40 p-4 text-sm">
        <p class="mb-2 font-medium text-gray-300">Overtime Policy Applied</p>
        <ul class="space-y-1 text-gray-400">
            <li>Base OT rate starts at 1.25x hourly rate.</li>
            <li>Additional bonus is added per completed 30-minute OT block based on employee performance rating.</li>
            <li>Rating 5: +6% per 30m, Rating 4: +4%, Rating 3: +2.5%, Rating 2: +1%, Rating 1: +0%.</li>
            <li>Break time is deducted from work hours, including overnight/midnight-crossing breaks when entered.</li>
        </ul>
    </div>
</div>
@endsection
