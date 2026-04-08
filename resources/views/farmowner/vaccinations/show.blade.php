@extends('farmowner.layouts.app')

@section('title', 'Vaccination Details')
@section('header', 'Vaccination Details')
@section('subheader', $vaccination->name)

@section('content')
<div class="max-w-4xl">
    <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700 bg-gray-900">
            <h3 class="text-lg font-semibold text-white">{{ $vaccination->name }}</h3>
            <p class="text-gray-400 text-sm">{{ $vaccination->brand }}</p>
        </div>

        <div class="grid grid-cols-2 gap-6 p-6">
            <!-- Left Column -->
            <div class="space-y-4">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Type</label>
                    <p class="text-white font-medium">{{ ucfirst(str_replace('_', ' ', $vaccination->type)) }}</p>
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-1">Flock</label>
                    <p class="text-white font-medium">{{ $vaccination->flock?->batch_name ?? '-' }}</p>
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-1">Date Administered</label>
                    <p class="text-white font-medium">{{ $vaccination->date_administered?->format('M d, Y') ?? '-' }}</p>
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-1">Next Due Date</label>
                    <p class="text-white font-medium">{{ $vaccination->next_due_date?->format('M d, Y') ?? '-' }}</p>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-4">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Administration Method</label>
                    <p class="text-white font-medium">{{ ucfirst(str_replace('_', ' ', $vaccination->administration_method ?? '-')) }}</p>
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-1">Dosage</label>
                    <p class="text-white font-medium">{{ $vaccination->dosage ?? '-' }} {{ $vaccination->dosage_unit ?? '' }}</p>
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-1">Birds Treated</label>
                    <p class="text-white font-medium">{{ $vaccination->birds_treated ?? '-' }}</p>
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-1">Cost</label>
                    <p class="text-white font-medium text-lg text-green-400">₱{{ number_format($vaccination->cost ?? 0, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Additional Details -->
        <div class="border-t border-gray-700 px-6 py-4 space-y-4">
            <div>
                <label class="block text-xs text-gray-400 mb-1">Batch Number</label>
                <p class="text-white">{{ $vaccination->batch_number ?? 'N/A' }}</p>
            </div>

            <div>
                <label class="block text-xs text-gray-400 mb-1">Administered By</label>
                <p class="text-white">{{ $vaccination->administeredBy?->name ?? '-' }}</p>
            </div>

            <div>
                <label class="block text-xs text-gray-400 mb-1">Status</label>
                <div class="inline-block">
                    @if($vaccination->status === 'completed')
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-400 border border-green-500/30">Completed</span>
                    @elseif($vaccination->status === 'scheduled')
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-500/20 text-blue-400 border border-blue-500/30">Scheduled</span>
                    @elseif($vaccination->status === 'overdue')
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-500/20 text-red-400 border border-red-500/30">Overdue</span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-500/20 text-gray-400 border border-gray-500/30">{{ ucfirst($vaccination->status) }}</span>
                    @endif
                </div>
            </div>

            @if($vaccination->notes)
            <div>
                <label class="block text-xs text-gray-400 mb-1">Notes</label>
                <p class="text-white whitespace-pre-line">{{ $vaccination->notes }}</p>
            </div>
            @endif
        </div>

        <!-- Actions -->
        <div class="border-t border-gray-700 px-6 py-4 bg-gray-900 flex gap-2">
            <a href="{{ route('vaccinations.edit', $vaccination) }}" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                Edit
            </a>
            <a href="{{ route('vaccinations.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">
                Back to List
            </a>
        </div>
    </div>
</div>
@endsection
