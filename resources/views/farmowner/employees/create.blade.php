@extends(auth()->user()?->isHR() ? 'hr.layouts.app' : 'farmowner.layouts.app')

@section('title', 'Add Employee')
@section('header', 'Add New Employee')
@section('subheader', 'Register a new staff member')

@section('content')
<div class="max-w-3xl">
    <form action="{{ route('employees.store') }}" method="POST" class="bg-gray-800 border border-gray-700 rounded-lg p-6 space-y-6">
        @csrf
        
        <!-- Basic Info -->
        <div>
            <h4 class="font-medium text-white mb-4">Basic Information</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Employee ID</label>
                    <input type="text" value="Auto-generated on save" disabled
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-200 text-gray-700 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">First Name *</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Last Name *</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Phone (PH) <span class="text-xs text-gray-400">(testing)</span></label>
                    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="09123456789 (for testing - optional)"
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
                    <p class="text-xs text-gray-400 mt-1">💡 Enter any test number or leave blank</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Gender</label>
                    <select name="gender" class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                        <option value="">Select</option>
                        <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Employment Info -->
        <div>
            <h4 class="font-medium text-white mb-4">Employment Details</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Department *</label>
                    <select name="department" id="department-select" required class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                        <option value="">Select</option>
                        @foreach(['farm_operations', 'logistics', 'hr', 'finance', 'sales', 'admin'] as $dept)
                        <option value="{{ $dept }}" {{ old('department', 'logistics') === $dept ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $dept)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Position *</label>
                    <input type="text" name="position" value="{{ old('position', 'Driver') }}" required
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500"
                        placeholder="e.g., Farm Worker">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Employment Type *</label>
                    <select name="employment_type" required class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                        <option value="full_time" {{ old('employment_type') === 'full_time' ? 'selected' : '' }}>Full Time</option>
                        <option value="part_time" {{ old('employment_type') === 'part_time' ? 'selected' : '' }}>Part Time</option>
                        <option value="contract" {{ old('employment_type') === 'contract' ? 'selected' : '' }}>Contract</option>
                        <option value="seasonal" {{ old('employment_type') === 'seasonal' ? 'selected' : '' }}>Seasonal</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Hire Date *</label>
                    <input type="date" name="hire_date" value="{{ old('hire_date', date('Y-m-d')) }}" required
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Daily Rate (₱) <span class="text-red-400">*</span></label>
                    <input type="number" name="daily_rate" id="daily_rate" value="{{ old('daily_rate') }}" step="0.01" min="0" required
                        placeholder="Enter employee's daily rate"
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
                    <p class="text-xs text-gray-400 mt-1">💡 Philippines Standard: Monthly = Daily Rate × 22 working days</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Monthly Salary (₱) <span class="text-gray-400 text-xs">(auto-calculated)</span></label>
                    <input type="number" name="monthly_salary" id="monthly_salary" value="{{ old('monthly_salary') }}" step="0.01" min="0" readonly
                        placeholder="Auto-calculates from Daily Rate × 22"
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-700 text-gray-300 cursor-not-allowed placeholder-gray-500">
                    <p id="salary-info" class="text-xs text-gray-400 mt-1">Enter daily rate above to calculate</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Performance Rating (1-5)</label>
                    <select name="performance_rating" class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                        @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" {{ (int) old('performance_rating', 3) === $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Account Password *</label>
                    <input type="password" name="password" required
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500"
                        placeholder="Minimum 8 characters">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Confirm Password *</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500"
                        placeholder="Re-enter password">
                </div>
            </div>
        </div>

        <!-- Roles & Permissions -->
        <div>
            <h4 class="font-medium text-white mb-4">Roles & Permissions</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @php
                    $availableRoles = \App\Models\Role::all();
                    $roleDescriptions = [
                        'driver' => 'Can perform deliveries and earn commission',
                        'logistics_staff' => 'Can manage drivers and assign deliveries',
                        'hr_staff' => 'Can manage employees and attendance',
                        'finance_staff' => 'Can process payroll and finances',
                        'farm_operations' => 'Can manage farm operations',
                        'admin' => 'Full system access',
                    ];
                @endphp
                @foreach($availableRoles as $role)
                    <label class="flex items-center space-x-3 p-3 border border-gray-600 rounded-lg hover:bg-gray-700 cursor-pointer">
                        <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                            {{ collect(old('roles', ['driver']))->contains($role->name) ? 'checked' : '' }}
                            class="w-4 h-4 rounded role-checkbox"
                            data-role="{{ $role->name }}">
                        <div>
                            <div class="text-sm font-medium text-white">{{ $role->display_name }}</div>
                            <div class="text-xs text-gray-400">{{ $roleDescriptions[$role->name] ?? $role->description }}</div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Driver Details (Conditional) -->
        <div id="driver-section" class="hidden bg-blue-900 bg-opacity-20 border border-blue-700 rounded-lg p-6">
            <h4 class="font-medium text-white mb-4 flex items-center">
                <span class="text-blue-400 mr-2">🚗</span>
                Driver Details
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Vehicle Type *</label>
                    <select name="vehicle_type" class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-blue-500">
                        <option value="">Select</option>
                        <option value="motorcycle" {{ old('vehicle_type') === 'motorcycle' ? 'selected' : '' }}>Motorcycle</option>
                        <option value="tricycle" {{ old('vehicle_type') === 'tricycle' ? 'selected' : '' }}>Tricycle</option>
                        <option value="van" {{ old('vehicle_type') === 'van' ? 'selected' : '' }}>Van</option>
                        <option value="truck" {{ old('vehicle_type') === 'truck' ? 'selected' : '' }}>Truck</option>
                    </select>
                    @error('vehicle_type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Plate Number</label>
                    <input type="text" name="vehicle_plate" value="{{ old('vehicle_plate') }}" placeholder="ABC 1234"
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-blue-500">
                    @error('vehicle_plate')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Vehicle Model</label>
                    <input type="text" name="vehicle_model" value="{{ old('vehicle_model') }}" placeholder="e.g., Honda PCX, Isuzu NPR"
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-blue-500">
                    @error('vehicle_model')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">License Number</label>
                    <input type="text" name="license_number" value="{{ old('license_number') }}"
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-blue-500">
                    @error('license_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">License Expiry *</label>
                    <input type="date" name="license_expiry" value="{{ old('license_expiry') }}"
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-blue-500">
                    @error('license_expiry')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Delivery Fee (₱) *</label>
                    <input type="number" name="delivery_fee" value="{{ old('delivery_fee') }}" step="0.01" min="0" placeholder="e.g., 50.00"
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">💡 Commission earned per completed delivery</p>
                    @error('delivery_fee')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Notes</label>
                    <textarea name="driver_notes" rows="3" placeholder="e.g., Vehicle condition notes, special instructions..."
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-blue-500">{{ old('driver_notes') }}</textarea>
                    @error('driver_notes')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="flex gap-4 pt-4">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Add Employee</button>
            <a href="{{ route('employees.index') }}" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">Cancel</a>
        </div>
    </form>
</div>

<script>
    // Auto-calculate monthly salary from daily rate (22 working days - Philippines standard)
    const dailyRateInput = document.getElementById('daily_rate');
    const monthlySalaryInput = document.getElementById('monthly_salary');
    const salaryInfo = document.getElementById('salary-info');
    
    function updateMonthlySalary() {
        const dailyRate = parseFloat(dailyRateInput.value) || 0;
        if (dailyRate > 0) {
            const monthlySalary = (dailyRate * 22).toFixed(2);
            monthlySalaryInput.value = monthlySalary;
            const formatted = parseFloat(monthlySalary).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            salaryInfo.textContent = `✓ Monthly Salary: ₱${formatted} (₱${dailyRate.toFixed(2)} × 22 days)`;
            salaryInfo.classList.remove('text-gray-400');
            salaryInfo.classList.add('text-green-400');
        } else {
            monthlySalaryInput.value = '';
            salaryInfo.textContent = 'Enter daily rate above to calculate';
            salaryInfo.classList.remove('text-green-400');
            salaryInfo.classList.add('text-gray-400');
        }
    }
    
    dailyRateInput.addEventListener('input', updateMonthlySalary);
    dailyRateInput.addEventListener('change', updateMonthlySalary);
    
    // Calculate on page load (in case of old() values)
    window.addEventListener('load', updateMonthlySalary);

    // Toggle driver section visibility based on driver role checkbox OR department selection
    const driverSection = document.getElementById('driver-section');
    const roleCheckboxes = document.querySelectorAll('.role-checkbox');
    const departmentSelect = document.getElementById('department-select');
    
    function updateDriverSectionVisibility() {
        // Show driver form if either:
        // 1. Driver role is checked
        // 2. Driver department is selected
        const isDriverRoleSelected = Array.from(roleCheckboxes)
            .some(checkbox => checkbox.dataset.role === 'driver' && checkbox.checked);
        const isDriverDepartmentSelected = departmentSelect && departmentSelect.value === 'driver';
        
        if (isDriverRoleSelected || isDriverDepartmentSelected) {
            driverSection.classList.remove('hidden');
        } else {
            driverSection.classList.add('hidden');
        }
    }
    
    roleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateDriverSectionVisibility);
    });
    
    if (departmentSelect) {
        departmentSelect.addEventListener('change', updateDriverSectionVisibility);
    }
    
    // Initialize visibility on page load (in case driver role was pre-selected or department was pre-filled)
    window.addEventListener('load', updateDriverSectionVisibility);
</script>

@endsection
