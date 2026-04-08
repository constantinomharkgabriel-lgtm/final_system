@extends('farmowner.layouts.app')

@section('title', 'Edit Flock')
@section('header', 'Edit Flock')
@section('subheader', $flock->batch_name)

@section('content')
<div class="max-w-2xl">
    <form id="flockForm" action="{{ route('flocks.update', $flock) }}" method="POST" class="bg-gray-800 border border-gray-700 rounded-lg p-6 space-y-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Batch Name *</label>
                <input type="text" name="batch_name" value="{{ old('batch_name', $flock->batch_name) }}" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 input-tracker">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Breed Type *</label>
                <input type="text" name="breed_type" value="{{ old('breed_type', $flock->breed_type) }}" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 input-tracker">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Age (weeks)</label>
                <input type="number" name="age_weeks" value="{{ old('age_weeks', $flock->age_weeks) }}" min="0"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 input-tracker">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Status *</label>
                <select name="status" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 input-tracker">
                    <option value="active" {{ old('status', $flock->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="sold" {{ old('status', $flock->status) === 'sold' ? 'selected' : '' }}>Sold</option>
                    <option value="culled" {{ old('status', $flock->status) === 'culled' ? 'selected' : '' }}>Culled</option>
                    <option value="transferred" {{ old('status', $flock->status) === 'transferred' ? 'selected' : '' }}>Transferred</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-1">Housing Location</label>
                <input type="text" name="housing_location" value="{{ old('housing_location', $flock->housing_location) }}"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 input-tracker">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-1">Notes</label>
                <textarea name="notes" rows="3"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 input-tracker">{{ old('notes', $flock->notes) }}</textarea>
            </div>
        </div>

        <div class="flex gap-4 pt-4">
            <button type="submit" id="submitBtn" disabled
                class="px-6 py-2 bg-gray-500 text-gray-300 rounded-lg cursor-not-allowed disabled:opacity-50">
                Save Record
            </button>
            <a href="{{ route('flocks.show', $flock) }}" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('flockForm');
    const submitBtn = document.getElementById('submitBtn');
    const inputs = form.querySelectorAll('.input-tracker');
    
    // Store initial values
    const initialValues = {};
    inputs.forEach(input => {
        initialValues[input.name] = input.type === 'checkbox' ? input.checked : input.value;
    });
    
    // Check for changes on input/change events
    function checkForChanges() {
        let hasChanges = false;
        inputs.forEach(input => {
            const currentValue = input.type === 'checkbox' ? input.checked : input.value;
            if (currentValue !== initialValues[input.name]) {
                hasChanges = true;
            }
        });
        
        // Enable/disable button based on changes
        if (hasChanges) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('bg-gray-500', 'text-gray-300', 'cursor-not-allowed', 'disabled:opacity-50');
            submitBtn.classList.add('bg-green-600', 'text-white', 'hover:bg-green-700', 'cursor-pointer');
        } else {
            submitBtn.disabled = true;
            submitBtn.classList.remove('bg-green-600', 'text-white', 'hover:bg-green-700', 'cursor-pointer');
            submitBtn.classList.add('bg-gray-500', 'text-gray-300', 'cursor-not-allowed', 'disabled:opacity-50');
        }
    }
    
    // Listen for input changes
    inputs.forEach(input => {
        input.addEventListener('input', checkForChanges);
        input.addEventListener('change', checkForChanges);
    });
    
    // Handle form submission
    form.addEventListener('submit', function(e) {
        if (submitBtn.disabled) {
            e.preventDefault();
            alert('No changes to save');
        }
    });
});
</script>
@endsection
