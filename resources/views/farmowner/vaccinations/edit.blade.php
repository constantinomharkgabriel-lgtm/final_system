@extends('farmowner.layouts.app')

@section('title', 'Edit Vaccination')
@section('header', 'Edit Vaccination')
@section('subheader', $vaccination->name)

@section('content')
<div class="max-w-2xl">
    <form action="{{ route('vaccinations.update', $vaccination) }}" method="POST" class="bg-gray-800 border border-gray-700 rounded-lg p-6 space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Flock</label>
                <select name="flock_id" class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 bg-gray-700 text-white">
                    <option value="">Select a flock</option>
                    @foreach($flocks as $flock)
                        <option value="{{ $flock->id }}" {{ old('flock_id', $vaccination->flock_id) == $flock->id ? 'selected' : '' }}>
                            {{ $flock->batch_name }}
                        </option>
                    @endforeach
                </select>
                @error('flock_id')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Type *</label>
                <select name="type" required class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 bg-gray-700 text-white">
                    <option value="">Select type</option>
                    <option value="vaccine" {{ old('type', $vaccination->type) === 'vaccine' ? 'selected' : '' }}>Vaccine</option>
                    <option value="medication" {{ old('type', $vaccination->type) === 'medication' ? 'selected' : '' }}>Medication</option>
                    <option value="supplement" {{ old('type', $vaccination->type) === 'supplement' ? 'selected' : '' }}>Supplement</option>
                    <option value="dewormer" {{ old('type', $vaccination->type) === 'dewormer' ? 'selected' : '' }}>Dewormer</option>
                </select>
                @error('type')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Name *</label>
                <input type="text" name="name" value="{{ old('name', $vaccination->name) }}" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 bg-gray-700 text-white">
                @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Brand</label>
                <input type="text" name="brand" value="{{ old('brand', $vaccination->brand) }}"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 bg-gray-700 text-white">
                @error('brand')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Batch Number</label>
                <input type="text" name="batch_number" value="{{ old('batch_number', $vaccination->batch_number) }}"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 bg-gray-700 text-white">
                @error('batch_number')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Administration Method *</label>
                <select name="administration_method" required class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 bg-gray-700 text-white">
                    <option value="">Select method</option>
                    <option value="drinking_water" {{ old('administration_method', $vaccination->administration_method) === 'drinking_water' ? 'selected' : '' }}>Drinking Water</option>
                    <option value="injection" {{ old('administration_method', $vaccination->administration_method) === 'injection' ? 'selected' : '' }}>Injection</option>
                    <option value="spray" {{ old('administration_method', $vaccination->administration_method) === 'spray' ? 'selected' : '' }}>Spray</option>
                    <option value="eye_drop" {{ old('administration_method', $vaccination->administration_method) === 'eye_drop' ? 'selected' : '' }}>Eye Drop</option>
                    <option value="feed_mix" {{ old('administration_method', $vaccination->administration_method) === 'feed_mix' ? 'selected' : '' }}>Feed Mix</option>
                </select>
                @error('administration_method')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Dosage *</label>
                <input type="number" name="dosage" value="{{ old('dosage', $vaccination->dosage) }}" step="0.01" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 bg-gray-700 text-white">
                @error('dosage')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Dosage Unit *</label>
                <input type="text" name="dosage_unit" value="{{ old('dosage_unit', $vaccination->dosage_unit) }}" placeholder="ml, grams, etc." required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 bg-gray-700 text-white">
                @error('dosage_unit')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Date Administered *</label>
                <input type="date" name="date_administered" value="{{ old('date_administered', $vaccination->date_administered?->format('Y-m-d')) }}" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 bg-gray-700 text-white">
                @error('date_administered')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Next Due Date</label>
                <input type="date" name="next_due_date" value="{{ old('next_due_date', $vaccination->next_due_date?->format('Y-m-d')) }}"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 bg-gray-700 text-white">
                @error('next_due_date')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Birds Treated</label>
                <input type="number" name="birds_treated" value="{{ old('birds_treated', $vaccination->birds_treated) }}" min="0"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 bg-gray-700 text-white">
                @error('birds_treated')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Cost</label>
                <input type="number" name="cost" value="{{ old('cost', $vaccination->cost) }}" step="0.01" min="0"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 bg-gray-700 text-white">
                @error('cost')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Notes</label>
            <textarea name="notes" rows="3"
                class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 bg-gray-700 text-white">{{ old('notes', $vaccination->notes) }}</textarea>
            @error('notes')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="flex gap-4 pt-4">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Update Vaccination
            </button>
            <a href="{{ route('vaccinations.show', $vaccination) }}" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
